@extends('layouts.admin')

@section('title', 'Document Requests | PUP Taguig Dental Clinic')

@section('styles')
<style>
    .document-requests-page {
        margin-left: var(--sidebar-w, 256px);
        padding: calc(var(--header-h, 70px) + 12px) 1.5rem 2rem;
        min-height: 100vh;
        background: #f6f7f9;
        overflow-x: hidden;
    }

    /* Page Banner */
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
        margin-bottom: 1.75rem;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        padding: 1.1rem 1.1rem 1rem;
        padding-top: 2.3rem;
        position: relative;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease, opacity .15s ease;
        cursor: pointer;
        text-decoration: none;
        display: block;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .07);
    }

    .stat-card:active {
        transform: translateY(0);
    }

    .stat-card.stat-active {
        border-color: currentColor;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .08);
    }

    .stat-card.total.stat-active {
        border-color: #9ca3af;
        box-shadow: 0 0 0 3px rgba(107, 114, 128, .1);
    }

    .stat-card.pending.stat-active {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .12);
    }

    .stat-card.approved.stat-active {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .12);
    }

    .stat-card.ready.stat-active {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    .stat-card.released.stat-active {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, .12);
    }

    .stat-card.rejected.stat-active {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .12);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 14px 14px 0 0;
    }

    .stat-card.total::before { background: #e5e7eb; }
    .stat-card.pending::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .stat-card.approved::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .stat-card.ready::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .stat-card.released::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
    .stat-card.rejected::before { background: linear-gradient(90deg, #ef4444, #f87171); }

    .stat-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        margin-bottom: .75rem;
    }

    .stat-card.total .stat-icon { background: #f3f4f6; color: #6b7280; }
    .stat-card.pending .stat-icon { background: #fef3c7; color: #d97706; }
    .stat-card.approved .stat-icon { background: #d1fae5; color: #059669; }
    .stat-card.ready .stat-icon { background: #dbeafe; color: #2563eb; }
    .stat-card.released .stat-icon { background: #ede9fe; color: #7c3aed; }
    .stat-card.rejected .stat-icon { background: #fee2e2; color: #dc2626; }

    .stat-val {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -.03em;
        margin-bottom: .25rem;
    }

    .stat-card.total .stat-val { color: #111827; }
    .stat-card.pending .stat-val { color: #d97706; }
    .stat-card.approved .stat-val { color: #059669; }
    .stat-card.ready .stat-val { color: #2563eb; }
    .stat-card.released .stat-val { color: #7c3aed; }
    .stat-card.rejected .stat-val { color: #dc2626; }

    .stat-lbl {
        font-size: .67rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #9ca3af;
    }

    .stat-trend {
        position: absolute;
        top: .7rem;
        right: .7rem;
        font-size: .62rem;
        font-weight: 700;
        padding: .22rem .5rem;
        border-radius: 999px;
        z-index: 2;
    }

    .stat-card.pending .stat-trend { background: #fef3c7; color: #92400e; }
    .stat-card.rejected .stat-trend { background: #fee2e2; color: #991b1b; }

    /* Toolbar */
    .toolbar {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .search-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #FAFAF9;
        border: 1.5px solid #E0DDD8;
        border-radius: 12px;
        padding: 0 14px;
        height: 38px;
        transition: border-color .2s, box-shadow .2s;
        flex: 1;
        min-width: 200px;
        max-width: 320px;
    }

    .search-wrap:focus-within {
        border-color: #8B0000;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
    }

    .search-wrap .search-icon {
        color: #8B0000;
        font-size: 13px;
        flex-shrink: 0;
        pointer-events: none;
    }

    .search-wrap input {
        border: none;
        background: none;
        outline: none;
        font-size: 13px;
        color: #333;
        width: 100%;
        padding: 0;
    }

    .search-wrap input::placeholder {
        color: #B0ABA6;
    }

    .document-request-search-row {
        display: flex;
        align-items: center;
        gap: .5rem;
        width: 100%;
        max-width: 320px;
    }

    .document-request-search-row .search-wrap {
        flex: 1 1 auto;
        min-width: 0;
        max-width: none;
    }

    .search-clear-btn {
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

    .search-clear-btn.hidden { display: none; }
    .search-clear-btn:hover { color: #991b1b; }

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

    .filter-btn {
        height: 38px;
        padding: 0 14px;
        border-radius: 12px;
        border: 1.5px solid #E0DDD8;
        background: #FAFAF9;
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all .2s;
        white-space: nowrap;
        position: relative;
    }

    .filter-btn:hover,
    .filter-btn.active {
        border-color: #8B0000;
        color: #8B0000;
        background: #fef2f2;
    }

    .filter-dot {
        position: absolute;
        top: -4px;
        right: -4px;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #8B0000;
        border: 2px solid #fff;
        display: none;
    }

    .filter-dot.visible {
        display: block;
    }

    .view-toggle {
        display: inline-flex;
        align-items: center;
        background: #FAFAF9;
        border: 1.5px solid #E0DDD8;
        border-radius: 12px;
        padding: 3px;
        gap: 3px;
        height: 38px;
    }

    .view-toggle-btn {
        width: 32px;
        height: 30px;
        padding: 0;
        border: none;
        background: transparent;
        color: #6b7280;
        border-radius: 9px;
        font-size: .82rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .15s ease;
        flex-shrink: 0;
    }

    .view-toggle-btn:hover {
        background: #f3f4f6;
        color: #8B0000;
    }

    .view-toggle-btn.active {
        background: #8B0000;
        color: #fff;
        box-shadow: 0 2px 8px rgba(139, 0, 0, .15);
    }

    /* Filter Modal */
    .filter-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 999;
        padding: 1rem;
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }

    .filter-modal-backdrop.show {
        display: flex;
    }

    .filter-modal {
        background: #fff;
        width: 100%;
        max-width: 760px;
        border-radius: 18px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, .18);
        overflow: hidden;
        border: 1px solid #ececec;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        animation: filterModalIn .2s ease;
    }

    @keyframes filterModalIn {
        from { opacity: 0; transform: scale(.97) translateY(8px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    .filter-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        background: #fafafa;
        flex-shrink: 0;
    }

    .filter-modal-title {
        display: flex;
        align-items: center;
        gap: .6rem;
        font-size: .92rem;
        font-weight: 700;
        color: #111;
    }

    .filter-modal-title-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: linear-gradient(135deg, #8B0000, #6b0000);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .72rem;
        flex-shrink: 0;
    }

    .filter-modal-close {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        border: none;
        background: #f3f4f6;
        color: #6b7280;
        font-size: .78rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: .15s ease;
        flex-shrink: 0;
    }

    .filter-modal-close:hover { background: #fee2e2; color: #dc2626; }

    .filter-modal-body {
        overflow-y: auto;
        padding: 1.25rem;
        flex: 1;
    }

    .filter-section-label {
        font-size: .67rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .09em;
        color: #9ca3af;
        margin-bottom: .6rem;
    }

    .filter-section {
        margin-bottom: 1.4rem;
    }

    .filter-section:last-child {
        margin-bottom: 0;
    }

    .filter-chip-group {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .75rem;
        border-radius: 999px;
        border: 1.5px solid #e5e7eb;
        background: #fff;
        font-size: .75rem;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: .12s ease;
        user-select: none;
    }

    .filter-chip:hover { border-color: #8B0000; color: #8B0000; background: #fff8f8; }

    .filter-chip.active {
        background: #8B0000;
        border-color: #8B0000;
        color: #fff;
    }

    .filter-chip .chip-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .filter-chip[data-value="pending"] .chip-dot { background: #f59e0b; }
    .filter-chip[data-value="approved"] .chip-dot { background: #10b981; }
    .filter-chip[data-value="ready"] .chip-dot { background: #3b82f6; }
    .filter-chip[data-value="released"] .chip-dot { background: #8b5cf6; }
    .filter-chip[data-value="rejected"] .chip-dot { background: #ef4444; }
    .filter-chip.active .chip-dot { background: rgba(255,255,255,.7); }

    .filter-sel {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        padding: .5rem .75rem;
        font-size: .82rem;
        background: #fff;
        outline: none;
        color: #374151;
        transition: border-color .15s;
    }

    .filter-sel:focus {
        border-color: #8B0000;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .08);
    }

    .filter-date-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .75rem;
    }

    .filter-date-wrap label {
        display: block;
        font-size: .72rem;
        font-weight: 600;
        color: #9ca3af;
        margin-bottom: .3rem;
    }

    .filter-date-wrap input[type="date"] {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        padding: .45rem .75rem;
        font-size: .82rem;
        background: #fff;
        color: #374151;
        outline: none;
        transition: border-color .15s;
    }

    .filter-date-wrap input[type="date"]:focus {
        border-color: #8B0000;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .08);
    }

    .filter-divider {
        border: none;
        border-top: 1px solid #f3f4f6;
        margin: 1.25rem 0;
    }

    .filter-modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .9rem 1.25rem;
        border-top: 1px solid #f3f4f6;
        background: #fafafa;
        flex-shrink: 0;
    }

    .filter-reset-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .78rem;
        font-weight: 600;
        color: #9ca3af;
        background: none;
        border: none;
        cursor: pointer;
        padding: .3rem .5rem;
        border-radius: 6px;
        transition: .12s ease;
    }

    .filter-reset-btn:hover { color: #dc2626; background: #fee2e2; }

    .filter-apply-btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .52rem 1.25rem;
        border-radius: 8px;
        background: #8B0000;
        color: #fff;
        font-size: .82rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: .15s ease;
    }

    .filter-apply-btn:hover { background: #6b0000; }

    [data-theme="dark"] .filter-modal {
        background: #161b22 !important;
        border-color: #21262d !important;
    }

    [data-theme="dark"] .filter-modal-header,
    [data-theme="dark"] .filter-modal-footer {
        background: #0d1117 !important;
        border-color: #21262d !important;
    }

    [data-theme="dark"] .filter-modal-body input[type="date"] {
        background: #0d1117;
        color: #f3f4f6;
        border-color: #21262d;
    }

    [data-theme="dark"] .filter-chip {
        background: #0d1117;
        border-color: #21262d;
        color: #9ca3af;
    }

    [data-theme="dark"] .filter-sel {
        background: #0d1117;
        border-color: #21262d;
        color: #f3f4f6;
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
    }

    .btn-primary {
        padding: .52rem 1.1rem;
        border: none;
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

    .request-view {
        width: 100%;
    }

    .request-view[hidden] {
        display: none !important;
    }

    .table-responsive-fix {
        width: 100%;
        overflow-x: hidden;
    }

    .tbl {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .tbl th,
    .tbl td {
        padding: .75rem .55rem;
        vertical-align: middle;
    }

    .tbl th:nth-child(1),
    .tbl td:nth-child(1),
    .tbl th:nth-child(3),
    .tbl td:nth-child(3),
    .tbl th:nth-child(5),
    .tbl td:nth-child(5),
    .tbl th:nth-child(6),
    .tbl td:nth-child(6),
    .tbl th:nth-child(7),
    .tbl td:nth-child(7) {
        white-space: nowrap;
    }

    .tbl th:nth-child(4),
    .tbl td:nth-child(4) {
        white-space: normal;
    }

    .tbl td:nth-child(4) {
        line-height: 1.2;
    }

    .tbl th:nth-child(1),
    .tbl td:nth-child(1) { width: 14%; }
    .tbl th:nth-child(2),
    .tbl td:nth-child(2) { width: 23%; }
    .tbl th:nth-child(3),
    .tbl td:nth-child(3) { width: 14%; }
    .tbl th:nth-child(4),
    .tbl td:nth-child(4) { width: 13%; }
    .tbl th:nth-child(5),
    .tbl td:nth-child(5) { width: 12%; }
    .tbl th:nth-child(6),
    .tbl td:nth-child(6) { width: 10%; }
    .tbl th:nth-child(7),
    .tbl td:nth-child(7) { width: 14%; }

    .cell-document,
    .cell-patient-name {
        white-space: nowrap;
    }

    .cell-patient-name {
        font-size: .78rem;
        font-weight: 600;
        white-space: normal;
        overflow: visible;
        text-overflow: unset;
        display: block;
        max-width: none;
        line-height: 1.15;
    }

    .cell-purpose {
        font-size: .78rem;
        white-space: normal;
        word-break: break-word;
        line-height: 1.2;
    }

    .tbl-wrap {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        overflow: hidden;
    }

    .tbl th {
        padding: .7rem 1rem;
        font-size: .67rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
        text-align: left;
        background: #fafafa;
        border-bottom: 1px solid #f3f4f6;
    }

    .tbl td {
        padding: .65rem .55rem;
        font-size: .8rem;
        color: #374151;
        border-bottom: 1px solid #f8f5f5;
        vertical-align: middle;
        overflow: hidden;
    }

    .tbl td > div {
        min-width: 0;
    }

    .tbl tbody tr:last-child td { border-bottom: none; }
    .tbl tbody tr:hover td { background: #fffafa; }

    .document-badge {
        display: inline-block;
        white-space: nowrap;
        background: #fef2f2;
        color: #8B0000;
        padding: .15rem .5rem;
        border-radius: 5px;
        font-size: .64rem;
        font-weight: 700;
        text-decoration: none;
        transition: .15s ease;
    }

    .document-badge:hover { background: #8B0000; color: #fff; }

    .requests-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        padding: 1rem;
    }

    .request-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 16px;
        padding: 1rem;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        display: flex;
        flex-direction: column;
        gap: .85rem;
        min-width: 0;
    }

    .request-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .06);
        border-color: #ead6d6;
    }

    .request-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
    }

    .request-card-ref {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: .72rem;
        font-weight: 800;
        color: #8B0000;
        line-height: 1.2;
        word-break: break-word;
    }

    .request-card-patient {
        display: flex;
        align-items: center;
        gap: .7rem;
        min-width: 0;
    }

    .request-card-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg,#8B0000,#6b0000);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .82rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .request-card-patient-info {
        min-width: 0;
        flex: 1;
    }

    .request-card-name {
        font-size: .84rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .request-card-id {
        font-size: .68rem;
        color: #9ca3af;
        margin-top: .15rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .request-card-meta {
        display: grid;
        grid-template-columns: 1fr;
        gap: .65rem;
    }

    .request-card-field {
        min-width: 0;
    }

    .request-card-label {
        font-size: .64rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: .28rem;
    }

    .request-card-value {
        font-size: .8rem;
        color: #374151;
        line-height: 1.35;
        min-width: 0;
    }

    .request-card-purpose {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }

    .request-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-top: .15rem;
        flex-wrap: wrap;
    }

    .request-card-actions {
        display: flex;
        align-items: center;
        gap: .35rem;
        flex-wrap: wrap;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .empty-state i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 1rem;
        color: #e5e7eb;
    }

    .tbl-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .85rem 1.25rem;
        border-top: 1px solid #f3f4f6;
        background: #fafafa;
        font-size: .78rem;
        color: #9ca3af;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .act-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: .7rem;
        transition: .12s ease;
    }

    .act-btn.view { background: #fef2f2; color: #8B0000; }
    .act-btn.view:hover { background: #8B0000; color: #fff; }
    .act-btn.tog-on { background: #f0fdf4; color: #166534; }
    .act-btn.tog-on:hover { background: #166534; color: #fff; }
    .act-btn.del { background: transparent; color: #dc2626; }
    .act-btn.del:hover { background: #fef2f2; }

    .panel-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        overflow: hidden;
        position: sticky;
        top: calc(var(--header-h, 70px) + 12px);
    }

    .panel-header {
        padding: .9rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        background: #fafafa;
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .panel-header-icon {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: linear-gradient(135deg, #8B0000, #6b0000);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    #documentRequestsFragment.is-loading {
        opacity: .6;
        pointer-events: none;
        transition: opacity .15s ease;
    }

    [data-theme="dark"] .document-requests-page {
        background: #0b1117 !important;
    }

    [data-theme="dark"] .tbl-wrap,
    [data-theme="dark"] .panel-card,
    [data-theme="dark"] .request-card {
        background: #161b22 !important;
        border-color: #21262d !important;
        box-shadow: none !important;
    }

    [data-theme="dark"] .stat-card {
        background:
            radial-gradient(circle at bottom right, rgba(255,255,255,.07), transparent 42%),
            linear-gradient(135deg, rgba(22, 27, 34, .68), rgba(17, 24, 39, .50)) !important;
        border: 1px solid rgba(255,255,255,.10) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.10),
            0 0 0 1px rgba(255,255,255,.025),
            0 8px 18px rgba(0,0,0,.16) !important;
    }

    [data-theme="dark"] .request-card:hover {
        box-shadow: 0 10px 24px rgba(0, 0, 0, .28) !important;
        border-color: #2b313a !important;
    }

    [data-theme="dark"] .stat-card:hover {
        border-color: rgba(255,255,255,.16) !important;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.13),
            0 0 18px rgba(255,255,255,.045),
            0 12px 24px rgba(0,0,0,.20) !important;
    }

    [data-theme="dark"] .stat-card.total.stat-active {
        border-color: #6b7280 !important;
        box-shadow: 0 0 0 3px rgba(107, 114, 128, .16) !important;
    }

    [data-theme="dark"] .stat-card.pending.stat-active {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .16) !important;
    }

    [data-theme="dark"] .stat-card.approved.stat-active {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .16) !important;
    }

    [data-theme="dark"] .stat-card.ready.stat-active {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .16) !important;
    }

    [data-theme="dark"] .stat-card.released.stat-active {
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, .16) !important;
    }

    [data-theme="dark"] .stat-card.rejected.stat-active {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .16) !important;
    }

    [data-theme="dark"] .stat-card.total .stat-icon {
        background: #21262d !important;
        color: #9ca3af !important;
    }

    [data-theme="dark"] .stat-card.pending .stat-icon {
        background: rgba(245, 158, 11, .12) !important;
        color: #fbbf24 !important;
    }

    [data-theme="dark"] .stat-card.approved .stat-icon {
        background: rgba(16, 185, 129, .12) !important;
        color: #34d399 !important;
    }

    [data-theme="dark"] .stat-card.ready .stat-icon {
        background: rgba(59, 130, 246, .12) !important;
        color: #60a5fa !important;
    }

    [data-theme="dark"] .stat-card.released .stat-icon {
        background: rgba(139, 92, 246, .12) !important;
        color: #a78bfa !important;
    }

    [data-theme="dark"] .stat-card.rejected .stat-icon {
        background: rgba(239, 68, 68, .12) !important;
        color: #f87171 !important;
    }

    [data-theme="dark"] .stat-card.total .stat-val {
        color: #f3f4f6 !important;
    }

    [data-theme="dark"] .stat-card.pending .stat-val {
        color: #fbbf24 !important;
    }

    [data-theme="dark"] .stat-card.approved .stat-val {
        color: #34d399 !important;
    }

    [data-theme="dark"] .stat-card.ready .stat-val {
        color: #60a5fa !important;
    }

    [data-theme="dark"] .stat-card.released .stat-val {
        color: #a78bfa !important;
    }

    [data-theme="dark"] .stat-card.rejected .stat-val {
        color: #f87171 !important;
    }

    [data-theme="dark"] .stat-lbl,
    [data-theme="dark"] .request-card-label,
    [data-theme="dark"] .request-card-id,
    [data-theme="dark"] .tbl th,
    [data-theme="dark"] .tbl-pagination,
    [data-theme="dark"] .filter-date-wrap label,
    [data-theme="dark"] .panel-header div.text-\[11px\],
    [data-theme="dark"] .empty-state,
    [data-theme="dark"] .empty-state p,
    [data-theme="dark"] .text-gray-400,
    [data-theme="dark"] .text-gray-500 {
        color: #9ca3af !important;
    }

    [data-theme="dark"] .stat-card.pending .stat-trend {
        background: rgba(245, 158, 11, .12) !important;
        color: #fbbf24 !important;
    }

    [data-theme="dark"] .stat-card.rejected .stat-trend {
        background: rgba(239, 68, 68, .12) !important;
        color: #f87171 !important;
    }

    [data-theme="dark"] .search-wrap,
    [data-theme="dark"] .view-toggle,
    [data-theme="dark"] .filter-btn {
        background: #0d1117 !important;
        border-color: #21262d !important;
        color: #9ca3af !important;
    }

    [data-theme="dark"] .search-wrap:focus-within,
    [data-theme="dark"] .filter-btn:hover,
    [data-theme="dark"] .filter-btn.active {
        border-color: #8B0000 !important;
        background: #161b22 !important;
        color: #f3f4f6 !important;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .14) !important;
    }

    [data-theme="dark"] .search-wrap input {
        color: #f3f4f6 !important;
    }

    [data-theme="dark"] .search-wrap input::placeholder {
        color: #6b7280 !important;
    }

    [data-theme="dark"] .search-wrap .search-icon,
    [data-theme="dark"] .search-wrap.voice-search-wrap .voice-search-mic {
        color: #f87171 !important;
    }

    [data-theme="dark"] .search-clear-btn {
        color: #f87171 !important;
    }

    [data-theme="dark"] .search-clear-btn:hover {
        color: #fecaca !important;
    }

    [data-theme="dark"] .search-wrap.voice-search-wrap [data-voice-status] {
        background: #161b22 !important;
        border-color: #2b313a !important;
        box-shadow: none !important;
    }

    [data-theme="dark"] .search-wrap.voice-search-wrap [data-voice-status].is-listening {
        color: #93c5fd !important;
        border-color: #1d4ed8 !important;
        background: rgba(29, 78, 216, .14) !important;
    }

    [data-theme="dark"] .search-wrap.voice-search-wrap [data-voice-status].is-error {
        color: #fca5a5 !important;
        border-color: #b91c1c !important;
        background: rgba(185, 28, 28, .14) !important;
    }

    [data-theme="dark"] .search-wrap.voice-search-wrap [data-voice-status].is-success {
        color: #86efac !important;
        border-color: #166534 !important;
        background: rgba(22, 101, 52, .14) !important;
    }

    [data-theme="dark"] .filter-modal-close {
        background: rgba(255,255,255,.06) !important;
        color: #fca5a5 !important;
        border: 1px solid rgba(255,255,255,.12);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }

    [data-theme="dark"] .filter-modal-close:hover {
        background: rgba(139, 0, 0, .9) !important;
        color: #fff !important;
        border-color: rgba(139, 0, 0, .9);
        box-shadow: 0 0 14px rgba(139, 0, 0, .45);
    }

    [data-theme="dark"] #panelRefNo {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .view-toggle-btn {
        color: #9ca3af !important;
    }

    [data-theme="dark"] .view-toggle-btn:hover {
        background: #1c2128 !important;
        color: #f3f4f6 !important;
    }

    [data-theme="dark"] .view-toggle-btn.active {
        background: #8B0000 !important;
        color: #fff !important;
    }

    [data-theme="dark"] .btn-secondary {
        background: #161b22 !important;
        color: #e5e7eb !important;
        border-color: #2b313a !important;
    }

    [data-theme="dark"] .btn-secondary:hover {
        background: #1c2128 !important;
    }

    [data-theme="dark"] .btn-primary {
        background: #8B0000 !important;
        color: #fff !important;
    }

    [data-theme="dark"] .btn-primary:hover {
        background: #6b0000 !important;
    }

    [data-theme="dark"] .tbl th {
        background: #0d1117 !important;
        border-bottom-color: #21262d !important;
    }

    [data-theme="dark"] .tbl td {
        color: #d1d5db !important;
        border-bottom-color: #1c2128 !important;
    }

    [data-theme="dark"] .tbl tbody tr:hover td {
        background: #1c2128 !important;
    }

    [data-theme="dark"] .cell-patient-name,
    [data-theme="dark"] .request-card-name,
    [data-theme="dark"] .request-card-ref,
    [data-theme="dark"] .panel-header .font-bold {
        color: #f3f4f6 !important;
    }

    [data-theme="dark"] #panelRefNo {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .cell-purpose,
    [data-theme="dark"] .request-card-value {
        color: #d1d5db !important;
    }

    [data-theme="dark"] .document-badge {
        background: rgba(139, 0, 0, .14) !important;
        color: #fca5a5 !important;
    }

    [data-theme="dark"] .document-badge:hover {
        background: #8B0000 !important;
        color: #fff !important;
    }

    [data-theme="dark"] .request-card-avatar,
    [data-theme="dark"] .panel-header-icon {
        background: linear-gradient(135deg, #8B0000, #6b0000) !important;
        color: #fff !important;
    }

    [data-theme="dark"] .tbl-pagination {
        background: #0d1117 !important;
        border-top-color: #21262d !important;
    }

    [data-theme="dark"] .panel-header {
        background: #0d1117 !important;
        border-bottom-color: #21262d !important;
    }

    [data-theme="dark"] #panelBody {
        color: #d1d5db !important;
    }

    [data-theme="dark"] #panelFoot {
        background: #0d1117 !important;
        border-top-color: #21262d !important;
    }

    [data-theme="dark"] #panelBody [style*="background:#fef2f2"] {
        background: rgba(139, 0, 0, .12) !important;
        border-color: rgba(139, 0, 0, .24) !important;
    }

    [data-theme="dark"] #panelBody [style*="color:#111"] {
        color: #f3f4f6 !important;
    }

    [data-theme="dark"] #panelBody [style*="color:#374151"] {
        color: #d1d5db !important;
    }

    [data-theme="dark"] #panelBody [style*="color:#9ca3af"] {
        color: #9ca3af !important;
    }

    [data-theme="dark"] #panelBody [style*="border-left:2px solid #f0eaea"] {
        border-left-color: #2b313a !important;
    }

    [data-theme="dark"] .act-btn.view {
        background: rgba(139, 0, 0, .12) !important;
        color: #fca5a5 !important;
    }

    [data-theme="dark"] .act-btn.view:hover {
        background: #8B0000 !important;
        color: #fff !important;
    }

    [data-theme="dark"] .act-btn.tog-on {
        background: rgba(22, 101, 52, .14) !important;
        color: #86efac !important;
    }

    [data-theme="dark"] .act-btn.tog-on:hover {
        background: #166534 !important;
        color: #fff !important;
    }

    [data-theme="dark"] .act-btn.del {
        color: #f87171 !important;
    }

    [data-theme="dark"] .act-btn.del:hover {
        background: rgba(239, 68, 68, .12) !important;
    }

    [data-theme="dark"] .empty-state i {
        color: #374151 !important;
    }

    @media (max-width: 1280px) {
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 1024px) {
        .content-grid { grid-template-columns: 1fr !important; }
        .panel-card { position: static; }
        .requests-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 900px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 900px) {
        .document-requests-page {
            margin-left: 0;
            padding: calc(var(--header-h, 70px) + 12px) 1rem 2rem;
        }

        .search-wrap {
            max-width: 100%;
            width: 100%;
            height: 42px;
        }

        .toolbar { gap: .5rem; }

        .filter-btn {
            height: 42px;
            flex: 1;
            justify-content: center;
        }

        .page-banner {
            border-radius: 14px;
            padding: 1.1rem 1.1rem 1.4rem;
        }

        .page-title { font-size: 1.45rem; }
        .page-banner-inner { flex-direction: column; gap: .6rem; }
        .filter-date-row { grid-template-columns: 1fr; }

        .view-toggle {
            height: 42px;
            width: auto;
        }

        .view-toggle-btn {
            width: 36px;
            height: 34px;
        }

        .requests-grid {
            grid-template-columns: 1fr;
            padding: .85rem;
            gap: .85rem;
        }
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 767px) {
        #documentRequestListView {
            display: none !important;
        }

        #documentRequestGridView {
            display: block !important;
        }

        #documentRequestViewToggle {
            display: none !important;
        }
    }
</style>
@endsection

@section('content')
<div class="document-requests-page">
    <main class="w-full">
        <div class="max-w-[1280px] mx-auto">

            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Document Requests</h1>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('admin.document-requests.export') }}" class="btn-secondary">
                            <i class="fa-solid fa-file-export text-xs"></i>
                            Export CSV
                        </a>
                        <a href="{{ route('admin.document-requests.print-queue') }}" class="btn-primary">
                            <i class="fa-solid fa-print text-xs"></i>
                            Print Queue
                        </a>
                    </div>
                </div>
            </div>

            @php
                $_s = $stats ?? [];
                $_total    = $_s['total']    ?? 0;
                $_pending  = $_s['pending']  ?? 0;
                $_approved = $_s['approved'] ?? 0;
                $_ready    = $_s['ready']    ?? 0;
                $_released = $_s['released'] ?? 0;
                $_rejected = $_s['rejected'] ?? 0;
                $currentQuery = request()->only(['status', 'type', 'sort', 'date_from', 'date_to']);

                $activeFilterCount = (request('status') ? 1 : 0)
                    + (request('type') ? 1 : 0)
                    + (request('sort') && request('sort') !== 'newest' ? 1 : 0)
                    + (request('date_from') ? 1 : 0)
                    + (request('date_to') ? 1 : 0);
            @endphp

            <div id="documentRequestsFragment">

                <div class="stats-grid">
                    <a href="{{ route('admin.document-requests.index', array_filter(array_merge($currentQuery, ['status' => null]))) }}"
                       class="stat-card total js-ajax-nav {{ !request('status') ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div class="stat-val">{{ $_total }}</div>
                        <div class="stat-lbl">Total Requests</div>
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'pending'])) }}"
                       class="stat-card pending js-ajax-nav {{ request('status') === 'pending' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                        <div class="stat-val">{{ $_pending }}</div>
                        <div class="stat-lbl">Pending Review</div>
                        @if($_pending > 0)<span class="stat-trend">Needs action</span>@endif
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'approved'])) }}"
                       class="stat-card approved js-ajax-nav {{ request('status') === 'approved' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-val">{{ $_approved }}</div>
                        <div class="stat-lbl">Approved</div>
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'ready'])) }}"
                       class="stat-card ready js-ajax-nav {{ request('status') === 'ready' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-file-circle-check"></i></div>
                        <div class="stat-val">{{ $_ready }}</div>
                        <div class="stat-lbl">Ready for Pickup</div>
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'released'])) }}"
                       class="stat-card released js-ajax-nav {{ request('status') === 'released' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-paper-plane"></i></div>
                        <div class="stat-val">{{ $_released }}</div>
                        <div class="stat-lbl">Released</div>
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'rejected'])) }}"
                       class="stat-card rejected js-ajax-nav {{ request('status') === 'rejected' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-ban"></i></div>
                        <div class="stat-val">{{ $_rejected }}</div>
                        <div class="stat-lbl">Rejected</div>
                        @if($_rejected > 0)<span class="stat-trend">Review needed</span>@endif
                    </a>
                </div>

                <div class="toolbar">
                    <div class="document-request-search-row">
                        <div class="search-wrap">
                            <i class="fa fa-search search-icon"></i>
                            <input
                                type="text"
                                id="documentRequestSearch"
                                placeholder="Search name/ID..."
                                value=""
                                autocomplete="off"
                            >
                        </div>
                        <button type="button" id="documentRequestClearBtn" class="search-clear-btn hidden" title="Clear">Clear</button>
                    </div>

                    <button
                        type="button"
                        id="filterModalOpenBtn"
                        class="filter-btn {{ $activeFilterCount > 0 ? 'active' : '' }}"
                    >
                        <i class="fa-solid fa-sliders"></i>
                        Filter
                        <span class="filter-dot {{ $activeFilterCount > 0 ? 'visible' : '' }}"></span>
                    </button>

                    <div class="view-toggle" id="documentRequestViewToggle">
                        <button type="button" class="view-toggle-btn active" data-view="list" id="listViewBtn" title="List view" aria-label="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="view-toggle-btn" data-view="grid" id="gridViewBtn" title="Grid view" aria-label="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>

                    <form
                        method="GET"
                        action="{{ route('admin.document-requests.index') }}"
                        class="js-ajax-filter-form"
                        id="documentRequestsFilterForm"
                        style="display: none;"
                    >
                        <input type="hidden" name="status"    id="hiddenStatus"   value="{{ request('status') }}">
                        <input type="hidden" name="type"      id="hiddenType"     value="{{ request('type') }}">
                        <input type="hidden" name="date_from" id="hiddenDateFrom" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to"   id="hiddenDateTo"   value="{{ request('date_to') }}">
                        <input type="hidden" name="sort"      id="hiddenSort"     value="{{ request('sort', 'newest') }}">
                    </form>
                </div>

                <div class="filter-modal-backdrop" id="filterModalBackdrop">
                    <div class="filter-modal" role="dialog" aria-modal="true" aria-label="Filter requests">

                        <div class="filter-modal-header">
                            <div class="filter-modal-title">
                                <div class="filter-modal-title-icon">
                                    <i class="fa-solid fa-sliders"></i>
                                </div>
                                Filter Requests
                            </div>
                            <button type="button" class="filter-modal-close" id="filterModalCloseBtn" title="Close">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="filter-modal-body">

                            <div class="filter-section">
                                <div class="filter-section-label">Status</div>
                                <div class="filter-chip-group" id="filterStatusChips">
                                    <button type="button" class="filter-chip {{ !request('status') ? 'active' : '' }}" data-value="">
                                        All
                                    </button>
                                    @foreach([
                                        'pending'  => 'Pending',
                                        'approved' => 'Approved',
                                        'ready'    => 'Ready for Pickup',
                                        'released' => 'Released',
                                        'rejected' => 'Rejected',
                                    ] as $val => $label)
                                        <button
                                            type="button"
                                            class="filter-chip {{ request('status') === $val ? 'active' : '' }}"
                                            data-value="{{ $val }}"
                                        >
                                            <span class="chip-dot"></span>
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="filter-divider">

                            <div class="filter-section">
                                <div class="filter-section-label">Document Type</div>
                                <select class="filter-sel" id="filterTypeSelect">
                                    <option value="">All Types</option>
                                    <option value="dental records"          {{ request('type') === 'dental records' ? 'selected' : '' }}>Dental Records</option>
                                    <option value="medical records"         {{ request('type') === 'medical records' ? 'selected' : '' }}>Medical Records</option>
                                    <option value="dental clearance"        {{ request('type') === 'dental clearance' ? 'selected' : '' }}>Dental Clearance</option>
                                    <option value="annual dental clearance" {{ request('type') === 'annual dental clearance' ? 'selected' : '' }}>Annual Dental Clearance</option>
                                </select>
                            </div>

                            <hr class="filter-divider">

                            <div class="filter-section">
                                <div class="filter-section-label">Date Range</div>
                                <div class="filter-date-row">
                                    <div class="filter-date-wrap">
                                        <label for="filterDateFrom">From</label>
                                        <input type="date" id="filterDateFrom" value="{{ request('date_from') }}">
                                    </div>
                                    <div class="filter-date-wrap">
                                        <label for="filterDateTo">To</label>
                                        <input type="date" id="filterDateTo" value="{{ request('date_to') }}">
                                    </div>
                                </div>
                            </div>

                            <hr class="filter-divider">

                            <div class="filter-section">
                                <div class="filter-section-label">Sort Order</div>
                                <div class="filter-chip-group" id="filterSortChips">
                                    <button type="button"
                                        class="filter-chip {{ request('sort', 'newest') === 'newest' ? 'active' : '' }}"
                                        data-value="newest">
                                        <i class="fa-solid fa-arrow-down-wide-short" style="font-size:.65rem;"></i>
                                        Newest First
                                    </button>
                                    <button type="button"
                                        class="filter-chip {{ request('sort') === 'oldest' ? 'active' : '' }}"
                                        data-value="oldest">
                                        <i class="fa-solid fa-arrow-up-wide-short" style="font-size:.65rem;"></i>
                                        Oldest First
                                    </button>
                                    <button type="button"
                                        class="filter-chip {{ request('sort') === 'alpha' ? 'active' : '' }}"
                                        data-value="alpha">
                                        <i class="fa-solid fa-arrow-down-a-z" style="font-size:.65rem;"></i>
                                        Alphabetical
                                    </button>
                                </div>
                            </div>

                        </div>

                        <div class="filter-modal-footer">
                            <button type="button" class="filter-reset-btn" id="filterResetBtn">
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                                Reset all filters
                            </button>
                            <button type="button" class="filter-apply-btn" id="filterApplyBtn">
                                <i class="fa-solid fa-check text-xs"></i>
                                Apply Filters
                            </button>
                        </div>

                    </div>
                </div>

                <div class="content-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:1rem;align-items:start;">

                    <div class="tbl-wrap">
                        @if(empty($requests) || $requests->isEmpty())
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <p class="font-semibold text-gray-500 mb-1">No requests found</p>
                                <p class="text-sm">Try adjusting your filters.</p>
                            </div>
                        @else
                            <div class="request-view" id="documentRequestListView">
                                <div class="table-responsive-fix">
                                    <table class="tbl">
                                        <thead>
                                            <tr>
                                                <th>Ref No.</th>
                                                <th>Patient</th>
                                                <th>Document</th>
                                                <th>Purpose</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($requests as $req)
                                                @php
                                                    $sBg = [
                                                        'pending'  => '#fef3c7',
                                                        'approved' => '#d1fae5',
                                                        'ready'    => '#dbeafe',
                                                        'released' => '#ede9fe',
                                                        'rejected' => '#fef2f2',
                                                    ];
                                                    $sTx = [
                                                        'pending'  => '#92400e',
                                                        'approved' => '#065f46',
                                                        'ready'    => '#1e40af',
                                                        'released' => '#5b21b6',
                                                        'rejected' => '#8B0000',
                                                    ];
                                                    $patientName      = $req->patient->name ?? 'Unknown Patient';
                                                    $patientStudentId = $req->patient->id ?? 'No ID';
                                                    $patientInitial   = strtoupper(substr($patientName, 0, 1));
                                                @endphp
                                                <tr
                                                    class="document-request-row"
                                                    data-reference="{{ strtolower($req->reference_number) }}"
                                                    data-patient="{{ strtolower($patientName) }}"
                                                    data-student="{{ strtolower($patientStudentId) }}"
                                                    data-document="{{ strtolower(str_replace('_', ' ', $req->document_type)) }}"
                                                    data-purpose="{{ strtolower($req->purpose ?? '') }}"
                                                    data-status="{{ strtolower($req->status) }}"
                                                >
                                                    <td>
                                                        <span class="font-mono text-xs font-bold text-[#8B0000]">
                                                            {{ $req->reference_number }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div class="flex items-center gap-2">
                                                            <div style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#6b0000);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0;">
                                                                {{ $patientInitial }}
                                                            </div>
                                                            <div>
                                                                <div class="font-semibold text-gray-800 cell-patient-name">
                                                                    {{ $patientName }}
                                                                </div>
                                                                <div class="text-[10px] text-gray-400 whitespace-nowrap">
                                                                    {{ $patientStudentId }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="cell-document">
                                                        <a href="{{ route('admin.document-requests.index', array_merge(request()->only(['status', 'sort', 'date_from', 'date_to']), ['type' => $req->document_type])) }}"
                                                           class="document-badge js-ajax-nav">
                                                            {{ ucwords(str_replace('_', ' ', $req->document_type)) }}
                                                        </a>
                                                    </td>

                                                    <td class="text-xs text-gray-500 cell-purpose">{{ $req->purpose ?: '—' }}</td>

                                                    <td class="text-xs text-gray-400 whitespace-nowrap" style="overflow: visible; text-overflow: unset;">
                                                        {{ $req->created_at->format('M d, Y') }}
                                                    </td>

                                                    <td>
                                                        <span style="background:{{ $sBg[$req->status] ?? '#f3f4f6' }};color:{{ $sTx[$req->status] ?? '#6b7280' }};padding:.2rem .65rem;border-radius:999px;font-size:.68rem;font-weight:700;display:inline-block;">
                                                            {{ ucfirst($req->status) }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div class="flex items-center gap-1">
                                                            <button type="button" class="act-btn view" onclick="openPanel({{ $req->id }})" title="View">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </button>
                                                            @if($req->status === 'pending')
                                                                <form action="{{ route('admin.document-requests.approve', $req) }}" method="POST" style="display:inline;">
                                                                    @csrf @method('PATCH')
                                                                    <button type="submit" class="act-btn tog-on" title="Approve">
                                                                        <i class="fa-solid fa-check"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @if(in_array($req->status, ['approved', 'ready']))
                                                                <form action="{{ route('admin.document-requests.release', $req) }}" method="POST" style="display:inline;">
                                                                    @csrf @method('PATCH')
                                                                    <button type="submit" class="act-btn" style="background:#dbeafe;color:#1e40af;" title="Release">
                                                                        <i class="fa-solid fa-paper-plane"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @if(in_array($req->status, ['pending', 'approved']))
                                                                <form action="{{ route('admin.document-requests.reject', $req) }}" method="POST" style="display:inline;" onsubmit="return confirm('Reject this request?')">
                                                                    @csrf @method('PATCH')
                                                                    <button type="submit" class="act-btn del" title="Reject">
                                                                        <i class="fa-solid fa-xmark"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="request-view" id="documentRequestGridView" hidden>
                                <div class="requests-grid">
                                    @foreach($requests as $req)
                                        @php
                                            $sBg = [
                                                'pending'  => '#fef3c7',
                                                'approved' => '#d1fae5',
                                                'ready'    => '#dbeafe',
                                                'released' => '#ede9fe',
                                                'rejected' => '#fef2f2',
                                            ];
                                            $sTx = [
                                                'pending'  => '#92400e',
                                                'approved' => '#065f46',
                                                'ready'    => '#1e40af',
                                                'released' => '#5b21b6',
                                                'rejected' => '#8B0000',
                                            ];
                                            $patientName      = $req->patient->name ?? 'Unknown Patient';
                                            $patientStudentId = $req->patient->id ?? 'No ID';
                                            $patientInitial   = strtoupper(substr($patientName, 0, 1));
                                        @endphp

                                        <div
                                            class="request-card document-request-row"
                                            data-reference="{{ strtolower($req->reference_number) }}"
                                            data-patient="{{ strtolower($patientName) }}"
                                            data-student="{{ strtolower($patientStudentId) }}"
                                            data-document="{{ strtolower(str_replace('_', ' ', $req->document_type)) }}"
                                            data-purpose="{{ strtolower($req->purpose ?? '') }}"
                                            data-status="{{ strtolower($req->status) }}"
                                        >
                                            <div class="request-card-top">
                                                <div class="request-card-ref">{{ $req->reference_number }}</div>
                                                <span style="background:{{ $sBg[$req->status] ?? '#f3f4f6' }};color:{{ $sTx[$req->status] ?? '#6b7280' }};padding:.2rem .65rem;border-radius:999px;font-size:.68rem;font-weight:700;display:inline-block;white-space:nowrap;">
                                                    {{ ucfirst($req->status) }}
                                                </span>
                                            </div>

                                            <div class="request-card-patient">
                                                <div class="request-card-avatar">{{ $patientInitial }}</div>
                                                <div class="request-card-patient-info">
                                                    <div class="request-card-name">{{ $patientName }}</div>
                                                    <div class="request-card-id">{{ $patientStudentId }}</div>
                                                </div>
                                            </div>

                                            <div class="request-card-meta">
                                                <div class="request-card-field">
                                                    <div class="request-card-label">Document</div>
                                                    <div class="request-card-value">
                                                        <a href="{{ route('admin.document-requests.index', array_merge(request()->only(['status', 'sort', 'date_from', 'date_to']), ['type' => $req->document_type])) }}"
                                                           class="document-badge js-ajax-nav">
                                                            {{ ucwords(str_replace('_', ' ', $req->document_type)) }}
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="request-card-field">
                                                    <div class="request-card-label">Purpose</div>
                                                    <div class="request-card-value request-card-purpose">
                                                        {{ $req->purpose ?: '—' }}
                                                    </div>
                                                </div>

                                                <div class="request-card-field">
                                                    <div class="request-card-label">Date</div>
                                                    <div class="request-card-value">
                                                        {{ $req->created_at->format('M d, Y') }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="request-card-footer">
                                                <button type="button" class="act-btn view" onclick="openPanel({{ $req->id }})" title="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>

                                                <div class="request-card-actions">
                                                    @if($req->status === 'pending')
                                                        <form action="{{ route('admin.document-requests.approve', $req) }}" method="POST" style="display:inline;">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="act-btn tog-on" title="Approve">
                                                                <i class="fa-solid fa-check"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if(in_array($req->status, ['approved', 'ready']))
                                                        <form action="{{ route('admin.document-requests.release', $req) }}" method="POST" style="display:inline;">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="act-btn" style="background:#dbeafe;color:#1e40af;" title="Release">
                                                                <i class="fa-solid fa-paper-plane"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if(in_array($req->status, ['pending', 'approved']))
                                                        <form action="{{ route('admin.document-requests.reject', $req) }}" method="POST" style="display:inline;" onsubmit="return confirm('Reject this request?')">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="act-btn del" title="Reject">
                                                                <i class="fa-solid fa-xmark"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div id="documentRequestClientEmpty" class="empty-state" style="display:none;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <p class="font-semibold text-gray-500 mb-1">No matching requests found</p>
                                <p class="text-sm">Try a different patient name or reference number.</p>
                            </div>

                            @if(!empty($requests) && $requests->hasPages())
                                <div class="tbl-pagination">
                                    <span>Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of 
                                        {{ $requests->total() }} requests</span>
                                    <div>{{ $requests->withQueryString()->links() }}</div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-header-icon">
                                <i class="fa-solid fa-file-circle-check text-white" style="font-size:.7rem;"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-800 text-sm" id="panelRefNo">Select a request</div>
                                <div class="text-[11px] text-gray-400">Document Request</div>
                            </div>
                        </div>

                        <div style="padding:1.25rem;" id="panelBody">
                            <div style="text-align:center;padding:2.5rem 0 2rem;color:#d1d5db;">
                                <div style="width:52px;height:52px;border-radius:14px;background:#fef2f2;display:flex;
                                    align-items:center;justify-content:center;margin:0 auto .9rem;">
                                    <i class="fa-solid fa-file-circle-check" style="font-size:1.4rem;color:#f9c1c1;"></i>
                                </div>
                                <p style="font-size:.78rem;font-weight:600;color:#cbd5e1;margin-bottom:.25rem;">No request selected</p>
                                <p style="font-size:.7rem;color:#e2e8f0;">Click a row to view details</p>
                            </div>
                        </div>

                        <div id="panelFoot" style="padding:.9rem 1.25rem;border-top:1px solid #f3f4f6;background:#fafafa;display:none;gap:.5rem;flex-wrap:wrap;"></div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>
@endsection

@section('scripts')
<script>
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    const statusBackground = { pending:'#fef3c7', approved:'#d1fae5', ready:'#dbeafe', released:'#ede9fe', rejected:'#fef2f2' };
    const statusText       = { pending:'#92400e', approved:'#065f46', ready:'#1e40af', released:'#5b21b6', rejected:'#8B0000' };

    function updateFilterButtonState() {
        const btn = document.getElementById('filterModalOpenBtn');
        const dot = btn ? btn.querySelector('.filter-dot') : null;

        const statusVal = document.getElementById('hiddenStatus')?.value || '';
        const typeVal   = document.getElementById('hiddenType')?.value || '';
        const dateFrom  = document.getElementById('hiddenDateFrom')?.value || '';
        const dateTo    = document.getElementById('hiddenDateTo')?.value || '';
        const sortVal   = document.getElementById('hiddenSort')?.value || 'newest';

        const hasFilters = !!statusVal || !!typeVal || !!dateFrom || !!dateTo || (sortVal && sortVal !== 'newest');

        if (btn) btn.classList.toggle('active', hasFilters);
        if (dot) dot.classList.toggle('visible', hasFilters);
    }

    function initFilterModal() {
        const backdrop  = document.getElementById('filterModalBackdrop');
        const openBtn   = document.getElementById('filterModalOpenBtn');
        const closeBtn  = document.getElementById('filterModalCloseBtn');
        const applyBtn  = document.getElementById('filterApplyBtn');
        const resetBtn  = document.getElementById('filterResetBtn');

        if (!backdrop || !openBtn) return;

        if (!openBtn.dataset.bound) {
            openBtn.dataset.bound = '1';
            openBtn.addEventListener('click', () => {
                backdrop.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        }

        function closeModal() {
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }

        if (closeBtn && !closeBtn.dataset.bound) {
            closeBtn.dataset.bound = '1';
            closeBtn.addEventListener('click', closeModal);
        }

        if (!backdrop.dataset.bound) {
            backdrop.dataset.bound = '1';
            backdrop.addEventListener('click', e => {
                if (e.target === backdrop) closeModal();
            });
        }

        if (!document.body.dataset.documentRequestEscapeBound) {
            document.body.dataset.documentRequestEscapeBound = '1';
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') closeModal();
            });
        }

        const chipGroup = document.getElementById('filterStatusChips');
        if (chipGroup && !chipGroup.dataset.bound) {
            chipGroup.dataset.bound = '1';
            chipGroup.addEventListener('click', e => {
                const chip = e.target.closest('.filter-chip');
                if (!chip) return;
                chipGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
            });
        }

        const sortGroup = document.getElementById('filterSortChips');
        if (sortGroup && !sortGroup.dataset.bound) {
            sortGroup.dataset.bound = '1';
            sortGroup.addEventListener('click', e => {
                const chip = e.target.closest('.filter-chip');
                if (!chip) return;
                sortGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
            });
        }

        if (applyBtn && !applyBtn.dataset.bound) {
            applyBtn.dataset.bound = '1';
            applyBtn.addEventListener('click', () => {
                const activeChip     = chipGroup ? chipGroup.querySelector('.filter-chip.active') : null;
                const activeSortChip = sortGroup ? sortGroup.querySelector('.filter-chip.active') : null;

                const statusVal = activeChip ? activeChip.dataset.value : '';
                const sortVal   = activeSortChip ? activeSortChip.dataset.value : 'newest';
                const typeVal   = document.getElementById('filterTypeSelect')?.value || '';
                const dateFrom  = document.getElementById('filterDateFrom')?.value || '';
                const dateTo    = document.getElementById('filterDateTo')?.value || '';

                document.getElementById('hiddenStatus').value   = statusVal;
                document.getElementById('hiddenType').value     = typeVal;
                document.getElementById('hiddenDateFrom').value = dateFrom;
                document.getElementById('hiddenDateTo').value   = dateTo;
                document.getElementById('hiddenSort').value     = sortVal;

                updateFilterButtonState();
                closeModal();

                const form = document.getElementById('documentRequestsFilterForm');
                const url  = new URL(form.action, window.location.origin);
                url.search = '';

                if (statusVal)                        url.searchParams.set('status', statusVal);
                if (typeVal)                          url.searchParams.set('type', typeVal);
                if (dateFrom)                         url.searchParams.set('date_from', dateFrom);
                if (dateTo)                           url.searchParams.set('date_to', dateTo);
                if (sortVal && sortVal !== 'newest') url.searchParams.set('sort', sortVal);

                loadDocumentRequestsFragment(url.toString());
            });
        }

        if (resetBtn && !resetBtn.dataset.bound) {
            resetBtn.dataset.bound = '1';
            resetBtn.addEventListener('click', () => {
                if (chipGroup) {
                    chipGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                    const allChip = chipGroup.querySelector('.filter-chip[data-value=""]');
                    if (allChip) allChip.classList.add('active');
                }

                if (sortGroup) {
                    sortGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                    const defaultSort = sortGroup.querySelector('.filter-chip[data-value="newest"]');
                    if (defaultSort) defaultSort.classList.add('active');
                }

                const typeSelect = document.getElementById('filterTypeSelect');
                if (typeSelect) typeSelect.value = '';

                const dfrom = document.getElementById('filterDateFrom');
                const dto   = document.getElementById('filterDateTo');
                if (dfrom) dfrom.value = '';
                if (dto) dto.value = '';

                document.getElementById('hiddenStatus').value   = '';
                document.getElementById('hiddenType').value     = '';
                document.getElementById('hiddenDateFrom').value = '';
                document.getElementById('hiddenDateTo').value   = '';
                document.getElementById('hiddenSort').value     = 'newest';

                updateFilterButtonState();
            });
        }
    }

    function detailRow(label, value) {
        return `<div style="display:flex;gap:.5rem;margin-bottom:.6rem;font-size:.8rem;">
            <span style="color:#9ca3af;min-width:100px;flex-shrink:0;">${label}</span>
            <span style="color:#111;font-weight:600;">${value}</span>
        </div>`;
    }

    async function openPanel(id) {
        const panelRefNo = document.getElementById('panelRefNo');
        const panelBody  = document.getElementById('panelBody');
        const panelFoot  = document.getElementById('panelFoot');

        panelRefNo.textContent = 'Loading...';
        panelBody.innerHTML = `<div style="text-align:center;padding:2rem 0;color:#d1d5db;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;"></i></div>`;
        panelFoot.style.display = 'none';
        panelFoot.innerHTML = '';

        try {
            const response = await fetch(`/admin/document-requests/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (!response.ok) throw new Error('Failed to fetch.');
            const data = await response.json();

            panelRefNo.textContent = data.reference_number;
            panelBody.innerHTML = `
                <div style="background:#fef2f2;border-radius:10px;padding:.9rem 1rem;margin-bottom:1rem;display:flex;
                    align-items:center;gap:.75rem;border:1px solid #fce8e8;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#6b0000);
                        color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                        ${((data.patient_name || '?')[0] || '?').toUpperCase()}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.84rem;font-weight:700;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${data.patient_name || '—'}</div>
                        <div style="font-size:.7rem;color:#9ca3af;">${data.patient_id || ''}</div>
                    </div>
                    <span style="background:${statusBackground[data.status] || '#f3f4f6'};color:${statusText[data.status] || '#6b7280'};padding:.2rem .65rem;border-radius:999px;font-size:.68rem;font-weight:700;white-space:nowrap;">
                        ${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : '—'}
                    </span>
                </div>
                ${detailRow('Document', formatTitle(data.document_type))}
                ${detailRow('Purpose', data.purpose || '—')}
                ${detailRow('Date', data.created_at || '—')}
                ${detailRow('Copies', data.copies_needed || '1')}
                <div style="margin-top:1rem;">
                    <div style="font-size:.67rem;font-weight:700;color:#9ca3af;text-transform:uppercase;
                        letter-spacing:.08em;margin-bottom:.6rem;">Activity</div>
                    <div style="padding-left:1rem;border-left:2px solid #f0eaea;">
                        ${(data.activities || [{ date: '—', description: 'No activity yet.' }]).map(a => `
                            <div style="position:relative;margin-bottom:.6rem;">
                                <div style="position:absolute;left:-1.35rem;top:.3rem;width:6px;
                                    height:6px;border-radius:50%;background:#8B0000;border:1.5px solid #fef2f2;"></div>
                                <div style="font-size:.67rem;color:#9ca3af;">${a.date}</div>
                                <div style="font-size:.76rem;color:#374151;">${a.description}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            panelFoot.style.display = 'flex';
            let actions = '';

            if (data.status === 'pending') {
                actions += `<form action="/admin/document-requests/${data.id}/approve" method="POST">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="btn-primary" style="font-size:.75rem;padding:.4rem .9rem;">
                        <i class="fa-solid fa-check text-xs"></i> Approve
                    </button>
                </form>`;
            }
            if (data.status === 'approved' || data.status === 'ready') {
                actions += `<form action="/admin/document-requests/${data.id}/release" method="POST">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="btn-secondary" style="font-size:.75rem;padding:.38rem .9rem;">Release</button>
                </form>`;
            }
            if (data.status === 'pending' || data.status === 'approved') {
                actions += `<form action="/admin/document-requests/${data.id}/reject" method="POST" onsubmit="return confirm('Reject this request?')">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="btn-secondary" style="font-size:.75rem;
                        padding:.38rem .9rem;color:#dc2626;border-color:#fce8e8;">Reject</button>
                </form>`;
            }

            panelFoot.innerHTML = actions;
        } catch {
            panelBody.innerHTML = `<p style="color:#dc2626;text-align:center;padding:1.5rem;">Failed to load details.</p>`;
        }
    }

    function formatTitle(value) {
        if (!value) return '—';
        return value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function getPreferredDocumentRequestView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem('documentRequestView') || 'list';
    }

    function applyDocumentRequestView(view, save = true) {
        const listView = document.getElementById('documentRequestListView');
        const gridView = document.getElementById('documentRequestGridView');
        const listBtn  = document.getElementById('listViewBtn');
        const gridBtn  = document.getElementById('gridViewBtn');

        if (!listView || !gridView) return;

        const finalView = window.innerWidth <= 767 ? 'grid' : view;

        if (finalView === 'grid') {
            listView.hidden = true;
            gridView.hidden = false;
        } else {
            listView.hidden = false;
            gridView.hidden = true;
        }

        if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
        if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

        if (save && window.innerWidth > 767) {
            localStorage.setItem('documentRequestView', finalView);
        }
    }

    function initDocumentRequestViewToggle() {
        const listBtn = document.getElementById('listViewBtn');
        const gridBtn = document.getElementById('gridViewBtn');

        applyDocumentRequestView(getPreferredDocumentRequestView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', () => applyDocumentRequestView('list', true));
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', () => applyDocumentRequestView('grid', true));
        }
    }

    function updateDocumentRequestSearchClear() {
        const searchInput = document.getElementById('documentRequestSearch');
        const clearBtn    = document.getElementById('documentRequestClearBtn');
        if (!searchInput || !clearBtn) return;
        clearBtn.classList.toggle('hidden', searchInput.value.trim().length === 0);
    }

    function filterDocumentRequestRows() {
        const searchInput = document.getElementById('documentRequestSearch');
        const rows        = Array.from(document.querySelectorAll('.document-request-row'));
        const emptyState  = document.getElementById('documentRequestClientEmpty');
        const pagination  = document.querySelector('.tbl-pagination');
        if (!searchInput) return;

        const q = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;
        const seenKeys = new Set();

        rows.forEach(row => {
            const haystack = [
                row.dataset.reference,
                row.dataset.patient,
                row.dataset.student,
                row.dataset.document,
                row.dataset.purpose,
                row.dataset.status
            ].join(' ');

            const match = !q || haystack.includes(q);
            row.style.display = match ? '' : 'none';

            if (match) {
                const key = row.dataset.reference || Math.random().toString();
                if (!seenKeys.has(key)) {
                    seenKeys.add(key);
                    visibleCount++;
                }
            }
        });

        if (emptyState) emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
        if (pagination) pagination.style.display = q ? 'none' : '';
    }

    function initDocumentRequestSearch() {
        const searchInput = document.getElementById('documentRequestSearch');
        const clearBtn    = document.getElementById('documentRequestClearBtn');
        if (!searchInput) return;

        updateDocumentRequestSearchClear();
        filterDocumentRequestRows();

        if (!searchInput.dataset.searchBound) {
            searchInput.dataset.searchBound = '1';
            searchInput.addEventListener('input', () => {
                updateDocumentRequestSearchClear();
                filterDocumentRequestRows();
            });
        }

        if (clearBtn && !clearBtn.dataset.bound) {
            clearBtn.dataset.bound = '1';
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                const status = searchInput.closest('.search-wrap')?.querySelector('[data-voice-status]');
                if (status) status.classList.add('hidden');
                updateDocumentRequestSearchClear();
                filterDocumentRequestRows();
                searchInput.focus();
            });
        }
    }

    async function loadDocumentRequestsFragment(url, push = true) {
        const fragment = document.getElementById('documentRequestsFragment');
        if (!fragment) return;

        fragment.classList.add('is-loading');

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Failed to load.');

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newFragment = doc.querySelector('#documentRequestsFragment');

            if (!newFragment) { window.location.href = url; return; }

            fragment.innerHTML = newFragment.innerHTML;
            if (push) window.history.pushState({}, '', url);

            bindDocumentRequestAjax();
            initDocumentRequestSearch();
            initFilterModal();
            initDocumentRequestViewToggle();
            updateFilterButtonState();
        } catch {
            window.location.href = url;
        } finally {
            fragment.classList.remove('is-loading');
        }
    }

    function bindDocumentRequestAjax() {
        document.querySelectorAll('.js-ajax-nav').forEach(link => {
            if (link.dataset.bound === '1') return;
            link.dataset.bound = '1';

            link.addEventListener('click', function (e) {
                e.preventDefault();
                loadDocumentRequestsFragment(this.href);
            });
        });

        document.querySelectorAll('.tbl-pagination a').forEach(link => {
            if (link.dataset.bound === '1') return;
            link.dataset.bound = '1';

            link.addEventListener('click', function (e) {
                e.preventDefault();
                loadDocumentRequestsFragment(this.href);
            });
        });
    }

    window.addEventListener('popstate', () => loadDocumentRequestsFragment(window.location.href, false));

    document.addEventListener('DOMContentLoaded', () => {
        bindDocumentRequestAjax();
        initDocumentRequestSearch();
        initFilterModal();
        initDocumentRequestViewToggle();
        updateFilterButtonState();

        window.addEventListener('resize', () => {
            applyDocumentRequestView(getPreferredDocumentRequestView(), false);
        });
    });
</script>
@endsection