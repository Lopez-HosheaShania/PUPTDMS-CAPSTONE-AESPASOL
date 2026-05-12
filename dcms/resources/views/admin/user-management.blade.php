@extends('layouts.admin')

@section('title', 'User Management | PUP Taguig Dental Clinic')

@section('body-class', 'bg-[#f4f5f7] dark:bg-[#000D1A]')

@section('styles')

    <style>
        :root {
            --um-primary: #8B0000;
            --um-primary-dark: #6b0000;
            --um-primary-soft: #fff1f1;
            --um-ink: #14213d;
            --um-muted: #6b7280;
            --um-border: #ebe7e2;
            --um-surface: #ffffff;
            --um-surface-soft: #fbfaf8;
        }

        .um-hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 1.6rem 1.6rem;
            margin-bottom: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 28%),
                linear-gradient(135deg, #650000 0%, #8B0000 55%, #b91c1c 100%);
            box-shadow: 0 18px 40px rgba(139, 0, 0, .18);
        }

        .um-hero-pattern {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px),
                linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: linear-gradient(to bottom right, rgba(0, 0, 0, .9), transparent 75%);
            opacity: .35;
        }

        .um-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .um-hero-copy {
            max-width: 760px;
        }

        .um-hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem .75rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #fff;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .14);
            backdrop-filter: blur(8px);
            margin-bottom: .9rem;
        }

        .um-hero-title {
            font-size: clamp(1.7rem, 2.4vw, 2.45rem);
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: -.03em;
            color: #fff;
            margin: 0;
        }

        .um-hero-subtitle {
            margin-top: .65rem;
            max-width: 720px;
            color: rgba(255, 255, 255, .82);
            font-size: .95rem;
            line-height: 1.65;
        }

        .um-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            margin-top: 1rem;
        }

        .um-hero-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem .85rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            color: #fff;
            font-size: .78rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, .12);
            backdrop-filter: blur(8px);
        }

        .um-hero-btn {
            min-height: 48px;
            padding: 0 1.1rem;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 14px;
            background: #fff;
            color: var(--um-primary);
            font-weight: 800;
            font-size: .92rem;
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            box-shadow: 0 10px 24px rgba(0, 0, 0, .12);
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .um-hero-btn:hover {
            transform: translateY(-1px);
            background: #fff8f8;
            box-shadow: 0 14px 26px rgba(0, 0, 0, .16);
        }

        .um-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .um-stat-card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #fcfbfa 100%);
            border: 1px solid #ede8e2;
            border-radius: 22px;
            padding: 1.15rem 1.15rem 1.05rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .um-stat-card::after {
            content: '';
            position: absolute;
            right: -22px;
            top: -22px;
            width: 110px;
            height: 110px;
            border-radius: 999px;
            opacity: .08;
        }

        .um-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 30px rgba(15, 23, 42, .08);
            border-color: #e5ddd5;
        }

        .um-stat-card--users::after {
            background: #8B0000;
        }

        .um-stat-card--active::after {
            background: #22c55e;
        }

        .um-stat-card--inactive::after {
            background: #64748b;
        }

        .um-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .um-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, .12);
        }

        .um-stat-card--users .um-stat-icon {
            background: linear-gradient(135deg, #8B0000, #5f0000);
        }

        .um-stat-card--active .um-stat-icon {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }

        .um-stat-card--inactive .um-stat-icon {
            background: linear-gradient(135deg, #94a3b8, #64748b);
        }

        .um-stat-trend {
            font-size: .72rem;
            font-weight: 700;
            color: #94a3b8;
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 999px;
            padding: .32rem .6rem;
        }

        .um-stat-label {
            font-size: .76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin-bottom: .35rem;
        }

        .um-stat-value {
            font-size: clamp(2rem, 3vw, 2.5rem);
            line-height: 1;
            font-weight: 900;
            letter-spacing: -.04em;
            color: #14213d;
            margin: 0;
        }

        .um-stat-caption {
            margin-top: .45rem;
            font-size: .82rem;
            color: #94a3b8;
        }

        .um-panel {
            background: linear-gradient(180deg, #ffffff 0%, #fffdfb 100%);
            border: 1px solid #ebe7e2;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .05);
        }

        .um-panel-header {
            padding: 1.1rem 1.2rem 1rem;
            background: linear-gradient(180deg, #fff 0%, #fbfaf8 100%);
            border-bottom: 1px solid #f1ece7;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .um-panel-title-wrap {
            display: flex;
            align-items: flex-start;
            gap: .9rem;
        }

        .um-panel-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #8B0000, #6b0000);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 18px rgba(139, 0, 0, .18);
            flex-shrink: 0;
        }

        .um-panel-title {
            font-size: 1rem;
            font-weight: 900;
            color: #14213d;
            margin: 0;
            letter-spacing: -.02em;
        }

        .um-panel-subtitle {
            margin-top: .2rem;
            font-size: .83rem;
            color: #8b95a7;
        }

        .um-panel-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 22px;
            padding: 0 .55rem;
            border-radius: 999px;
            background: #8B0000;
            color: #fff;
            font-size: .68rem;
            font-weight: 800;
        }

        .search-wrap {
            background: #fff;
            border: 1.5px solid #e7e2dc;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6);
        }

        .search-wrap:focus-within {
            border-color: #c11b1b;
            box-shadow: 0 0 0 4px rgba(139, 0, 0, .08);
        }

        .um-role-tabs {
            background: #f8f5f1;
            border: 1px solid #ebe5de;
            border-radius: 14px;
            padding: 4px;
            gap: 4px;
        }

        .tab-btn {
            min-height: 36px;
            padding: 7px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
        }

        #statusFilter {
            border-radius: 14px !important;
            border-color: #e7e2dc !important;
            background: #fff !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6);
        }

        .um-view-toggle {
            height: 44px;
            border-radius: 14px;
            border-color: #e7e2dc;
            background: #fff;
        }

        .um-view-toggle-btn {
            width: 36px;
            height: 36px;
            border-radius: 11px;
        }

        #umListView thead {
            background: #faf8f6;
        }

        #umListView thead th {
            font-size: .7rem;
            letter-spacing: .08em;
            color: #8B0000;
            font-weight: 800;
        }

        .user-table-row {
            transition: background .18s ease, transform .18s ease;
        }

        .user-table-row:hover {
            background: #fff8f8;
        }

        .user-table-row td {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .um-grid-card {
            border-radius: 18px;
            border-color: #ece5dd;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
        }

        @media (max-width: 1024px) {
            .um-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .um-hero {
                border-radius: 20px;
                padding: 1.15rem 1rem;
                margin-bottom: 1rem;
            }

            .um-hero-content {
                align-items: stretch;
                gap: 1rem;
            }

            .um-hero-title {
                font-size: 1.65rem;
            }

            .um-hero-subtitle {
                font-size: .84rem;
                line-height: 1.55;
            }

            .um-hero-meta {
                gap: .5rem;
            }

            .um-hero-meta-pill {
                font-size: .7rem;
                padding: .48rem .7rem;
            }

            .um-hero-actions {
                width: 100%;
            }

            .um-hero-btn {
                width: 100%;
                justify-content: center;
                min-height: 46px;
                border-radius: 13px;
            }

            .um-stats-grid {
                grid-template-columns: 1fr;
                gap: .75rem;
            }

            .um-stat-card {
                padding: 1rem;
                border-radius: 18px;
            }

            .um-stat-top {
                margin-bottom: .8rem;
            }

            .um-stat-icon {
                width: 42px;
                height: 42px;
                border-radius: 14px;
            }

            .um-stat-value {
                font-size: 2.1rem;
            }

            .um-panel {
                border-radius: 20px;
            }

            .um-panel-header {
                padding: 1rem;
                gap: .85rem;
            }

            .um-panel-title-wrap {
                gap: .75rem;
            }

            .um-panel-icon {
                width: 38px;
                height: 38px;
                border-radius: 12px;
            }

            .um-panel-title {
                font-size: .95rem;
            }

            .um-panel-subtitle {
                font-size: .76rem;
            }
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
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

        .page-banner-date {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .78rem;
            color: rgba(255, 255, 255, .82);
            margin-bottom: .35rem;
        }

        .page-banner-date i {
            color: #fff;
            font-size: .75rem;
        }

        .um-view-toggle {
            display: inline-flex;
            align-items: center;
            background: #FAFAF9;
            border: 1.5px solid #E0DDD8;
            border-radius: 12px;
            padding: 3px;
            gap: 3px;
            height: 42px;
        }

        .um-view-toggle-btn {
            width: 34px;
            height: 34px;
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

        .um-view-toggle-btn:hover {
            background: #f3f4f6;
            color: #8B0000;
        }

        .um-view-toggle-btn.active {
            background: #8B0000;
            color: #fff;
            box-shadow: 0 2px 8px rgba(139, 0, 0, .15);
        }

        .um-view[hidden] {
            display: none !important;
        }

        .um-grid-wrap {
            padding: 1rem;
        }

        .um-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .um-grid-card {
            background: #fff;
            border: 1px solid #f0eaea;
            border-radius: 16px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: .85rem;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            min-width: 0;
        }

        .um-grid-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, .06);
            border-color: #ead6d6;
        }

        .um-grid-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .um-grid-number {
            font-size: .72rem;
            font-weight: 800;
            color: #8B0000;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .um-grid-meta {
            display: grid;
            gap: .65rem;
        }

        .um-grid-field {
            min-width: 0;
        }

        .um-grid-label {
            font-size: .64rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .28rem;
        }

        .um-grid-value {
            font-size: .8rem;
            color: #374151;
            line-height: 1.35;
            min-width: 0;
            word-break: break-word;
        }

        @media (max-width: 767px) {
            .page-banner {
                border-radius: 14px;
                padding: 1.1rem 1.1rem 1.4rem;
            }

            .page-title {
                font-size: 1.45rem;
            }

            .page-banner-inner {
                flex-direction: column;
                gap: .75rem;
            }
        }

        body,
        main,
        footer {
            transition: background-color .3s ease, color .3s ease;
        }

        [data-theme="dark"] body {
            background-color: #000D1A;
            color: #E5E7EB;
        }

        [data-theme="dark"] .bg-white {
            background-color: #161b22 !important;
        }

        [data-theme="dark"] .text-\[\#333333\] {
            color: #E5E7EB !important;
        }

        [data-theme="dark"] .sl-card,
        [data-theme="dark"] .sl-stat {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .sl-page-title {
            color: #f3f4f6;
        }

        [data-theme="dark"] .sl-toolbar-title {
            color: #f3f4f6;
        }

        [data-theme="dark"] .sl-table thead tr {
            background: #0d1117;
        }

        [data-theme="dark"] .sl-table tbody tr:hover {
            background: #1c2128;
        }

        [data-theme="dark"] .sl-table tbody td {
            color: #d1d5db;
        }

        [data-theme="dark"] .sl-username,
        [data-theme="dark"] .sl-date-day {
            color: #e5e7eb;
        }

        [data-theme="dark"] .sl-pagebar {
            background: #0d1117;
            border-color: #21262d;
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
            min-width: 0;
            flex-shrink: 1;
        }

        .search-wrap:focus-within {
            border-color: var(--crimson);
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        .search-wrap i {
            color: var(--crimson);
            font-size: 13px;
            flex-shrink: 0;
        }

        .search-wrap input {
            border: none;
            background: none;
            outline: none;
            font-size: 13px;
            color: #333;
            width: 100%;
        }

        .search-wrap input::placeholder {
            color: #B0ABA6;
        }

        .um-search-row {
            display: flex;
            align-items: center;
            gap: .5rem;
            width: 100%;
        }

        .um-search-row .search-wrap {
            flex: 1 1 auto;
            min-width: 0;
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

        .search-clear-btn:hover {
            color: #991b1b;
        }

        .search-clear-btn.hidden {
            display: none;
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

        .patient-voice-status.hidden {
            display: none;
        }

        .patient-voice-status.is-listening {
            color: #1d4ed8;
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .patient-voice-status.is-error {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fef2f2;
        }

        .patient-voice-status.is-success {
            color: #166534;
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        @keyframes micPulse {
            0%, 100% { box-shadow: 0 0 0 0px rgba(192, 57, 43, 0.4); }
            50%       { box-shadow: 0 0 0 8px rgba(192, 57, 43, 0); }
        }

        /* External circular mic button */
        .voice-search-mic.external {
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
            z-index: 31;
            pointer-events: auto;
        }

        .voice-search-mic.external:hover {
            background: #374151;
        }

        .voice-search-mic.external i {
            font-size: 12px;
            line-height: 1;
        }

        .voice-search-mic.external.mic-active {
            background: #c0392b;
            transform: scale(1.1);
            animation: micPulse 1.2s ease-in-out infinite;
        }

        /* Hide any mic injected inside the search-wrap by global helper */
        .search-wrap .voice-search-mic,
        .search-wrap [data-voice-trigger] {
            display: none !important;
        }

        .tab-btn {
            padding: 6px 14px;
            border-radius: 7px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #9A9490;
            transition: all .2s;
            white-space: nowrap;
        }

        .tab-btn.active {
            background: var(--crimson);
            color: #fff;
            box-shadow: 0 2px 8px rgba(139, 0, 0, .3);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.4s ease-out forwards;
        }

        #toastContainer {
            position: fixed !important;
            top: 80px !important;
            right: 16px !important;
            z-index: 99999 !important;
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
            pointer-events: none;
            width: 340px;
        }

        @media (max-width: 640px) {
            #toastContainer {
                top: 80px !important;
                right: 10px !important;
                left: 10px !important;
                width: auto !important;
                align-items: stretch;
            }

            #toastContainer>div {
                max-width: 100% !important;
                width: 100% !important;
            }
        }

        @media (max-width: 640px) {
            .modal-overlay {
                padding: 10px !important;
                align-items: center !important;
            }

            .modal-box {
                width: 100% !important;
                max-width: 100% !important;
                max-height: calc(100vh - 32px) !important;
                border-radius: 18px !important;
                overflow: hidden !important;
            }

            .modal-box>div[class*="p-6"],
            .modal-box>div[class*="p-8"] {
                padding: 14px !important;
            }

            .modal-box .sticky.top-0,
            .modal-box .border-b {
                padding: 14px 14px 10px !important;
            }

            .modal-box .sticky.bottom-0,
            .modal-box .border-t {
                padding: 10px 14px 14px !important;
            }

            .modal-box h2,
            .modal-box h3 {
                font-size: 1.1rem !important;
                line-height: 1.3 !important;
            }

            .modal-box p {
                font-size: 0.8rem !important;
                line-height: 1.4 !important;
            }

            .modal-box .grid,
            .modal-box .md\\:grid-cols-2,
            .modal-box .lg\\:grid-cols-2 {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }

            .modal-box input,
            .modal-box select,
            .modal-box textarea {
                min-height: 44px !important;
                font-size: 0.95rem !important;
                padding-top: 10px !important;
                padding-bottom: 10px !important;
            }

            .modal-box textarea {
                min-height: 90px !important;
            }

            .modal-box label {
                font-size: 0.78rem !important;
                margin-bottom: 6px !important;
            }

            .modal-box .flex.justify-end,
            .modal-box .flex.items-center.justify-end {
                gap: 8px !important;
                flex-wrap: nowrap !important;
            }

            .modal-box .flex.justify-end>button,
            .modal-box .flex.items-center.justify-end>button,
            .modal-box .flex.justify-end>a,
            .modal-box .flex.items-center.justify-end>a {
                min-height: 42px !important;
                padding: 0 14px !important;
                font-size: 0.9rem !important;
                border-radius: 12px !important;
            }

            .modal-box .w-10.h-10,
            .modal-box .w-12.h-12 {
                width: 40px !important;
                height: 40px !important;
            }

            .modal-box .text-2xl,
            .modal-box .text-xl {
                font-size: 1.1rem !important;
            }
        }

        @media (max-width: 767px) {
            #mainContent {
                padding-bottom: 2rem !important;
            }

            .sl-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .sl-table thead th:nth-child(6),
            .sl-table tbody td:nth-child(6),
            .sl-table thead th:nth-child(7),
            .sl-table tbody td:nth-child(7) {
                display: none;
            }

            .user-table-row td {
                padding-top: 0.65rem;
                padding-bottom: 0.65rem;
            }

            .action-btn {
                padding: 5px 6px;
            }

            #umListView {
                display: none !important;
            }

            #umGridView {
                display: block !important;
            }

            #umViewToggle {
                display: none !important;
            }

            .um-grid {
                grid-template-columns: 1fr;
            }

            .um-grid-wrap {
                padding: .85rem;
            }
        }

        [data-theme="dark"] .theme-toggle-container {
            background: #1F1F1F;
            border-color: #2A2A2A;
        }

        [data-theme="dark"] .theme-option {
            color: #6B7280;
        }

        [data-theme="dark"] .theme-option.active {
            color: #F3F4F6;
        }

        [data-theme="dark"] .theme-indicator {
            background: #2A2A2A;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .3);
        }

        [data-theme="dark"] .nav-sep,
        [data-theme="dark"] .sidebar-bottom {
            border-color: #21262d;
        }

        [data-theme="dark"] .sl-card,
        [data-theme="dark"] .sl-stat {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .sl-page-title {
            color: #f3f4f6;
        }

        [data-theme="dark"] .sl-toolbar-title {
            color: #f3f4f6;
        }

        [data-theme="dark"] .sl-table thead tr {
            background: #0d1117;
        }

        [data-theme="dark"] .sl-table tbody tr:hover {
            background: #1c2128;
        }

        [data-theme="dark"] .sl-table tbody td {
            color: #d1d5db;
        }

        [data-theme="dark"] .sl-username,
        [data-theme="dark"] .sl-date-day {
            color: #e5e7eb;
        }

        [data-theme="dark"] .sl-pagebar {
            background: #0d1117;
            border-color: #21262d;
        }

        .modal-box.um-user-modal {
            max-width: 980px !important;
            width: min(980px, calc(100vw - 32px)) !important;
        }

        @media (min-width: 1024px) {
            .um-user-modal .lg\:grid-cols-2 {
                align-items: start;
            }
        }

        @media (max-width: 640px) {
            .um-user-modal {
                max-width: 100% !important;
            }

            .um-user-modal .rounded-2xl {
                border-radius: 16px !important;
            }

            .um-user-modal .px-6,
            .um-user-modal .p-6 {
                padding-left: 14px !important;
                padding-right: 14px !important;
            }

            .um-user-modal .py-5 {
                padding-top: 14px !important;
                padding-bottom: 12px !important;
            }

            .um-user-modal .p-4 {
                padding: 14px !important;
            }
        }

        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .1);
        }

        .user-table-row {
            transition: background .15s;
        }

        .user-table-row:hover {
            background: #fef9f9;
        }

        .badge-role {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 99px;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-inactive {
            background: #f3f4f6;
            color: #6b7280;
        }

        .action-btn {
            padding: 6px 8px;
            border-radius: 7px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            transition: all .15s;
        }

        .action-btn:hover {
            transform: scale(1.08);
        }

        .btn-edit {
            background: #eff6ff;
            color: #2563eb;
        }

        .btn-edit:hover {
            background: #dbeafe;
        }

        .btn-toggle-on {
            background: #fef3c7;
            color: #b45309;
        }

        .btn-toggle-on:hover {
            background: #fde68a;
        }

        .btn-toggle-off {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-toggle-off:hover {
            background: #a7f3d0;
        }

        .btn-reset {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .btn-reset:hover {
            background: #ede9fe;
        }

        .modal-overlay {
            position: fixed !important;
            inset: 0 !important;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.6) !important;
            z-index: 99999 !important;
            backdrop-filter: blur(4px);
            pointer-events: auto !important;
            padding: 16px;
        }

        .modal-overlay.open {
            display: flex !important;
        }

        .modal-box {
            position: relative;
            width: 100%;
            max-width: 560px;
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
            z-index: 20001;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.22);
            border: 1px solid rgba(229, 231, 235, 0.9);
            transform: scale(.96) translateY(14px);
            opacity: 0;
            transition: transform .2s ease, opacity .2s ease;
        }

        .modal-overlay,
        .modal-overlay * {
            box-sizing: border-box;
        }

        body.modal-open {
            overflow: hidden !important;
        }

        .modal-overlay.open .modal-box {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .modal-sm {
            max-width: 420px;
        }

        .field-input {
            transition: border-color .15s, box-shadow .15s;
        }

        .field-input:focus {
            outline: none;
            border-color: #8B0000 !important;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        .page-btn {
            min-width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #fff;
            cursor: pointer;
            font-size: .78rem;
            font-weight: 600;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 8px;
            transition: all .15s;
        }

        .page-btn:hover {
            background: #fef2f2;
            border-color: #8B0000;
            color: #8B0000;
        }

        .page-btn.active {
            background: #8B0000;
            border-color: #8B0000;
            color: #fff;
        }

        .page-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
            pointer-events: none;
        }

        .flash-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: .75rem 1rem;
            border-radius: 12px;
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }

        [data-theme="dark"] .text-gray-800 {
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .border-gray-100 {
            border-color: #21262d !important;
        }

        [data-theme="dark"] .bg-gray-50 {
            background-color: #0d1117 !important;
        }

        [data-theme="dark"] .bg-\[\#f5f5f5\] {
            background-color: #000D1A !important;
        }

        [data-theme="dark"] .user-table-row:hover {
            background: #0d1117;
        }

        [data-theme="dark"] .modal-box {
            background: #161b22;
        }

        [data-theme="dark"] .modal-box input,
        [data-theme="dark"] .modal-box select {
            background: #0d1117 !important;
            border-color: #21262d !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] table thead tr {
            background: #0d1117 !important;
        }

        [data-theme="dark"] .page-btn {
            background: #161b22;
            border-color: #21262d;
            color: #9ca3af;
        }

        [data-theme="dark"] .page-btn:hover {
            background: rgba(139, 0, 0, .2);
            border-color: #8B0000;
            color: #f87171;
        }

        [data-theme="dark"] .border-gray-200 {
            border-color: #21262d !important;
        }

        [data-theme="dark"] .bg-gray-100 {
            background-color: #21262d !important;
        }

        .um-search-mobile {
            width: 300px;
            max-width: 100%;
        }

        #umViewToggle {
            flex-shrink: 0;
        }

        .um-role-tabs {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: flex-start;
            background: #F5F2EE;
            border: 1px solid #E8E4DE;
            border-radius: 10px;
            padding: 3px;
            gap: 2px;
            width: auto;
            max-width: 100%;
        }

        #umListView table {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
        }

        #umListView thead th,
        #umListView tbody td {
            vertical-align: middle;
        }

        #umListView thead th:nth-child(1),
        #umListView tbody td:nth-child(1) {
            width: 56px;
        }

        #umListView thead th:nth-child(2),
        #umListView tbody td:nth-child(2) {
            width: 34%;
        }

        #umListView thead th:nth-child(3),
        #umListView tbody td:nth-child(3) {
            width: 16%;
        }

        #umListView thead th:nth-child(4),
        #umListView tbody td:nth-child(4) {
            width: 14%;
        }

        #umListView thead th:nth-child(5),
        #umListView tbody td:nth-child(5) {
            width: 18%;
        }

        #umListView thead th:nth-child(6),
        #umListView tbody td:nth-child(6) {
            width: 18%;
        }

        .user-table-row td {
            border-bottom: 1px solid #f3f4f6;
        }

        .user-table-row:last-child td {
            border-bottom: 0;
        }

        @media (max-width: 767px) {

            .um-search-mobile,
            .um-filter-main,
            .um-filter-actions {
                width: 100%;
            }

            #umFilterForm {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .search-wrap,
            #statusFilter {
                width: 100%;
            }

            .search-wrap {
                height: 38px;
                padding: 0 12px;
                border-radius: 12px;
            }

            .search-wrap input {
                font-size: 13px;
                min-width: 0;
            }

            .search-clear-btn-outside {
                width: 38px;
                min-width: 38px;
                height: 38px;
                border-radius: 12px;
            }

            .um-role-tabs {
                width: 100%;
                justify-content: flex-start;
                overflow-x: auto;
                overflow-y: hidden;
                flex-wrap: nowrap;
                white-space: nowrap;
                scrollbar-width: none;
                margin: 0;
                padding: 3px;
            }

            .um-role-tabs::-webkit-scrollbar {
                display: none;
            }

            .um-role-tabs .tab-btn {
                flex: 0 0 auto;
                padding: 6px 14px;
            }

            #statusFilter {
                min-width: 100%;
                height: 40px;
            }

            #umViewToggle {
                display: none !important;
            }

            .um-grid-wrap {
                padding: .75rem;
            }

            .um-grid {
                grid-template-columns: 1fr;
                gap: .75rem;
            }

            .um-grid-card {
                border-radius: 14px;
                padding: .8rem;
                gap: .65rem;
            }

            .um-grid-top {
                gap: .5rem;
                align-items: center;
            }

            .um-grid-number {
                font-size: .68rem;
            }

            .um-grid-card .w-10.h-10 {
                width: 2.25rem !important;
                height: 2.25rem !important;
                border-radius: 12px !important;
                font-size: .9rem !important;
            }

            .um-grid-card .font-semibold.text-gray-800.text-sm.leading-tight {
                font-size: .95rem !important;
                line-height: 1.2 !important;
            }

            .um-grid-card .text-\[11px\].text-gray-400.mt-0\.5 {
                font-size: .74rem !important;
                margin-top: 1px !important;
                line-height: 1.2 !important;
                word-break: break-word;
            }

            .um-grid-meta {
                gap: .45rem;
            }

            .um-grid-label {
                font-size: .62rem;
                margin-bottom: .18rem;
            }

            .um-grid-value {
                font-size: .78rem;
                line-height: 1.25;
            }

            .um-grid-card .badge-role,
            .um-grid-card .badge-active,
            .um-grid-card .badge-inactive {
                font-size: .68rem !important;
                padding: .22rem .55rem !important;
            }

            .um-grid-card .action-btn {
                width: 32px !important;
                height: 32px !important;
                border-radius: 10px !important;
            }

            .um-grid-card .action-btn i {
                font-size: 10px !important;
            }

        }

        .modal-box.um-user-modal {
            max-width: 1100px !important;
            width: min(1100px, calc(100vw - 40px)) !important;
            max-height: calc(100vh - 28px) !important;
            overflow: hidden !important;
            display: flex;
            flex-direction: column;
        }

        .um-user-modal .um-user-modal-header {
            flex-shrink: 0;
        }

        .um-user-modal .um-user-modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 1.25rem 1.5rem 1.1rem;
        }

        .um-user-modal .um-user-modal-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(340px, .8fr);
            gap: 1rem;
            align-items: start;
        }

        .um-user-modal .um-user-main-card,
        .um-user-modal .um-user-side-card {
            border: 1px solid #edf0f3;
            border-radius: 22px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfc 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7);
        }

        .um-user-modal .um-user-main-card {
            padding: 1.15rem;
        }

        .um-user-modal .um-user-side-card {
            padding: 1.15rem;
        }

        .um-user-modal .um-section-title {
            display: flex;
            align-items: center;
            gap: .7rem;
            margin-bottom: 1rem;
        }

        .um-user-modal .um-section-icon {
            width: 2.35rem;
            height: 2.35rem;
            border-radius: .95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .um-user-modal .um-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .um-user-modal .um-field-full {
            grid-column: 1 / -1;
        }

        .um-user-modal .um-status-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .um-user-modal .um-status-card {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .9rem .95rem;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: #fafafa;
            cursor: pointer;
            transition: all .18s ease;
        }

        .um-user-modal .um-status-card:hover {
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

        .um-user-modal .um-status-card--active {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .um-user-modal .um-status-card--inactive {
            background: #f9fafb;
            border-color: #e5e7eb;
        }

        .um-user-modal .um-password-note {
            border: 1px dashed #d9dee5;
            background: #f8fafc;
            border-radius: 16px;
            padding: .9rem 1rem;
            font-size: .78rem;
            line-height: 1.55;
            color: #667085;
        }

        .um-user-modal .um-user-modal-footer {
            flex-shrink: 0;
            display: flex;
            justify-content: flex-end;
            gap: .75rem;
            padding: 1rem 1.5rem 1.2rem;
            border-top: 1px solid #eef2f6;
            background: linear-gradient(180deg, rgba(255, 255, 255, .92), #fff);
            position: sticky;
            bottom: 0;
            z-index: 5;
        }

        .um-user-modal .um-user-modal-footer .btn-cancel,
        .um-user-modal .um-user-modal-footer .btn-save {
            min-height: 46px;
            border-radius: 14px;
            padding: 0 1.1rem;
        }

        .um-user-modal .field-input {
            min-height: 52px;
        }

        .um-user-modal .field-input,
        .um-user-modal select,
        .um-user-modal input {
            border-radius: 14px !important;
        }

        .um-user-modal .um-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #edf0f3 12%, #edf0f3 88%, transparent);
            margin: 1rem 0 1.1rem;
        }

        @media (max-width: 1023px) {
            .modal-box.um-user-modal {
                width: min(960px, calc(100vw - 28px)) !important;
            }

            .um-user-modal .um-user-modal-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .modal-box.um-user-modal {
                width: 100% !important;
                max-width: 100% !important;
                max-height: calc(100vh - 16px) !important;
                border-radius: 18px !important;
            }

            .um-user-modal .um-user-modal-body {
                padding: .95rem .95rem .85rem;
            }

            .um-user-modal .um-user-main-card,
            .um-user-modal .um-user-side-card {
                border-radius: 18px;
                padding: .95rem;
            }

            .um-user-modal .um-field-grid,
            .um-user-modal .um-status-grid {
                grid-template-columns: 1fr;
                gap: .75rem;
            }

            .um-user-modal .um-user-modal-footer {
                padding: .85rem .95rem 1rem;
                gap: .6rem;
            }

            .um-user-modal .um-user-modal-footer .btn-cancel,
            .um-user-modal .um-user-modal-footer .btn-save {
                flex: 1 1 0;
                justify-content: center;
            }
        }

        [data-theme="dark"] body,
        [data-theme="dark"] main,
        [data-theme="dark"] #mainContent {
            background: #000D1A !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .um-stat-card,
        [data-theme="dark"] .um-panel,
        [data-theme="dark"] .um-grid-card,
        [data-theme="dark"] .bg-white {
            background: linear-gradient(180deg, #161b22 0%, #0d1117 100%) !important;
            border-color: #21262d !important;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .28) !important;
        }

        [data-theme="dark"] .um-stat-trend,
        [data-theme="dark"] .search-wrap,
        [data-theme="dark"] #statusFilter,
        [data-theme="dark"] .um-view-toggle,
        [data-theme="dark"] .um-role-tabs,
        [data-theme="dark"] .bg-gray-50 {
            background: #0d1117 !important;
            border-color: #21262d !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .um-stat-value,
        [data-theme="dark"] .um-panel-title,
        [data-theme="dark"] .um-grid-value,
        [data-theme="dark"] .text-gray-800,
        [data-theme="dark"] .font-semibold.text-gray-800 {
            color: #f3f4f6 !important;
        }

        [data-theme="dark"] .um-stat-label,
        [data-theme="dark"] .um-stat-caption,
        [data-theme="dark"] .um-panel-subtitle,
        [data-theme="dark"] .um-grid-label,
        [data-theme="dark"] .text-gray-500,
        [data-theme="dark"] .text-gray-400 {
            color: #9ca3af !important;
        }

        [data-theme="dark"] #umListView thead,
        [data-theme="dark"] #umListView thead tr,
        [data-theme="dark"] table thead tr {
            background: #0d1117 !important;
        }

        [data-theme="dark"] .user-table-row td {
            border-color: #21262d !important;
        }

        [data-theme="dark"] .user-table-row:hover {
            background: #161b22 !important;
        }

        [data-theme="dark"] .modal-box,
        [data-theme="dark"] .modal-box .sticky,
        [data-theme="dark"] .um-user-modal-header,
        [data-theme="dark"] .um-user-modal-footer {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .um-user-main-card,
        [data-theme="dark"] .um-user-side-card,
        [data-theme="dark"] .um-status-card,
        [data-theme="dark"] .um-password-note {
            background: #0d1117 !important;
            border-color: #21262d !important;
            color: #d1d5db !important;
        }

        [data-theme="dark"] input,
        [data-theme="dark"] select,
        [data-theme="dark"] textarea,
        [data-theme="dark"] .field-input {
            background: #0d1117 !important;
            border-color: #21262d !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] input::placeholder {
            color: #6b7280 !important;
        }

        [data-theme="dark"] .page-btn,
        [data-theme="dark"] .um-pagination-wrap button,
        [data-theme="dark"] .um-pagination-wrap span {
            background: #161b22 !important;
            border-color: #21262d !important;
            color: #d1d5db !important;
        }

        [data-theme="dark"] .um-pagination-wrap span {
            background: linear-gradient(135deg, #8B0000, #6b0000) !important;
            border-color: #8B0000 !important;
            color: #fff !important;
        }

        [data-theme="dark"] .action-btn[style*="background:#f3f4f6"] {
            background: #21262d !important;
            color: #e5e7eb !important;
        }
    </style>
@endsection

@section('content')

    @php
        $totalUsers = $totalUsers ?? ($allUsersCount ?? ($users->total() ?? 0));
        $activeCount = $activeCount ?? 0;
        $inactiveCount = $inactiveCount ?? 0;
    @endphp

    <main id="mainContent" class="px-3 sm:px-6 pt-[82px] pb-8 min-h-screen">
        <div style="max-width:1280px; margin:0 auto;">

            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">User Management</h1>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap w-full sm:w-auto">
                        <button type="button" onclick="openModal('addModal', this)" class="um-hero-btn">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Add New User</span>
                        </button>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showSuccessToast("{{ session('success') }}");
                    });
                </script>
            @endif

            @if (session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showErrorToast("{{ session('error') }}");
                    });
                </script>
            @endif

            <div class="um-stats-grid mb-6">
                <div class="um-stat-card um-stat-card--users">
                    <div class="um-stat-top">
                        <div class="um-stat-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <span class="um-stat-trend">Overview</span>
                    </div>
                    <div class="um-stat-body">
                        <p class="um-stat-label">Total Users</p>
                        <p class="um-stat-value" id="countTotalUsers">{{ $totalUsers }}</p>
                        <p class="um-stat-caption">All registered system accounts</p>
                    </div>
                </div>

                <div class="um-stat-card um-stat-card--active">
                    <div class="um-stat-top">
                        <div class="um-stat-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <span class="um-stat-trend">Healthy</span>
                    </div>
                    <div class="um-stat-body">
                        <p class="um-stat-label">Active</p>
                        <p class="um-stat-value" id="countActiveUsers">{{ $activeCount }}</p>
                        <p class="um-stat-caption">Accounts currently enabled</p>
                    </div>
                </div>

                <div class="um-stat-card um-stat-card--inactive">
                    <div class="um-stat-top">
                        <div class="um-stat-icon">
                            <i class="fa-solid fa-user-slash"></i>
                        </div>
                        <span class="um-stat-trend">Attention</span>
                    </div>
                    <div class="um-stat-body">
                        <p class="um-stat-label">Inactive</p>
                        <p class="um-stat-value" id="countInactiveUsers">{{ $inactiveCount }}</p>
                        <p class="um-stat-caption">Accounts currently disabled</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-100 overflow-visible mb-6">
                <div class="px-4 sm:px-5 py-4 border-b bg-gray-50 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-users-gear text-[#8B0000]"></i>
                        <h2 class="font-bold text-gray-800 text-sm">All System Users</h2>
                        <span id="countBadgeUsers"
                            class="text-[10px] font-bold bg-[#8B0000] text-white px-2 py-0.5 rounded-full">{{ $totalUsers }}</span>
                    </div>

                    <form method="GET" action="{{ route('admin.user_management') }}" id="umFilterForm"
                        class="flex items-center gap-2.5 flex-wrap">
                        {{-- Search --}}
                        <div class="um-search-mobile um-search-row">
                            <div class="search-wrap">
                                <i class="fa fa-search" style="color:#8B0000;font-size:13px;flex-shrink:0;"></i>
                                <input id="umSearch" name="search" class="no-voice" placeholder="Search name or email…"
                                    value="{{ $search ?? '' }}" autocomplete="off" oninput="toggleSearchClear(this)"
                                    onkeydown="if(event.key==='Enter'){event.preventDefault();}" />
                            </div>

                            <button type="button" id="searchClearBtn"
                                class="search-clear-btn {{ $search ?? '' ? '' : 'hidden' }}" onclick="clearSearch()"
                                title="Clear">Clear</button>

                            <div class="patient-voice-toggle">
                                <button type="button" id="umMicToggleBtn" class="voice-search-mic external" aria-label="Toggle voice input" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="umVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>

                        <div class="um-view-toggle" id="umViewToggle">
                            <button type="button" class="um-view-toggle-btn active" id="umListViewBtn"
                                title="List view" aria-label="List view">
                                <i class="fa-solid fa-table-list"></i>
                            </button>
                            <button type="button" class="um-view-toggle-btn" id="umGridViewBtn" title="Grid view"
                                aria-label="Grid view">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>
                </div>
                </form>
            </div>

            <div class="um-view" id="umListView">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr class="text-[10px] uppercase tracking-wide text-[#8B0000] font-bold">
                                <th class="py-3 px-3 sm:px-5 text-left w-12 hidden sm:table-cell">#</th>
                                <th class="py-3 px-4 text-left">User</th>
                                <th class="py-3 px-4 text-left">Role</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-left hidden lg:table-cell">Registered</th>
                                <th class="py-3 px-5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="umTableBody">
                            @forelse($users as $user)
                                <tr class="user-table-row border-b border-gray-50 last:border-0"
                                    data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}"
                                    data-role="{{ strtolower(optional($user->role)->name ?? '') }}">
                                    <td class="py-3.5 px-3 sm:px-5 hidden sm:table-cell">
                                        <span
                                            class="text-xs text-gray-400 font-medium">{{ $users->firstItem() + $loop->index }}</span>
                                    </td>

                                    <td class="py-3.5 px-3 sm:px-4">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <div
                                                class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-800 text-sm leading-tight">
                                                    {{ $user->name }}
                                                </div>
                                                <div class="text-[11px] text-gray-400 mt-0.5 hidden sm:block">
                                                    {{ $user->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4">
                                        @php $roleSlug = optional($user->role)->slug; @endphp
                                        <span class="badge-role"
                                            style="background:
                {{ $roleSlug === 'patient' ? '#dbeafe' : ($roleSlug === 'dentist' ? '#d1fae5' : '#fee2e2') }};
                color:
                {{ $roleSlug === 'patient' ? '#1d4ed8' : ($roleSlug === 'dentist' ? '#065f46' : '#8B0000') }};">
                                            {{ optional($user->role)->name ?? 'No Role' }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 text-center">
                                        <span
                                            class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $user->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 hidden lg:table-cell">
                                        <span
                                            class="text-xs text-gray-400">{{ $user->created_at->format('M d, Y') }}</span>
                                    </td>

                                    <td class="py-3.5 px-2 sm:px-5">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button"
                                                onclick="openEditModal(
                                                    'users',
                                                    {{ $user->id }},
                                                    @js($user->name),
                                                    @js($user->email),
                                                    @js($user->role_id),
                                                    @js($user->status)
                                                  )"
                                                class="action-btn btn-edit" title="Edit account">
                                                <i class="fa-solid fa-pen text-[11px]"></i>
                                            </button>

                                            <button type="button"
                                                onclick="openToggleConfirm({{ $user->id }}, @js($user->status), @js($user->name))"
                                                class="action-btn {{ $user->status === 'active' ? 'btn-toggle-on' : 'btn-toggle-off' }}"
                                                title="{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                <i
                                                    class="fa-solid {{ $user->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }} text-[11px]"></i>
                                            </button>

                                            <button type="button"
                                                onclick="openResetModal('users', {{ $user->id }}, @js($user->name))"
                                                class="action-btn btn-reset" title="Reset password">
                                                <i class="fa-solid fa-key text-[11px]"></i>
                                            </button>

                                            <button type="button"
                                                onclick="openViewModal(
                                                    @js($user->name),
                                                    @js($user->email),
                                                    @js(optional($user->role)->name ?? 'No Role'),
                                                    @js(ucfirst($user->status)),
                                                    'Users',
                                                    @js($user->created_at ? $user->created_at->format('M d, Y h:i A') : 'N/A')
                                                  )"
                                                class="action-btn" style="background:#f3f4f6;color:#374151;"
                                                title="View details">
                                                <i class="fa-solid fa-eye text-[11px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr id="dbEmptyRow">
                                    <td colspan="6" style="padding:3.5rem 1rem;text-align:center;">
                                        <div
                                            style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;background:#f3f4f6;border-radius:18px;margin-bottom:1rem;">
                                            <i class="fa-solid fa-magnifying-glass"
                                                style="font-size:1.6rem;color:#d1d5db;"></i>
                                        </div>
                                        <p style="font-size:.9rem;font-weight:700;color:#374151;margin:0 0 .3rem;">No
                                            users
                                            found</p>
                                        <p style="font-size:.78rem;color:#9ca3af;margin:0;">Try adjusting your filters.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="um-view" id="umGridView" hidden>
                <div class="um-grid-wrap">
                    <div class="um-grid" id="umGridBody">
                        @forelse($users as $user)
                            @php
                                $roleSlug = optional($user->role)->slug;
                                $roleName = optional($user->role)->name ?? 'No Role';
                                $roleBg =
                                    $roleSlug === 'patient'
                                        ? '#dbeafe'
                                        : ($roleSlug === 'dentist'
                                            ? '#d1fae5'
                                            : '#fee2e2');
                                $roleColor =
                                    $roleSlug === 'patient'
                                        ? '#1d4ed8'
                                        : ($roleSlug === 'dentist'
                                            ? '#065f46'
                                            : '#8B0000');
                            @endphp

                            <div class="um-grid-card">
                                <div class="um-grid-top">
                                    <div class="um-grid-number">#{{ $users->firstItem() + $loop->index }}</div>
                                    <span
                                        class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $user->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-gray-800 text-sm leading-tight">
                                            {{ $user->name }}
                                        </div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">
                                            {{ $user->email }}
                                        </div>
                                    </div>
                                </div>

                                <div class="um-grid-meta">
                                    <div class="um-grid-field">
                                        <div class="um-grid-label">Role</div>
                                        <div class="um-grid-value">
                                            <span class="badge-role"
                                                style="background:{{ $roleBg }};color:{{ $roleColor }};">
                                                {{ $roleName }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="um-grid-field">
                                        <div class="um-grid-label">Registered</div>
                                        <div class="um-grid-value">{{ $user->created_at->format('M d, Y') }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-1 flex-wrap">
                                    <button type="button"
                                        onclick="openEditModal(
                                                'users',
                                                {{ $user->id }},
                                                @js($user->name),
                                                @js($user->email),
                                                @js($user->role_id),
                                                @js($user->status)
                                            )"
                                        class="action-btn btn-edit" title="Edit account">
                                        <i class="fa-solid fa-pen text-[11px]"></i>
                                    </button>

                                    <button type="button"
                                        onclick="openToggleConfirm({{ $user->id }}, @js($user->status), @js($user->name))"
                                        class="action-btn {{ $user->status === 'active' ? 'btn-toggle-on' : 'btn-toggle-off' }}"
                                        title="{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        <i
                                            class="fa-solid {{ $user->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }} text-[11px]"></i>
                                    </button>

                                    <button type="button"
                                        onclick="openResetModal('users', {{ $user->id }}, @js($user->name))"
                                        class="action-btn btn-reset" title="Reset password">
                                        <i class="fa-solid fa-key text-[11px]"></i>
                                    </button>

                                    <button type="button"
                                        onclick="openViewModal(
                                                @js($user->name),
                                                @js($user->email),
                                                @js($roleName),
                                                @js(ucfirst($user->status)),
                                                'Users',
                                                @js($user->created_at ? $user->created_at->format('M d, Y h:i A') : 'N/A')
                                            )"
                                        class="action-btn" style="background:#f3f4f6;color:#374151;"
                                        title="View details">
                                        <i class="fa-solid fa-eye text-[11px]"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div
            class="px-4 sm:px-5 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-gray-500 um-pagebar-info">
                Showing
                <strong>{{ $users->firstItem() ?? 0 }}</strong>–<strong>{{ $users->lastItem() ?? 0 }}</strong>
                of <strong>{{ $users->total() }}</strong> users
            </p>
            <div class="um-pagination-wrap flex items-center gap-1.5"></div>
        </div>
        </div>
        </div>
    </main>

    <!-- Global Toast Container -->
    <div id="toastContainer"
        style="position:fixed;top:16px;right:16px;z-index:99999;display:flex;flex-direction:column;gap:8px;align-items:flex-end;pointer-events:none;width:340px;">
    </div>

    <div class="modal-overlay" id="addModal" aria-hidden="true" onclick="closeModalOutside(event,'addModal')">
        <div class="modal-box um-user-modal">
            <div
                class="um-user-modal-header px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#8B0000] via-[#a40000] to-[#6B0000] flex items-center justify-center shadow-lg shadow-red-900/20 flex-shrink-0">
                        <i class="fa-solid fa-user-plus text-white text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-gray-800 text-lg leading-tight">Add New User</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Create a system account and assign access permissions.</p>
                    </div>
                </div>

                <button type="button" onclick="closeModal('addModal')"
                    class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:border-red-200 hover:text-[#8B0000] transition-all flex-shrink-0">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.user_management.store') }}"
                id="addUserForm"
                class="flex-1 flex flex-col min-h-0">
                @csrf

                <div class="um-user-modal-body">
                    @if ($errors->any())
                        <div class="mb-4 bg-red-50 border border-red-200 rounded-2xl p-3 text-xs text-red-700 space-y-1.5">
                            @foreach ($errors->all() as $error)
                                <div class="flex items-start gap-2">
                                    <i class="fa-solid fa-circle-xmark mt-0.5"></i>
                                    <span>{{ $error }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="um-user-modal-grid">
                        <div class="um-user-main-card">
                            <div class="um-section-title">
                                <div class="um-section-icon bg-red-50 text-[#8B0000]">
                                    <i class="fa-solid fa-id-card text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-extrabold text-gray-800 leading-tight">Account Details</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Basic identity, role assignment, and account
                                        status.</p>
                                </div>
                            </div>

                            <div class="um-field-grid">
                                <div class="um-field-full">
                                    <label
                                        class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input type="text" id="addNameInput" name="name"
                                            value="{{ old('name') }}" class="no-voice field-input flex-1 min-w-0 border border-gray-200 px-3.5 py-3 text-sm bg-white"
                                            placeholder="e.g. Juan dela Cruz" required>
                                        <div class="patient-voice-toggle">
                                            <button type="button" id="addNameMicBtn" class="voice-search-mic external" aria-label="Voice input for full name">
                                                <i class="fa-solid fa-microphone"></i>
                                            </button>
                                            <span id="addNameVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="um-field-full">
                                    <label
                                        class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-envelope text-gray-400 text-xs flex-shrink-0 pl-1"></i>
                                        <input type="email" id="addEmailInput" name="email"
                                            value="{{ old('email') }}" class="no-voice field-input flex-1 min-w-0 border border-gray-200 px-3.5 py-3 text-sm bg-white"
                                            placeholder="user@pup.edu.ph" required>
                                        <div class="patient-voice-toggle">
                                            <button type="button" id="addEmailMicBtn" class="voice-search-mic external" aria-label="Voice input for email">
                                                <i class="fa-solid fa-microphone"></i>
                                            </button>
                                            <span id="addEmailVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Role
                                    </label>
                                    <select name="role_id"
                                        class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white">
                                        <option value="">— No Role —</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Account Type
                                    </label>
                                    <div
                                        class="field-input w-full border border-dashed border-gray-200 px-3.5 py-3 text-sm bg-gray-50 text-gray-500 flex items-center">
                                        System-managed user account
                                    </div>
                                </div>
                            </div>

                            <div class="um-divider"></div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>

                                <div class="um-status-grid">
                                    <label class="um-status-card um-status-card--active">
                                        <input type="radio" name="status" value="active"
                                            {{ old('status', 'active') === 'active' ? 'checked' : '' }}
                                            style="accent-color:#8B0000; margin-top:.22rem;">
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-emerald-800 leading-tight">Active</div>
                                            <div class="text-[11px] text-emerald-700 mt-0.5">Can access the system
                                                immediately</div>
                                        </div>
                                    </label>

                                    <label class="um-status-card um-status-card--inactive">
                                        <input type="radio" name="status" value="inactive"
                                            {{ old('status') === 'inactive' ? 'checked' : '' }}
                                            style="accent-color:#8B0000; margin-top:.22rem;">
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-gray-700 leading-tight">Inactive</div>
                                            <div class="text-[11px] text-gray-500 mt-0.5">Account exists but login is
                                                disabled</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="um-user-side-card">
                            <div class="um-section-title">
                                <div class="um-section-icon bg-blue-50 text-blue-600">
                                    <i class="fa-solid fa-lock text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-extrabold text-gray-800 leading-tight">Security Setup</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Set the initial login credentials.</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                        <input type="password" name="password" id="addPassword"
                                            placeholder="Min. 8 characters"
                                            class="field-input w-full border border-gray-200 pl-10 pr-11 py-3 text-sm bg-white"
                                            required>
                                        <button type="button" onclick="togglePassVis('addPassword','addEye')"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <i class="fa-regular fa-eye text-sm" id="addEye"></i>
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-gray-400 mt-1.5">Use at least 8 characters for better
                                        security.</p>
                                </div>

                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Confirm Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                        <input type="password" name="password_confirmation" id="addPasswordConf"
                                            placeholder="Repeat password"
                                            class="field-input w-full border border-gray-200 pl-10 pr-11 py-3 text-sm bg-white"
                                            required>
                                        <button type="button" onclick="togglePassVis('addPasswordConf','addEye2')"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <i class="fa-regular fa-eye text-sm" id="addEye2"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="um-password-note">
                                    The user can update their password after first sign-in depending on your account
                                    workflow.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="um-user-modal-footer">
                    <button type="button" onclick="closeModal('addModal')"
                        class="btn-cancel px-5 py-2.5 border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all inline-flex items-center">
                        Cancel
                    </button>

                    <button type="submit"
                        class="btn-save px-6 py-2.5 bg-[#8B0000] hover:bg-[#760000] text-white text-sm font-bold shadow transition-all inline-flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editModal" aria-hidden="true" onclick="closeModalOutside(event,'editModal')">
        <div class="modal-box">
            <div
                class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow">
                        <i class="fa-solid fa-user-pen text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-gray-800 text-base">Edit User</h3>
                        <p class="text-[10px] text-gray-500" id="editModalSubtitle">Updating user details</p>
                    </div>
                </div>
                <button type="button" data-close-modal="editModal"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#8B0000] transition-all">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" id="editForm" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Full Name
                        <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="editName" placeholder="Full name"
                        class="field-input w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm" required>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Email
                        Address <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="email" name="email" id="editEmail" placeholder="user@pup.edu.ph"
                            class="field-input w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm" required>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Role</label>
                    <select name="role_id" id="editRole"
                        class="field-input w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white">
                        <option value="">— No Role —</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Status
                        <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" id="editStatusActive" value="active"
                                style="accent-color:#8B0000;">
                            <span class="text-sm text-gray-700 font-medium">Active</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" id="editStatusInactive" value="inactive"
                                style="accent-color:#8B0000;">
                            <span class="text-sm text-gray-700 font-medium">Inactive</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('editModal')"
                        class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal-overlay" id="resetModal" aria-hidden="true" onclick="closeModalOutside(event,'resetModal')">
        <div class="modal-box modal-sm">
            <div
                class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow">
                        <i class="fa-solid fa-key text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-gray-800 text-base">Reset Password</h3>
                        <p class="text-[10px] text-gray-500" id="resetModalSubtitle">Set a new password</p>
                    </div>
                </div>
                <button type="button" data-close-modal="resetModal"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#8B0000] transition-all">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" id="resetForm" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">New
                        Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="password" name="password" id="resetPassword" placeholder="Min. 8 characters"
                            class="field-input w-full border border-gray-200 rounded-lg pl-9 pr-10 py-2.5 text-sm"
                            required>
                        <button type="button" onclick="togglePassVis('resetPassword','resetEye')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fa-regular fa-eye text-xs" id="resetEye"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Confirm
                        Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="password" name="password_confirmation" id="resetPasswordConf"
                            placeholder="Repeat password"
                            class="field-input w-full border border-gray-200 rounded-lg pl-9 pr-10 py-2.5 text-sm"
                            required>
                        <button type="button" onclick="togglePassVis('resetPasswordConf','resetEye2')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fa-regular fa-eye text-xs" id="resetEye2"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('resetModal')"
                        class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold shadow transition-all flex items-center gap-2">
                        <i class="fa-solid fa-key"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="viewModal" aria-hidden="true" onclick="closeModalOutside(event,'viewModal')">
        <div class="modal-box modal-sm">
            <div
                class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center shadow">
                        <i class="fa-solid fa-eye text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-gray-800 text-base">Account Details</h3>
                        <p class="text-[10px] text-gray-500">View selected account information</p>
                    </div>
                </div>
                <button type="button" data-close-modal="viewModal"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#8B0000] transition-all">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 text-sm">
                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Name</div>
                    <div id="viewName" class="text-gray-800 font-semibold mt-1"></div>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Email</div>
                    <div id="viewEmail" class="text-gray-800 mt-1"></div>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Role</div>
                    <div id="viewRole" class="text-gray-800 mt-1"></div>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status</div>
                    <div id="viewStatus" class="text-gray-800 mt-1"></div>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Source</div>
                    <div id="viewSource" class="text-gray-800 mt-1"></div>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Created At</div>
                    <div id="viewCreatedAt" class="text-gray-800 mt-1"></div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" onclick="closeModal('viewModal')"
                        class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="toggleConfirmModal" aria-hidden="true"
        onclick="closeModalOutside(event,'toggleConfirmModal')">
        <div class="modal-box modal-sm">
            <div
                class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
                <div class="flex items-center gap-3">
                    <div id="toggleModalIcon" class="w-10 h-10 rounded-xl flex items-center justify-center shadow">
                    </div>
                    <div>
                        <h3 class="font-extrabold text-gray-800 text-base" id="toggleModalTitle">Confirm Action</h3>
                        <p class="text-[10px] text-gray-500" id="toggleModalSubtitle">Please confirm this change</p>
                    </div>
                </div>
                <button type="button" data-close-modal="toggleConfirmModal"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#8B0000] transition-all">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6">
                <div id="toggleModalBody" class="rounded-xl p-4 mb-5 flex items-start gap-3 text-sm"></div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('toggleConfirmModal')"
                        class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <form id="toggleConfirmForm" method="POST" style="display:inline;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" id="toggleConfirmBtn"
                            class="px-6 py-2.5 rounded-lg text-white text-sm font-bold shadow transition-all flex items-center gap-2">
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const currentDateEl = document.getElementById('currentDate');
        if (currentDateEl) {
            currentDateEl.textContent = new Date().toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        var umState = {
            search: '{{ $search ?? '' }}',
            role: '{{ $roleFilter ?? '' }}',
            status: '{{ $statusFilter ?? '' }}',
            perPage: {{ $perPage ?? 10 }},
            page: {{ request('page', 1) }},
        };

        var umSearchTimer = null;
        var umController = null;

        function getPreferredUmView() {
            if (window.innerWidth <= 767) return 'grid';
            return localStorage.getItem('userManagementView') || 'list';
        }

        function applyUmView(view, save = true) {
            var listView = document.getElementById('umListView');
            var gridView = document.getElementById('umGridView');
            var listBtn = document.getElementById('umListViewBtn');
            var gridBtn = document.getElementById('umGridViewBtn');

            if (!listView || !gridView) return;

            var finalView = window.innerWidth <= 767 ? 'grid' : view;

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
                localStorage.setItem('userManagementView', finalView);
            }
        }

        function initUmViewToggle() {
            var listBtn = document.getElementById('umListViewBtn');
            var gridBtn = document.getElementById('umGridViewBtn');

            applyUmView(getPreferredUmView(), false);

            if (listBtn && !listBtn.dataset.bound) {
                listBtn.dataset.bound = '1';
                listBtn.addEventListener('click', function() {
                    applyUmView('list', true);
                });
            }

            if (gridBtn && !gridBtn.dataset.bound) {
                gridBtn.dataset.bound = '1';
                gridBtn.addEventListener('click', function() {
                    applyUmView('grid', true);
                });
            }
        }

        window.closeAllModals = function() {
            document.querySelectorAll('.modal-overlay').forEach(function(modal) {
                var activeEl = document.activeElement;

                if (activeEl && modal.contains(activeEl)) {
                    activeEl.blur();
                }

                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            });

            document.body.classList.remove('modal-open');
        };

        window.openModal = function(id, trigger = null) {
            var modal = document.getElementById(id);
            if (!modal) return;

            window.lastModalTrigger = trigger || document.activeElement;

            document.querySelectorAll('.modal-overlay.open').forEach(function(m) {
                if (m.id !== id) {
                    m.classList.remove('open');
                    m.setAttribute('aria-hidden', 'true');
                }
            });

            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            var firstField = modal.querySelector('input, select, textarea, button');
            if (firstField) {
                setTimeout(function() {
                    firstField.focus();
                }, 30);
            }
        };

        window.closeModal = function(id) {
            var modal = document.getElementById(id);
            if (!modal) return;

            var activeEl = document.activeElement;
            if (activeEl && modal.contains(activeEl)) {
                activeEl.blur();
            }

            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');

            if (!document.querySelector('.modal-overlay.open')) {
                document.body.classList.remove('modal-open');
            }

            if (window.lastModalTrigger && typeof window.lastModalTrigger.focus === 'function') {
                setTimeout(function() {
                    window.lastModalTrigger.focus();
                }, 30);
            }
        };

        window.closeModalOutside = function(e, id) {
            if (e.target.id === id) {
                window.closeModal(id);
            }
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });

        @if ($errors->any() && old('_method') !== 'PUT')
            document.addEventListener('DOMContentLoaded', () => openModal('addModal'));
        @endif

        function openToggleConfirm(userId, currentStatus, userName) {
            var isActive = currentStatus === 'active';
            var icon = document.getElementById('toggleModalIcon');
            var title = document.getElementById('toggleModalTitle');
            var subtitle = document.getElementById('toggleModalSubtitle');
            var body = document.getElementById('toggleModalBody');
            var btn = document.getElementById('toggleConfirmBtn');
            var form = document.getElementById('toggleConfirmForm');

            form.dataset.userId = userId;
            form.dataset.currentStatus = currentStatus;
            form.dataset.userName = userName;
            form.action = '/admin/user-management/' + userId + '/toggle-status';

            btn.disabled = false;

            if (isActive) {
                icon.className =
                    'w-10 h-10 rounded-xl flex items-center justify-center shadow bg-gradient-to-br from-amber-400 to-orange-500';
                icon.innerHTML = '<i class="fa-solid fa-user-slash text-white text-sm"></i>';
                title.textContent = 'Deactivate User';
                subtitle.textContent = 'This will restrict their access';
                body.className = 'rounded-xl p-4 mb-5 flex items-start gap-3 text-sm bg-amber-50 border border-amber-100';
                body.innerHTML =
                    '<i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 flex-shrink-0"></i><div><strong class="text-amber-800">' +
                    userName +
                    '</strong><span class="text-amber-700"> will be <strong>deactivated</strong>. They will no longer be able to log in until reactivated.</span></div>';
                btn.className =
                    'px-6 py-2.5 rounded-lg text-white text-sm font-bold shadow transition-all flex items-center gap-2 bg-amber-500 hover:bg-amber-600';
                btn.innerHTML = '<i class="fa-solid fa-user-slash"></i> Yes, Deactivate';
            } else {
                icon.className =
                    'w-10 h-10 rounded-xl flex items-center justify-center shadow bg-gradient-to-br from-emerald-500 to-green-600';
                icon.innerHTML = '<i class="fa-solid fa-user-check text-white text-sm"></i>';
                title.textContent = 'Activate User';
                subtitle.textContent = 'This will restore their access';
                body.className =
                    'rounded-xl p-4 mb-5 flex items-start gap-3 text-sm bg-emerald-50 border border-emerald-100';
                body.innerHTML =
                    '<i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 flex-shrink-0"></i><div><strong class="text-emerald-800">' +
                    userName +
                    '</strong><span class="text-emerald-700"> will be <strong>activated</strong>. They will regain full access to the system.</span></div>';
                btn.className =
                    'px-6 py-2.5 rounded-lg text-white text-sm font-bold shadow transition-all flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700';
                btn.innerHTML = '<i class="fa-solid fa-user-check"></i> Yes, Activate';
            }

            btn.dataset.originalHtml = btn.innerHTML;

            openModal('toggleConfirmModal');
        }

        function openEditModal(source, id, name, email, roleId, status) {
            const form = document.getElementById('editForm');

            if (source === 'patients') {
                form.action = `/admin/user-management/patient/${id}`;
                document.getElementById('editRole').disabled = true;
                document.getElementById('editStatusActive').disabled = true;
                document.getElementById('editStatusInactive').disabled = true;
            } else {
                form.action = `/admin/user-management/${id}`;
                let methodInput = form.querySelector('input[name="_method"]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    form.appendChild(methodInput);
                }
                methodInput.value = 'PUT';
                document.getElementById('editRole').disabled = false;
                document.getElementById('editStatusActive').disabled = false;
                document.getElementById('editStatusInactive').disabled = false;
            }

            form.dataset.source = source;

            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editModalSubtitle').textContent = 'Editing: ' + name;

            const roleSelect = document.getElementById('editRole');
            roleSelect.value = roleId || '';

            document.getElementById('editStatusActive').checked = (status === 'active');
            document.getElementById('editStatusInactive').checked = (status === 'inactive');

            openModal('editModal');
        }

        function openResetModal(source, id, name) {
            if (source === 'patients') {
                document.getElementById('resetForm').action = `/admin/user-management/patient/${id}/reset-password`;
            } else {
                document.getElementById('resetForm').action = `/admin/user-management/${id}/reset-password`;
            }

            document.getElementById('resetModalSubtitle').textContent = 'Resetting password for: ' + name;
            document.getElementById('resetPassword').value = '';
            document.getElementById('resetPasswordConf').value = '';
            openModal('resetModal');
        }

        function openViewModal(name, email, role, status, source, createdAt) {
            document.getElementById('viewName').textContent = name;
            document.getElementById('viewEmail').textContent = email;
            document.getElementById('viewRole').textContent = role;
            document.getElementById('viewStatus').textContent = status;
            document.getElementById('viewSource').textContent = source;
            document.getElementById('viewCreatedAt').textContent = createdAt;

            openModal('viewModal');
        }

        function togglePassVis(inputId, iconId) {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.className = 'fa-regular fa-eye-slash text-xs';
            } else {
                inp.type = 'password';
                ico.className = 'fa-regular fa-eye text-xs';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(localStorage.getItem('theme') || 'light');
            document.querySelectorAll('.theme-option').forEach(o =>
                o.addEventListener('click', e => {
                    e.stopPropagation();
                    applyTheme(o.getAttribute('data-theme'));
                })
            );

            document.querySelectorAll('.flash-alert').forEach(el => {
                setTimeout(() => {
                    el.style.transition = 'opacity .4s';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 400);
                }, 4000);
            });
        });

        function toggleSearchClear(input) {
            document.getElementById('searchClearBtn')?.classList.toggle('hidden', input.value.trim().length === 0);
        }

        function clearSearch() {
            var input = document.getElementById('umSearch');
            if (!input) return;

            if (window.umListening && window.umRecognition) {
                try {
                    window.umRecognition.stop();
                } catch (e) {}
                window.umListening = false;
            }

            input.value = '';
            document.getElementById('searchClearBtn')?.classList.add('hidden');
            document.getElementById('umVoiceStatus')?.classList.add('hidden');
            var micBtn = document.getElementById('umMicToggleBtn');
            if (micBtn) {
                micBtn.classList.remove('mic-active');
                micBtn.setAttribute('aria-pressed', 'false');
                micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
            }
            umState.search = '';
            umState.page = 1;
            umFetch();
            input.focus();
        }

        function umFetch(silent) {
            if (umController) umController.abort();
            umController = new AbortController();

            var params = new URLSearchParams({
                search: umState.search,
                role: umState.role,
                status: umState.status,
                per_page: umState.perPage,
                page: umState.page,
            });

            history.replaceState(null, '', window.location.pathname + '?' + params.toString());

            fetch('{{ route('admin.user_management') }}?' + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: umController.signal
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    umRenderRows(data.users);
                    umRenderPagebar(data.pagination);
                    if (data.counts) {
                        umRenderCounts(data.counts);
                    }
                })
                .catch(function(e) {
                    if (e.name !== 'AbortError') console.error(e);
                });
        }

        function umRenderRows(users) {
            function jsAttr(value) {
                return JSON.stringify(value ?? '').replace(/"/g, '&quot;');
            }

            var tbody = document.getElementById('umTableBody');
            var gridBody = document.getElementById('umGridBody');

            if (!tbody || !gridBody) return;

            if (!users || users.length === 0) {
                var searchVal = umState.search || '';
                var emptyTitle = searchVal ?
                    'No results for &ldquo;' + searchVal + '&rdquo;' :
                    'No users found';
                var emptySub = searchVal ?
                    'Try a different name or email.' :
                    'Try adjusting your filters.';
                var clearBtn = searchVal ?
                    '<button onclick="clearSearch()" style="margin-top:.75rem;display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border-radius:99px;border:1.5px dashed #d1d5db;background:none;font-size:.78rem;color:#9ca3af;cursor:pointer;transition:all .2s;" onmouseover="this.style.borderColor=\'#8B0000\';this.style.color=\'#8B0000\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#9ca3af\';"><i class=\"fa-solid fa-xmark\" style=\"font-size:.7rem;\"></i> Clear search</button>' :
                    '';

                var emptyHtml = `
                    <div style="padding:3.5rem 1rem;text-align:center;grid-column:1 / -1;">
                        <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;background:#f3f4f6;border-radius:18px;margin-bottom:1rem;">
                            <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
                        </div>
                        <p style="font-size:.9rem;font-weight:700;color:#374151;margin:0 0 .3rem;">${emptyTitle}</p>
                        <p style="font-size:.78rem;color:#9ca3af;margin:0;">${emptySub}</p>
                        ${clearBtn}
                    </div>
                `;

                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="padding:3.5rem 1rem;text-align:center;">
                            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;background:#f3f4f6;border-radius:18px;margin-bottom:1rem;">
                                <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
                            </div>
                            <p style="font-size:.9rem;font-weight:700;color:#374151;margin:0 0 .3rem;">${emptyTitle}</p>
                            <p style="font-size:.78rem;color:#9ca3af;margin:0;">${emptySub}</p>
                            ${clearBtn}
                        </td>
                    </tr>
                `;

                gridBody.innerHTML = emptyHtml;
                return;
            }

            var startNumber = ((umState.page - 1) * umState.perPage) + 1;
            var tableHtml = '';
            var gridHtml = '';

            users.forEach(function(user, index) {
                var rowNumber = startNumber + index;
                var roleSlug = (user.role_slug || '').toLowerCase();
                var roleLabel = user.role_name || 'No Role';
                var registeredDay = user.created_at_day || '—';

                var roleBg = '#fee2e2';
                var roleColor = '#8B0000';

                if (roleSlug === 'patient') {
                    roleBg = '#dbeafe';
                    roleColor = '#1d4ed8';
                } else if (roleSlug === 'dentist') {
                    roleBg = '#d1fae5';
                    roleColor = '#065f46';
                }

                var statusClass = user.status === 'active' ? 'badge-active' : 'badge-inactive';
                var initial = (user.name || 'U').charAt(0).toUpperCase();
                var statusLabel = (user.status || '').charAt(0).toUpperCase() + (user.status || '').slice(1);
                var createdFull = (user.created_at_day || '—') + (user.created_at_time ? ' ' + user
                    .created_at_time : '');

                tableHtml += `
                <tr class="user-table-row border-b border-gray-50 last:border-0">
                    <td class="py-3.5 px-3 sm:px-5 hidden sm:table-cell">
                        <span class="text-xs text-gray-400 font-medium">${rowNumber}</span>
                    </td>

                    <td class="py-3.5 px-3 sm:px-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                                ${initial}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800 text-sm leading-tight">
                                    ${user.name}
                                </div>
                                <div class="text-[11px] text-gray-400 mt-0.5 hidden sm:block">
                                    ${user.email}
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="py-3.5 px-4">
                        <span class="badge-role" style="background:${roleBg};color:${roleColor};">
                            ${roleLabel}
                        </span>
                    </td>

                    <td class="py-3.5 px-4 text-center">
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full ${statusClass}">
                            ${statusLabel}
                        </span>
                    </td>

                    <td class="py-3.5 px-4 hidden lg:table-cell">
                        <span class="text-xs text-gray-400">${registeredDay}</span>
                    </td>

                    <td class="py-3.5 px-2 sm:px-5">
                        <div class="flex items-center justify-center gap-1">
                            <button type="button"
                                onclick="openEditModal(
                                    'users',
                                    ${user.id},
                                    ${jsAttr(user.name)},
                                    ${jsAttr(user.email)},
                                    ${jsAttr(user.role_id)},
                                    ${jsAttr(user.status)}
                                )"
                                class="action-btn btn-edit" title="Edit account">
                                <i class="fa-solid fa-pen text-[11px]"></i>
                            </button>

                            <button type="button"
                                onclick="openToggleConfirm(${user.id}, ${jsAttr(user.status)}, ${jsAttr(user.name)})"
                                class="action-btn ${user.status === 'active' ? 'btn-toggle-on' : 'btn-toggle-off'}"
                                title="${user.status === 'active' ? 'Deactivate' : 'Activate'}">
                                <i class="fa-solid ${user.status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'} text-[11px]"></i>
                            </button>

                            <button type="button"
                                onclick="openResetModal('users', ${user.id}, ${jsAttr(user.name)})"
                                class="action-btn btn-reset" title="Reset password">
                                <i class="fa-solid fa-key text-[11px]"></i>
                            </button>

                            <button type="button"
                                onclick="openViewModal(
                                    ${jsAttr(user.name)},
                                    ${jsAttr(user.email)},
                                    ${jsAttr(roleLabel)},
                                    ${jsAttr(statusLabel)},
                                    'Users',
                                    ${jsAttr(createdFull)}
                                )"
                                class="action-btn" style="background:#f3f4f6;color:#374151;"
                                title="View details">
                                <i class="fa-solid fa-eye text-[11px]"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;

                gridHtml += `
                <div class="um-grid-card">
                    <div class="um-grid-top">
                        <div class="um-grid-number">#${rowNumber}</div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full ${statusClass}">
                            ${statusLabel}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                            ${initial}
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-800 text-sm leading-tight">${user.name}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">${user.email}</div>
                        </div>
                    </div>

                    <div class="um-grid-meta">
                        <div class="um-grid-field">
                            <div class="um-grid-label">Role</div>
                            <div class="um-grid-value">
                                <span class="badge-role" style="background:${roleBg};color:${roleColor};">
                                    ${roleLabel}
                                </span>
                            </div>
                        </div>

                        <div class="um-grid-field">
                            <div class="um-grid-label">Registered</div>
                            <div class="um-grid-value">${registeredDay}</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-1 flex-wrap">
                        <button type="button"
                            onclick="openEditModal(
                                'users',
                                ${user.id},
                                ${jsAttr(user.name)},
                                ${jsAttr(user.email)},
                                ${jsAttr(user.role_id)},
                                ${jsAttr(user.status)}
                            )"
                            class="action-btn btn-edit" title="Edit account">
                            <i class="fa-solid fa-pen text-[11px]"></i>
                        </button>

                        <button type="button"
                            onclick="openToggleConfirm(${user.id}, ${jsAttr(user.status)}, ${jsAttr(user.name)})"
                            class="action-btn ${user.status === 'active' ? 'btn-toggle-on' : 'btn-toggle-off'}"
                            title="${user.status === 'active' ? 'Deactivate' : 'Activate'}">
                            <i class="fa-solid ${user.status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'} text-[11px]"></i>
                        </button>

                        <button type="button"
                            onclick="openResetModal('users', ${user.id}, ${jsAttr(user.name)})"
                            class="action-btn btn-reset" title="Reset password">
                            <i class="fa-solid fa-key text-[11px]"></i>
                        </button>

                        <button type="button"
                            onclick="openViewModal(
                                ${jsAttr(user.name)},
                                ${jsAttr(user.email)},
                                ${jsAttr(roleLabel)},
                                ${jsAttr(statusLabel)},
                                'Users',
                                ${jsAttr(createdFull)}
                            )"
                            class="action-btn" style="background:#f3f4f6;color:#374151;"
                            title="View details">
                            <i class="fa-solid fa-eye text-[11px]"></i>
                        </button>
                    </div>
                </div>
            `;
            });

            tbody.innerHTML = tableHtml;
            gridBody.innerHTML = gridHtml;
            applyUmView(getPreferredUmView(), false);
        }

        function umGoPage(page) {
            umState.page = page;
            umFetch();
        }

        function umRenderPagebar(p) {
            if (!p) return;

            document.querySelectorAll('.um-pagebar-info').forEach(function(el) {
                el.innerHTML = 'Showing <strong>' + p.from + '–' + p.to + '</strong> of <strong>' + p.total +
                    '</strong> users';
            });

            var html = umBuildPagination(p);
            document.querySelectorAll('.um-pagination-wrap').forEach(function(el) {
                el.innerHTML = html;
            });
        }

        function umRenderCounts(counts) {
            if (!counts) return;

            var totalEl = document.getElementById('countTotalUsers');
            var activeEl = document.getElementById('countActiveUsers');
            var inactiveEl = document.getElementById('countInactiveUsers');
            var badgeEl = document.getElementById('countBadgeUsers');

            if (totalEl) totalEl.textContent = counts.all ?? 0;
            if (activeEl) activeEl.textContent = counts.active ?? 0;
            if (inactiveEl) inactiveEl.textContent = counts.inactive ?? 0;
            if (badgeEl) badgeEl.textContent = counts.all ?? 0;
        }

        function umBuildPagination(p) {
            if (p.last_page <= 1) return '';

            var current = p.current_page;
            var last = p.last_page;
            var windowSize = 5;
            var half = Math.floor(windowSize / 2);
            var start = Math.max(1, current - half);
            var end = Math.min(last, start + windowSize - 1);

            if (end - start + 1 < windowSize) {
                start = Math.max(1, end - windowSize + 1);
            }

            var btn =
                'style="height:32px;min-width:32px;padding:0 10px;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;color:#374151;font-size:.75rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"';
            var btnActive =
                'style="height:32px;min-width:32px;padding:0 10px;border-radius:8px;border:1.5px solid #8B0000;background:linear-gradient(135deg,#8B0000,#6b0000);color:#fff;font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;"';
            var btnDis =
                'style="height:32px;min-width:32px;padding:0 10px;border-radius:8px;border:1.5px solid #e5e7eb;background:#f9fafb;color:#d1d5db;font-size:.75rem;font-weight:600;cursor:not-allowed;display:inline-flex;align-items:center;justify-content:center;"';

            var html = '<nav style="display:flex;align-items:center;gap:.35rem;flex-wrap:nowrap;">';

            if (current <= 1) {
                html += '<button disabled ' + btnDis +
                    '><i class="fa-solid fa-chevron-left" style="font-size:.65rem;"></i></button>';
            } else {
                html += '<button onclick="umGoPage(' + (current - 1) + ')" ' + btn +
                    '><i class="fa-solid fa-chevron-left" style="font-size:.65rem;"></i></button>';
            }

            for (var i = start; i <= end; i++) {
                if (i === current) {
                    html += '<span ' + btnActive + '>' + i + '</span>';
                } else {
                    html += '<button onclick="umGoPage(' + i + ')" ' + btn + '>' + i + '</button>';
                }
            }

            if (current >= last) {
                html += '<button disabled ' + btnDis +
                    '><i class="fa-solid fa-chevron-right" style="font-size:.65rem;"></i></button>';
            } else {
                html += '<button onclick="umGoPage(' + (current + 1) + ')" ' + btn +
                    '><i class="fa-solid fa-chevron-right" style="font-size:.65rem;"></i></button>';
            }

            html += '</nav>';
            return html;
        }

        function showSuccessToast(message) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const normalizedMessage = String(message || '').trim();

            const existing = Array.from(container.querySelectorAll('[data-toast-type="success"]'))
                .find(toast => toast.dataset.toastMessage === normalizedMessage);

            if (existing) return;

            const toast = document.createElement('div');
            toast.dataset.toastType = 'success';
            toast.dataset.toastMessage = normalizedMessage;

            toast.style.cssText =
                'pointer-events:auto;position:relative;overflow:hidden;display:flex;align-items:flex-start;gap:10px;background:#fff;border:1px solid #d1fae5;box-shadow:0 8px 24px rgba(0,0,0,.12);border-radius:14px;padding:10px 12px;width:320px;animation:slideIn .35s ease forwards;';

            toast.innerHTML = `
        <div class="absolute inset-y-0 left-0 w-1 bg-emerald-500"></div>

        <div class="flex-shrink-0 ml-1">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
            </div>
        </div>

        <div class="flex-1 min-w-0 pr-1">
            <h3 class="text-[13px] sm:text-sm font-extrabold text-gray-800 leading-tight">Success</h3>
            <p class="text-[12px] sm:text-[13px] text-gray-500 leading-4 mt-0.5 break-words">${normalizedMessage}</p>
        </div>

        <button
            type="button"
            class="flex-shrink-0 w-7 h-7 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
            onclick="this.parentElement.remove()"
        >
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.transition = 'all 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        function showErrorToast(message) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const normalizedMessage = String(message || '').trim();

            const existing = Array.from(container.querySelectorAll('[data-toast-type="error"]'))
                .find(toast => toast.dataset.toastMessage === normalizedMessage);

            if (existing) return;

            const toast = document.createElement('div');
            toast.dataset.toastType = 'error';
            toast.dataset.toastMessage = normalizedMessage;

            toast.style.cssText =
                'pointer-events:auto;position:relative;overflow:hidden;display:flex;align-items:flex-start;gap:10px;background:#fff;border:1px solid #fee2e2;box-shadow:0 8px 24px rgba(0,0,0,.12);border-radius:14px;padding:10px 12px;width:320px;animation:slideIn .35s ease forwards;';

            toast.innerHTML = `
        <div class="absolute inset-y-0 left-0 w-1 bg-red-500"></div>

        <div class="flex-shrink-0 ml-1">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                <i class="fa-solid fa-circle-exclamation text-red-500 text-base"></i>
            </div>
        </div>

        <div class="flex-1 min-w-0 pr-1">
            <h3 class="text-[13px] sm:text-sm font-extrabold text-gray-800 leading-tight">Error</h3>
            <p class="text-[12px] sm:text-[13px] text-gray-500 leading-4 mt-0.5 break-words">${normalizedMessage}</p>
        </div>

        <button
            type="button"
            class="flex-shrink-0 w-7 h-7 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
            onclick="this.parentElement.remove()"
        >
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.transition = 'all 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        function setRoleFilter(el, role) {
            document.querySelectorAll('#umFilterForm [data-role]').forEach(function(b) {
                b.classList.remove('active');
            });
            el.classList.add('active');

            umState.role = (role === 'all' || role === '') ? '' : role;
            umState.page = 1;
            umFetch();
        }

        document.addEventListener('DOMContentLoaded', function() {
            applyTheme(localStorage.getItem('theme') || 'light');
            initUmViewToggle();
            document.querySelectorAll('.theme-option').forEach(function(o) {
                o.addEventListener('click', function(e) {
                    e.stopPropagation();
                    applyTheme(o.getAttribute('data-theme'));
                });
            });

            umRenderPagebar({
                total: {{ $users->total() }},
                from: {{ $users->firstItem() ?? 0 }},
                to: {{ $users->lastItem() ?? 0 }},
                current_page: {{ $users->currentPage() }},
                last_page: {{ $users->lastPage() }},
                per_page: {{ $users->perPage() }},
            });

            var searchInput = document.getElementById('umSearch');
            if (searchInput) {
                toggleSearchClear(searchInput);
                searchInput.addEventListener('input', function() {
                    toggleSearchClear(this);
                    clearTimeout(umSearchTimer);
                    var val = this.value;
                    umSearchTimer = setTimeout(function() {
                        umState.search = val;
                        umState.page = 1;
                        umFetch(true);
                    }, 350);
                });
            }

            var statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    umState.status = this.value;
                    umState.page = 1;
                    umFetch();
                });
            }

            var toggleForm = document.getElementById('toggleConfirmForm');
            if (toggleForm) {
                toggleForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var form = this;
                    var url = form.action;
                    var btn = document.getElementById('toggleConfirmBtn');
                    var originalHtml = btn.dataset.originalHtml || btn.innerHTML;

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing…';

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: '_method=PATCH&_token={{ csrf_token() }}'
                        })
                        .then(function(res) {
                            return res.json().then(function(data) {
                                return {
                                    ok: res.ok,
                                    data: data
                                };
                            });
                        })
                        .then(function(result) {
                            if (result.ok && result.data.success) {
                                closeAllModals();
                                showSuccessToast(result.data.message);
                                umFetch(true);
                            } else {
                                showErrorToast(result.data.message || 'Something went wrong.');
                            }
                        })
                        .catch(function() {
                            showErrorToast('Something went wrong. Please try again.');
                        })
                        .finally(function() {
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        });
                });
            }

            var editForm = document.getElementById('editForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var form = this;
                    var url = form.action;
                    var submitBtn = form.querySelector('button[type="submit"]');
                    var originalHtml = submitBtn.innerHTML;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

                    var params = new URLSearchParams();
                    params.append('_token', '{{ csrf_token() }}');
                    params.append('_method', 'PUT');
                    params.append('name', document.getElementById('editName').value);
                    params.append('email', document.getElementById('editEmail').value);
                    params.append('role_id', document.getElementById('editRole').value);
                    params.append('status', form.querySelector('input[name="status"]:checked')?.value ?? '');

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: params.toString()
                        })
                        .then(function(res) {
                            return res.json().then(function(data) {
                                return {
                                    ok: res.ok,
                                    status: res.status,
                                    data: data
                                };
                            });
                        })
                        .then(function(result) {
                            if (result.status === 422 && result.data.errors) {
                                var msgs = Object.values(result.data.errors).flat().join(' ');
                                showErrorToast(msgs);
                            } else if (result.ok && result.data.success) {
                                closeAllModals();
                                showSuccessToast(result.data.message || 'User updated successfully.');
                                umFetch(true);
                            } else {
                                showErrorToast(result.data.message || 'Something went wrong.');
                            }
                        })
                        .catch(function() {
                            showErrorToast('Something went wrong. Please try again.');
                        })
                        .finally(function() {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        });
                });
            }

            var resetForm = document.getElementById('resetForm');
            if (resetForm) {
                resetForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var form = this;
                    var url = form.action;
                    var submitBtn = form.querySelector('button[type="submit"]');
                    var originalHtml = submitBtn.innerHTML;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resetting…';

                    var formData = new FormData(form);

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData
                        })
                        .then(function(res) {
                            return res.json().then(function(data) {
                                return {
                                    ok: res.ok,
                                    status: res.status,
                                    data: data
                                };
                            });
                        })
                        .then(function(result) {
                            if (result.status === 422 && result.data.errors) {
                                var msgs = Object.values(result.data.errors).flat().join(' ');
                                showErrorToast(msgs);
                            } else if (result.ok && result.data.success) {
                                closeAllModals();
                                showSuccessToast(result.data.message || 'Password reset successfully.');
                                document.getElementById('resetPassword').value = '';
                                document.getElementById('resetPasswordConf').value = '';
                            } else {
                                showErrorToast(result.data.message || 'Something went wrong.');
                            }
                        })
                        .catch(function() {
                            showErrorToast('Something went wrong. Please try again.');
                        })
                        .finally(function() {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        });
                });
            }

            // ─── Voice input for Add New User form fields ───────────────────────────
            (function initAddFormVoice() {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition) return;

                const inputs = [
                    { inputId: 'addNameInput',  micId: 'addNameMicBtn',  statusId: 'addNameVoiceStatus'  },
                    { inputId: 'addEmailInput', micId: 'addEmailMicBtn', statusId: 'addEmailVoiceStatus' }
                ];

                inputs.forEach(function(config) {
                    const input   = document.getElementById(config.inputId);
                    const micBtn  = document.getElementById(config.micId);
                    const status  = document.getElementById(config.statusId);

                    if (!input || !micBtn || !status) return;

                    let listening   = false;
                    let recognition = null;
                    let manualStop  = false;

                    // ── helpers ──────────────────────────────────────────────────────
                    const setStatus = function(text, state) {
                        status.textContent = text;
                        status.className   = 'patient-voice-status';
                        if (state) status.classList.add('is-' + state);
                        if (text) {
                            status.classList.remove('hidden');
                        } else {
                            status.classList.add('hidden');
                        }
                    };

                    const hideStatus = function(delay) {
                        window.setTimeout(function() {
                            status.classList.add('hidden');
                        }, delay || 0);
                    };

                    const setMicState = function(isActive) {
                        micBtn.classList.toggle('mic-active', isActive);
                        micBtn.innerHTML = isActive
                            ? '<i class="fa-solid fa-stop"></i>'
                            : '<i class="fa-solid fa-microphone"></i>';
                    };

                    const stopListeningNow = function() {
                        manualStop  = true;
                        listening   = false;
                        setMicState(false);
                        setStatus('Voice captured.', 'success');
                        hideStatus(1200);
                        if (recognition) {
                            try { recognition.abort(); } catch (e) {
                                try { recognition.stop(); } catch (err) {}
                            }
                        }
                    };

                    // ── recognition factory ──────────────────────────────────────────
                    const createRecognition = function() {
                        const r = new SpeechRecognition();
                        r.lang            = 'en-US';
                        r.continuous      = false;
                        r.interimResults  = true;
                        r.maxAlternatives = 1;

                        let sawSpeech     = false;
                        let timeoutId     = null;
                        const LISTEN_TIMEOUT = 6000;

                        const clearTimeout_ = function() {
                            if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                        };

                        r.onstart = function() {
                            timeoutId = window.setTimeout(function() {
                                if (listening && !sawSpeech) {
                                    try { r.stop(); } catch (e) {}
                                }
                            }, LISTEN_TIMEOUT);
                        };

                        r.onspeechend = function() {
                            clearTimeout_();
                            try { r.stop(); } catch (e) {}
                        };

                        r.onresult = function(event) {
                            let transcript = '';
                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                const result = event.results[i];
                                const chunk  = (result && result[0] && result[0].transcript
                                    ? result[0].transcript : '').trim();
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
                                input.value = transcript;
                                input.dispatchEvent(new Event('input',  { bubbles: true }));
                                input.dispatchEvent(new Event('change', { bubbles: true }));
                                setStatus('Listening...', 'listening');
                            }
                        };

                        r.onerror = function() {
                            clearTimeout_();
                            listening = false;
                            if (manualStop) { manualStop = false; return; }
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        };

                        r.onend = function() {
                            clearTimeout_();
                            if (manualStop) {
                                manualStop = false;
                                listening  = false;
                                setMicState(false);
                                return;
                            }
                            const hadSpeech = sawSpeech || !!input.value.trim();
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

                    // ── click handler ────────────────────────────────────────────────
                    micBtn.addEventListener('click', function() {
                        if (listening && recognition) { stopListeningNow(); return; }

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
                });
            })();

            // ─── Voice input for search bar ─────────────────────────────────────────
            (function() {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const input   = document.getElementById('umSearch');
                const micBtn  = document.getElementById('umMicToggleBtn');
                const status  = document.getElementById('umVoiceStatus');

                if (!input || !micBtn || !status || !SpeechRecognition) {
                    if (micBtn) {
                        micBtn.disabled = true;
                        micBtn.setAttribute('aria-disabled', 'true');
                    }
                    return;
                }

                window.umRecognition = null;
                window.umListening   = false;
                window.umManualStop  = false;

                const setStatus = function(text, state) {
                    status.textContent = text;
                    status.className   = 'patient-voice-status';
                    if (state) status.classList.add('is-' + state);
                    status.classList.remove('hidden');
                };

                const hideStatus = function(delay) {
                    window.setTimeout(function() {
                        status.classList.add('hidden');
                    }, delay || 0);
                };

                const setMicState = function(isActive) {
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                };

                const stopListeningNow = function() {
                    window.umManualStop = true;
                    window.umListening  = false;
                    setMicState(false);
                    setStatus('Voice input stopped.', 'success');
                    hideStatus(1200);
                    if (window.umRecognition) {
                        try { window.umRecognition.abort(); } catch (e) {
                            try { window.umRecognition.stop(); } catch (err) {}
                        }
                    }
                };

                const createRecognition = function() {
                    const recognition = new SpeechRecognition();
                    recognition.lang            = 'en-US';
                    recognition.continuous      = false;
                    recognition.interimResults  = true;
                    recognition.maxAlternatives = 1;

                    let sawSpeech       = false;
                    let listenTimeoutId = null;
                    const LISTEN_TIMEOUT = 6000;

                    const clearListenTimeout = function() {
                        if (listenTimeoutId) { clearTimeout(listenTimeoutId); listenTimeoutId = null; }
                    };

                    recognition.onstart = function() {
                        listenTimeoutId = window.setTimeout(function() {
                            if (window.umListening && !sawSpeech) {
                                try { recognition.stop(); } catch (e) {}
                            }
                        }, LISTEN_TIMEOUT);
                    };

                    recognition.onspeechend = function() {
                        clearListenTimeout();
                        try { recognition.stop(); } catch (e) {}
                    };

                    recognition.onresult = function(event) {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const result = event.results[i];
                            const chunk  = (result && result[0] && result[0].transcript
                                ? result[0].transcript : '').trim();
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
                            clearListenTimeout();
                            input.value = transcript;
                            input.dispatchEvent(new Event('input',  { bubbles: true }));
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                            setStatus('Listening...', 'listening');
                        }
                    };

                    recognition.onerror = function() {
                        clearListenTimeout();
                        window.umListening = false;
                        if (window.umManualStop) { window.umManualStop = false; return; }
                        setMicState(false);
                        setStatus("Didn't catch that. Try again.", 'error');
                        hideStatus(2500);
                    };

                    recognition.onend = function() {
                        clearListenTimeout();
                        if (window.umManualStop) {
                            window.umManualStop = false;
                            window.umListening  = false;
                            setMicState(false);
                            return;
                        }
                        const hadSpeech = sawSpeech || !!input.value.trim();
                        window.umListening = false;
                        setMicState(false);
                        if (hadSpeech) {
                            setStatus('Voice captured.', 'success');
                            hideStatus(2200);
                        } else {
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                    };

                    return recognition;
                };

                micBtn.addEventListener('click', function() {
                    if (window.umListening && window.umRecognition) { stopListeningNow(); return; }

                    window.umRecognition = createRecognition();
                    try {
                        window.umRecognition.start();
                    } catch (error) {
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                        setMicState(false);
                        window.umListening = false;
                        return;
                    }
                    window.umListening = true;
                    setMicState(true);
                    setStatus('Listening...', 'listening');
                });
            })();
        });

        window.addEventListener('resize', function() {
            applyUmView(getPreferredUmView(), false);
        });
    </script>
@endsection