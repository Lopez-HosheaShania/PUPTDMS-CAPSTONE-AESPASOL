@extends('layouts.admin')

@section('title', 'Academic Period | PUP Taguig Dental Clinic')

@section('styles')

    <style>
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

        .active-banner {
            background: #fff;
            border-radius: 12px;
            border-left: 4px solid #8B0000;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .06);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .active-banner-inner {
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .page-banner {
            background: linear-gradient(135deg, #6b0000 0%, #8B0000 60%, #c0392b 100%);
            padding: 1.75rem 2rem 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(139, 0, 0, .25);
            border-radius: 16px;
            margin-top: 0;
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
        }

        .page-subtitle {
            font-size: .8rem;
            color: rgba(255, 255, 255, .7);
            margin-top: .35rem;
        }

        .page-banner-date {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .75rem;
            color: rgba(255, 255, 255, .8);
            margin-bottom: .3rem;
        }

        .academic-view-toggle {
            display: inline-flex;
            align-items: center;
            background: #FAFAF9;
            border: 1.5px solid #E0DDD8;
            border-radius: 14px;
            padding: 4px;
            gap: 4px;
            height: 42px;
        }

        .academic-view-btn {
            width: 38px;
            height: 34px;
            border: none;
            background: transparent;
            color: #6b7280;
            border-radius: 10px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .15s ease;
            flex-shrink: 0;
        }

        .academic-view-btn:hover {
            background: #f3f4f6;
            color: #8B0000;
        }

        .academic-view-btn.active {
            background: #8B0000;
            color: #fff;
            box-shadow: 0 2px 8px rgba(139, 0, 0, .15);
        }

        @media (min-width:1024px) {
            .active-banner-inner {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 1.25rem;
            }
        }

        .progress-track {
            height: 6px;
            border-radius: 99px;
            background: #f3f4f6;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, #8B0000, #c0392b);
            transition: width .6s cubic-bezier(.4, 0, .2, 1);
        }

        .sem-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 99px;
        }

        .s-active {
            background: #fee2e2;
            color: #8B0000;
        }

        .s-upcoming {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .s-ended {
            background: #f3f4f6;
            color: #6b7280;
        }

        .s-inactive {
            background: #fef9c3;
            color: #92400e;
        }

        .act {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            transition: all .15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
            font-weight: 600;
            flex-shrink: 0;
        }

        .act i {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .act:hover {
            transform: scale(1.06);
        }

        .act-edit {
            background: #eff6ff;
            color: #2563eb;
        }

        .act-edit:hover {
            background: #dbeafe;
        }

        .act-star {
            background: #d1fae5;
            color: #065f46;
        }

        .act-star:hover {
            background: #a7f3d0;
        }

        .act-del {
            background: #fef2f2;
            color: #dc2626;
        }

        .act-del:hover {
            background: #fee2e2;
        }

        .act-pinned {
            background: #d1fae5;
            color: #065f46;
            opacity: .55;
            cursor: default;
        }

        .tbl-row {
            transition: background .12s;
        }

        .tbl-row:hover {
            background: #fef9f9;
        }

        .tbl-row.is-active {
            background: #fff7f7;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at top, rgba(139, 0, 0, .24), rgba(0, 0, 0, .58));
            backdrop-filter: blur(3px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.1rem;
            opacity: 0;
            transition: opacity .2s;
        }

        .modal-overlay.open {
            display: flex;
            opacity: 1;
        }

        .modal-box {
            position: relative;
            z-index: 2001;
            background: #fff;
            border-radius: 24px;
            width: 100%;
            max-width: 720px;
            max-height: min(92vh, calc(100dvh - 2.2rem));
            overflow: hidden;
            transform: scale(.96) translateY(12px);
            transition: transform .25s cubic-bezier(.4, 0, .2, 1);
            box-shadow: 0 28px 70px rgba(0, 0, 0, .3);
            border: 1px solid rgba(255, 255, 255, .35);
        }

        .modal-overlay.open .modal-box {
            transform: scale(1) translateY(0);
        }

        .modal-box>form {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }

        .modal-sm .modal-box {
            max-width: 420px;
        }

        .ap-modal-shell {
            background: linear-gradient(180deg, #ffffff 0%, #fffdfc 100%);
        }

        .ap-modal-header {
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 1.1rem 1.35rem;
            border-bottom: 1px solid #f5dede;
            background: linear-gradient(180deg, #fff7f7 0%, #ffffff 90%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .ap-modal-titlewrap {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-width: 0;
        }

        .ap-modal-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 10px 20px rgba(0, 0, 0, .12);
            flex-shrink: 0;
        }

        .ap-modal-icon.add {
            background: linear-gradient(145deg, #8b0000, #b80000);
        }

        .ap-modal-icon.edit {
            background: linear-gradient(145deg, #2563eb, #1d4ed8);
        }

        .ap-modal-icon.delete {
            background: linear-gradient(145deg, #b91c1c, #ef4444);
        }

        .ap-modal-close {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid #f0dcdc;
            color: #94a3b8;
            background: #fff;
            transition: all .2s;
        }

        .ap-modal-close:hover {
            color: #8B0000;
            border-color: #f6bcbc;
            background: #fff5f5;
        }

        .ap-modal-body {
            padding: 1.2rem 1.35rem 1rem;
            display: grid;
            gap: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #fffafa 100%);
        }

        .ap-modal-label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .45rem;
        }

        .ap-modal-sem-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            align-items: stretch;
        }

        .ap-sem-card {
            position: relative;
        }

        .ap-sem-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .ap-sem-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            min-height: 76px;
            border-radius: 16px;
            border: 1.5px solid #e5e7eb;
            padding: 14px 10px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            background: #fff;
            transition: all .2s ease;
            cursor: pointer;
            user-select: none;
            position: relative;
            z-index: 1;
        }

        .ap-sem-option i {
            font-size: 16px;
            margin: 0;
        }

        .ap-sem-option:hover {
            border-color: #8B0000;
            color: #8B0000;
            box-shadow: 0 4px 14px rgba(139, 0, 0, .08);
        }

        .ap-sem-input:checked+.ap-sem-option {
            background: #8B0000;
            color: #fff;
            border-color: #8B0000;
            box-shadow: 0 10px 24px rgba(139, 0, 0, .18);
        }

        .ap-sem-input:focus+.ap-sem-option {
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .12);
        }

        .ap-add-modal .modal-box {
            max-width: 1080px;
            width: min(1080px, calc(100vw - 2rem));
            height: min(88vh, calc(100dvh - 2rem));
            max-height: min(88vh, calc(100dvh - 2rem));
            overflow: hidden;
            border-radius: 26px;
            display: flex;
            flex-direction: column;
        }

        .ap-add-form {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            height: 100%;
        }

        .ap-add-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .ap-add-header-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #8b0000, #b80000);
            color: #fff;
            box-shadow: 0 12px 24px rgba(139, 0, 0, 0.18);
            flex-shrink: 0;
        }

        .ap-add-header-title {
            font-size: 1.35rem;
            font-weight: 900;
            color: #111827;
            line-height: 1.15;
            margin: 0;
        }

        .ap-add-header-subtitle {
            margin: 0.25rem 0 0;
            font-size: 0.82rem;
            color: #6b7280;
            font-weight: 500;
        }

        .ap-add-close {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #94a3b8;
            transition: all .2s ease;
            flex-shrink: 0;
        }

        .ap-add-close:hover {
            color: #8B0000;
            border-color: #fecaca;
            background: #fff5f5;
        }

        .ap-add-body {
            flex: 1;
            min-height: 0;
            padding: 1.2rem 1.5rem 1rem;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem 1.1rem;
            align-content: start;
            overflow-y: auto;
            overflow-x: hidden;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }

        .ap-col-span-2 {
            grid-column: span 2;
        }

        .ap-panel {
            border: 1px solid #e9eef5;
            background: #ffffff;
            border-radius: 18px;
            padding: 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            min-width: 0;
        }

        .ap-panel-soft {
            background: linear-gradient(180deg, #fcfdff 0%, #ffffff 100%);
            border: 1px solid #e9eef5;
        }

        .ap-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .5rem;
        }

        .ap-label-text {
            font-size: 11px;
            font-weight: 800;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .ap-label-hint {
            font-size: 10px;
            font-weight: 800;
            color: #9ca3af;
            text-transform: uppercase;
        }

        .ap-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .ap-input-wrap.voice-input-wrap {
            position: relative;
            width: 100%;
        }

        .ap-input-wrap .ap-input,
        .ap-input-wrap .voice-input-wrap .ap-input,
        .ap-input-wrap[data-voice-field] .ap-input {
            width: 100%;
            padding-left: 42px;
            padding-right: 48px;
        }

        .ap-input-wrap .ap-input-icon,
        .ap-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            font-size: 14px;
            line-height: 1;
            color: #9ca3af;
            pointer-events: none;
            z-index: 3;
            flex-shrink: 0;
        }

        .ap-input-wrap .ap-input-icon i,
        .ap-input-icon i {
            display: block;
            line-height: 1;
            margin: 0;
        }

        /* ── Description textarea wrap: flex row so mic sits outside ── */
        .ap-textarea-wrap {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            width: 100%;
        }

        .ap-textarea-wrap .ap-textarea-inner {
            position: relative;
            flex: 1;
            min-width: 0;
        }

        .ap-textarea-wrap .ap-placeholder {
            position: absolute;
            top: 14px;
            left: 15px;
            right: 15px;
            font-size: 11px;
            line-height: 1.35;
            color: #9ca3af;
            pointer-events: none;
            white-space: normal;
            word-break: keep-all;
            overflow: visible;
            z-index: 2;
        }

        .ap-textarea-wrap.has-value .ap-placeholder,
        .ap-textarea-wrap.is-focused .ap-placeholder {
            opacity: 0;
            visibility: hidden;
        }

        .ap-textarea-wrap .ap-textarea {
            width: 100%;
            padding-right: 16px;
        }

        /* ── External circular mic button (description) ── */
        .ap-textarea-wrap .voice-mic-btn {
            position: relative;
            top: auto;
            right: auto;
            transform: none;
            width: 40px;
            height: 40px;
            border-radius: 999px;
            background: #4b5563;
            color: #fff;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(75, 85, 99, 0.22);
            margin-top: 6px;
        }

        .ap-textarea-wrap .voice-mic-btn:hover {
            background: #374151;
        }

        .ap-textarea-wrap .voice-mic-btn.is-listening {
            background: #c0392b;
            box-shadow: 0 0 0 4px rgba(192, 57, 43, 0.18);
            border: none;
            color: #fff !important;
            animation: micPulse 1.2s ease-in-out infinite;
        }

        .ap-textarea-wrap .voice-mic-btn i {
            font-size: 14px;
            line-height: 1;
        }

        /* voice status badge for textarea mic */
        .ap-textarea-wrap .voice-status {
            position: absolute;
            right: 0;
            top: calc(100% + 6px);
            z-index: 5;
        }

        .ap-textarea {
            min-height: 140px;
            height: 140px;
            max-height: 140px;
            font-size: 12px;
            padding: 14px 15px;
        }

        .ap-input,
        .ap-textarea {
            width: 100%;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #111827;
            font-size: 14px;
            font-weight: 600;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .ap-input {
            height: 52px;
            border-radius: 14px;
            padding: 0 16px 0 42px;
        }

        .ap-textarea {
            display: block;
            width: 100% !important;
            min-width: 100%;
            max-width: 100%;
            height: 160px;
            resize: none;
            border-radius: 16px;
            padding: 16px 18px;
            font-size: 12px;
            line-height: 1.4;
            box-sizing: border-box;
            vertical-align: top;
        }

        .ap-desc-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            margin-top: .7rem;
            flex-wrap: wrap;
        }

        .ap-desc-help {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
        }

        .ap-word-counter {
            margin-left: auto;
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
            padding: 6px 12px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            transition: all .2s ease;
        }

        .ap-word-counter.is-warning {
            color: #a16207;
            background: #fef9c3;
            border-color: #fde68a;
        }

        .ap-word-counter.is-danger {
            color: #b91c1c;
            background: #fee2e2;
            border-color: #fecaca;
        }

        .ap-word-counter.is-invalid {
            color: #991b1b;
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .ap-input:focus,
        .ap-textarea:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.10);
        }

        .ap-semester-grid-redesign {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .ap-semester-item {
            position: relative;
        }

        .ap-semester-item input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .ap-semester-card {
            min-height: 92px;
            border-radius: 18px;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            text-align: center;
            padding: .85rem;
            cursor: pointer;
            transition: all .2s ease;
            color: #64748b;
        }

        .ap-semester-card i {
            font-size: 18px;
        }

        .ap-semester-card span {
            font-size: 13px;
            font-weight: 800;
            line-height: 1.2;
        }

        .ap-semester-item input:checked+.ap-semester-card {
            border-color: var(--active-color, #8B0000);
            background: linear-gradient(180deg, #fff5f5 0%, #ffeaea 100%);
            color: var(--active-color, #8B0000);
            box-shadow: 0 10px 24px rgba(139, 0, 0, 0.12);
        }

        .ap-semester-card:hover {
            border-color: #8B0000;
            color: #8B0000;
        }

        .ap-date-grid-redesign {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem;
        }

        .ap-active-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: 1px solid #f3d4d4;
            border-radius: 18px;
            background: linear-gradient(90deg, #fff7f7 0%, #ffffff 100%);
            padding: 1rem 1.05rem;
        }

        .ap-active-card-left {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .ap-active-badge {
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

        .ap-active-title {
            font-size: 14px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 2px;
        }

        .ap-active-desc {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            margin: 0;
            line-height: 1.4;
        }

        .ap-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        .ap-switch input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .ap-switch-slider {
            width: 48px;
            height: 28px;
            border-radius: 999px;
            background: #d1d5db;
            position: relative;
            transition: .2s ease;
        }

        .ap-add-header {
            position: relative;
            flex-shrink: 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid #eef2f7;
            background: #ffffff;
            overflow: hidden;
        }

        .ap-add-header::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 5px;
            background: linear-gradient(90deg, #8b0000, #dc2626);
        }

        #editModal .ap-add-header::before {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
        }

        .ap-add-header-left {
            display: flex;
            align-items: center;
            gap: .95rem;
            min-width: 0;
            flex: 1;
        }

        .ap-add-header-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
            background: linear-gradient(145deg, #8b0000, #b80000);
        }

        #editModal .ap-add-header-icon {
            background: linear-gradient(145deg, #2563eb, #1d4ed8);
        }

        .ap-add-header-title {
            font-size: 1.5rem;
            font-weight: 900;
            line-height: 1.1;
            color: #0f172a;
            margin: 0;
        }

        .ap-add-header-subtitle {
            margin: .28rem 0 0;
            font-size: .88rem;
            color: #64748b;
            font-weight: 500;
            line-height: 1.35;
        }

        .ap-add-close {
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

        .ap-add-close:hover {
            color: #8b0000;
            border-color: #fecaca;
            background: #fff5f5;
        }

        #editModal .ap-add-close:hover {
            color: #2563eb;
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .ap-switch-slider::after {
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

        .ap-switch input:checked+.ap-switch-slider {
            background: var(--switch-color, #8B0000);
        }

        .ap-switch input:checked+.ap-switch-slider::after {
            transform: translateX(20px);
        }

        .ap-add-footer {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .75rem;
            padding: 1rem 1.5rem 1.15rem;
            border-top: 1px solid #eef2f7;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
        }

        .ap-add-btn {
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

        .ap-desc-panel {
            display: flex;
            flex-direction: column;
        }

        .ap-desc-panel .field-error {
            margin-top: .45rem;
        }

        .ap-add-btn-cancel {
            background: #fff;
            color: #4b5563;
            border-color: #e5e7eb;
        }

        .ap-add-btn-cancel:hover {
            background: #f9fafb;
            border-color: #cbd5e1;
        }

        .ap-add-btn-submit {
            background: linear-gradient(145deg, #8b0000, #760000);
            color: #fff;
            box-shadow: 0 10px 20px rgba(139, 0, 0, 0.16);
        }

        .ap-add-btn-submit:hover {
            filter: brightness(1.04);
        }

        @media (max-width: 991px) {
            .ap-add-modal .modal-box {
                max-width: 880px;
            }

            .ap-add-body {
                gap: 1rem;
            }

            .ap-semester-grid-redesign {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
        .ap-add-modal {
            align-items: flex-end;
            justify-content: center;
            padding: 0;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(5px);
        }

        .ap-add-modal .modal-box {
            width: 100%;
            max-width: 100%;
            height: min(92dvh, 92vh);
            max-height: min(92dvh, 92vh);
            margin: 0;
            border-radius: 22px 22px 0 0;
            border-left: none;
            border-right: none;
            border-bottom: none;
            box-shadow: 0 -18px 42px rgba(15, 23, 42, 0.18);
            transform: translateY(100%);
            transition: transform .28s cubic-bezier(.22, 1, .36, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .ap-add-modal.open .modal-box {
            transform: translateY(0);
        }

        .ap-add-modal .modal-box::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 44px;
            height: 5px;
            border-radius: 999px;
            background: #cbd5e1;
            z-index: 40;
        }

        .ap-add-form {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        .ap-add-header {
            flex-shrink: 0;
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) 38px;
            align-items: start;
            column-gap: .75rem;
            padding: 1.35rem 1rem .95rem;
            padding-top: 2rem;
            border-radius: 22px 22px 0 0;
        }

        .ap-add-header-left {
            display: contents;
        }

        .ap-add-header-icon {
            grid-column: 1;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            flex-shrink: 0;
        }

        .ap-add-header-left > div:last-child {
            grid-column: 2;
            min-width: 0;
            width: 100%;
        }

        .ap-add-header-title {
            font-size: 1rem;
            line-height: 1.08;
            margin: 0;
            word-break: break-word;
        }

        .ap-add-header-subtitle {
            display: block;
            font-size: .72rem;
            margin-top: .22rem;
            line-height: 1.3;
            color: #64748b;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            max-width: 100%;
            padding-right: .15rem;
        }

        .ap-add-close {
            grid-column: 3;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            flex-shrink: 0;
            align-self: start;
            justify-self: end;
        }

        #editModal .ap-add-header-subtitle {
            max-width: 100%;
            font-size: .7rem;
            line-height: 1.28;
        }

        .ap-add-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            grid-template-columns: 1fr;
            padding: .95rem 1rem 1rem;
            gap: .8rem;
            align-content: start;
        }

        .ap-col-span-2 {
            grid-column: span 1;
        }

        .ap-panel,
        .ap-panel-soft {
            padding: .9rem;
            border-radius: 16px;
            min-width: 0;
        }

        .ap-label {
            margin-bottom: .45rem;
        }

        .ap-label-text,
        .ap-label-hint {
            font-size: 10px;
        }

        .ap-input-wrap,
        .ap-textarea-wrap {
            width: 100%;
            min-width: 0;
        }

        .ap-input {
            height: 48px;
            border-radius: 13px;
            font-size: 14px;
        }

        .ap-date-grid-redesign,
        .ap-semester-grid-redesign {
            grid-template-columns: 1fr;
            gap: .7rem;
        }

        .ap-semester-card {
            min-height: 70px;
            border-radius: 15px;
            padding: .8rem;
        }

        .ap-semester-card span {
            font-size: 12px;
        }

        .ap-textarea {
            min-height: 140px;
            height: 140px;
            max-height: 140px;
            font-size: 12px;
            padding: 14px 15px;
        }

        .ap-desc-meta {
            gap: .5rem;
            align-items: flex-start;
        }

        .ap-desc-help,
        .ap-word-counter {
            font-size: 11px;
        }

        .ap-active-card {
            flex-direction: column;
            align-items: stretch;
            gap: .8rem;
            padding: .95rem;
        }

        .ap-active-card-left {
            gap: .7rem;
        }

        .ap-active-badge {
            width: 38px;
            height: 38px;
        }

        .ap-active-title {
            font-size: 13px;
        }

        .ap-active-desc {
            font-size: 11px;
            line-height: 1.45;
        }

        .ap-switch {
            align-self: flex-end;
        }

        .ap-add-footer {
            flex-shrink: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .7rem;
            padding: .85rem 1rem calc(.95rem + env(safe-area-inset-bottom));
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(12px);
            border-top: 1px solid #e9eef5;
        }

        .ap-add-footer .ap-add-btn {
            width: 100%;
            min-width: 0;
            height: 44px;
            border-radius: 13px;
            font-size: 12px;
            padding: 0 .85rem;
        }
    }

        [data-theme="dark"] .ap-add-header,
        [data-theme="dark"] .ap-add-footer,
        [data-theme="dark"] .ap-panel,
        [data-theme="dark"] .ap-panel-soft,
        [data-theme="dark"] .ap-active-card,
        [data-theme="dark"] .ap-add-close {
            background: #161b22 !important;
            border-color: #2b313a !important;
        }

        [data-theme="dark"] .ap-add-header-title,
        [data-theme="dark"] .ap-active-title {
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .ap-add-header-subtitle,
        [data-theme="dark"] .ap-active-desc,
        [data-theme="dark"] .ap-label-text,
        [data-theme="dark"] .ap-label-hint {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .ap-input,
        [data-theme="dark"] .ap-textarea,
        [data-theme="dark"] .ap-semester-card {
            background: #0d1117 !important;
            border-color: #2b313a !important;
            color: #e5e7eb !important;
        }

        @media (max-width: 767px) {
            .ap-modal-sem-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .ap-sem-option {
                min-height: 68px;
                font-size: 12px;
            }
        }

        .ap-date-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .ap-active-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #f0e2e2;
            background: linear-gradient(90deg, #fff8f8 0%, #f9fafb 100%);
            border-radius: 14px;
            padding: .85rem 1rem;
            gap: 1rem;
        }

        .ap-modal-footer {
            position: sticky;
            bottom: 0;
            z-index: 10;
            padding: .9rem 1.35rem 1.1rem;
            border-top: 1px solid #f5dede;
            background: linear-gradient(180deg, rgba(255, 255, 255, .94), #ffffff 70%);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .6rem;
        }

        .ap-btn {
            height: 42px;
            padding: 0 1.1rem;
            border-radius: 11px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            font-size: 13px;
            font-weight: 700;
            transition: all .2s;
        }

        .ap-btn-ghost {
            color: #4b5563;
            border-color: #e5e7eb;
            background: #fff;
        }

        .ap-btn-ghost:hover {
            border-color: #cbd5e1;
            background: #f9fafb;
        }

        .ap-btn-primary {
            color: #fff;
            background: linear-gradient(145deg, #8b0000, #760000);
        }

        .ap-btn-primary:hover {
            filter: brightness(1.04);
        }

        .ap-btn-primary-blue {
            color: #fff;
            background: linear-gradient(145deg, #2563eb, #1d4ed8);
        }

        .ap-btn-primary-blue:hover {
            filter: brightness(1.05);
        }

        .ap-btn-danger {
            color: #fff;
            background: linear-gradient(145deg, #b91c1c, #dc2626);
        }

        .ap-btn-danger:hover {
            filter: brightness(1.05);
        }

        .ap-delete-shell {
            max-width: 460px;
        }

        .ap-delete-body {
            padding: 1.4rem;
            text-align: center;
            display: grid;
            gap: .5rem;
        }

        .ap-delete-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem;
            margin-top: .65rem;
        }

        .field-input {
            transition: border-color .15s, box-shadow .15s;
        }

        .field-input:focus {
            outline: none;
            border-color: #8B0000 !important;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1 }
            50% { opacity: .35 }
        }

        .dot-pulse {
            animation: pulse 2s cubic-bezier(.4, 0, .6, 1) infinite;
        }

        /* ── Voice mic pulse animation ── */
        @keyframes micPulse {
            0%, 100% { box-shadow: 0 0 0 0px rgba(192, 57, 43, 0.4); }
            50%       { box-shadow: 0 0 0 8px rgba(192, 57, 43, 0); }
        }

        /* ── External circular mic button (search bar) ── */
        .ap-voice-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
        }

        .ap-voice-mic-ext {
            display: inline-flex;
            width: 38px;
            height: 38px;
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
        }

        .ap-voice-mic-ext:hover {
            background: #374151;
        }

        .ap-voice-mic-ext i {
            font-size: 12px;
            line-height: 1;
        }

        .ap-voice-mic-ext.mic-active {
            background: #c0392b;
            transform: scale(1.1);
            animation: micPulse 1.2s ease-in-out infinite;
        }

        .ap-voice-status {
            position: absolute;
            right: 0;
            top: -1.55rem;
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

        .ap-voice-status.hidden { display: none; }

        .ap-voice-status.is-listening {
            color: #1d4ed8;
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .ap-voice-status.is-error {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fef2f2;
        }

        .ap-voice-status.is-success {
            color: #166534;
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        /* Dark mode for the mic buttons */
        [data-theme="dark"] .ap-voice-mic-ext,
        [data-theme="dark"] .ap-textarea-wrap .voice-mic-btn {
            background: rgba(255, 255, 255, 0.10) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #d1d5db !important;
        }

        [data-theme="dark"] .ap-voice-mic-ext:hover,
        [data-theme="dark"] .ap-textarea-wrap .voice-mic-btn:hover {
            background: rgba(255, 255, 255, 0.16) !important;
        }

        [data-theme="dark"] .ap-voice-mic-ext.mic-active,
        [data-theme="dark"] .ap-textarea-wrap .voice-mic-btn.is-listening {
            background: #c0392b !important;
            color: #fff !important;
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
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        .search-wrap i {
            color: #8B0000;
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

        .ap-field-block {
            display: grid;
            gap: 8px;
        }

        .ap-input-shell {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .ap-input-left-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: #9ca3af;
            pointer-events: none;
            z-index: 2;
        }

        .ap-text-input {
            width: 100%;
            height: 50px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding-left: 42px;
            padding-right: 44px;
            font-size: 14px;
            line-height: 1.2;
            background: #fff;
        }

        .ap-text-input::placeholder {
            color: #a1a1aa;
        }

        .search-wrap input::placeholder {
            color: #B0ABA6;
        }

        .search-clear-btn {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: none;
            background: #E0DDD8;
            color: #7A7370;
            font-size: 10px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all .2s;
            padding: 0;
        }

        .search-clear-btn:hover {
            background: #8b000076;
            color: #fff;
        }

        .search-clear-btn.visible {
            display: flex;
        }

        .filter-select {
            height: 38px;
            border: 1.5px solid #E0DDD8;
            border-radius: 12px;
            background: #FAFAF9;
            padding: 0 12px;
            font-size: 12px;
            color: #4b5563;
            outline: none;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s;
        }

        .filter-select:focus {
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
        }

        .filter-btn {
            height: 38px;
            padding: 0 16px;
            border-radius: 10px;
            background: #8B0000;
            color: white;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
        }

        .filter-btn:hover {
            background: #760000;
        }

        .reset-btn {
            height: 38px;
            padding: 0 16px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .reset-btn:hover {
            background: #f9fafb;
        }

        [data-theme="dark"] .border-gray-100 {
            border-color: #21262d !important;
        }

        [data-theme="dark"] .border-gray-200 {
            border-color: #21262d !important;
        }

        [data-theme="dark"] .text-gray-800 {
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .text-\[\#333333\] {
            color: #E5E7EB !important;
        }

        [data-theme="dark"] .flyout-panel {
            background: #161b22;
            border-color: #2d1a1a;
        }

        [data-theme="dark"] .flyout-link {
            color: #d1d5db;
        }

        [data-theme="dark"] .modal-box {
            background: #161b22;
            border-color: #2b313a;
        }

        [data-theme="dark"] .ap-modal-header {
            background: linear-gradient(180deg, #1a1f27 0%, #161b22 100%);
            border-color: #2b313a;
        }

        [data-theme="dark"] .ap-modal-body {
            background: linear-gradient(180deg, #161b22 0%, #0f141a 100%);
        }

        [data-theme="dark"] .ap-modal-footer {
            background: linear-gradient(180deg, rgba(22, 27, 34, .9), #161b22 70%);
            border-color: #2b313a;
        }

        [data-theme="dark"] .ap-active-toggle {
            border-color: #2b313a;
            background: #0f141a;
        }

        [data-theme="dark"] .ap-sem-option {
            background: #0d1117;
            border-color: #2b313a;
            color: #d1d5db;
        }

        [data-theme="dark"] .ap-btn-ghost {
            background: #0f141a;
            border-color: #2b313a;
            color: #d1d5db;
        }

        [data-theme="dark"] .modal-box input,
        [data-theme="dark"] .modal-box select,
        [data-theme="dark"] .modal-box textarea {
            background: #0d1117 !important;
            border-color: #21262d !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .tbl-row:hover {
            background: #0d1117;
        }

        [data-theme="dark"] .tbl-row.is-active {
            background: rgba(139, 0, 0, .07);
        }

        [data-theme="dark"] thead tr {
            background: #0d1117 !important;
        }

        [data-theme="dark"] tr {
            border-color: #21262d !important;
        }

        [data-theme="dark"] .active-banner {
            background: #161b22 !important;
        }

        [data-theme="dark"] .progress-track {
            background: #21262d;
        }

        [data-theme="dark"] .modal-box .bg-gray-50 {
            background: #0d1117 !important;
        }

        [data-theme="dark"] .cal-card {
            background: #161b22 !important;
            border-color: #21262d !important;
        }

        .field-error {
            font-size: 11px;
            color: #dc2626;
            margin-top: 4px;
            display: none;
            align-items: center;
            gap: 4px;
            font-weight: 600;
        }

        .field-error.show {
            display: flex;
        }

        .field-invalid {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .1) !important;
        }

        .field-valid {
            border-color: #16a34a !important;
        }

        .ap-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .ap-toolbar-left {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .ap-toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .ap-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .ap-table {
            width: 100%;
            min-width: 760px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .ap-table th,
        .ap-table td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .ap-table td.col-year,
        .ap-table td.col-semester {
            white-space: normal;
        }

        .ap-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .ap-empty {
            padding: 3rem 1rem;
        }

        .academic-grid-view {
            display: none;
            padding: 14px;
            gap: 12px;
        }

        .academic-card {
            background: #fff;
            border: 1px solid #f1e8e8;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
        }

        .academic-card.is-active {
            border-color: #f3c9c9;
            box-shadow: 0 4px 16px rgba(139, 0, 0, .08);
        }

        .academic-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .academic-card-year {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
        }

        .academic-card-year-text {
            font-size: 14px;
            font-weight: 800;
            color: #111827;
            line-height: 1.2;
        }

        .academic-card-meta {
            display: grid;
            gap: 10px;
            margin-bottom: 14px;
        }

        .academic-card-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .academic-card-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #9ca3af;
            min-width: 70px;
        }

        .academic-card-value {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            text-align: right;
            line-height: 1.35;
        }

        .academic-card-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .academic-card-actions .act,
        .academic-card-actions form {
            flex: 1;
        }

        .academic-card-actions form .act {
            width: 100%;
        }

        @media (max-width: 1023px) {
            .ap-toolbar {
                align-items: stretch;
            }

            .ap-toolbar-right {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 767px) {
            #mainContent {
                padding-left: 0.9rem !important;
                padding-right: 0.9rem !important;
                padding-top: 74px !important;
            }

            .active-banner-inner {
                padding: 1rem;
            }

            .ap-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .ap-toolbar-left,
            .ap-toolbar-right {
                width: 100%;
            }

            .ap-toolbar-right {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .search-wrap,
            .filter-select,
            .filter-btn,
            .reset-btn {
                width: 100% !important;
            }

            .ap-voice-toggle {
                width: 100%;
                justify-content: center;
            }

            .filter-select,
            .filter-btn,
            .reset-btn {
                justify-content: center;
            }

            #academicViewToggle {
                display: none !important;
            }

            #academicListView {
                display: none !important;
            }

            #academicGridView {
                display: grid !important;
                grid-template-columns: 1fr;
            }

            .ap-table {
                min-width: 640px;
            }

            .ap-table th,
            .ap-table td {
                padding: 10px 12px !important;
                font-size: 12px;
            }

            .ap-table thead th:nth-child(4),
            .ap-table tbody td:nth-child(4),
            .ap-table thead th:nth-child(5),
            .ap-table tbody td:nth-child(5) {
                display: none;
            }

            .ap-actions {
                gap: 6px;
            }

            .act {
                width: 28px;
                height: 28px;
            }

            .sem-pill,
            .status-badge {
                font-size: 10px !important;
            }

            .modal-overlay:not(.ap-add-modal) {
                align-items: flex-end;
                padding: 0;
                padding-top: calc(var(--header-h, 68px) + 10px);
            }

            .modal-overlay:not(.ap-add-modal) .modal-box {
                width: 100%;
                max-width: 100%;
                max-height: calc(100dvh - var(--header-h, 68px) - 10px);
                border-radius: 20px 20px 0 0;
                border-left: none;
                border-right: none;
                border-bottom: none;
            }

            .ap-modal-header {
                padding: .95rem 1rem;
            }

            .ap-modal-body {
                padding: 1rem;
                gap: .9rem;
            }

            .ap-modal-sem-grid,
            .ap-date-grid,
            .ap-delete-actions {
                grid-template-columns: 1fr;
            }

            .ap-active-toggle {
                flex-direction: column;
                align-items: flex-start;
                gap: .7rem;
            }

            .ap-modal-footer {
                padding: .8rem 1rem 1rem;
                justify-content: stretch;
            }

            .ap-modal-footer .ap-btn {
                flex: 1;
            }

            .ap-delete-body {
                padding: 1.1rem 1rem;
            }
        }

        @media (max-width: 640px) {
        #editModal .ap-add-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: .9rem 1rem calc(1rem + env(safe-area-inset-bottom));
        }

        #editModal .ap-add-footer .ap-add-btn {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        #editModal .ap-add-footer .ap-add-btn[type="submit"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            text-align: center;
            line-height: 1.15;
            padding: 0 1rem;
        }

        #editModal .ap-add-footer .ap-add-btn[type="submit"] i {
            flex-shrink: 0;
            font-size: 12px;
            line-height: 1;
        }
    }

        [data-theme="dark"] #mainContent {
            background: #020c16 !important;
        }

        [data-theme="dark"] .bg-white.rounded-xl.shadow.border.border-gray-100,
        [data-theme="dark"] .cal-card,
        [data-theme="dark"] .active-banner,
        [data-theme="dark"] .academic-card {
            background: linear-gradient(145deg, rgba(22,27,34,.90), rgba(13,17,23,.82)) !important;
            border-color: rgba(255,255,255,.10) !important;
            box-shadow: 0 14px 34px rgba(0,0,0,.32) !important;
        }

        [data-theme="dark"] .bg-gray-50,
        [data-theme="dark"] .ap-toolbar,
        [data-theme="dark"] .border-t.bg-gray-50 {
            background: rgba(13,17,23,.90) !important;
            border-color: rgba(255,255,255,.08) !important;
        }

        [data-theme="dark"] .search-wrap,
        [data-theme="dark"] .filter-select,
        [data-theme="dark"] .reset-btn,
        [data-theme="dark"] .academic-view-toggle {
            background: rgba(255,255,255,.06) !important;
            border-color: rgba(255,255,255,.12) !important;
            color: #d1d5db !important;
        }

        [data-theme="dark"] .search-wrap input {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .tbl-row,
        [data-theme="dark"] .ap-table tbody tr {
            background: rgba(255,255,255,.02) !important;
        }

        [data-theme="dark"] .tbl-row.is-active {
            background: rgba(252,165,165,.08) !important;
        }

        [data-theme="dark"] .tbl-row:hover {
            background: rgba(255,255,255,.05) !important;
        }

        [data-theme="dark"] .ap-table td,
        [data-theme="dark"] .ap-table td span,
        [data-theme="dark"] .academic-card-value,
        [data-theme="dark"] .academic-card-year-text,
        [data-theme="dark"] #liveDate {
            color: #d1d5db !important;
        }

        [data-theme="dark"] h2.text-gray-800,
        [data-theme="dark"] .ap-toolbar-left h2,
        [data-theme="dark"] .cal-card h2 {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] #liveClock {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .act,
        [data-theme="dark"] .sem-pill,
        [data-theme="dark"] .status-badge,
        [data-theme="dark"] #calYear {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        [data-theme="dark"] .act-edit {
            background: rgba(37,99,235,.13) !important;
            border: 1px solid rgba(96,165,250,.20) !important;
        }

        [data-theme="dark"] .act-star,
        [data-theme="dark"] .act-pinned {
            background: rgba(16,185,129,.13) !important;
            border: 1px solid rgba(52,211,153,.20) !important;
        }

        [data-theme="dark"] .act-del {
            background: rgba(239,68,68,.12) !important;
            border: 1px solid rgba(248,113,113,.20) !important;
        }

        [data-theme="dark"] .group {
            background: rgba(255,255,255,.04) !important;
            border-color: rgba(255,255,255,.10) !important;
        }

        [data-theme="dark"] .group > div:first-child {
            background: rgba(255,255,255,.07) !important;
            border-color: rgba(255,255,255,.12) !important;
        }

        [data-theme="dark"] #calendarList > div {
            border-color: rgba(255,255,255,.10) !important;
        }

        [data-theme="dark"] #calendarList div[style*="background"] {
            background: rgba(255,255,255,.08) !important;
            border-color: rgba(255,255,255,.12) !important;
        }

        [data-theme="dark"] .ap-table tbody tr.tbl-row {
            background: rgba(255,255,255,.025) !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.025) !important;
        }

        [data-theme="dark"] .ap-table tbody tr.tbl-row.is-active {
            background: rgba(252,165,165,.055) !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.035) !important;
        }

        [data-theme="dark"] .ap-table tbody tr.tbl-row:hover {
            background: rgba(255,255,255,.04) !important;
        }

        [data-theme="dark"] .sem-pill,
        [data-theme="dark"] .status-badge {
            background: rgba(255,255,255,.06) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            color: #d1d5db !important;
            backdrop-filter: blur(12px) saturate(150%) !important;
            -webkit-backdrop-filter: blur(12px) saturate(150%) !important;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.10),
                0 4px 12px rgba(0,0,0,.22) !important;
            text-shadow: none !important;
        }

        [data-theme="dark"] .sem-pill::before,
        [data-theme="dark"] .status-badge::before {
            display: none !important;
        }

        [data-theme="dark"] .academic-item[data-semester="Summer"] .sem-pill {
            background: linear-gradient(135deg, rgba(251,191,36,.24), rgba(245,158,11,.10)) !important;
            border: 1px solid rgba(252,211,77,.32) !important;
            color: #fcd34d !important;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.08),
                0 2px 6px rgba(245,158,11,.12) !important;
        }

        [data-theme="dark"] .academic-item[data-semester="2nd Semester"] .sem-pill {
            background: linear-gradient(135deg, rgba(96,165,250,.24), rgba(37,99,235,.10)) !important;
            border: 1px solid rgba(147,197,253,.32) !important;
            color: #93c5fd !important;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.08),
                0 2px 6px rgba(59,130,246,.12) !important;
        }

        [data-theme="dark"] .academic-item[data-semester="1st Semester"] .sem-pill {
            background: linear-gradient(135deg, rgba(248,113,113,.24), rgba(239,68,68,.10)) !important;
            border: 1px solid rgba(252,165,165,.32) !important;
            color: #fca5a5 !important;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.08),
                0 2px 6px rgba(239,68,68,.12) !important;
        }

        [data-theme="dark"] .academic-item .sem-pill i {
            color: currentColor !important;
        }

        [data-theme="dark"] .status-badge.s-upcoming {
            background: rgba(37,99,235,.13) !important;
            border-color: rgba(96,165,250,.20) !important;
            color: #93c5fd !important;
        }

        [data-theme="dark"] .status-badge.s-active {
            background: rgba(236,72,153,.13) !important;
            border-color: rgba(244,114,182,.20) !important;
            color: #f9a8d4 !important;
        }

        [data-theme="dark"] .status-badge.s-ended {
            background: rgba(107,114,128,.13) !important;
            border-color: rgba(156,163,175,.20) !important;
            color: #d1d5db !important;
        }

        [data-theme="dark"] .status-badge.s-inactive {
            background: rgba(245,158,11,.13) !important;
            border-color: rgba(251,191,36,.20) !important;
            color: #facc15 !important;
        }

        [data-theme="dark"] .sem-pill i {
            color: currentColor !important;
            opacity: .8;
        }

        [data-theme="dark"] #openAddPeriodQuickBtn .font-bold,
        [data-theme="dark"] #openEditPeriodQuickBtn .font-bold {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] #calendarList div {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] #calendarList div > div:first-child {
            color: #E5E7EB !important;
        }

        [data-theme="dark"] #calendarList div > div:nth-child(2) {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .ap-add-modal .modal-box,
        [data-theme="dark"] .ap-add-header,
        [data-theme="dark"] .ap-add-body,
        [data-theme="dark"] .ap-add-footer,
        [data-theme="dark"] .ap-panel,
        [data-theme="dark"] .ap-panel-soft,
        [data-theme="dark"] .ap-active-card,
        [data-theme="dark"] .ap-semester-card,
        [data-theme="dark"] .ap-input,
        [data-theme="dark"] .ap-textarea {
            background: #161b22 !important;
            border-color: #2b313a !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .ap-add-header-title,
        [data-theme="dark"] .ap-active-title {
            color: #e5e7eb !important;
        }

        [data-theme="dark"] .ap-add-header-subtitle,
        [data-theme="dark"] .ap-active-desc,
        [data-theme="dark"] .ap-label-text,
        [data-theme="dark"] .ap-label-hint {
            color: #9ca3af !important;
        }

        [data-theme="dark"] .ap-add-close {
            background: #161b22 !important;
            border-color: #2b313a !important;
            color: #9ca3af !important;
        }

        [data-theme="dark"] .ap-add-close:hover {
            color: #f87171 !important;
            background: rgba(255,255,255,.05) !important;
        }

        input[type="date"] {
            color: #111827;
        }

        input[type="date"]::-webkit-datetime-edit {
            color: #111827;
        }

        input[type="date"]:invalid::-webkit-datetime-edit {
            color: #9ca3af;
        }

        [data-theme="dark"] .ap-add-header-title {
            color: #FCA5A5 !important;
        }
    </style>
@endsection

@php
    $calendarPeriodsPayload = collect($calendarPeriods ?? [])
        ->sortBy('start_date')
        ->map(function ($period) {
            return [
                'id' => $period->id,
                'academic_year' => $period->academic_year,
                'semester' => $period->semester,
                'start_date' => optional($period->start_date)->format('Y-m-d'),
                'end_date' => optional($period->end_date)->format('Y-m-d'),
            ];
        })
        ->values()
        ->all();

    $holidayEvents = collect($holidays ?? [])
        ->map(function ($name, $date) {
            return [
                'date' => $date,
                'label' => $name,
                'year' => date('Y', strtotime($date)),
                'color' => '#6b7280',
                'type' => 'holiday',
            ];
        })
        ->values()
        ->all();

    $activePeriodPayload = $activePeriod
        ? [
            'id' => $activePeriod->id,
            'academic_year' => $activePeriod->academic_year,
            'semester' => $activePeriod->semester,
            'start_date' => optional($activePeriod->start_date)->format('Y-m-d'),
            'end_date' => optional($activePeriod->end_date)->format('Y-m-d'),
            'description' => $activePeriod->description,
            'is_active' => (bool) $activePeriod->is_active,
        ]
        : null;
@endphp

@section('content')
    <main id="mainContent"
        style="padding-top:82px; padding-bottom:2rem; padding-left:1.5rem; padding-right:1.5rem; min-height:100vh;">
        <div style="max-width:1280px; margin:0 auto;">

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <div class="font-bold mb-1">Please fix the following:</div>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Academic Periods</h1>
                    </div>

                    <button id="openAddPeriodBtn" type="button" data-open-modal="addModal" onclick="openModal('addModal')"
                        class="flex items-center gap-2 bg-white hover:bg-gray-100 text-[#8B0000] px-5 py-2.5 rounded-lg font-semibold text-sm shadow transition-all">
                        <i class="fa-solid fa-plus"></i>
                        Add Period
                    </button>
                </div>
            </div>

            <div class="active-banner mb-6" id="activeBannerWrap">
                <div class="active-banner-inner">
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-calendar text-[#8B0000] text-sm"></i>
                                <p class="text-[10px] tracking-widest text-gray-500 uppercase font-semibold">Current
                                    Semester</p>
                            </div>
                            <p class="text-xl font-bold text-gray-800" id="bannerSem">
                                {{ $activePeriod?->semester ?? 'No Active Period' }}
                            </p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex h-4 w-4 items-center justify-center leading-none shrink-0">
                                    <i class="fa-solid fa-graduation-cap text-[#8B0000] text-sm"></i>
                                </span>
                                <p class="text-[10px] tracking-widest text-gray-500 uppercase font-semibold">Academic Year
                                </p>
                            </div>
                            <p class="text-xl font-bold text-gray-800" id="bannerYear">
                                {{ $activePeriod?->academic_year ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-clock text-[#8B0000] text-sm"></i>
                                <p class="text-[10px] tracking-widest text-gray-500 uppercase font-semibold">Period Ends</p>
                            </div>
                            <p class="text-xl font-bold text-gray-800" id="bannerEnd">
                                {{ $activePeriod ? $activePeriod->end_date->format('F d, Y') : '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 lg:flex-shrink-0 lg:w-64">
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-[10px] text-gray-500 uppercase tracking-widest font-semibold">Semester
                                    Progress</span>
                                <span class="text-[11px] font-bold text-[#8B0000]" id="bannerPct">
                                    {{ $activePeriod?->progress_percent ?? 0 }}%
                                </span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" id="bannerFill"
                                    style="width:{{ $activePeriod?->progress_percent ?? 0 }}%;">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1" id="bannerDaysLeft">
                                {{ $activePeriod
                                    ? $activePeriod->days_remaining . ' day' . ($activePeriod->days_remaining !== 1 ? 's' : '') . ' remaining'
                                    : 'No active period' }}
                            </p>
                        </div>

                        <button type="button"
                            onclick='@if ($activePeriodPayload) openEditModal(@json($activePeriodPayload)) @endif'
                            class="bg-[#8B0000] hover:bg-[#760000] text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-gear"></i> Manage Period
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">

                        <div class="px-5 py-4 border-b bg-gray-50 ap-toolbar">
                            <div class="ap-toolbar-left">
                                <i class="fa-solid fa-school text-[#8B0000]"></i>
                                <h2 class="font-bold text-gray-800 text-sm">All Academic Periods</h2>
                                <span id="periodCount"
                                    class="text-[10px] font-bold bg-[#8B0000] text-white px-2 py-0.5 rounded-full">
                                    {{ $academicPeriods->total() }}
                                </span>
                            </div>

                            <form method="GET" action="{{ route('admin.academic_periods') }}" id="filterForm"
                                class="ap-toolbar-right">

                                <div class="search-wrap" style="width:220px;">
                                    <i class="fa fa-search"></i>
                                    <input id="searchInput" name="search" type="text" placeholder="Search periods…"
                                        value="{{ request('search') }}" autocomplete="off">
                                    <button type="button" id="clearSearch"
                                        class="search-clear-btn {{ request('search') ? 'visible' : '' }}" title="Clear">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                {{-- ── External circular mic button (search bar) ── --}}
                                <div class="ap-voice-toggle">
                                    <button type="button" id="apMicToggleBtn" class="ap-voice-mic-ext"
                                        aria-label="Toggle voice search" aria-pressed="false">
                                        <i class="fa-solid fa-microphone"></i>
                                    </button>
                                    <span id="apVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
                                </div>

                                <select name="semester" id="semesterFilter" class="filter-select">
                                    <option value="">All Semesters</option>
                                    <option value="1st Semester"
                                        {{ request('semester') === '1st Semester' ? 'selected' : '' }}>1st Semester
                                    </option>
                                    <option value="2nd Semester"
                                        {{ request('semester') === '2nd Semester' ? 'selected' : '' }}>2nd Semester
                                    </option>
                                    <option value="Summer" {{ request('semester') === 'Summer' ? 'selected' : '' }}>Summer
                                    </option>
                                </select>

                                <select name="status" id="statusFilter" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="Upcoming" {{ request('status') === 'Upcoming' ? 'selected' : '' }}>
                                        Upcoming</option>
                                    <option value="Ended" {{ request('status') === 'Ended' ? 'selected' : '' }}>Ended
                                    </option>
                                    <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>
                                        Inactive</option>
                                </select>

                                <button type="button" class="filter-btn">Filter</button>

                                <button type="button" class="reset-btn" onclick="resetAcademicFilters()">
                                    Reset
                                </button>

                                <div class="academic-view-toggle" id="academicViewToggle">
                                    <button type="button" class="academic-view-btn active" id="academicListBtn"
                                        title="List view" aria-label="List view">
                                        <i class="fa-solid fa-table-list"></i>
                                    </button>
                                    <button type="button" class="academic-view-btn" id="academicGridBtn"
                                        title="Grid view" aria-label="Grid view">
                                        <i class="fa-solid fa-grip"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div id="academicListView" class="ap-table-wrap scrollbar-thin">
                            <table class="ap-table text-sm">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr class="text-[10px] uppercase tracking-wide text-[#8B0000] font-bold">
                                        <th class="py-3 px-4 text-left">#</th>
                                        <th class="py-3 px-4 text-left">Year</th>
                                        <th class="py-3 px-4 text-left">Semester</th>
                                        <th class="py-3 px-4 text-left">Start</th>
                                        <th class="py-3 px-4 text-left">End</th>
                                        <th class="py-3 px-4 text-center">Status</th>
                                        <th class="py-3 px-4 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="academicTableBody">
                                    @forelse($academicPeriods as $index => $period)
                                        @php
                                            $statusClass = match ($period->status) {
                                                'Active' => 's-active',
                                                'Upcoming' => 's-upcoming',
                                                'Ended' => 's-ended',
                                                default => 's-inactive',
                                            };

                                            $semStyle = match ($period->semester) {
                                                '1st Semester' => ['bg' => '#fee2e2', 'color' => '#8B0000'],
                                                '2nd Semester' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                                                'Summer' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                                default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                            };

                                            $periodPayload = [
                                                'id' => $period->id,
                                                'academic_year' => $period->academic_year,
                                                'semester' => $period->semester,
                                                'start_date' => optional($period->start_date)->format('Y-m-d'),
                                                'end_date' => optional($period->end_date)->format('Y-m-d'),
                                                'description' => $period->description,
                                                'is_active' => (bool) $period->is_active,
                                            ];
                                        @endphp

                                        <tr class="tbl-row academic-item {{ $period->is_active ? 'is-active' : '' }} border-b border-gray-50 last:border-0"
                                            data-semester="{{ $period->semester }}" data-status="{{ $period->status }}"
                                            data-search="{{ strtolower($period->academic_year . ' ' . $period->semester . ' ' . $period->status . ' ' . optional($period->start_date)->format('M d, Y') . ' ' . optional($period->end_date)->format('M d, Y')) }}">
                                            <td class="py-3 px-4 text-sm">{{ $academicPeriods->firstItem() + $index }}
                                            </td>

                                            <td class="py-3 px-4 col-year">
                                                <div class="flex items-center">
                                                    @if ($period->is_active)
                                                        <span class="dot-pulse"
                                                            style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#22c55e;margin-right:6px;"></span>
                                                    @else
                                                        <span
                                                            style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#e5e7eb;margin-right:6px;"></span>
                                                    @endif
                                                    <span class="font-bold text-sm">{{ $period->academic_year }}</span>
                                                </div>
                                            </td>

                                            <td class="py-3 px-4 col-semester">
                                                <span class="sem-pill"
                                                    style="background:{{ $semStyle['bg'] }};color:{{ $semStyle['color'] }};">
                                                    <i class="fa-solid {{ $period->semester === 'Summer' ? 'fa-sun' : 'fa-book' }}"
                                                        style="font-size:9px;"></i>
                                                    {{ str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester) }}
                                                </span>
                                            </td>

                                            <td class="py-3 px-4 text-xs text-gray-600">
                                                {{ optional($period->start_date)->format('M d, Y') }}
                                            </td>
                                            <td class="py-3 px-4 text-xs text-gray-600">
                                                {{ optional($period->end_date)->format('M d, Y') }}
                                            </td>

                                            <td class="py-3 px-4 text-center">
                                                <span class="status-badge {{ $statusClass }}"
                                                    style="display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;">
                                                    {{ $period->status }}
                                                </span>
                                            </td>

                                            <td class="py-3 px-4">
                                                <div class="ap-actions">
                                                    <button type="button" class="act act-edit" title="Edit"
                                                        onclick='openEditModal(@json($periodPayload))'>
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>

                                                    @if (!$period->is_active)
                                                        <form method="POST"
                                                            action="{{ route('admin.academic_periods.set_active', $period) }}"
                                                            class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="act act-star"
                                                                title="Set as active">
                                                                <i class="fa-solid fa-circle-check"
                                                                    style="font-size:10px;"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="act act-pinned"><i class="fa-solid fa-star"
                                                                style="font-size:10px;"></i></span>
                                                    @endif

                                                    @php
                                                        $label =
                                                            $period->academic_year .
                                                            ' — ' .
                                                            str_replace(
                                                                ['1st', '2nd'],
                                                                ['First', 'Second'],
                                                                $period->semester,
                                                            );
                                                    @endphp

                                                    <button type="button" class="act act-del" title="Delete"
                                                        onclick='openDeleteModal(@json(route('admin.academic_periods.destroy', $period)), @json($label))'>
                                                        <i class="fa-solid fa-trash" style="font-size:10px;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="serverEmptyState">
                                            <td colspan="7" class="text-center text-gray-400 ap-empty">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <i class="fa-solid fa-school text-3xl mb-3 opacity-30 block"></i>
                                                    <p class="text-sm font-medium">No academic periods found.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div id="academicGridView" class="academic-grid-view">
                            @forelse($academicPeriods as $index => $period)
                                @php
                                    $statusClass = match ($period->status) {
                                        'Active' => 's-active',
                                        'Upcoming' => 's-upcoming',
                                        'Ended' => 's-ended',
                                        default => 's-inactive',
                                    };

                                    $semStyle = match ($period->semester) {
                                        '1st Semester' => ['bg' => '#fee2e2', 'color' => '#8B0000'],
                                        '2nd Semester' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                                        'Summer' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                        default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                    };

                                    $periodPayload = [
                                        'id' => $period->id,
                                        'academic_year' => $period->academic_year,
                                        'semester' => $period->semester,
                                        'start_date' => optional($period->start_date)->format('Y-m-d'),
                                        'end_date' => optional($period->end_date)->format('Y-m-d'),
                                        'description' => $period->description,
                                        'is_active' => (bool) $period->is_active,
                                    ];

                                    $label =
                                        $period->academic_year .
                                        ' — ' .
                                        str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester);
                                @endphp

                                <div class="academic-card academic-item {{ $period->is_active ? 'is-active' : '' }}"
                                    data-semester="{{ $period->semester }}" data-status="{{ $period->status }}"
                                    data-search="{{ strtolower($period->academic_year . ' ' . $period->semester . ' ' . $period->status . ' ' . optional($period->start_date)->format('M d, Y') . ' ' . optional($period->end_date)->format('M d, Y')) }}">

                                    <div class="academic-card-top">
                                        <div class="academic-card-year">
                                            @if ($period->is_active)
                                                <span class="dot-pulse"
                                                    style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;"></span>
                                            @else
                                                <span
                                                    style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#e5e7eb;"></span>
                                            @endif
                                            <div class="academic-card-year-text">{{ $period->academic_year }}</div>
                                        </div>

                                        <span class="status-badge {{ $statusClass }}"
                                            style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;padding:4px 9px;border-radius:99px;">
                                            {{ $period->status }}
                                        </span>
                                    </div>

                                    <div class="academic-card-meta">
                                        <div class="academic-card-row">
                                            <div class="academic-card-label">Semester</div>
                                            <div class="academic-card-value">
                                                <span class="sem-pill"
                                                    style="background:{{ $semStyle['bg'] }};color:{{ $semStyle['color'] }};">
                                                    <i class="fa-solid {{ $period->semester === 'Summer' ? 'fa-sun' : 'fa-book' }}"
                                                        style="font-size:9px;"></i>
                                                    {{ str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="academic-card-row">
                                            <div class="academic-card-label">Start</div>
                                            <div class="academic-card-value">
                                                {{ optional($period->start_date)->format('M d, Y') }}</div>
                                        </div>

                                        <div class="academic-card-row">
                                            <div class="academic-card-label">End</div>
                                            <div class="academic-card-value">
                                                {{ optional($period->end_date)->format('M d, Y') }}</div>
                                        </div>
                                    </div>

                                    <div class="academic-card-actions">
                                        <button type="button" class="act act-edit" title="Edit"
                                            onclick='openEditModal(@json($periodPayload))'>
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        @if (!$period->is_active)
                                            <form method="POST"
                                                action="{{ route('admin.academic_periods.set_active', $period) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="act act-star" title="Set as active">
                                                    <i class="fa-solid fa-circle-check" style="font-size:10px;"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="act act-pinned"><i class="fa-solid fa-star"
                                                    style="font-size:10px;"></i></span>
                                        @endif

                                        <button type="button" class="act act-del" title="Delete"
                                            onclick='openDeleteModal(@json(route('admin.academic_periods.destroy', $period)), @json($label))'>
                                            <i class="fa-solid fa-trash" style="font-size:10px;"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div id="serverEmptyStateGrid" class="text-center text-gray-400 ap-empty">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <i class="fa-solid fa-school text-3xl mb-3 opacity-30 block"></i>
                                        <p class="text-sm font-medium">No academic periods found.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        <div
                            class="px-5 py-3.5 border-t border-gray-100 bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <p class="text-xs text-gray-500">
                                Showing
                                <strong>{{ $academicPeriods->firstItem() ?? 0 }}–{{ $academicPeriods->lastItem() ?? 0 }}</strong>
                                of <strong>{{ $academicPeriods->total() }}</strong> periods
                            </p>

                            <div class="overflow-x-auto scrollbar-thin w-full md:w-auto">
                                {{ $academicPeriods->onEachSide(2)->links('vendor.pagination.tailwind') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">

                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                        <div class="px-5 py-4 border-b bg-gray-50 flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-[#8B0000]"></i>
                            <h2 class="font-bold text-gray-800 text-sm">Quick Actions</h2>
                        </div>
                        <div class="p-4 space-y-2.5">
                            <button id="openAddPeriodQuickBtn" type="button" data-open-modal="addModal"
                                onclick="openModal('addModal')"
                                class="w-full flex items-center gap-3 bg-gradient-to-r from-red-50 to-white hover:from-red-100 hover:to-red-50 border border-red-100 rounded-lg px-4 py-3 text-left transition-all group">
                                <div
                                    class="w-10 h-10 rounded-lg bg-white border border-red-200 flex items-center justify-center text-[#8B0000] shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-sm text-[#8B0000]">Add Period</div>
                                    <div class="text-[10px] text-gray-500">Create a new academic term</div>
                                </div>
                                <i
                                    class="fa-solid fa-chevron-right text-gray-300 text-xs group-hover:text-[#8B0000] group-hover:translate-x-1 transition-all"></i>
                            </button>

                            <button id="openEditPeriodQuickBtn" type="button"
                                onclick='@if ($activePeriodPayload) openEditModal(@json($activePeriodPayload)) @endif'
                                class="w-full flex items-center gap-3 bg-gradient-to-r from-red-50 to-white hover:from-red-100 hover:to-red-50 border border-red-100 rounded-lg px-4 py-3 text-left transition-all group">
                                <div
                                    class="w-10 h-10 rounded-lg bg-white border border-red-200 flex items-center justify-center text-[#8B0000] shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-pen"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-sm text-[#8B0000]">Edit Active Period</div>
                                    <div class="text-[10px] text-gray-500">Modify current semester</div>
                                </div>
                                <i
                                    class="fa-solid fa-chevron-right text-gray-300 text-xs group-hover:text-[#8B0000] group-hover:translate-x-1 transition-all"></i>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                        <div class="px-5 py-4 border-b bg-gray-50 flex items-center gap-2">
                            <i class="fa-solid fa-clock text-[#8B0000]"></i>
                            <h2 class="font-bold text-gray-800 text-sm">Date &amp; Time</h2>
                            <span class="ml-auto text-[10px] text-gray-400 font-semibold">Philippine Time</span>
                        </div>
                        <div class="p-5 text-center">
                            <div id="liveClock"
                                class="text-4xl font-extrabold text-[#8B0000] tracking-tight leading-none mb-1">
                                00:00:00
                            </div>
                            <div id="liveAmPm" class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">AM
                            </div>
                            <div id="liveDate" class="text-sm font-semibold text-gray-700 mb-1"></div>
                            <div id="liveDay" class="text-xs text-gray-400"></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden cal-card">
                        <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-days text-[#8B0000]"></i>
                                <h2 class="font-bold text-gray-800 text-sm">PUP Academic Calendar</h2>
                            </div>
                            <span id="calYear"
                                class="text-[9px] font-bold text-[#8B0000] bg-red-50 px-1.5 py-0.5 rounded-full whitespace-nowrap">
                                Academic Periods
                            </span>
                        </div>
                        <div id="calendarList" class="p-4 space-y-1 overflow-y-auto scrollbar-thin"
                            style="max-height:485px;"></div>
                        <div class="px-4 pb-4">
                            <a href="https://www.pup.edu.ph/calendar/" target="_blank"
                                class="flex items-center justify-center gap-2 w-full py-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-500 hover:bg-red-50 hover:border-[#8B0000] hover:text-[#8B0000] transition-all mt-2">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                View Full PUP Calendar
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    {{-- ════════════════════════════════════════════════
         ADD MODAL
    ════════════════════════════════════════════════ --}}
    <div class="modal-overlay ap-add-modal" id="addModal" onclick="closeModalOutside(event,'addModal')">
        <div class="modal-box">
            <form method="POST" action="{{ route('admin.academic_periods.store') }}" class="ap-add-form">
                @csrf

                <div class="ap-add-header">
                    <div class="ap-add-header-left">
                        <div class="ap-add-header-icon">
                            <i class="fa-solid fa-calendar-plus text-xl"></i>
                        </div>

                        <div>
                            <h3 class="ap-add-header-title">Add Academic Period</h3>
                            <p class="ap-add-header-subtitle">Add new semester or academic term schedule</p>
                        </div>
                    </div>

                    <button type="button" data-close-modal="addModal" class="ap-add-close">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="ap-add-body">
                    <div class="ap-panel ap-panel-soft">
                        <div class="ap-label">
                            <span class="ap-label-text">Academic Year <span class="text-red-500">*</span></span>
                        </div>

                        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                            <div class="ap-input-wrap" id="addAcademicYearWrap" style="flex: 1;">
                                <span class="ap-input-icon">
                                    <i class="fa-solid fa-calendar"></i>
                                </span>
                                <input name="academic_year" type="text" placeholder="e.g. 2026-2027"
                                    class="ap-input field-input no-voice" required>
                            </div>
                            <div class="ap-voice-toggle" style="margin-top: 0; position: relative;">
                                <button type="button" class="ap-voice-mic-ext" id="addYearMicBtn" aria-label="Voice input for academic year" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addYearVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>

                        <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                    </div>

                    <div class="ap-panel ap-panel-soft">
                        <div class="ap-label">
                            <span class="ap-label-text">Semester <span class="text-red-500">*</span></span>
                        </div>

                        <div class="ap-semester-grid-redesign">
                            <label class="ap-semester-item">
                                <input type="radio" name="semester" value="1st Semester" required>
                                <div class="ap-semester-card">
                                    <i class="fa-solid fa-book"></i>
                                    <span>1st Semester</span>
                                </div>
                            </label>

                            <label class="ap-semester-item">
                                <input type="radio" name="semester" value="2nd Semester" required>
                                <div class="ap-semester-card">
                                    <i class="fa-solid fa-book-open"></i>
                                    <span>2nd Semester</span>
                                </div>
                            </label>

                            <label class="ap-semester-item">
                                <input type="radio" name="semester" value="Summer" required>
                                <div class="ap-semester-card">
                                    <i class="fa-solid fa-sun"></i>
                                    <span>Summer</span>
                                </div>
                            </label>
                        </div>

                        <span class="sem-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                    </div>

                    <div class="ap-col-span-2 ap-panel">
                        <div class="ap-date-grid-redesign">
                            <div>
                                <div class="ap-label">
                                    <span class="ap-label-text">Start Date <span class="text-red-500">*</span></span>
                                </div>

                                <div class="ap-input-wrap">
                                    <span class="ap-input-icon">
                                        <i class="fa-solid fa-calendar-day"></i>
                                    </span>
                                    <input name="start_date" type="date" class="ap-input field-input"
                                        required min="1900-01-01" max="9999-12-31" inputmode="numeric">
                                </div>
                                <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                            </div>

                            <div>
                                <div class="ap-label">
                                    <span class="ap-label-text">End Date <span class="text-red-500">*</span></span>
                                </div>

                                <div class="ap-input-wrap">
                                    <span class="ap-input-icon">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </span>
                                    <input name="end_date" type="date" class="ap-input field-input"
                                        required min="1900-01-01" max="9999-12-31" inputmode="numeric">
                                </div>
                                <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                            </div>
                        </div>
                    </div>

                    {{-- ── Description with external circular mic button ── --}}
                    <div class="ap-col-span-2 ap-panel ap-desc-panel">
                        <div class="ap-label">
                            <span class="ap-label-text">Description</span>
                            <span class="ap-label-hint">Optional</span>
                        </div>

                        <div class="ap-textarea-wrap" id="addDescWrap">
                            <div class="ap-textarea-inner">
                                <span class="ap-placeholder">Add any notes about this academic period...</span>
                                <textarea name="description" rows="6"
                                    class="ap-textarea field-input no-voice" id="addDesc" data-word-limit="150" maxlength="150"></textarea>
                            </div>
                            <div style="position: relative;">
                                <button type="button" class="ap-voice-mic-ext" id="addDescMicBtn" aria-label="Voice input for description" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addDescVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>

                        <div class="ap-desc-meta">
                            <span class="ap-desc-help">Maximum of 150 characters</span>
                            <span class="ap-word-counter" id="addDescCounter">0 / 150 characters</span>
                        </div>
                        <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                    </div>

                    <div class="ap-col-span-2">
                        <div class="ap-active-card">
                            <div class="ap-active-card-left">
                                <div class="ap-active-badge">
                                    <i class="fa-solid fa-star text-sm"></i>
                                </div>

                                <div>
                                    <p class="ap-active-title">Set as Active Period</p>
                                    <p class="ap-active-desc">This will deactivate the currently active semester.</p>
                                </div>
                            </div>

                            <label class="ap-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1">
                                <span class="ap-switch-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="ap-add-footer">
                    <button type="button" data-close-modal="addModal" class="ap-add-btn ap-add-btn-cancel">
                        Cancel
                    </button>

                    <button type="submit" class="ap-add-btn ap-add-btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Period
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         EDIT MODAL
    ════════════════════════════════════════════════ --}}
    <div class="modal-overlay ap-add-modal" id="editModal" onclick="closeModalOutside(event,'editModal')">
        <div class="modal-box">
            <form method="POST" id="editForm" class="ap-add-form">
                @csrf
                @method('PUT')

                <div class="ap-add-header">
                    <div class="ap-add-header-left">
                        <div class="ap-add-header-icon" style="background: linear-gradient(145deg, #2563eb, #1d4ed8);">
                            <i class="fa-solid fa-pen text-xl"></i>
                        </div>

                        <div>
                            <h3 class="ap-add-header-title">Edit Academic Period</h3>
                            <p class="ap-add-header-subtitle" id="editSubtitle">Updating period details</p>
                        </div>
                    </div>

                    <button type="button" onclick="closeModal('editModal')" class="ap-add-close">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="ap-add-body">
                    <div class="ap-panel ap-panel-soft">
                        <div class="ap-label">
                            <span class="ap-label-text">Academic Year <span class="text-red-500">*</span></span>
                        </div>

                        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                            <div class="ap-input-wrap" id="editAcademicYearWrap" style="flex: 1;">
                                <span class="ap-input-icon">
                                    <i class="fa-solid fa-calendar"></i>
                                </span>
                                <input type="text" name="academic_year" id="editYear" class="ap-input field-input no-voice"
                                    placeholder="e.g. 2026-2027" required>
                            </div>
                            <div class="ap-voice-toggle" style="margin-top: 0; position: relative;">
                                <button type="button" class="ap-voice-mic-ext" id="editYearMicBtn" aria-label="Voice input for academic year" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editYearVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>

                        <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                    </div>

                    <div class="ap-panel ap-panel-soft">
                        <div class="ap-label">
                            <span class="ap-label-text">Semester <span class="text-red-500">*</span></span>
                        </div>

                        <div class="ap-semester-grid-redesign">
                            <label class="ap-semester-item">
                                <input type="radio" name="semester" id="edit-sem-1" value="1st Semester"
                                    class="edit-sem" required>
                                <div class="ap-semester-card" style="--active-color:#2563eb;">
                                    <i class="fa-solid fa-book"></i>
                                    <span>1st Semester</span>
                                </div>
                            </label>

                            <label class="ap-semester-item">
                                <input type="radio" name="semester" id="edit-sem-2" value="2nd Semester"
                                    class="edit-sem" required>
                                <div class="ap-semester-card" style="--active-color:#2563eb;">
                                    <i class="fa-solid fa-book-open"></i>
                                    <span>2nd Semester</span>
                                </div>
                            </label>

                            <label class="ap-semester-item">
                                <input type="radio" name="semester" id="edit-sem-3" value="Summer" class="edit-sem"
                                    required>
                                <div class="ap-semester-card" style="--active-color:#2563eb;">
                                    <i class="fa-solid fa-sun"></i>
                                    <span>Summer</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="ap-col-span-2 ap-panel">
                        <div class="ap-date-grid-redesign">
                            <div>
                                <div class="ap-label">
                                    <span class="ap-label-text">Start Date <span class="text-red-500">*</span></span>
                                </div>

                                <div class="ap-input-wrap">
                                    <span class="ap-input-icon">
                                        <i class="fa-solid fa-calendar-day"></i>
                                    </span>
                                    <input type="date" name="start_date" id="editStart" class="ap-input field-input"
                                        required min="1900-01-01" max="9999-12-31" inputmode="numeric">
                                </div>
                                <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                            </div>

                            <div>
                                <div class="ap-label">
                                    <span class="ap-label-text">End Date <span class="text-red-500">*</span></span>
                                </div>

                                <div class="ap-input-wrap">
                                    <span class="ap-input-icon">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </span>
                                    <input type="date" name="end_date" id="editEnd" class="ap-input field-input"
                                        required min="1900-01-01" max="9999-12-31" inputmode="numeric">
                                </div>
                                <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                            </div>
                        </div>
                    </div>

                    {{-- ── Description with external circular mic button ── --}}
                    <div class="ap-col-span-2 ap-panel ap-desc-panel">
                        <div class="ap-label">
                            <span class="ap-label-text">Description</span>
                            <span class="ap-label-hint">Optional</span>
                        </div>

                        <div class="ap-textarea-wrap" id="editDescWrap">
                            <div class="ap-textarea-inner">
                                <span class="ap-placeholder">Add any notes about this academic period...</span>
                                <textarea rows="6" name="description" id="editDesc"
                                    class="ap-textarea field-input no-voice" data-word-limit="150" maxlength="150"></textarea>
                            </div>
                            <div style="position: relative;">
                                <button type="button" class="ap-voice-mic-ext" id="editDescMicBtn" aria-label="Voice input for description" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editDescVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
                            </div>
                        </div>

                        <div class="ap-desc-meta">
                            <span class="ap-desc-help">Maximum of 150 characters</span>
                            <span class="ap-word-counter" id="editDescCounter">0 / 150 characters</span>
                        </div>
                        <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                    </div>

                    <div class="ap-col-span-2">
                        <div class="ap-active-card"
                            style="border-color: rgba(37, 99, 235, 0.16); background: linear-gradient(90deg, #eff6ff 0%, #ffffff 100%);">
                            <div class="ap-active-card-left">
                                <div class="ap-active-badge" style="color:#2563eb;">
                                    <i class="fa-solid fa-star text-sm"></i>
                                </div>

                                <div>
                                    <p class="ap-active-title">Set as Active Period</p>
                                    <p class="ap-active-desc">This will deactivate the currently active semester.</p>
                                </div>
                            </div>

                            <label class="ap-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" id="editIsActive" value="1">
                                <span class="ap-switch-slider" style="--switch-color:#2563eb;"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="ap-add-footer">
                    <button type="button" onclick="closeModal('editModal')" class="ap-add-btn ap-add-btn-cancel">
                        Cancel
                    </button>

                    <button type="submit" class="ap-add-btn"
                        style="background: linear-gradient(145deg, #2563eb, #1d4ed8); color:#fff; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Update Period
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         DELETE MODAL
    ════════════════════════════════════════════════ --}}
    <div class="modal-overlay modal-sm" id="deleteModal" onclick="closeModalOutside(event,'deleteModal')">
        <div class="modal-box ap-delete-shell">
            <form method="POST" id="deleteForm">
                @csrf
                @method('DELETE')

                <div class="ap-delete-body">
                    <div class="ap-modal-icon delete mx-auto">
                        <i class="fa-solid fa-triangle-exclamation text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-extrabold text-gray-800 mb-2">Delete Academic Period?</h3>
                    <p class="text-sm text-gray-500 mb-1">You are about to permanently delete</p>
                    <p class="font-bold text-[#8B0000] text-base mb-4" id="deletePeriodLabel">—</p>

                    <div class="ap-delete-actions">
                        <button type="button" onclick="closeModal('deleteModal')"
                            class="ap-btn ap-btn-ghost">Cancel</button>
                        <button type="submit" class="ap-btn ap-btn-danger">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addForm = document.querySelector('#addModal form');
            if (!addForm) return;

            const yearInput = addForm.querySelector('[name="academic_year"]');
            const startInput = addForm.querySelector('[name="start_date"]');
            const endInput = addForm.querySelector('[name="end_date"]');
            const semRadios = addForm.querySelectorAll('[name="semester"]');
            const addDesc = addForm.querySelector('#addDesc');
            const addDescCounter = addForm.querySelector('#addDescCounter');
            const editDesc = document.getElementById('editDesc');
            const editDescCounter = document.getElementById('editDescCounter');

            function getErr(field) {
                // For description fields
                if (field.id === 'addDesc' || field.id === 'editDesc') {
                    return field.closest('.ap-desc-panel')?.querySelector('.field-error') || null;
                }

                // For other fields, search up to the nearest .ap-panel or parent wrapper
                let current = field;
                while (current) {
                    // Check if we're in a .ap-panel or .ap-panel-soft
                    const panel = current.closest('.ap-panel, .ap-panel-soft, .ap-col-span-2');
                    if (panel) {
                        return panel.querySelector('.field-error');
                    }
                    current = current.parentElement;
                }

                return null;
            }

            function setError(field, msg) {
                field.classList.add('field-invalid');
                field.classList.remove('field-valid');
                const err = getErr(field);
                if (err) {
                    err.textContent = '⚠ ' + msg;
                    err.classList.add('show');
                }
            }

            function clearError(field) {
                field.classList.remove('field-invalid');
                const err = getErr(field);
                if (err) err.classList.remove('show');
            }

            function setValid(field) {
                field.classList.remove('field-invalid');
                field.classList.add('field-valid');
                const err = getErr(field);
                if (err) err.classList.remove('show');
            }

            function validateYear() {
                const v = yearInput.value.trim();
                const pattern = /^\d{4}-\d{4}$/;
                if (!v) {
                    setError(yearInput, 'Academic year is required.');
                    return false;
                }
                if (!pattern.test(v)) {
                    setError(yearInput, 'Format must be YYYY-YYYY (e.g. 2025-2026).');
                    return false;
                }
                const [y1, y2] = v.split('-').map(Number);
                if (y2 !== y1 + 1) {
                    setError(yearInput, 'Second year must be one after the first.');
                    return false;
                }
                setValid(yearInput);
                return true;
            }

            function validateSemester() {
                const checked = [...semRadios].some(r => r.checked);
                const semErr = addForm.querySelector('.sem-error');
                if (!checked) {
                    if (semErr) {
                        semErr.textContent = '⚠ Please select a semester.';
                        semErr.classList.add('show');
                    }
                    return false;
                }
                if (semErr) semErr.classList.remove('show');
                return true;
            }

            function isStrictIsoDate(value) {
                if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;

                const [year, month, day] = value.split('-').map(Number);

                if (year < 1900 || year > 9999) return false;
                if (month < 1 || month > 12) return false;
                if (day < 1 || day > 31) return false;

                const date = new Date(`${value}T00:00:00`);
                if (Number.isNaN(date.getTime())) return false;

                return (
                    date.getFullYear() === year &&
                    date.getMonth() + 1 === month &&
                    date.getDate() === day
                );
            }

            function validateDates() {
                let ok = true;
                const s = startInput.value.trim();
                const e = endInput.value.trim();

                if (!s) {
                    setError(startInput, 'Start date is required.');
                    ok = false;
                } else if (!isStrictIsoDate(s)) {
                    setError(startInput, 'Start date must be a valid date in YYYY-MM-DD format.');
                    ok = false;
                } else {
                    setValid(startInput);
                }

                if (!e) {
                    setError(endInput, 'End date is required.');
                    ok = false;
                } else if (!isStrictIsoDate(e)) {
                    setError(endInput, 'End date must be a valid date in YYYY-MM-DD format.');
                    ok = false;
                } else if (s && isStrictIsoDate(s) && e <= s) {
                    setError(endInput, 'End date must be after start date.');
                    ok = false;
                } else {
                    setValid(endInput);
                }

                return ok;
            }

            yearInput.addEventListener('input', validateYear);
            yearInput.addEventListener('blur', validateYear);
            startInput.addEventListener('change', () => { validateDates(); });
            endInput.addEventListener('change', () => { validateDates(); });

            if (addDesc) {
                addDesc.addEventListener('input', () => {
                    const limit = Number(addDesc.dataset.wordLimit || 150);
                    if (addDesc.value.length > limit) {
                        addDesc.value = addDesc.value.slice(0, limit);
                    }
                    validateDescription(addDesc, addDescCounter, setError, clearError);
                });

                addDesc.addEventListener('blur', () => validateDescription(addDesc, addDescCounter, setError,
                    clearError));
                updateWordCounter(addDesc, addDescCounter);
            }

            if (editDesc) {
                editDesc.addEventListener('input', () => {
                    const limit = Number(editDesc.dataset.wordLimit || 150);
                    if (editDesc.value.length > limit) {
                        editDesc.value = editDesc.value.slice(0, limit);
                    }
                    validateDescription(editDesc, editDescCounter, setError, clearError);
                });

                editDesc.addEventListener('blur', () => validateDescription(editDesc, editDescCounter, setError,
                    clearError));
                updateWordCounter(editDesc, editDescCounter);
            }

            addForm.addEventListener('submit', e => {
                const y = validateYear();
                const s = validateSemester();
                const d = validateDates();
                const descOk = validateDescription(addDesc, addDescCounter, setError, clearError);

                if (!y || !s || !d || !descOk) e.preventDefault();
            });
        });

        function countChars(value) {
            return value.length;
        }

        function updateWordCounter(textarea, counter) {
            if (!textarea || !counter) return true;

            const limit = Number(textarea.dataset.wordLimit || 150);
            const chars = countChars(textarea.value);

            counter.textContent = `${chars} / ${limit} characters`;
            counter.classList.remove('is-warning', 'is-danger', 'is-invalid');

            if (chars >= 120 && chars < 140) {
                counter.classList.add('is-warning');
            } else if (chars >= 140 && chars <= limit) {
                counter.classList.add('is-danger');
            } else if (chars > limit) {
                counter.classList.add('is-invalid');
            }

            return chars <= limit;
        }

        function validateDescription(textarea, counter, setErrorFn, clearErrorFn) {
            if (!textarea) return true;

            const limit = Number(textarea.dataset.wordLimit || 150);
            const chars = countChars(textarea.value);

            updateWordCounter(textarea, counter);

            if (chars > limit) {
                if (typeof setErrorFn === 'function') {
                    setErrorFn(textarea, `Description must not exceed ${limit} characters.`);
                }
                return false;
            }

            if (typeof clearErrorFn === 'function') {
                clearErrorFn(textarea);
            }

            return true;
        }

        function bindTextareaPlaceholder(textareaId, wrapId) {
            const textarea = document.getElementById(textareaId);
            const wrap = document.getElementById(wrapId);
            if (!textarea || !wrap) return;

            const sync = () => {
                wrap.classList.toggle('has-value', textarea.value.trim().length > 0);
            };

            textarea.addEventListener('focus', () => wrap.classList.add('is-focused'));
            textarea.addEventListener('blur', () => wrap.classList.remove('is-focused'));
            textarea.addEventListener('input', sync);

            sync();
        }

        const calendarPeriods = @json($calendarPeriodsPayload);
        const holidayEvents = @json($holidayEvents);

        function renderCalendar() {
            const list = document.getElementById('calendarList');
            const calYear = document.getElementById('calYear');
            if (!list) return;

            const periodEvents = [];

            calendarPeriods.forEach(period => {
                if (period.start_date) {
                    periodEvents.push({
                        date: period.start_date,
                        label: `${period.semester} Start`,
                        year: period.academic_year,
                        color: '#8B0000',
                        type: 'start'
                    });
                }

                if (period.end_date) {
                    periodEvents.push({
                        date: period.end_date,
                        label: `${period.semester} End`,
                        year: period.academic_year,
                        color: '#2563eb',
                        type: 'end'
                    });
                }
            });

            const events = [...periodEvents, ...holidayEvents].sort((a, b) => a.date.localeCompare(b.date));
            const today = todayStr();
            const show = events.sort((a, b) => a.date.localeCompare(b.date));

            if (show.length) {
                const years = [...new Set(show.map(e => e.year))];
                calYear.textContent = years.length === 1 ? years[0] : 'Academic Periods & Holidays';
            } else {
                calYear.textContent = 'Academic Periods & Holidays';
            }

            if (!show.length) {
                list.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">No events found</p>';
                return;
            }

            list.innerHTML = show.map(e => {
                const d = new Date(e.date + 'T00:00:00');
                const isToday = e.date === today;
                const isPast = e.date < today;
                const mon = d.toLocaleDateString('en-US', { month: 'short' });
                const day = d.getDate();
                const isHoliday = e.type === 'holiday';
                let color = e.color;

                if (e.type === 'holiday') color = '#16a34a';
                if (e.type === 'start') color = '#8B0000';
                if (e.type === 'end') color = '#2563eb';

                return `
        <div class="flex items-start gap-3 py-2 border-b border-gray-50 last:border-0 ${isPast ? 'opacity-50' : ''}">
          <div style="flex-shrink:0;width:38px;text-align:center;background:${isToday ? '#8B0000' : isHoliday ? '#f3f4f6' : '#fef2f2'};
                      border-radius:8px;padding:4px 2px;border:1px solid ${isToday ? '#8B0000' : isHoliday ? '#e5e7eb' : '#fde8e8'}">
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:${isToday ? 'rgba(255,255,255,.8)' : isHoliday ? '#6b7280' : '#8B0000'};">${mon}</div>
            <div style="font-size:16px;font-weight:900;line-height:1;color:${isToday ? '#fff' : isHoliday ? '#374151' : '#8B0000'};">${day}</div>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:12px;font-weight:${isToday ? '700' : '600'};color:${isToday ? '#8B0000' : '#374151'};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              ${e.label}
            </div>
            <div style="font-size:10px;color:#9ca3af;margin-top:1px;">
              ${e.year}${isHoliday ? ' • Holiday' : ''}${isToday ? ' • Today' : ''}
            </div>
          </div>
          <div style="width:8px;height:8px;border-radius:50%;background:${color};flex-shrink:0;margin-top:4px;"></div>
        </div>
      `;
            }).join('');
        }

        function todayStr() {
            const now = new Date();
            const ph = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
            return `${ph.getFullYear()}-${String(ph.getMonth() + 1).padStart(2, '0')}-${String(ph.getDate()).padStart(2, '0')}`;
        }

        function resetModalForm(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            const form = modal.querySelector('form');
            if (!form) return;

            form.reset();

            form.querySelectorAll('.field-invalid, .field-valid').forEach(el => {
                el.classList.remove('field-invalid', 'field-valid');
            });

            form.querySelectorAll('.field-error').forEach(el => {
                el.classList.remove('show');
                el.textContent = '';
            });

            form.querySelectorAll('.sem-error').forEach(el => {
                el.classList.remove('show');
                el.textContent = '';
            });

            const addDesc = document.getElementById('addDesc');
            const addDescCounter = document.getElementById('addDescCounter');
            const editDesc = document.getElementById('editDesc');
            const editDescCounter = document.getElementById('editDescCounter');

            if (id === 'addModal' && typeof updateWordCounter === 'function' && addDesc && addDescCounter) {
                updateWordCounter(addDesc, addDescCounter);
            }

            if (id === 'editModal' && typeof updateWordCounter === 'function' && editDesc && editDescCounter) {
                updateWordCounter(editDesc, editDescCounter);
            }

            if (typeof bindTextareaPlaceholder === 'function') {
                bindTextareaPlaceholder('addDesc', 'addDescWrap');
                bindTextareaPlaceholder('editDesc', 'editDescWrap');
            }
        }

        function setModalState(id, isOpen) {
            const modal = document.getElementById(id);
            if (!modal) return;

            if (!isOpen && (id === 'addModal' || id === 'editModal')) {
                resetModalForm(id);
            }

            modal.classList.toggle('open', isOpen);
            modal.style.display = isOpen ? 'flex' : 'none';

            const modalBox = modal.querySelector('.modal-box');
            if (modalBox) {
                modalBox.style.display = 'block';
                modalBox.style.visibility = 'visible';
                modalBox.style.opacity = '1';
            }

            const hasOpenModal = document.querySelector('.modal-overlay.open');
            document.body.style.overflow = hasOpenModal ? 'hidden' : '';
        }

        window.openModal = function(id) {
            setModalState(id, true);
        };

        window.closeModal = function(id) {
            setModalState(id, false);
        };

        window.closeModalOutside = function(e, id) {
            if (e.target && e.target.id === id) {
                setModalState(id, false);
            }
        };

        window.openEditModal = function(period) {
            document.getElementById('editForm').action = `/admin/academic-periods/${period.id}`;
            document.getElementById('editYear').value = period.academic_year ?? '';
            document.getElementById('editStart').value = period.start_date ?? '';
            document.getElementById('editEnd').value = period.end_date ?? '';

            const editDesc = document.getElementById('editDesc');
            const editDescWrap = document.getElementById('editDescWrap');

            editDesc.value = period.description ?? '';

            if (editDescWrap) {
                editDescWrap.classList.toggle('has-value', editDesc.value.trim().length > 0);
                editDescWrap.classList.remove('is-focused');
            }

            if (typeof updateWordCounter === 'function') {
                updateWordCounter(editDesc, document.getElementById('editDescCounter'));
            }

            document.getElementById('editIsActive').checked = !!period.is_active;

            const semMap = {
                '1st Semester': 'First Semester',
                '2nd Semester': 'Second Semester',
            };

            document.getElementById('editSubtitle').textContent =
                `${period.academic_year} • ${semMap[period.semester] || period.semester}`;

            document.querySelectorAll('.edit-sem').forEach(radio => {
                radio.checked = radio.value === period.semester;
            });

            openModal('editModal');
        };

        window.openDeleteModal = function(action, label) {
            document.getElementById('deleteForm').action = action;
            document.getElementById('deletePeriodLabel').textContent = label;
            openModal('deleteModal');
        };

        function updateClock() {
            const now = new Date();
            const ph = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
            let h = ph.getHours();
            const m = String(ph.getMinutes()).padStart(2, '0');
            const s = String(ph.getSeconds()).padStart(2, '0');
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            const hh = String(h).padStart(2, '0');

            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December'];

            const liveClock = document.getElementById('liveClock');
            const liveAmPm = document.getElementById('liveAmPm');
            const liveDate = document.getElementById('liveDate');
            const liveDay = document.getElementById('liveDay');
            const currentDateTime = document.getElementById('currentDateTime');
            const timeIcon = document.getElementById('timeIcon');

            if (liveClock) liveClock.textContent = `${hh}:${m}:${s}`;
            if (liveAmPm) liveAmPm.textContent = ampm;
            if (liveDate) liveDate.textContent = `${months[ph.getMonth()]} ${ph.getDate()}, ${ph.getFullYear()}`;
            if (liveDay) liveDay.textContent = days[ph.getDay()];
            if (currentDateTime) {
                currentDateTime.textContent =
                    `${days[ph.getDay()]}, ${months[ph.getMonth()]} ${ph.getDate()}, ${ph.getFullYear()} · ${hh}:${m} ${ampm}`;
            }

            if (timeIcon) {
                if (ph.getHours() >= 6 && ph.getHours() < 18) {
                    timeIcon.className = 'fa-solid fa-sun text-yellow-400 text-xs';
                } else {
                    timeIcon.className = 'fa-solid fa-moon text-indigo-400 text-xs';
                }
            }
        }

        function clearAcademicSearch() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');

            if (searchInput) searchInput.value = '';
            if (clearBtn) clearBtn.classList.remove('visible');

            const items = document.querySelectorAll('.academic-item');
            items.forEach(item => item.style.display = '');

            const jsEmpty = document.getElementById('jsEmptyState');
            if (jsEmpty) jsEmpty.style.display = 'none';

            const jsEmptyGrid = document.getElementById('jsEmptyStateGrid');
            if (jsEmptyGrid) jsEmptyGrid.style.display = 'none';

            const jsFilterEmpty = document.getElementById('jsFilterEmptyState');
            if (jsFilterEmpty) jsFilterEmpty.style.display = 'none';

            const jsFilterEmptyGrid = document.getElementById('jsFilterEmptyStateGrid');
            if (jsFilterEmptyGrid) jsFilterEmptyGrid.style.display = 'none';

            const serverEmpty = document.getElementById('serverEmptyState');
            if (serverEmpty) {
                const hasRows = document.querySelectorAll('#academicTableBody tr.tbl-row').length > 0;
                serverEmpty.style.display = hasRows ? 'none' : '';
            }

            const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
            if (serverEmptyGrid) {
                const hasCards = document.querySelectorAll('#academicGridView .academic-card').length > 0;
                serverEmptyGrid.style.display = hasCards ? 'none' : '';
            }

            if (searchInput) searchInput.focus();
        }

        function resetAcademicFilters() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');
            const semesterFilter = document.getElementById('semesterFilter');
            const statusFilter = document.getElementById('statusFilter');
            const items = document.querySelectorAll('.academic-item');

            if (searchInput) searchInput.value = '';
            if (semesterFilter) semesterFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            if (clearBtn) clearBtn.classList.remove('visible');

            items.forEach(item => item.style.display = '');

            ['jsEmptyState', 'jsEmptyStateGrid', 'jsFilterEmptyState', 'jsFilterEmptyStateGrid'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            const serverEmpty = document.getElementById('serverEmptyState');
            if (serverEmpty) {
                const hasRows = document.querySelectorAll('#academicTableBody tr.tbl-row').length > 0;
                serverEmpty.style.display = hasRows ? 'none' : '';
            }

            const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
            if (serverEmptyGrid) {
                const hasCards = document.querySelectorAll('#academicGridView .academic-card').length > 0;
                serverEmptyGrid.style.display = hasCards ? 'none' : '';
            }
        }

        function getPreferredAcademicView() {
            if (window.innerWidth <= 767) return 'grid';
            return localStorage.getItem('academicView') || 'list';
        }

        function applyAcademicView(view, save = true) {
            const listView = document.getElementById('academicListView');
            const gridView = document.getElementById('academicGridView');
            const listBtn = document.getElementById('academicListBtn');
            const gridBtn = document.getElementById('academicGridBtn');

            if (!listView || !gridView) return;

            const finalView = window.innerWidth <= 767 ? 'grid' : view;

            if (finalView === 'grid') {
                listView.style.display = 'none';
                gridView.style.display = 'grid';
            } else {
                listView.style.display = 'block';
                gridView.style.display = 'none';
            }

            if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
            if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

            if (save && window.innerWidth > 767) {
                localStorage.setItem('academicView', finalView);
            }
        }

        function initAcademicViewToggle() {
            const listBtn = document.getElementById('academicListBtn');
            const gridBtn = document.getElementById('academicGridBtn');

            applyAcademicView(getPreferredAcademicView(), false);

            if (listBtn && !listBtn.dataset.bound) {
                listBtn.dataset.bound = '1';
                listBtn.addEventListener('click', () => applyAcademicView('list', true));
            }

            if (gridBtn && !gridBtn.dataset.bound) {
                gridBtn.dataset.bound = '1';
                gridBtn.addEventListener('click', () => applyAcademicView('grid', true));
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateClock();
            renderCalendar();
            setInterval(updateClock, 1000);
        });

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');
            const semesterFilter = document.getElementById('semesterFilter');
            const statusFilter = document.getElementById('statusFilter');
            let searchTimer = null;

            const tableBody = document.getElementById('academicTableBody');
            const gridView = document.getElementById('academicGridView');

            const allTableRows = () => tableBody ? tableBody.querySelectorAll('tr.tbl-row') : [];
            const allGridCards = () => gridView ? gridView.querySelectorAll('.academic-card') : [];

            function showSearchEmptyState(query) {
                let rowEmpty = document.getElementById('jsEmptyState');
                if (!rowEmpty && tableBody) {
                    rowEmpty = document.createElement('tr');
                    rowEmpty.id = 'jsEmptyState';
                    rowEmpty.innerHTML = `
          <td colspan="7" class="text-center py-12 text-gray-400">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1rem;text-align:center;gap:.5rem;">
              <div style="width:60px;height:60px;border-radius:16px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;margin-bottom:.75rem;">
                <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
              </div>
              <p class="font-semibold text-sm text-gray-500 mb-1">
                No results for "<span id="jsEmptyQuery"></span>"
              </p>
              <p class="text-xs text-gray-400">
                Try a different academic year or semester name.
              </p>
              <button
                type="button"
                onclick="clearAcademicSearch()"
                style="margin-top:.75rem;padding:.5rem 1.1rem;border-radius:10px;border:1.5px dashed #d1d5db;background:none;font-size:.8rem;color:#9ca3af;cursor:pointer;"
                onmouseover="this.style.borderColor='#8B0000';this.style.color='#8B0000';"
                onmouseout="this.style.borderColor='#d1d5db';this.style.color='#9ca3af';">
                <i class="fa-solid fa-xmark" style="margin-right:.4rem;font-size:.7rem;"></i>
                Clear search
              </button>
            </div>
          </td>`;
                    tableBody.appendChild(rowEmpty);
                }

                let gridEmpty = document.getElementById('jsEmptyStateGrid');
                if (!gridEmpty && gridView) {
                    gridEmpty = document.createElement('div');
                    gridEmpty.id = 'jsEmptyStateGrid';
                    gridEmpty.className = 'text-center py-12 text-gray-400';
                    gridEmpty.innerHTML = `
          <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1rem;text-align:center;gap:.5rem;">
            <div style="width:60px;height:60px;border-radius:16px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;margin-bottom:.75rem;">
              <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
            </div>
            <p class="font-semibold text-sm text-gray-500 mb-1">
              No results for "<span id="jsEmptyQueryGrid"></span>"
            </p>
            <p class="text-xs text-gray-400">
              Try a different academic year or semester name.
            </p>
            <button
              type="button"
              onclick="clearAcademicSearch()"
              style="margin-top:.75rem;padding:.5rem 1.1rem;border-radius:10px;border:1.5px dashed #d1d5db;background:none;font-size:.8rem;color:#9ca3af;cursor:pointer;"
              onmouseover="this.style.borderColor='#8B0000';this.style.color='#8B0000';"
              onmouseout="this.style.borderColor='#d1d5db';this.style.color='#9ca3af';">
              <i class="fa-solid fa-xmark" style="margin-right:.4rem;font-size:.7rem;"></i>
              Clear search
            </button>
          </div>`;
                    gridView.appendChild(gridEmpty);
                }

                const q1 = document.getElementById('jsEmptyQuery');
                const q2 = document.getElementById('jsEmptyQueryGrid');
                if (q1) q1.textContent = query;
                if (q2) q2.textContent = query;

                if (rowEmpty) rowEmpty.style.display = '';
                if (gridEmpty) gridEmpty.style.display = '';

                const serverEmpty = document.getElementById('serverEmptyState');
                if (serverEmpty) serverEmpty.style.display = 'none';

                const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
                if (serverEmptyGrid) serverEmptyGrid.style.display = 'none';
            }

            function hideSearchEmptyState() {
                const rowEmpty = document.getElementById('jsEmptyState');
                const gridEmpty = document.getElementById('jsEmptyStateGrid');
                if (rowEmpty) rowEmpty.style.display = 'none';
                if (gridEmpty) gridEmpty.style.display = 'none';
            }

            function showFilterEmptyState() {
                let rowEmpty = document.getElementById('jsFilterEmptyState');
                if (!rowEmpty && tableBody) {
                    rowEmpty = document.createElement('tr');
                    rowEmpty.id = 'jsFilterEmptyState';
                    rowEmpty.innerHTML = `
          <td colspan="7" class="text-center text-gray-400 ap-empty">
            <div class="flex flex-col items-center justify-center text-center">
              <i class="fa-solid fa-filter text-3xl mb-3 opacity-30 block"></i>
              <p class="text-sm font-medium">No academic periods match the selected filters.</p>
            </div>
          </td>
        `;
                    tableBody.appendChild(rowEmpty);
                }

                let gridEmpty = document.getElementById('jsFilterEmptyStateGrid');
                if (!gridEmpty && gridView) {
                    gridEmpty = document.createElement('div');
                    gridEmpty.id = 'jsFilterEmptyStateGrid';
                    gridEmpty.className = 'text-center text-gray-400 ap-empty';
                    gridEmpty.innerHTML = `
          <div class="flex flex-col items-center justify-center text-center">
            <i class="fa-solid fa-filter text-3xl mb-3 opacity-30 block"></i>
            <p class="text-sm font-medium">No academic periods match the selected filters.</p>
          </div>
        `;
                    gridView.appendChild(gridEmpty);
                }

                if (rowEmpty) rowEmpty.style.display = '';
                if (gridEmpty) gridEmpty.style.display = '';

                hideSearchEmptyState();

                const serverEmpty = document.getElementById('serverEmptyState');
                if (serverEmpty) serverEmpty.style.display = 'none';

                const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
                if (serverEmptyGrid) serverEmptyGrid.style.display = 'none';
            }

            function hideFilterEmptyState() {
                const rowEmpty = document.getElementById('jsFilterEmptyState');
                const gridEmpty = document.getElementById('jsFilterEmptyStateGrid');
                if (rowEmpty) rowEmpty.style.display = 'none';
                if (gridEmpty) gridEmpty.style.display = 'none';
            }

            function filterItems() {
                const semesterValue = semesterFilter?.value || '';
                const statusValue = statusFilter?.value || '';
                const searchValue = (searchInput?.value || '').trim().toLowerCase();

                const rows = allTableRows();
                const cards = allGridCards();

                let visibleCount = 0;

                rows.forEach(row => {
                    const rowSemester = row.dataset.semester || '';
                    const rowStatus = row.dataset.status || '';
                    const rowSearch = row.dataset.search || '';

                    const semesterMatch = !semesterValue || rowSemester === semesterValue;
                    const statusMatch = !statusValue || rowStatus === statusValue;
                    const searchMatch = !searchValue || rowSearch.includes(searchValue);

                    const show = semesterMatch && statusMatch && searchMatch;
                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                cards.forEach(card => {
                    const rowSemester = card.dataset.semester || '';
                    const rowStatus = card.dataset.status || '';
                    const rowSearch = card.dataset.search || '';

                    const semesterMatch = !semesterValue || rowSemester === semesterValue;
                    const statusMatch = !statusValue || rowStatus === statusValue;
                    const searchMatch = !searchValue || rowSearch.includes(searchValue);

                    const show = semesterMatch && statusMatch && searchMatch;
                    card.style.display = show ? '' : 'none';
                });

                if (visibleCount === 0) {
                    if (searchValue) {
                        hideFilterEmptyState();
                        showSearchEmptyState(searchInput.value.trim());
                    } else {
                        hideSearchEmptyState();
                        showFilterEmptyState();
                    }
                } else {
                    hideSearchEmptyState();
                    hideFilterEmptyState();
                }

                const serverEmpty = document.getElementById('serverEmptyState');
                if (serverEmpty) serverEmpty.style.display = 'none';

                const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
                if (serverEmptyGrid) serverEmptyGrid.style.display = 'none';

                if (clearBtn) {
                    clearBtn.classList.toggle('visible', searchValue !== '');
                }
            }

            clearBtn?.addEventListener('click', () => {
                clearAcademicSearch();
                filterItems();
            });

            searchInput?.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => filterItems(), 250);
            });

            searchInput?.addEventListener('keydown', e => {
                if (e.key === 'Enter') e.preventDefault();
            });

            semesterFilter?.addEventListener('change', filterItems);
            statusFilter?.addEventListener('change', filterItems);

            filterItems();
            initAcademicViewToggle();

            window.addEventListener('resize', () => {
                applyAcademicView(getPreferredAcademicView(), false);
            });

            document.querySelectorAll('[data-open-modal]').forEach(button => {
                button.addEventListener('click', () => {
                    const target = button.getAttribute('data-open-modal');
                    if (target) window.openModal(target);
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach(button => {
                button.addEventListener('click', () => {
                    const target = button.getAttribute('data-close-modal');
                    if (target) window.closeModal(target);
                });
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.open').forEach(modal => {
                        setModalState(modal.id, false);
                    });
                    document.body.style.overflow = '';
                }
            });

            const addPeriodButtons = document.querySelectorAll('[data-open-modal="addModal"]');
            const addModalCloseButtons = document.querySelectorAll('[data-close-modal="addModal"]');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const addModal = document.getElementById('addModal');

            addPeriodButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    setModalState('addModal', true);
                });
            });

            addModalCloseButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    setModalState('addModal', false);
                });
            });

            [addModal, editModal, deleteModal].forEach(modal => {
                if (!modal) return;

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        setModalState(modal.id, false);
                    }
                });
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.open').forEach(modal => {
                        setModalState(modal.id, false);
                    });
                    document.body.style.overflow = '';
                }
            });

            bindTextareaPlaceholder('addDesc', 'addDescWrap');
            bindTextareaPlaceholder('editDesc', 'editDescWrap');

            // ── Search bar voice (external circular mic) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const input  = document.getElementById('searchInput');
                const micBtn = document.getElementById('apMicToggleBtn');
                const status = document.getElementById('apVoiceStatus');

                if (!input || !micBtn || !status) return;

                if (!SpeechRecognition) {
                    micBtn.disabled = true;
                    micBtn.setAttribute('aria-disabled', 'true');
                    return;
                }

                let listening   = false;
                let manualStop  = false;
                let recognition = null;

                const setStatus = (text, state) => {
                    status.textContent = text;
                    status.className = 'ap-voice-status';
                    if (state) status.classList.add(`is-${state}`);
                    status.classList.remove('hidden');
                };

                const hideStatus = (delay = 0) =>
                    window.setTimeout(() => status.classList.add('hidden'), delay);

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                };

                const createRecognition = () => {
                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;

                    let sawSpeech = false;
                    let timeoutId = null;

                    const clearT = () => {
                        if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                    };

                    r.onstart = () => {
                        timeoutId = window.setTimeout(() => {
                            if (listening && !sawSpeech) r.stop();
                        }, 6000);
                    };

                    r.onspeechend = () => { clearT(); try { r.stop(); } catch (e) {} };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const res   = event.results[i];
                            const chunk = res?.[0]?.transcript?.trim() || '';
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (res.isFinal) transcript = `${transcript} ${chunk}`.trim();
                            else if (!transcript) transcript = chunk;
                        }
                        transcript = transcript.trim();
                        if (transcript) {
                            clearT();
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            setStatus('Listening...', 'listening');
                        }
                    };

                    r.onerror = () => {
                        clearT();
                        if (manualStop) { manualStop = false; return; }
                        setMicState(false);
                        setStatus("Didn't catch that. Try again.", 'error');
                        hideStatus(2500);
                    };

                    r.onend = () => {
                        clearT();
                        if (manualStop) { manualStop = false; setMicState(false); return; }
                        const hadSpeech = sawSpeech || !!input.value.trim();
                        setMicState(false);
                        if (hadSpeech) { setStatus('Voice captured.', 'success'); hideStatus(2200); }
                        else { setStatus("Didn't catch that. Try again.", 'error'); hideStatus(2500); }
                    };

                    return r;
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) { try { recognition.stop(); } catch (err) {} }
                        return;
                    }

                    recognition = createRecognition();
                    try { recognition.start(); }
                    catch (err) {
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                        setMicState(false);
                        return;
                    }
                    setMicState(true);
                    setStatus('Listening...', 'listening');
                });
            })();

            // ── Academic Year voice mic (Add modal) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const input  = document.getElementById('addAcademicYearWrap')?.querySelector('input');
                const micBtn = document.getElementById('addYearMicBtn');
                const status = document.getElementById('addYearVoiceStatus');

                if (!input || !micBtn) return;
                if (!SpeechRecognition) { micBtn.disabled = true; return; }

                let listening = false;
                let manualStop = false;
                let recognition = null;
                let statusTimeout = null;

                const setStatus = (msg, type) => {
                    if (status) {
                        status.textContent = msg;
                        status.className = `ap-voice-status is-${type}`;
                    }
                };

                const hideStatus = (delay = 1500) => {
                    if (statusTimeout) clearTimeout(statusTimeout);
                    statusTimeout = setTimeout(() => {
                        if (status) status.classList.add('hidden');
                    }, delay);
                };

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                    micBtn.setAttribute('aria-pressed', isActive);
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) {}
                        return;
                    }

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;
                    recognition = r;

                    r.onstart = () => { 
                        setMicState(true); 
                        setStatus('Listening...', 'listening');
                    };
                    r.onend = () => { 
                        setMicState(false); 
                        manualStop = false; 
                    };
                    r.onerror = () => { 
                        if (!manualStop) {
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                        manualStop = false; 
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            if (event.results[i].isFinal) {
                                transcript = event.results[i][0].transcript.trim();
                            }
                        }
                        if (transcript) {
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            setStatus('Voice captured.', 'success');
                            hideStatus(2200);
                        }
                    };

                    try { r.start(); }
                    catch (e) { 
                        setMicState(false);
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                    }
                });
            })();

            // ── Academic Year voice mic (Edit modal) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const input  = document.getElementById('editYear');
                const micBtn = document.getElementById('editYearMicBtn');
                const status = document.getElementById('editYearVoiceStatus');

                if (!input || !micBtn) return;
                if (!SpeechRecognition) { micBtn.disabled = true; return; }

                let listening = false;
                let manualStop = false;
                let recognition = null;
                let statusTimeout = null;

                const setStatus = (msg, type) => {
                    if (status) {
                        status.textContent = msg;
                        status.className = `ap-voice-status is-${type}`;
                    }
                };

                const hideStatus = (delay = 1500) => {
                    if (statusTimeout) clearTimeout(statusTimeout);
                    statusTimeout = setTimeout(() => {
                        if (status) status.classList.add('hidden');
                    }, delay);
                };

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                    micBtn.setAttribute('aria-pressed', isActive);
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) {}
                        return;
                    }

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;
                    recognition = r;

                    r.onstart = () => { 
                        setMicState(true); 
                        setStatus('Listening...', 'listening');
                    };
                    r.onend = () => { 
                        setMicState(false); 
                        manualStop = false; 
                    };
                    r.onerror = () => { 
                        if (!manualStop) {
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                        manualStop = false; 
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            if (event.results[i].isFinal) {
                                transcript = event.results[i][0].transcript.trim();
                            }
                        }
                        if (transcript) {
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            setStatus('Voice captured.', 'success');
                            hideStatus(2200);
                        }
                    };

                    try { r.start(); }
                    catch (e) { 
                        setMicState(false);
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                    }
                });
            })();

            // ── Description textarea voice mic (Add modal) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const textarea = document.getElementById('addDesc');
                const micBtn   = document.getElementById('addDescMicBtn');
                const counter  = document.getElementById('addDescCounter');
                const status   = document.getElementById('addDescVoiceStatus');

                if (!textarea || !micBtn) return;
                if (!SpeechRecognition) { micBtn.disabled = true; return; }

                let listening   = false;
                let manualStop  = false;
                let recognition = null;
                let statusTimeout = null;

                const setStatus = (msg, type) => {
                    if (status) {
                        status.textContent = msg;
                        status.className = `ap-voice-status is-${type}`;
                    }
                };

                const hideStatus = (delay = 1500) => {
                    if (statusTimeout) clearTimeout(statusTimeout);
                    statusTimeout = setTimeout(() => {
                        if (status) status.classList.add('hidden');
                    }, delay);
                };

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                    micBtn.setAttribute('aria-pressed', isActive);
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) {}
                        return;
                    }

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;
                    recognition = r;

                    let sawSpeech = false;

                    r.onstart = () => { 
                        setMicState(true); 
                        setStatus('Listening...', 'listening');
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const res   = event.results[i];
                            const chunk = res?.[0]?.transcript?.trim() || '';
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (res.isFinal) transcript = `${transcript} ${chunk}`.trim();
                            else if (!transcript) transcript = chunk;
                        }
                        transcript = transcript.trim();
                        if (transcript) {
                            const limit = Number(textarea.dataset.wordLimit || 150);
                            const appended = (textarea.value + (textarea.value ? ' ' : '') + transcript).slice(0, limit);
                            textarea.value = appended;
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                            if (typeof updateWordCounter === 'function') updateWordCounter(textarea, counter);
                        }
                    };

                    r.onerror = () => { 
                        if (!manualStop) {
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                        manualStop = false; 
                    };
                    r.onend   = () => { setMicState(false); manualStop = false; };

                    try { r.start(); setMicState(true); }
                    catch (e) { 
                        setMicState(false);
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                    }
                });
            })();

            // ── Description textarea voice mic (Edit modal) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const textarea = document.getElementById('editDesc');
                const micBtn   = document.getElementById('editDescMicBtn');
                const counter  = document.getElementById('editDescCounter');
                const status   = document.getElementById('editDescVoiceStatus');

                if (!textarea || !micBtn) return;
                if (!SpeechRecognition) { micBtn.disabled = true; return; }

                let listening   = false;
                let manualStop  = false;
                let recognition = null;
                let statusTimeout = null;

                const setStatus = (msg, type) => {
                    if (status) {
                        status.textContent = msg;
                        status.className = `ap-voice-status is-${type}`;
                    }
                };

                const hideStatus = (delay = 1500) => {
                    if (statusTimeout) clearTimeout(statusTimeout);
                    statusTimeout = setTimeout(() => {
                        if (status) status.classList.add('hidden');
                    }, delay);
                };

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                    micBtn.setAttribute('aria-pressed', isActive);
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) {}
                        return;
                    }

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;
                    recognition = r;

                    let sawSpeech = false;

                    r.onstart = () => { 
                        setMicState(true); 
                        setStatus('Listening...', 'listening');
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const res   = event.results[i];
                            const chunk = res?.[0]?.transcript?.trim() || '';
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (res.isFinal) transcript = `${transcript} ${chunk}`.trim();
                            else if (!transcript) transcript = chunk;
                        }
                        transcript = transcript.trim();
                        if (transcript) {
                            const limit = Number(textarea.dataset.wordLimit || 150);
                            const appended = (textarea.value + (textarea.value ? ' ' : '') + transcript).slice(0, limit);
                            textarea.value = appended;
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                            if (typeof updateWordCounter === 'function') updateWordCounter(textarea, counter);
                        }
                    };

                    r.onerror = () => { 
                        if (!manualStop) {
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                        manualStop = false; 
                    };
                    r.onend   = () => { setMicState(false); manualStop = false; };

                    try { r.start(); setMicState(true); }
                    catch (e) { 
                        setMicState(false);
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                    }
                });
            })();
        });
    </script>
@endsection