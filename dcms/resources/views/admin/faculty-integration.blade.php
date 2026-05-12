@extends('layouts.admin')

@section('title', 'Faculty Integration | PUP Taguig Dental Clinic')

@section('styles')
    <style>
        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes micPulse {
            0%, 100% { box-shadow: 0 0 0 0px rgba(192, 57, 43, 0.4); }
            50%       { box-shadow: 0 0 0 8px rgba(192, 57, 43, 0); }
        }

        .faculty-page {
            min-height: 100vh;
            background: #f5f6fa;
        }

        .faculty-shell {
            max-width: 1280px;
            margin: 0 auto;
        }

        .page-banner {
            background: linear-gradient(135deg, var(--crimson-dark, #660000) 0%, var(--crimson, #8B0000) 60%, #c0392b 100%);
            padding: 1.5rem 2rem 1.6rem;
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
            margin: 0;
        }

        .page-subtitle {
            margin-top: .35rem;
            color: rgba(255, 255, 255, .82);
            font-size: .92rem;
            max-width: 720px;
        }

        .faculty-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(300px, .8fr);
            gap: 1.25rem;
            align-items: start;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, .05);
            box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
            overflow: hidden;
            animation: fadeSlideUp .4s ease both;
        }

        .card-header {
            position: relative;
            padding: 1.05rem 1.25rem;
            border-bottom: 1px solid #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background:
                radial-gradient(circle at 12% 28%, rgba(139, 0, 0, 0.08) 0, rgba(139, 0, 0, 0.08) 26px, transparent 27px),
                radial-gradient(circle at 22% 78%, rgba(192, 57, 43, 0.06) 0, rgba(192, 57, 43, 0.06) 18px, transparent 19px),
                radial-gradient(circle at 78% 24%, rgba(139, 0, 0, 0.05) 0, rgba(139, 0, 0, 0.05) 22px, transparent 23px),
                radial-gradient(circle at 92% 70%, rgba(192, 57, 43, 0.07) 0, rgba(192, 57, 43, 0.07) 24px, transparent 25px),
                linear-gradient(180deg, #fcfcfd 0%, #f8f9fb 100%);
            gap: .75rem;
            flex-wrap: wrap;
            overflow: hidden;
        }

        .card-header::before,
        .card-header::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .card-header::before {
            width: 120px;
            height: 120px;
            top: -55px;
            right: -30px;
            background: radial-gradient(circle, rgba(139, 0, 0, 0.08) 0%, rgba(139, 0, 0, 0.03) 48%, transparent 72%);
        }

        .card-header::after {
            width: 90px;
            height: 90px;
            bottom: -42px;
            left: 210px;
            background: radial-gradient(circle, rgba(192, 57, 43, 0.08) 0%, rgba(192, 57, 43, 0.03) 50%, transparent 72%);
        }

        .card-header-left,
        .entry-badge {
            position: relative;
            z-index: 1;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .card-header-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff6f6 0%, #fdeaea 100%);
            color: #8B0000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            border: 1px solid rgba(139, 0, 0, 0.08);
            box-shadow: 0 6px 16px rgba(139, 0, 0, 0.06);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1a202c;
            margin: 0;
        }

        .card-subtitle {
            font-size: .78rem;
            color: #757575;
            margin-top: 2px;
        }

        .card-body {
            padding: 1.25rem;
        }

        .status-alert {
            margin: 0 1.25rem 1rem;
            padding: .9rem 1rem;
            border-radius: 12px;
            font-size: .92rem;
            font-weight: 600;
        }

        .status-alert.success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-alert.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .section-block {
            border: 1px solid #f1ece8;
            border-radius: 16px;
            padding: 1rem;
            background: linear-gradient(180deg, #fff 0%, #fcfbfb 100%);
        }

        .section-block + .section-block {
            margin-top: 1rem;
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            background: linear-gradient(135deg, #660000 0%, #8B0000 60%, #c0392b 100%);
            padding: .9rem 1rem;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(139, 0, 0, .16);
        }

        .section-head-left {
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .section-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .14);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            flex-shrink: 0;
        }

        .section-title {
            font-size: .94rem;
            font-weight: 800;
            color: #fff;
            margin: 0;
        }

        .section-note {
            font-size: .8rem;
            color: rgba(255, 255, 255, .82);
            margin-top: .15rem;
        }

        .entry-badge {
            background: rgba(255, 244, 244, 0.92);
            color: #8B0000;
            font-size: .7rem;
            font-weight: 800;
            padding: .38rem .82rem;
            border-radius: 999px;
            border: 1px solid rgba(139, 0, 0, 0.14);
            box-shadow: 0 4px 12px rgba(139, 0, 0, 0.05);
            backdrop-filter: blur(4px);
        }

        .faculty-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            align-items: start;
        }

        .faculty-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            align-items: start;
        }

        .search-combo {
            position: relative;
        }

        .faculty-search-row {
            display: flex;
            align-items: stretch;
            gap: .65rem;
        }

        .search-input-wrap {
            --faculty-toggle-width: 46px;
            --faculty-search-gap: .65rem;
            display: grid;
            grid-template-columns: 1fr var(--faculty-toggle-width);
            gap: var(--faculty-search-gap);
            position: relative;
            align-items: stretch;
            flex: 1 1 auto;
            min-width: 0;
        }

        .faculty-search-clear-btn {
            border: none;
            background: transparent;
            color: #dc2626;
            font-size: .95rem;
            font-weight: 700;
            padding: 0 .15rem;
            line-height: 1;
            cursor: pointer;
            align-self: center;
            flex: 0 0 auto;
            transition: color .15s ease;
        }

        .faculty-search-clear-btn:hover {
            color: #991b1b;
        }

        /* ── External circular mic button (matches Add User style) ── */
        .patient-voice-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
            z-index: 30;
            pointer-events: auto;
        }

        .patient-voice-status {
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

        .patient-voice-status.hidden      { display: none; }
        .patient-voice-status.is-listening { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .patient-voice-status.is-error     { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
        .patient-voice-status.is-success   { color: #166534; border-color: #bbf7d0; background: #f0fdf4; }

        .voice-search-mic.external {
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
            flex-shrink: 0;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            position: relative;
            z-index: 31;
            pointer-events: auto;
        }

        .voice-search-mic.external:hover      { background: #374151; }
        .voice-search-mic.external i          { font-size: 12px; line-height: 1; }

        .voice-search-mic.external.mic-active {
            background: #c0392b;
            transform: scale(1.1);
            animation: micPulse 1.2s ease-in-out infinite;
        }

        /* Hide any mic injected inside the search-input-wrap by global helper */
        .search-input-wrap .voice-search-mic,
        .search-input-wrap [data-voice-trigger] { display: none !important; }

        .dropdown-toggle-btn {
            border: 1.5px solid #E0DDD8;
            border-radius: 12px;
            background: #FAFAF9;
            color: #7b7b86;
            font-size: .95rem;
            height: 100%;
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown-toggle-btn:hover {
            background: #fef2f2;
            border-color: #d8b4b4;
            color: #8b0000;
        }

        .dropdown-toggle-btn:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        .search-results {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 8px);
            background: #fff;
            border: 1px solid #eadede;
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .12);
            max-height: 290px;
            overflow-y: auto;
            display: none;
            z-index: 9999;
        }

        .search-results::-webkit-scrollbar {
            width: 6px;
        }

        .search-results::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 10px;
        }

        .search-item {
            width: 100%;
            border: 0;
            background: #fff;
            text-align: left;
            padding: .95rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f3eded;
            transition: background .15s ease;
        }

        .search-item:last-child {
            border-bottom: none;
        }

        .search-item:hover {
            background: #fff6f6;
        }

        .search-name {
            font-size: .94rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: .15rem;
        }

        .search-email {
            font-size: .84rem;
            color: #6b7280;
        }

        .search-empty {
            padding: .95rem 1rem;
            font-size: .9rem;
            color: #7c7c89;
        }

        .field-group {
            min-width: 0;
        }

        .field-group.full {
            grid-column: 1 / -1;
        }

        .field-label {
            display: block;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #8B0000;
            margin-bottom: .3rem;
        }

        .required-mark {
            color: #dc2626;
            margin-left: 2px;
        }

        .access-input,
        .access-select {
            width: 100%;
            border: 1.5px solid #e0ddd8;
            border-radius: 10px;
            padding: .6rem .85rem;
            font-size: .9rem;
            line-height: 1.3;
            background: #fff;
            color: #374151;
            outline: none;
            transition: all .15s ease;
            box-sizing: border-box;
            height: 46px;
        }

        .access-input:focus,
        .access-select:focus {
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        .access-input[readonly] {
            background: #fff;
            cursor: default;
            border-color: #bdbdbd;
        }

        .access-select {
            padding-right: 3.2rem;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%236b7280' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 18px 18px;
            cursor: pointer;
        }

        .access-select:invalid     { color: #6b7280; }
        .access-select:not(:invalid) { color: #111827; }
        .access-select option       { color: #111827; }
        .access-select option[value=""] { color: #6b7280; }

        .field-help {
            margin-top: .45rem;
            font-size: .79rem;
            color: #7c7c89;
            line-height: 1.4;
        }

        .sidebar-stack {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-card {
            padding: 1rem;
            border-radius: 16px;
            border: 1px solid #f0eaea;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .03);
        }

        .preview-card {
            position: relative;
            overflow: hidden;
        }

        .preview-card::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -30px;
            width: 110px;
            height: 110px;
            border-radius: 999px;
            background: rgba(139, 0, 0, .05);
        }

        .preview-card::after {
            content: '';
            position: absolute;
            bottom: -35px;
            left: -20px;
            width: 90px;
            height: 90px;
            border-radius: 999px;
            background: rgba(192, 57, 43, .04);
        }

        .preview-inner {
            position: relative;
            z-index: 1;
        }

        .preview-avatar {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #8B0000, #c0392b);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 8px 20px rgba(139, 0, 0, .18);
            margin-bottom: .9rem;
        }

        .preview-name {
            font-size: 1rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: .2rem;
        }

        .preview-email {
            font-size: .86rem;
            color: #6b7280;
            word-break: break-word;
            margin-bottom: 1rem;
        }

        .preview-meta {
            display: grid;
            gap: .75rem;
        }

        .preview-meta-item {
            padding: .78rem .85rem;
            border: 1px solid #f1ece8;
            border-radius: 12px;
            background: #fcfcfc;
        }

        .preview-meta-label {
            font-size: .66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #9ca3af;
            margin-bottom: .28rem;
        }

        .preview-meta-value {
            font-size: .88rem;
            color: #374151;
            font-weight: 600;
            word-break: break-word;
        }

        .tip-list {
            display: grid;
            gap: .7rem;
            margin-top: .8rem;
        }

        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
            font-size: .86rem;
            color: #4b5563;
            line-height: 1.45;
        }

        .tip-item i {
            width: 18px;
            margin-top: 2px;
            color: #8B0000;
            flex-shrink: 0;
        }

        .access-card-footer {
            padding: 1rem 1.25rem 1.2rem;
            border-top: 1px solid #f1f1f4;
            display: flex;
            justify-content: flex-end;
            gap: .8rem;
            background: #fff;
        }

        .btn-cancel,
        .btn-save {
            border: none;
            border-radius: 12px;
            height: 42px;
            padding: 0 1.2rem;
            font-size: .9rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            cursor: pointer;
            transition: all .15s ease;
        }

        .btn-cancel {
            background: #FAFAF9;
            color: #4b5563;
            border: 1.5px solid #E0DDD8;
        }

        .btn-cancel:hover { background: #f3f4f6; }

        .btn-save {
            background: #8B0000;
            color: #fff;
            box-shadow: 0 8px 20px rgba(139, 0, 0, .18);
        }

        .btn-save:hover {
            background: #760000;
            transform: translateY(-1px);
        }

        [data-theme="dark"] .card,
        [data-theme="dark"] .section-block,
        [data-theme="dark"] .info-card,
        [data-theme="dark"] .preview-meta-item {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .card-header,
        [data-theme="dark"] .access-card-footer {
            background: #0d1117 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .card-title,
        [data-theme="dark"] .section-title,
        [data-theme="dark"] .preview-name,
        [data-theme="dark"] .preview-meta-value { color: #f3f4f6 !important; }

        [data-theme="dark"] .card-subtitle,
        [data-theme="dark"] .field-help,
        [data-theme="dark"] .section-note,
        [data-theme="dark"] .preview-email,
        [data-theme="dark"] .tip-item { color: #9ca3af !important; }

        [data-theme="dark"] .access-input,
        [data-theme="dark"] .access-select,
        [data-theme="dark"] .dropdown-toggle-btn {
            background: #0d1117;
            border-color: #21262d;
            color: #f3f4f6;
        }

        [data-theme="dark"] .faculty-page { background: #0b1117 !important; }

        [data-theme="dark"] .access-input[readonly] {
            background: #0d1117 !important;
            border-color: #2b313a !important;
            color: #f3f4f6 !important;
        }

        [data-theme="dark"] .search-results {
            background: #161b22 !important;
            border-color: #21262d !important;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .35) !important;
        }

        [data-theme="dark"] .search-item {
            background: #161b22 !important;
            border-bottom-color: #21262d !important;
        }

        [data-theme="dark"] .search-item:hover { background: #1c2128 !important; }
        [data-theme="dark"] .search-name       { color: #f3f4f6 !important; }

        [data-theme="dark"] .search-email,
        [data-theme="dark"] .search-empty { color: #9ca3af !important; }

        [data-theme="dark"] .btn-cancel {
            background: #161b22 !important;
            color: #d1d5db !important;
            border-color: #2b313a !important;
        }

        [data-theme="dark"] .btn-cancel:hover { background: #1c2128 !important; }

        [data-theme="dark"] .status-alert.success {
            background: rgba(22, 101, 52, .14) !important;
            color: #86efac !important;
            border-color: #166534 !important;
        }

        [data-theme="dark"] .status-alert.error {
            background: rgba(185, 28, 28, .14) !important;
            color: #fca5a5 !important;
            border-color: #991b1b !important;
        }

        [data-theme="dark"] .preview-card::before { background: rgba(139, 0, 0, .10) !important; }
        [data-theme="dark"] .preview-card::after  { background: rgba(192, 57, 43, .08) !important; }

        [data-theme="dark"] .entry-badge {
            background: rgba(139, 0, 0, .16) !important;
            color: #fca5a5 !important;
            border-color: rgba(248, 113, 113, .18) !important;
        }

        [data-theme="dark"] .card-header-icon {
            background: linear-gradient(135deg, rgba(139, 0, 0, .18) 0%, rgba(192, 57, 43, .12) 100%) !important;
            color: #fca5a5 !important;
            border-color: rgba(248, 113, 113, .12) !important;
        }

        [data-theme="dark"] .search-results::-webkit-scrollbar-thumb { background: #374151 !important; }

        [data-theme="dark"] .patient-voice-status {
            background: rgba(13, 17, 23, .92) !important;
            border-color: #21262d !important;
            color: #d1d5db !important;
        }

        @media (max-width: 1100px) {
            .faculty-layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 767px) {
            .page-banner {
                padding: 1.05rem 1.1rem 1.15rem !important;
                margin-bottom: 1rem;
            }

            .page-title    { font-size: 1.45rem !important; }
            .page-subtitle { font-size: .84rem; }

            .card-header,
            .section-head  { align-items: flex-start; }

            .card-body { padding: 1rem; }

            .faculty-grid,
            .faculty-grid-3 { grid-template-columns: 1fr; }

            .access-input,
            .access-select {
                height: 46px;
                padding-top: .55rem;
                padding-bottom: .55rem;
                border-radius: 10px;
            }

            .access-card-footer { flex-direction: column; }

            .btn-cancel,
            .btn-save {
                width: 100%;
                justify-content: center;
            }

            .search-input-wrap {
                --faculty-toggle-width: 42px;
                --faculty-search-gap: .5rem;
                grid-template-columns: 1fr var(--faculty-toggle-width);
            }

            .faculty-search-row { gap: .5rem; }

            .voice-search-mic.external {
                width: 36px;
                height: 36px;
            }
        }
    </style>
@endsection

@section('content')
    <main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-screen faculty-page">
        <div class="faculty-shell">
            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Faculty Integration</h1>
                    </div>
                </div>
            </div>

            <div class="faculty-layout">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                            <div>
                                <h2 class="card-title">Faculty Integration Form</h2>
                            </div>
                        </div>
                        <span class="entry-badge">Faculty Setup</span>
                    </div>

                    @if (session('success'))
                        <div class="status-alert success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="status-alert error">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="status-alert error">
                            <ul style="margin: 0; padding-left: 1.2rem;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="facultyIntegrationForm" method="POST" action="{{ route('admin.faculty.store') }}">
                        @csrf

                        <div class="card-body">
                            <div class="section-block">
                                <div class="section-head">
                                    <div class="section-head-left">
                                        <div class="section-icon">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </div>
                                        <div>
                                            <h3 class="section-title">Faculty Selection</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="faculty-grid">
                                    <div class="field-group full search-combo">
                                        <label for="faculty_search" class="field-label">
                                            Select Faculty<span class="required-mark">*</span>
                                        </label>

                                        <div class="faculty-search-row">
                                            <div class="search-input-wrap">
                                                <input type="text" id="faculty_search" class="access-input"
                                                    placeholder="Search faculty by name, email, or faculty code"
                                                    autocomplete="off">

                                                <button type="button" id="toggleFacultyDropdown"
                                                    class="dropdown-toggle-btn" aria-label="Show faculty list">
                                                    <i class="fa-solid fa-chevron-down"></i>
                                                </button>
                                            </div>

                                            <button type="button" id="facultySearchClearBtn"
                                                class="faculty-search-clear-btn hidden"
                                                onclick="clearFacultySearch()">
                                                Clear
                                            </button>

                                            {{-- External circular mic button (matches Add User style) --}}
                                            <div class="patient-voice-toggle">
                                                <button type="button" id="facultyMicBtn"
                                                    class="voice-search-mic external"
                                                    aria-label="Toggle voice input"
                                                    aria-pressed="false">
                                                    <i class="fa-solid fa-microphone"></i>
                                                </button>
                                                <span id="facultyVoiceStatus"
                                                    class="patient-voice-status hidden"
                                                    aria-live="polite"></span>
                                            </div>
                                        </div>

                                        <div id="facultyResults" class="search-results"></div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="faculty_json" name="faculty_json">

                            <div class="section-block">
                                <div class="section-head">
                                    <div class="section-head-left">
                                        <div class="section-icon">
                                            <i class="fa-solid fa-id-card"></i>
                                        </div>
                                        <div>
                                            <h3 class="section-title">Synced Faculty Information</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="faculty-grid">
                                    <div class="field-group">
                                        <label for="faculty_id" class="field-label">External Faculty ID</label>
                                        <input type="text" id="faculty_id" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="faculty_code" class="field-label">Faculty Code</label>
                                        <input type="text" id="faculty_code" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="first_name" class="field-label">First Name</label>
                                        <input type="text" id="first_name" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="middle_name" class="field-label">Middle Name</label>
                                        <input type="text" id="middle_name" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="last_name" class="field-label">Last Name</label>
                                        <input type="text" id="last_name" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="suffix_name" class="field-label">Suffix Name</label>
                                        <input type="text" id="suffix_name" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="faculty_type" class="field-label">Faculty Type</label>
                                        <input type="text" id="faculty_type" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="department" class="field-label">Department</label>
                                        <input type="text" id="department" class="access-input" readonly>
                                    </div>

                                    <div class="field-group full">
                                        <label for="email" class="field-label">Email</label>
                                        <input type="email" id="email" class="access-input" readonly>
                                    </div>
                                </div>

                                <div class="faculty-grid-3" style="margin-top: 1rem;">
                                    <div class="field-group">
                                        <label for="birthday" class="field-label">Birthday</label>
                                        <input type="text" id="birthday" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="gender" class="field-label">Gender</label>
                                        <input type="text" id="gender" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="zipcode" class="field-label">Zipcode</label>
                                        <input type="text" id="zipcode" class="access-input" readonly>
                                    </div>
                                </div>

                                <div class="faculty-grid-3" style="margin-top: 1rem;">
                                    <div class="field-group">
                                        <label for="house_num" class="field-label">House / Unit No.</label>
                                        <input type="text" id="house_num" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="street" class="field-label">Street</label>
                                        <input type="text" id="street" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="barangay" class="field-label">Barangay</label>
                                        <input type="text" id="barangay" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="city" class="field-label">City</label>
                                        <input type="text" id="city" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="province" class="field-label">Province</label>
                                        <input type="text" id="province" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="country" class="field-label">Country</label>
                                        <input type="text" id="country" class="access-input" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="section-block">
                                <div class="section-head">
                                    <div class="section-head-left">
                                        <div class="section-icon">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </div>
                                        <div>
                                            <h3 class="section-title">Access Configuration</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="faculty-grid">
                                    <div class="field-group">
                                        <label for="cms_role" class="field-label">
                                            CMS Role<span class="required-mark">*</span>
                                        </label>
                                        <select name="cms_role" id="cms_role" class="access-select" required>
                                            <option value="" disabled selected hidden>Select CMS Role</option>
                                            <option value="patient">Patient</option>
                                            <option value="admin">Admin</option>
                                            <option value="dentist">Dentist</option>
                                        </select>
                                    </div>

                                    <div class="field-group">
                                        <label for="account_status" class="field-label">
                                            Account Status<span class="required-mark">*</span>
                                        </label>
                                        <select name="account_status" id="account_status" class="access-select" required>
                                            <option value="" disabled selected hidden>Select Status</option>
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="access-card-footer">
                            <button type="button" class="btn-cancel" id="cancelFacultyBtn">
                                <i class="fa-solid fa-arrow-left"></i>
                                Cancel
                            </button>

                            <button type="submit" class="btn-save">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Save Faculty
                            </button>
                        </div>
                    </form>
                </div>

                <div class="sidebar-stack">
                    <div class="info-card preview-card">
                        <div class="preview-inner">
                            <div class="preview-avatar">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>

                            <div class="preview-name" id="preview_name">No faculty selected</div>
                            <div class="preview-email" id="preview_email">Select a faculty record to preview the synced information.</div>

                            <div class="preview-meta">
                                <div class="preview-meta-item">
                                    <div class="preview-meta-label">Faculty Code</div>
                                    <div class="preview-meta-value" id="preview_code">—</div>
                                </div>

                                <div class="preview-meta-item">
                                    <div class="preview-meta-label">Department</div>
                                    <div class="preview-meta-value" id="preview_department">—</div>
                                </div>

                                <div class="preview-meta-item">
                                    <div class="preview-meta-label">Faculty Type</div>
                                    <div class="preview-meta-value" id="preview_type">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="section-head" style="margin-bottom: .3rem;">
                            <div class="section-head-left">
                                <div class="section-icon">
                                    <i class="fa-solid fa-circle-info"></i>
                                </div>
                                <div>
                                    <h3 class="section-title">Quick Notes</h3>
                                    <div class="section-note">Small guidance for cleaner admin workflow.</div>
                                </div>
                            </div>
                        </div>

                        <div class="tip-list">
                            <div class="tip-item">
                                <i class="fa-solid fa-check"></i>
                                <span>Select from the dropdown or filtered search results to keep faculty information accurately synced.</span>
                            </div>
                            <div class="tip-item">
                                <i class="fa-solid fa-user-gear"></i>
                                <span>Review the faculty type, department, and email before assigning the CMS role.</span>
                            </div>
                            <div class="tip-item">
                                <i class="fa-solid fa-shield"></i>
                                <span>Use <strong>Inactive</strong> status when the record should remain stored but account access must be disabled.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ─── Element refs ───────────────────────────────────────────────────────────
            const searchInput       = document.getElementById('faculty_search');
            const toggleButton      = document.getElementById('toggleFacultyDropdown');
            const clearSearchButton = document.getElementById('facultySearchClearBtn');
            const resultsBox        = document.getElementById('facultyResults');

            const facultyJson  = document.getElementById('faculty_json');
            const facultyId    = document.getElementById('faculty_id');
            const firstName    = document.getElementById('first_name');
            const middleName   = document.getElementById('middle_name');
            const lastName     = document.getElementById('last_name');
            const suffixName   = document.getElementById('suffix_name');
            const facultyCode  = document.getElementById('faculty_code');
            const facultyType  = document.getElementById('faculty_type');
            const department   = document.getElementById('department');
            const email        = document.getElementById('email');
            const birthday     = document.getElementById('birthday');
            const gender       = document.getElementById('gender');
            const houseNum     = document.getElementById('house_num');
            const street       = document.getElementById('street');
            const barangay     = document.getElementById('barangay');
            const city         = document.getElementById('city');
            const province     = document.getElementById('province');
            const country      = document.getElementById('country');
            const zipcode      = document.getElementById('zipcode');

            const previewName       = document.getElementById('preview_name');
            const previewEmail      = document.getElementById('preview_email');
            const previewCode       = document.getElementById('preview_code');
            const previewDepartment = document.getElementById('preview_department');
            const previewType       = document.getElementById('preview_type');
            const cancelFacultyBtn  = document.getElementById('cancelFacultyBtn');

            let faculties      = [];
            let dropdownOpen   = false;
            let isDropdownMode = false;

            // ─── Helpers ────────────────────────────────────────────────────────────────
            function toggleFacultySearchClear(input) {
                if (!clearSearchButton) return;
                (input.value || '').trim().length > 0
                    ? clearSearchButton.classList.remove('hidden')
                    : clearSearchButton.classList.add('hidden');
            }

            window.clearFacultySearch = function () {
                if (!searchInput) return;
                searchInput.value = '';
                clearFields();
                hideResults();
                toggleFacultySearchClear(searchInput);
                searchInput.focus();
            };

            function showResults() {
                resultsBox.style.display = 'block';
                dropdownOpen = true;
            }

            function hideResults() {
                resultsBox.style.display = 'none';
                resultsBox.innerHTML     = '';
                dropdownOpen   = false;
                isDropdownMode = false;
            }

            function resetPreview() {
                previewName.textContent       = 'No faculty selected';
                previewEmail.textContent      = 'Select a faculty record to preview the synced information.';
                previewCode.textContent       = '—';
                previewDepartment.textContent = '—';
                previewType.textContent       = '—';
            }

            function clearFields() {
                facultyJson.value = '';
                facultyId.value = firstName.value = middleName.value = lastName.value =
                    suffixName.value = facultyCode.value = facultyType.value =
                    department.value = email.value = birthday.value = gender.value =
                    houseNum.value = street.value = barangay.value = city.value =
                    province.value = country.value = zipcode.value = '';
                resetPreview();
            }

            function resetFacultyForm() {
                searchInput.value = '';
                clearFields();
                document.getElementById('cms_role').value      = '';
                document.getElementById('account_status').value = '';
                hideResults();
                toggleFacultySearchClear(searchInput);
            }

            function fillFaculty(faculty) {
                const profile  = faculty.profile  ?? {};
                const addr     = profile.address  ?? {};

                facultyJson.value  = JSON.stringify(faculty);
                searchInput.value  = `${faculty.first_name ?? ''} ${faculty.last_name ?? ''}`.trim();

                facultyId.value    = faculty.faculty_id   ?? '';
                firstName.value    = faculty.first_name   ?? '';
                middleName.value   = faculty.middle_name  ?? '';
                lastName.value     = faculty.last_name    ?? '';
                suffixName.value   = faculty.suffix_name  ?? '';
                facultyCode.value  = faculty.faculty_code ?? '';
                facultyType.value  = faculty.faculty_type ?? '';
                department.value   = faculty.department   ?? '';
                email.value        = faculty.email        ?? '';
                birthday.value     = profile.birthday     ?? '';
                gender.value       = profile.gender       ?? '';
                houseNum.value     = addr.house_num       ?? '';
                street.value       = addr.street          ?? '';
                barangay.value     = addr.barangay        ?? '';
                city.value         = addr.city            ?? '';
                province.value     = addr.province        ?? '';
                country.value      = addr.country         ?? '';
                zipcode.value      = addr.zipcode         ?? '';

                previewName.textContent       = `${faculty.first_name ?? ''} ${faculty.last_name ?? ''}`.trim() || 'Selected faculty';
                previewEmail.textContent      = faculty.email        ?? 'No email available';
                previewCode.textContent       = faculty.faculty_code ?? '—';
                previewDepartment.textContent = faculty.department   ?? '—';
                previewType.textContent       = faculty.faculty_type ?? '—';

                toggleFacultySearchClear(searchInput);
                hideResults();
            }

            function renderNoResults(message = 'No results found.') {
                resultsBox.innerHTML = `<div class="search-empty">${message}</div>`;
                showResults();
            }

            function renderResults(list) {
                resultsBox.innerHTML = '';

                if (!Array.isArray(list) || list.length === 0) {
                    renderNoResults();
                    return;
                }

                list.forEach(faculty => {
                    const item     = document.createElement('button');
                    item.type      = 'button';
                    item.className = 'search-item';

                    const fullName = `${faculty.first_name ?? ''} ${faculty.middle_name ?? ''} ${faculty.last_name ?? ''}`
                        .replace(/\s+/g, ' ').trim();

                    item.innerHTML = `
                        <div class="search-name">${fullName || 'Unnamed Faculty'}</div>
                        <div class="search-email">${faculty.email ?? faculty.faculty_code ?? ''}</div>
                    `;

                    item.addEventListener('click', function (e) {
                        e.preventDefault();
                        fillFaculty(faculty);
                    });

                    resultsBox.appendChild(item);
                });

                showResults();
            }

            function filterFaculties(query) {
                const q = query.trim().toLowerCase();
                if (!q) return [];

                return faculties.filter(f => {
                    const name = `${f.first_name ?? ''} ${f.middle_name ?? ''} ${f.last_name ?? ''}`.toLowerCase();
                    return name.includes(q) ||
                        (f.email        ?? '').toLowerCase().includes(q) ||
                        (f.faculty_code ?? '').toLowerCase().includes(q) ||
                        (f.department   ?? '').toLowerCase().includes(q);
                });
            }

            // ─── Load faculty list ──────────────────────────────────────────────────────
            fetch('/faculties', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(data => { faculties = Array.isArray(data) ? data : []; })
            .catch(err  => { console.error('Failed to load faculties:', err); faculties = []; });

            // ─── Search input ───────────────────────────────────────────────────────────
            searchInput.addEventListener('input', function () {
                const query = this.value.trim();
                toggleFacultySearchClear(this);
                clearFields();
                isDropdownMode = false;

                if (!query) { hideResults(); return; }

                const filtered = filterFaculties(query);
                filtered.length ? renderResults(filtered) : renderNoResults('No results found.');
            });

            // ─── Dropdown toggle ────────────────────────────────────────────────────────
            toggleButton.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (dropdownOpen && isDropdownMode) { hideResults(); return; }
                isDropdownMode = true;
                faculties.length ? renderResults(faculties) : renderNoResults('No faculty records available.');
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') hideResults();
            });

            document.addEventListener('click', function (e) {
                const inside = searchInput.contains(e.target) ||
                               toggleButton.contains(e.target) ||
                               resultsBox.contains(e.target);
                if (!inside) hideResults();
            });

            if (cancelFacultyBtn) cancelFacultyBtn.addEventListener('click', resetFacultyForm);

            toggleFacultySearchClear(searchInput);
            resetPreview();

            // ─── Voice input for faculty search (external circular button) ──────────────
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const micBtn = document.getElementById('facultyMicBtn');
                const status = document.getElementById('facultyVoiceStatus');

                if (!micBtn || !status || !SpeechRecognition) {
                    if (micBtn) micBtn.disabled = true;
                    return;
                }

                let recognition = null;
                let listening   = false;
                let manualStop  = false;

                const setStatus = (text, state) => {
                    status.textContent = text;
                    status.className   = 'patient-voice-status' + (state ? ' is-' + state : '');
                    text ? status.classList.remove('hidden') : status.classList.add('hidden');
                };

                const hideStatus = (delay) => setTimeout(() => status.classList.add('hidden'), delay || 0);

                const setMicState = (isActive) => {
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                };

                const stopNow = () => {
                    manualStop = true;
                    listening  = false;
                    setMicState(false);
                    setStatus('Voice captured.', 'success');
                    hideStatus(1200);
                    if (recognition) { try { recognition.abort(); } catch (e) {} }
                };

                const createRecognition = () => {
                    const r = new SpeechRecognition();
                    r.lang            = 'en-US';
                    r.continuous      = false;
                    r.interimResults  = true;
                    r.maxAlternatives = 1;

                    let sawSpeech = false;
                    let timeoutId = null;

                    r.onstart = () => {
                        timeoutId = setTimeout(() => {
                            if (listening && !sawSpeech) { try { r.stop(); } catch (e) {} }
                        }, 6000);
                    };

                    r.onspeechend = () => { clearTimeout(timeoutId); try { r.stop(); } catch (e) {} };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const res   = event.results[i];
                            const chunk = (res && res[0] ? res[0].transcript : '').trim();
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (res.isFinal) transcript = (transcript + ' ' + chunk).trim();
                            else if (!transcript) transcript = chunk;
                        }
                        if (transcript) {
                            clearTimeout(timeoutId);
                            searchInput.value = transcript;
                            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                            setStatus('Listening...', 'listening');
                        }
                    };

                    r.onerror = () => {
                        clearTimeout(timeoutId);
                        listening = false;
                        if (manualStop) { manualStop = false; return; }
                        setMicState(false);
                        setStatus("Didn't catch that. Try again.", 'error');
                        hideStatus(2500);
                    };

                    r.onend = () => {
                        clearTimeout(timeoutId);
                        if (manualStop) { manualStop = false; listening = false; setMicState(false); return; }
                        const hadSpeech = sawSpeech || !!searchInput.value.trim();
                        listening = false;
                        setMicState(false);
                        hadSpeech
                            ? (setStatus('Voice captured.', 'success'), hideStatus(2200))
                            : (setStatus("Didn't catch that. Try again.", 'error'), hideStatus(2500));
                    };

                    return r;
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) { stopNow(); return; }

                    recognition = createRecognition();
                    try {
                        recognition.start();
                    } catch (e) {
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
            })();

        });
    </script>
@endsection