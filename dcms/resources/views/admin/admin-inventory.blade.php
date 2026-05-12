@extends('layouts.admin')

@section('title', 'Inventory | PUP Taguig Dental Clinic')

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

        .page-banner {
            background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 60%, #c0392b 100%);
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
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
            padding: .95rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
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

        /* ── Search bar — matches user management style ── */
        .search-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #FAFAF9;
            border: 1.5px solid #E0DDD8;
            border-radius: 12px;
            padding: 0 14px;
            height: 40px;
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

        /* ── Search row (search + clear + mic) ── */
        .inv-search-row {
            display: flex;
            align-items: center;
            gap: .5rem;
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

        /* ── Voice toggle wrapper (absolute-positioned pill) ── */
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
            box-shadow: 0 6px 18px rgba(75, 85, 99, 0.12);
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

        /* Hide any injected mic inside the search wrap */
        .search-wrap .voice-search-mic,
        .search-wrap [data-voice-trigger] {
            display: none !important;
        }

        .filter-btn,
        .btn-add {
            height: 40px;
            padding: 0 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            white-space: nowrap;
        }

        .filter-btn {
            border: 1.5px solid #E0DDD8;
            background: #FAFAF9;
            color: #6b7280;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: #8B0000;
            color: #8B0000;
            background: #fef2f2;
        }

        .btn-add {
            border: none;
            background: #8B0000;
            color: #fff;
            box-shadow: 0 3px 10px rgba(139, 0, 0, .18);
        }

        .btn-add:hover {
            background: #760000;
        }

        .filter-dot {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 999px;
            background: #8B0000;
            color: #fff;
            border: 2px solid #fff;
            font-size: 10px;
            font-weight: 800;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .filter-dot.visible {
            display: inline-flex;
        }

        .tab-group {
            display: inline-flex;
            align-items: center;
            background: #F8F6F3;
            border: 1px solid #E7E1DA;
            border-radius: 14px;
            padding: 4px;
            gap: 4px;
            flex-shrink: 0;
        }

        .tab-btn {
            min-width: 56px;
            height: 34px;
            padding: 0 14px;
            border-radius: 10px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            color: #9A9490;
            transition: all .18s ease;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: #8B0000;
            background: #f3efea;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #8B0000, #6b0000);
            color: #fff;
            box-shadow: 0 2px 8px rgba(139, 0, 0, .18);
        }

        .inv-view-toggle {
            display: inline-flex;
            align-items: center;
            background: #FAFAF9;
            border: 1.5px solid #E0DDD8;
            border-radius: 12px;
            padding: 3px;
            gap: 3px;
            height: 40px;
            flex-shrink: 0;
        }

        .inv-view-toggle-btn {
            width: 34px;
            height: 32px;
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

        .inv-view-toggle-btn:hover {
            background: #f3f4f6;
            color: #8B0000;
        }

        .inv-view-toggle-btn.active {
            background: #8B0000;
            color: #fff;
            box-shadow: 0 2px 8px rgba(139, 0, 0, .15);
        }

        .inv-view[hidden] {
            display: none !important;
        }

        .inventory-table-wrap {
            overflow-x: auto;
        }

        .inv-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inv-table thead tr {
            background: #FFFFFF;
            border-bottom: 2px solid #EDE9E4;
        }

        .inv-table th {
            padding: 12px 14px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #8B0000;
            white-space: nowrap;
        }

        .inv-table th:last-child,
        .inv-table td:last-child {
            text-align: center;
        }

        .inv-table tbody tr {
            border-bottom: 1px solid #F0ECE8;
            transition: background .15s;
        }

        .inv-table tbody tr:last-child {
            border-bottom: none;
        }

        .inv-table tbody tr:hover {
            background: #FFF8F8;
        }

        .inv-table td {
            padding: 12px 14px;
            font-size: 13px;
            color: #333;
            vertical-align: middle;
        }

        .stock-no {
            font-size: 12px;
            font-weight: 500;
            background: #FFFFFF;
            border: 1px solid #E8E4DE;
            padding: 3px 7px;
            border-radius: 6px;
            color: #7A7370;
            display: inline-block;
            white-space: nowrap;
        }

        .supply-name {
            font-weight: 600;
            color: #1A1614;
        }

        .supply-cat {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 2px 7px;
            border-radius: 5px;
            margin-top: 3px;
        }

        .supply-cat.medicine {
            background: #E3F2FD;
            color: #1565C0;
        }

        .supply-cat.supplies {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .bal-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        @keyframes stockAlertPulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(192, 57, 43, 0.0);
                opacity: 1;
            }
            50% {
                box-shadow: 0 0 0 6px rgba(192, 57, 43, 0.10);
                opacity: .88;
            }
        }

        .bal-chip.alert-blink {
            animation: stockAlertPulse 1.2s ease-in-out infinite;
        }

        .out-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 4px;
            font-size: 11px;
            line-height: 1;
        }

        .bal-chip::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        }

        .bal-chip.ok {
            background: #D4EDDA;
            color: #1A6B34;
        }

        .bal-chip.low {
            background: #FFF3CD;
            color: #92600A;
        }

        .bal-chip.critical {
            background: #FFE5E5;
            color: #C0392B;
        }

        .act-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
        }

        .act-btn.edit {
            background: #FFF0F0;
            color: #8B0000;
        }

        .act-btn.edit:hover {
            background: #8B0000;
            color: #fff;
            transform: scale(1.05);
        }

        .act-btn.delete {
            background: #FFF0F0;
            color: #C0392B;
        }

        .act-btn.delete:hover {
            background: #C0392B;
            color: #fff;
            transform: scale(1.05);
        }

        .inventory-grid-wrap {
            padding: 1rem;
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .inventory-card {
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

        .inventory-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, .06);
            border-color: #ead6d6;
        }

        .inventory-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .inventory-card-name {
            font-size: .95rem;
            font-weight: 700;
            color: #1A1614;
            line-height: 1.3;
        }

        .inventory-card-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
        }

        .inventory-card-label {
            font-size: .64rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .2rem;
        }

        .inventory-card-value {
            font-size: .82rem;
            color: #374151;
            line-height: 1.35;
            word-break: break-word;
        }

        .inventory-card-actions {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            flex-wrap: wrap;
        }

        .table-footer-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-top: 1px solid #EDE9E4;
            background: #FFFFFF;
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

        .modal-box-custom {
            background: #fff;
            border-radius: 20px;
            width: min(720px, 92vw);
            max-width: 92vw;
            max-height: min(86vh, 820px);
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .15);
        }

        .modal-box-split {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 0;
            overflow: hidden;
        }

        .modal-sticky-header,
        .modal-sticky-footer {
            flex-shrink: 0;
            background: #fff;
            position: relative;
            z-index: 2;
        }

        .modal-sticky-header {
            padding: 22px 22px 16px;
            border-bottom: 1px solid #F1ECE7;
        }

        .modal-scroll-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 18px 22px;
            min-height: 0;
        }

        .modal-sticky-footer {
            padding: 16px 22px 22px;
            border-top: 1px solid #F1ECE7;
            box-shadow: 0 -6px 18px rgba(0, 0, 0, 0.04);
        }

        .modal-header-custom {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .modal-icon-custom {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            flex-shrink: 0;
            background: linear-gradient(135deg, #660000, #8B0000);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            color: #fff;
        }

        .modal-title-custom {
            font-size: 17px;
            font-weight: 700;
            color: #660000;
        }

        .modal-sub-custom {
            font-size: 12px;
            color: #9A9490;
            margin-top: 1px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 14px;
        }

        .form-group-custom {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
        }

        .form-group-custom.full {
            grid-column: 1 / -1;
        }

        .form-label-custom {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #8B0000;
        }

        .form-input-custom,
        .form-select-custom {
            height: 40px;
            padding: 0 13px;
            border-radius: 9px;
            border: 1.5px solid #E0DDD8;
            background: #FFFFFF;
            font-size: 13px;
            color: #333;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
            min-width: 0;
        }

        .form-input-custom:focus,
        .form-select-custom:focus {
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        #addModal .add-modal-voice-row {
            display: flex;
            align-items: stretch;
            gap: .5rem;
            width: 100%;
        }

        #addModal .add-modal-voice-row .voice-input-wrap {
            position: relative;
            width: 100%;
            flex: 1 1 auto;
            min-width: 0;
        }

        #addModal .voice-input-wrap > .form-input-custom.has-voice-padding {
            padding-right: 2.35rem;
        }

        #addModal .voice-input-wrap .voice-mic-btn {
            position: absolute;
            right: 10px;
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
            z-index: 4;
        }

        #addModal .add-modal-voice-clear-btn {
            border: none;
            background: transparent;
            color: #dc2626;
            font-size: .78rem;
            font-weight: 600;
            line-height: 1;
            padding: 0 .1rem;
            margin: 0;
            cursor: pointer;
            align-self: center;
            flex: 0 0 auto;
            transition: color .15s ease;
        }

        #addModal .add-modal-voice-clear-btn.hidden {
            display: none;
        }

        #addModal .add-modal-voice-clear-btn:hover {
            color: #991b1b;
        }

        #addModal .voice-input-wrap [data-voice-status] {
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

        #addModal .voice-input-wrap [data-voice-status].hidden {
            display: none;
        }

        .form-input-custom[readonly] {
            background: #F2F0EC;
            color: #9A9490;
            cursor: not-allowed;
        }

        .modal-footer-custom {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-modal-cancel {
            height: 40px;
            padding: 0 18px;
            border-radius: 9px;
            border: 1.5px solid #E0DDD8;
            background: #FFFFFF;
            font-size: 13px;
            font-weight: 600;
            color: #6B6661;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-modal-cancel:hover {
            background: #EDE9E4;
        }

        .btn-modal-save {
            height: 40px;
            padding: 0 20px;
            border-radius: 9px;
            border: none;
            background: #8B0000;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            box-shadow: 0 3px 10px rgba(139, 0, 0, .3);
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-modal-save:hover {
            background: #660000;
        }

        .form-input-custom.is-invalid,
        .form-select-custom.is-invalid {
            border-color: #C0392B !important;
            box-shadow: 0 0 0 3px rgba(192, 57, 43, .12) !important;
            background: #FFF8F7 !important;
        }

        .form-input-custom.is-valid,
        .form-select-custom.is-valid {
            border-color: #2E7D32 !important;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, .1) !important;
        }

        .field-error {
            font-size: 10px;
            font-weight: 600;
            color: #C0392B;
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
            min-height: 14px;
        }

        .char-counter {
            font-size: 10px;
            color: #757575;
            text-align: right;
            margin-top: 3px;
        }

        .empty-state {
            display: none;
            padding: 3.5rem 1rem;
            text-align: center;
        }

        .empty-state.visible {
            display: block;
        }

        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .card,
        [data-theme="dark"] .filter-modal,
        [data-theme="dark"] .modal-box-custom,
        [data-theme="dark"] .inventory-card {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .card-header,
        [data-theme="dark"] .table-footer-bar,
        [data-theme="dark"] .modal-sticky-header,
        [data-theme="dark"] .modal-sticky-footer {
            background: #0d1117 !important;
            border-color: #21262d !important;
        }

        [data-theme="dark"] .card-title,
        [data-theme="dark"] .stat-value,
        [data-theme="dark"] .supply-name,
        [data-theme="dark"] .inventory-card-name {
            color: #f3f4f6 !important;
        }

        [data-theme="dark"] .inv-table td,
        [data-theme="dark"] .stat-footer,
        [data-theme="dark"] .inventory-card-value {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .search-wrap,
        [data-theme="dark"] .filter-btn,
        [data-theme="dark"] .form-input-custom,
        [data-theme="dark"] .form-select-custom,
        [data-theme="dark"] .tab-group,
        [data-theme="dark"] .inv-view-toggle {
            background: #0d1117;
            border-color: #21262d;
        }

        [data-theme="dark"] .search-wrap input {
            color: #f3f4f6;
        }

        @media (max-width: 1279px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 767px) {
            .page-banner {
                padding: 1.05rem 1.1rem 1.15rem !important;
            }

            .page-title {
                font-size: 1.45rem !important;
            }

            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: .75rem;
            }

            .stat-value {
                font-size: 1.7rem;
            }

            .stat-footer {
                display: none;
            }

            .card-header {
                flex-direction: column;
                align-items: stretch !important;
                padding: .8rem 1rem;
            }

            .card-header-right {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                justify-content: flex-start;
            }

            .search-wrap {
                width: 100%;
                height: 42px;
            }

            .filter-btn,
            .btn-add {
                width: 100%;
                height: 42px;
            }

            .inv-search-row {
                width: 100%;
            }

            .tab-group {
                width: 100%;
                display: flex;
            }

            .tab-btn {
                flex: 1;
                min-width: 0;
                text-align: center;
            }

            .form-grid-2 {
                grid-template-columns: 1fr;
            }

            #inventoryListView {
                display: none !important;
            }

            #inventoryGridView {
                display: block !important;
            }

            #inventoryViewToggle {
                display: none !important;
            }

            .inventory-grid {
                grid-template-columns: 1fr;
            }

            .inventory-table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .inv-table {
                min-width: 760px;
            }
        }
    </style>
