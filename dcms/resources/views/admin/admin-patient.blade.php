@extends('layouts.admin')

@section('title', 'Patient List | PUP Taguig Dental Clinic')

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

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(10px);
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
            align-items: flex-start;
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

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.15rem 1.2rem;
            border: 1px solid rgba(0, 0, 0, .05);
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            transition: transform .2s, box-shadow .2s;
            position: relative;
            overflow: hidden;
            animation: fadeSlideUp .4s ease both;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, .1);
        }

        .stat-card-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .85rem;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .stat-badge {
            font-size: .65rem;
            font-weight: 700;
            padding: .28rem .65rem;
            border-radius: 999px;
        }

        .stat-label {
            font-size: .66rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #757575;
            margin-bottom: .28rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            color: #1a202c;
            letter-spacing: -.03em;
            margin-bottom: .45rem;
        }

        .stat-footer {
            font-size: .68rem;
            color: #757575;
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, .05);
            box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
            overflow: visible;
            animation: fadeSlideUp .4s ease .2s both;
        }

        .card-header {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
            position: relative;
            z-index: 20;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: .6rem;
            flex-shrink: 0;
        }

        .card-header-right {
            display: flex;
            align-items: center;
            gap: .6rem;
            flex-wrap: wrap;
            flex: 1 1 auto;
            justify-content: flex-end;
            position: relative;
            z-index: 30;
        }

        .card-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--crimson-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--crimson);
            flex-shrink: 0;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1a202c;
        }

        .entry-badge {
            background: #fef2f2;
            color: #8B0000;
            font-size: .68rem;
            font-weight: 700;
            padding: .2rem .55rem;
            border-radius: 999px;
            border: 1px solid #fecaca;
            margin-left: .2rem;
        }

        /* Search bar matches the lighter User Management style */
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
            width: 260px;
            box-shadow: none;
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
            padding: 0;
        }

        .search-wrap input::placeholder {
            color: #B0ABA6;
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
            overflow: visible;
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

        /* ── External circular mic button ── */
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
        }

        .voice-search-mic.external:hover {
            background: #374151;
        }

        .voice-search-mic.external i {
            font-size: 12px;
            line-height: 1;
        }

        .patient-voice-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
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

        /* Active / listening state — red with pulse ring */
        .voice-search-mic.external.mic-active {
            background: #c0392b;
            transform: scale(1.1);
            animation: micPulse 1.2s ease-in-out infinite;
        }

        /* Hide any mic injected inside the search-wrap */
        .search-wrap .voice-search-mic,
        .search-wrap [data-voice-trigger] {
            display: none !important;
        }

        .search-wrap.voice-search-wrap .voice-search-mic i {
            font-size: 13px;
            line-height: 1;
        }

        .search-wrap.voice-search-wrap [data-voice-status] {
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

        .tab-strip {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: .8rem;
            margin: 1rem 0 1.1rem;
        }

        .tab-btn {
            position: relative;
            background: #fff;
            border: 1.5px solid #E5E7EB;
            border-radius: 16px;
            padding: 14px 12px 12px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            cursor: pointer;
            transition: box-shadow .2s ease, transform .2s ease, border-color .2s;
            overflow: hidden;
        }

        .tab-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
            border-color: transparent;
        }

        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            border-radius: 0 0 16px 16px;
            opacity: 0;
            transition: opacity .2s;
        }

        .tab-btn.tab-active {
            border-color: transparent;
            box-shadow: 0 6px 24px rgba(0, 0, 0, .10);
            transform: translateY(-2px);
        }

        .tab-btn.tab-active::after {
            opacity: 1;
        }

        .tab-top-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            width: 100%;
            gap: 6px;
        }

        .tab-icon-wrap {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .tab-count {
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -1px;
            color: #111827;
        }

        .tab-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .5px;
            color: #6B7280;
            text-transform: uppercase;
            margin-top: 5px;
            display: block;
        }

        .tab-today .tab-icon-wrap {
            background: #EFF6FF;
            color: #2563EB;
        }

        .tab-upcoming .tab-icon-wrap {
            background: #FFF7ED;
            color: #EA580C;
        }

        .tab-rescheduled .tab-icon-wrap {
            background: #fefce8;
            color: #ca8a04;
        }

        .tab-cancelled .tab-icon-wrap {
            background: #ffecec;
            color: #df0606;
        }

        .tab-completed .tab-icon-wrap {
            background: #F0FDF4;
            color: #16A34A;
        }

        .tab-all .tab-icon-wrap {
            background: #FEF2F2;
            color: #8B0000;
        }

        .tab-today::after {
            background: #2563EB;
        }

        .tab-upcoming::after {
            background: #EA580C;
        }

        .tab-rescheduled::after {
            background: #ca8a04;
        }

        .tab-cancelled::after {
            background: #df0606;
        }

        .tab-completed::after {
            background: #16A34A;
        }

        .tab-all::after {
            background: #8B0000;
        }

        .patient-list-wrap {
            padding: 1rem;
        }

        .patient-card {
            position: relative;
            background: #fff;
            border: 1.5px solid #E5E7EB;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s, transform .2s;
            animation: cardIn .4s ease both;
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .patient-card + .patient-card {
            margin-top: .8rem;
        }

        .patient-card:nth-child(even) {
            background: #FDFAF8;
        }

        .patient-card:hover {
            border-color: #D1D5DB;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .09);
            transform: translateY(-2px);
        }

        .patient-card .accent-bar {
            position: absolute;
            left: 0;
            top: 14px;
            bottom: 14px;
            width: 4px;
            border-radius: 0 4px 4px 0;
        }

        .accent-upcoming {
            background: linear-gradient(180deg, #E64A19, #BF360C);
        }

        .accent-today {
            background: linear-gradient(180deg, #1E88E5, #1565C0);
        }

        .accent-rescheduled {
            background: linear-gradient(180deg, #ca8a04, #92400e);
        }

        .accent-cancelled {
            background: linear-gradient(180deg, #E53935, #B71C1C);
        }

        .accent-completed {
            background: linear-gradient(180deg, #388E3C, #1B5E20);
        }

        .accent-default {
            background: linear-gradient(180deg, #9CA3AF, #6B7280);
        }

        .patient-card-body {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            padding: 1rem 1.2rem 1rem 2rem;
        }

        .patient-avatar {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid #f3f4f6;
            flex-shrink: 0;
        }

        .patient-main {
            width: 220px;
            flex-shrink: 0;
        }

        .patient-name {
            font-weight: 700;
            font-size: .95rem;
            color: #111827;
            line-height: 1.25;
        }

        .patient-id {
            display: inline-block;
            margin-top: .45rem;
            padding: .25rem .55rem;
            border-radius: 999px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: .68rem;
            font-weight: 700;
        }

        .divider-y {
            width: 1px;
            height: 42px;
            background: #E5E7EB;
            flex-shrink: 0;
        }

        .detail-box {
            display: flex;
            align-items: flex-start;
            gap: .8rem;
            min-width: 0;
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .detail-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #9ca3af;
            font-weight: 700;
            margin-bottom: .2rem;
        }

        .detail-value {
            font-size: .85rem;
            font-weight: 700;
            color: #111827;
        }

        .detail-sub {
            font-size: .74rem;
            color: #6b7280;
            margin-top: .15rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 6px;
        }

        .status-pill .pill-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .pill-today {
            background: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
        }

        .pill-upcoming {
            background: #FFF7ED;
            color: #C2410C;
            border: 1px solid #FED7AA;
        }

        .pill-rescheduled {
            background: #FEFCE8;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .pill-cancelled {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        .pill-completed {
            background: #F0FDF4;
            color: #15803D;
            border: 1px solid #BBF7D0;
        }

        .pill-default {
            background: #F9FAFB;
            color: #374151;
            border: 1px solid #E5E7EB;
        }

        .card-arrow-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #F3F4F6;
            border: 1.5px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9CA3AF;
            font-size: 14px;
            flex-shrink: 0;
            transition: all .2s;
            margin-left: auto;
        }

        .patient-card:hover .card-arrow-btn {
            background: #8B0000;
            border-color: #8B0000;
            color: #fff;
            box-shadow: 0 4px 12px rgba(139, 0, 0, .3);
        }

        .empty-state {
            display: none;
            padding: 3.5rem 1rem;
            text-align: center;
        }

        .empty-state.visible {
            display: block;
        }

        .pagebar {
            padding: .85rem 1.2rem;
            border-top: 1px solid #f3f4f6;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .pagebar-info {
            font-size: .73rem;
            color: #757575;
            font-weight: 500;
        }

        .pagebar-info strong {
            color: #333333;
        }

        .filter-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 1rem;
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
        }

        .filter-modal-body {
            overflow-y: auto;
            padding: 1.25rem;
        }

        .radio-red {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border: 2px solid #8B0000;
            border-radius: 9999px;
            display: inline-grid;
            place-content: center;
            background: #fff;
            flex-shrink: 0;
        }

        .radio-red::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            transform: scale(0);
            transition: transform 120ms ease-in-out;
            background: #8B0000;
        }

        .radio-red:checked::before {
            transform: scale(1);
        }

        /* DARK MODE - GLASS TAB STAT CARDS */
        [data-theme="dark"] .tab-btn {
            background:
                radial-gradient(circle at bottom right, rgba(255,255,255,.07), transparent 42%),
                linear-gradient(135deg, rgba(22, 27, 34, .68), rgba(17, 24, 39, .50)) !important;
            border: 1px solid rgba(255,255,255,.10);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.10),
                0 0 0 1px rgba(255,255,255,.025),
                0 8px 18px rgba(0,0,0,.16);
        }

        [data-theme="dark"] .tab-btn:hover,
        [data-theme="dark"] .tab-btn.tab-active {
            border-color: rgba(252,165,165,.24);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.13),
                0 0 18px rgba(252,165,165,.08),
                0 12px 24px rgba(0,0,0,.20);
        }

        [data-theme="dark"] .tab-count {
            color: #f3f4f6 !important;
        }

        [data-theme="dark"] .tab-label {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .tab-icon-wrap {
            background: rgba(255,255,255,.10) !important;
            border: 1px solid rgba(255,255,255,.12);
        }

        [data-theme="dark"] .card-title,
        [data-theme="dark"] .patient-name,
        [data-theme="dark"] .patient-grid-name {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .card,
        [data-theme="dark"] .patient-card,
        [data-theme="dark"] .filter-modal {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .card-header,
        [data-theme="dark"] .pagebar {
            background: #0d1117 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .detail-value,
        [data-theme="dark"] .stat-value {
            color: #f3f4f6 !important;
        }

        [data-theme="dark"] .detail-sub,
        [data-theme="dark"] .detail-label,
        [data-theme="dark"] .pagebar-info,
        [data-theme="dark"] .patient-id {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .search-wrap,
        [data-theme="dark"] .filter-btn {
            background: #0d1117;
            border-color: #21262d;
        }

        [data-theme="dark"] .search-wrap input {
            color: #f3f4f6;
        }

        [data-theme="dark"] .filter-modal-body input[type="date"] {
            background: #0d1117;
            color: #f3f4f6;
            border-color: #21262d;
        }

        [data-theme="dark"] .card-header-icon {
            background: rgba(252, 165, 165, 0.10) !important;
            border: 1px solid rgba(252, 165, 165, 0.15);
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .detail-icon {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.08);
        }

        [data-theme="dark"] .patient-id,
        [data-theme="dark"] .patient-grid-id {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.08);
            color: #9ca3af !important;
        }

        [data-theme="dark"] .status-pill {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.10) !important;
        }

        [data-theme="dark"] .card-arrow-btn {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
        }

        [data-theme="dark"] .patient-grid-card {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .patient-grid-card:hover {
            border-color: rgba(252,165,165,.24) !important;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.08),
                0 0 18px rgba(252,165,165,.08),
                0 12px 24px rgba(0,0,0,.22);
        }

        [data-theme="dark"] .patient-grid-id {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .patient-avatar {
            border-color: rgba(255,255,255,.10) !important;
        }

        [data-theme="dark"] .divider-y {
            background: rgba(255,255,255,.08) !important;
        }

        [data-theme="dark"] .tab-icon-wrap,
        [data-theme="dark"] .detail-icon,
        [data-theme="dark"] .card-header-icon,
        [data-theme="dark"] .status-pill,
        [data-theme="dark"] .patient-id,
        [data-theme="dark"] .patient-grid-id,
        [data-theme="dark"] .card-arrow-btn {
            background: rgba(255,255,255,.045) !important;
            border-color: rgba(255,255,255,.075) !important;
        }

        [data-theme="dark"] .view-toggle {
            background: rgba(255,255,255,0.04) !important;
            border-color: rgba(255,255,255,0.08) !important;
        }

        [data-theme="dark"] .view-toggle-btn {
            color: rgba(255,255,255,0.6) !important;
        }

        [data-theme="dark"] .view-toggle-btn.active {
            background: rgba(139, 0, 0, 0.85) !important;
            color: #fff !important;
        }

        [data-theme="dark"] .entry-badge {
            background: rgba(255,255,255,0.06) !important;
            border-color: rgba(255,255,255,0.1) !important;
            color: #FCA5A5 !important;
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
            z-index: 40;
            pointer-events: auto;
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
            position: relative;
            z-index: 41;
            pointer-events: auto;
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

        .patient-view {
            width: 100%;
        }

        .patient-view[hidden] {
            display: none !important;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .patient-grid-card {
            position: relative;
            background: #fff;
            border: 1.5px solid #E5E7EB;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s, transform .2s;
            animation: cardIn .4s ease both;
            display: block;
            text-decoration: none;
            color: inherit;
            min-width: 0;
        }

        .patient-grid-card:hover {
            border-color: #D1D5DB;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .09);
            transform: translateY(-2px);
        }

        .patient-grid-card-body {
            padding: 1rem 1rem 1rem 1.2rem;
            display: flex;
            flex-direction: column;
            gap: .9rem;
            min-width: 0;
        }

        .patient-grid-top {
            display: flex;
            align-items: center;
            gap: .8rem;
            min-width: 0;
        }

        .patient-grid-main {
            min-width: 0;
            flex: 1;
        }

        .patient-grid-name {
            font-weight: 700;
            font-size: .95rem;
            color: #111827;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .patient-grid-id {
            display: inline-block;
            margin-top: .4rem;
            padding: .25rem .55rem;
            border-radius: 999px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: .68rem;
            font-weight: 700;
        }

        .patient-grid-meta {
            display: grid;
            grid-template-columns: 1fr;
            gap: .75rem;
            min-width: 0;
        }

        .patient-grid-actions {
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 1279px) {
            .stat-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .tab-strip {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .patient-card-body {
                flex-wrap: wrap;
                align-items: flex-start;
            }

            .divider-y {
                display: none;
            }

            .patient-main {
                width: calc(100% - 80px);
            }
        }

        @media (max-width: 767px) {
            .page-banner {
                padding: 1.1rem 1.1rem 1.4rem !important;
            }

            .page-title {
                font-size: 1.45rem !important;
            }

            .page-banner-inner {
                flex-direction: column;
            }

            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: .75rem;
            }

            .stat-value {
                font-size: 1.7rem;
            }

            .stat-footer,
            .stat-badge {
                display: none;
            }

            .card-header {
                flex-direction: column;
                align-items: stretch !important;
                padding: .75rem 1rem;
            }

            .card-header-right {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                justify-content: flex-start;
            }

            .search-wrap,
            .filter-btn {
                width: 100%;
                height: 42px;
            }

            .tab-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .patient-list-wrap {
                padding: .75rem;
            }

            .patient-card-body {
                padding: .9rem .95rem .9rem 1.2rem;
                gap: .9rem;
            }

            .patient-main {
                width: 100%;
            }

            .detail-box {
                width: 100%;
            }

            #patientListView {
                display: none !important;
            }

            #patientGridView {
                display: block !important;
            }

            #patientViewToggle {
                display: none !important;
            }
        }

        @media (max-width: 480px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: .55rem;
            }

            .tab-strip {
                grid-template-columns: 1fr 1fr;
                gap: .55rem;
            }
        }
    </style>
@endsection

@section('content')
    @php
        use Carbon\Carbon;

        $appointments = $appointments ?? collect([]);
        $allAppointments = $appointments instanceof \Illuminate\Pagination\AbstractPaginator
            ? collect($appointments->items())
            : collect($appointments);

        $today = Carbon::today()->toDateString();

        $todayCount = $todayCount ?? 0;
        $upcomingCount = $upcomingCount ?? 0;
        $rescheduledCount = $rescheduledCount ?? 0;
        $cancelledCount = $cancelledCount ?? 0;
        $completedCount = $completedCount ?? 0;
        $allCount = $allCount ?? $allAppointments->count();
    @endphp

    <main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-screen">
        <div class="max-w-[1280px] mx-auto">

            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Patient List</h1>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="page-badge">
                            <span class="page-badge-dot"></span>
                            {{ $allCount }} {{ \Illuminate\Support\Str::plural('record', $allCount) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <div class="card-header-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <span class="card-title">Patient Directory</span>
                        <span id="entryBadge" class="entry-badge">
                            {{ $allCount }} {{ \Illuminate\Support\Str::plural('entry', $allCount) }}
                        </span>
                    </div>

                    <div class="card-header-right">
                        <div class="search-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input id="searchInput" class="no-voice" type="text" placeholder="Search patients...">
                        </div>

                        <button type="button" id="searchClearBtn" class="search-clear-btn hidden" title="Clear">Clear</button>

                        <div class="patient-voice-toggle">
                            <button type="button" id="micToggleBtn" class="voice-search-mic external" aria-label="Toggle voice input" aria-pressed="false">
                                <i class="fa-solid fa-microphone"></i>
                            </button>
                            <span id="patientVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                                const input = document.getElementById('searchInput');
                                const micBtn = document.getElementById('micToggleBtn');
                                const status = document.getElementById('patientVoiceStatus');

                                if (!input || !micBtn || !status) return;

                                if (!SpeechRecognition) {
                                    micBtn.disabled = true;
                                    micBtn.setAttribute('aria-disabled', 'true');
                                    return;
                                }

                                let listening = false;
                                let manualStop = false;

                                const setStatus = (text, state) => {
                                    status.textContent = text;
                                    status.className = 'patient-voice-status';
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
                                        } catch (e) {
                                            try { recognition.stop(); } catch (err) {}
                                        }
                                    }
                                };

                                // Create a fresh recognition instance each time to avoid state bugs
                                const createRecognition = () => {
                                    const r = new SpeechRecognition();
                                    r.lang = 'en-US';
                                    r.continuous = false;
                                    r.interimResults = true;
                                    r.maxAlternatives = 1;

                                    let sawSpeech = false;
                                    let timeoutId = null;
                                    const LISTEN_TIMEOUT = 6000; // 6 seconds

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
                                        clearTimeout_(timeoutId);
                                        try { r.stop(); } catch (e) {}
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
                                            clearTimeout_(timeoutId);
                                            input.value = transcript;
                                            input.dispatchEvent(new Event('input', { bubbles: true }));
                                            input.dispatchEvent(new Event('change', { bubbles: true }));

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

                                let recognition = null;

                                micBtn.addEventListener('click', () => {
                                    if (listening && recognition) {
                                        stopListeningNow();
                                        return;
                                    }

                                    // create fresh instance and start
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
                        </script>

                        <button type="button" id="openFilterBtn" class="filter-btn">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Filter</span>
                            <span id="filterDot" class="filter-dot"></span>
                        </button>

                        <div class="view-toggle" id="patientViewToggle">
                            <button type="button" class="view-toggle-btn active" data-view="list" id="patientListViewBtn" title="List view" aria-label="List view">
                                <i class="fa-solid fa-table-list"></i>
                            </button>
                            <button type="button" class="view-toggle-btn" data-view="grid" id="patientGridViewBtn" title="Grid view" aria-label="Grid view">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="tab-strip px-4 pt-4">
                    <button class="tab-btn tab-today tab-active" data-filter="today" type="button">
                        <div class="tab-top-row">
                            <div class="tab-icon-wrap"><i class="fa-solid fa-calendar-days"></i></div>
                            <span class="tab-count">{{ $todayCount }}</span>
                        </div>
                        <span class="tab-label">Scheduled Today</span>
                    </button>

                    <button class="tab-btn tab-upcoming" data-filter="upcoming" type="button">
                        <div class="tab-top-row">
                            <div class="tab-icon-wrap"><i class="fa-solid fa-hourglass-half"></i></div>
                            <span class="tab-count">{{ $upcomingCount }}</span>
                        </div>
                        <span class="tab-label">Upcoming</span>
                    </button>

                    <button class="tab-btn tab-rescheduled" data-filter="rescheduled" type="button">
                        <div class="tab-top-row">
                            <div class="tab-icon-wrap"><i class="fa-solid fa-rotate"></i></div>
                            <span class="tab-count">{{ $rescheduledCount }}</span>
                        </div>
                        <span class="tab-label">Rescheduled</span>
                    </button>

                    <button class="tab-btn tab-cancelled" data-filter="cancelled" type="button">
                        <div class="tab-top-row">
                            <div class="tab-icon-wrap"><i class="fa-solid fa-xmark"></i></div>
                            <span class="tab-count">{{ $cancelledCount }}</span>
                        </div>
                        <span class="tab-label">Cancelled</span>
                    </button>

                    <button class="tab-btn tab-completed" data-filter="completed" type="button">
                        <div class="tab-top-row">
                            <div class="tab-icon-wrap"><i class="fa-solid fa-circle-check"></i></div>
                            <span class="tab-count">{{ $completedCount }}</span>
                        </div>
                        <span class="tab-label">Completed</span>
                    </button>

                    <button class="tab-btn tab-all" data-filter="all" type="button">
                        <div class="tab-top-row">
                            <div class="tab-icon-wrap"><i class="fa-solid fa-clipboard-list"></i></div>
                            <span class="tab-count">{{ $allCount }}</span>
                        </div>
                        <span class="tab-label">All Patients</span>
                    </button>
                </div>

                <div id="patientListWrap" class="patient-list-wrap">
                    @if($allAppointments->count())
                        <div class="patient-view" id="patientListView">
                            @foreach($allAppointments as $appt)
                                @php
                                    $status = strtolower($appt->status ?? '');
                                    $isCancelled = $status === 'cancelled';
                                    $isCompleted = $status === 'completed';
                                    $isRescheduled = $status === 'rescheduled';
                                    $isToday = $appt->appointment_date === $today && !$isCancelled && !$isCompleted;
                                    $isUpcoming = $appt->appointment_date > $today && in_array($status, ['upcoming', 'rescheduled', 'pending', 'confirmed'], true);

                                    $tabClass = $isCancelled ? 'cancelled' : ($isCompleted ? 'completed' : ($isRescheduled ? 'rescheduled' : ($isToday ? 'today' : ($isUpcoming ? 'upcoming' : 'all'))));

                                    $patientName = $appt->patient->name ?? 'Unknown Patient';
                                    $dateLabel = Carbon::parse($appt->appointment_date)->format('d M Y');
                                    $timeLabel = Carbon::parse($appt->appointment_time)->format('g:i A');
                                    $serviceLabel = ($appt->service_type === 'Others') ? ($appt->other_services ?: 'Others') : $appt->service_type;

                                    $accentClass = $isCancelled ? 'accent-cancelled' : ($isCompleted ? 'accent-completed' : ($isRescheduled ? 'accent-rescheduled' : ($isToday ? 'accent-today' : ($isUpcoming ? 'accent-upcoming' : 'accent-default'))));
                                    $iconBg = $isCancelled ? 'background:#FEE2E2;color:#DC2626;' : ($isCompleted ? 'background:#F0FDF4;color:#16A34A;' : ($isRescheduled ? 'background:#FEFCE8;color:#A16207;' : ($isToday ? 'background:#EFF6FF;color:#2563EB;' : ($isUpcoming ? 'background:#FFF7ED;color:#EA580C;' : 'background:#F3F4F6;color:#6B7280;'))));
                                    $pillClass = $isCancelled ? 'pill-cancelled' : ($isCompleted ? 'pill-completed' : ($isRescheduled ? 'pill-rescheduled' : ($isToday ? 'pill-today' : ($isUpcoming ? 'pill-upcoming' : 'pill-default'))));
                                    $pillText = $isCancelled ? 'Cancelled' : ($isCompleted ? 'Completed' : ($isRescheduled ? 'Rescheduled' : ($isToday ? 'Appointment Today' : ($isUpcoming ? ($status === 'upcoming' ? 'Upcoming' : 'Upcoming · '.ucfirst($status)) : ucfirst($status ?: 'Pending')))));
                                @endphp

                                <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                                    class="patient-card patient-item"
                                    data-tab="{{ $tabClass }}"
                                    data-name="{{ strtolower($patientName) }}"
                                    data-service="{{ strtolower($serviceLabel) }}"
                                    data-course="{{ strtolower($appt->patient->course ?? '') }}"
                                    data-year="{{ strtolower($appt->patient->year_level ?? '') }}"
                                    data-section="{{ strtolower($appt->patient->section ?? '') }}"
                                    data-department="{{ strtolower($appt->patient->department ?? '') }}"
                                    data-date="{{ $appt->appointment_date }}"
                                    data-record-key="appt-{{ $appt->id }}"
                                    data-view-type="list">

                                    <div class="accent-bar {{ $accentClass }}"></div>

                                    <div class="patient-card-body">
                                        <img
                                            src="{{ $appt->patient->profile_image ? asset('storage/'.$appt->patient->profile_image) : 'https://ui-avatars.com/api/?name='.urlencode($patientName).'&background=660000&color=FFFFFF&rounded=true&size=128' }}"
                                            alt="{{ $patientName }}"
                                            class="patient-avatar">

                                        <div class="patient-main">
                                            <div class="patient-name">{{ $patientName }}</div>
                                            <span class="patient-id">ID #{{ $appt->patient_id }}</span>
                                        </div>

                                        <div class="divider-y"></div>

                                        <div class="detail-box" style="width: 210px; flex-shrink:0;">
                                            <div class="detail-icon" style="background:#EFF6FF;color:#2563EB;">
                                                <i class="fa-regular fa-calendar"></i>
                                            </div>
                                            <div>
                                                <div class="detail-label">Date & Time</div>
                                                <div class="detail-value">{{ $dateLabel }}</div>
                                                <div class="detail-sub">{{ $timeLabel }}</div>
                                            </div>
                                        </div>

                                        <div class="divider-y"></div>

                                        <div class="detail-box" style="flex:1; min-width:220px;">
                                            <div class="detail-icon" style="{{ $iconBg }}">
                                                <i class="fa-solid fa-tooth"></i>
                                            </div>
                                            <div>
                                                <div class="detail-label">Service</div>
                                                <div class="detail-value">{{ $serviceLabel }}</div>
                                                <span class="status-pill {{ $pillClass }}">
                                                    <span class="pill-dot"></span>{{ $pillText }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="card-arrow-btn">
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <div class="patient-view" id="patientGridView" hidden>
                            <div class="patient-grid">
                                @foreach($allAppointments as $appt)
                                    @php
                                        $status = strtolower($appt->status ?? '');
                                        $isCancelled = $status === 'cancelled';
                                        $isCompleted = $status === 'completed';
                                        $isRescheduled = $status === 'rescheduled';
                                        $isToday = $appt->appointment_date === $today && !$isCancelled && !$isCompleted;
                                        $isUpcoming = $appt->appointment_date > $today && in_array($status, ['upcoming', 'rescheduled', 'pending', 'confirmed'], true);

                                        $tabClass = $isCancelled ? 'cancelled' : ($isCompleted ? 'completed' : ($isRescheduled ? 'rescheduled' : ($isToday ? 'today' : ($isUpcoming ? 'upcoming' : 'all'))));

                                        $patientName = $appt->patient->name ?? 'Unknown Patient';
                                        $dateLabel = Carbon::parse($appt->appointment_date)->format('d M Y');
                                        $timeLabel = Carbon::parse($appt->appointment_time)->format('g:i A');
                                        $serviceLabel = ($appt->service_type === 'Others') ? ($appt->other_services ?: 'Others') : $appt->service_type;

                                        $accentClass = $isCancelled ? 'accent-cancelled' : ($isCompleted ? 'accent-completed' : ($isRescheduled ? 'accent-rescheduled' : ($isToday ? 'accent-today' : ($isUpcoming ? 'accent-upcoming' : 'accent-default'))));
                                        $pillClass = $isCancelled ? 'pill-cancelled' : ($isCompleted ? 'pill-completed' : ($isRescheduled ? 'pill-rescheduled' : ($isToday ? 'pill-today' : ($isUpcoming ? 'pill-upcoming' : 'pill-default'))));
                                        $pillText = $isCancelled ? 'Cancelled' : ($isCompleted ? 'Completed' : ($isRescheduled ? 'Rescheduled' : ($isToday ? 'Appointment Today' : ($isUpcoming ? ($status === 'upcoming' ? 'Upcoming' : 'Upcoming · '.ucfirst($status)) : ucfirst($status ?: 'Pending')))));
                                    @endphp

                                    <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                                        class="patient-grid-card patient-item"
                                        data-tab="{{ $tabClass }}"
                                        data-name="{{ strtolower($patientName) }}"
                                        data-service="{{ strtolower($serviceLabel) }}"
                                        data-course="{{ strtolower($appt->patient->course ?? '') }}"
                                        data-year="{{ strtolower($appt->patient->year_level ?? '') }}"
                                        data-section="{{ strtolower($appt->patient->section ?? '') }}"
                                        data-department="{{ strtolower($appt->patient->department ?? '') }}"
                                        data-date="{{ $appt->appointment_date }}"
                                        data-record-key="appt-{{ $appt->id }}"
                                        data-view-type="grid">

                                        <div class="accent-bar {{ $accentClass }}"></div>

                                        <div class="patient-grid-card-body">
                                            <div class="patient-grid-top">
                                                <img
                                                    src="{{ $appt->patient->profile_image ? asset('storage/'.$appt->patient->profile_image) : 'https://ui-avatars.com/api/?name='.urlencode($patientName).'&background=660000&color=FFFFFF&rounded=true&size=128' }}"
                                                    alt="{{ $patientName }}"
                                                    class="patient-avatar">

                                                <div class="patient-grid-main">
                                                    <div class="patient-grid-name">{{ $patientName }}</div>
                                                    <span class="patient-grid-id">ID #{{ $appt->patient_id }}</span>
                                                </div>
                                            </div>

                                            <div class="patient-grid-meta">
                                                <div>
                                                    <div class="detail-label">Date & Time</div>
                                                    <div class="detail-value">{{ $dateLabel }}</div>
                                                    <div class="detail-sub">{{ $timeLabel }}</div>
                                                </div>

                                                <div>
                                                    <div class="detail-label">Service</div>
                                                    <div class="detail-value">{{ $serviceLabel }}</div>
                                                    <span class="status-pill {{ $pillClass }}">
                                                        <span class="pill-dot"></span>{{ $pillText }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="patient-grid-actions">
                                                <div class="card-arrow-btn">
                                                    <i class="fa-solid fa-arrow-right"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div id="serverEmptyState" class="empty-state visible">
                            <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-users text-3xl text-gray-300"></i>
                            </div>
                            <p class="text-gray-500 font-semibold text-base">No appointments found</p>
                            <p class="text-gray-400 text-sm mt-1">There are no patient records to display yet.</p>
                        </div>
                    @endif

                    <div id="clientEmptyState" class="empty-state">
                        <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-filter-circle-xmark text-3xl text-gray-300"></i>
                        </div>
                        <p class="text-gray-500 font-semibold text-base">No matching records found</p>
                        <p class="text-gray-400 text-sm mt-1">Try changing the search, tab, or filter options.</p>
                    </div>
                </div>

                <div class="pagebar">
                    <span class="pagebar-info">
                        Showing <strong id="visibleCount">{{ $allCount }}</strong> of <strong id="totalCount">{{ $allCount }}</strong> entries
                    </span>
                </div>
            </div>
        </div>
    </main>

    <div id="filterModalBackdrop" class="filter-modal-backdrop">
        <div class="filter-modal">
            <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-sliders text-[#8B0000]"></i>
                    <h2 class="text-lg font-semibold text-[#8B0000]">Filter Patients</h2>
                </div>
                <button type="button" id="closeFilterModal" class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 hover:bg-gray-200">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="filter-modal-body space-y-5">
                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Sort</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 text-sm text-gray-700">
                            <input type="radio" name="sort" value="az" class="radio-red"> A-Z
                        </label>
                        <label class="flex items-center gap-3 text-sm text-gray-700">
                            <input type="radio" name="sort" value="za" class="radio-red"> Z-A
                        </label>
                    </div>
                </div>

                <div class="h-px bg-gray-200"></div>

                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Date Range</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm text-gray-700">From</label>
                            <input type="date" id="fromDate" class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm text-gray-700">To</label>
                            <input type="date" id="toDate" class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <div class="h-px bg-gray-200"></div>

                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Course</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-x-6 gap-y-3 text-sm text-gray-700">
                        @foreach(['BSIT','BSECE','BSBA - HRM','BSED - ENG','BSOA','BSPSYCH','DIT','BSME','BSBA - MM','BSED - MATH','DOMT'] as $course)
                            <label class="flex items-center gap-2">
                                <input type="radio" name="course" value="{{ strtolower($course) }}" class="radio-red"> {{ $course }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="h-px bg-gray-200"></div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <p class="text-sm text-gray-500">Year</p>
                        <div class="grid grid-cols-2 gap-y-3 text-sm text-gray-700">
                            @foreach(['1st Year','2nd Year','3rd Year','4th Year'] as $year)
                                <label class="flex items-center gap-3">
                                    <input type="radio" name="year" value="{{ strtolower($year) }}" class="radio-red"> {{ $year }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-sm text-gray-500">Section</p>
                        <div class="space-y-3 text-sm text-gray-700">
                            <label class="flex items-center gap-3">
                                <input type="radio" name="section" value="1" class="radio-red"> 1
                            </label>
                            <label class="flex items-center gap-3">
                                <input type="radio" name="section" value="2" class="radio-red"> 2
                            </label>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-gray-200"></div>

                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Department</p>
                    <div class="flex flex-wrap gap-x-8 gap-y-3 text-sm text-gray-700">
                        @foreach(['Administrative','Faculty','Dependent'] as $department)
                            <label class="flex items-center gap-3">
                                <input type="radio" name="department" value="{{ strtolower($department) }}" class="radio-red"> {{ $department }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="px-5 py-4 flex items-center justify-between border-t border-gray-200 bg-white">
                <button type="button" id="clearFiltersBtn" class="text-[#8B0000] text-sm font-medium hover:underline">Clear</button>
                <button type="button" id="applyFiltersBtn" class="bg-[#8B0000] text-white px-8 py-2 rounded-md text-sm font-medium shadow hover:bg-[#760000] transition">
                    Apply
                </button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const allCards = Array.from(document.querySelectorAll('.patient-item'));
            const listCards = allCards.filter(card => card.dataset.viewType === 'list');
            const gridCards = allCards.filter(card => card.dataset.viewType === 'grid');

            const searchInput = document.getElementById('searchInput');
            const searchClearBtn = document.getElementById('searchClearBtn');
            const tabButtons = Array.from(document.querySelectorAll('.tab-btn[data-filter]'));
            const visibleCount = document.getElementById('visibleCount');
            const totalCount = document.getElementById('totalCount');
            const clientEmptyState = document.getElementById('clientEmptyState');
            const serverEmptyState = document.getElementById('serverEmptyState');

            const filterModalBackdrop = document.getElementById('filterModalBackdrop');
            const openFilterBtn = document.getElementById('openFilterBtn');
            const closeFilterModal = document.getElementById('closeFilterModal');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            const filterDot = document.getElementById('filterDot');

            const patientListView = document.getElementById('patientListView');
            const patientGridView = document.getElementById('patientGridView');
            const patientListViewBtn = document.getElementById('patientListViewBtn');
            const patientGridViewBtn = document.getElementById('patientGridViewBtn');

            let activeTab = 'today';
            let currentView = getPreferredPatientView();

            let filters = {
                search: '',
                sort: '',
                fromDate: '',
                toDate: '',
                course: '',
                year: '',
                section: '',
                department: ''
            };

            function getPreferredPatientView() {
                if (window.innerWidth <= 767) return 'grid';
                return localStorage.getItem('patientView') || 'list';
            }

            function applyPatientView(view, save = true) {
                if (!patientListView || !patientGridView) return;

                const finalView = window.innerWidth <= 767 ? 'grid' : view;
                currentView = finalView;

                if (finalView === 'grid') {
                    patientListView.setAttribute('hidden', 'hidden');
                    patientGridView.removeAttribute('hidden');
                } else {
                    patientGridView.setAttribute('hidden', 'hidden');
                    patientListView.removeAttribute('hidden');
                }

                if (patientListViewBtn) patientListViewBtn.classList.toggle('active', finalView === 'list');
                if (patientGridViewBtn) patientGridViewBtn.classList.toggle('active', finalView === 'grid');

                if (save && window.innerWidth > 767) {
                    localStorage.setItem('patientView', finalView);
                }
            }

            function updateSearchClear() {
                if (!searchClearBtn || !searchInput) return;
                searchClearBtn.classList.toggle('hidden', searchInput.value.trim().length === 0);
            }

            function hasActiveFilters() {
                return !!filters.sort || !!filters.fromDate || !!filters.toDate || !!filters.course ||
                    !!filters.year || !!filters.section || !!filters.department;
            }

            function updateFilterDot() {
                if (!filterDot || !openFilterBtn) return;
                filterDot.classList.toggle('visible', hasActiveFilters());
                openFilterBtn.classList.toggle('active', hasActiveFilters());
            }

            function cardMatchesFilters(card) {
                if (activeTab !== 'all' && card.dataset.tab !== activeTab) return false;

                if (filters.search) {
                    const q = filters.search.toLowerCase();
                    const matchesSearch =
                        (card.dataset.name || '').includes(q) ||
                        (card.dataset.service || '').includes(q) ||
                        (card.textContent || '').toLowerCase().includes(q);

                    if (!matchesSearch) return false;
                }

                if (filters.course && (card.dataset.course || '') !== filters.course) return false;
                if (filters.year && (card.dataset.year || '') !== filters.year) return false;
                if (filters.section && (card.dataset.section || '') !== filters.section) return false;
                if (filters.department && (card.dataset.department || '') !== filters.department) return false;
                if (filters.fromDate && (card.dataset.date || '') < filters.fromDate) return false;
                if (filters.toDate && (card.dataset.date || '') > filters.toDate) return false;

                return true;
            }

            function applySorting(cards) {
                if (filters.sort === 'az') {
                    cards.sort((a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || ''));
                } else if (filters.sort === 'za') {
                    cards.sort((a, b) => (b.dataset.name || '').localeCompare(a.dataset.name || ''));
                }
                return cards;
            }

            function applyCardFilters() {
                const listWrap = patientListView;
                const gridWrap = patientGridView ? patientGridView.querySelector('.patient-grid') : null;

                allCards.forEach(card => {
                    card.style.display = 'none';
                });

                let filteredListCards = listCards.filter(cardMatchesFilters);
                let filteredGridCards = gridCards.filter(cardMatchesFilters);

                filteredListCards = applySorting(filteredListCards);
                filteredGridCards = applySorting(filteredGridCards);

                if (listWrap) {
                    filteredListCards.forEach(card => {
                        card.style.display = '';
                        listWrap.appendChild(card);
                    });
                }

                if (gridWrap) {
                    filteredGridCards.forEach(card => {
                        card.style.display = '';
                        gridWrap.appendChild(card);
                    });
                }

                const visible = filteredListCards.length;

                if (visibleCount) visibleCount.textContent = visible;
                if (totalCount) totalCount.textContent = listCards.length;

                if (serverEmptyState) {
                    serverEmptyState.classList.remove('visible');
                }

                if (clientEmptyState) {
                    clientEmptyState.classList.toggle('visible', visible === 0 && listCards.length > 0);
                }

                updateFilterDot();
            }

            tabButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    tabButtons.forEach(b => b.classList.remove('tab-active'));
                    this.classList.add('tab-active');
                    activeTab = this.dataset.filter;
                    applyCardFilters();
                });
            });

            if (patientListViewBtn) {
                patientListViewBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    applyPatientView('list', true);
                });
            }

            if (patientGridViewBtn) {
                patientGridViewBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    applyPatientView('grid', true);
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    filters.search = this.value.trim();
                    updateSearchClear();
                    applyCardFilters();
                });
            }

            if (searchClearBtn) {
                searchClearBtn.addEventListener('click', function () {
                    const micBtn = document.getElementById('micToggleBtn');
                    if (micBtn && micBtn.classList.contains('mic-active')) {
                        micBtn.click();
                    }

                    searchInput.value = '';
                    filters.search = '';
                    updateSearchClear();
                    applyCardFilters();
                    document.getElementById('patientVoiceStatus')?.classList.add('hidden');
                    searchInput.focus();
                });
            }

            if (openFilterBtn) {
                openFilterBtn.addEventListener('click', function () {
                    filterModalBackdrop.classList.add('show');
                });
            }

            if (closeFilterModal) {
                closeFilterModal.addEventListener('click', function () {
                    filterModalBackdrop.classList.remove('show');
                });
            }

            if (filterModalBackdrop) {
                filterModalBackdrop.addEventListener('click', function (e) {
                    if (e.target === filterModalBackdrop) {
                        filterModalBackdrop.classList.remove('show');
                    }
                });
            }

            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', function () {
                    filters.sort = document.querySelector('input[name="sort"]:checked')?.value || '';
                    filters.fromDate = document.getElementById('fromDate')?.value || '';
                    filters.toDate = document.getElementById('toDate')?.value || '';
                    filters.course = document.querySelector('input[name="course"]:checked')?.value || '';
                    filters.year = document.querySelector('input[name="year"]:checked')?.value || '';
                    filters.section = document.querySelector('input[name="section"]:checked')?.value || '';
                    filters.department = document.querySelector('input[name="department"]:checked')?.value || '';

                    filterModalBackdrop.classList.remove('show');
                    applyCardFilters();
                });
            }

            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function () {
                    document.querySelectorAll('#filterModalBackdrop input[type="radio"]').forEach(input => {
                        input.checked = false;
                    });

                    const fromDate = document.getElementById('fromDate');
                    const toDate = document.getElementById('toDate');

                    if (fromDate) fromDate.value = '';
                    if (toDate) toDate.value = '';

                    filters.sort = '';
                    filters.fromDate = '';
                    filters.toDate = '';
                    filters.course = '';
                    filters.year = '';
                    filters.section = '';
                    filters.department = '';

                    applyCardFilters();
                });
            }

            window.addEventListener('resize', function () {
                applyPatientView(getPreferredPatientView(), false);
            });

            applyPatientView(currentView, false);
            updateSearchClear();
            applyCardFilters();
        });
    </script>
@endsection