@extends('layouts.admin')

@section('title', 'Service Types | Admin Dashboard')

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

        #toastContainer .toast {
            min-width: 300px;
            max-width: 360px;
            background: white !important;
            border-radius: 14px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .15) !important;
            padding: 14px 16px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px;
            opacity: 0;
            transform: translateX(340px);
            transition: all .35s cubic-bezier(.68, -.55, .265, 1.55);
            position: relative;
            overflow: hidden;
            pointer-events: all;
            flex-direction: row !important;
        }

        #toastContainer .toast::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: none;
        }

        #toastContainer .toast.error::before {
            background: var(--crimson) !important;
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
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toast.error .toast-icon-wrap {
            background: rgba(139, 0, 0, .08);
        }

        .toast.success .toast-icon-wrap {
            background: rgba(22, 163, 74, .08);
        }

        .toast-icon {
            font-size: 15px;
        }

        .toast.error .toast-icon {
            color: var(--crimson) !important;
        }

        .toast.success .toast-icon {
            color: #16a34a !important;
        }

        .toast-body {
            flex: 1;
            min-width: 0;
        }

        .toast-title {
            font-size: 12px;
            font-weight: 800;
            color: #333333 !important;
        }

        .toast-msg {
            font-size: 11px;
            color: #9ca3af !important;
            margin-top: 2px;
            line-height: 1.4;
        }

        .toast-close {
            background: none !important;
            border: none;
            cursor: pointer;
            color: #d1d5db;
            font-size: 12px;
            flex-shrink: 0;
            padding: 2px 4px;
            transition: color .15s;
        }

        .toast-close:hover {
            color: #6b7280;
        }

        [data-theme="dark"] #toastContainer .toast {
            background: #161b22 !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .4) !important;
        }

        [data-theme="dark"] .toast-title {
            color: #f3f4f6 !important;
        }

        [data-theme="dark"] .toast.error .toast-icon-wrap {
            background: rgba(239, 68, 68, .1);
        }

        [data-theme="dark"] .toast.success .toast-icon-wrap {
            background: rgba(34, 197, 94, .1);
        }

        .page-banner {
            background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 60%, #c0392b 100%);
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
        }

        .content-lift {
            margin-top: 0;
            padding: 0 1.75rem 2rem;
            position: relative;
            z-index: 2;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 1.25rem;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, .05);
            box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
            overflow: hidden;
        }

        .card-header {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .card-header-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--crimson-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: var(--crimson);
        }

        .card-title {
            font-size: .82rem;
            font-weight: 800;
            color: #1a202c;
        }

        .st-form-group {
            margin-bottom: 1.1rem;
        }

        .st-label {
            display: block;
            font-size: .68rem;
            font-weight: 700;
            color: #4b5563;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .st-input-wrap {
            position: relative;
            flex: 1 1 auto;
            min-width: 0;
        }

        .st-input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 12px;
            pointer-events: none;
            z-index: 2;
        }

        /* ── FIX: input takes full width of its wrap; right padding reserves
           space for the Clear button so text never slides under it ── */
        .st-input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 44px 10px 34px; /* left=icon, right=clear btn */
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: .8rem;
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #1f2937;
            outline: none;
            transition: all .2s;
            display: block;
        }

        .st-textarea-wrap {
            width: 100%;
            position: relative;
        }

        .st-textarea {
            width: 100%;
            box-sizing: border-box;
            display: block;
            min-height: 110px;
            resize: none;
            padding: 10px 14px 26px 14px;
            line-height: 1.45;
        }

        .st-input.with-icon {
            padding-left: 34px;
        }

        .st-input::placeholder {
            color: #9ca3af;
        }

        .st-input:focus {
            border-color: var(--crimson);
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        .st-input.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .st-input.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .2);
        }

        /* ── voice row: input-wrap grows, mic toggle is fixed size ── */
        .st-voice-row {
            display: flex;
            align-items: center;
            gap: .5rem;
            width: 100%;
        }

        .st-voice-row.is-textarea {
            align-items: flex-start;
        }

        /* ── FIX: Clear button sits INSIDE the input, right of text,
           left of the mic button. Absolute inside .st-input-wrap ── */
        .st-voice-clear-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #dc2626;
            font-size: .72rem;
            font-weight: 700;
            cursor: pointer;
            z-index: 5;
            line-height: 1;
            padding: 2px 0;
        }

        /* textarea variant: align to top */
        .st-voice-row.is-textarea .st-voice-clear-btn {
            top: auto;
            bottom: 30px;
            transform: none;
        }

        .st-voice-clear-btn.hidden {
            visibility: hidden !important;
            display: inline-flex !important;
            pointer-events: none;
        }

        .st-voice-clear-btn:hover {
            color: #991b1b;
        }

        .st-char-count {
            position: absolute;
            bottom: 8px;
            right: 12px;
            font-size: .65rem;
            font-weight: 700;
            color: #9ca3af;
            pointer-events: none;
            transition: color .2s;
        }

        .st-char-count.near-limit {
            color: #f59e0b;
        }

        .st-char-count.at-limit {
            color: #ef4444;
        }

        .st-copy-bullet-wrap {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 8px;
        }

        .st-copy-bullet-box {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fafafa;
            color: #4b5563;
            font-size: .74rem;
            font-weight: 700;
            line-height: 1;
            user-select: none;
            cursor: default;
        }

        .st-copy-bullet-symbol {
            font-size: .95rem;
            color: var(--crimson);
            line-height: 1;
            user-select: all;
            cursor: text;
            display: inline-block;
            padding: 1px 2px;
        }

        .st-copy-bullet-label {
            font-size: .68rem;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: .02em;
            user-select: none;
        }

        [data-theme="dark"] .st-copy-bullet-box {
            background: #111827;
            border-color: #374151;
            color: #e5e7eb;
        }

        [data-theme="dark"] .st-copy-bullet-label {
            color: #9ca3af;
        }

        .st-field-error {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: .72rem;
            font-weight: 600;
            color: #ef4444;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--crimson) 0%, var(--crimson-dark) 100%);
            color: #fff;
            font-weight: 800;
            font-size: .8rem;
            padding: .8rem 1rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: all .2s;
            box-shadow: 0 4px 14px rgba(139, 0, 0, .3);
            font-family: 'Inter', sans-serif;
        }

        .btn-submit:hover {
            box-shadow: 0 6px 20px rgba(139, 0, 0, .4);
            transform: translateY(-1px);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .76rem;
        }

        .data-table thead th {
            padding: .7rem 1rem;
            text-align: left;
            font-weight: 700;
            color: #9ca3af;
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            background: #fafafa;
            border-bottom: 1px solid #f3f4f6;
        }

        .data-table tbody td {
            padding: .8rem 1rem;
            color: #4a5568;
            border-bottom: 1px solid #f9fafb;
            vertical-align: middle;
        }

        .data-table tbody tr:hover td {
            background: #fafafa;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .service-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 24px;
            padding: 0 8px;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 6px;
            font-size: .68rem;
            font-weight: 700;
        }

        .service-visibility-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .service-visibility-badge.is-visible {
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .service-visibility-badge.is-hidden {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .btn-delete-sm,
        .btn-manage-sm {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            cursor: pointer;
            transition: all .15s;
        }

        .btn-delete-sm {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fee2e2;
        }

        .btn-delete-sm:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
            box-shadow: 0 2px 8px rgba(239, 68, 68, .3);
        }

        .btn-manage-sm {
            background: #fef2f2;
            color: var(--crimson);
            border: 1px solid #fee2e2;
        }

        .btn-manage-sm:hover {
            background: var(--crimson);
            color: #fff;
            border-color: var(--crimson);
            box-shadow: 0 2px 8px rgba(139, 0, 0, .25);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.4rem;
            color: #d1d5db;
        }

        #deleteServiceModal {
            border: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 32px 24px 24px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            background: #ffffff;
            overflow: visible;
            width: min(90vw, 380px);
            text-align: center;
        }

        #deleteServiceModal::backdrop {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(4px);
        }

        .del-modal-icon {
            width: 64px;
            height: 64px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: #ef4444;
            font-size: 28px;
            border: 6px solid #fff;
            box-shadow: 0 0 0 1px #fee2e2;
        }

        .del-modal-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #111827;
            margin: 0 0 8px;
            text-align: center;
        }

        .del-modal-body {
            font-size: .85rem;
            color: #6b7280;
            line-height: 1.6;
            margin: 0 0 24px;
            text-align: center;
        }

        .del-modal-name {
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

        .del-modal-warning {
            display: block;
            color: #ef4444;
            font-weight: 600;
            font-size: .8rem;
            margin-top: 8px;
        }

        .del-modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .del-btn-cancel {
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #374151;
            font-weight: 600;
            font-size: .85rem;
            cursor: pointer;
            transition: all .15s;
            font-family: 'Inter', sans-serif;
        }

        .del-btn-cancel:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .del-btn-confirm {
            padding: 10px;
            border-radius: 10px;
            border: none;
            background: #ef4444;
            color: #ffffff;
            font-weight: 600;
            font-size: .85rem;
            cursor: pointer;
            transition: all .15s;
            width: 100%;
            font-family: 'Inter', sans-serif;
        }

        .del-btn-confirm:hover {
            background: #dc2626;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .data-table tbody tr {
            height: 60px;
        }

        .data-table tbody td {
            vertical-align: middle !important;
        }

        .manage-service-body {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 8px;
        }

        .manage-toggle-row {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
            padding: .8rem .9rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fafafa;
        }

        .manage-toggle-copy {
            min-width: 0;
        }

        .manage-toggle-title {
            font-size: .8rem;
            font-weight: 700;
            color: #1f2937;
        }

        .manage-toggle-desc {
            font-size: .72rem;
            color: #6b7280;
            line-height: 1.5;
            margin-top: 2px;
        }

        [data-theme="dark"] #deleteServiceModal,
        [data-theme="dark"] #manageServiceModal {
            background: #1f2937;
        }

        [data-theme="dark"] .del-modal-icon {
            background: rgba(239, 68, 68, 0.1);
            border-color: #1f2937;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.2);
        }

        [data-theme="dark"] .del-modal-title {
            color: #f9fafb;
        }

        [data-theme="dark"] .del-modal-body {
            color: #9ca3af;
        }

        [data-theme="dark"] .del-modal-name {
            color: #f9fafb;
            background: #374151;
        }

        [data-theme="dark"] .del-btn-cancel {
            background: #374151;
            border-color: #4b5563;
            color: #d1d5db;
        }

        [data-theme="dark"] .del-btn-cancel:hover {
            background: #4b5563;
        }

        [data-theme="dark"] .card {
            background: #161b22;
            border-color: #21262d;
        }

        [data-theme="dark"] .card-header {
            background: #0d1117;
            border-color: #21262d;
        }

        [data-theme="dark"] .card-title {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .card-header-icon {
            background: rgba(139,0,0,.16) !important;
            border: 1px solid rgba(220,38,38,.18) !important;
            color: #b91c1c !important;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.04),
                0 4px 12px rgba(0,0,0,.18);
        }

        [data-theme="dark"] .card-header > span {
            background: rgba(255,255,255,.06) !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            color: #FCA5A5 !important;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.04),
                0 4px 12px rgba(0,0,0,.18);
        }

        [data-theme="dark"] .st-input {
            background: #0d1117;
            border-color: #21262d;
            color: #f3f4f6;
        }

        [data-theme="dark"] .data-table thead th {
            background: #0d1117;
            border-color: #21262d;
            color: #6b7280;
        }

        [data-theme="dark"] .data-table tbody td {
            border-color: #1c2128;
            color: #d1d5db;
        }

        [data-theme="dark"] .data-table tbody tr:hover td {
            background: #1c2128;
        }

        [data-theme="dark"] td div[style*="width:26px"][style*="background:#fef2f2"] {
            background: rgba(255,255,255,.05) !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            color: rgba(255,255,255,.72) !important;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.04),
                0 4px 12px rgba(0,0,0,.18);
        }

        [data-theme="dark"] .service-visibility-badge.is-visible {
            background: rgba(16,185,129,.12) !important;
            border: 1px solid rgba(52,211,153,.24) !important;
            color: #d1fae5 !important;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
        }

        [data-theme="dark"] .service-badge {
            background: rgba(16,185,129,.10) !important;
            border: 1px solid rgba(52,211,153,.22) !important;
            color: #d1fae5 !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
        }

        [data-theme="dark"] .btn-manage-sm {
            background: rgba(255,255,255,.06) !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            color: #fecaca !important;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.05),
                0 4px 12px rgba(0,0,0,.18);
        }

        [data-theme="dark"] .btn-manage-sm:hover {
            background: rgba(139,0,0,.35) !important;
            border-color: rgba(252,165,165,.22) !important;
            color: #fff !important;
        }

        [data-theme="dark"] #serviceTypeListView .data-table tbody td:nth-child(2) > div > span {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .manage-toggle-row {
            background: #111827;
            border-color: #374151;
        }

        [data-theme="dark"] .manage-toggle-title {
            color: #f9fafb;
        }

        [data-theme="dark"] .manage-toggle-desc {
            color: #9ca3af;
        }

        .service-type-view-toggle {
            display: inline-flex;
            align-items: center;
            background: #FAFAF9;
            border: 1.5px solid #E0DDD8;
            border-radius: 12px;
            padding: 3px;
            gap: 3px;
            height: 38px;
        }

        .service-type-view-btn {
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

        .service-type-view-btn:hover {
            background: #f3f4f6;
            color: var(--crimson);
        }

        .service-type-view-btn.active {
            background: var(--crimson);
            color: #fff;
            box-shadow: 0 2px 8px rgba(139, 0, 0, .15);
        }

        .service-type-view[hidden] {
            display: none !important;
        }

        .service-types-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            padding: 1rem;
        }

        .service-type-card {
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

        .service-type-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, .06);
            border-color: #ead6d6;
        }

        .service-type-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .service-type-card-id {
            font-size: .72rem;
            font-weight: 800;
            color: var(--crimson);
            line-height: 1.2;
        }

        .service-type-card-name-wrap {
            display: flex;
            align-items: center;
            gap: .7rem;
            min-width: 0;
        }

        .service-type-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--crimson), var(--crimson-dark));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .82rem;
            flex-shrink: 0;
        }

        .service-type-card-name {
            font-size: .88rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.25;
            word-break: break-word;
        }

        .service-type-card-desc-wrap {
            min-width: 0;
        }

        .service-type-card-label {
            font-size: .64rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .28rem;
        }

        .service-type-card-desc {
            font-size: .8rem;
            color: #374151;
            line-height: 1.4;
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .service-type-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-top: .1rem;
            flex-wrap: wrap;
        }

        .service-type-card-actions {
            display: flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
        }

        [data-theme="dark"] .service-type-view-toggle {
            background: rgba(255,255,255,.10) !important;
            border: 1px solid rgba(255,255,255,.18) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.05),
                0 4px 14px rgba(0,0,0,.18);
        }

        [data-theme="dark"] .service-type-view-btn {
            color: rgba(255,255,255,.72) !important;
        }

        [data-theme="dark"] .service-type-view-btn:hover {
            background: rgba(255,255,255,.08) !important;
            color: #fff !important;
        }

        [data-theme="dark"] .service-type-view-btn {
            color: #9ca3af;
        }

        [data-theme="dark"] .service-type-view-btn:hover {
            background: #1c2128;
            color: #f3f4f6;
        }

        [data-theme="dark"] .service-type-card {
            background: #161b22;
            border-color: #21262d;
        }

        [data-theme="dark"] .service-type-card-name {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .service-type-card-desc {
            color: #d1d5db;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at top, rgba(139, 0, 0, .18), rgba(0, 0, 0, .55));
            backdrop-filter: blur(4px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .2s ease, visibility .2s ease;
        }

        .modal-overlay.open {
            display: flex !important;
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .st-modal-box {
            position: relative;
            z-index: 100000;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background: #fff;
            width: min(980px, calc(100vw - 2rem));
            max-width: 980px;
            max-height: min(88vh, calc(100dvh - 2rem));
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(0, 0, 0, .28);
            border: 1px solid rgba(255,255,255,.35);
            transform: scale(.96) translateY(12px);
            transition: transform .25s cubic-bezier(.4, 0, .2, 1);
        }

        .modal-overlay.open .st-modal-box {
            transform: scale(1) translateY(0);
        }

        .st-manage-form {
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }

        .st-modal-header {
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid #eef2f7;
            background: #ffffff;
        }

        .st-modal-header::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 5px;
            background: linear-gradient(90deg, #8b0000, #dc2626);
        }

        .st-modal-header-left {
            display: flex;
            align-items: center;
            gap: .95rem;
            min-width: 0;
            flex: 1;
        }

        .st-modal-header-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(145deg, #8b0000, #b80000);
            box-shadow: 0 12px 24px rgba(139, 0, 0, 0.18);
            flex-shrink: 0;
        }

        .st-modal-title {
            font-size: 1.4rem;
            font-weight: 900;
            line-height: 1.1;
            color: #0f172a;
            margin: 0;
        }

        .st-modal-subtitle {
            margin: .28rem 0 0;
            font-size: .85rem;
            color: #64748b;
            font-weight: 500;
            line-height: 1.35;
        }

        .st-modal-close {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #94a3b8;
            transition: all .2s ease;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .st-modal-close:hover {
            color: #8b0000;
            border-color: #fecaca;
            background: #fff5f5;
        }

        .st-modal-body {
            padding: 1.2rem 1.5rem 1rem;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem 1.1rem;
            align-content: start;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }

        .st-panel {
            border: 1px solid #e9eef5;
            background: #ffffff;
            border-radius: 18px;
            padding: 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .st-col-span-2 {
            grid-column: span 2;
        }

        .st-modal-field-wrap,
        .st-modal-textarea-wrap {
            position: relative;
            width: 100%;
            display: block;
        }

        .st-modal-field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: #9ca3af;
            pointer-events: none;
            z-index: 2;
        }

        .st-modal-input {
            width: 100% !important;
            max-width: 100%;
            display: block;
            height: 54px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 0 16px 0 42px;
            font-size: 14px;
            background: #fff;
            box-sizing: border-box;
        }

        .st-modal-textarea {
            width: 100% !important;
            max-width: 100%;
            display: block;
            min-height: 154px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px 16px;
            font-size: 13px;
            line-height: 1.45;
            resize: none;
            background: #fff;
            box-sizing: border-box;
        }

        .st-panel > .st-modal-field-wrap,
        .st-panel > .st-modal-textarea-wrap {
            width: 100%;
        }

        .st-panel input,
        .st-panel textarea {
            width: 100% !important;
        }

        .st-input-shell {
            position: relative;
        }

        .st-modal-input:focus,
        .st-modal-textarea:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.10);
        }

        .st-active-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: 1px solid #f3d4d4;
            border-radius: 18px;
            background: linear-gradient(90deg, #fff7f7 0%, #ffffff 100%);
            padding: 1rem 1.05rem;
        }

        .st-active-card-left {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .st-active-badge {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #8B0000;
            box-shadow: 0 4px 14px rgba(139, 0, 0, 0.08);
            flex-shrink: 0;
        }

        .st-active-title {
            font-size: 14px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 2px;
        }

        .st-active-desc {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            margin: 0;
            line-height: 1.4;
        }

        .st-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        .st-switch input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .st-switch-slider {
            width: 48px;
            height: 28px;
            border-radius: 999px;
            background: #d1d5db;
            position: relative;
            transition: .2s ease;
        }

        .st-switch-slider::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .18);
            transition: .2s ease;
        }

        .st-switch input:checked + .st-switch-slider {
            background: #8B0000;
        }

        .st-switch input:checked + .st-switch-slider::after {
            transform: translateX(20px);
        }

        .st-default-note {
            margin-top: .75rem;
            font-size: 12px;
            font-weight: 600;
            color: #8B0000;
        }

        .st-modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .75rem;
            padding: 1rem 1.5rem 1.15rem;
            border-top: 1px solid #eef2f7;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
        }

        .st-btn {
            height: 46px;
            border-radius: 14px;
            padding: 0 1.2rem;
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            border: 1px solid transparent;
            transition: all .2s ease;
        }

        .st-btn-ghost {
            background: #fff;
            color: #4b5563;
            border-color: #e5e7eb;
        }

        .st-btn-ghost:hover {
            background: #f9fafb;
            border-color: #cbd5e1;
        }

        .st-btn-primary {
            background: linear-gradient(145deg, #8b0000, #760000);
            color: #fff;
            box-shadow: 0 10px 20px rgba(139, 0, 0, 0.16);
        }

        .st-btn-primary:hover {
            filter: brightness(1.04);
        }

        [data-theme="dark"] .st-modal-box,
        [data-theme="dark"] .st-panel,
        [data-theme="dark"] .st-modal-header,
        [data-theme="dark"] .st-modal-footer,
        [data-theme="dark"] .st-modal-close {
            background: #161b22 !important;
            border-color: #2b313a !important;
        }

        [data-theme="dark"] .st-modal-title,
        [data-theme="dark"] .st-active-title {
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .st-modal-subtitle,
        [data-theme="dark"] .st-active-desc,
        [data-theme="dark"] .st-modal-label {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .st-modal-input,
        [data-theme="dark"] .st-modal-textarea {
            background: #0d1117 !important;
            border-color: #2b313a !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .st-active-card {
            background: #0f141a !important;
            border-color: #2b313a !important;
        }

        [data-theme="dark"] .st-modal-body {
            background: linear-gradient(180deg, #0d1117 0%, #161b22 100%) !important;
        }

        [data-theme="dark"] .st-modal-footer {
            background: #161b22 !important;
        }

        @media (max-width: 767px) {
            .st-modal-box {
                width: 100%;
                max-width: 100%;
                max-height: calc(100dvh - 10px);
                border-radius: 22px 22px 0 0;
            }

            .st-modal-header,
            .st-modal-body,
            .st-modal-footer {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .st-modal-body {
                grid-template-columns: 1fr;
            }

            .st-col-span-2 {
                grid-column: span 1;
            }

            .st-active-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .st-modal-footer {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .st-btn {
                width: 100%;
            }
        }

        @media (max-width: 900px) {
            .service-types-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .page-banner {
                padding: 1.5rem 1rem 3rem;
            }

            .content-lift {
                padding: 0 1rem 2rem;
            }

            #serviceTypeListView {
                display: none !important;
            }

            #serviceTypeGridView {
                display: block !important;
            }

            #serviceTypeViewToggle {
                display: none !important;
            }

            .service-types-grid {
                grid-template-columns: 1fr;
                padding: .85rem;
                gap: .85rem;
            }
        }

        @keyframes micPulse {
            0%, 100% { box-shadow: 0 0 0 0px rgba(192, 57, 43, 0.4); }
            50%       { box-shadow: 0 0 0 8px rgba(192, 57, 43, 0); }
        }

        /* ── Circular mic button ── */
        .service-voice-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
        }

        .service-voice-toggle .voice-search-mic.external {
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
        }

        .service-voice-toggle .voice-search-mic.external:hover {
            background: #374151;
        }

        .service-voice-toggle .voice-search-mic.external i {
            font-size: 12px;
            line-height: 1;
        }

        .service-voice-toggle .patient-voice-status {
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

        .service-voice-toggle .patient-voice-status.hidden {
            display: none;
        }

        .service-voice-toggle .patient-voice-status.is-listening {
            color: #1d4ed8;
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .service-voice-toggle .patient-voice-status.is-error {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fef2f2;
        }

        .service-voice-toggle .patient-voice-status.is-success {
            color: #166534;
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .service-voice-toggle .voice-search-mic.external.mic-active {
            background: #c0392b;
            transform: scale(1.1);
            animation: micPulse 1.2s ease-in-out infinite;
        }
        /* ── Modal voice rows ── */
        .st-modal-voice-row {
            display: flex;
            align-items: center;
            gap: .5rem;
            width: 100%;
        }

        .st-modal-voice-row--textarea {
            align-items: flex-start;
        }

        .st-modal-voice-row .st-modal-field-wrap,
        .st-modal-voice-row .st-modal-textarea-wrap {
            flex: 1 1 auto;
            min-width: 0;
        }

        /* Shrink the modal input/textarea so the mic button fits inside the panel */
        .st-modal-voice-row .st-modal-input,
        .st-modal-voice-row .st-modal-textarea {
            width: 100%;
        }

        .st-modal-voice-row .service-voice-toggle {
            flex-shrink: 0;
        }

        /* Nudge mic down to align with top of textarea */
        .st-modal-voice-row--textarea .service-voice-toggle {
            margin-top: 6px;
        }

        /* Allow mic button to sit inside the panel without clipping */
        .st-panel {
            overflow: visible;
        }
    </style>
@endsection

@section('content')
    <main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-screen">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Service Types</h1>
                </div>

                <div class="flex items-center gap-2 whitespace-nowrap">
                    <span style="
                        background: rgba(255,255,255,.12);
                        border: 1px solid rgba(255,255,255,.18);
                        color: #fff;
                        padding: .6rem 1rem;
                        border-radius: 10px;
                        font-size: .75rem;
                        font-weight: 700;
                        line-height: 1;
                    ">
                        Active Services: {{ $services->count() }}
                    </span>

                    <div class="service-type-view-toggle" id="serviceTypeViewToggle" style="margin-left:4px;">
                        <button type="button" class="service-type-view-btn active" id="serviceTypeListBtn" title="List view" aria-label="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="service-type-view-btn" id="serviceTypeGridBtn" title="Grid view" aria-label="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-lift">
            <div class="main-grid">

                <div style="display:flex;flex-direction:column;gap:1.25rem;">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-plus"></i></div>
                                <span class="card-title">Add New Service</span>
                            </div>
                        </div>

                        <div style="padding:1.25rem;">
                            <form id="addServiceForm" method="POST" action="{{ route('admin.service-types.store') }}" novalidate>
                                @csrf

                                <div class="st-form-group">
                                    <label class="st-label">Service Name</label>
                                    <div class="st-voice-row">
                                        <div class="st-input-wrap">
                                            <i class="fa-solid fa-tag st-input-icon"></i>
                                            <input type="text" id="serviceNameInput" name="name"
                                                placeholder="e.g. Tooth Extraction" autocomplete="off"
                                                value="{{ old('name') }}" class="st-input with-icon no-voice">
                                            {{-- Clear button lives INSIDE the input-wrap, right of text --}}
                                            <button type="button" id="serviceNameClearBtn" class="st-voice-clear-btn hidden">Clear</button>
                                        </div>
                                        {{-- Mic toggle is a sibling of st-input-wrap, not inside it --}}
                                        <div class="service-voice-toggle" id="serviceNameVoiceToggle"></div>
                                    </div>

                                    <div id="nameClientError" class="st-field-error" style="display: none;">
                                        <i class="fa-solid fa-circle-exclamation"></i> Please provide a service name.
                                    </div>

                                    @error('name')
                                        <div class="st-field-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="st-form-group">
                                    <label class="st-label">Description (Optional)</label>

                                    <div class="st-copy-bullet-wrap">
                                        <div class="st-copy-bullet-box" title="Copy this bullet and paste into the description">
                                            <span class="st-copy-bullet-symbol">•</span>
                                            <span class="st-copy-bullet-label">Copy this bullet</span>
                                        </div>
                                    </div>

                                    <div class="st-voice-row is-textarea">
                                        <div class="st-input-wrap st-textarea-wrap">
                                            <textarea id="serviceDescInput"
                                                name="description"
                                                placeholder="Brief details about the service..."
                                                class="st-input st-textarea no-voice"
                                                maxlength="255">{{ old('description') }}</textarea>

                                            <div id="serviceDescCount" class="st-char-count">0 / 255</div>
                                            <button type="button" id="serviceDescClearBtn" class="st-voice-clear-btn hidden">Clear</button>
                                        </div>
                                        <div class="service-voice-toggle" id="serviceDescVoiceToggle"></div>
                                    </div>

                                    @error('description')
                                        <div class="st-field-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn-submit">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Save Service
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:1.25rem;">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-list-check"></i></div>
                                <span class="card-title">Existing Services</span>
                            </div>
                            <span
                                style="font-size:.65rem;font-weight:700;background:#fef2f2;color:var(--crimson);padding:.25rem .6rem;border-radius:20px;border:1px solid #fce8e8;">
                                {{ $services->count() }} {{ Str::plural('Item', $services->count()) }}
                            </span>
                        </div>

                        <div class="service-type-view" id="serviceTypeListView">
                            <div style="overflow-x:auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th style="width:70px;">ID</th>
                                            <th style="width:250px;">Service Name</th>
                                            <th>Description</th>
                                            <th style="width:220px; text-align:center;">Booking Visibility</th>
                                            <th style="width:100px; text-align:center;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($services as $service)
                                            <tr>
                                                <td><span class="service-badge">#{{ $service->id }}</span></td>
                                                <td>
                                                    <div style="display:flex; align-items:center; gap:.6em; min-height:40px;">
                                                        <div
                                                            style="width:26px; height:26px; background:#fef2f2; color:var(--crimson); border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:11px;">
                                                            <i class="fa-solid fa-tooth"></i>
                                                        </div>
                                                        <span style="font-size:.78rem;font-weight:700;color:#1a202c;">
                                                            {{ $service->name }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td style="font-size:.72rem; line-height:1.5;">
                                                    {{ $service->description ?: '—' }}
                                                </td>
                                                <td style="text-align:center;">
                                                    <div style="display:inline-flex; align-items:center; justify-content:center; gap:.45rem; flex-wrap:wrap;">
                                                        @if ($service->is_active_for_booking)
                                                            <span class="service-visibility-badge is-visible">
                                                                <i class="fa-solid fa-thumbtack"></i> Visible
                                                            </span>
                                                        @else
                                                            <span class="service-visibility-badge is-hidden">
                                                                <i class="fa-solid fa-eye-slash"></i> Hidden
                                                            </span>
                                                        @endif

                                                        @if ($service->is_default)
                                                            <span class="service-badge" style="background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;">
                                                                Default
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td style="text-align:center;">
                                                    <div style="display:flex; align-items:center; justify-content:center; gap:.4rem;">
                                                        <button
                                                            type="button"
                                                            class="btn-manage-sm"
                                                            title="Manage"
                                                            onclick="openManageServiceModal(
                                                                '{{ route('admin.service-types.update', $service->id) }}',
                                                                @js($service->name),
                                                                @js($service->description),
                                                                {{ $service->is_active_for_booking ? 'true' : 'false' }},
                                                                {{ $service->is_default ? 'true' : 'false' }}
                                                            )"
                                                        >
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>

                                                        @if (!$service->is_default)
                                                            <button
                                                                type="button"
                                                                class="btn-delete-sm"
                                                                title="Delete"
                                                                onclick="openDeleteModal('{{ route('admin.service-types.destroy', $service->id) }}', '{{ addslashes($service->name) }}')"
                                                            >
                                                                <i class="fa-solid fa-trash-can"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5">
                                                    <div class="empty-state">
                                                        <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                                                        <p style="font-size:.82rem;font-weight:700;color:#6b7280;margin-bottom:.25rem;">
                                                            No services found
                                                        </p>
                                                        <p style="font-size:.72rem;color:#b0b7c3;">
                                                            Your clinic doesn't have any service types yet. Use the form to add one.
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="service-type-view" id="serviceTypeGridView" hidden>
                            @if($services->count())
                                <div class="service-types-grid">
                                    @foreach($services as $service)
                                        <div class="service-type-card">
                                            <div class="service-type-card-top">
                                                <div class="service-type-card-id">#{{ $service->id }}</div>

                                                @if ($service->is_default)
                                                    <span class="service-badge" style="background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;">
                                                        Default
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="service-type-card-name-wrap">
                                                <div class="service-type-card-icon">
                                                    <i class="fa-solid fa-tooth"></i>
                                                </div>
                                                <div class="service-type-card-name">{{ $service->name }}</div>
                                            </div>

                                            <div class="service-type-card-desc-wrap">
                                                <div class="service-type-card-label">Description</div>
                                                <div class="service-type-card-desc">
                                                    {{ $service->description ?: '—' }}
                                                </div>
                                            </div>

                                            <div class="service-type-card-footer">
                                                <div style="display:flex; align-items:center; gap:.45rem; flex-wrap:wrap;">
                                                    @if ($service->is_active_for_booking)
                                                        <span class="service-visibility-badge is-visible">
                                                            <i class="fa-solid fa-thumbtack"></i> Visible
                                                        </span>
                                                    @else
                                                        <span class="service-visibility-badge is-hidden">
                                                            <i class="fa-solid fa-eye-slash"></i> Hidden
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="service-type-card-actions">
                                                    <button
                                                        type="button"
                                                        class="btn-manage-sm"
                                                        title="Manage"
                                                        onclick="openManageServiceModal(
                                                            '{{ route('admin.service-types.update', $service->id) }}',
                                                            @js($service->name),
                                                            @js($service->description),
                                                            {{ $service->is_active_for_booking ? 'true' : 'false' }},
                                                            {{ $service->is_default ? 'true' : 'false' }}
                                                        )"
                                                    >
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>

                                                    @if (!$service->is_default)
                                                        <button
                                                            type="button"
                                                            class="btn-delete-sm"
                                                            title="Delete"
                                                            onclick="openDeleteModal('{{ route('admin.service-types.destroy', $service->id) }}', '{{ addslashes($service->name) }}')"
                                                        >
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                                    <p style="font-size:.82rem;font-weight:700;color:#6b7280;margin-bottom:.25rem;">
                                        No services found
                                    </p>
                                    <p style="font-size:.72rem;color:#b0b7c3;">
                                        Your clinic doesn't have any service types yet. Use the form to add one.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <dialog id="deleteServiceModal">
            <div class="del-modal-icon">
                <i class="fa-solid fa-trash-can"></i>
            </div>
            <h2 class="del-modal-title">Delete Service Type</h2>
            <div class="del-modal-body">
                Are you sure you want to delete <span class="del-modal-name" id="deleteServiceName"></span>?
                <span class="del-modal-warning">This action cannot be undone.</span>
            </div>
            <div class="del-modal-actions">
                <button type="button" class="del-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <form id="deleteServiceForm" method="POST" action="" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="del-btn-confirm">Yes, delete it</button>
                </form>
            </div>
        </dialog>

        <div class="modal-overlay st-manage-modal" id="manageServiceModal" onclick="closeModalOutside(event, 'manageServiceModal')">
            <div class="st-modal-box">
                <form id="manageServiceForm" method="POST" class="st-manage-form">
                    @csrf
                    @method('PUT')

                    <div class="st-modal-header">
                        <div class="st-modal-header-left">
                            <div class="st-modal-header-icon">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </div>
                            <div>
                                <h3 class="st-modal-title">Manage Service Type</h3>
                                <p class="st-modal-subtitle">Update service details and booking visibility</p>
                            </div>
                        </div>

                        <button type="button" class="st-modal-close" onclick="closeModal('manageServiceModal')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="st-modal-body">
                        <div class="st-panel">
                            <label class="st-modal-label">Service Name <span class="text-red-500">*</span></label>
                            <div class="st-modal-voice-row">
                                <div class="st-modal-field-wrap">
                                    <span class="st-modal-field-icon"><i class="fa-solid fa-tag"></i></span>
                                    <input type="text" id="manageServiceName" name="name" class="st-modal-input no-voice" maxlength="255" required>
                                </div>
                                <div class="service-voice-toggle" id="manageServiceNameVoiceToggle"></div>
                            </div>
                        </div>

                        <div class="st-panel">
                            <label class="st-modal-label">Description</label>

                            <div class="st-copy-bullet-wrap">
                                <div class="st-copy-bullet-box" title="Copy this bullet and paste into the description">
                                    <span class="st-copy-bullet-symbol">•</span>
                                    <span class="st-copy-bullet-label">Copy this bullet</span>
                                </div>
                            </div>

                            <div class="st-modal-voice-row st-modal-voice-row--textarea">
                                <div class="st-modal-textarea-wrap">
                                    <textarea id="manageServiceDescription" name="description" class="st-modal-textarea no-voice" maxlength="255"
                                        placeholder="Brief details about the service..."></textarea>
                                </div>
                                <div class="service-voice-toggle" id="manageServiceDescVoiceToggle"></div>
                            </div>
                        </div>

                        <div class="st-panel st-col-span-2">
                            <div class="st-active-card">
                                <div class="st-active-card-left">
                                    <div class="st-active-badge">
                                        <i class="fa-solid fa-thumbtack"></i>
                                    </div>
                                    <div>
                                        <p class="st-active-title">Show in Book Appointment</p>
                                        <p class="st-active-desc">Turn this off if you want the service hidden from booking but still kept in Service Types.</p>
                                    </div>
                                </div>

                                <label class="st-switch">
                                    <input type="checkbox" id="manageServiceBookingToggle" name="is_active_for_booking" value="1">
                                    <span class="st-switch-slider"></span>
                                </label>
                            </div>

                            <div id="manageDefaultNote" class="st-default-note" style="display:none;">
                                This is a default service type. It can be edited and hidden from booking, but it cannot be deleted.
                            </div>
                        </div>
                    </div>

                    <div class="st-modal-footer">
                        <button type="button" class="st-btn st-btn-ghost" onclick="closeModal('manageServiceModal')">
                            Cancel
                        </button>
                        <button type="submit" class="st-btn st-btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
@endsection

@section('scripts')
    <script>
        document.getElementById('addServiceForm').addEventListener('submit', function(e) {
            const nameInput = document.getElementById('serviceNameInput');
            const errorDiv = document.getElementById('nameClientError');

            if (nameInput.value.trim() === '') {
                e.preventDefault();
                errorDiv.style.display = 'flex';
                nameInput.classList.add('is-invalid');
                nameInput.focus();
            }
        });

        document.getElementById('serviceNameInput').addEventListener('input', function() {
            document.getElementById('nameClientError').style.display = 'none';
            this.classList.remove('is-invalid');
        });

        function openDeleteModal(actionUrl, serviceName) {
            document.getElementById('deleteServiceName').textContent = serviceName;
            document.getElementById('deleteServiceForm').action = actionUrl;
            document.getElementById('deleteServiceModal').showModal();
        }

        function closeDeleteModal() {
            document.getElementById('deleteServiceModal').close();
        }

        function openManageServiceModal(actionUrl, serviceName, serviceDescription, isActiveForBooking, isDefault) {
            const modal = document.getElementById('manageServiceModal');
            const form = document.getElementById('manageServiceForm');
            const nameInput = document.getElementById('manageServiceName');
            const descInput = document.getElementById('manageServiceDescription');
            const bookingToggle = document.getElementById('manageServiceBookingToggle');
            const defaultNote = document.getElementById('manageDefaultNote');

            if (!modal || !form || !nameInput || !descInput || !bookingToggle || !defaultNote) {
                console.error('Manage modal elements not found.');
                return;
            }

            form.action = actionUrl;
            nameInput.value = serviceName ?? '';
            descInput.value = serviceDescription ?? '';
            bookingToggle.checked = Boolean(isActiveForBooking);
            defaultNote.style.display = isDefault ? 'block' : 'none';

            modal.style.display = 'flex';

            requestAnimationFrame(() => {
                modal.classList.add('open');
            });

            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.remove('open');

            setTimeout(() => {
                modal.style.display = 'none';

                const stillOpen = document.querySelector('.modal-overlay.open');
                if (!stillOpen) {
                    document.body.style.overflow = '';
                }
            }, 200);
        }

        function closeModalOutside(event, id) {
            if (event.target && event.target.id === id) {
                closeModal(id);
            }
        }

        function getPreferredServiceTypeView() {
            if (window.innerWidth <= 767) return 'grid';
            return localStorage.getItem('serviceTypeView') || 'list';
        }

        function applyServiceTypeView(view, save = true) {
            const listView = document.getElementById('serviceTypeListView');
            const gridView = document.getElementById('serviceTypeGridView');
            const listBtn = document.getElementById('serviceTypeListBtn');
            const gridBtn = document.getElementById('serviceTypeGridBtn');

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
                localStorage.setItem('serviceTypeView', finalView);
            }
        }

        function initServiceTypeViewToggle() {
            const listBtn = document.getElementById('serviceTypeListBtn');
            const gridBtn = document.getElementById('serviceTypeGridBtn');

            applyServiceTypeView(getPreferredServiceTypeView(), false);

            if (listBtn && !listBtn.dataset.bound) {
                listBtn.dataset.bound = '1';
                listBtn.addEventListener('click', () => applyServiceTypeView('list', true));
            }

            if (gridBtn && !gridBtn.dataset.bound) {
                gridBtn.dataset.bound = '1';
                gridBtn.addEventListener('click', () => applyServiceTypeView('grid', true));
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            initServiceTypeViewToggle();

            function bindVoiceClear(fieldId, clearBtnId) {
                const field = document.getElementById(fieldId);
                const clearBtn = document.getElementById(clearBtnId);
                if (!field || !clearBtn) return;

                const toggleClear = () => {
                    if ((field.value || '').trim().length > 0) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                };

                field.addEventListener('input', toggleClear);

                clearBtn.addEventListener('click', () => {
                    field.value = '';
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    toggleClear();
                    field.focus();
                });

                toggleClear();
            }

            bindVoiceClear('serviceNameInput', 'serviceNameClearBtn');
            bindVoiceClear('serviceDescInput', 'serviceDescClearBtn');

            // ─────────────────────────────────────────────────────────────
            // Voice controllers for service inputs
            // ─────────────────────────────────────────────────────────────
            (function initServiceVoice() {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition) return;

                const voiceInputs = [
                    { inputId: 'serviceNameInput',        toggleWrapperId: 'serviceNameVoiceToggle',       micId: 'serviceNameMicBtn'       },
                    { inputId: 'serviceDescInput',        toggleWrapperId: 'serviceDescVoiceToggle',        micId: 'serviceDescMicBtn'       },
                    { inputId: 'manageServiceName',       toggleWrapperId: 'manageServiceNameVoiceToggle',  micId: 'manageServiceNameMicBtn' },
                    { inputId: 'manageServiceDescription',toggleWrapperId: 'manageServiceDescVoiceToggle',  micId: 'manageServiceDescMicBtn' }
                ];

                voiceInputs.forEach(config => {
                    const input        = document.getElementById(config.inputId);
                    const toggleWrapper = document.getElementById(config.toggleWrapperId);
                    if (!input || !toggleWrapper) return;

                    // Build mic button
                    const micBtn = document.createElement('button');
                    micBtn.type      = 'button';
                    micBtn.id        = config.micId;
                    micBtn.className = 'voice-search-mic external';
                    micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
                    micBtn.title     = 'Toggle voice input';
                    toggleWrapper.appendChild(micBtn);

                    // Build status chip
                    const status = document.createElement('span');
                    status.className = 'patient-voice-status hidden';
                    status.setAttribute('aria-hidden', 'true');
                    status.setAttribute('aria-live', 'polite');
                    toggleWrapper.appendChild(status);

                    // State
                    let recognition  = null;
                    let listening    = false;
                    let manualStop   = false;
                    let capturedText = false; // ← tracks whether any speech was recorded

                    const setStatus = (text, state) => {
                        status.textContent = text || '';
                        status.className   = state ? `patient-voice-status is-${state}` : 'patient-voice-status';
                        if (!text) status.classList.add('hidden');
                        else       status.classList.remove('hidden');
                    };

                    const setMicState = (active) => {
                        micBtn.classList.toggle('mic-active', !!active);
                        micBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
                        micBtn.innerHTML = active
                            ? '<i class="fa-solid fa-stop"></i>'
                            : '<i class="fa-solid fa-microphone"></i>';
                    };

                    // ── FIX: manual stop now checks whether speech was captured ──
                    const stopNow = () => {
                        manualStop = true;
                        listening  = false;
                        setMicState(false);

                        if (capturedText) {
                            setStatus('Voice captured.', 'success');
                            setTimeout(() => setStatus('', null), 1200);
                        } else {
                            setStatus("Didn't catch that. Try again.", 'error');
                            setTimeout(() => setStatus('', null), 2500);
                        }

                        if (recognition) {
                            try { recognition.abort(); } catch (e) {
                                try { recognition.stop(); } catch (_) {}
                            }
                        }
                    };

                    const createRecognition = () => {
                        capturedText = false; // reset per session

                        const r           = new SpeechRecognition();
                        r.lang            = 'en-US';
                        r.continuous      = false;
                        r.interimResults  = true;
                        r.maxAlternatives = 1;

                        let sawSpeech  = false;
                        let timeoutId  = null;
                        const LISTEN_TIMEOUT = 6000;

                        const clearTimeout_ = () => {
                            if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                        };

                        r.onstart = () => {
                            timeoutId = setTimeout(() => {
                                if (listening && !sawSpeech) { try { r.stop(); } catch (e) {} }
                            }, LISTEN_TIMEOUT);
                        };

                        r.onspeechend = () => { clearTimeout_(); try { r.stop(); } catch (e) {} };

                        r.onresult = (event) => {
                            let transcript = '';
                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                const result = event.results[i];
                                const chunk  = (result?.[0]?.transcript ?? '').trim();
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
                                capturedText  = true; // ← speech was actually received
                                input.value   = transcript;
                                input.dispatchEvent(new Event('input',  { bubbles: true }));
                                input.dispatchEvent(new Event('change', { bubbles: true }));
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
                            const hadSpeech = sawSpeech || capturedText;
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

                    // Click: toggle on / off
                    micBtn.addEventListener('click', () => {
                        if (listening && recognition) { stopNow(); return; }
                        recognition = createRecognition();
                        try {
                            recognition.start();
                        } catch (e) {
                            setStatus('Unable to start voice input.', 'error');
                            setTimeout(() => setStatus('', null), 2500);
                            setMicState(false);
                            listening = false;
                            return;
                        }
                        listening = true;
                        setMicState(true);
                        setStatus('Listening...', 'listening');
                    });

                    // pointerdown: immediate stop while active
                    micBtn.addEventListener('pointerdown', (ev) => {
                        if (listening && recognition) {
                            ev.preventDefault();
                            ev.stopPropagation();
                            manualStop = true;
                            try { recognition.stop(); } catch (e) {}
                        }
                    }, { passive: false });
                });
            })();

            // Char counter for description textarea
            const descInput = document.getElementById('serviceDescInput');
            const charCount = document.getElementById('serviceDescCount');
            const maxChars  = 255;

            if (descInput && charCount) {
                function updateCharCount() {
                    const currentLength = descInput.value.length;
                    charCount.textContent = `${currentLength} / ${maxChars}`;

                    if (currentLength >= maxChars) {
                        charCount.classList.remove('near-limit');
                        charCount.classList.add('at-limit');
                    } else if (currentLength >= maxChars * 0.8) {
                        charCount.classList.remove('at-limit');
                        charCount.classList.add('near-limit');
                    } else {
                        charCount.classList.remove('at-limit', 'near-limit');
                    }
                }

                updateCharCount();
                descInput.addEventListener('input', updateCharCount);
            }
        });

        window.addEventListener('resize', () => {
            applyServiceTypeView(getPreferredServiceTypeView(), false);
        });
    </script>
@endsection