@endsection

@section('content')
    <div id="toastContainer"></div>

    <main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-screen">
        <div class="max-w-[1280px] mx-auto">

            <div class="page-banner">
                <div class="page-banner-inner">
                    <h1 class="page-title">Inventory</h1>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-card-accent" style="background:linear-gradient(90deg,#8B0000,#c0392b);"></div>
                    <div class="stat-top">
                        <div class="stat-icon" style="background:#FEF2F2;color:#8B0000;">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                    </div>
                    <div class="stat-label">Total Items</div>
                    <div class="stat-value" id="statTotal">0</div>
                    <div class="stat-footer">all categories</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-accent" style="background:linear-gradient(90deg,#2563EB,#60A5FA);"></div>
                    <div class="stat-top">
                        <div class="stat-icon" style="background:#EFF6FF;color:#2563EB;">
                            <i class="fa-solid fa-pills"></i>
                        </div>
                    </div>
                    <div class="stat-label">Medicines</div>
                    <div class="stat-value" id="statMedicine">0</div>
                    <div class="stat-footer">pharmaceutical items</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-accent" style="background:linear-gradient(90deg,#16A34A,#4ADE80);"></div>
                    <div class="stat-top">
                        <div class="stat-icon" style="background:#F0FDF4;color:#16A34A;">
                            <i class="fa-solid fa-syringe"></i>
                        </div>
                    </div>
                    <div class="stat-label">Supplies</div>
                    <div class="stat-value" id="statSupplies">0</div>
                    <div class="stat-footer">clinic consumables</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-accent" style="background:linear-gradient(90deg,#F59E0B,#FBBF24);"></div>
                    <div class="stat-top">
                        <div class="stat-icon" style="background:#FEFCE8;color:#CA8A04;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="stat-label">Low Stock</div>
                    <div class="stat-value" id="statLow">0</div>
                    <div class="stat-footer">need restocking</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <div class="card-header-icon">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <span class="card-title">Inventory Directory</span>
                        <span id="entryBadge" class="entry-badge">0 entries</span>
                    </div>

                    <div class="card-header-right">
                        <div class="tab-group">
                            <button class="tab-btn active" type="button" onclick="setTab('all', this)">All</button>
                            <button class="tab-btn" type="button" onclick="setTab('medicine', this)">Med</button>
                            <button class="tab-btn" type="button" onclick="setTab('supplies', this)">Sup</button>
                        </div>

                        {{-- ── Search row: bar + clear + mic (matches user management) ── --}}
                        <div class="inv-search-row">
                            <div class="search-wrap" style="width:200px;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input id="searchInput" type="text" placeholder="Search stock no. or item name...">
                            </div>

                            <button type="button" id="searchClearBtn" class="search-clear-btn hidden">
                                Clear
                            </button>

                            <div class="patient-voice-toggle">
                                <button type="button" id="invMicToggleBtn" class="voice-search-mic external"
                                    aria-label="Toggle voice search" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="invVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>

                        <button type="button" id="openFilterBtn" class="filter-btn">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Filter</span>
                            <span id="filterDot" class="filter-dot"></span>
                        </button>

                        <div class="inv-view-toggle" id="inventoryViewToggle">
                            <button type="button" class="inv-view-toggle-btn active" id="inventoryListViewBtn"
                                title="List view" aria-label="List view">
                                <i class="fa-solid fa-table-list"></i>
                            </button>
                            <button type="button" class="inv-view-toggle-btn" id="inventoryGridViewBtn"
                                title="Grid view" aria-label="Grid view">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>

                        <button type="button" class="btn-add" onclick="openAddModal()">
                            <i class="fa-solid fa-plus"></i>
                            <span>Add Item</span>
                        </button>
                    </div>
                </div>

                <div class="inv-view" id="inventoryListView">
                    <div class="inventory-table-wrap" id="tableWrapper">
                        <table class="inv-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Stock No.</th>
                                    <th>Supply / Medicine</th>
                                    <th>Unit</th>
                                    <th>Qty</th>
                                    <th>Used</th>
                                    <th>Balance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="inv-view" id="inventoryGridView" hidden>
                    <div class="inventory-grid-wrap">
                        <div class="inventory-grid" id="inventoryGrid"></div>
                    </div>
                </div>

                <div id="emptyState" class="empty-state"></div>

                <div class="table-footer-bar">
                    <span class="text-xs text-gray-400" id="pageInfo"></span>
                    <div></div>
                </div>
            </div>
        </div>
    </main>

    <div id="filterModalBackdrop" class="filter-modal-backdrop">
        <div class="filter-modal">
            <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-sliders text-[#8B0000]"></i>
                    <h2 class="text-lg font-semibold text-[#8B0000]">Filter Inventory</h2>
                </div>
                <button type="button" id="closeFilterModal"
                    class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 hover:bg-gray-200">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="filter-modal-body space-y-5">
                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Sort by Name</p>
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
                    <p class="text-sm text-gray-500">Date Received</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm text-gray-700">From</label>
                            <input type="date" id="fromDate"
                                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm text-gray-700">To</label>
                            <input type="date" id="toDate"
                                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <div class="h-px bg-gray-200"></div>

                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Date Order</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 text-sm text-gray-700">
                            <input type="radio" name="dateOrder" value="asc" class="radio-red"> Ascending
                        </label>
                        <label class="flex items-center gap-3 text-sm text-gray-700">
                            <input type="radio" name="dateOrder" value="desc" class="radio-red"> Descending
                        </label>
                    </div>
                </div>

                <div class="h-px bg-gray-200"></div>

                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Stock Level</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 text-sm text-gray-700">
                            <input type="radio" name="stock" value="low-high" class="radio-red"> Lowest to Highest
                        </label>
                        <label class="flex items-center gap-3 text-sm text-gray-700">
                            <input type="radio" name="stock" value="high-low" class="radio-red"> Highest to Lowest
                        </label>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4 flex items-center justify-between border-t border-gray-200 bg-white">
                <button type="button" id="clearFiltersBtn"
                    class="text-[#8B0000] text-sm font-medium hover:underline">Clear</button>
                <button type="button" id="applyFiltersBtn"
                    class="bg-[#8B0000] text-white px-8 py-2 rounded-md text-sm font-medium shadow hover:bg-[#760000] transition">
                    Apply
                </button>
            </div>
        </div>
    </div>

    <dialog id="addModal" class="modal backdrop-blur-sm">
        <div class="modal-box-custom modal-box-split">
            <div class="modal-header-custom modal-sticky-header">
                <div class="modal-icon-custom"><i class="fa-solid fa-plus"></i></div>
                <div>
                    <div class="modal-title-custom">Add Inventory Item</div>
                    <div class="modal-sub-custom">A new row will be added once you save this item.</div>
                </div>
            </div>

            <div class="modal-scroll-body">
                <div class="form-grid-2">
                    <div class="form-group-custom">
                        <div class="form-label-custom">Category <span style="color:#C0392B">*</span></div>
                        <select id="addCategory" class="form-select-custom" onchange="validateAddField('addCategory')">
                            <option disabled selected value="">Select…</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Supplies">Supplies</option>
                        </select>
                        <div class="field-error" id="err-addCategory"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Date Received <span style="color:#C0392B">*</span></div>
                        <input id="addDate" type="date" class="form-input-custom" onchange="validateAddField('addDate')">
                        <div class="field-error" id="err-addDate"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Stock Number <span style="color:#C0392B">*</span></div>
                        <div class="flex items-center gap-2">
                            <input id="addStock" class="no-voice form-input-custom flex-1 min-w-0" placeholder="00-000" maxlength="6"
                                oninput="formatStockNo(this); validateAddField('addStock')" style="letter-spacing:0.15em">
                            <div class="patient-voice-toggle">
                                <button type="button" id="addStockMicBtn" class="voice-search-mic external" aria-label="Voice input for stock number">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addStockVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                        <div class="field-error" id="err-addStock"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Unit <span style="color:#C0392B">*</span></div>
                        <div class="flex items-center gap-2">
                            <input id="addUnit" list="unitOptions" class="no-voice form-input-custom flex-1 min-w-0" placeholder="Type or select unit"
                                maxlength="50" oninput="validateAddField('addUnit')">
                            <div class="patient-voice-toggle">
                                <button type="button" id="addUnitMicBtn" class="voice-search-mic external" aria-label="Voice input for unit">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addUnitVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                        <datalist id="unitOptions">
                            <option value="Box">
                            <option value="Pack">
                            <option value="Bottle">
                            <option value="Piece">
                            <option value="Set">
                            <option value="Tube">
                            <option value="Vial">
                            <option value="Roll">
                        </datalist>
                        <div class="field-error" id="err-addUnit"></div>
                    </div>

                    <div class="form-group-custom full">
                        <div class="flex justify-between items-center">
                            <div class="form-label-custom">Supply / Medicine Name <span style="color:#C0392B">*</span></div>
                            <div class="char-counter" id="charCounter-addName">0 / 100</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <input id="addName" class="no-voice form-input-custom flex-1 min-w-0" placeholder="e.g. Amoxicillin 500mg"
                                maxlength="100" oninput="updateCharCounter('addName',100); validateAddField('addName')">
                            <div class="patient-voice-toggle">
                                <button type="button" id="addNameMicBtn" class="voice-search-mic external" aria-label="Voice input for medicine name">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addNameVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                        <div class="field-error" id="err-addName"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Quantity <span style="color:#C0392B">*</span></div>
                        <div class="flex items-center gap-2">
                            <input id="addQty" type="number" class="no-voice form-input-custom flex-1 min-w-0" placeholder="0" min="0" max="99999"
                                oninput="computeAddBalance(); validateAddField('addQty'); validateAddField('addUsed')">
                            <div class="patient-voice-toggle">
                                <button type="button" id="addQtyMicBtn" class="voice-search-mic external" aria-label="Voice input for quantity">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addQtyVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                        <div class="field-error" id="err-addQty"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Consumed</div>
                        <div class="flex items-center gap-2">
                            <input id="addUsed" type="number" class="no-voice form-input-custom flex-1 min-w-0" placeholder="0" min="0" max="99999"
                                oninput="computeAddBalance(); validateAddField('addUsed')">
                            <div class="patient-voice-toggle">
                                <button type="button" id="addUsedMicBtn" class="voice-search-mic external" aria-label="Voice input for consumed">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addUsedVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                        <div class="field-error" id="err-addUsed"></div>
                    </div>

                    <div class="form-group-custom full">
                        <div class="form-label-custom">Balance (auto-calculated)</div>
                        <input id="addBalance" class="form-input-custom" readonly placeholder="—">
                    </div>
                </div>
            </div>

            <div class="modal-footer-custom modal-sticky-footer">
                <button class="btn-modal-cancel" onclick="document.getElementById('addModal').close()">Cancel</button>
                <button id="btnSaveAdd" class="btn-modal-save" onclick="addItem()">
                    <i class="fa-solid fa-floppy-disk"></i> Save Item
                </button>
            </div>
        </div>
    </dialog>

    <dialog id="editModal" class="modal backdrop-blur-sm">
        <div class="modal-box-custom modal-box-split">
            <div class="modal-header-custom modal-sticky-header">
                <div class="modal-icon-custom" style="background:linear-gradient(135deg,#1a4a8a,#2563EB);">
                    <i class="fa-solid fa-pen"></i>
                </div>
                <div>
                    <div class="modal-title-custom">Edit Inventory Item</div>
                    <div class="modal-sub-custom">Update the details for this item.</div>
                </div>
            </div>

            <div class="modal-scroll-body">
                <div class="form-grid-2">
                    <div class="form-group-custom">
                        <div class="form-label-custom">Category</div>
                        <select id="editCategory" class="form-select-custom">
                            <option value="Medicine">Medicine</option>
                            <option value="Supplies">Supplies</option>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Date Received</div>
                        <input id="editDate" type="date" class="form-input-custom">
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Stock Number</div>
                        <div class="flex items-center gap-2">
                            <input id="editStock" class="no-voice form-input-custom flex-1 min-w-0" maxlength="6" oninput="formatStockNo(this)">
                            <div class="patient-voice-toggle">
                                <button type="button" id="editStockMicBtn" class="voice-search-mic external" aria-label="Voice input for stock number">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editStockVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Unit</div>
                        <div class="flex items-center gap-2">
                            <input id="editUnit" list="unitOptionsEdit" class="no-voice form-input-custom flex-1 min-w-0" placeholder="Type or select unit">
                            <div class="patient-voice-toggle">
                                <button type="button" id="editUnitMicBtn" class="voice-search-mic external" aria-label="Voice input for unit">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editUnitVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                        <datalist id="unitOptionsEdit">
                            <option value="Box">
                            <option value="Pack">
                            <option value="Bottle">
                            <option value="Piece">
                            <option value="Set">
                            <option value="Tube">
                            <option value="Vial">
                            <option value="Roll">
                        </datalist>
                    </div>

                    <div class="form-group-custom full">
                        <div class="form-label-custom">Supply / Medicine Name</div>
                        <div class="flex items-center gap-2">
                            <input id="editName" class="no-voice form-input-custom flex-1 min-w-0">
                            <div class="patient-voice-toggle">
                                <button type="button" id="editNameMicBtn" class="voice-search-mic external" aria-label="Voice input for medicine name">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editNameVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Quantity</div>
                        <div class="flex items-center gap-2">
                            <input id="editQty" type="number" class="no-voice form-input-custom flex-1 min-w-0" oninput="computeEditBalance()">
                            <div class="patient-voice-toggle">
                                <button type="button" id="editQtyMicBtn" class="voice-search-mic external" aria-label="Voice input for quantity">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editQtyVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Consumed</div>
                        <div class="flex items-center gap-2">
                            <input id="editUsed" type="number" class="no-voice form-input-custom flex-1 min-w-0" oninput="computeEditBalance()">
                            <div class="patient-voice-toggle">
                                <button type="button" id="editUsedMicBtn" class="voice-search-mic external" aria-label="Voice input for consumed">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editUsedVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-custom full">
                        <div class="form-label-custom">Balance (auto-calculated)</div>
                        <input id="editBalance" class="form-input-custom" readonly>
                    </div>
                </div>
            </div>

            <div class="modal-footer-custom modal-sticky-footer">
                <button class="btn-modal-cancel" onclick="document.getElementById('editModal').close()">Cancel</button>
                <button class="btn-modal-save" onclick="saveEdit()">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </div>
    </dialog>

    <dialog id="deleteModal" class="modal">
        <div class="modal-box-custom" style="max-width:380px;text-align:center;padding:24px;">
            <div class="w-[60px] h-[60px] rounded-2xl bg-gradient-to-br from-[#FFE5E5] to-[#FFCDD2] flex items-center justify-center text-[26px] text-[#C0392B] mx-auto mb-3">
                <i class="fa-solid fa-trash"></i>
            </div>
            <div class="text-lg font-bold text-[#1A1614] mb-2">Delete Item?</div>
            <div class="text-[13px] text-[#9A9490] mb-6">
                This action cannot be undone. The item will be permanently removed from the inventory.
            </div>
            <div class="modal-footer-custom justify-center">
                <button class="btn-modal-cancel" onclick="document.getElementById('deleteModal').close()">Cancel</button>
                <button id="confirmDeleteBtn" class="btn-modal-save"
                    style="background:#C0392B;box-shadow:0 3px 10px rgba(192,57,43,.3);">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </dialog>
@endsection

@section('scripts')
    <script>
        let inventory = [];
        let activeTab = 'all';
        let editId = null;
        let deleteId = null;
        let currentInventoryView = getPreferredInventoryView();

        let filters = {
            search: '',
            sort: '',
            fromDate: '',
            toDate: '',
            dateOrder: '',
            stock: ''
        };

        function applyDashboardStockFilterFromQuery() {
            const params = new URLSearchParams(window.location.search);
            const stockFilter = (params.get('stock_filter') || '').toLowerCase();

            if (stockFilter === 'in-stock') {
                filters.stock = 'in-stock';
            } else if (stockFilter === 'low-stock') {
                filters.stock = 'low-stock';
            } else if (stockFilter === 'out-stock') {
                filters.stock = 'out-stock';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyDashboardStockFilterFromQuery();
            bindEvents();
            applyInventoryView(getPreferredInventoryView(), false);
            updateSearchClear();
            initializeAddModalVoiceEnhancements();
            initInvVoiceSearch();
            loadInventory();
        });

        // ─── Inventory search voice (matches user management pattern) ────────────
        function initInvVoiceSearch() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const input  = document.getElementById('searchInput');
            const micBtn = document.getElementById('invMicToggleBtn');
            const status = document.getElementById('invVoiceStatus');

            if (!input || !micBtn || !status || !SpeechRecognition) {
                if (micBtn) {
                    micBtn.disabled = true;
                    micBtn.setAttribute('aria-disabled', 'true');
                }
                return;
            }

            let invRecognition = null;
            let invListening   = false;
            let invManualStop  = false;

            const setStatus = function (text, state) {
                status.textContent = text;
                status.className   = 'patient-voice-status';
                if (state) status.classList.add('is-' + state);
                status.classList.remove('hidden');
            };

            const hideStatus = function (delay) {
                window.setTimeout(function () {
                    status.classList.add('hidden');
                }, delay || 0);
            };

            const setMicState = function (isActive) {
                micBtn.classList.toggle('mic-active', isActive);
                micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                micBtn.innerHTML = isActive
                    ? '<i class="fa-solid fa-stop"></i>'
                    : '<i class="fa-solid fa-microphone"></i>';
            };

            const stopListeningNow = function () {
                invManualStop = true;
                invListening  = false;
                setMicState(false);
                setStatus('Voice input stopped.', 'success');
                hideStatus(1200);
                if (invRecognition) {
                    try { invRecognition.abort(); } catch (e) {
                        try { invRecognition.stop(); } catch (err) {}
                    }
                }
            };

            const createRecognition = function () {
                const recognition = new SpeechRecognition();
                recognition.lang            = 'en-US';
                recognition.continuous      = false;
                recognition.interimResults  = true;
                recognition.maxAlternatives = 1;

                let sawSpeech       = false;
                let listenTimeoutId = null;
                const LISTEN_TIMEOUT = 6000;

                const clearListenTimeout = function () {
                    if (listenTimeoutId) { clearTimeout(listenTimeoutId); listenTimeoutId = null; }
                };

                recognition.onstart = function () {
                    listenTimeoutId = window.setTimeout(function () {
                        if (invListening && !sawSpeech) {
                            try { recognition.stop(); } catch (e) {}
                        }
                    }, LISTEN_TIMEOUT);
                };

                recognition.onspeechend = function () {
                    clearListenTimeout();
                    try { recognition.stop(); } catch (e) {}
                };

                recognition.onresult = function (event) {
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
                        filters.search = transcript;
                        updateSearchClear();
                        renderTable();
                        setStatus('Listening...', 'listening');
                    }
                };

                recognition.onerror = function () {
                    clearListenTimeout();
                    invListening = false;
                    if (invManualStop) { invManualStop = false; return; }
                    setMicState(false);
                    setStatus("Didn't catch that. Try again.", 'error');
                    hideStatus(2500);
                };

                recognition.onend = function () {
                    clearListenTimeout();
                    if (invManualStop) {
                        invManualStop = false;
                        invListening  = false;
                        setMicState(false);
                        return;
                    }
                    const hadSpeech = sawSpeech || !!input.value.trim();
                    invListening = false;
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

            micBtn.addEventListener('click', function () {
                if (invListening && invRecognition) { stopListeningNow(); return; }

                invRecognition = createRecognition();
                try {
                    invRecognition.start();
                } catch (error) {
                    setStatus('Unable to start voice input.', 'error');
                    hideStatus(2500);
                    setMicState(false);
                    invListening = false;
                    return;
                }
                invListening = true;
                setMicState(true);
                setStatus('Listening...', 'listening');
            });
        }

        function initializeAddModalVoiceEnhancements() {
            const modal = document.getElementById('addModal');
            if (!modal) return;

            const wrappers = modal.querySelectorAll('.voice-input-wrap');

            wrappers.forEach((wrapper) => {
                if (wrapper.dataset.addModalVoiceReady === 'true') return;

                const field = wrapper.querySelector('input.form-input-custom, textarea.form-input-custom');
                if (!field || field.readOnly || field.disabled) return;

                let row = wrapper.parentElement && wrapper.parentElement.classList.contains('add-modal-voice-row')
                    ? wrapper.parentElement
                    : null;

                if (!row) {
                    row = document.createElement('div');
                    row.className = 'add-modal-voice-row';
                    wrapper.parentNode.insertBefore(row, wrapper);
                    row.appendChild(wrapper);
                }

                let clearBtn = row.querySelector('.add-modal-voice-clear-btn');
                if (!clearBtn) {
                    clearBtn = document.createElement('button');
                    clearBtn.type = 'button';
                    clearBtn.className = 'add-modal-voice-clear-btn hidden';
                    clearBtn.textContent = 'Clear';
                    row.appendChild(clearBtn);
                }

                const status = wrapper.querySelector('[data-voice-status]');

                function toggleClear() {
                    if ((field.value || '').trim().length > 0) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                }

                clearBtn.addEventListener('click', () => {
                    field.value = '';
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    if (status) status.classList.add('hidden');
                    clearBtn.classList.add('hidden');
                    field.focus();
                });

                field.addEventListener('input', toggleClear);
                toggleClear();

                wrapper.dataset.addModalVoiceReady = 'true';
            });
        }

        function syncAddModalVoiceClearButtons() {
            const rows = document.querySelectorAll('#addModal .add-modal-voice-row');
            rows.forEach((row) => {
                const field = row.querySelector('input.form-input-custom, textarea.form-input-custom');
                const btn = row.querySelector('.add-modal-voice-clear-btn');
                if (!field || !btn) return;

                if ((field.value || '').trim().length > 0) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            });
        }

        function getPreferredInventoryView() {
            if (window.innerWidth <= 767) return 'grid';
            return localStorage.getItem('adminInventoryView') || 'list';
        }

        function applyInventoryView(view, save = true) {
            const listView = document.getElementById('inventoryListView');
            const gridView = document.getElementById('inventoryGridView');
            const listBtn = document.getElementById('inventoryListViewBtn');
            const gridBtn = document.getElementById('inventoryGridViewBtn');

            if (!listView || !gridView) return;

            const finalView = window.innerWidth <= 767 ? 'grid' : view;
            currentInventoryView = finalView;

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
                localStorage.setItem('adminInventoryView', finalView);
            }
        }

        function bindEvents() {
            document.getElementById('searchInput')?.addEventListener('input', function () {
                filters.search = this.value.trim();
                updateSearchClear();
                renderTable();
            });

            document.getElementById('searchClearBtn')?.addEventListener('click', function () {
                const input = document.getElementById('searchInput');
                input.value = '';
                filters.search = '';
                updateSearchClear();
                renderTable();
                input.focus();
            });

            document.getElementById('openFilterBtn')?.addEventListener('click', function () {
                document.getElementById('filterModalBackdrop').classList.add('show');
            });

            document.getElementById('closeFilterModal')?.addEventListener('click', function () {
                document.getElementById('filterModalBackdrop').classList.remove('show');
            });

            document.getElementById('filterModalBackdrop')?.addEventListener('click', function (e) {
                if (e.target === this) this.classList.remove('show');
            });

            document.getElementById('applyFiltersBtn')?.addEventListener('click', function () {
                filters.sort = document.querySelector('input[name="sort"]:checked')?.value || '';
                filters.fromDate = document.getElementById('fromDate')?.value || '';
                filters.toDate = document.getElementById('toDate')?.value || '';
                filters.dateOrder = document.querySelector('input[name="dateOrder"]:checked')?.value || '';
                filters.stock = document.querySelector('input[name="stock"]:checked')?.value || '';

                document.getElementById('filterModalBackdrop').classList.remove('show');
                renderTable();
            });

            document.getElementById('clearFiltersBtn')?.addEventListener('click', function () {
                document.querySelectorAll('#filterModalBackdrop input[type="radio"]').forEach(input => {
                    input.checked = false;
                });

                document.getElementById('fromDate').value = '';
                document.getElementById('toDate').value = '';

                filters.sort = '';
                filters.fromDate = '';
                filters.toDate = '';
                filters.dateOrder = '';
                filters.stock = '';

                renderTable();
            });

            document.getElementById('inventoryListViewBtn')?.addEventListener('click', function () {
                applyInventoryView('list', true);
            });

            document.getElementById('inventoryGridViewBtn')?.addEventListener('click', function () {
                applyInventoryView('grid', true);
            });

            document.getElementById('confirmDeleteBtn')?.addEventListener('click', confirmDelete);

            window.addEventListener('resize', function () {
                applyInventoryView(getPreferredInventoryView(), false);
            });
        }

        function setTab(tab, btn) {
            activeTab = tab;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderTable();
        }

        function updateSearchClear() {
            const btn = document.getElementById('searchClearBtn');
            const input = document.getElementById('searchInput');
            if (!btn || !input) return;

            if (input.value.trim().length > 0) {
                btn.classList.remove('hidden');
            } else {
                btn.classList.add('hidden');
            }
        }

        function updateFilterDot() {
            const dot = document.getElementById('filterDot');
            const btn = document.getElementById('openFilterBtn');
            const count = [filters.sort, (filters.fromDate || filters.toDate) ? 'range' : '', filters.dateOrder, filters.stock].filter(Boolean).length;

            dot.classList.toggle('visible', count > 0);
            dot.textContent = count > 0 ? count : '';
            btn.classList.toggle('active', count > 0);
        }

        function showToast(type, message, duration = 3000) {
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.style.position = 'fixed';
                container.style.bottom = '28px';
                container.style.right = '16px';
                container.style.zIndex = '9999';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.gap = '10px';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '10px';
            toast.style.padding = '13px 18px';
            toast.style.borderRadius = '12px';
            toast.style.fontSize = '13px';
            toast.style.fontWeight = '600';
            toast.style.color = '#fff';
            toast.style.boxShadow = '0 6px 24px rgba(0,0,0,.18)';
            toast.style.background = type === 'success' ? '#1A6B34' : '#C0392B';
            toast.innerHTML = `${type === 'success' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-exclamation"></i>'} ${message}`;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all .25s ease';
                setTimeout(() => toast.remove(), 250);
            }, duration);
        }

        async function loadInventory() {
            const res = await fetch('{{ route('admin.inventory.data') }}', { cache: 'no-store' });
            inventory = await res.json();
            renderTable();
        }

        function updateStats() {
            const total = inventory.length;
            const medicine = inventory.filter(i => i.category === 'Medicine').length;
            const supplies = inventory.filter(i => i.category === 'Supplies').length;
            const low = inventory.filter(i => (Number(i.qty) - Number(i.used)) <= 5).length;

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statMedicine').textContent = medicine;
            document.getElementById('statSupplies').textContent = supplies;
            document.getElementById('statLow').textContent = low;

            document.getElementById('entryBadge').textContent = `${total} ${total === 1 ? 'entry' : 'entries'}`;
        }

        function getFilteredInventory() {
            let data = [...inventory];

            if (activeTab === 'medicine') {
                data = data.filter(item => item.category === 'Medicine');
            }

            if (activeTab === 'supplies') {
                data = data.filter(item => item.category === 'Supplies');
            }

            if (filters.search) {
                const q = filters.search.toLowerCase();
                data = data.filter(item =>
                    String(item.stock_no || '').toLowerCase().includes(q) ||
                    String(item.name || '').toLowerCase().includes(q)
                );
            }

            if (filters.fromDate) {
                const from = new Date(filters.fromDate);
                from.setHours(0, 0, 0, 0);
                data = data.filter(item => item.date_received && new Date(item.date_received) >= from);
            }

            if (filters.toDate) {
                const to = new Date(filters.toDate);
                to.setHours(23, 59, 59, 999);
                data = data.filter(item => item.date_received && new Date(item.date_received) <= to);
            }

            if (filters.stock === 'in-stock') {
                data = data.filter(item => (Number(item.qty) - Number(item.used)) > 5);
            } else if (filters.stock === 'low-stock') {
                data = data.filter(item => {
                    const bal = Number(item.qty) - Number(item.used);
                    return bal >= 1 && bal <= 5;
                });
            } else if (filters.stock === 'out-stock') {
                data = data.filter(item => (Number(item.qty) - Number(item.used)) <= 0);
            } else if (filters.stock === 'low-high') {
                data.sort((a, b) => (Number(a.qty) - Number(a.used)) - (Number(b.qty) - Number(b.used)));
            } else if (filters.stock === 'high-low') {
                data.sort((a, b) => (Number(b.qty) - Number(b.used)) - (Number(a.qty) - Number(a.used)));
            } else if (filters.sort === 'az') {
                data.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
            } else if (filters.sort === 'za') {
                data.sort((a, b) => String(b.name || '').localeCompare(String(a.name || '')));
            } else if (filters.dateOrder === 'asc') {
                data.sort((a, b) => new Date(a.date_received) - new Date(b.date_received));
            } else if (filters.dateOrder === 'desc') {
                data.sort((a, b) => new Date(b.date_received) - new Date(a.date_received));
            }

            return data;
        }

        function renderTable() {
            updateStats();
            updateFilterDot();

            const tbody = document.getElementById('tableBody');
            const grid = document.getElementById('inventoryGrid');
            const emptyState = document.getElementById('emptyState');
            const pageInfo = document.getElementById('pageInfo');

            tbody.innerHTML = '';
            if (grid) grid.innerHTML = '';

            const data = getFilteredInventory();
            pageInfo.textContent = `Showing ${data.length} of ${inventory.length} items`;

            if (!data.length) {
                document.getElementById('inventoryListView').hidden = true;
                document.getElementById('inventoryGridView').hidden = true;
                emptyState.classList.add('visible');
                emptyState.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-16 text-center gap-2">
                        <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                            <i class="fa-solid fa-box-open text-3xl text-gray-300"></i>
                        </div>
                        <p class="text-base font-semibold text-gray-500">No matching inventory found</p>
                        <p class="text-sm text-gray-400 max-w-xs">Try changing the search, tab, or filter options.</p>
                    </div>
                `;
                return;
            }

            emptyState.classList.remove('visible');
            emptyState.innerHTML = '';

            data.forEach(function(item) {
                const balance = Number(item.qty) - Number(item.used);

                const isOutOfStock = balance <= 0;
                const balClass = balance <= 5 ? 'critical' : 'ok';
                const balLabel = isOutOfStock ? 'Out of stock' : balance <= 5 ? 'Low stock' : 'In stock';
                const catClass = item.category === 'Medicine' ? 'medicine' : 'supplies';

                const balExtraClass = isOutOfStock ? ' alert-blink' : '';
                const balIcon = isOutOfStock ? '<span class="out-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>' : '';

                tbody.innerHTML +=
                    '<tr>' +
                    '<td style="color:#9A9490;font-size:12px;white-space:nowrap;">' + (item.formatted_date || '') + '</td>' +
                    '<td><span class="stock-no">' + (item.stock_no || '') + '</span></td>' +
                    '<td><div class="supply-name">' + (item.name || '') + '</div><span class="supply-cat ' + catClass + '">' + (item.category || '') + '</span></td>' +
                    '<td style="color:#9A9490;">' + (item.unit || '') + '</td>' +
                    '<td style="font-weight:700;">' + (item.qty || 0) + '</td>' +
                    '<td style="color:#9A9490;">' + (item.used || 0) + '</td>' +
                    '<td><span class="bal-chip ' + balClass + balExtraClass + '">' + balance +
                        ' <span style="font-weight:400;font-size:10px;">' + balLabel + '</span>' +
                        balIcon + '</span></td>' +
                    '<td><div style="display:flex;justify-content:center;gap:6px;"><button class="act-btn edit" title="Edit" onclick="openEdit(' + item.id + ')"><i class="fa fa-pen"></i></button><button class="act-btn delete" title="Delete" onclick="deleteItem(' + item.id + ')"><i class="fa fa-trash"></i></button></div></td>' +
                    '</tr>';

                if (grid) {
                    grid.innerHTML +=
                        '<div class="inventory-card">' +
                        '<div class="inventory-card-top">' +
                        '<div>' +
                        '<div class="inventory-card-name">' + (item.name || '') + '</div>' +
                        '<div style="margin-top:6px;"><span class="stock-no">' + (item.stock_no || '') + '</span></div>' +
                        '<span class="supply-cat ' + catClass + '" style="margin-top:8px;">' + (item.category || '') + '</span>' +
                        '</div>' +
                        '<span class="bal-chip ' + balClass + balExtraClass + '">' + balance + balIcon + '</span>' +
                        '</div>' +

                        '<div class="inventory-card-meta">' +
                        '<div><div class="inventory-card-label">Date</div><div class="inventory-card-value">' + (item.formatted_date || '') + '</div></div>' +
                        '<div><div class="inventory-card-label">Unit</div><div class="inventory-card-value">' + (item.unit || '') + '</div></div>' +
                        '<div><div class="inventory-card-label">Qty</div><div class="inventory-card-value">' + (item.qty || 0) + '</div></div>' +
                        '<div><div class="inventory-card-label">Used</div><div class="inventory-card-value">' + (item.used || 0) + '</div></div>' +
                        '</div>' +

                        '<div class="inventory-card-actions">' +
                        '<button class="act-btn edit" title="Edit" onclick="openEdit(' + item.id + ')"><i class="fa fa-pen"></i></button>' +
                        '<button class="act-btn delete" title="Delete" onclick="deleteItem(' + item.id + ')"><i class="fa fa-trash"></i></button>' +
                        '</div>' +
                        '</div>';
                }
            });

            applyInventoryView(currentInventoryView, false);
        }

        function formatStockNo(input) {
            let digits = input.value.replace(/\D/g, '');
            if (digits.length > 5) digits = digits.slice(0, 5);
            input.value = digits.length <= 2 ? digits : digits.slice(0, 2) + '-' + digits.slice(2);
        }

        function updateCharCounter(fieldId, max) {
            const len = document.getElementById(fieldId).value.length;
            const counter = document.getElementById('charCounter-' + fieldId);
            if (!counter) return;
            counter.textContent = `${len} / ${max}`;
        }

        function setFieldState(id, errorMsg) {
            const el = document.getElementById(id);
            const errEl = document.getElementById('err-' + id);
            if (!el) return;

            if (errorMsg) {
                el.classList.add('is-invalid');
                el.classList.remove('is-valid');
                if (errEl) errEl.innerHTML = `<i class="fa-solid fa-circle-exclamation" style="font-size:9px;"></i> ${errorMsg}`;
            } else {
                el.classList.remove('is-invalid');
                el.classList.add('is-valid');
                if (errEl) errEl.innerHTML = '';
            }
        }

        function validateAddField(id) {
            const el = document.getElementById(id);
            if (!el) return true;
            const val = el.value.trim();
            const today = new Date();
            today.setHours(23, 59, 59, 999);

            switch (id) {
                case 'addCategory':
                    if (!val) {
                        setFieldState(id, 'Please select a category');
                        return false;
                    }
                    break;
                case 'addDate': {
                    if (!val) {
                        setFieldState(id, 'Date is required');
                        return false;
                    }
                    const picked = new Date(val);
                    if (isNaN(picked.getTime())) {
                        setFieldState(id, 'Invalid date');
                        return false;
                    }
                    if (picked > today) {
                        setFieldState(id, 'Date cannot be in the future');
                        return false;
                    }
                    break;
                }
                case 'addStock':
                    if (!val) {
                        setFieldState(id, 'Stock number is required');
                        return false;
                    }
                    if (!/^\d{2}-\d{3}$/.test(val)) {
                        setFieldState(id, 'Must be in format 00-000');
                        return false;
                    }
                    break;
                case 'addUnit':
                    if (!val) {
                        setFieldState(id, 'Unit is required');
                        return false;
                    }
                    break;
                case 'addName':
                    if (!val) {
                        setFieldState(id, 'Name is required');
                        return false;
                    }
                    if (val.length < 2) {
                        setFieldState(id, 'Minimum 2 characters');
                        return false;
                    }
                    break;
                case 'addQty': {
                    const raw = el.value;
                    if (raw === '' || raw === null) {
                        setFieldState(id, 'Quantity is required');
                        return false;
                    }
                    const qn = Number(raw);
                    if (!Number.isInteger(qn) || qn < 0) {
                        setFieldState(id, 'Must be a whole number ≥ 0');
                        return false;
                    }
                    if (qn > 99999) {
                        setFieldState(id, 'Maximum quantity is 99,999');
                        return false;
                    }
                    break;
                }
                case 'addUsed': {
                    const rawU = el.value;
                    const un = Number(rawU || 0);
                    const qnU = Number(document.getElementById('addQty').value || 0);
                    if (rawU !== '' && (!Number.isInteger(un) || un < 0)) {
                        setFieldState(id, 'Must be a whole number ≥ 0');
                        return false;
                    }
                    if (un > qnU) {
                        setFieldState(id, 'Consumed cannot exceed quantity');
                        return false;
                    }
                    break;
                }
            }

            setFieldState(id, '');
            return true;
        }

        function validateAllAddFields() {
            return ['addCategory', 'addDate', 'addStock', 'addUnit', 'addName', 'addQty', 'addUsed']
                .map(id => validateAddField(id))
                .every(Boolean);
        }

        function computeAddBalance() {
            document.getElementById('addBalance').value =
                Number(document.getElementById('addQty').value || 0) -
                Number(document.getElementById('addUsed').value || 0);
        }

        function computeEditBalance() {
            document.getElementById('editBalance').value =
                Number(document.getElementById('editQty').value || 0) -
                Number(document.getElementById('editUsed').value || 0);
        }

        function resetAddForm() {
            document.getElementById('addCategory').value = '';
            ['addDate', 'addStock', 'addName', 'addUnit', 'addQty', 'addUsed', 'addBalance'].forEach(id => {
                document.getElementById(id).value = '';
            });

            ['addCategory', 'addDate', 'addStock', 'addUnit', 'addName', 'addQty', 'addUsed'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid', 'is-valid');
                const err = document.getElementById('err-' + id);
                if (err) err.innerHTML = '';
            });

            const counter = document.getElementById('charCounter-addName');
            if (counter) counter.textContent = '0 / 100';

            document.querySelectorAll('#addModal [data-voice-status]').forEach((status) => {
                status.classList.add('hidden');
            });

            syncAddModalVoiceClearButtons();
        }

        function openAddModal() {
            initializeAddModalVoiceEnhancements();
            resetAddForm();
            document.getElementById('addModal').showModal();
        }

        async function addItem() {
            if (!validateAllAddFields()) {
                const firstInvalid = document.querySelector('#addModal .is-invalid');
                if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const btnSave = document.getElementById('btnSaveAdd');
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

            const res = await fetch('{{ route('admin.inventory.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    category: document.getElementById('addCategory').value,
                    date_received: document.getElementById('addDate').value,
                    stock_no: document.getElementById('addStock').value.trim(),
                    name: document.getElementById('addName').value.trim(),
                    unit: document.getElementById('addUnit').value.trim(),
                    qty: Number(document.getElementById('addQty').value),
                    used: Number(document.getElementById('addUsed').value || 0)
                })
            });

            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Item';

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));

                if (err.errors) {
                    const map = {
                        category: 'addCategory',
                        date_received: 'addDate',
                        stock_no: 'addStock',
                        name: 'addName',
                        unit: 'addUnit',
                        qty: 'addQty',
                        used: 'addUsed'
                    };

                    Object.entries(err.errors).forEach(([k, v]) => {
                        if (map[k]) setFieldState(map[k], Array.isArray(v) ? v[0] : v);
                    });
                } else {
                    showToast('error', 'Could not save item. Please try again.');
                }
                return;
            }

            document.getElementById('addModal').close();
            resetAddForm();
            await loadInventory();
            showToast('success', 'Item added successfully!');
        }

        function openEdit(id) {
            editId = id;
            const item = inventory.find(x => x.id === id);
            if (!item) return;

            document.getElementById('editCategory').value = item.category;
            document.getElementById('editDate').value = item.date_received ? item.date_received.slice(0, 10) : '';
            document.getElementById('editStock').value = item.stock_no;
            document.getElementById('editName').value = item.name;
            document.getElementById('editUnit').value = item.unit;
            document.getElementById('editQty').value = item.qty;
            document.getElementById('editUsed').value = item.used;
            computeEditBalance();

            document.getElementById('editModal').showModal();
        }

        async function saveEdit() {
            if (!editId) return;

            const res = await fetch(`{{ url('/admin/inventory') }}/${editId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    category: document.getElementById('editCategory').value,
                    date_received: document.getElementById('editDate').value,
                    stock_no: document.getElementById('editStock').value.trim(),
                    name: document.getElementById('editName').value.trim(),
                    unit: document.getElementById('editUnit').value.trim(),
                    qty: Number(document.getElementById('editQty').value),
                    used: Number(document.getElementById('editUsed').value || 0)
                })
            });

            if (!res.ok) {
                showToast('error', 'Edit failed — please try again.');
                return;
            }

            document.getElementById('editModal').close();
            editId = null;
            await loadInventory();
            showToast('success', 'Item updated successfully!');
        }

        function deleteItem(id) {
            deleteId = id;
            document.getElementById('deleteModal').showModal();
        }

        async function confirmDelete() {
            if (!deleteId) return;

            await fetch(`{{ url('/admin/inventory') }}/${deleteId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            document.getElementById('deleteModal').close();
            deleteId = null;
            await loadInventory();
            showToast('success', 'Item deleted.');
        }

        /* ── Generic Voice Handler for Inventory Form Fields ── */
        function initializeInventoryVoiceHandler(micBtnId, inputId, statusId) {
            const micBtn = document.getElementById(micBtnId);
            const input = document.getElementById(inputId);
            const status = document.getElementById(statusId);

            if (!micBtn || !input || !status) return;

            let listening = false;
            let recognition = null;
            let manualStop = false;

            const setStatus = function (text, state) {
                status.textContent = text;
                status.className = 'patient-voice-status';
                if (state) status.classList.add('is-' + state);
                status.classList.remove('hidden');
            };

            const hideStatus = function (delay) {
                window.setTimeout(function () {
                    status.classList.add('hidden');
                }, delay || 0);
            };

            const setMicState = function (isActive) {
                micBtn.classList.toggle('mic-active', isActive);
                micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                micBtn.innerHTML = isActive
                    ? '<i class="fa-solid fa-stop"></i>'
                    : '<i class="fa-solid fa-microphone"></i>';
            };

            const stopListeningNow = function () {
                manualStop = true;
                listening = false;
                setMicState(false);
                setStatus('Voice input stopped.', 'success');
                hideStatus(1200);
                if (recognition) {
                    try { recognition.abort(); } catch (e) {
                        try { recognition.stop(); } catch (err) {}
                    }
                }
            };

            const createRecognition = function () {
                const r = new SpeechRecognition();
                r.lang = 'en-US';
                r.continuous = false;
                r.interimResults = true;
                r.maxAlternatives = 1;

                let sawSpeech = false;
                let listenTimeoutId = null;
                const LISTEN_TIMEOUT = 6000;

                const clearListenTimeout = function () {
                    if (listenTimeoutId) { clearTimeout(listenTimeoutId); listenTimeoutId = null; }
                };

                r.onstart = function () {
                    listenTimeoutId = window.setTimeout(function () {
                        if (listening && !sawSpeech) {
                            try { r.stop(); } catch (e) {}
                        }
                    }, LISTEN_TIMEOUT);
                };

                r.onspeechend = function () {
                    clearListenTimeout();
                    try { r.stop(); } catch (e) {}
                };

                r.onresult = function (event) {
                    let transcript = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const result = event.results[i];
                        const chunk = (result && result[0] && result[0].transcript
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
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        setStatus('Listening...', 'listening');
                    }
                };

                r.onerror = function () {
                    clearListenTimeout();
                    listening = false;
                    if (manualStop) { manualStop = false; return; }
                    setMicState(false);
                    setStatus("Didn't catch that. Try again.", 'error');
                    hideStatus(2500);
                };

                r.onend = function () {
                    clearListenTimeout();
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

            micBtn.addEventListener('click', function () {
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
        }

        /* ── Initialize Voice Handlers on Document Load ── */
        document.addEventListener('DOMContentLoaded', function () {
            // Existing code...
        }, { once: false });

        /* ── Voice Handler Initialization (must run after page load) ── */
        window.addEventListener('load', function () {
            // Add modal voice handlers
            initializeInventoryVoiceHandler('addStockMicBtn', 'addStock', 'addStockVoiceStatus');
            initializeInventoryVoiceHandler('addUnitMicBtn', 'addUnit', 'addUnitVoiceStatus');
            initializeInventoryVoiceHandler('addNameMicBtn', 'addName', 'addNameVoiceStatus');
            initializeInventoryVoiceHandler('addQtyMicBtn', 'addQty', 'addQtyVoiceStatus');
            initializeInventoryVoiceHandler('addUsedMicBtn', 'addUsed', 'addUsedVoiceStatus');

            // Edit modal voice handlers
            initializeInventoryVoiceHandler('editStockMicBtn', 'editStock', 'editStockVoiceStatus');
            initializeInventoryVoiceHandler('editUnitMicBtn', 'editUnit', 'editUnitVoiceStatus');
            initializeInventoryVoiceHandler('editNameMicBtn', 'editName', 'editNameVoiceStatus');
            initializeInventoryVoiceHandler('editQtyMicBtn', 'editQty', 'editQtyVoiceStatus');
            initializeInventoryVoiceHandler('editUsedMicBtn', 'editUsed', 'editUsedVoiceStatus');
        }, { once: false });
    </script>
@endsection