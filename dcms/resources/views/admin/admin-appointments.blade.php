@extends('layouts.admin')

@section('title', 'Appointments | PUP Taguig Dental Clinic')

@section('styles')
<style>
    :root {
        --crimson: #8B0000;
        --crimson-dark: #6b0000;
        --crimson-light: #fef2f2;
    }

    /* Page Banner */
    .page-banner {
        background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 60%, #c0392b 100%);
        padding: 1.75rem 2rem 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(139, 0, 0, .25);
        margin-bottom: 1.5rem;
        border-radius: 1rem;
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
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 900;
        color: #fff !important;
        line-height: 1.1;
        letter-spacing: -.02em;
    }

    .page-subtitle {
        font-size: .8rem;
        color: rgba(255, 255, 255, .72) !important;
        margin-top: .45rem;
    }

    .page-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: .72rem;
        font-weight: 700;
        color: #8B0000;
        background: rgba(255, 255, 255, .95);
        border: 1px solid rgba(255, 255, 255, .8);
        padding: .42rem .8rem;
        border-radius: 999px;
        white-space: nowrap;
    }

    .page-badge-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
    }

    .summary-bar {
        background: linear-gradient(135deg, #7f0000 0%, #a00000 100%);
        border-bottom: 1px solid rgba(255, 255, 255, .08);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
    }

    .summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 9999px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 500;
        color: white;
    }

    .summary-chip-highlight {
        background: rgba(255, 255, 255, .22);
        border-color: rgba(255, 255, 255, .35);
        font-weight: 700;
    }

    .tab-toggle-wrap {
        background: #5a0000;
        border-radius: 9999px;
        padding: 5px;
        display: flex;
        gap: 4px;
        box-shadow: 0 4px 16px rgba(139, 0, 0, .35);
    }

    .tab-btn-toggle {
        padding: 8px 20px;
        border-radius: 9999px;
        font-size: 13px;
        font-weight: 600;
        transition: all .25s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        color: rgba(255, 255, 255, .6);
        border: none;
        background: transparent;
        cursor: pointer;
    }

    .tab-btn-toggle.active {
        background: white;
        color: #8b0000;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .15);
    }

    .tab-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
    }

    .tab-btn-toggle.active .tab-count-badge {
        background: #8b0000;
        color: white;
    }

    .tab-btn-toggle:not(.active) .tab-count-badge {
        background: rgba(255, 255, 255, .2);
        color: rgba(255, 255, 255, .8);
    }

    .appt-card {
        background: #fff;
        border: 1px solid #EDE8E3;
        border-radius: 14px;
        position: relative;
        overflow: hidden;
        transition: box-shadow .2s, border-color .2s, transform .15s;
        animation: slideIn .3s ease both;
    }

    .appt-card:nth-child(even) {
        background: #FDFAF8;
    }

    .appt-card:hover {
        border-color: rgba(139, 0, 0, .25);
        box-shadow: 0 6px 24px rgba(139, 0, 0, .09);
    }

    .appt-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #8B0000;
        border-radius: 14px 0 0 14px;
        opacity: 0;
        transition: opacity .2s;
    }

    .appt-card:hover::before {
        opacity: 1;
    }

    .appt-card.is-today {
        background: #f0fdf4 !important;
        border-color: #86efac !important;
        box-shadow: 0 2px 12px rgba(34, 197, 94, .1);
    }

    .appt-card.is-today::before {
        background: #16a34a;
        opacity: 1;
    }

    .service-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 500;
        padding: 3px 10px;
        border-radius: 6px;
        width: fit-content;
    }

    .service-badge-default {
        background: #f9f0f0;
        color: #8B0000;
    }

    .service-badge-surgery {
        background: #fff0f0;
        color: #C41E3A;
    }

    .service-badge-checkup {
        background: #ebf5ee;
        color: #2D7A5E;
    }

    .service-badge-whitening {
        background: #fff3e0;
        color: #B86C00;
    }

    .service-badge-extraction {
        background: #fff0e8;
        color: #B85000;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .status-confirmed {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .status-completed {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        flex-shrink: 0;
        animation: pulse 2s infinite;
    }

    .time-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #F5F0EB;
        border: 1px solid #E8E0D8;
        border-radius: 7px;
        padding: 5px 10px;
        font-size: 13px;
        font-weight: 500;
        color: #6B5E52;
    }

    .timeline-dot {
        width: 18px;
        height: 18px;
        background: #8b0000;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .2), 0 2px 8px rgba(139, 0, 0, .3);
        flex-shrink: 0;
    }

    .timeline-dot-past {
        width: 18px;
        height: 18px;
        background: #9ca3af;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 3px rgba(156, 163, 175, .2);
        flex-shrink: 0;
    }

    .view-toggle {
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 12px;
        padding: 3px;
        gap: 3px;
        height: 38px;
        margin-left: 2px;
    }

    .appointment-banner-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .55rem;
        flex-wrap: wrap;
        margin-left: auto;
        align-self: center;
    }

    .appointment-banner-title-wrap {
        min-width: 0;
    }

    .view-toggle-btn {
        width: 32px;
        height: 30px;
        padding: 0;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, .72);
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
        background: rgba(255, 255, 255, .12);
        color: #fff;
    }

    .view-toggle-btn.active {
        background: #fff;
        color: #8B0000;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        height: 28px;
        padding: 0 9px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        transition: all .15s ease;
        white-space: nowrap;
        border: none;
        cursor: pointer;
        text-decoration: none;
    }

    .action-btn-view {
        background: #fff;
        color: #8B0000;
        border: 1.5px solid rgba(139, 0, 0, .3);
    }

    .action-btn-view:hover {
        background: #8B0000;
        color: white;
    }

    .action-btn-start {
        background: #15803d;
        color: white;
        border: 1.5px solid #15803d;
    }

    .action-btn-start:hover {
        background: #166534;
        box-shadow: 0 2px 8px rgba(21, 128, 61, .3);
    }

    .action-btn-start:disabled {
        background: #d1d5db;
        border-color: #d1d5db;
        color: #9ca3af;
        cursor: not-allowed;
        box-shadow: none;
    }

    .action-btn-reschedule {
        background: #fffbeb;
        color: #92400e;
        border: 1.5px solid #fcd34d;
    }

    .action-btn-reschedule:hover {
        background: #fef3c7;
        box-shadow: 0 2px 8px rgba(251, 191, 36, .25);
    }

    .action-btn-cancel {
        background: #fff1f2;
        color: #9f1239;
        border: 1.5px solid #fecdd3;
    }

    .action-btn-cancel:hover {
        background: #ffe4e6;
        box-shadow: 0 2px 8px rgba(159, 18, 57, .15);
    }

    .mobile-appt-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 16px;
        padding: 1rem;
        position: relative;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        animation: slideIn .3s ease both;
        display: flex;
        flex-direction: column;
        gap: .85rem;
        min-width: 0;
    }

    .mobile-appt-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        min-width: 0;
        padding-left: .35rem;
    }

    .mobile-appt-patient {
        min-width: 0;
        flex: 1;
    }

    .mobile-appt-name {
        font-size: .9rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
        word-break: break-word;
    }

    .mobile-appt-sub {
        font-size: .72rem;
        color: #9ca3af;
        margin-top: .2rem;
        line-height: 1.3;
    }

    .mobile-appt-meta {
        display: grid;
        grid-template-columns: 1fr;
        gap: .65rem;
        padding-left: .35rem;
    }

    .mobile-appt-field {
        min-width: 0;
    }

    .mobile-appt-label {
        font-size: .64rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: .28rem;
    }

    .mobile-appt-value {
        font-size: .82rem;
        color: #374151;
        line-height: 1.35;
        min-width: 0;
    }

    .mobile-appt-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .mobile-appt-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .5rem;
        padding-left: .35rem;
    }

    .mobile-appt-actions .action-btn {
        width: 100%;
        justify-content: center;
        height: 34px;
        font-size: 11px;
        padding: 0 10px;
    }

    .mobile-appt-actions .action-btn i {
        font-size: 10px;
    }

    .desktop-appointments-table {
        display: block;
    }

    .mobile-appointments-list {
        display: none;
    }

    .appointments-list-view[hidden],
    .appointments-grid-view[hidden] {
        display: none !important;
    }

    .appointments-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    @media (max-width: 1024px) {
        .appointments-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .mobile-appt-actions {
            grid-template-columns: 1fr;
        }

        .mobile-appt-name {
            font-size: .85rem;
        }

        .status-pill {
            font-size: 10px;
            padding: 4px 8px;
        }
    }

    .mobile-appt-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .06);
        border-color: #ead6d6;
    }

    .mobile-appt-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #8B0000;
        border-radius: 16px 0 0 16px;
    }

    .mobile-appt-card.is-today {
        background: #f0fdf4 !important;
        border-color: #86efac !important;
    }

    .mobile-appt-card.is-today::before {
        background: #16a34a;
    }

    #toastContainer {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }

    .toast-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: #1a1a1a;
        border: 1px solid #2d2d2d;
        border-radius: 14px;
        padding: 14px 16px;
        min-width: 280px;
        max-width: 360px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, .35);
        pointer-events: auto;
        position: relative;
        overflow: hidden;
        animation: toastIn .4s cubic-bezier(.34, 1.56, .64, 1) forwards;
    }

    .toast-item.toast-exit {
        animation: toastOut .35s ease forwards;
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        border-radius: 0 0 14px 14px;
        background: linear-gradient(90deg, #dc2626, #f87171);
        animation: toastProg linear forwards;
    }

    .toast-icon-wrap {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: rgba(220, 38, 38, .15);
        border: 1px solid rgba(220, 38, 38, .25);
    }

    .toast-title {
        font-size: 13px;
        font-weight: 700;
        color: #f3f4f6;
        line-height: 1.3;
        margin-bottom: 2px;
    }

    .toast-message {
        font-size: 12px;
        color: #9ca3af;
        line-height: 1.4;
    }

    .toast-close {
        width: 22px;
        height: 22px;
        border-radius: 6px;
        background: rgba(255, 255, 255, .06);
        border: none;
        cursor: pointer;
        color: #6b7280;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .15s;
    }

    .toast-close:hover {
        background: rgba(255, 255, 255, .12);
        color: #e5e7eb;
    }

    .cancel-modal-panel,
    .reschedule-modal-panel,
    .start-modal-panel {
        animation: modalUp .3s cubic-bezier(.34, 1.56, .64, 1) forwards;
    }

    .reason-chip input[type="radio"] {
        display: none;
    }

    .reason-chip label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 9999px;
        border: 1.5px solid #e5e7eb;
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        transition: all .15s;
        background: white;
        user-select: none;
    }

    .reason-chip label:hover {
        border-color: #fca5a5;
        color: #9f1239;
        background: #fff1f2;
    }

    .reason-chip input[type="radio"]:checked+label {
        border-color: #dc2626;
        background: #fef2f2;
        color: #dc2626;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(220, 38, 38, .15);
    }

    #cancelReasonChips.invalid .reason-chip label {
        border-color: #fca5a5;
        background: #fff5f5;
    }

    .chips-error-shake {
        animation: errorShake .35s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1
        }

        50% {
            opacity: .4
        }
    }

    @keyframes toastIn {
        from {
            opacity: 0;
            transform: translateX(60px) scale(.95);
        }

        to {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }

    @keyframes toastOut {
        from {
            opacity: 1;
            transform: translateX(0);
            max-height: 100px;
        }

        to {
            opacity: 0;
            transform: translateX(60px);
            max-height: 0;
        }
    }

    @keyframes toastProg {
        from {
            width: 100%;
        }

        to {
            width: 0%;
        }
    }

    @keyframes modalUp {
        from {
            opacity: 0;
            transform: translateY(24px) scale(.97);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes errorShake {
        0%, 100% {
            transform: translateX(0);
        }

        20% {
            transform: translateX(-5px);
        }

        40% {
            transform: translateX(5px);
        }

        60% {
            transform: translateX(-3px);
        }

        80% {
            transform: translateX(3px);
        }
    }

    /* =========================================================
       DARK MODE
       Consolidated overrides for appointment cards and controls.
       Some !important rules are kept only to beat Tailwind utility classes.
    ========================================================= */
    [data-theme="dark"] .bg-white {
        background-color: #0d1117 !important;
        border-color: #21262d !important;
    }

    [data-theme="dark"] .appt-card,
    [data-theme="dark"] .mobile-appt-card {
        background:
            linear-gradient(135deg, rgba(22, 27, 34, .68), rgba(13, 17, 23, .78)) !important;
        border: 1px solid rgba(255, 255, 255, .08) !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .45);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    [data-theme="dark"] .appt-card:nth-child(even) {
        background:
            linear-gradient(135deg, rgba(22, 27, 34, .60), rgba(10, 15, 20, .82)) !important;
    }

    [data-theme="dark"] .appt-card.is-today,
    [data-theme="dark"] .mobile-appt-card.is-today {
        background:
            radial-gradient(circle at top right, rgba(34, 197, 94, .12), transparent 36%),
            linear-gradient(135deg, rgba(22, 27, 34, .68), rgba(13, 17, 23, .78)) !important;
        border-color: rgba(34, 197, 94, .24) !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .45);
    }

    [data-theme="dark"] .appt-card:hover,
    [data-theme="dark"] .mobile-appt-card:hover {
        border-color: rgba(252, 165, 165, .22) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .06),
            0 14px 36px rgba(0, 0, 0, .52),
            0 0 18px rgba(252, 165, 165, .06);
    }

    [data-theme="dark"] .appt-card::before,
    [data-theme="dark"] .mobile-appt-card::before {
        opacity: 1;
        background: rgba(252, 165, 165, .55);
    }

    [data-theme="dark"] .appt-card.is-today::before,
    [data-theme="dark"] .mobile-appt-card.is-today::before {
        background: rgba(34, 197, 94, .75);
    }

    [data-theme="dark"] .desktop-appointments-table > .grid:first-of-type {
        color: rgba(255, 255, 255, .48) !important;
        border-color: rgba(255, 255, 255, .08) !important;
    }

    [data-theme="dark"] .desktop-appointments-table .absolute {
        background: linear-gradient(to bottom, rgba(252, 165, 165, .28), rgba(252, 165, 165, .04)) !important;
    }

    [data-theme="dark"] .timeline-dot {
        border-color: #0d1117;
        box-shadow: 0 0 0 3px rgba(252, 165, 165, .16), 0 2px 8px rgba(252, 165, 165, .18);
    }

    [data-theme="dark"] .timeline-dot-past {
        border-color: #0d1117;
        box-shadow: 0 0 0 3px rgba(156, 163, 175, .14);
    }

    [data-theme="dark"] .page-badge,
    [data-theme="dark"] .summary-chip {
        background: rgba(255,255,255,0.12) !important;
        border: 1px solid rgba(255,255,255,0.18) !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .page-badge {
        background: rgba(255,255,255,0.08) !important;
        border: 1px solid rgba(255,255,255,0.14) !important;
        color: #8B0000 !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.12),
            0 6px 16px rgba(0,0,0,0.25);
    }

    [data-theme="dark"] .timeline-dot,
    [data-theme="dark"] .timeline-dot-past {
        background: #FCA5A5 !important;
        box-shadow:
            0 0 0 3px rgba(252,165,165,0.18),
            0 2px 8px rgba(252,165,165,0.22) !important;
    }

    [data-theme="dark"] h3.text-\[\#8b0000\] {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] span.bg-\[\#f9f0f0\].text-\[\#8b0000\] {
        background: rgba(255,255,255,0.08) !important;
        border-color: rgba(255,255,255,0.14) !important;
        color: #FCA5A5 !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.12),
            0 6px 16px rgba(0,0,0,0.25);
    }

    [data-theme="dark"] .appt-card .text-gray-800,
    [data-theme="dark"] .appt-card .grid > div:nth-child(4) p,
    [data-theme="dark"] .mobile-appt-name,
    [data-theme="dark"] .mobile-appt-name.text-gray-500 {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .appt-card .grid > div:first-child p:first-child,
    [data-theme="dark"] .mobile-appt-sub,
    [data-theme="dark"] .appt-card p.text-\[13px\].text-gray-500 {
        color: #ffffff !important;
    }

    [data-theme="dark"] .appt-card .text-gray-400,
    [data-theme="dark"] .mobile-appt-label,
    [data-theme="dark"] .mobile-appt-value,
    [data-theme="dark"] .mobile-appt-value.text-gray-400 {
        color: rgba(255, 255, 255, .62) !important;
    }

    [data-theme="dark"] .time-chip,
    [data-theme="dark"] .service-badge,
    [data-theme="dark"] .status-pill,
    [data-theme="dark"] .appt-card .bg-gray-100,
    [data-theme="dark"] .mobile-appt-card .bg-gray-100 {
        background: rgba(255, 255, 255, .055) !important;
        border-color: rgba(255, 255, 255, .085) !important;
        color: rgba(255, 255, 255, .74) !important;
    }

    [data-theme="dark"] .summary-chip-highlight {
        background: rgba(255,255,255,0.14) !important;
        border-color: rgba(255,255,255,0.22) !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .service-badge-surgery,
    [data-theme="dark"] .service-badge-default {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .service-badge-checkup {
        color: #86efac !important;
    }

    [data-theme="dark"] .service-badge-whitening,
    [data-theme="dark"] .service-badge-extraction {
        color: #fde68a !important;
    }

    [data-theme="dark"] .status-confirmed {
        background: rgba(34, 197, 94, .12) !important;
        border-color: rgba(34, 197, 94, .25) !important;
        color: #86efac !important;
    }

    [data-theme="dark"] .status-pending {
        background: rgba(251, 191, 36, .12) !important;
        border-color: rgba(251, 191, 36, .25) !important;
        color: #fde68a !important;
    }

    [data-theme="dark"] .status-completed {
        background: rgba(156, 163, 175, .10) !important;
        border-color: rgba(156, 163, 175, .18) !important;
        color: #cbd5e1 !important;
    }

    [data-theme="dark"] .view-toggle,
    [data-theme="dark"] .tab-toggle-wrap {
        background: rgba(255, 255, 255, .055);
        border: 1px solid rgba(255, 255, 255, .085);
        box-shadow: 0 8px 22px rgba(0, 0, 0, .20);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    [data-theme="dark"] .view-toggle-btn.active,
    [data-theme="dark"] .tab-btn-toggle.active {
        background: rgba(255,255,255,0.10);
        color: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
    }

    [data-theme="dark"] .view-toggle-btn:hover,
    [data-theme="dark"] .tab-btn-toggle:hover {
        background: rgba(255, 255, 255, .09);
        color: #ffffff;
    }

    [data-theme="dark"] .tab-btn-toggle.active .tab-count-badge {
        background: rgba(139, 0, 0, .70);
        color: #ffffff;
    }

    [data-theme="dark"] .tab-btn-toggle:not(.active) .tab-count-badge {
        background: rgba(255, 255, 255, .10);
        color: rgba(255, 255, 255, .72);
    }

    [data-theme="dark"] .action-btn {
        transform: none;
        height: 28px;
        min-width: auto;
        box-shadow: none;
    }

    [data-theme="dark"] .action-btn-view {
        background: rgba(59, 130, 246, .15);
        border: 1px solid rgba(59, 130, 246, .35);
        color: #93c5fd;
    }

    [data-theme="dark"] .action-btn-start {
        background: rgba(34, 197, 94, .15);
        border: 1px solid rgba(34, 197, 94, .35);
        color: #86efac;
    }

    [data-theme="dark"] .action-btn-start:disabled {
        background: rgba(156, 163, 175, .10);
        border-color: rgba(156, 163, 175, .18);
        color: rgba(255, 255, 255, .36);
    }

    [data-theme="dark"] .action-btn-reschedule {
        background: rgba(251, 191, 36, .15);
        border: 1px solid rgba(251, 191, 36, .35);
        color: #fde68a;
    }

    [data-theme="dark"] .action-btn-cancel {
        background: rgba(239, 68, 68, .15);
        border: 1px solid rgba(239, 68, 68, .35);
        color: #fca5a5;
    }

    [data-theme="dark"] .action-btn:hover:not(:disabled) {
        transform: none;
        filter: brightness(1.12);
        border-color: rgba(255, 255, 255, .25);
        box-shadow: none;
    }

    @media (max-width: 767px) {
        .page-banner {
            padding: 1rem 1rem 1.2rem !important;
        }

        .page-banner-inner {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            gap: .85rem;
        }

        .appointment-banner-title-wrap {
            width: 100%;
        }

        .page-title {
            font-size: 1.28rem !important;
            line-height: 1.15;
            text-align: left;
            margin: 0;
        }

        .appointment-banner-actions {
            width: 100%;
            margin-left: 0;
            justify-content: flex-start;
            align-items: center;
            gap: .55rem;
            flex-wrap: nowrap;
        }

        .tab-toggle-wrap {
            width: fit-content;
            max-width: 100%;
            padding: 4px;
            gap: 3px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab-btn-toggle {
            padding: 7px 12px;
            font-size: 11.5px;
            gap: 6px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .tab-count-badge {
            min-width: 18px;
            height: 18px;
            font-size: 10px;
            padding: 0 5px;
        }

        .view-toggle {
            display: none !important;
        }

        .summary-bar {
            padding: .85rem 1rem;
        }

        .desktop-appointments-table {
            display: none !important;
        }

        .appointments-list-view,
        .appointments-grid-view {
            display: none !important;
        }

        .mobile-appointments-list {
            display: block !important;
        }

        #toastContainer {
            bottom: 16px;
            right: 16px;
            left: 16px;
        }

        .toast-item {
            min-width: unset;
            max-width: 100%;
        }
    }
</style>
@endsection

@section('content')
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $upcomingAppointments = collect($upcomingAppointments ?? []);
    $pastAppointments = collect($pastAppointments ?? []);
    $today = $today ?? Carbon::today()->toDateString();

    $todayAppts = $upcomingAppointments->filter(fn($a) => ($a->appointment_date ?? null) === $today);
    $todayCount = $todayAppts->count();

    $nextAppt = $upcomingAppointments->sortBy('appointment_date')->first();
    $nextName = $nextAppt ? (optional($nextAppt->patient)->name ?? 'Unknown') : null;
    $nextTime = $nextAppt ? Carbon::parse($nextAppt->appointment_time)->format('g:i A') : null;
    $nextDate = $nextAppt ? Carbon::parse($nextAppt->appointment_date)->format('M j') : null;

    $upcomingGrouped = $upcomingAppointments->groupBy(fn($a) => Carbon::parse($a->appointment_date)->format('F'));
    $pastGrouped = $pastAppointments->groupBy(fn($a) => Carbon::parse($a->appointment_date)->format('F'));

    $upcomingTotal = $upcomingAppointments->count();
    $pastTotal = $pastAppointments->count();
@endphp

<main id="mainContent" class="pt-[80px] sm:pt-[88px] px-3 sm:px-6 pb-6 min-h-screen">
    <div class="max-w-7xl mx-auto">

        <div class="page-banner mt-2">
            <div class="page-banner-inner">
                <div class="appointment-banner-title-wrap">
                    <h1 class="page-title">Appointment Management</h1>
                </div>

                <div class="appointment-banner-actions">

                    <div class="tab-toggle-wrap">
                        <button id="btnUpcoming" class="tab-btn-toggle active">
                            <i class="fa-solid fa-calendar-days text-xs"></i>
                            Upcoming
                            <span class="tab-count-badge">{{ $upcomingTotal }}</span>
                        </button>
                        <button id="btnPast" class="tab-btn-toggle">
                            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                            Past
                            <span class="tab-count-badge">{{ $pastTotal }}</span>
                        </button>
                    </div>

                    <div class="view-toggle" id="appointmentViewToggle">
                        <button type="button" class="view-toggle-btn active" id="appointmentListViewBtn" title="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="view-toggle-btn" id="appointmentGridViewBtn" title="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar">
            <i class="fa-solid fa-circle-info text-white/60 text-sm"></i>
            <span class="text-white/70 text-xs font-medium hidden sm:inline">Today's snapshot:</span>

            @if($todayCount > 0)
                <span class="summary-chip summary-chip-highlight">
                    <i class="fa-solid fa-calendar-check text-xs"></i>
                    {{ $todayCount }} appt{{ $todayCount > 1 ? 's' : '' }} today
                </span>
            @else
                <span class="summary-chip">
                    <i class="fa-regular fa-calendar text-xs"></i>
                    No appointments today
                </span>
            @endif

            @if($nextAppt)
                <span class="summary-chip hidden sm:inline-flex">
                    <i class="fa-solid fa-clock text-xs"></i>
                    Next: <strong>{{ $nextName }}</strong> — {{ $nextDate }} at {{ $nextTime }}
                </span>
            @endif
        </div>

        {{-- ── Upcoming Section ── --}}
        <section id="upcomingSection" class="pb-16 mt-2">
            @forelse($upcomingGrouped as $month => $items)
                <div class="mb-10 sm:mb-14">
                    <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-5">
                        <div class="timeline-dot"></div>
                        <h3 class="text-lg sm:text-xl font-bold text-[#8b0000]">{{ $month }}</h3>
                        <span class="bg-[#f9f0f0] text-[#8b0000] text-xs font-semibold px-3 py-1 rounded-full border border-[#8b0000]/15">
                            {{ $items->count() }} {{ Str::plural('appointment', $items->count()) }}
                        </span>
                    </div>

                    <div class="appointments-list-view">
                        <div class="desktop-appointments-table relative pl-10">
                            <div class="absolute left-[8px] top-0 bottom-0 w-[2px] bg-gradient-to-b from-[#8b0000]/30 to-[#8b0000]/05 rounded-full"></div>

                            <div class="grid grid-cols-[1.2fr_0.9fr_1.3fr_1.3fr_0.8fr_0.8fr_auto] text-[11px] font-semibold uppercase tracking-wider text-gray-600 pb-2 border-b border-gray-200 mb-3 px-5">
                                <span class="flex items-center gap-1.5"><i class="fa-regular fa-calendar text-[10px]"></i>Date</span>
                                <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-[10px]"></i>Time</span>
                                <span>Service</span>
                                <span>Patient</span>
                                <span>Program</span>
                                <span>Status</span>
                                <span class="text-right">Actions</span>
                            </div>

                            <div class="space-y-2.5">
                                @foreach($items as $i => $appt)
                                    @php
                                        $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                                        $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                                        $dateLabel = Carbon::parse($appt->appointment_date)->format('F j, Y');
                                        $weekday = Carbon::parse($appt->appointment_date)->format('l');
                                        $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') : '—';
                                        $serviceLabel = ($appt->service_type ?? '') === 'Others'
                                            ? (($appt->other_services ?? '') ?: 'Others')
                                            : ($appt->service_type ?? '—');
                                        $isToday = ($appt->appointment_date ?? null) === $today;

                                        $serviceLower = strtolower($serviceLabel);
                                        $badgeClass = 'service-badge-default';
                                        if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                                        elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                                        elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                                        elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                                    @endphp

                                    <div class="appt-card {{ $isToday ? 'is-today' : '' }}" data-appt-id="{{ $appt->id }}" style="animation-delay:{{ $i * 0.04 }}s">
                                        <div class="grid grid-cols-[1.2fr_0.9fr_1.3fr_1.3fr_0.8fr_0.8fr_auto] items-center px-5 py-3.5 gap-2">
                                            <div>
                                                <p class="text-[13px] font-semibold text-gray-800">{{ $dateLabel }}</p>
                                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $weekday }}</p>
                                                @if($isToday)
                                                    <span class="inline-block mt-1 text-[9px] font-bold uppercase tracking-wide bg-green-500 text-white px-2 py-0.5 rounded-md">Today</span>
                                                @endif
                                            </div>

                                            <div>
                                                <span class="time-chip"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}</span>
                                            </div>

                                            <div>
                                                <span class="service-badge {{ $badgeClass }}">{{ $serviceLabel }}</span>
                                            </div>

                                            <div>
                                                <p class="text-[13px] font-semibold text-gray-800">{{ $patientName }}</p>
                                            </div>

                                            <div>
                                                @if($program === '—')
                                                    <span class="text-[12px] text-gray-400">—</span>
                                                @else
                                                    <span class="inline-block bg-gray-100 text-gray-500 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">
                                                        {{ $program }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div>
                                                @if($isToday)
                                                    <span class="status-pill status-confirmed"><span class="status-dot"></span>Confirmed</span>
                                                @else
                                                    <span class="status-pill status-pending"><span class="status-dot"></span>Upcoming</span>
                                                @endif
                                            </div>

                                            <div class="flex items-center justify-end gap-1 flex-nowrap">
                                                <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                                                    class="action-btn action-btn-view">
                                                    <i class="fa-regular fa-user text-[9px]"></i> View
                                                </a>

                                                <button type="button"
                                                    class="action-btn action-btn-start"
                                                    onclick="openStartProcedureModal(this)"
                                                    data-id="{{ $appt->id }}"
                                                    data-name="{{ $patientName }}"
                                                    data-datetime="{{ $dateLabel }} | {{ $timeLabel }}"
                                                    {{ $isToday ? '' : 'disabled' }}>
                                                    <i class="fa-solid fa-play text-[9px]"></i> Start
                                                </button>

                                                <button type="button"
                                                    class="action-btn action-btn-reschedule"
                                                    onclick="openRescheduleModal(this)"
                                                    data-id="{{ $appt->id }}"
                                                    data-name="{{ $patientName }}"
                                                    data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                                    <i class="fa-solid fa-rotate-right text-[9px]"></i> Reschedule
                                                </button>

                                                <button type="button"
                                                    class="action-btn action-btn-cancel"
                                                    onclick="openCancelAppointmentModal(this)"
                                                    data-id="{{ $appt->id }}"
                                                    data-name="{{ $patientName }}"
                                                    data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                                    <i class="fa-solid fa-xmark text-[9px]"></i> Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="appointments-grid-view">
                        <div class="appointments-grid">
                            @foreach($items as $i => $appt)
                                @php
                                    $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                                    $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                                    $dateLabel = Carbon::parse($appt->appointment_date)->format('M j, Y');
                                    $weekday = Carbon::parse($appt->appointment_date)->format('l');
                                    $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') : '—';
                                    $serviceLabel = ($appt->service_type ?? '') === 'Others'
                                        ? (($appt->other_services ?? '') ?: 'Others')
                                        : ($appt->service_type ?? '—');
                                    $isToday = ($appt->appointment_date ?? null) === $today;

                                    $serviceLower = strtolower($serviceLabel);
                                    $badgeClass = 'service-badge-default';
                                    if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                                    elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                                    elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                                    elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                                @endphp

                                <div class="mobile-appt-card {{ $isToday ? 'is-today' : '' }}" data-appt-id="{{ $appt->id }}" style="animation-delay:{{ $i * 0.04 }}s">
                                    <div class="mobile-appt-top">
                                        <div class="mobile-appt-patient">
                                            <div class="mobile-appt-name">{{ $patientName }}</div>
                                            <div class="mobile-appt-sub">{{ $weekday }}, {{ $dateLabel }}</div>
                                        </div>

                                        @if($isToday)
                                            <span class="status-pill status-confirmed flex-shrink-0"><span class="status-dot"></span>Confirmed</span>
                                        @else
                                            <span class="status-pill status-pending flex-shrink-0"><span class="status-dot"></span>Upcoming</span>
                                        @endif
                                    </div>

                                    <div class="mobile-appt-meta">
                                        <div class="mobile-appt-field">
                                            <div class="mobile-appt-label">Appointment Details</div>
                                            <div class="mobile-appt-badges">
                                                <span class="time-chip text-xs"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}</span>
                                                <span class="service-badge {{ $badgeClass }} text-xs">{{ $serviceLabel }}</span>
                                                @if($program !== '—')
                                                    <span class="inline-block bg-gray-100 text-gray-500 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">
                                                        {{ $program }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mobile-appt-actions">
                                        <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                                            class="action-btn action-btn-view">
                                            <i class="fa-regular fa-user text-[9px]"></i> View
                                        </a>

                                        <button type="button"
                                            class="action-btn action-btn-start"
                                            onclick="openStartProcedureModal(this)"
                                            data-id="{{ $appt->id }}"
                                            data-name="{{ $patientName }}"
                                            data-datetime="{{ $dateLabel }} | {{ $timeLabel }}"
                                            {{ $isToday ? '' : 'disabled' }}>
                                            <i class="fa-solid fa-play text-[9px]"></i> Start
                                        </button>

                                        <button type="button"
                                            class="action-btn action-btn-reschedule"
                                            onclick="openRescheduleModal(this)"
                                            data-id="{{ $appt->id }}"
                                            data-name="{{ $patientName }}"
                                            data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                            <i class="fa-solid fa-rotate-right text-[9px]"></i> Reschedule
                                        </button>

                                        <button type="button"
                                            class="action-btn action-btn-cancel"
                                            onclick="openCancelAppointmentModal(this)"
                                            data-id="{{ $appt->id }}"
                                            data-name="{{ $patientName }}"
                                            data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                            <i class="fa-solid fa-xmark text-[9px]"></i> Cancel
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mobile-appointments-list space-y-3">
                        @foreach($items as $i => $appt)
                            @php
                                $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                                $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                                $dateLabel = Carbon::parse($appt->appointment_date)->format('M j, Y');
                                $weekday = Carbon::parse($appt->appointment_date)->format('l');
                                $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') : '—';
                                $serviceLabel = ($appt->service_type ?? '') === 'Others'
                                    ? (($appt->other_services ?? '') ?: 'Others')
                                    : ($appt->service_type ?? '—');
                                $isToday = ($appt->appointment_date ?? null) === $today;

                                $serviceLower = strtolower($serviceLabel);
                                $badgeClass = 'service-badge-default';
                                if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                                elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                                elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                                elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                            @endphp

                            <div class="mobile-appt-card {{ $isToday ? 'is-today' : '' }}" data-appt-id="{{ $appt->id }}" style="animation-delay:{{ $i * 0.04 }}s">
                                <div class="flex items-start justify-between gap-2 mb-3 pl-2">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-[13px] font-bold text-gray-800">{{ $patientName }}</p>
                                            @if($isToday)
                                                <span class="text-[9px] font-bold uppercase bg-green-500 text-white px-2 py-0.5 rounded-md">Today</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $weekday }}, {{ $dateLabel }}</p>
                                    </div>

                                    @if($isToday)
                                        <span class="status-pill status-confirmed flex-shrink-0"><span class="status-dot"></span>Confirmed</span>
                                    @else
                                        <span class="status-pill status-pending flex-shrink-0"><span class="status-dot"></span>Upcoming</span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2 mb-3 pl-2">
                                    <span class="time-chip text-xs"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}</span>
                                    <span class="service-badge {{ $badgeClass }} text-xs">{{ $serviceLabel }}</span>
                                    @if($program !== '—')
                                        <span class="inline-block bg-gray-100 text-gray-500 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">
                                            {{ $program }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2 pl-2">
                                    <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                                        class="action-btn action-btn-view text-xs">
                                        <i class="fa-regular fa-user text-[9px]"></i> View
                                    </a>

                                    <button type="button"
                                        class="action-btn action-btn-start text-xs"
                                        onclick="openStartProcedureModal(this)"
                                        data-id="{{ $appt->id }}"
                                        data-name="{{ $patientName }}"
                                        data-datetime="{{ $dateLabel }} | {{ $timeLabel }}"
                                        {{ $isToday ? '' : 'disabled' }}>
                                        <i class="fa-solid fa-play text-[9px]"></i> Start
                                    </button>

                                    <button type="button"
                                        class="action-btn action-btn-reschedule text-xs"
                                        onclick="openRescheduleModal(this)"
                                        data-id="{{ $appt->id }}"
                                        data-name="{{ $patientName }}"
                                        data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                        <i class="fa-solid fa-rotate-right text-[9px]"></i> Reschedule
                                    </button>

                                    <button type="button"
                                        class="action-btn action-btn-cancel text-xs"
                                        onclick="openCancelAppointmentModal(this)"
                                        data-id="{{ $appt->id }}"
                                        data-name="{{ $patientName }}"
                                        data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                        <i class="fa-solid fa-xmark text-[9px]"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                    <i class="fa-regular fa-calendar-xmark text-4xl sm:text-5xl mb-4 text-gray-300"></i>
                    <p class="text-base font-semibold text-gray-500">No upcoming appointments</p>
                    <p class="text-sm mt-1 text-center px-4">New appointments will appear here once scheduled.</p>
                </div>
            @endforelse
        </section>

        {{-- ── Past Section ── --}}
        <section id="pastSection" class="pb-16 mt-2 hidden">
            @forelse($pastGrouped as $month => $items)
                <div class="mb-10 sm:mb-14">
                    <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-5 pl-2">
                        <div class="timeline-dot-past"></div>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-400">{{ $month }}</h3>
                        <span class="bg-gray-100 text-gray-400 text-xs font-semibold px-3 py-1 rounded-full">
                            {{ $items->count() }} {{ Str::plural('appointment', $items->count()) }}
                        </span>
                    </div>

                    <div class="appointments-list-view">
                        <div class="desktop-appointments-table relative pl-10">
                            <div class="absolute left-[8px] top-0 bottom-0 w-[2px] bg-gray-200 rounded-full"></div>

                            <div class="grid grid-cols-[1.4fr_1fr_1.5fr_1.5fr_1fr] text-[11px] font-semibold uppercase tracking-wider text-gray-400 pb-2 border-b border-gray-200 mb-3 px-5">
                                <span class="flex items-center gap-1.5"><i class="fa-regular fa-calendar text-[10px]"></i>Date</span>
                                <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-[10px]"></i>Time</span>
                                <span>Service</span>
                                <span>Patient</span>
                                <span>Program</span>
                            </div>

                            <div class="space-y-2.5">
                                @foreach($items as $i => $appt)
                                    @php
                                        $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                                        $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                                        $dateLabel = Carbon::parse($appt->appointment_date)->format('F j, Y');
                                        $weekday = Carbon::parse($appt->appointment_date)->format('l');
                                        $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') : '—';
                                        $serviceLabel = ($appt->service_type ?? '') === 'Others'
                                            ? (($appt->other_services ?? '') ?: 'Others')
                                            : ($appt->service_type ?? '—');

                                        $serviceLower = strtolower($serviceLabel);
                                        $badgeClass = 'service-badge-default';
                                        if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                                        elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                                        elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                                        elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                                    @endphp

                                    <div class="appt-card opacity-70" style="animation-delay:{{ $i * 0.04 }}s">
                                        <div class="grid grid-cols-[1.4fr_1fr_1.5fr_1.5fr_1fr] items-center px-5 py-3.5 gap-2">
                                            <div>
                                                <p class="text-[13px] font-semibold text-gray-500">{{ $dateLabel }}</p>
                                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $weekday }}</p>
                                            </div>

                                            <div>
                                                <span class="time-chip text-gray-400"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}</span>
                                            </div>

                                            <div>
                                                <span class="service-badge {{ $badgeClass }} opacity-70">{{ $serviceLabel }}</span>
                                            </div>

                                            <div>
                                                <p class="text-[13px] font-medium text-gray-500">{{ $patientName }}</p>
                                            </div>

                                            <div>
                                                @if($program === '—')
                                                    <span class="text-[12px] text-gray-400">—</span>
                                                @else
                                                    <span class="inline-block bg-gray-100 text-gray-400 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">
                                                        {{ $program }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="appointments-grid-view">
                        <div class="appointments-grid">
                            @foreach($items as $i => $appt)
                                @php
                                    $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                                    $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                                    $dateLabel = Carbon::parse($appt->appointment_date)->format('M j, Y');
                                    $weekday = Carbon::parse($appt->appointment_date)->format('l');
                                    $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') : '—';
                                    $serviceLabel = ($appt->service_type ?? '') === 'Others'
                                        ? (($appt->other_services ?? '') ?: 'Others')
                                        : ($appt->service_type ?? '—');

                                    $serviceLower = strtolower($serviceLabel);
                                    $badgeClass = 'service-badge-default';
                                    if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                                    elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                                    elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                                    elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                                @endphp

                                <div class="mobile-appt-card opacity-70 border-gray-200" style="animation-delay:{{ $i * 0.04 }}s">
                                    <div class="mobile-appt-top">
                                        <div class="mobile-appt-patient">
                                            <div class="mobile-appt-name text-gray-500">{{ $patientName }}</div>
                                            <div class="mobile-appt-sub">{{ $weekday }}, {{ $dateLabel }}</div>
                                        </div>

                                        <span class="status-pill status-completed flex-shrink-0">
                                            <span class="status-dot"></span>Past
                                        </span>
                                    </div>

                                    <div class="mobile-appt-meta">
                                        <div class="mobile-appt-field">
                                            <div class="mobile-appt-label">Appointment Details</div>
                                            <div class="mobile-appt-badges">
                                                <span class="time-chip text-xs text-gray-400">
                                                    <i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}
                                                </span>
                                                <span class="service-badge {{ $badgeClass }} opacity-70 text-xs">
                                                    {{ $serviceLabel }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mobile-appt-field">
                                            <div class="mobile-appt-label">Program</div>
                                            <div class="mobile-appt-value text-gray-400">
                                                @if($program === '—')
                                                    —
                                                @else
                                                    <span class="inline-block bg-gray-100 text-gray-400 text-[11px] px-2.5 py-1 rounded-full border border-gray-200">
                                                        {{ $program }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mobile-appointments-list space-y-3">
                        @foreach($items as $i => $appt)
                            @php
                                $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                                $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                                $dateLabel = Carbon::parse($appt->appointment_date)->format('M j, Y');
                                $weekday = Carbon::parse($appt->appointment_date)->format('l');
                                $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') : '—';
                                $serviceLabel = ($appt->service_type ?? '') === 'Others'
                                    ? (($appt->other_services ?? '') ?: 'Others')
                                    : ($appt->service_type ?? '—');

                                $serviceLower = strtolower($serviceLabel);
                                $badgeClass = 'service-badge-default';
                                if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                                elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                                elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                                elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                            @endphp

                            <div class="mobile-appt-card opacity-70 border-gray-200" style="animation-delay:{{ $i * 0.04 }}s">
                                <div class="mobile-appt-top">
                                    <div class="mobile-appt-patient">
                                        <div class="mobile-appt-name text-gray-500">{{ $patientName }}</div>
                                        <div class="mobile-appt-sub">{{ $weekday }}, {{ $dateLabel }}</div>
                                    </div>

                                    <span class="status-pill status-completed flex-shrink-0">
                                        <span class="status-dot"></span>Past
                                    </span>
                                </div>

                                <div class="mobile-appt-meta">
                                    <div class="mobile-appt-field">
                                        <div class="mobile-appt-label">Appointment Details</div>
                                        <div class="mobile-appt-badges">
                                            <span class="time-chip text-xs text-gray-400">
                                                <i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}
                                            </span>
                                            <span class="service-badge {{ $badgeClass }} opacity-70 text-xs">
                                                {{ $serviceLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mobile-appt-field">
                                        <div class="mobile-appt-label">Program</div>
                                        <div class="mobile-appt-value text-gray-400">
                                            @if($program === '—')
                                                —
                                            @else
                                                <span class="inline-block bg-gray-100 text-gray-400 text-[11px] px-2.5 py-1 rounded-full border border-gray-200">
                                                    {{ $program }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                    <i class="fa-regular fa-calendar-check text-4xl sm:text-5xl mb-4 text-gray-300"></i>
                    <p class="text-base font-semibold text-gray-500">No past appointments</p>
                    <p class="text-sm mt-1">Completed appointments will appear here.</p>
                </div>
            @endforelse
        </section>

    </div>
</main>

<div id="toastContainer"></div>

{{-- ── Start Procedure Modal ── --}}
<div id="startProcedureModal" class="fixed inset-0 bg-black/45 hidden z-[1000] flex items-center justify-center p-4" onclick="handleStartBackdropClick(event)">
    <div class="start-modal-panel bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-[#8B0000]">Start Procedure</h3>
            <p class="text-sm text-gray-500 mt-1">Confirm that you want to start this appointment.</p>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-500">Patient</p>
            <p id="startPatientName" class="font-semibold text-gray-800">—</p>
            <p class="text-sm text-gray-500 mt-4">Appointment</p>
            <p id="startAppointmentDate" class="font-semibold text-gray-800">—</p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button type="button" onclick="closeStartProcedureModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600">Cancel</button>
            <button type="button" onclick="confirmStartProcedure()" class="px-4 py-2 rounded-lg bg-green-600 text-white font-semibold">Start</button>
        </div>
    </div>
</div>

{{-- ── Reschedule Modal ── --}}
<div id="rescheduleModal" class="fixed inset-0 bg-black/45 hidden z-[1000] flex items-center justify-center p-4" onclick="handleRescheduleBackdropClick(event)">
    <div class="reschedule-modal-panel bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-[#8B0000]">Reschedule Appointment</h3>
            <p class="text-sm text-gray-500 mt-1">You are about to reschedule this appointment.</p>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-500">Patient</p>
            <p id="resPatientName" class="font-semibold text-gray-800">—</p>
            <p class="text-sm text-gray-500 mt-4">Appointment</p>
            <p id="resAppointmentDate" class="font-semibold text-gray-800">—</p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button type="button" onclick="closeRescheduleModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600">Close</button>
            <button type="button" onclick="confirmReschedule()" class="px-4 py-2 rounded-lg bg-[#8B0000] text-white font-semibold">Continue</button>
        </div>
    </div>
</div>

{{-- ── Cancel Modal ── --}}
<div id="cancelAppointmentModal" class="fixed inset-0 bg-black/45 hidden z-[1000] flex items-center justify-center p-4" onclick="handleCancelBackdropClick(event)">
    <div class="cancel-modal-panel bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-[#8B0000]">Cancel Appointment</h3>
            <p class="text-sm text-gray-500 mt-1">Select a reason before cancelling this appointment.</p>
        </div>

        <div class="px-6 py-5">
            <p class="text-sm text-gray-500">Patient</p>
            <p id="cancelPatientName" class="font-semibold text-gray-800">—</p>
            <p class="text-sm text-gray-500 mt-4">Appointment</p>
            <p id="cancelAppointmentDate" class="font-semibold text-gray-800">—</p>

            <div class="mt-6">
                <p class="text-sm font-semibold text-gray-700 mb-3">Reason</p>
                <div id="cancelReasonChips" class="flex flex-wrap gap-2">
                    @foreach (['Patient no-show', 'Requested by patient', 'Conflict in schedule', 'Emergency case', 'Other'] as $reason)
                        <div class="reason-chip">
                            <input type="radio" name="cancelReason" id="reason_{{ \Illuminate\Support\Str::slug($reason) }}" value="{{ $reason }}">
                            <label for="reason_{{ \Illuminate\Support\Str::slug($reason) }}">{{ $reason }}</label>
                        </div>
                    @endforeach
                </div>
                <p id="reasonError" class="hidden text-sm text-red-600 mt-3">Please select a cancellation reason.</p>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button type="button" onclick="closeCancelAppointmentModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600">Close</button>
            <button type="button" id="confirmCancelBtn" onclick="confirmCancelAppointment()" class="px-4 py-2 rounded-lg bg-red-600 text-white font-semibold">
                <i class="fa-solid fa-ban text-xs mr-1.5"></i>Yes, Cancel
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const btnUpcoming = document.getElementById('btnUpcoming');
    const btnPast = document.getElementById('btnPast');
    const upcomingSection = document.getElementById('upcomingSection');
    const pastSection = document.getElementById('pastSection');

    if (btnUpcoming && btnPast && upcomingSection && pastSection) {
        btnUpcoming.addEventListener('click', function () {
            btnUpcoming.classList.add('active');
            btnPast.classList.remove('active');
            upcomingSection.classList.remove('hidden');
            pastSection.classList.add('hidden');
        });

        btnPast.addEventListener('click', function () {
            btnPast.classList.add('active');
            btnUpcoming.classList.remove('active');
            pastSection.classList.remove('hidden');
            upcomingSection.classList.add('hidden');
        });
    }

    function getPreferredAppointmentView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem('appointmentView') || 'list';
    }

    function applyAppointmentView(view, save = true) {
        const listViews = document.querySelectorAll('.appointments-list-view');
        const gridViews = document.querySelectorAll('.appointments-grid-view');
        const listBtn = document.getElementById('appointmentListViewBtn');
        const gridBtn = document.getElementById('appointmentGridViewBtn');

        const finalView = window.innerWidth <= 767 ? 'grid' : view;

        if (window.innerWidth <= 767) {
            listViews.forEach(el => el.hidden = true);
            gridViews.forEach(el => el.hidden = true);
        } else {
            listViews.forEach(el => el.hidden = finalView !== 'list');
            gridViews.forEach(el => el.hidden = finalView !== 'grid');
        }

        if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
        if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

        if (save && window.innerWidth > 767) {
            localStorage.setItem('appointmentView', finalView);
        }
    }

    function initAppointmentViewToggle() {
        const listBtn = document.getElementById('appointmentListViewBtn');
        const gridBtn = document.getElementById('appointmentGridViewBtn');

        applyAppointmentView(getPreferredAppointmentView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', () => applyAppointmentView('list', true));
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', () => applyAppointmentView('grid', true));
        }
    }

    function showToast({ title = 'Notice', message = '', duration = 4000 }) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast-item';
        toast.innerHTML = `
            <div class="toast-icon-wrap"><i class="fa-solid fa-ban text-red-400 text-sm"></i></div>
            <div class="flex-1 min-w-0">
                <div class="toast-title">${title}</div>
                ${message ? `<div class="toast-message">${message}</div>` : ''}
            </div>
            <button class="toast-close" onclick="dismissToast(this.closest('.toast-item'))"><i class="fa-solid fa-xmark"></i></button>
            <div class="toast-progress" style="animation-duration:${duration}ms;"></div>
        `;
        container.appendChild(toast);
        setTimeout(() => dismissToast(toast), duration);
    }

    function dismissToast(toast) {
        if (!toast || toast.classList.contains('toast-exit')) return;
        toast.classList.add('toast-exit');
        setTimeout(() => toast.remove(), 350);
    }

    let selectedApptId = null;
    let cancelPatientNameCache = '';

    function openRescheduleModal(btn) {
        selectedApptId = btn.dataset.id;
        document.getElementById('resPatientName').textContent = btn.dataset.name || '—';
        document.getElementById('resAppointmentDate').textContent = btn.dataset.datetime || '—';

        const modal = document.getElementById('rescheduleModal');
        modal.classList.remove('hidden');

        const panel = modal.querySelector('.reschedule-modal-panel');
        if (panel) {
            panel.style.animation = 'none';
            requestAnimationFrame(() => requestAnimationFrame(() => {
                panel.style.animation = '';
            }));
        }
    }

    function closeRescheduleModal() {
        document.getElementById('rescheduleModal').classList.add('hidden');
        selectedApptId = null;
    }

    function handleRescheduleBackdropClick(e) {
        if (e.target === document.getElementById('rescheduleModal')) {
            closeRescheduleModal();
        }
    }

    function confirmReschedule() {
        if (!selectedApptId) return;
        var url = "{{ route('admin.admin.appointments.reschedule', ['id' => ':id']) }}".replace(':id', selectedApptId);
        window.location.href = url;
    }

    function openStartProcedureModal(btn) {
        selectedApptId = btn.dataset.id;
        document.getElementById('startPatientName').textContent = btn.dataset.name || '—';
        document.getElementById('startAppointmentDate').textContent = btn.dataset.datetime || '—';
        document.getElementById('startProcedureModal').classList.remove('hidden');
    }

    function closeStartProcedureModal() {
        document.getElementById('startProcedureModal').classList.add('hidden');
        selectedApptId = null;
    }

    function handleStartBackdropClick(e) {
        if (e.target === document.getElementById('startProcedureModal')) {
            closeStartProcedureModal();
        }
    }

    function confirmStartProcedure() {
        if (!selectedApptId) return;
        window.location.href = `/admin/appointments/${selectedApptId}/start`;
    }

    function openCancelAppointmentModal(btn) {
        selectedApptId = btn.dataset.id;
        cancelPatientNameCache = btn.dataset.name || 'this patient';

        document.getElementById('cancelPatientName').textContent = btn.dataset.name || '—';
        document.getElementById('cancelAppointmentDate').textContent = btn.dataset.datetime || '—';

        document.querySelectorAll('input[name="cancelReason"]').forEach(r => r.checked = false);
        clearReasonError();

        const confirmBtn = document.getElementById('confirmCancelBtn');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa-solid fa-ban text-xs mr-1.5"></i>Yes, Cancel';
        }

        const modal = document.getElementById('cancelAppointmentModal');
        modal.classList.remove('hidden');

        const panel = modal.querySelector('.cancel-modal-panel');
        if (panel) {
            panel.style.animation = 'none';
            requestAnimationFrame(() => requestAnimationFrame(() => {
                panel.style.animation = '';
            }));
        }
    }

    function closeCancelAppointmentModal() {
        document.getElementById('cancelAppointmentModal').classList.add('hidden');
        selectedApptId = null;
    }

    function handleCancelBackdropClick(e) {
        if (e.target === document.getElementById('cancelAppointmentModal')) {
            closeCancelAppointmentModal();
        }
    }

    function clearReasonError() {
        const chips = document.getElementById('cancelReasonChips');
        const error = document.getElementById('reasonError');

        if (chips) chips.classList.remove('invalid', 'chips-error-shake');
        if (error) error.classList.add('hidden');
    }

    document.querySelectorAll('input[name="cancelReason"]').forEach(r => {
        r.addEventListener('change', clearReasonError);
    });

    function confirmCancelAppointment() {
        var selectedReason = document.querySelector('input[name="cancelReason"]:checked')?.value || null;

        if (!selectedReason) {
            var chips = document.getElementById('cancelReasonChips');
            var error = document.getElementById('reasonError');

            if (error) error.classList.remove('hidden');
            if (chips) {
                chips.classList.add('invalid');
                chips.classList.remove('chips-error-shake');
                void chips.offsetWidth;
                chips.classList.add('chips-error-shake');
            }
            return;
        }

        var btn = document.getElementById('confirmCancelBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-xs mr-1.5"></i>Cancelling…';
        }

        var patientName = cancelPatientNameCache;
        var apptId = selectedApptId;

        closeCancelAppointmentModal();

        fetch(`/admin/appointments/${apptId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                reason: selectedReason
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll(`[data-appt-id="${apptId}"]`).forEach(card => {
                    card.style.transition = 'all 0.35s cubic-bezier(.4,0,.2,1)';
                    card.style.overflow = 'hidden';

                    requestAnimationFrame(() => {
                        card.style.maxHeight = card.offsetHeight + 'px';
                        requestAnimationFrame(() => {
                            card.style.maxHeight = '0';
                            card.style.opacity = '0';
                            card.style.transform = 'scaleY(0.85) translateX(-8px)';
                            card.style.marginBottom = '0';
                            card.style.paddingTop = '0';
                            card.style.paddingBottom = '0';
                        });
                    });

                    setTimeout(() => card.remove(), 380);
                });

                showToast({
                    title: 'Appointment Cancelled',
                    message: `${patientName}'s appointment has been successfully cancelled.`,
                    duration: 5000
                });
            } else {
                showToast({
                    title: 'Cancel Failed',
                    message: data.message || 'Something went wrong.',
                    duration: 4000
                });
            }
        })
        .catch(() => {
            showToast({
                title: 'Network Error',
                message: 'Could not reach the server.',
                duration: 4000
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAppointmentViewToggle();

        window.addEventListener('resize', function () {
            applyAppointmentView(getPreferredAppointmentView(), false);
        });
    });
</script>
@endsection