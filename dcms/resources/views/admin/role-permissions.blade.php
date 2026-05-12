@extends('layouts.admin')

@section('title', 'Role Permissions | Admin Dashboard')

@section('styles')
    <style>
        #toastContainer {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            bottom: unset !important;
            left: unset !important;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
            width: auto !important;
            padding: 0 !important;
        }

        #toastContainer>div {
            min-width: 300px !important;
            max-width: 360px !important;
            background: white !important;
            border-radius: 14px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .15) !important;
            padding: 14px 16px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            opacity: 0;
            transform: translateX(340px);
            transition: all .35s cubic-bezier(.68, -.55, .265, 1.55);
            position: relative !important;
            overflow: hidden !important;
            pointer-events: all !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            justify-content: flex-start !important;
            border: none !important;
            height: auto !important;
            min-height: 60px !important;
        }

        #toastContainer .toast::before {
            content: '';
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            bottom: 0 !important;
            width: 3px !important;
            background: none !important;
        }

        #toastContainer .toast.error::before {
            background: #DC2626 !important;
        }

        #toastContainer .toast.success::before {
            background: #16a34a !important;
        }

        #toastContainer .toast.show {
            opacity: 1 !important;
            transform: translateX(0) !important;
        }

        #toastContainer .toast.hide {
            opacity: 0 !important;
            transform: translateX(340px) !important;
        }

        .toast-icon-wrap {
            width: 34px !important;
            height: 34px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .toast.error .toast-icon-wrap {
            background: #FEF2F2 !important;
        }

        .toast.success .toast-icon-wrap {
            background: #F0FDF4 !important;
        }

        .toast-icon {
            font-size: 15px !important;
            margin: 0 !important;
        }

        .toast.error .toast-icon {
            color: #DC2626 !important;
        }

        .toast.success .toast-icon {
            color: #16a34a !important;
        }

        .toast-body {
            flex: 1 !important;
            min-width: 0 !important;
            text-align: left !important;
            background: transparent !important;
        }

        .toast-title {
            font-size: 13px !important;
            font-weight: 800 !important;
            color: #333333 !important;
            margin: 0 !important;
            line-height: 1.2 !important;
        }

        .toast-msg {
            font-size: 11px !important;
            color: #6b7280 !important;
            margin-top: 3px !important;
            line-height: 1.4 !important;
            white-space: normal !important;
        }

        .toast-close {
            background: transparent !important;
            border: none !important;
            cursor: pointer !important;
            color: #d1d5db !important;
            font-size: 13px !important;
            flex-shrink: 0 !important;
            padding: 4px !important;
            transition: color .15s !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            align-self: flex-start !important;
            margin-left: auto !important;
            width: auto !important;
            height: auto !important;
        }

        .toast-close:hover {
            color: #6b7280 !important;
            background: transparent !important;
        }

        /* Dark mode overrides for Toasts */
        [data-theme="dark"] #toastContainer>div {
            background: #161b22 !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .4) !important;
        }

        [data-theme="dark"] .toast-title {
            color: #f3f4f6 !important;
        }

        [data-theme="dark"] .toast.error .toast-icon-wrap {
            background: rgba(239, 68, 68, .1) !important;
        }

        [data-theme="dark"] .toast.success .toast-icon-wrap {
            background: rgba(34, 197, 94, .1) !important;
        }

        * {
            box-sizing: border-box;
        }

        .role-permission-shell {
            padding: 1.5rem 1.75rem 2rem;
        }

        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        @keyframes micPulse {
            0%, 100% { box-shadow: 0 0 0 0px rgba(192, 57, 43, 0.4); }
            50%       { box-shadow: 0 0 0 8px rgba(192, 57, 43, 0); }
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

    .page-banner-date {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .75rem;
        color: rgba(255,255,255,.85);
        margin-bottom: .3rem;
    }

    .page-banner-date i {
        color: #fff;
    }

    .page-banner-title {
        font-size: 2rem;
        font-weight: 900;
        color: #fff;
    }

    .page-banner-title span {
        color: #ffdede;
    }

    .page-banner-subtitle {
        font-size: .8rem;
        color: rgba(255,255,255,.7);
        margin-top: .3rem;
    }
    
    .page-banner-actions {
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
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
        flex-shrink: 0;
        position: relative;
        z-index: 5;
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

    .role-list-container.role-list-view {
        display: block;
    }

    .role-list-container.role-grid-view {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .role-list-container.role-grid-view .role-card {
        margin-bottom: 0;
        height: 100%;
    }


    .btn-new-role {
        background: linear-gradient(135deg, var(--crimson) 0%, var(--crimson-dark) 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: .8rem 1.25rem;
        font-weight: 700;
        font-size: .85rem;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(139, 0, 0, 0.25);
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: .5rem;
        font-family: 'Inter', sans-serif;
    }

    .btn-new-role:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 0, 0, 0.35);
    }

    .main-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 1.5rem;
        align-items: start;
    }

    .role-list-header {
        font-size: .65rem;
        color: #6b7280;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 12px;
        font-weight: 800;
    }

    .role-card {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .06);
        border-radius: 14px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(.4, 0, .2, 1);
        margin-bottom: 10px;
        position: relative;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .02);
    }

    .role-card:last-child {
        margin-bottom: 0;
    }

    .role-card:hover {
        transform: translateX(3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, .05);
    }

    .role-card.active {
        border-color: var(--crimson);
        box-shadow: 0 4px 20px rgba(139, 0, 0, 0.1);
    }

    .role-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 12px;
        flex-shrink: 0;
        transition: all 0.2s;
        background: #f3f4f6;
        color: #6b7280;
    }

    .role-card.active .role-avatar {
        background: linear-gradient(135deg, var(--crimson), #c0392b);
        color: #fff;
        box-shadow: 0 4px 10px rgba(139, 0, 0, 0.25);
    }

    .badge-pill {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .progress-bar {
        height: 4px;
        background: #f3f4f6;
        border-radius: 10px;
        overflow: hidden;
        margin: 4px 0;
    }

    .progress-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.4s ease;
        background: #d1d5db;
    }

    .role-card.active .progress-fill {
        background: linear-gradient(90deg, var(--crimson), #e11d48);
    }

    .btn-delete-role {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 26px;
        height: 26px;
        border-radius: 7px;
        border: none;
        background: transparent;
        color: #9ca3af;
        font-size: 11px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all .15s;
        z-index: 10;
    }

    .role-card:hover .btn-delete-role {
        opacity: 1;
    }

    .btn-delete-role:hover {
        background: #FEE2E2;
        color: #DC2626;
    }

    .accent-card {
        background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 100%);
        border-radius: 14px;
        padding: 18px 20px;
        color: #fff;
        margin-top: 16px;
        box-shadow: 0 10px 25px rgba(139, 0, 0, .15);
    }

    .card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, .05);
        box-shadow: 0 4px 20px rgba(0, 0, 0, .03);
        overflow: visible;
    }

    .card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fafafa;
        flex-wrap: wrap;
        gap: 10px;
    }

    .st-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .perm-search-row {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex: 1;
        min-width: 240px;
    }

    .perm-search-row .st-input-wrap {
        flex: 1 1 auto;
        min-width: 0;
    }

    .perm-search-clear-btn {
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

    .perm-search-clear-btn.hidden {
        display: none;
    }

    .perm-search-clear-btn:hover {
        color: #991b1b;
    }

    .perm-search-row .st-input-wrap.voice-search-wrap {
        position: relative;
        padding-right: 42px;
    }

    .perm-search-row .st-input-wrap.voice-search-wrap .st-input.has-voice-padding,
    .perm-search-row .st-input-wrap.voice-search-wrap .voice-search-input {
        padding-right: 0 !important;
    }

    .perm-search-row .st-input-wrap.voice-search-wrap .voice-mic-btn,
    .perm-search-row .st-input-wrap.voice-search-wrap .voice-search-mic {
        right: 14px;
        z-index: 5;
        color: #8B0000;
    }

    .perm-search-row .st-input-wrap.voice-search-wrap .voice-mic-btn:hover,
    .perm-search-row .st-input-wrap.voice-search-wrap .voice-search-mic:hover,
    .perm-search-row .st-input-wrap.voice-search-wrap .voice-search-mic.text-\[\#8B0000\] {
        color: #660000;
    }

    .perm-search-row .st-input-wrap [data-voice-status] {
        top: -1.35rem;
    }

    /* Hide any legacy/injected inline mic toggle inside the input wrap */
    .perm-search-row .voice-mic-btn,
    .perm-search-row [data-voice-trigger] {
        display: none !important;
    }

    /* External circular mic button (match patient list) */
    .perm-search-row .voice-search-mic.external {
        display: inline-flex !important;
        width: 40px;
        height: 40px;
        border-radius: 999px;
        align-items: center;
        justify-content: center;
        background: #4b5563;
        color: #ffffff;
        box-shadow: 0 6px 18px rgba(75,85,99,0.12);
        border: none;
        margin-left: 0;
        flex-shrink: 0;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        position: relative;
    }

    .perm-search-row .voice-search-mic.external:hover {
        background: #374151;
    }

    .perm-search-row .voice-search-mic.external i {
        font-size: 12px;
        line-height: 1;
    }

    .perm-search-row .patient-voice-status {
        position: absolute;
        right: 0;
        top: -1.35rem;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        font-size: .62rem;
        font-weight: 700;
        line-height: 1;
        padding: .08rem .28rem;
        border-radius: 999px;
        pointer-events: none;
        z-index: 6;
        background: rgba(255, 255, 255, .92);
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
    }

    .perm-search-row .patient-voice-status.hidden {
        display: none;
    }

    .perm-search-row .patient-voice-status.is-listening {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .perm-search-row .patient-voice-status.is-error {
        color: #b91c1c;
        border-color: #fecaca;
        background: #fef2f2;
    }

    .perm-search-row .patient-voice-status.is-success {
        color: #166534;
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .perm-search-row .voice-search-mic.external.mic-active {
        background: #c0392b;
        transform: scale(1.1);
        animation: micPulse 1.2s ease-in-out infinite;
    }


    .voice-mic-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        width: 18px;
        height: 18px;
        font-size: 13px;
        line-height: 1;
        padding: 0;
        margin: 0;
        color: #6b7280;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        z-index: 4;
    }

    .voice-mic-btn:hover {
        color: #8B0000;
    }

    .st-input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 12px;
        pointer-events: none;
    }

    .st-input {
        width: 100%;
        padding: 10px 40px 10px 34px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: .8rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: all .2s;
    }

    .st-input.has-voice-padding {
        padding-right: 40px;
    }

    .st-input-wrap [data-voice-status] {
        position: absolute;
        right: 0;
        top: -0.75rem;
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

    .st-input-wrap [data-voice-status].hidden {
        display: none;
    }

    .st-input-wrap [data-voice-status].is-listening {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .st-input-wrap [data-voice-status].is-error {
        color: #b91c1c;
        border-color: #fecaca;
        background: #fef2f2;
    }

    .st-input-wrap [data-voice-status].is-success {
        color: #166534;
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .st-input:focus {
        border-color: var(--crimson);
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
    }

    .btn-collapse,
    .btn-reset {
        padding: 9px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        font-size: .75rem;
        font-weight: 600;
        cursor: pointer;
        color: #4b5563;
        transition: all .15s;
        font-family: 'Inter', sans-serif;
    }

    .btn-collapse:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .btn-reset:hover {
        background: #fef2f2;
        border-color: #fca5a5;
        color: var(--crimson);
    }

    .protected-banner {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 12px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    /* Permission Groups */
    .group-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 10px;
    }

    .perm-group-header {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: #fafafa;
        cursor: pointer;
        transition: background 0.15s;
        user-select: none;
    }

    .perm-group-header:hover {
        background: #f3f4f6;
    }

    .perm-group-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
        margin-right: 12px;
    }

    .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #e5e7eb;
        transition: background 0.2s;
    }

    .all-toggle-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 4px 10px;
        cursor: pointer;
    }

    /* Custom Toggle Switch */
    .toggle-switch {
        position: relative;
        width: 42px;
        height: 24px;
        display: inline-block;
        flex-shrink: 0;
        margin: 0;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }

    .toggle-track {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: #e5e7eb;
        border-radius: 12px;
        transition: all 0.2s;
    }

    .toggle-track::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
        transition: all 0.2s;
    }

    .toggle-switch input:checked+.toggle-track {
        background: var(--crimson);
    }

    .toggle-switch input:checked+.toggle-track::after {
        transform: translateX(18px);
    }

    .toggle-switch.disabled .toggle-track {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .perm-group-body {
        border-top: 1px solid #f3f4f6;
        overflow: hidden;
        transition: max-height 0.3s ease, opacity 0.2s ease;
        max-height: 9999px;
        opacity: 1;
    }

    .perm-group-body.collapsed {
        max-height: 0;
        opacity: 0;
        border-top: none;
    }

    .chevron {
        transition: transform 0.2s;
        color: #9ca3af;
        font-size: 11px;
    }

    .chevron.collapsed {
        transform: rotate(180deg);
    }

    .perm-row {
        display: flex;
        align-items: center;
        padding: 12px 16px 12px 60px;
        border-bottom: 1px solid #f9fafb;
        transition: background 0.15s;
    }

    .perm-row:last-child {
        border-bottom: none;
    }

    .perm-row:hover {
        background: #fdfcfb;
    }

    .status-granted {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        text-transform: uppercase;
    }

    .status-denied {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        background: #f3f4f6;
        color: #9ca3af;
        text-transform: uppercase;
    }

    /* View As Toolbar Button */
    .btn-view-as {
        display: none;
        align-items: center;
        gap: 8px;
        background: #f0f9ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 9px 14px;
        font-weight: 600;
        font-size: .75rem;
        cursor: pointer;
        transition: all 0.15s;
        position: relative;
        white-space: nowrap;
        font-family: 'Inter', sans-serif;
    }

    .btn-view-as.show {
        display: flex;
    }

    .btn-view-as:hover {
        background: #e0f2fe;
        border-color: #93c5fd;
    }

    .btn-view-as.fsb-view-as {
        background: rgba(59, 130, 246, 0.15);
        color: #93c5fd;
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 30px;
        padding: 8px 16px;
    }

    .btn-view-as.fsb-view-as:hover {
        background: rgba(59, 130, 246, 0.25);
        border-color: rgba(59, 130, 246, 0.5);
    }

    .va-count-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #ef4444;
        color: #fff;
        font-size: 9px;
        font-weight: 800;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ══════════════════════════════════════
            FLOATING SAVE BAR (UNSAVED CHANGES)
        ══════════════════════════════════════ */
    .floating-save-bar {
        position: fixed;
        bottom: -100px;
        left: 50%;
        transform: translateX(-50%);
        background: #111827;
        border-radius: 50px;
        padding: 10px 10px 10px 24px;
        display: flex;
        align-items: center;
        gap: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        transition: bottom 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s;
        opacity: 0;
        pointer-events: none;
        width: max-content;
        max-width: 90vw;
    }

    .floating-save-bar.show {
        bottom: 32px;
        opacity: 1;
        pointer-events: auto;
    }

    .fsb-text {
        color: #fff;
        display: flex;
        flex-direction: column;
    }

    .fsb-title {
        font-size: .85rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .fsb-sub {
        font-size: .7rem;
        color: #9ca3af;
    }

    .fsb-actions {
        display: flex;
        gap: 8px;
    }

    .btn-discard {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 30px;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        font-family: 'Inter', sans-serif;
    }

    .btn-discard:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .btn-save-float {
        background: #fff;
        color: #111827;
        border: none;
        padding: 8px 20px;
        border-radius: 30px;
        font-size: .8rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
        font-family: 'Inter', sans-serif;
    }

    .btn-save-float:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
    }

    /* ══════════════════════════════════════
            MODALS (MODERN PILL STYLE)
        ══════════════════════════════════════ */
    .modern-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        margin: 0;
        border: none;
        padding: 32px 24px 24px;
        border-radius: 20px;
        width: min(90vw, 400px);
        max-width: calc(100vw - 2rem);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        background: #ffffff;
        text-align: center;
        overflow: visible;
    }

    .modern-modal::backdrop {
        background: rgba(17, 24, 39, 0.6);
        backdrop-filter: blur(4px);
    }

    .modern-modal[open] {
        display: block;
    }

    .modal-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 26px;
        border: 6px solid #fff;
    }

    .modal-icon.danger {
        background: #fef2f2;
        color: #ef4444;
        box-shadow: 0 0 0 1px #fee2e2;
    }

    .modal-icon.warning {
        background: #fffbeb;
        color: #f59e0b;
        box-shadow: 0 0 0 1px #fef3c7;
    }

    .modal-icon.primary {
        background: #eff6ff;
        color: #3b82f6;
        box-shadow: 0 0 0 1px #dbeafe;
    }

    .modal-icon.success {
        background: #f0fdf4;
        color: #22c55e;
        box-shadow: 0 0 0 1px #bbf7d0;
    }

    .modal-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #111827;
        margin: 0 0 8px;
    }

    .modal-body {
        font-size: .85rem;
        color: #6b7280;
        line-height: 1.6;
        margin: 0 0 24px;
    }

    .modal-highlight {
        display: inline-block;
        font-size: .9rem;
        font-weight: 700;
        color: #111827;
        background: #f3f4f6;
        padding: 4px 10px;
        border-radius: 6px;
        margin: 8px 0;
        word-break: break-all;
    }

    .modal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .modal-btn-cancel {
        padding: 10px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        color: #374151;
        font-weight: 600;
        font-size: .85rem;
        cursor: pointer;
        transition: all .15s;
    }

    .modal-btn-cancel:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .modal-btn-confirm {
        padding: 10px;
        border-radius: 10px;
        border: none;
        color: #ffffff !important;
        font-weight: 600;
        font-size: .85rem;
        cursor: pointer;
        transition: all .15s;
        width: 100%;
    }

    .modal-btn-confirm.danger {
        background: #ef4444;
    }

    .modal-btn-confirm.danger:hover {
        background: #dc2626;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
    }

    .modal-btn-confirm.warning {
        background: #f59e0b;
    }

    .modal-btn-confirm.warning:hover {
        background: #d97706;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
    }

    .modal-btn-confirm.primary {
        background: linear-gradient(135deg, var(--crimson), var(--crimson-dark));
    }

    .modal-btn-confirm.primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(139, 0, 0, 0.3);
    }

    .modal-form-group {
        text-align: left;
        margin-bottom: 16px;
    }

    .modal-label {
        display: block;
        font-size: .7rem;
        font-weight: 700;
        color: #4b5563;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    /* View As Panel (Specific Modal) */
    #vaOverlay,
    #patientPickerOverlay {
        position: fixed;
        inset: 0;
        background: rgba(17, 24, 39, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 300;
        backdrop-filter: blur(4px);
        opacity: 0;
        pointer-events: none;
        transition: opacity .25s;
    }

    #vaOverlay.open,
    #patientPickerOverlay.open {
        opacity: 1;
        pointer-events: auto;
    }

    .va-panel {
        background: #fff;
        border-radius: 20px;
        width: 640px;
        max-width: 94vw;
        max-height: 85vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: scale(.95);
        transition: transform .3s;
    }

    #vaOverlay.open .va-panel,
    #patientPickerOverlay.open .va-panel {
        transform: scale(1);
    }

    .va-head {
        padding: 20px 24px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 14px;
        background: #fafafa;
    }

    .va-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px 24px;
    }

    .va-foot {
        padding: 16px 24px;
        border-top: 1px solid #f3f4f6;
        display: flex;
        justify-content: flex-end;
        background: #fafafa;
    }

    .va-role-row {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all .2s;
        margin-bottom: 10px;
    }

    .va-role-row:hover {
        border-color: #93c5fd;
        box-shadow: 0 4px 12px rgba(59, 130, 246, .1);
        transform: translateY(-1px);
    }

    .va-go-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        border: none;
        border-radius: 8px;
        padding: 8px 14px;
        font-weight: 700;
        font-size: .75rem;
        cursor: pointer;
        transition: all .2s;
    }

    .va-go-btn:hover {
        background: #dbeafe;
    }

    /* Redirect Overlay */
    .redirect-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity .3s;
    }

    .redirect-overlay.show {
        opacity: 1;
        pointer-events: auto;
    }

    .redirect-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid rgba(255, 255, 255, .2);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .7s linear infinite;
        margin-bottom: 16px;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Dark Mode Overrides */
    [data-theme="dark"] .card {
        background: #161b22;
        border-color: #21262d;
    }

    [data-theme="dark"] .card-header,
    [data-theme="dark"] .perm-group-header,
    [data-theme="dark"] .va-head,
    [data-theme="dark"] .va-foot {
        background: #0d1117;
        border-color: #21262d;
    }

    [data-theme="dark"] .st-input {
        background: #0d1117;
        border-color: #21262d;
        color: #f3f4f6;
    }

    [data-theme="dark"] .role-card,
    [data-theme="dark"] .group-card,
    [data-theme="dark"] .va-role-row {
        background: #161b22;
        border-color: #21262d;
    }

    [data-theme="dark"] .role-card:hover {
        background: #1c2128;
    }

    [data-theme="dark"] .perm-row {
        border-color: #1c2128;
    }

    [data-theme="dark"] .perm-row:hover {
        background: #1c2128;
    }

    [data-theme="dark"] .modern-modal,
    [data-theme="dark"] .va-panel {
        background: #1f2937;
    }

    [data-theme="dark"] .page-title,
    [data-theme="dark"] .modal-title,
    [data-theme="dark"] .role-name-label {
        color: #f9fafb;
    }

    [data-theme="dark"] .page-subtitle,
    [data-theme="dark"] .modal-body,
    [data-theme="dark"] .role-list-header {
        color: #9ca3af;
    }

    [data-theme="dark"] .modal-btn-cancel,
    [data-theme="dark"] .btn-collapse,
    [data-theme="dark"] .btn-reset {
        background: #374151;
        border-color: #4b5563;
        color: #d1d5db;
    }

    [data-theme="dark"] .modal-highlight {
        background: #374151;
        color: #f3f4f6;
    }

    [data-theme="dark"] .floating-save-bar {
        background: #1f2937;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        border: 1px solid #374151;
    }

    [data-theme="dark"] .btn-save-float {
        background: #fff;
        color: #111827;
    }

    [data-theme="dark"] .fsb-sub {
        color: #9ca3af;
    }

    [data-theme="dark"] .btn-view-as.fsb-view-as {
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.2);
    }

    @media (max-width: 1024px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        #mainContent {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .role-permission-shell {
            padding: 1rem 1rem 1.5rem;
        }

        .page-banner {
            padding: 1rem 1rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1rem;
        }

        .page-banner-inner {
            flex-direction: column;
            align-items: stretch;
            gap: .75rem;
        }

        .page-banner-title {
            font-size: 1.45rem;
            line-height: 1.1;
        }

        .page-banner-actions {
            width: 100%;
        }

        .btn-new-role {
            width: 100%;
            justify-content: center;
        }

        #rolePermissionViewToggle {
            display: none !important;
        }

        #roleListContainer {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        #roleListContainer .role-card {
            margin-bottom: 0;
            padding: 12px;
        }

        #roleListContainer .role-avatar {
            width: 34px;
            height: 34px;
            font-size: 11px;
            border-radius: 9px;
        }

        #roleListContainer .role-name-label {
            font-size: 12px !important;
        }

        #roleListContainer .badge-pill {
            font-size: .58rem;
            padding: 2px 8px;
        }

        #roleListContainer .progress-bar {
            height: 3px;
        }

        #roleListContainer .count-label,
        #roleListContainer .pct-label {
            font-size: 9px !important;
        }

        .main-grid {
            grid-template-columns: 1fr !important;
            gap: 1rem;
        }

        .card-header {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .card-header > div:first-child {
            width: 100%;
            min-width: 0;
        }

        .card-header > div:last-child {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            align-items: stretch;
        }

        #globalViewAsBtn {
            grid-column: 1 / -1;
        }

        .btn-collapse,
        .btn-reset,
        .btn-view-as {
            width: 100%;
            min-height: 42px;
            text-align: center;
            justify-content: center;
            display: flex;
            align-items: center;
        }

        .role-card {
            padding: 13px 14px;
        }

        .accent-card {
            margin-top: 12px;
            padding: 16px 16px;
        }

        .perm-group-header {
            padding: 12px;
            display: grid;
            grid-template-columns: 32px 1fr;
            align-items: start;
            column-gap: 10px;
            row-gap: 10px;
        }

        .perm-group-icon {
            width: 32px;
            height: 32px;
            margin-right: 0;
            grid-column: 1;
            grid-row: 1 / span 2;
        }

        .perm-group-header > div:nth-child(2) {
            grid-column: 2;
            grid-row: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .perm-group-header > div:nth-child(2) > div:first-child {
            font-size: 12px !important;
            line-height: 1.2;
            word-break: break-word;
        }

        .perm-group-header > div:nth-child(2) > .group-count {
            font-size: 10px !important;
            line-height: 1.2;
            margin-top: 0;
        }

        .perm-group-header > div:last-child {
            grid-column: 2;
            grid-row: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-width: 0;
            width: 100%;
        }

        .dot-row {
            display: flex;
            align-items: center;
            gap: 3px;
            flex-wrap: nowrap;
            overflow: hidden;
            max-width: none;
            flex: 1 1 auto;
        }

        .all-toggle-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: 3px 8px;
            min-height: 30px;
            flex-shrink: 0;
        }

        .all-toggle-wrap span {
            font-size: 9px !important;
            line-height: 1;
        }

        .chevron {
            margin-left: 0;
            flex-shrink: 0;
            font-size: 11px;
        }
        .perm-row {
            padding: 10px 12px 10px 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .modern-modal {
            width: calc(100vw - 1.5rem);
            max-width: calc(100vw - 1.5rem);
            padding: 24px 16px 18px;
            border-radius: 18px;
        }
    }

    @media (max-width: 480px) {
        #roleListContainer {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 420px) {
        .perm-group-header {
            grid-template-columns: 32px 1fr;
        }

        .perm-group-header > div:nth-child(2) {
            grid-column: 2;
            grid-row: 1;
        }

        .perm-group-header > div:last-child {
            grid-column: 2;
            grid-row: 2;
            justify-content: space-between;
            width: 100%;
            padding-left: 0;
        }

        .dot-row {
            overflow: visible;
        }
    }
    </style>
@endsection

@php
    $logs = $logs ?? collect([]);
    $totalCount = $logs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $logs->total() : $logs->count();
@endphp

@section('content')
    <main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-screen">
        <div class="role-permission-shell" style="max-width: 1300px; margin: 0 auto;">

            <div class="page-banner">
                <div class="page-banner-inner">
                    <h1 class="page-banner-title">Roles & Permissions</h1>

                    <div class="page-banner-actions">
                        <button type="button" class="btn-new-role" onclick="openNewRoleModal()"
                            style="background:white; color:#8B0000;">
                            <i class="fa-solid fa-plus"></i> New Role
                        </button>

                        <div class="view-toggle" id="rolePermissionViewToggle" onclick="event.stopPropagation()">
                            <button type="button" class="view-toggle-btn active" id="roleListViewBtn" title="List view" aria-label="List view">
                                <i class="fa-solid fa-table-list"></i>
                            </button>
                            <button type="button" class="view-toggle-btn" id="roleGridViewBtn" title="Grid view" aria-label="Grid view">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-grid">

                <div>
                    @php
                        function getRoleBadge($name, $slug)
                        {
                            $n = strtolower($name);
                            $s = strtolower($slug);

                            if (str_contains($n, 'super') || str_contains($s, 'super') || $s === 'admin') {
                                return ['badgeColor' => '#7B0D0D', 'label' => 'Full Access'];
                            }

                            if (str_contains($n, 'dentist') || str_contains($s, 'dentist')) {
                                return ['badgeColor' => '#d97706', 'label' => 'Clinical'];
                            }

                            if (str_contains($n, 'staff') || str_contains($s, 'staff') || str_contains($n, 'clinic')) {
                                return ['badgeColor' => '#059669', 'label' => 'Front Desk'];
                            }

                            if (
                                str_contains($n, 'student') ||
                                str_contains($s, 'student') ||
                                str_contains($n, 'patient') ||
                                str_contains($s, 'patient')
                            ) {
                                return ['badgeColor' => '#4b5563', 'label' => 'Limited'];
                            }

                            return ['badgeColor' => '#6B7280', 'label' => 'Custom'];
                        }
                        $totalPerms = $groupedPermissions->flatten()->count();
                    @endphp

                    <div class="role-list-header">Active Roles ({{ $roles->count() }})</div>

                    <div class="role-list-container role-list-view" id="roleListContainer">
                        @foreach ($roles as $i => $role)
                            @php
                                $c = getRoleBadge($role->name, $role->slug);
                                $granted = $role->permissions->count();
                                $pct = $totalPerms > 0 ? round(($granted / $totalPerms) * 100) : 0;
                                $words = array_slice(explode(' ', $role->name), 0, 2);
                                $initials = '';
                                foreach ($words as $_w) {
                                    $initials .= strtoupper($_w[0]);
                                }
                                $isHighlighted = isset($highlightRoleId) && (int) $highlightRoleId === (int) $role->id;
                                $isFirst = isset($highlightRoleId) ? $isHighlighted : $i === 0;
                                $isSuperRole =
                                    in_array(strtolower($role->slug), ['super_admin', 'super-admin', 'superadmin']) ||
                                    str_contains(strtolower($role->name), 'super');
                                $isProtectedRole =
                                    $isSuperRole || in_array(strtolower($role->slug), ['admin', 'patient', 'dentist']);
                            @endphp

                            <div class="role-card {{ $isFirst ? 'active' : '' }}" data-role-id="{{ $role->id }}"
                                data-role-name="{{ $role->display_name }}" data-granted="{{ $granted }}"
                                data-total="{{ $totalPerms }}" data-pct="{{ $pct }}"
                                data-slug="{{ $role->slug }}" data-is-super="{{ $isSuperRole ? '1' : '0' }}"
                                onclick="selectRole(this)">

                                @if (!$isProtectedRole)
                                    <button type="button" class="btn-delete-role"
                                        onclick="event.stopPropagation(); openDeleteModal('{{ $role->id }}', '{{ addslashes($role->name) }}')"
                                        title="Delete role">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                @endif

                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="role-avatar">{{ $initials }}</div>
                                    <div style="flex:1; min-width:0;">
                                        <div style="display:flex; align-items:center; gap:7px; margin-bottom:3px;">
                                            <span
                                                style="font-weight:700; font-size:13px; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                                class="role-name-label">{{ $role->display_name }}</span>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <span class="badge-pill"
                                                style="background:{{ $c['badgeColor'] }}15; color:{{ $c['badgeColor'] }};">{{ $c['label'] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-top:12px;">
                                    <div
                                        style="display:flex; justify-content:space-between; font-size:10px; color:#9ca3af; font-weight:700; text-transform:uppercase;">
                                        <span>Access</span>
                                        <span class="pct-label">{{ $pct }}%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width:{{ $pct }}%;"></div>
                                    </div>
                                    <div style="font-size:10px; color:#9ca3af;" class="count-label">
                                        {{ $granted }} / {{ $totalPerms }} permissions
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="accent-card">
                        @php
                            $fr = isset($highlightRoleId)
                                ? $roles->firstWhere('id', (int) $highlightRoleId)
                                : $roles->first();
                            $fp = $fr
                                ? ($totalPerms > 0
                                    ? round(($fr->permissions->count() / $totalPerms) * 100)
                                    : 0)
                                : 0;
                        @endphp
                        <div style="font-size:15px; font-weight:800; margin-bottom:4px;" id="accentRoleName">
                            {{ $fr?->display_name ?? '' }}</div>
                        <div style="font-size:32px; font-weight:900; margin-bottom:2px; line-height:1;" id="accentPct">
                            {{ $fp }}%</div>
                        <div style="font-size:11px; opacity:0.8; margin-bottom:14px;" id="accentCount">
                            {{ $fr?->permissions->count() ?? 0 }} of {{ $totalPerms }} active</div>
                        <div style="height:4px; background:rgba(255,255,255,0.2); border-radius:10px;">
                            <div id="accentBar"
                                style="height:100%; width:{{ $fp }}%; background:#fff; border-radius:10px; transition:width 0.4s;">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card" style="display:flex; flex-direction:column; min-height:600px;">
                        <div class="card-header">
                            <div class="perm-search-row">
                                <div class="st-input-wrap">
                                    <i class="fa-solid fa-magnifying-glass st-input-icon"></i>
                                    <input type="text" id="permSearch" placeholder="Search permissions..." class="st-input"
                                        oninput="filterPerms(this.value)">
                                </div>
                                <button type="button" id="permSearchClearBtn" class="perm-search-clear-btn hidden" title="Clear">Clear</button>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <button type="button" class="btn-view-as" id="globalViewAsBtn" onclick="openViewAs()">
                                    <i class="fa-solid fa-eye"></i> View As
                                    <span class="va-count-badge" id="globalVaBadge">0</span>
                                </button>
                                <button type="button" class="btn-collapse" id="collapseBtn"
                                    onclick="toggleAllGroups()">Collapse All</button>
                                <button type="button" class="btn-reset" id="resetDefaultsBtn"
                                    onclick="ajaxResetDefaults()">
                                    <i class="fa-solid fa-rotate-left"></i> Reset Defaults
                                </button>
                            </div>
                        </div>

                        <div style="padding: 1.25rem 1.25rem 0; flex:1;">
                            <div class="protected-banner" id="protectedBanner" style="display:none;">
                                <i class="fa-solid fa-shield-halved" style="font-size:24px; color:#d97706;"></i>
                                <div>
                                    <div style="font-weight:800; font-size:13px; color:#92400e;">Protected Role</div>
                                    <div style="font-size:12px; color:#b45309;">Super Admin has unrestricted access and
                                        cannot be modified.</div>
                                </div>
                            </div>

                            @foreach ($roles as $ri => $role)
                                @php
                                    $isSuperRole =
                                        in_array(strtolower($role->slug), [
                                            'super_admin',
                                            'super-admin',
                                            'superadmin',
                                        ]) || str_contains(strtolower($role->name), 'super');
                                    $isActiveRole = isset($highlightRoleId)
                                        ? (int) $highlightRoleId === (int) $role->id
                                        : $ri === 0;
                                    $micons = [
                                        'Dental Records' => ['fa-notes-medical', '#8B0000'],
                                        'Patients' => ['fa-user-group', '#d97706'],
                                        'Appointments' => ['fa-calendar-days', '#059669'],
                                        'Document Requests' => ['fa-envelope-open-text', '#2563eb'],
                                        'Document Templates' => ['fa-file-lines', '#7c3aed'],
                                        'Reports' => ['fa-chart-pie', '#7c3aed'],
                                        'General Access' => ['fa-user-shield', '#059669'],
                                        'Inventory' => ['fa-boxes-stacked', '#ea580c'],
                                        'User Management' => ['fa-user-cog', '#dc2626'],
                                        'System Settings' => ['fa-screwdriver-wrench', '#4b5563'],
                                    ];
                                @endphp

                                <form id="form-role-{{ $role->id }}" class="role-form"
                                    data-role-id="{{ $role->id }}"
                                    style="display:{{ $isActiveRole ? 'block' : 'none' }}; height: 100%;">
                                    @csrf
                                    <input type="hidden" name="role_id" value="{{ $role->id }}">

                                    <div style="display:flex; flex-direction:column; gap:10px; padding-bottom: 80px;"
                                        class="groups-container">
                                        @forelse($groupedPermissions as $module => $permissions)
                                            @php
                                                [$ico, $icol] = $micons[$module] ?? ['fa-shield-halved', '#4b5563'];
                                                $mSlug = Str::slug($module);
                                                $mTotal = $permissions->count();
                                                $roleGranted = 0;
                                                foreach ($permissions as $_p) {
                                                    if ($role->permissions->contains('id', $_p->id)) {
                                                        $roleGranted++;
                                                    }
                                                }
                                                $allOn = $roleGranted === $mTotal;
                                            @endphp

                                            <div class="group-card perm-group" data-group="{{ strtolower($module) }}">
                                                <div class="perm-group-header" onclick="togglePermGroup(this)">
                                                    <div class="perm-group-icon"
                                                        style="background:{{ $icol }}15; color:{{ $icol }};">
                                                        <i class="fa-solid {{ $ico }}"></i>
                                                    </div>
                                                    <div style="flex:1;">
                                                        <div style="font-weight:800; font-size:13px; color:#1f2937;">
                                                            {{ $module }}</div>
                                                        <div style="font-size:11px; color:#6b7280; font-weight: 600;"
                                                            class="group-count">{{ $roleGranted }} of
                                                            {{ $mTotal }} enabled</div>
                                                    </div>
                                                    <div style="display:flex; align-items:center; gap:14px;">
                                                        <div class="dot-row"
                                                            id="dots-{{ $role->id }}-{{ $mSlug }}">
                                                            @for ($d = 0; $d < $mTotal; $d++)
                                                                <div class="dot"
                                                                    style="{{ $d < $roleGranted ? 'background:' . $icol . ';' : '' }}">
                                                                </div>
                                                            @endfor
                                                        </div>
                                                        <div class="all-toggle-wrap"
                                                            onclick="event.stopPropagation(); toggleGroupPerms(this,'{{ $role->id }}','{{ $mSlug }}',{{ $allOn ? 'true' : 'false' }})">
                                                            <span
                                                                style="font-size:10px; color:#4b5563; font-weight:700; text-transform:uppercase;">All</span>
                                                            <label
                                                                class="toggle-switch {{ $isSuperRole ? 'disabled' : '' }}"
                                                                onclick="event.preventDefault();">
                                                                <input type="checkbox" class="group-master"
                                                                    data-role="{{ $role->id }}"
                                                                    data-module="{{ $mSlug }}"
                                                                    {{ $allOn ? 'checked' : '' }}
                                                                    {{ $isSuperRole ? 'disabled' : '' }}>
                                                                <span class="toggle-track"></span>
                                                            </label>
                                                        </div>
                                                        <i class="fa-solid fa-chevron-up chevron"></i>
                                                    </div>
                                                </div>

                                                <div class="perm-group-body">
                                                    @foreach ($permissions as $permission)
                                                        @php $isGranted = $role->permissions->contains('id',$permission->id); @endphp
                                                        <div class="perm-row"
                                                            data-perm-search="{{ strtolower($permission->name . ' ' . $permission->slug) }}">
                                                            <div style="flex:1;">
                                                                <div
                                                                    style="display:flex; align-items:center; gap:8px; margin-bottom:2px;">
                                                                    <span
                                                                        style="font-weight:700; font-size:12px; color:{{ $isGranted ? '#1f2937' : '#6b7280' }};"
                                                                        class="perm-label">{{ $permission->name }}</span>
                                                                </div>
                                                                <div style="font-size:11px; color:#9ca3af;">
                                                                    {{ $permission->slug }}</div>
                                                            </div>
                                                            <div style="display:flex; align-items:center; gap:10px;">
                                                                <span
                                                                    class="perm-status {{ $isGranted ? 'status-granted' : 'status-denied' }}"
                                                                    style="{{ $isGranted ? 'background:' . $icol . '18; color:' . $icol . ';' : '' }}">
                                                                    {{ $isGranted ? 'Granted' : 'Denied' }}
                                                                </span>
                                                                <label
                                                                    class="toggle-switch {{ $isSuperRole ? 'disabled' : '' }}">
                                                                    <input type="checkbox"
                                                                        name="permissions[{{ $role->id }}][]"
                                                                        value="{{ $permission->id }}" class="perm-toggle"
                                                                        data-role="{{ $role->id }}"
                                                                        data-module="{{ $mSlug }}"
                                                                        data-color="{{ $icol }}"
                                                                        data-perm-name="{{ $permission->name }}"
                                                                        data-perm-slug="{{ $permission->slug }}"
                                                                        {{ $isGranted ? 'checked' : '' }}
                                                                        {{ $isSuperRole ? 'disabled' : '' }}
                                                                        onchange="onPermChange(this)">
                                                                    <span class="toggle-track"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @empty
                                            <div style="text-align:center; padding:60px 20px;">
                                                <i class="fa-solid fa-shield-halved"
                                                    style="font-size:40px; color:#e5e7eb; margin-bottom:12px;"></i>
                                                <p style="font-size:14px; font-weight:700; color:#6b7280;">No permissions
                                                    found.</p>
                                            </div>
                                        @endforelse
                                    </div>

                                    @if (!$isSuperRole)
                                        <div class="floating-save-bar" id="footer-bar-{{ $role->id }}">
                                            <div class="fsb-text">
                                                <span class="fsb-title">Unsaved changes</span>
                                                <span class="fsb-sub">0 changes</span>
                                            </div>
                                            <div class="fsb-actions">
                                                <button type="button" class="btn-view-as fsb-view-as"
                                                    onclick="openViewAs()">
                                                    <i class="fa-solid fa-eye"></i> View As
                                                    <span class="va-count-badge">0</span>
                                                </button>
                                                <button type="button" class="btn-discard"
                                                    onclick="discardChanges('{{ $role->id }}')">Discard</button>
                                                <button type="button" class="btn-save-float"
                                                    id="save-btn-{{ $role->id }}"
                                                    onclick="ajaxSaveRole('{{ $role->id }}')">
                                                    <i class="fa-solid fa-floppy-disk"></i> Save
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <dialog id="newRoleModal" class="modern-modal">
        <div class="modal-icon primary"><i class="fa-solid fa-user-shield"></i></div>
        <h2 class="modal-title">Create New Role</h2>
        <div class="modal-body">Define a new role and assign permissions to it right away.</div>

        <form id="createRoleForm" action="{{ route('admin.role_permissions.store_role') }}" method="POST">
            @csrf
            <div class="modal-form-group">
                <label class="modal-label">Role Name</label>
                <div class="st-input-wrap">
                    <i class="fa-solid fa-tag st-input-icon"></i>
                    <input type="text" id="newRoleName" name="name" class="st-input"
                        placeholder="e.g. Dental Intern" autocomplete="off">
                </div>
            </div>
            <div class="modal-form-group">
                <label class="modal-label">Role Slug</label>
                <div class="st-input-wrap">
                    <i class="fa-solid fa-link st-input-icon"></i>
                    <input type="text" id="newRoleSlug" name="slug" class="st-input"
                        placeholder="e.g. dental-intern" autocomplete="off">
                </div>
            </div>

            <div id="newRoleError"
                style="display:none; color:#dc2626; font-size:12px; font-weight:600; margin-bottom:16px; background:#fef2f2; padding:8px; border-radius:8px;">
            </div>

            <div class="modal-actions">
                <button type="button" class="modal-btn-cancel" onclick="closeNewRoleModal()">Cancel</button>
                <button type="submit" class="modal-btn-confirm primary" id="btnSubmitNewRole">Create Role</button>
            </div>
        </form>
    </dialog>

    <dialog id="deleteRoleModal" class="modern-modal">
        <div class="modal-icon danger"><i class="fa-solid fa-trash-can"></i></div>
        <h2 class="modal-title">Delete Role</h2>
        <div class="modal-body">
            Are you sure you want to permanently delete <span class="modal-highlight" id="deleteRoleName"></span>?
            <span style="display:block; color:#ef4444; font-weight:600; margin-top:8px;">This action cannot be
                undone.</span>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteRoleForm" method="POST" style="margin:0;">
                @csrf @method('DELETE')
                <button type="submit" class="modal-btn-confirm danger">Yes, delete it</button>
            </form>
        </div>
    </dialog>

    <dialog id="resetConfirmModal" class="modern-modal">
        <div class="modal-icon warning"><i class="fa-solid fa-rotate-left"></i></div>
        <h2 class="modal-title">Reset to Defaults?</h2>
        <div class="modal-body">
            This restores original permissions for <strong style="color:#111827;">Super Admin</strong>, <strong
                style="color:#111827;">Dentist</strong>, and <strong style="color:#111827;">Patient</strong>.
            <div
                style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:10px; margin-top:12px; color:#b45309; font-size:.8rem; font-weight:600; text-align:left;">
                <i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i> Custom changes will be lost.
                This cannot be undone.
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn-cancel" onclick="closeResetConfirm()">Cancel</button>
            <button type="button" id="resetConfirmBtn" class="modal-btn-confirm warning"
                onclick="confirmResetDefaults()">Yes, Reset</button>
        </div>
    </dialog>

    <div id="vaOverlay">
        <div class="va-panel">
            <div class="va-head">
                <div
                    style="width:40px;height:40px;border-radius:10px;background:#eff6ff;color:#3b82f6;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:1.1rem;font-weight:800;color:#111827;margin-bottom:2px;">View As Role</div>
                    <div style="font-size:.8rem;color:#6b7280;" id="vaSubtitle">Select a role to preview dashboard access
                    </div>
                </div>
                <button onclick="closeViewAs()"
                    style="width:32px;height:32px;border-radius:8px;background:#f3f4f6;border:none;cursor:pointer;color:#6b7280;"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="va-body">
                <div
                    style="background: linear-gradient(135deg, var(--crimson), var(--crimson-dark)); border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 14px; margin-bottom: 16px; color: #fff;">
                    <i class="fa-solid fa-shield-halved" style="font-size: 20px; opacity: .8;"></i>
                    <div>
                        <div
                            style="font-size:11px; opacity:.8; margin-bottom:2px; text-transform:uppercase; font-weight:700;">
                            Newly granted & saved</div>
                        <div><strong style="font-size:20px;" id="vaTotalPerms">0</strong> permissions across <strong
                                style="font-size:20px;" id="vaTotalRoles">0</strong> roles</div>
                    </div>
                </div>
                <div id="vaRoleList"></div>
            </div>
        </div>
    </div>

    <div id="patientPickerOverlay">
        <div class="va-panel">
            <div class="va-head">
                <div
                    style="width:40px;height:40px;border-radius:10px;background:#f0fdf4;color:#22c55e;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                    <i class="fa-solid fa-user-injured"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:1.1rem;font-weight:800;color:#111827;margin-bottom:2px;">Select Patient Account
                    </div>
                    <div style="font-size:.8rem;color:#6b7280;">Choose which patient to impersonate</div>
                </div>
                <button onclick="closePatientPicker()"
                    style="width:32px;height:32px;border-radius:8px;background:#f3f4f6;border:none;cursor:pointer;color:#6b7280;"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="va-body">
                <div class="st-input-wrap" style="margin-bottom: 16px;">
                    <i class="fa-solid fa-magnifying-glass st-input-icon"></i>
                    <input type="text" id="patientPickerSearch" placeholder="Search patient name or email..."
                        class="st-input" oninput="filterPatientPicker(this.value)">
                </div>
                <div id="patientPickerList"></div>
            </div>
        </div>
    </div>

    <div class="redirect-overlay" id="redirectOverlay">
        <div class="redirect-spinner"></div>
        <div id="redirectText" style="font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:6px;"></div>
        <div id="redirectSub" style="font-size:.85rem;color:rgba(255,255,255,.7);"></div>
    </div>

@endsection

@section('scripts')
    <script>
        // PERM MODULE META
        const PERM_MODULES = [{
                module: 'Dashboard',
                color: '#8B0000'
            },
            {
                module: 'Patients',
                color: '#d97706'
            },
            {
                module: 'Appointments',
                color: '#059669'
            },
            {
                module: 'Document Requests',
                color: '#2563eb'
            },
            {
                module: 'Document Template',
                color: '#7c3aed'
            },
            {
                module: 'Reports',
                color: '#7c3aed'
            },
            {
                module: 'Academic Periods',
                color: '#059669'
            },
            {
                module: 'Data Backup',
                color: '#ea580c'
            },
            {
                module: 'System Logs',
                color: '#dc2626'
            },
            {
                module: 'System Settings',
                color: '#4b5563'
            },
        ];

        function getModuleColor(module) {
            const found = PERM_MODULES.find(m => m.module === module);
            return found ? found.color : '#4b5563';
        }

        // GLOBAL STATE
        let initialStates = {};
        let savedGrants = {};
        let activeRoleId = null;
        let isScrolledDown = window.scrollY > 40;
        let isModalActive = false;

        const flashedViewAs = @json(session('saved_view_as') ?? null);
        if (flashedViewAs && flashedViewAs.role_id) {
            savedGrants[String(flashedViewAs.role_id)] = (flashedViewAs.permissions || []).map(p => ({
                name: p.name,
                slug: p.slug,
                color: getModuleColor(p.module)
            }));
        }

        // Scroll Listener
        document.addEventListener('scroll', () => {
            isScrolledDown = window.scrollY > 40;
            updateFABVisibility();
        });

        function initRoleForms() {
            initialStates = {};
            savedGrants = {};

            const firstActiveCard = document.querySelector('.role-card.active');
            if (firstActiveCard) activeRoleId = firstActiveCard.dataset.roleId;

            document.querySelectorAll('.role-form').forEach(form => {
                const roleId = form.dataset.roleId;
                if (!roleId) return;

                initialStates[roleId] = {};
                savedGrants[roleId] = [];

                form.querySelectorAll('.perm-toggle').forEach(input => {
                    initialStates[roleId][input.value] = input.checked;
                    if (input.checked) {
                        savedGrants[roleId].push({
                            name: input.dataset.permName || '',
                            slug: input.dataset.permSlug || '',
                            color: input.dataset.color || '#4b5563'
                        });
                    }
                });

                // Ensure floating bar is hidden initially
                const bar = document.getElementById('footer-bar-' + roleId);
                if (bar) bar.classList.remove('show');

                const modules = [...new Set(Array.from(form.querySelectorAll('.perm-toggle')).map(t => t.dataset
                    .module).filter(Boolean))];
                modules.forEach(module => {
                    const sample = form.querySelector(`.perm-toggle[data-module="${module}"]`);
                    if (!sample) return;
                    syncGroupMaster(roleId, module);
                    updateGroupCount(roleId, module);
                    updateDots(roleId, module, sample.dataset.color || '#4b5563');
                });
            });

            updateViewAsBtn();
        }

        function getPreferredRolePermissionView() {
            if (window.innerWidth <= 767) return 'grid';
            return localStorage.getItem('rolePermissionView') || 'list';
        }

        function applyRolePermissionView(view, save = true) {
            const container = document.getElementById('roleListContainer');
            const listBtn = document.getElementById('roleListViewBtn');
            const gridBtn = document.getElementById('roleGridViewBtn');

            if (!container) return;

            const finalView = window.innerWidth <= 767 ? 'grid' : view;

            container.classList.remove('role-list-view', 'role-grid-view');
            container.classList.add(finalView === 'grid' ? 'role-grid-view' : 'role-list-view');

            if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
            if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

            if (save && window.innerWidth > 767) {
                localStorage.setItem('rolePermissionView', finalView);
            }
        }

        function initRolePermissionViewToggle() {
            const listBtn = document.getElementById('roleListViewBtn');
            const gridBtn = document.getElementById('roleGridViewBtn');

            applyRolePermissionView(getPreferredRolePermissionView(), false);

            if (listBtn && !listBtn.dataset.bound) {
                listBtn.dataset.bound = '1';
                listBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    applyRolePermissionView('list', true);
                });
            }

            if (gridBtn && !gridBtn.dataset.bound) {
                gridBtn.dataset.bound = '1';
                gridBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    applyRolePermissionView('grid', true);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const firstCard = document.querySelector('.role-card');
            const protectedBanner = document.getElementById('protectedBanner');
            const permSearch = document.getElementById('permSearch');
            const permSearchClearBtn = document.getElementById('permSearchClearBtn');

            function syncPermSearchClear() {
                if (!permSearch || !permSearchClearBtn) return;
                permSearchClearBtn.classList.toggle('hidden', (permSearch.value || '').trim().length === 0);
            }

            if (permSearchClearBtn && !permSearchClearBtn.dataset.bound) {
                permSearchClearBtn.dataset.bound = '1';
                permSearchClearBtn.addEventListener('click', () => {
                    if (!permSearch) return;
                    permSearch.value = '';
                    filterPerms('');
                    syncPermSearchClear();

                    const status = permSearch.closest('.st-input-wrap')?.querySelector('[data-voice-status]');
                    if (status) status.classList.add('hidden');

                    permSearch.focus();
                });
            }

            if (permSearch && !permSearch.dataset.clearSyncBound) {
                permSearch.dataset.clearSyncBound = '1';
                permSearch.addEventListener('input', syncPermSearchClear);
            }

            syncPermSearchClear();
            
            // Inject compact external mic button and voice controller for permissions search
            (function initPermSearchVoice() {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition) return;

                const searchRow = document.querySelector('.perm-search-row');
                const inputWrap = searchRow?.querySelector('.st-input-wrap');
                const permSearchInput = document.getElementById('permSearch');
                if (!searchRow || !inputWrap || !permSearchInput) return;

                // remove any legacy injected mic or data-voice trigger inside the input wrap
                inputWrap.querySelectorAll('.voice-mic-btn, [data-voice-trigger]').forEach(el => el.remove());

                // create a patient-style wrapper (button + status) and append it to the row
                let wrapper = searchRow.querySelector('.patient-voice-toggle');
                if (!wrapper) {
                    wrapper = document.createElement('div');
                    wrapper.className = 'patient-voice-toggle';
                    wrapper.style.position = 'relative';
                    wrapper.style.display = 'inline-flex';
                    wrapper.style.alignItems = 'center';
                    wrapper.style.flexShrink = '0';
                    searchRow.appendChild(wrapper);
                }

                // create external mic button inside wrapper if missing
                let micBtn = wrapper.querySelector('#permMicToggleBtn');
                if (!micBtn) {
                    micBtn = document.createElement('button');
                    micBtn.type = 'button';
                    micBtn.id = 'permMicToggleBtn';
                    micBtn.className = 'voice-search-mic external';
                    micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
                    micBtn.title = 'Toggle voice input';
                    wrapper.appendChild(micBtn);
                }

                // create status span inside wrapper if missing (use patient-voice-status for consistent styling)
                let status = wrapper.querySelector('.patient-voice-status');
                if (!status) {
                    status = document.createElement('span');
                    status.className = 'patient-voice-status hidden';
                    status.setAttribute('aria-hidden', 'true');
                    status.setAttribute('id', 'permPatientVoiceStatus');
                    status.setAttribute('aria-live', 'polite');
                    wrapper.appendChild(status);
                }

                // ensure input has padding for mic (keeps space for external button)
                permSearchInput.classList.add('has-voice-padding');

                let recognition = null;
                let listening = false;
                let manualStop = false;

                const setStatus = (text, state) => {
                    status.textContent = text || '';
                    status.className = state ? `patient-voice-status is-${state}` : 'patient-voice-status';
                    if (!text) status.classList.add('hidden'); else status.classList.remove('hidden');
                };

                const setMicState = (active) => {
                    micBtn.classList.toggle('mic-active', !!active);
                    micBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
                    micBtn.innerHTML = active ? '<i class="fa-solid fa-stop"></i>' : '<i class="fa-solid fa-microphone"></i>';
                };

                const stopNow = () => {
                    manualStop = true;
                    listening = false;
                    setMicState(false);
                    setStatus('Voice captured.', 'success');
                    setTimeout(() => setStatus('', null), 1200);
                    if (recognition) {
                        try { recognition.abort(); } catch (e) { try { recognition.stop(); } catch (e) {} }
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

                    const clearTimeout_ = () => { if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; } };

                    r.onstart = () => {
                        timeoutId = setTimeout(() => { if (listening && !sawSpeech) { try { r.stop(); } catch (e){} } }, LISTEN_TIMEOUT);
                    };

                    r.onspeechend = () => { clearTimeout_(); try { r.stop(); } catch (e) {} };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const result = event.results[i];
                            const chunk = (result && result[0] && result[0].transcript ? result[0].transcript : '').trim();
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (result.isFinal) {
                                transcript = (transcript + ' ' + chunk).trim();
                            } else if (!transcript) {
                                transcript = chunk;
                            }
                        }
                        transcript = transcript.trim();
                        if (transcript) {
                            clearTimeout_();
                            permSearchInput.value = transcript;
                            permSearchInput.dispatchEvent(new Event('input', { bubbles: true }));
                            permSearchInput.dispatchEvent(new Event('change', { bubbles: true }));
                            setStatus('Listening...', 'listening');
                        }
                    };

                    r.onerror = () => {
                        clearTimeout_();
                        listening = false;
                        if (manualStop) { manualStop = false; return; }
                        setMicState(false);
                        setStatus("Didn't catch that. Try again.", 'error');
                        setTimeout(() => setStatus('', null), 2500);
                    };

                    r.onend = () => {
                        clearTimeout_();
                        if (manualStop) { manualStop = false; listening = false; setMicState(false); return; }
                        const hadSpeech = sawSpeech || !!permSearchInput.value.trim();
                        listening = false;
                        setMicState(false);
                        if (hadSpeech) {
                            setStatus('Voice captured.', 'success');
                            setTimeout(() => setStatus('', null), 2200);
                        } else {
                            setStatus("Didn't catch that. Try again.", 'error');
                            setTimeout(() => setStatus('', null), 2500);
                        }
                    };

                    return r;
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) { stopNow(); return; }
                    recognition = createRecognition();
                    try { recognition.start(); } catch (e) { setStatus('Unable to start voice input.', 'error'); setTimeout(() => setStatus('', null), 2500); setMicState(false); listening = false; return; }
                    listening = true;
                    setMicState(true);
                    setStatus('Listening...', 'listening');
                });

                // support pointerdown immediate stop (some browsers)
                micBtn.addEventListener('pointerdown', (ev) => {
                    if (listening && recognition) { ev.preventDefault(); ev.stopPropagation(); manualStop = true; try { recognition.stop(); } catch (e) {} }
                }, { passive: false });

            })();
            if (firstCard && protectedBanner && firstCard.dataset.isSuper === '1') {
                protectedBanner.style.display = 'flex';
            }

            initRoleForms();
            initRolePermissionViewToggle();

            window.addEventListener('resize', () => {
                applyRolePermissionView(getPreferredRolePermissionView(), false);
            });

            @if (session('success'))
                if (typeof showToast === 'function') {
                    showToast('Success', '{!! addslashes(session('success')) !!}', 'success');
                }
            @endif

            @if (session('error'))
                if (typeof showToast === 'function') {
                    showToast('Error', '{!! addslashes(session('error')) !!}', 'error');
                }
            @endif

            // Slug auto-fill for new role
            document.getElementById('newRoleName')?.addEventListener('input', function() {
                document.getElementById('newRoleSlug').value = this.value.toLowerCase().trim()
                    .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
            });
        });

        // ROLE CARD SELECT
        function selectRole(card) {
            document.querySelectorAll('.role-card').forEach(c => {
                c.classList.remove('active');
            });

            card.classList.add('active');

            const roleId = card.dataset.roleId;
            const roleName = card.dataset.roleName || '';
            const granted = parseInt(card.dataset.granted || '0', 10);
            const total = parseInt(card.dataset.total || '0', 10);
            const pct = parseInt(card.dataset.pct || '0', 10);

            document.getElementById('accentRoleName').textContent = roleName;
            document.getElementById('accentPct').textContent = pct + '%';
            document.getElementById('accentCount').textContent = granted + ' of ' + total + ' active';
            document.getElementById('accentBar').style.width = pct + '%';

            const slug = (card.dataset.slug || '').toLowerCase();
            const isSuper = ['super_admin', 'super-admin', 'superadmin'].includes(slug) || roleName.toLowerCase().includes(
                'super');
            const banner = document.getElementById('protectedBanner');
            if (banner) banner.style.display = isSuper ? 'flex' : 'none';

            document.querySelectorAll('.role-form').forEach(f => f.style.display = 'none');
            const form = document.getElementById('form-role-' + roleId);
            if (form) form.style.display = 'block';

            const permSearch = document.getElementById('permSearch');
            if (permSearch) permSearch.value = '';
            const permSearchClearBtn = document.getElementById('permSearchClearBtn');
            if (permSearchClearBtn) permSearchClearBtn.classList.add('hidden');
            filterPerms('');

            activeRoleId = roleId;
            updateFABVisibility();
        }

        // UPDATE VIEW AS BUTTONS (TOP TOOLBAR)
        function updateViewAsBtn() {
            let totalSavedRoles = 0;
            Object.values(savedGrants).forEach(grants => {
                if (grants.length > 0) totalSavedRoles++;
            });

            document.querySelectorAll('.btn-view-as:not(.fsb-view-as)').forEach(btn => {
                if (totalSavedRoles > 0) {
                    btn.classList.add('show');
                } else {
                    btn.classList.remove('show');
                }
                const badge = btn.querySelector('.va-count-badge');
                if (badge) badge.textContent = totalSavedRoles;
            });

            updateFABVisibility();
        }

        // ══════════════════════════════════════════
        // FLOATING ACTION BAR (FAB) VISIBILITY LOGIC
        // ══════════════════════════════════════════
        function updateFABVisibility() {
            // Hide all first
            document.querySelectorAll('.floating-save-bar').forEach(b => b.classList.remove('show'));

            if (!activeRoleId) return;
            const bar = document.getElementById('footer-bar-' + activeRoleId);
            if (!bar) return;

            // Ensure it is hidden if a modal is active or user is at the top of the page
            if (isModalActive || !isScrolledDown) {
                return;
            }

            const form = document.getElementById('form-role-' + activeRoleId);
            if (!form) return;

            let isDirty = false;
            let changesCount = 0;

            form.querySelectorAll('.perm-toggle').forEach(t => {
                const isInitiallyChecked = initialStates[activeRoleId][t.value];
                if (t.checked !== isInitiallyChecked) {
                    isDirty = true;
                    changesCount++;
                }
            });

            let totalSavedRoles = 0;
            Object.values(savedGrants).forEach(grants => {
                if (grants.length > 0) totalSavedRoles++;
            });

            const title = bar.querySelector('.fsb-title');
            const sub = bar.querySelector('.fsb-sub');
            const btnDiscard = bar.querySelector('.btn-discard');
            const btnSave = bar.querySelector('.btn-save-float');
            const btnViewAs = bar.querySelector('.btn-view-as.fsb-view-as');

            if (isDirty) {
                bar.classList.add('show');
                title.textContent = 'Unsaved changes';
                sub.textContent = changesCount + ' unsaved change' + (changesCount > 1 ? 's' : '');
                sub.style.display = 'block';
                btnDiscard.style.display = 'inline-block';
                btnSave.style.display = 'inline-flex';
                if (btnViewAs) {
                    btnViewAs.style.display = 'inline-flex';
                    const badge = btnViewAs.querySelector('.va-count-badge');
                    if (badge) badge.textContent = totalSavedRoles;
                }
            } else if (totalSavedRoles > 0) {
                bar.classList.add('show');
                title.textContent = 'Ready to preview';
                sub.style.display = 'none';
                btnDiscard.style.display = 'none';
                btnSave.style.display = 'none';
                if (btnViewAs) {
                    btnViewAs.style.display = 'inline-flex';
                    const badge = btnViewAs.querySelector('.va-count-badge');
                    if (badge) badge.textContent = totalSavedRoles;
                }
            }
        }

        function discardChanges(roleId) {
            const form = document.getElementById('form-role-' + roleId);
            if (!form) return;

            form.querySelectorAll('.perm-toggle').forEach(t => {
                const initVal = initialStates[roleId][t.value];
                if (t.checked !== initVal) {
                    t.checked = initVal;
                    updatePermVisuals(t);
                }
            });

            // Update group masters, dots, accent card
            const modules = [...new Set(Array.from(form.querySelectorAll('.perm-toggle')).map(t => t.dataset.module).filter(
                Boolean))];
            modules.forEach(module => {
                const sample = form.querySelector(`.perm-toggle[data-module="${module}"]`);
                syncGroupMaster(roleId, module);
                updateGroupCount(roleId, module);
                updateDots(roleId, module, sample.dataset.color || '#4b5563');
            });

            updateAccentCard(roleId);
            updateFABVisibility();
        }

        function updatePermVisuals(input) {
            const row = input.closest('.perm-row');
            const badge = row.querySelector('.perm-status');
            const label = row.querySelector('.perm-label');
            const color = input.dataset.color;

            if (input.checked) {
                badge.textContent = 'Granted';
                badge.className = 'perm-status status-granted';
                badge.style.background = color + '18';
                badge.style.color = color;
                label.style.color = '#1f2937';
            } else {
                badge.textContent = 'Denied';
                badge.className = 'perm-status status-denied';
                badge.style.background = '';
                badge.style.color = '';
                label.style.color = '#6b7280';
            }
        }

        // PERMISSION GROUP COLLAPSE
        let allExpanded = true;

        function togglePermGroup(header) {
            const body = header.nextElementSibling;
            const chev = header.querySelector('.chevron');
            const isCollapsed = body.classList.contains('collapsed');
            body.classList.toggle('collapsed');
            chev.classList.toggle('collapsed', !isCollapsed);
        }

        function toggleAllGroups() {
            const btn = document.getElementById('collapseBtn');
            const form = [...document.querySelectorAll('.role-form')].find(f => f.style.display === 'block');
            if (!form) return;
            allExpanded = !allExpanded;
            form.querySelectorAll('.perm-group-body').forEach(b => b.classList.toggle('collapsed', !allExpanded));
            form.querySelectorAll('.chevron').forEach(c => c.classList.toggle('collapsed', !allExpanded));
            btn.textContent = allExpanded ? 'Collapse All' : 'Expand All';
        }

        // PER-PERMISSION TOGGLE
        function onPermChange(input) {
            updatePermVisuals(input);
            const roleId = input.dataset.role;
            const mSlug = input.dataset.module;

            updateDots(roleId, mSlug, input.dataset.color);
            updateGroupCount(roleId, mSlug);
            syncGroupMaster(roleId, mSlug);
            updateAccentCard(roleId);
            updateFABVisibility();
        }

        // ALL TOGGLE
        function toggleGroupPerms(wrapper, roleId, mSlug, currentlyAllOn) {
            const newState = !currentlyAllOn;
            wrapper.setAttribute('onclick',
                `event.stopPropagation(); toggleGroupPerms(this,'${roleId}','${mSlug}',${newState})`);
            const form = document.getElementById('form-role-' + roleId);
            if (!form) return;
            form.querySelectorAll(`.perm-toggle[data-module="${mSlug}"]`).forEach(t => {
                if (t.disabled) return;
                t.checked = newState;
                updatePermVisuals(t);
            });
            const master = form.querySelector(`.group-master[data-module="${mSlug}"]`);
            if (master) master.checked = newState;

            updateDots(roleId, mSlug, form.querySelector(`.perm-toggle[data-module="${mSlug}"]`)?.dataset.color);
            updateGroupCount(roleId, mSlug);
            updateAccentCard(roleId);
            updateFABVisibility();
        }

        function syncGroupMaster(roleId, mSlug) {
            const form = document.getElementById('form-role-' + roleId);
            if (!form) return;
            const all = [...form.querySelectorAll(`.perm-toggle[data-module="${mSlug}"]`)];
            const checked = all.filter(t => t.checked).length;
            const master = form.querySelector(`.group-master[data-module="${mSlug}"]`);
            if (!master) return;
            master.checked = checked === all.length;
            master.indeterminate = checked > 0 && checked < all.length;
        }

        function updateDots(roleId, mSlug, color) {
            const cont = document.getElementById(`dots-${roleId}-${mSlug}`);
            if (!cont) return;
            const form = document.getElementById('form-role-' + roleId);
            if (!form) return;
            const toggles = [...form.querySelectorAll(`.perm-toggle[data-module="${mSlug}"]`)];
            const dots = cont.querySelectorAll('.dot');
            toggles.forEach((t, i) => {
                if (dots[i]) dots[i].style.background = t.checked ? color : '#e5e7eb';
            });
        }

        function updateGroupCount(roleId, mSlug) {
            const form = document.getElementById('form-role-' + roleId);
            if (!form) return;
            const dotsEl = form.querySelector(`[id="dots-${roleId}-${mSlug}"]`);
            if (!dotsEl) return;
            const gc = dotsEl.closest('.group-card');
            if (!gc) return;
            const all = [...gc.querySelectorAll('.perm-toggle')];
            const countEl = gc.querySelector('.group-count');
            if (countEl) countEl.textContent = `${all.filter(t => t.checked).length} of ${all.length} enabled`;
        }

        function updateAccentCard(roleId) {
            const form = document.getElementById('form-role-' + roleId);
            if (!form) return;
            const all = [...form.querySelectorAll('.perm-toggle')];
            const total = all.length;
            const checked = all.filter(t => t.checked).length;
            const pct = total > 0 ? Math.round(checked / total * 100) : 0;

            document.getElementById('accentPct').textContent = pct + '%';
            document.getElementById('accentCount').textContent = `${checked} of ${total} active`;
            document.getElementById('accentBar').style.width = pct + '%';

            const card = document.querySelector(`.role-card[data-role-id="${roleId}"]`);
            if (card) {
                card.querySelector('.pct-label').textContent = pct + '%';
                card.querySelector('.count-label').textContent = `${checked} / ${total} permissions`;
                card.querySelector('.progress-fill').style.width = pct + '%';
                card.dataset.granted = checked;
                card.dataset.pct = pct;
            }
        }

        // AJAX SAVE
        function ajaxSaveRole(roleId) {
            const form = document.getElementById('form-role-' + roleId);
            const btn = document.getElementById('save-btn-' + roleId);
            if (!form || !btn) return;

            const checkedIds = [...form.querySelectorAll('.perm-toggle:checked')].map(t => t.value);
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

            fetch('{{ route('admin.role_permissions.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        role_id: roleId,
                        permissions: checkedIds
                    })
                })
                .then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message || 'Server error ' + res.status);
                    return data;
                })
                .then(data => {
                    // Update initial states so it's no longer dirty
                    form.querySelectorAll('.perm-toggle').forEach(input => {
                        initialStates[roleId][input.value] = input.checked;
                    });

                    // Update savedGrants for View As
                    savedGrants[roleId] = [];
                    form.querySelectorAll('.perm-toggle:checked').forEach(input => {
                        savedGrants[roleId].push({
                            name: input.dataset.permName || '',
                            slug: input.dataset.permSlug || '',
                            color: input.dataset.color || '#4b5563'
                        });
                    });

                    updateViewAsBtn();

                    if (typeof showToast === 'function') {
                        showToast('Success', `Permissions updated successfully.`, 'success');
                    }
                })
                .catch(err => {
                    if (typeof showToast === 'function') {
                        showToast('Error', err.message || 'Could not save permissions.', 'error');
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save';
                });
        }

        function filterPerms(q) {
            q = q.toLowerCase().trim();
            const form = [...document.querySelectorAll('.role-form')].find(f => f.style.display === 'block');
            if (!form) return;

            const permSearchClearBtn = document.getElementById('permSearchClearBtn');
            if (permSearchClearBtn) permSearchClearBtn.classList.toggle('hidden', q.length === 0);

            form.querySelectorAll('.perm-row').forEach(row => {
                row.style.display = (!q || (row.dataset.permSearch || '').includes(q)) ? '' : 'none';
            });
            form.querySelectorAll('.perm-group').forEach(group => {
                const visible = [...group.querySelectorAll('.perm-row')].some(r => r.style.display !== 'none');
                group.style.display = visible ? '' : 'none';
                if (q && visible) {
                    const b = group.querySelector('.perm-group-body');
                    if (b) b.classList.remove('collapsed');
                }
            });
        }

        // MODALS: New Role (Seamless AJAX Add)
        function openNewRoleModal() {
            isModalActive = true;
            updateFABVisibility();

            document.getElementById('newRoleName').value = '';
            document.getElementById('newRoleSlug').value = '';
            document.getElementById('newRoleError').style.display = 'none';

            const modal = document.getElementById('newRoleModal');
            modal.showModal();
            modal.style.top = '50%';
            modal.style.left = '50%';
            modal.style.transform = 'translate(-50%, -50%)';
        }

        function closeNewRoleModal() {
            isModalActive = false;
            updateFABVisibility();
            document.getElementById('newRoleModal').close();
        }

        document.getElementById('createRoleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('newRoleName').value.trim();
            const slug = document.getElementById('newRoleSlug').value.trim();
            const errEl = document.getElementById('newRoleError');

            if (!name || !slug) {
                errEl.textContent = 'Please fill out all fields.';
                errEl.style.display = 'block';
                return;
            }
            if (document.querySelector(`.role-card[data-slug="${slug}"]`)) {
                errEl.textContent = 'A role with this slug already exists.';
                errEl.style.display = 'block';
                return;
            }

            const form = this;
            const btn = document.getElementById('btnSubmitNewRole');

            btn.disabled = true;
            btn.innerHTML = 'Creating...';

            fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async res => {
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        let errorMsg = 'Could not create role.';
                        if (data.errors) errorMsg = Object.values(data.errors).flat().join(' ');
                        else if (data.message) errorMsg = data.message;
                        throw new Error(errorMsg);
                    }
                    return res;
                })
                .then(() => {
                    closeNewRoleModal();
                    if (typeof showToast === 'function') {
                        showToast('Success', 'Role created successfully.', 'success');
                    }

                    // Seamless UI fetch replacement
                    fetch(window.location.href)
                        .then(r => r.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newGrid = doc.querySelector('.main-grid');

                            if (newGrid) {
                                document.querySelector('.main-grid').innerHTML = newGrid.innerHTML;
                                initRoleForms();
                                initRolePermissionViewToggle();
                                applyRolePermissionView(getPreferredRolePermissionView(), false);

                                const newRoleCard = document.querySelector(
                                    `.role-card[data-slug="${slug}"]`) || document.querySelector(
                                    '.role-card');
                                if (newRoleCard) selectRole(newRoleCard);
                            }
                            btn.disabled = false;
                            btn.innerHTML = 'Create Role';
                        });
                })
                .catch(err => {
                    errEl.textContent = err.message;
                    errEl.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = 'Create Role';
                });
        });

        // MODALS: Delete Role (Seamless AJAX Delete)
        const PROTECTED_ROLE_SLUGS = ['admin', 'patient', 'dentist', 'super_admin', 'super-admin', 'superadmin'];

        function openDeleteModal(roleId, roleName) {
            const card = document.querySelector(`.role-card[data-role-id="${roleId}"]`);
            const slug = (card?.dataset.slug || '').toLowerCase().trim();
            if (PROTECTED_ROLE_SLUGS.includes(slug)) {
                if (typeof showToast === 'function') {
                    showToast('Protected Role', `Cannot delete built-in role.`, 'error');
                }
                return;
            }

            isModalActive = true;
            updateFABVisibility();

            document.getElementById('deleteRoleName').textContent = roleName;
            document.getElementById('deleteRoleForm').action = `/admin/role-permissions/${roleId}/destroy`;
            const deleteModal = document.getElementById('deleteRoleModal');
            deleteModal.showModal();
            deleteModal.style.top = '50%';
            deleteModal.style.left = '50%';
            deleteModal.style.transform = 'translate(-50%, -50%)';
        }

        function closeDeleteModal() {
            isModalActive = false;
            updateFABVisibility();
            document.getElementById('deleteRoleModal').close();
        }

        document.getElementById('deleteRoleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn = form.querySelector('.modal-btn-confirm');
            btn.disabled = true;
            btn.innerHTML = 'Deleting...';

            fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async res => {
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        throw new Error(data.message || 'Could not delete role.');
                    }
                    return res;
                })
                .then(() => {
                    closeDeleteModal();
                    if (typeof showToast === 'function') {
                        showToast('Success', 'Role deleted successfully.', 'success');
                    }

                    // Seamless UI fetch replacement
                    fetch(window.location.href)
                        .then(r => r.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newGrid = doc.querySelector('.main-grid');

                            if (newGrid) {
                                document.querySelector('.main-grid').innerHTML = newGrid.innerHTML;
                                initRoleForms();
                                initRolePermissionViewToggle();
                                applyRolePermissionView(getPreferredRolePermissionView(), false);
                                const firstRole = document.querySelector('.role-card');
                                if (firstRole) selectRole(firstRole);
                            }
                            btn.disabled = false;
                            btn.innerHTML = 'Yes, delete it';
                        });
                })
                .catch(err => {
                    closeDeleteModal();
                    if (typeof showToast === 'function') {
                        showToast('Error', err.message, 'error');
                    }
                    btn.disabled = false;
                    btn.innerHTML = 'Yes, delete it';
                });
        });

        // MODALS: Reset (Seamless AJAX Reset)
        function ajaxResetDefaults() {
            isModalActive = true;
            updateFABVisibility();
            const resetModal = document.getElementById('resetConfirmModal');
            resetModal.showModal();
            resetModal.style.top = '50%';
            resetModal.style.left = '50%';
            resetModal.style.transform = 'translate(-50%, -50%)';
        }

        function closeResetConfirm() {
            isModalActive = false;
            updateFABVisibility();
            document.getElementById('resetConfirmModal').close();
        }

        function confirmResetDefaults() {
            const confirmBtn = document.getElementById('resetConfirmBtn');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = 'Resetting…';

            fetch('/admin/role-permissions/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message || 'Server error ' + res.status);
                    return data;
                })
                .then(() => {
                    closeResetConfirm();
                    if (typeof showToast === 'function') {
                        showToast('Success', 'Permissions reset to defaults.', 'success');
                    }

                    // Seamless UI fetch replacement
                    fetch(window.location.href)
                        .then(res => res.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newGrid = doc.querySelector('.main-grid');
                            const currentGrid = document.querySelector('.main-grid');

                            if (newGrid && currentGrid) {
                                currentGrid.innerHTML = newGrid.innerHTML;
                                const firstRole = document.querySelector('.role-card');
                                if (firstRole) selectRole(firstRole);

                                initRoleForms();
                                initRolePermissionViewToggle();
                                applyRolePermissionView(getPreferredRolePermissionView(), false);
                            }
                            confirmBtn.disabled = false;
                            confirmBtn.innerHTML = 'Yes, Reset';
                        });
                })
                .catch(err => {
                    closeResetConfirm();
                    if (typeof showToast === 'function') {
                        showToast('Error', err.message || 'Could not reset.', 'error');
                    }
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Yes, Reset';
                });
        }

        // MODALS: View As
        function openViewAs() {
            isModalActive = true;
            updateFABVisibility();

            const overlay = document.getElementById('vaOverlay');
            const list = document.getElementById('vaRoleList');
            if (!overlay || !list) return;
            list.innerHTML = '';
            let totalPerms = 0,
                totalRoles = 0;

            document.querySelectorAll('.role-card').forEach(card => {
                const roleId = card.dataset.roleId;
                const roleName = card.dataset.roleName || 'Role';
                const roleSlug = (card.dataset.slug || '').toLowerCase();
                const granted = parseInt(card.dataset.granted || '0', 10);
                if (granted <= 0) return;

                const form = document.getElementById(`form-role-${roleId}`);
                if (!form) return;

                const checkedPerms = [...form.querySelectorAll('.perm-toggle:checked')].map(input => ({
                    name: input.dataset.permName || 'Permission',
                    color: input.dataset.color || '#4b5563'
                }));
                if (!checkedPerms.length) return;

                totalRoles++;
                totalPerms += checkedPerms.length;
                const initials = roleName.split(' ').slice(0, 2).map(w => w[0].toUpperCase()).join('');
                const color = checkedPerms[0]?.color || '#4b5563';
                const isSuperAdmin = ['super_admin', 'super-admin', 'superadmin'].includes(roleSlug) || roleName
                    .toLowerCase().includes('super');

                const tags = checkedPerms.map(p =>
                    `<span style="font-size:10px; font-weight:700; color:${p.color}; background:${p.color}15; padding:2px 8px; border-radius:12px;">${p.name}</span>`
                ).join('');
                const goBtn = !isSuperAdmin ?
                    `<button class="va-go-btn va-redirect-btn" data-role-id="${roleId}" data-role-name="${roleName}" data-role-slug="${roleSlug}" data-color="${color}">Go to Dashboard <i class="fa-solid fa-arrow-right"></i></button>` :
                    '';

                list.innerHTML += `
            <div class="va-role-row ${!isSuperAdmin ? 'va-redirect-btn' : ''}" data-role-id="${roleId}" data-role-name="${roleName}" data-role-slug="${roleSlug}" data-color="${color}" style="${isSuperAdmin ? 'cursor:default;' : ''}">
                <div style="width:40px; height:40px; border-radius:10px; background:${color}; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px;">${initials}</div>
                <div style="flex:1;">
                    <div style="font-size:13px; font-weight:800; color:#111827; margin-bottom:2px;">${roleName}</div>
                    <div style="display:flex; flex-wrap:wrap; gap:5px;">${tags}</div>
                </div>
                ${goBtn}
            </div>`;
            });

            list.querySelectorAll('.va-redirect-btn').forEach(el => {
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const t = this.closest('[data-role-id]') || this;
                    redirectToRole(t.dataset.roleId, t.dataset.roleName, t.dataset.roleSlug, t.dataset
                        .color);
                });
            });

            document.getElementById('vaTotalPerms').textContent = totalPerms;
            document.getElementById('vaTotalRoles').textContent = totalRoles;
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeViewAs() {
            isModalActive = false;
            updateFABVisibility();
            document.getElementById('vaOverlay').classList.remove('open');
            document.body.style.overflow = '';
        }

        // MODALS: Patient Picker & Impersonation
        let patientAccountsCache = [];

        function escapeHtml(v) {
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function closePatientPicker() {
            isModalActive = false;
            updateFABVisibility();
            document.getElementById('patientPickerOverlay').classList.remove('open');
            document.body.style.overflow = '';
        }

        function redirectToRole(roleId, roleName, roleSlug, color) {
            if (roleSlug === 'patient' || roleSlug === 'patient_role') {
                closeViewAs();
                openPatientPicker(roleName, roleSlug, color);
                return;
            }

            closeViewAs();
            triggerRedirect(roleName, roleSlug, null, color, `Loading ${roleName} view for Super Admin`);
        }

        function openPatientPicker(roleName, roleSlug, color) {
            isModalActive = true;
            updateFABVisibility();

            fetch("{{ route('admin.patients.list') }}", {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    const d = await res.json();
                    if (!res.ok) throw new Error(d.message || 'Error');
                    patientAccountsCache = Array.isArray(d) ? d : [];
                    renderPatientPicker(patientAccountsCache);
                    document.getElementById('patientPickerSearch').value = '';
                    document.getElementById('patientPickerOverlay').classList.add('open');
                    document.body.style.overflow = 'hidden';
                })
                .catch(err => {
                    if (typeof showToast === 'function') {
                        showToast('Error', err.message || 'Unable to load patients', 'error');
                    }
                });
        }

        function renderPatientPicker(patients) {
            const list = document.getElementById('patientPickerList');
            if (!list) return;
            if (!patients.length) {
                list.innerHTML =
                    '<div style="text-align:center;padding:32px 20px;color:#6b7280;font-size:14px;">No patients found.</div>';
                return;
            }
            list.innerHTML = patients.map(p => {
                const n = (p.name || 'Patient').replace(/'/g, "\\'");
                const i = (p.name || 'P').charAt(0).toUpperCase();
                return `<div class="va-role-row" onclick="startPatientImpersonation('patient','patient','#059669',${p.id},'${n}')">
            <div style="width:40px; height:40px; border-radius:10px; background:#059669; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px;">${i}</div>
            <div style="flex:1;">
                <div style="font-size:13px; font-weight:800; color:#111827;">${escapeHtml(p.name || 'Unnamed')}</div>
                <div style="font-size:11px; color:#6b7280;">${escapeHtml(p.email || '')} | ID: ${p.id}</div>
            </div>
            <button class="va-go-btn">Impersonate <i class="fa-solid fa-arrow-right"></i></button>
        </div>`;
            }).join('');
        }

        function filterPatientPicker(q) {
            q = q.toLowerCase().trim();
            if (!q) {
                renderPatientPicker(patientAccountsCache);
                return;
            }
            const filtered = patientAccountsCache.filter(p => ((p.name || '') + (p.email || '')).toLowerCase().includes(q));
            renderPatientPicker(filtered);
        }

        function startPatientImpersonation(roleName, roleSlug, color, patientId, patientName) {
            closePatientPicker();
            triggerRedirect(patientName, roleSlug, patientId, color, 'Loading patient dashboard for Super Admin');
        }

        function triggerRedirect(title, slug, patientId, color, sub) {
            const ol = document.getElementById('redirectOverlay');
            ol.style.background = `linear-gradient(135deg, ${color}, #1f2937)`;
            document.getElementById('redirectText').textContent = `Redirecting to ${title}…`;
            document.getElementById('redirectSub').textContent = sub;
            ol.classList.add('show');

            const body = {
                role: slug
            };
            if (patientId) body.patient_id = patientId;

            fetch("{{ route('admin.impersonate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                })
                .then(async res => {
                    const d = await res.json();
                    if (!res.ok) throw new Error(d.message || 'Error');
                    if (d.redirect) {
                        window.location.href = d.redirect;
                        return;
                    }
                    throw new Error('No redirect');
                })
                .catch(err => {
                    ol.classList.remove('show');
                    if (typeof showToast === 'function') {
                        showToast('Error', err.message || 'Something went wrong', 'error');
                    }
                });
        }

        function hexDarken(hex) {
            const r = parseInt(hex.slice(1, 3), 16),
                g = parseInt(hex.slice(3, 5), 16),
                b = parseInt(hex.slice(5, 7), 16);
            return '#' + Math.max(0, r - 45).toString(16).padStart(2, '0') + Math.max(0, g - 45).toString(16).padStart(2,
                '0') + Math.max(0, b - 45).toString(16).padStart(2, '0');
        }
    </script>
@endsection
