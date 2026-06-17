@extends('layouts.dentist')

@section('title', 'Walk-in Appointment')

@section('styles')

<link rel="stylesheet" href="{{ asset('css/dentist-walk-in.css') }}?v={{ time() }}">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        @keyframes confirmBackdropFade {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes confirmModalPop {
            0% {
                opacity: 0;
                transform: translate3d(-50%, -50%, 0) scale(0.92);
            }

            60% {
                opacity: 1;
                transform: translate3d(-50%, -50%, 0) scale(1.03);
            }

            100% {
                opacity: 1;
                transform: translate3d(-50%, -50%, 0) scale(1);
            }
        }

        @keyframes confirmIconPop {
            0% {
                opacity: 0;
                transform: scale(0.55) rotate(-10deg);
            }

            60% {
                opacity: 1;
                transform: scale(1.08) rotate(4deg);
            }

            100% {
                opacity: 1;
                transform: scale(1) rotate(0);
            }
        }

        @keyframes confirmContentRise {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #confirmModal::backdrop {
            animation: confirmBackdropFade 0.25s ease-out;
        }

        #confirmModal {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            right: auto !important;
            bottom: auto !important;
            margin: 0 !important;

            transform: translate3d(-50%, -50%, 0) scale(1) !important;
            transform-origin: center !important;

            opacity: 0;
            will-change: transform, opacity;
        }

        #confirmModal[open] {
            opacity: 1;
            animation: confirmModalPop 0.38s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        #confirmModal .confirm-modal-icon {
            animation: confirmIconPop 0.5s 0.12s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        #confirmModal .confirm-modal-title,
        #confirmModal .confirm-modal-message,
        #confirmModal .confirm-modal-button {
            opacity: 0;
            animation: confirmContentRise 0.4s ease-out forwards;
        }

        #confirmModal .confirm-modal-title {
            animation-delay: 0.14s;
        }

        #confirmModal .confirm-modal-message {
            animation-delay: 0.22s;
        }

        #confirmModal .confirm-modal-button {
            animation-delay: 0.3s;
        }

        .step-content {
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .step-content.show {
            opacity: 1;
        }

        .step-circle {
            transition: all 0.4s ease;
        }

        .step-connector {
            transition: background 0.4s;
        }

        .slot-chip {
            transition: all 0.2s;
        }

        .service-step-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .service-option {
            display: block;
            cursor: pointer;
        }

        .service-option-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .service-option-card {
            min-height: 98px;
            border-radius: 20px;
            border: 1px solid #eadfda;
            background: linear-gradient(180deg, #fffefe 0%, #fff8f7 100%);
            padding: 0.85rem 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.03);
            transition:
                transform 0.22s ease,
                box-shadow 0.22s ease,
                border-color 0.22s ease,
                background 0.22s ease;
        }

        .service-option-main {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            min-width: 0;
            flex: 1;
        }

        .service-option-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: #f9e8e8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8B0000;
            flex-shrink: 0;
            box-shadow: inset 0 0 0 1px rgba(139, 0, 0, 0.05);
        }

        .service-option-icon i {
            font-size: 1rem;
        }

        .service-option-copy {
            min-width: 0;
            flex: 1;
        }

        .service-option-topline {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
            margin-bottom: 0.35rem;
        }

        .service-option-title {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 800;
            color: #1a1410;
            line-height: 1.2;
        }

        .service-option-badge {
            padding: 0.24rem 0.48rem;
            border-radius: 999px;
            background: #fff3e8;
            color: #9a5b00;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .service-option-desc {
            margin: 0;
            font-size: 0.82rem;
            line-height: 1.45;
            color: #8c817a;
        }

        .service-option-arrow {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 1px solid #efe4df;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4c7c0;
            flex-shrink: 0;
            transition: all 0.22s ease;
        }

        .service-option:hover .service-option-card {
            transform: translateY(-2px);
            border-color: #d9b8b0;
            box-shadow: 0 18px 34px rgba(139, 0, 0, 0.08);
            background: linear-gradient(180deg, #fffafa 0%, #fff4f3 100%);
        }

        .service-option:hover .service-option-arrow {
            color: #8B0000;
            border-color: #d9b8b0;
            background: #fff5f5;
        }

        .service-option:has(.service-option-input:checked) .service-option-card {
            border-color: #8B0000;
            background: linear-gradient(135deg, #8B0000 0%, #660000 100%);
            box-shadow: 0 18px 36px rgba(139, 0, 0, 0.22);
            transform: translateY(-2px);
        }

        .service-option:has(.service-option-input:checked) .service-option-title {
            color: #ffffff;
        }

        .service-option:has(.service-option-input:checked) .service-option-desc {
            color: rgba(255, 255, 255, 0.8);
        }

        .service-option:has(.service-option-input:checked) .service-option-icon {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
        }

        .service-option:has(.service-option-input:checked) .service-option-icon img {
            filter: brightness(0) invert(1) !important;
        }

        .service-option:has(.service-option-input:checked) .service-option-badge {
            background: rgba(255, 255, 255, 0.14);
            color: #fff3d6;
        }

        .service-option:has(.service-option-input:checked) .service-option-arrow {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .booking-step-shell {
            background: linear-gradient(180deg, #ffffff 0%, #fffafa 100%);
            border: 1px solid #ece3de;
            border-radius: 28px;
            padding: 1.35rem;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.05);
        }

        .booking-step-header {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            margin-bottom: 1.15rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1e8e3;
        }

        .booking-step-eyebrow {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #8B0000;
        }

        .booking-step-title {
            font-size: clamp(1.7rem, 2.5vw, 2.45rem);
            font-weight: 900;
            line-height: 1.06;
            color: #660000;
            margin: 0;
        }

        .booking-step-subtitle {
            font-size: 0.95rem;
            line-height: 1.65;
            color: #8c817a;
            margin: 0;
            max-width: 760px;
        }

        .booking-step-body {
            min-width: 0;
        }

        .section-card {
            background: linear-gradient(180deg, #fffefe 0%, #fff8f7 100%);
            border: 1px solid #e8e2dd;
            border-radius: 22px;
            padding: 1rem;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.025);
        }

        .booking-step-body>.section-card+.section-card,
        .section-stack>.section-card+.section-card {
            margin-top: 1rem;
        }

        .cal-time-layout>.section-card,
        .cal-time-layout>.time-panel.section-card {
            height: 100%;
            align-self: stretch;
        }

        .section-card-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #8B0000;
            margin-bottom: 0.9rem;
        }

        .section-card-title-line {
            flex: 1;
            height: 1px;
            background: #f0e4df;
        }

        @media (max-width: 768px) {
            .booking-step-shell {
                border-radius: 22px;
                padding: 1rem;
            }

            .booking-step-header {
                padding-bottom: 0.85rem;
                margin-bottom: 1rem;
            }

            .booking-step-title {
                font-size: 1.6rem;
            }

            .booking-step-subtitle {
                font-size: 0.88rem;
            }

            .section-card {
                border-radius: 18px;
                padding: 0.9rem;
            }
        }

        .progress-fill {
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-input {
            height: 46px;
            color: #333 !important;
        }

        textarea.form-input {
            height: auto;
            min-height: 110px;
            color: #333 !important;
        }

        select.form-input {
            height: 46px;
            color: #333 !important;
        }

        .form-input::placeholder,
        textarea.form-input::placeholder {
            color: #9ca3af;
        }

        .form-input:focus {
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.08);
            padding-right: 2.75rem !important;
        }

        .date-input-wrap {
            position: relative;
            width: 100%;
        }

        .date-input-wrap .form-input {
            height: 46px !important;
            min-height: 46px !important;
            padding-right: 2.85rem !important;
        }

        .date-input-wrap.compact {
            max-width: 260px;
        }

        .date-input-icon {
            position: absolute;
            right: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.95rem;
            pointer-events: none;
            z-index: 5;
        }

        .question-text {
            color: #333 !important;
        }

        .required-star {
            color: #8B0000;
            font-weight: 800;
            margin-left: 0.2rem;
            display: inline;
        }

        .q-radio:checked {
            border-color: #8B0000;
            background: #8B0000;
            box-shadow: inset 0 0 0 3px white;
        }

        .q-radio:hover:not(:checked) {
            border-color: #8B0000;
        }

        .btn-primary-custom:hover {
            background: #660000;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(139, 0, 0, 0.3);
        }

        .btn-secondary-custom:hover {
            border-color: #8B0000;
            color: #8B0000;
            background: #fff5f5;
        }

        .confirm-checkbox-wrap:hover {
            border-color: #8B0000;
        }

        .file-upload-zone:hover {
            border-color: #8B0000;
            background: #fff1f1;
        }

        .mini-tab {
            position: fixed;
            left: 50%;
            bottom: 2rem;
            transform: translate(-50%, 20px);

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;

            min-height: 68px;
            max-width: min(calc(100vw - 2rem), 560px);
            width: auto;

            padding: 0.95rem 1.35rem;
            border-radius: 24px;

            background: #1a1410;
            color: #fff;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);

            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
            text-align: left;

            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease;
            z-index: 999;
        }

        .mini-tab.show {
            opacity: 1;
            transform: translate(-50%, 0);
        }

        .mini-tab i {
            flex: 0 0 20px;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            line-height: 1;
            margin: 0;
        }

        #miniTabText {
            display: block;
            flex: 0 1 auto;
            margin: 0;
            line-height: 1.35;
            white-space: nowrap;
        }

        .field-help-text {
            margin-top: 0.45rem;
            font-size: 0.75rem;
            line-height: 1.35;
            min-height: 1rem;
        }

        .field-help-text.error {
            color: #dc2626;
        }

        .field-help-text.success {
            color: #15803d;
        }

        .input-invalid {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.08) !important;
        }

        .input-valid {
            border-color: #15803d !important;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.08) !important;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            padding-right: 2.5rem !important;
        }

        #leaveModal::backdrop,
        #othersModal::backdrop,
        #confirmModal::backdrop {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(2px);
        }

        #leaveModal.flex {
            display: flex;
        }

        .cal-time-layout {
            align-items: stretch !important;
        }

        .time-panel {
            position: relative;
            overflow: hidden;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .time-panel::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 160px;
            background: linear-gradient(180deg, rgba(139, 0, 0, 0.05), rgba(139, 0, 0, 0.01), transparent);
            pointer-events: none;
        }

        #slotGrid.slot-grid-ui {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-template-rows: repeat(4, 60px);
            grid-auto-rows: 60px;
            grid-auto-flow: column;
            gap: 0.85rem !important;
            margin-top: 0.8rem;
            align-items: stretch;
        }

        #slotGrid .slot-chip {
            width: 100%;
            height: 60px;
            min-height: 60px;
            border-radius: 16px !important;
            border: 1px solid #e7d8d2 !important;
            background: #ffffff !important;
            color: #2f2f2f !important;
            font-weight: 700 !important;
            font-size: 0.98rem !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0 0.9rem !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.03);
            transition:
                transform 0.22s ease,
                box-shadow 0.22s ease,
                border-color 0.22s ease,
                background 0.22s ease,
                color 0.22s ease,
                opacity 0.22s ease;
        }

        #slotGrid .slot-chip:hover:not(:disabled):not(.disabled):not(.selected):not(.bg-\[\#8B0000\]) {
            transform: translateY(-2px);
            border-color: #d5b2a9 !important;
            box-shadow: 0 16px 30px rgba(139, 0, 0, 0.10);
            background: linear-gradient(180deg, #fffafa 0%, #fff3f2 100%) !important;
        }

        [data-theme="dark"] #mainContent #slotGrid .slot-chip:hover:not(:disabled):not(.disabled):not(.selected):not(.bg-\[\#8B0000\]),
        [data-theme="dark"] #mainContent #slotGrid .slot-chip.hover\:bg-\[\#fff5f5\]:hover {
            background: linear-gradient(145deg, #1f2937, #111827) !important;
            border-color: rgba(252, 165, 165, 0.35) !important;
            color: #f9fafb !important;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.35) !important;
        }

        [data-theme="dark"] #mainContent #slotGrid .slot-chip:hover i {
            color: #fca5a5 !important;
        }

        #slotGrid .slot-chip.selected:hover,
        #slotGrid .slot-chip[aria-pressed="true"]:hover,
        #slotGrid .slot-chip.bg-\[\#8B0000\]:hover {
            transform: none !important;
            box-shadow: 0 16px 32px rgba(139, 0, 0, 0.22) !important;
            background: linear-gradient(135deg, #8B0000, #660000) !important;
            border-color: #8B0000 !important;
            color: #fff !important;
            cursor: default !important;
        }

        #slotGrid .slot-chip.bg-\[\#8B0000\]:hover i,
        #slotGrid .slot-chip.selected:hover i,
        #slotGrid .slot-chip[aria-pressed="true"]:hover i {
            color: #fff !important;
        }

        #slotGrid .slot-chip.selected:hover {
            background: linear-gradient(135deg, #8B0000, #660000) !important;
            color: #fff !important;
        }

        #slotGrid .slot-chip.selected,
        #slotGrid .slot-chip[aria-pressed="true"],
        #slotGrid .slot-chip.bg-\[\#8B0000\] {
            background: linear-gradient(135deg, #8B0000 0%, #660000 100%) !important;
            border-color: #8B0000 !important;
            color: #ffffff !important;
            box-shadow: 0 16px 32px rgba(139, 0, 0, 0.22);
        }

        #slotGrid .slot-chip.selected i,
        #slotGrid .slot-chip[aria-pressed="true"] i,
        #slotGrid .slot-chip.bg-\[\#8B0000\] i {
            color: #ffffff !important;
        }

        #slotGrid .slot-chip.disabled,
        #slotGrid .slot-chip:disabled {
            height: 60px;
            min-height: 60px;
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
            background: #f8f5f4 !important;
            color: #8f8580 !important;
            border-color: #e8dfdb !important;
        }

        #slotGrid .slot-chip span,
        #slotGrid .slot-chip small,
        #slotGrid .slot-chip strong {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #dateBanner {
            font-size: 0.92rem !important;
            font-weight: 800 !important;
            border-radius: 16px !important;
            padding: 0.9rem 1rem !important;
        }

        #slotPlaceholder {
            border: 1px dashed #e7d8d2;
            border-radius: 22px;
            background: linear-gradient(180deg, #fffdfd 0%, #fff7f6 100%);
            min-height: 320px;
        }

        #slotGrid .slot-chip i {
            color: #8B0000 !important;
            font-size: 0.9rem !important;
            transition: color 0.22s ease, transform 0.22s ease;
        }

        #slotGrid .slot-chip.selected i,
        #slotGrid .slot-chip[aria-pressed="true"] i,
        #slotGrid .slot-chip.bg-\[\#8B0000\] i {
            color: #fff !important;
        }

        #slotGrid .slot-chip,
        #slotGrid .slot-chip:hover,
        #slotGrid .slot-chip.selected,
        #slotGrid .slot-chip:active {
            transform: none !important;
            animation: none !important;
        }

        #slotGrid .slot-chip.selected,
        #slotGrid .slot-chip[aria-pressed="true"],
        #slotGrid .slot-chip.bg-\[\#8B0000\] {
            background: linear-gradient(135deg, #8B0000, #660000) !important;
            border-color: #8B0000 !important;
            color: #fff !important;
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(139, 0, 0, 0.24);
        }

        #slotGrid .slot-chip.selected::before,
        #slotGrid .slot-chip[aria-pressed="true"]::before,
        #slotGrid .slot-chip.bg-\[\#8B0000\]::before {
            color: #fff;
            opacity: 0.95;
        }

        #slotGrid .slot-chip:active {
            transform: scale(0.985);
        }

        #slotGrid .slot-chip span,
        #slotGrid .slot-chip strong {
            transition: color 0.22s ease;
        }

        #slotGrid .slot-chip span,
        #slotGrid .slot-chip small,
        #slotGrid .slot-chip strong {
            white-space: nowrap;
        }

        .voice-input-wrap {
            position: relative;
            width: 100%;
            max-width: 420px;
        }

        .voice-input-wrap>input,
        .voice-input-wrap>textarea {
            width: 100%;
        }

        .voice-input-wrap.is-full {
            max-width: 100%;
        }

        #additional_concerns {
            min-height: 136px;
        }

        .voice-input-wrap.is-medium {
            max-width: 320px;
        }

        .voice-input-wrap.is-small {
            max-width: 220px;
        }

        .voice-mic-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        .voice-mic-btn:focus {
            outline: none;
        }

        .voice-mic-btn i {
            pointer-events: none;
        }

        @media (max-width: 640px) {
            .sm-grid-1col {
                grid-template-columns: 1fr !important;
            }
        }

        @media only screen and (max-width: 600px) {

            .mini-tab {
                left: 50%;
                right: auto;
                bottom: 0.75rem;
                max-width: calc(100vw - 1rem);
                width: calc(100vw - 1rem);
                padding: 0.8rem 0.95rem;
                border-radius: 16px;
                font-size: 0.78rem;
                line-height: 1.35;
                box-shadow: 0 16px 30px rgba(0, 0, 0, 0.24);
            }

            #miniTabText {
                white-space: normal;
                word-break: break-word;
            }

            .field-help-text {
                font-size: 0.72rem;
                margin-top: 0.4rem;
            }

            .service-step-grid {
                grid-template-columns: 1fr !important;
                gap: 0.85rem !important;
            }

            .service-option-title {
                font-size: 0.95rem;
                line-height: 1.2;
            }

            .service-option-desc {
                font-size: 0.74rem;
                line-height: 1.4;
            }

            .service-option-badge {
                font-size: 0.55rem;
                padding: 0.22rem 0.42rem;
            }

            .service-option-icon img,
            .service-option-icon i {
                width: 16px;
                height: 16px;
                font-size: 0.9rem;
            }

            .service-option-card {
                min-height: 84px;
                gap: 0.75rem;
                padding: 0.75rem 0.8rem;
            }

            .service-option-main {
                gap: 0.7rem;
            }

            .service-option-icon {
                width: 38px;
                height: 38px;
                border-radius: 12px;
            }

            .service-option-arrow {
                width: 28px;
                height: 28px;
            }

            .cal-time-layout {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }

            .booking-step-shell {
                padding: 0.95rem;
                border-radius: 22px;
            }

            .section-card,
            .time-panel.section-card {
                padding: 0.9rem;
                border-radius: 18px;
            }

            .booking-step-title {
                font-size: 1.65rem;
            }

            .booking-step-subtitle {
                font-size: 0.88rem;
                line-height: 1.55;
            }

            #slotGrid.slot-grid-ui {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                grid-template-rows: repeat(4, 56px);
                grid-auto-rows: 56px;
                grid-auto-flow: column;
                gap: 0.75rem !important;
            }

            #slotGrid .slot-chip,
            #slotGrid .slot-chip.disabled,
            #slotGrid .slot-chip:disabled {
                height: 56px;
                min-height: 56px;
                font-size: 0.92rem !important;
                border-radius: 14px !important;
                padding: 0 0.85rem !important;
            }

            #slotPlaceholder {
                min-height: 180px;
            }

            #dateBanner {
                font-size: 0.84rem !important;
                padding: 0.8rem 0.9rem !important;
            }

            .time-panel {
                padding: 0.95rem !important;
            }

            .service-step-grid {
                grid-template-columns: 1fr;
            }
        }

        @media only screen and (min-width: 600px) and (max-width: 767px) {
            .cal-time-layout {
                grid-template-columns: 1fr !important;
            }

            .service-step-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.9rem;
            }

            .service-option-card {
                min-height: 92px;
                padding: 0.8rem 0.85rem;
                border-radius: 18px;
            }

            .service-option-title {
                font-size: 0.9rem;
            }

            .service-option-desc {
                font-size: 0.75rem;
            }

            .service-option-icon {
                width: 40px;
                height: 40px;
            }

            .service-option-arrow {
                width: 30px;
                height: 30px;
            }
        }

        @media only screen and (min-width: 768px) {

            .mini-tab {
                width: max-content;
                max-width: none;
                white-space: nowrap;
                padding: 0.95rem 1.5rem;
            }

            .service-option-card {
                min-height: 88px;
                border-radius: 18px;
                padding: 0.8rem 0.85rem;
            }

            .service-option-icon {
                width: 42px;
                height: 42px;
                border-radius: 13px;
            }

            .service-option-title {
                font-size: 0.93rem;
            }

            .service-option-desc {
                font-size: 0.78rem;
            }

            .service-option-badge {
                font-size: 0.58rem;
            }

            .cal-time-layout {
                grid-template-columns: minmax(0, 1fr) minmax(330px, 390px) !important;
                align-items: stretch !important;
            }

            .service-step-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #slotGrid.slot-grid-ui {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-template-rows: repeat(4, 58px);
                grid-auto-rows: 58px;
                grid-auto-flow: column;
            }

            #slotPlaceholder {
                min-height: 260px;
            }
        }

        @media only screen and (min-width: 992px) {
            .cal-time-layout {
                grid-template-columns: minmax(0, 1fr) minmax(360px, 420px) !important;
                gap: 1.5rem !important;
            }

            #slotGrid.slot-grid-ui {
                grid-template-rows: repeat(4, 60px);
                grid-auto-rows: 60px;
                grid-auto-flow: column;
            }

            #slotPlaceholder {
                min-height: 320px;
            }
        }

        @media only screen and (min-width: 1200px) {

            .cal-time-layout {
                grid-template-columns: minmax(0, 1fr) minmax(380px, 430px) !important;
            }
        }

        [data-theme="dark"] #mainContent {
            background:
                radial-gradient(circle at top, rgba(139, 0, 0, 0.18), transparent 34%),
                #020b14 !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #mainContent.book-container {
            background:
                radial-gradient(circle at top, rgba(139, 0, 0, 0.18), transparent 34%),
                #020b14 !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #mainContent .form-input,
        [data-theme="dark"] #mainContent input.form-input,
        [data-theme="dark"] #mainContent textarea.form-input,
        [data-theme="dark"] #mainContent select.form-input {
            background: #0b141c !important;
            border-color: #1f2a36 !important;
            color: #e6edf3 !important;
        }

        [data-theme="dark"] #mainContent .form-input::placeholder {
            color: #6b7c8f !important;
        }

        [data-theme="dark"] #mainContent label,
        [data-theme="dark"] #mainContent .question-text {
            color: #d1d5db !important;
        }

        [data-theme="dark"] #mainContent .q-radio {
            background: #0b141c !important;
            border-color: #374151 !important;
        }

        [data-theme="dark"] #mainContent .step-counter-pill {
            background: #0d1117 !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            color: #cbd5e1 !important;
        }

        [data-theme="dark"] #mainContent .q-radio:checked {
            background: #8B0000 !important;
            border-color: #fca5a5 !important;
            box-shadow: inset 0 0 0 3px #0b141c !important;
        }

        [data-theme="dark"] #mainContent .book-card {
            background: linear-gradient(145deg, #0d1117, #111827) !important;
            border: 1px solid rgba(255, 255, 255, 0.10) !important;
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.38) !important;
        }

        [data-theme="dark"] #mainContent .bg-white,
        [data-theme="dark"] #mainContent .booking-step-shell,
        [data-theme="dark"] #mainContent .section-card,
        [data-theme="dark"] #mainContent .time-panel {
            background: linear-gradient(145deg, #0d1117, #111827) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.38) !important;
        }

        [data-theme="dark"] #mainContent .booking-step-title,
        [data-theme="dark"] #mainContent .text-\[\#660000\] {
            color: #fecaca !important;
        }

        [data-theme="dark"] #mainContent .booking-step-eyebrow,
        [data-theme="dark"] #mainContent .section-card-title,
        [data-theme="dark"] #mainContent .text-\[\#8B0000\] {
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #mainContent .booking-step-subtitle,
        [data-theme="dark"] #mainContent .text-\[\#8c817a\],
        [data-theme="dark"] #mainContent .text-\[\#9e9690\],
        [data-theme="dark"] #mainContent .text-\[\#5c5550\] {
            color: #9ca3af !important;
        }

        [data-theme="dark"] #mainContent .border-\[\#e8e2dd\],
        [data-theme="dark"] #mainContent .border-\[\#ece3de\],
        [data-theme="dark"] #mainContent .border-\[\#f1e8e3\] {
            border-color: rgba(255, 255, 255, 0.10) !important;
        }

        [data-theme="dark"] #slotGrid .slot-chip:not(.selected):not(:disabled) {
            background: #161b22 !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #slotPlaceholder {
            color: #9ca3af !important;
        }

        [data-theme="dark"] #slotPlaceholder>div:first-child {
            background: rgba(139, 0, 0, 0.22) !important;
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #selectedSlotDisplay {
            background: rgba(139, 0, 0, 0.20) !important;
            border-color: rgba(252, 165, 165, 0.25) !important;
            color: #fecaca !important;
        }

        [data-theme="dark"] .book-container {
            background: transparent;
        }

        /* Cards / Sections */
        [data-theme="dark"] .book-card {
            background: #0F1A24;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        /* Titles */
        [data-theme="dark"] .book-title {
            color: #E6EDF3;
        }

        /* Labels */
        [data-theme="dark"] .book-label {
            color: #9FB3C8;
        }

        /* Inputs */
        [data-theme="dark"] .book-input {
            background: #0B141C;
            border: 1px solid #1F2A36;
            color: #E6EDF3;
        }

        [data-theme="dark"] .book-input::placeholder {
            color: #6B7C8F;
        }

        /* Focus */
        [data-theme="dark"] .book-input:focus {
            border-color: #8B0000;
            box-shadow: 0 0 0 2px rgba(139, 0, 0, 0.3);
        }

        /* Select dropdown */
        [data-theme="dark"] select.book-input {
            background: #0B141C;
            color: #E6EDF3;
        }

        /* Buttons */
        [data-theme="dark"] .book-btn-primary {
            background: #8B0000;
            color: #fff;
        }

        [data-theme="dark"] .book-btn-primary:hover {
            background: #a30000;
        }

        /* Secondary button */
        [data-theme="dark"] .book-btn-secondary {
            background: #1A2633;
            color: #C9D6E2;
        }

        [data-theme="dark"] .book-btn-secondary:hover {
            background: #243344;
        }

        /* Divider */
        [data-theme="dark"] .book-divider {
            border-color: rgba(255, 255, 255, 0.08);
        }

        /* Empty / helper text */
        [data-theme="dark"] .book-muted {
            color: #7F8C98;
        }

        .calendar-shell-no-card {
            min-width: 0;
        }

        .calendar-shell-no-card>* {
            width: 100%;
        }

        #mainContent.book-container {
            width: 100vw !important;
            max-width: none !important;
            margin-left: calc(50% - 50vw) !important;
            margin-right: calc(50% - 50vw) !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        #mainContent .book-page-wrap {
            width: min(100% - 2rem, 1180px) !important;
            max-width: 1180px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        @media (max-width: 768px) {
            #mainContent .book-page-wrap {
                width: min(100% - 1rem, 1180px) !important;
            }
        }

        [data-theme="dark"] #clearSlotSelection {
            background: #161b22 !important;
            border-color: rgba(252, 165, 165, 0.25) !important;
            color: #fecaca !important;
        }

        [data-theme="dark"] #clearSlotSelection:hover {
            background: #1f2937 !important;
        }

        #slotPlaceholder.slot-placeholder-empty {
            min-height: 260px !important;
        }

        .time-panel.is-empty {
            min-height: auto !important;
        }

        [data-theme="dark"] #slotPlaceholder {
            background: linear-gradient(145deg, #0d1117, #111827) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            color: #9ca3af !important;
        }

        [data-theme="dark"] #slotPlaceholder p:first-child {
            color: #cbd5e1 !important;
        }

        [data-theme="dark"] #slotPlaceholder p:last-child {
            color: #94a3b8 !important;
        }

        [data-theme="dark"] #slotPlaceholder>div:first-child {
            background: rgba(139, 0, 0, 0.22) !important;
            color: #fca5a5 !important;
        }

        /* FIX: empty slot layout height */
        #mainContent .time-panel.is-empty {
            height: auto !important;
            min-height: 0 !important;
            align-self: start !important;
        }

        #mainContent .time-panel.is-empty #slotPlaceholder {
            min-height: 240px !important;
            height: 240px !important;
            flex: 0 0 auto !important;
        }

        /* FIX: remove white flash/glitch */
        #mainContent #slotContainer,
        #mainContent #slotPlaceholder,
        #mainContent #slotGrid,
        #mainContent #dateBanner,
        #mainContent .slot-chip {
            transition:
                opacity .28s ease,
                transform .28s ease,
                background-color .28s ease,
                border-color .28s ease,
                color .28s ease !important;
        }

        #mainContent .slot-fade-out {
            opacity: 0 !important;
            transform: translateY(8px) scale(.98);
            pointer-events: none;
        }

        #mainContent .slot-fade-in {
            animation: slotFadeIn .32s ease both;
        }

        @keyframes slotFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* dark mode for choose a date placeholder */
        [data-theme="dark"] #mainContent #slotPlaceholder {
            background: linear-gradient(145deg, #0d1117, #111827) !important;
            border: 1px dashed rgba(255, 255, 255, .14) !important;
            color: #94a3b8 !important;
        }

        [data-theme="dark"] #mainContent #slotPlaceholder .empty-icon {
            background: rgba(139, 0, 0, 0.24) !important;
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #mainContent #slotPlaceholder .empty-title {
            color: #cbd5e1 !important;
        }

        [data-theme="dark"] #mainContent #slotPlaceholder .empty-subtitle {
            color: #94a3b8 !important;
        }

        #mainContent .time-panel {
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
        }

        #mainContent .time-panel::before {
            display: none !important;
        }

        #mainContent #slotPlaceholder,
        #mainContent #slotContainer {
            width: 100%;
        }

        /* FORCE empty placeholder back to correct position */
        #mainContent .time-panel.is-empty {
            display: flex !important;
            flex-direction: column !important;
        }

        #mainContent .time-panel.is-empty #slotPlaceholder {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 320px !important;
            min-height: 320px !important;
            margin-top: 1rem !important;
            transform: none !important;
            opacity: 1 !important;
        }

        /* FINAL OVERRIDE: remove Pick a Time Slot card and keep placeholder stable */
        #mainContent .cal-time-layout {
            align-items: start !important;
        }

        #mainContent .time-panel {
            min-height: 0 !important;
            height: auto !important;
            align-self: start !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
            overflow: visible !important;
        }

        #mainContent .time-panel::before {
            display: none !important;
        }

        #mainContent #slotPlaceholder {
            width: 100% !important;
            height: 260px !important;
            min-height: 260px !important;
            margin-top: 1rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transform: none !important;
            opacity: 1 !important;
        }

        #mainContent #slotPlaceholder.hidden {
            display: none !important;
        }

        #mainContent #slotContainer.hidden {
            display: none !important;
        }

        #mainContent #slotPlaceholder.hidden {
            display: none !important;
        }

        #mainContent .time-panel:has(#slotContainer:not(.hidden)) #slotPlaceholder {
            display: none !important;
        }

        #mainContent .time-panel.is-empty #slotPlaceholder:not(.hidden) {
            display: flex !important;
        }

        /* DARK MODE: prevent white skeleton/slot flash */
        [data-theme="dark"] #mainContent #slotGrid .slot-chip,
        [data-theme="dark"] #mainContent #slotGrid button,
        [data-theme="dark"] #mainContent #slotGrid [class*="bg-white"],
        [data-theme="dark"] #mainContent #slotGrid [class*="bg-gray"],
        [data-theme="dark"] #mainContent #slotGrid .animate-pulse,
        [data-theme="dark"] #mainContent #slotGrid .skeleton {
            background: #161b22 !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #mainContent #slotGrid .animate-pulse *,
        [data-theme="dark"] #mainContent #slotGrid .skeleton * {
            background: #0f1720 !important;
        }

        #mainContent #dateBanner:not(.hidden) {
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        [data-theme="dark"] #mainContent .service-option-card {
            background: linear-gradient(145deg, #161b22, #111827) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #mainContent .service-option-title {
            color: #f9fafb !important;
        }

        [data-theme="dark"] #mainContent .service-option-desc {
            color: #9ca3af !important;
        }

        [data-theme="dark"] #mainContent .service-option-icon {
            background: rgba(139, 0, 0, 0.22) !important;
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #mainContent .service-option-icon img {
            filter: brightness(0) saturate(100%) invert(76%) sepia(20%) saturate(950%) hue-rotate(313deg) brightness(105%) contrast(95%) !important;
        }

        [data-theme="dark"] #mainContent .service-option-badge {
            background: rgba(252, 165, 165, 0.12) !important;
            color: #fecaca !important;
        }

        [data-theme="dark"] #mainContent .service-option-arrow {
            background: rgba(255, 255, 255, 0.04) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #mainContent .service-option:hover .service-option-card {
            background: linear-gradient(145deg, #1f2937, #111827) !important;
            border-color: rgba(252, 165, 165, 0.35) !important;
        }

        /* selected service card */
        [data-theme="dark"] #mainContent .service-option:has(.service-option-input:checked) .service-option-card {
            background: linear-gradient(135deg, #8B0000, #b00000) !important;
            border-color: rgba(252, 165, 165, 0.45) !important;
            box-shadow: 0 18px 36px rgba(139, 0, 0, 0.35) !important;
        }

        [data-theme="dark"] #mainContent .service-option:has(.service-option-input:checked) .service-option-title,
        [data-theme="dark"] #mainContent .service-option:has(.service-option-input:checked) .service-option-desc {
            color: #ffffff !important;
        }

        [data-theme="dark"] #mainContent .service-option:has(.service-option-input:checked) .service-option-icon {
            background: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] #mainContent .service-option:has(.service-option-input:checked) .service-option-icon img {
            filter: brightness(0) invert(1) !important;
        }

        [data-theme="dark"] #mainContent .section-card-title-line,
        [data-theme="dark"] #mainContent .section-card-title span.flex-1,
        [data-theme="dark"] #mainContent .section-card p span.flex-1 {
            background: rgba(255, 255, 255, 0.12) !important;
        }

        [data-theme="dark"] #mainContent .border-b,
        [data-theme="dark"] #mainContent .border-\[\#f0ebe6\],
        [data-theme="dark"] #mainContent .border-\[\#f1e8e3\],
        [data-theme="dark"] #mainContent .border-\[\#e8e2dd\] {
            border-color: rgba(255, 255, 255, 0.10) !important;
        }

        /* DARK MODE: Flatpickr calendar */
        [data-theme="dark"] #mainContent .flatpickr-calendar {
            background: #0d1117 !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            box-shadow: 0 22px 50px rgba(0, 0, 0, 0.55) !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-weekdays,
        [data-theme="dark"] #mainContent .flatpickr-days {
            background: #0d1117 !important;
        }

        [data-theme="dark"] #mainContent span.flatpickr-weekday {
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-day {
            color: #d1d5db !important;
            background: transparent !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-day:hover {
            background: #1f2937 !important;
            border-color: rgba(252, 165, 165, 0.25) !important;
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-day.selected,
        [data-theme="dark"] #mainContent .flatpickr-day.startRange,
        [data-theme="dark"] #mainContent .flatpickr-day.endRange {
            background: linear-gradient(135deg, #8B0000, #660000) !important;
            border-color: #fca5a5 !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-day.today {
            border-color: #c9a84c !important;
            color: #fcd34d !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-day.today:hover {
            background: rgba(201, 168, 76, 0.14) !important;
            color: #fde68a !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-day.prevMonthDay,
        [data-theme="dark"] #mainContent .flatpickr-day.nextMonthDay {
            color: #4b5563 !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-day.flatpickr-disabled,
        [data-theme="dark"] #mainContent .flatpickr-day.prevMonthDay.flatpickr-disabled,
        [data-theme="dark"] #mainContent .flatpickr-day.nextMonthDay.flatpickr-disabled {
            color: #374151 !important;
            background: transparent !important;
        }

        [data-theme="dark"] #mainContent .flatpickr-day.flatpickr-future-disabled:hover {
            background: #111827 !important;
            border-color: rgba(252, 165, 165, 0.22) !important;
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #mainContent .custom-flatpickr-select {
            background: rgba(255, 255, 255, 0.10) !important;
            border-color: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] #mainContent .custom-flatpickr-select option {
            background: #111827 !important;
            color: #f9fafb !important;
        }

        [data-theme="dark"] #mainContent .medical-condition-grid label,
        [data-theme="dark"] #mainContent .medical-condition-grid span,
        [data-theme="dark"] #mainContent .medical-condition-grid p {
            color: #d1d5db !important;
        }

        [data-theme="dark"] #mainContent .medical-condition-grid input[type="checkbox"] {
            background: #0b141c !important;
            border-color: rgba(255, 255, 255, .24) !important;
            accent-color: #8B0000;
        }

        [data-theme="dark"] #mainContent .file-upload-zone {
            background: rgba(139, 0, 0, 0.10) !important;
            border-color: rgba(252, 165, 165, 0.18) !important;
            color: #cbd5e1 !important;
        }

        [data-theme="dark"] #mainContent .file-upload-zone:hover {
            background: rgba(139, 0, 0, 0.18) !important;
            border-color: rgba(252, 165, 165, 0.38) !important;
        }

        [data-theme="dark"] #mainContent .file-upload-zone * {
            color: #cbd5e1 !important;
        }

        [data-theme="dark"] #mainContent #step5,
        [data-theme="dark"] #mainContent #summarySection,
        [data-theme="dark"] #mainContent #confirmationSection {
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #mainContent #summaryBox>*,
        [data-theme="dark"] #mainContent .confirm-checkbox-wrap {
            background: linear-gradient(145deg, #0d1117, #111827) !important;
            border-color: rgba(255, 255, 255, .10) !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #mainContent .confirm-checkbox-wrap span,
        [data-theme="dark"] #mainContent #summaryBox,
        [data-theme="dark"] #mainContent #summaryBox * {
            color: #d1d5db !important;
        }

        #mainContent .emergency-contact-grid {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
            width: 100% !important;
            max-width: none !important;
        }

        #mainContent .emergency-fields-stack {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
            width: 100% !important;
            max-width: 520px !important;
        }

        #mainContent .emergency-fields-stack>div {
            width: 100% !important;
            max-width: 100% !important;
        }

        #mainContent .signature-full-row {
            width: 100% !important;
            max-width: none !important;
            grid-column: 1 / -1 !important;
        }

        [data-theme="dark"] #summaryBox>div,
        [data-theme="dark"] #summaryBox .bg-white,
        [data-theme="dark"] #summaryBox .bg-\[\#fffdfd\],
        [data-theme="dark"] #summaryBox .bg-\[\#fff7f6\],
        [data-theme="dark"] #summaryBox .bg-\[\#f9e8e8\] {
            background: linear-gradient(145deg, #0d1117, #111827) !important;
            border-color: rgba(255, 255, 255, .10) !important;
            color: #e5e7eb !important;
        }

        [data-theme="dark"] #summaryBox *,
        [data-theme="dark"] #summaryBox b,
        [data-theme="dark"] #summaryBox p,
        [data-theme="dark"] #summaryBox span {
            color: #d1d5db !important;
        }

        [data-theme="dark"] #summaryBox [class*="text-[#8B0000]"],
        [data-theme="dark"] #summaryBox .text-\[\#8B0000\] {
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #summaryBox img {
            border-color: rgba(255, 255, 255, .14) !important;
        }

        #introBookingModal::backdrop {
            background: rgba(17, 24, 39, 0.45);
            backdrop-filter: blur(12px);
        }

        #introBookingModal {
            margin: auto !important;
            transform: scale(.96) translateY(10px);
            opacity: 0;
            border: 0;
            padding: 0;
            background: transparent;
            width: min(calc(100vw - 1rem), 560px);
            border-radius: 28px;
            overflow: hidden;
        }

        #introBookingModal[open] {
            animation: introModalPop .34s cubic-bezier(.22, 1, .36, 1) forwards;
        }

        html.intro-modal-open,
        body.intro-modal-open {
            overflow: hidden !important;
        }

        body.intro-modal-open {
            position: fixed;
            width: 100%;
        }

        #introBookingModal .intro-booking-modal-panel {
            overflow: hidden;
            border-radius: 28px;
            background: linear-gradient(180deg, #fffdfa 0%, #fff6f2 100%);
            background-clip: padding-box;
            border: 1px solid #eaded8;
            box-shadow: 0 28px 90px rgba(78, 19, 19, 0.18), 0 10px 28px rgba(0, 0, 0, 0.08);
            color: #2f221d;
        }

        #introBookingModal .intro-booking-modal-hero {
            position: relative;
            padding: 1.1rem 1.25rem 0.95rem;
            color: #ffffff;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.12), transparent 40%),
                linear-gradient(135deg, #8B0000 0%, #660000 100%);
        }

        #introBookingModal .intro-booking-modal-hero::after {
            content: "";
            position: absolute;
            inset: auto -30% -42% 38%;
            height: 180px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.16), transparent 64%);
            pointer-events: none;
        }

        #introBookingModal .intro-booking-modal-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.7rem;
            font-size: 0.73rem;
            font-weight: 800;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.78);
        }

        #introBookingModal .intro-booking-modal-title {
            margin: 0;
            font-size: clamp(1.45rem, 2.2vw, 2rem);
            line-height: 1.04;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        #introBookingModal .intro-booking-modal-subtitle {
            margin: 0.65rem 0 0;
            max-width: 34rem;
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        #introBookingModal .intro-booking-modal-body {
            padding: 1rem 1.25rem 1.05rem;
        }

        #introBookingModal .intro-step-strip {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.42rem;
            margin-bottom: 0.85rem;
        }

        #introBookingModal .intro-step-pill {
            border-radius: 14px;
            border: 1px solid #eaded8;
            background: linear-gradient(180deg, #ffffff 0%, #fff8f5 100%);
            color: #5b4a44;
            padding: 0.58rem 0.38rem;
            text-align: center;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.03);
            font-size: 0.68rem;
            font-weight: 800;
            line-height: 1.2;
        }

        #introBookingModal .intro-step-pill span {
            display: block;
            margin-top: 0.1rem;
            font-size: 0.78rem;
            letter-spacing: -0.02em;
            color: #231815;
        }

        #introBookingModal .intro-checklist {
            display: grid;
            gap: 0.65rem;
            margin-top: 0.1rem;
        }

        #introBookingModal .intro-check-item {
            display: flex;
            align-items: flex-start;
            gap: 0.72rem;
            padding: 0.72rem 0.8rem;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #fffaf8 100%);
            border: 1px solid #efe3de;
            color: #564640;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.03);
        }

        #introBookingModal .intro-check-icon {
            width: 1.7rem;
            height: 1.7rem;
            flex: 0 0 1.7rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(139, 0, 0, 0.08);
            color: #8B0000;
            margin-top: 0.03rem;
            font-size: 0.8rem;
        }

        #introBookingModal .intro-check-item p {
            margin: 0;
            font-size: 0.88rem;
            line-height: 1.45;
            color: inherit;
        }

        #introBookingModal .intro-check-item b {
            color: #8B0000;
        }

        #introBookingModal .intro-modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 0.95rem;
        }

        #introBookingModal .intro-action-btn {
            min-height: 44px;
            flex: 1 1 0;
            min-width: 148px;
            justify-content: center;
            border-radius: 14px;
            text-decoration: none;
            font-size: 0.84rem;
        }

        #introBookingModal .intro-action-primary {
            background: linear-gradient(135deg, #8B0000, #b00000);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: 0 14px 30px rgba(139, 0, 0, 0.28);
        }

        #introBookingModal .intro-action-secondary {
            background: linear-gradient(180deg, #fffefc 0%, #f8f2ef 100%);
            color: #4b3c37;
            border: 1px solid #e6d9d3;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.05);
        }

        #introBookingModal .intro-action-muted {
            background: linear-gradient(180deg, #fffefc 0%, #f7f4f2 100%);
            color: #5f524d;
            border: 1px solid #e6d9d3;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.04);
        }

        [data-theme="dark"] #introBookingModal .intro-booking-modal-panel {
            background: linear-gradient(180deg, #0d1117 0%, #111827 100%);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 28px 90px rgba(0, 0, 0, 0.52), 0 12px 30px rgba(0, 0, 0, 0.38);
            color: #e5e7eb;
        }

        [data-theme="dark"] #introBookingModal .intro-booking-modal-hero {
            background:
                radial-gradient(circle at top right, rgba(252, 165, 165, 0.12), transparent 40%),
                linear-gradient(135deg, #8B0000 0%, #660000 100%);
        }

        [data-theme="dark"] #introBookingModal .intro-booking-modal-subtitle {
            color: rgba(229, 231, 235, 0.8);
        }

        [data-theme="dark"] #introBookingModal .intro-step-pill,
        [data-theme="dark"] #introBookingModal .intro-check-item,
        [data-theme="dark"] #introBookingModal .intro-action-secondary,
        [data-theme="dark"] #introBookingModal .intro-action-muted {
            background: linear-gradient(180deg, #111827 0%, #0d1117 100%);
            border-color: rgba(255, 255, 255, 0.12);
            color: #d1d5db;
        }

        [data-theme="dark"] #introBookingModal .intro-step-pill span {
            color: #f9fafb;
        }

        [data-theme="dark"] #introBookingModal .intro-check-item {
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28);
        }

        [data-theme="dark"] #introBookingModal .intro-check-icon {
            background: rgba(252, 165, 165, 0.14);
            color: #fca5a5;
        }

        [data-theme="dark"] #introBookingModal .intro-check-item b {
            color: #fca5a5;
        }

        @keyframes introModalPop {
            from {
                opacity: 0;
                transform: scale(.94) translateY(18px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .intro-action-btn {
            width: 100%;
            min-height: 42px;
            padding: .65rem 1rem;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            font-size: .86rem;
            font-weight: 850;
            line-height: 1;
            transition:
                transform .18s cubic-bezier(.22, 1, .36, 1),
                box-shadow .18s ease,
                background .18s ease,
                border-color .18s ease,
                filter .18s ease;
            will-change: transform;
        }

        .intro-action-btn i {
            font-size: .82rem;
        }

        .intro-action-primary {
            background: linear-gradient(135deg, #8B0000, #b00000);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .20);
            box-shadow: 0 12px 26px rgba(139, 0, 0, .30);
        }

        .intro-action-secondary {
            background: rgba(255, 255, 255, .07);
            color: #f3f4f6;
            border: 1px solid rgba(255, 255, 255, .14);
        }

        .intro-action-muted {
            background: rgba(255, 255, 255, .035);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, .10);
            text-decoration: none;
        }

        .intro-action-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
        }

        .intro-action-primary:hover {
            box-shadow:
                0 0 0 5px rgba(248, 113, 113, .11),
                0 16px 34px rgba(139, 0, 0, .42);
        }

        .intro-action-secondary:hover,
        .intro-action-muted:hover {
            background: rgba(255, 255, 255, .10);
            border-color: rgba(252, 165, 165, .28);
            box-shadow:
                0 0 0 5px rgba(255, 255, 255, .035),
                0 14px 28px rgba(0, 0, 0, .25);
        }

        .intro-action-btn:active {
            transform: translateY(0) scale(.96);
            box-shadow: 0 6px 16px rgba(0, 0, 0, .25);
        }

        /* UPDATE: Signature upload + draw side by side */
        .signature-methods-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 1rem;
            align-items: stretch;
        }

        .signature-methods-grid .file-upload-zone {
            min-height: 280px;
            height: 100%;
        }

        .signature-draw-card {
            border: 1px solid #e8e2dd;
            border-radius: 18px;
            background: linear-gradient(180deg, #fffefe 0%, #fff8f7 100%);
            padding: 1rem;
            height: 100%;
        }

        .signature-draw-title {
            text-align: center;
            color: #8B0000;
            font-size: 0.95rem;
            font-weight: 900;
            margin-bottom: 0.85rem;
        }

        .signature-pad-wrap {
            position: relative;
            border: 2px dashed #d9d9d9;
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
        }

        .signature-pad-canvas {
            width: 100%;
            height: 170px;
            display: block;
            background: #ffffff;
            cursor: crosshair;
            touch-action: none;
        }

        .signature-pad-footer {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-top: 0.85rem;
        }

        .signature-pad-help {
            font-size: 0.75rem;
            color: #9e9690;
        }

        .signature-pad-actions {
            display: flex;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        .signature-pad-btn {
            min-height: 40px;
            border-radius: 14px;
            padding: 0.55rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 850;
            border: 1px solid #e8e2dd;
            background: #ffffff;
            color: #8B0000;
            transition: all 0.2s ease;
        }

        .signature-pad-btn:hover {
            border-color: #8B0000;
            background: #fff5f5;
        }

        .signature-pad-btn.primary {
            background: linear-gradient(135deg, #8B0000, #660000);
            border-color: #8B0000;
            color: #ffffff;
        }

        .signature-pad-btn.primary:hover {
            filter: brightness(1.05);
        }

        [data-theme="dark"] #mainContent .signature-draw-card {
            background: linear-gradient(145deg, #0d1117, #111827) !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
        }

        [data-theme="dark"] #mainContent .signature-draw-title {
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #mainContent .signature-pad-wrap {
            border-color: rgba(255, 255, 255, 0.16) !important;
            background: #ffffff !important;
        }

        [data-theme="dark"] #mainContent .signature-pad-help {
            color: #9ca3af !important;
        }

        [data-theme="dark"] #mainContent .signature-pad-btn {
            background: #161b22 !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
            color: #fecaca !important;
        }

        [data-theme="dark"] #mainContent .signature-pad-btn:hover {
            background: #1f2937 !important;
            border-color: rgba(252, 165, 165, 0.35) !important;
        }

        [data-theme="dark"] #mainContent .signature-pad-btn.primary {
            background: linear-gradient(135deg, #8B0000, #660000) !important;
            color: #ffffff !important;
        }


        /* FINAL OVERRIDE: Signature upload and draw pad full-width layout */
        .signature-methods-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
            gap: 1.5rem !important;
            align-items: stretch !important;
            width: 100% !important;
            max-width: none !important;
            padding: 1rem !important;
            border: 1px dashed #e8e2dd !important;
            border-radius: 22px !important;
            box-sizing: border-box !important;
        }

        .signature-methods-grid .file-upload-zone {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            min-height: 360px !important;
            height: 100% !important;
            padding: 2rem !important;
            box-sizing: border-box !important;
            border-radius: 18px !important;
        }

        .signature-draw-card {
            width: 100% !important;
            min-height: 360px !important;
            height: 100% !important;
            padding: 1.35rem 1.45rem !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            border-radius: 18px !important;
        }

        .signature-draw-title {
            margin-bottom: 1rem !important;
            text-align: center !important;
        }

        .signature-pad-wrap {
            flex: 1 1 auto !important;
            min-height: 250px !important;
            height: 250px !important;
        }

        .signature-pad-canvas {
            width: 100% !important;
            height: 100% !important;
            min-height: 250px !important;
            display: block !important;
        }

        .signature-pad-footer {
            margin-top: 1rem !important;
            flex: 0 0 auto !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.9rem !important;
            justify-content: flex-start !important;
        }

        .signature-pad-help {
            text-align: center !important;
        }

        .signature-pad-actions {
            display: grid !important;
            grid-template-columns: 96px 136px 174px !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 0.65rem !important;
            width: 100% !important;
        }

        .signature-pad-btn {
            width: 100% !important;
            min-width: 0 !important;
            height: 42px !important;
            min-height: 42px !important;
            padding: 0 0.55rem !important;
            border-radius: 13px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            white-space: nowrap !important;
            font-size: 0.72rem !important;
            line-height: 1 !important;
            text-align: center !important;
        }

        .signature-pad-btn.primary {
            width: 100% !important;
            min-width: 0 !important;
            margin-left: 0 !important;
            font-size: 0.72rem !important;
        }

        /* FIX: Main card + nav buttons polished position */
        #mainContent .book-card {
            overflow: hidden !important;
        }

        #mainContent .book-card>div:last-child {
            padding: 1.5rem 1.75rem 1.35rem !important;
        }

        #mainContent #appointmentForm {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
        }

        /* Do not force display here, JS controls hide/show on last step */
        #mainContent #navBtns {
            width: calc(100% - 2.7rem) !important;
            max-width: calc(100% - 2.7rem) !important;
            margin: 1rem auto 0 !important;
            padding: 0 !important;
            justify-content: flex-end !important;
            align-items: center !important;
            gap: 0.75rem !important;
            position: static !important;
        }

        #mainContent #prevBtn,
        #mainContent #nextBtn {
            min-height: 48px !important;
            border-radius: 16px !important;
        }

        /* Step 4 signature size, not too tall */
        #mainContent .signature-section-card {
            margin-top: 1rem !important;
        }

        #mainContent .signature-methods-grid {
            min-height: 360px !important;
        }

        #mainContent .signature-methods-grid .file-upload-zone {
            min-height: 340px !important;
        }

        #mainContent .signature-draw-card {
            min-height: 340px !important;
        }

        #mainContent .signature-pad-wrap {
            height: 210px !important;
            min-height: 210px !important;
        }

        #mainContent .signature-pad-canvas {
            height: 100% !important;
            min-height: 210px !important;
        }

        @media (max-width: 768px) {
            #mainContent .book-card>div:last-child {
                padding: 1rem !important;
            }

            #mainContent #navBtns {
                width: 100% !important;
                max-width: 100% !important;
                margin: 1rem 0 0 !important;
                padding: 0 !important;
                display: flex !important;
                justify-content: stretch !important;
                align-items: center !important;
                gap: 0.65rem !important;
            }

            #mainContent #prevBtn,
            #mainContent #nextBtn {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                justify-content: center !important;
                min-height: 46px !important;
                padding-left: 0.9rem !important;
                padding-right: 0.9rem !important;
                border-radius: 15px !important;
                font-size: 0.82rem !important;
            }

            #mainContent .signature-methods-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
                padding: 0.85rem !important;
                min-height: auto !important;
            }

            #mainContent .signature-methods-grid .file-upload-zone,
            #mainContent .signature-draw-card {
                width: 100% !important;
                height: auto !important;
                min-height: 300px !important;
            }

            #mainContent .signature-pad-wrap {
                height: 210px !important;
                min-height: 210px !important;
            }

            #mainContent .signature-pad-canvas {
                height: 100% !important;
                min-height: 210px !important;
            }

            #mainContent .signature-pad-actions {
                display: grid !important;
                grid-template-columns: 0.72fr 1fr 1.28fr !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0.45rem !important;
                width: 100% !important;
            }

            #mainContent .signature-pad-btn,
            #mainContent .signature-pad-btn.primary {
                width: 100% !important;
                min-width: 0 !important;
                height: 40px !important;
                min-height: 40px !important;
                margin-left: 0 !important;
                padding: 0 0.3rem !important;
                justify-content: center !important;
                white-space: nowrap !important;
                font-size: 0.62rem !important;
                border-radius: 12px !important;
            }
        }

        @media (max-width: 480px) {
            #mainContent .book-page-wrap {
                width: min(100% - 0.75rem, 1180px) !important;
            }

            #mainContent .booking-step-shell {
                padding: 0.85rem !important;
                border-radius: 20px !important;
            }

            #mainContent .booking-step-title {
                font-size: 1.45rem !important;
            }

            #mainContent .booking-step-subtitle {
                font-size: 0.82rem !important;
            }

            #mainContent #navBtns {
                gap: 0.55rem !important;
            }

            #mainContent #prevBtn,
            #mainContent #nextBtn {
                min-height: 44px !important;
                font-size: 0.78rem !important;
            }
        }


        /* WALK-IN: drawing tablet only, no upload UI */
        #dentistWalkInPage .signature-methods-grid.signature-drawing-only,
        #dentistBookAppointmentPage .signature-methods-grid.signature-drawing-only,
        #mainContent .signature-methods-grid.signature-drawing-only {
            display: grid !important;
            grid-template-columns: 1fr !important;
            width: 100% !important;
            max-width: 820px !important;
            margin: 0 auto !important;
            padding: 1rem !important;
            border: 1px dashed #e8e2dd !important;
            border-radius: 22px !important;
        }

        #dentistWalkInPage .signature-methods-grid.signature-drawing-only .signature-draw-card,
        #dentistBookAppointmentPage .signature-methods-grid.signature-drawing-only .signature-draw-card,
        #mainContent .signature-methods-grid.signature-drawing-only .signature-draw-card {
            min-height: 340px !important;
            width: 100% !important;
        }

        #dentistWalkInPage .signature-methods-grid.signature-drawing-only .signature-pad-wrap,
        #dentistBookAppointmentPage .signature-methods-grid.signature-drawing-only .signature-pad-wrap,
        #mainContent .signature-methods-grid.signature-drawing-only .signature-pad-wrap {
            height: 230px !important;
            min-height: 230px !important;
        }

        @media (max-width: 768px) {

            #dentistWalkInPage .signature-methods-grid.signature-drawing-only,
            #dentistBookAppointmentPage .signature-methods-grid.signature-drawing-only,
            #mainContent .signature-methods-grid.signature-drawing-only {
                max-width: 100% !important;
                padding: 0.85rem !important;
            }
        }
    </style>
@endsection

@php
    $notifications = collect($notifications ?? []);
    $notifCount = $notifications->count();
    $isFemalePatient = strtolower($patient->gender ?? '') === 'female';
@endphp

@section('content')
    <main id="mainContent" class="book-container min-h-screen bg-[#F4F4F4] dark:bg-[#020b14] py-6">
        <div id="dentistBookAppointmentPage">
            <div class="book-page-wrap">

                <div class="w-full pt-10 pb-2 animate-fade-up">

                    <div class="flex items-center justify-between mt-8 mb-4">
                        <a href="{{ route('dentist.dentist.appointments') }}"
                            class="back-home-btn flex items-center gap-2 bg-[#8B0000] hover:bg-[#660000] text-white px-4 py-2 rounded-xl text-xs font-bold border border-[#660000] transition shadow-sm">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            Back to Appointments
                        </a>
                        <span
                            class="step-counter-pill text-xs text-[#9e9690] font-semibold bg-white border border-[#e8e2dd] px-3 py-1.5 rounded-full shadow-sm">
                            Step <span id="stepCounterText">1</span> <span class="text-[#c4bfba]">of 5</span>
                        </span>
                    </div>

                    <div class="w-full h-2 rounded-full bg-[#e8e2dd] overflow-hidden mb-5">
                        <div id="headerProgressFill" class="h-full rounded-full progress-fill"
                            style="width:20%; background: linear-gradient(90deg, #8B0000, #c9a84c)"></div>
                    </div>

                    <div class="text-center mb-1">
                        <p class="text-xs font-semibold uppercase tracking-widest mb-1 text-[#8B0000]">
                            <i class="fa-regular fa-calendar-check mr-1"></i> PUP TAGUIG DENTAL CLINIC
                        </p>
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-[#660000]">Walk-in Appointment</h1>
                        <p class="text-sm text-[#9e9690] mt-1">Create a walk-in appointment and proceed directly to
                            procedure.</p>
                    </div>

                </div>

                <div class="w-full pb-16">

                    <div class="w-full mt-4 mb-0 animate-fade-up-1 py-3 px-2" style="overflow: visible;">
                        <div class="flex items-start justify-between w-full" style="padding: 6px 0;">
                            <div class="flex flex-col items-center gap-1 min-w-0 flex-1">
                                <div id="sc1"
                                    class="step-circle w-10 h-10 rounded-full border-2 border-blue-600 bg-blue-600 flex items-center justify-center text-sm font-bold text-white shadow-[0_0_0_6px_rgba(37,99,235,0.12)] scale-110">
                                    1</div>
                                <span id="sl1"
                                    class="step-label text-[0.65rem] font-semibold uppercase tracking-wide text-blue-600 text-center hidden sm:block mt-4">Patient</span>
                            </div>

                            <div id="conn1" class="h-0.5 bg-[#e8e2dd] flex-shrink-0 self-start step-connector"
                                style="width:clamp(8px, 3vw, 40px); margin-top:20px;"></div>

                            <div class="flex flex-col items-center gap-1 min-w-0 flex-1">
                                <div id="sc2"
                                    class="step-circle w-10 h-10 rounded-full border-2 border-[#e8e2dd] bg-white flex items-center justify-center text-sm font-bold text-[#9e9690]">
                                    2</div>
                                <span id="sl2"
                                    class="step-label text-[0.65rem] font-semibold uppercase tracking-wide text-[#9e9690] text-center hidden sm:block mt-4">Service</span>
                            </div>

                            <div id="conn2" class="h-0.5 bg-[#e8e2dd] flex-shrink-0 self-start step-connector"
                                style="width:clamp(8px, 3vw, 40px); margin-top:20px;"></div>

                            <div class="flex flex-col items-center gap-1 min-w-0 flex-1">
                                <div id="sc3"
                                    class="step-circle w-10 h-10 rounded-full border-2 border-[#e8e2dd] bg-white flex items-center justify-center text-sm font-bold text-[#9e9690]">
                                    3</div>
                                <span id="sl3"
                                    class="step-label text-[0.65rem] font-semibold uppercase tracking-wide text-[#9e9690] text-center hidden sm:block mt-4">Dental
                                    History</span>
                            </div>

                            <div id="conn3" class="h-0.5 bg-[#e8e2dd] flex-shrink-0 self-start step-connector"
                                style="width:clamp(8px, 3vw, 40px); margin-top:20px;"></div>

                            <div class="flex flex-col items-center gap-1 min-w-0 flex-1">
                                <div id="sc4"
                                    class="step-circle w-10 h-10 rounded-full border-2 border-[#e8e2dd] bg-white flex items-center justify-center text-sm font-bold text-[#9e9690]">
                                    4</div>
                                <span id="sl4"
                                    class="step-label text-[0.65rem] font-semibold uppercase tracking-wide text-[#9e9690] text-center hidden sm:block mt-4">Medical
                                    History</span>
                            </div>

                            <div id="conn4" class="h-0.5 bg-[#e8e2dd] flex-shrink-0 self-start step-connector"
                                style="width:clamp(8px, 3vw, 40px); margin-top:20px;"></div>

                            <div class="flex flex-col items-center gap-1 min-w-0 flex-1">
                                <div id="sc5"
                                    class="step-circle w-10 h-10 rounded-full border-2 border-[#e8e2dd] bg-white flex items-center justify-center text-sm font-bold text-[#9e9690]">
                                    5</div>
                                <span id="sl5"
                                    class="step-label text-[0.65rem] font-semibold uppercase tracking-wide text-[#9e9690] text-center hidden sm:block mt-4">Start
                                    Procedure</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="book-card mt-6 w-full mx-auto bg-white rounded-2xl shadow-[0_4px_40px_rgba(0,0,0,0.08),0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden animate-fade-up-2">
                        <div class="h-1 w-full" style="background: linear-gradient(90deg, #660000, #8B0000, #c9a84c)">
                        </div>
                        <div class="p-6 sm:p-8">

                            <form id="appointmentForm" action="{{ route('dentist.walk-in.start') }}" method="POST"
                                enctype="multipart/form-data">

                                <input type="hidden" name="patient_id" id="selectedPatientId">
                                <input type="hidden" name="patient_mode" id="patientMode" value="existing">
                                @csrf

                              <div class="step-content hidden">
    <div class="booking-step-shell walkin-step1-layout" id="patientAccountSection">
        <div class="booking-step-header">
            <p class="booking-step-eyebrow">
                <i class="fa-solid fa-user-plus text-[11px] mr-1"></i>
                Step 1 of 5
            </p>

            <h2 class="booking-step-title">Patient Account</h2>

            <p class="booking-step-subtitle">
                Search an existing student, faculty, or administrative patient, or create a
                guest account for walk-in onboarding.
            </p>
        </div>

        <div class="booking-step-body">
            <div class="section-card walkin-account-card">
                <p class="section-card-title">
                    <i class="fa-solid fa-user-magnifying-glass text-xs"></i>
                    Select or Create Patient
                    <span class="section-card-title-line"></span>
                </p>

                <div class="account-tabs">
                    <button type="button" class="account-tab active" data-tab="existing">
                        <i class="fa-solid fa-database text-[13px]"></i>
                        Existing / Unified DB
                    </button>

                    <button type="button" class="account-tab" data-tab="guest">
                        <i class="fa-solid fa-user-plus text-[13px]"></i>
                        Guest Onboarding
                    </button>
                </div>

                <div class="tab-panel active" id="existingPanel">
                    <div class="walkin-search-wrap">
                        <i class="fa-solid fa-magnifying-glass walkin-search-icon"></i>

                        <input type="text" id="patientSearch"
                            class="form-input walkin-search-input w-full border border-[#e8e2dd] rounded-xl bg-white outline-none"
                            placeholder="Search by name, ID, or email..."
                            autocomplete="off">
                    </div>

                    <div class="patient-results" id="patientResults">
                        <div class="empty-state">
                            Search for a student, faculty, or administrative patient.
                        </div>
                    </div>
                </div>

                <div class="tab-panel" id="guestPanel">
                    <div class="guest-fields-grid">
                        <div>
                            <label class="walkin-field-label" for="guestName">
                                Guest Full Name <span class="required-star">*</span>
                            </label>

                            <input type="text" id="guestName" name="guest_name"
                                class="form-input w-full border border-[#e8e2dd] rounded-xl bg-white outline-none"
                                placeholder="Enter guest full name"
                                autocomplete="off"
                                disabled>
                        </div>

                        <div class="walkin-two-col">
                            <div>
                                <label class="walkin-field-label" for="guestEmail">
                                    Email
                                </label>

                                <input type="email" id="guestEmail" name="guest_email"
                                    class="form-input w-full border border-[#e8e2dd] rounded-xl bg-white outline-none"
                                    placeholder="Optional"
                                    autocomplete="off"
                                    disabled>
                            </div>

                            <div>
                                <label class="walkin-field-label" for="guestPhone">
                                    Phone
                                </label>

                                <input type="text" id="guestPhone" name="guest_phone"
                                    class="form-input w-full border border-[#e8e2dd] rounded-xl bg-white outline-none"
                                    placeholder="Optional"
                                    autocomplete="off"
                                    disabled>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="guest-create-btn" id="createGuestBtn">
                        <i class="fa-solid fa-user-plus"></i>
                        Create Guest Account
                    </button>
                </div>

                <div class="selected-patient-box" id="selectedPatientBox" hidden>
                    <span>
                        <i class="fa-solid fa-circle-check text-[11px] mr-1"></i>
                        Selected Patient
                    </span>
                    <strong id="selectedPatientName"></strong>
                    <small id="selectedPatientMeta"></small>
                </div>
            </div>
        </div>
    </div>
</div>
                                    <div class="step-content hidden">
                                        <div class="booking-step-shell">
                                            <div class="booking-step-header">
                                                <p class="booking-step-eyebrow">Step 2 of 5</p>
                                                <h2 class="booking-step-title">Choose Your Dental Service</h2>
                                                <p class="booking-step-subtitle">
                                                    Select the type of service for this walk-in appointment.
                                                </p>
                                            </div>

                                            <div class="booking-step-body">
                                                <div class="service-step-grid">
                                                    @foreach ($serviceTypes as $service)
                                                        <label class="service-option group">
                                                            <input type="radio" name="service_type"
                                                                value="{{ $service['name'] }}"
                                                                class="service-option-input">

                                                            <div class="service-option-card">
                                                                <div class="service-option-main">
                                                                    <div class="service-option-icon">
                                                                        @if (!empty($service['img']))
                                                                            <img src="{{ asset('images/' . $service['img'] . '.png') }}"
                                                                                class="w-6 h-6"
                                                                                style="filter:brightness(0) saturate(100%) invert(8%) sepia(80%) saturate(3000%) hue-rotate(345deg)" />
                                                                        @else
                                                                            <i class="fa-solid fa-tooth"></i>
                                                                        @endif
                                                                    </div>

                                                                    <div class="service-option-copy">
                                                                        <div class="service-option-topline">
                                                                            <p class="service-option-title">
                                                                                {{ $service['name'] }}</p>
                                                                            <span
                                                                                class="service-option-badge">Available</span>
                                                                        </div>
                                                                        <p class="service-option-desc">
                                                                            {{ $service['desc'] }}</p>
                                                                    </div>
                                                                </div>

                                                                <div class="service-option-arrow">
                                                                    <i class="fa-solid fa-chevron-right"></i>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <div class="step-content hidden">
                                    <div class="booking-step-header">
                                        <p class="booking-step-eyebrow">Step 3 of 5</p>
                                        <h2 class="booking-step-title">Dental History</h2>
                                        <p class="booking-step-subtitle">
                                            Record the patient's dental and medical information before starting the
                                            procedure.
                                        </p>
                                    </div>

                                    <div class="booking-step-body">

                                        <div class="section-card">
                                            <p class="section-card-title">
                                                <i class="fa-regular fa-calendar-days text-xs"></i> Basic Info
                                                <span class="section-card-title-line"></span>
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-[#333] mb-1.5">Last
                                                        Dental
                                                        Visit</label>
                                                    <div class="date-input-wrap compact">
                                                        <input type="text" id="lastDentalVisit"
                                                            name="last_dental_visit"
                                                            class="js-flatpickr-date-max-today form-input w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none"
                                                            placeholder="Select date" readonly required>
                                                        <i class="fa-regular fa-calendar date-input-icon"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-[#333] mb-1.5">Previous
                                                        Dentist</label>
                                                    <input type="text" id="previous_dentist" name="previous_dentist"
                                                        maxlength="50"
                                                        class="form-input w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none"
                                                        placeholder="Dr. Name" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section-card">
                                            <p
                                                class="flex items-center gap-2 text-[0.78rem] font-bold text-[#8B0000] uppercase tracking-widest mb-3">
                                                <i class="fa-solid fa-tooth text-xs"></i> Dental Symptoms <span
                                                    class="flex-1 h-px bg-[#f9e8e8]"></span>
                                            </p>
                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                                <span>Question</span><span class="text-center">YES</span><span
                                                    class="text-center">NO</span>
                                            </div>
                                            @php
                                                $dentalQ1 = [
                                                    [
                                                        'name' => 'bleeding_gums',
                                                        'q' => 'Do your gums bleed while brushing/flossing?',
                                                    ],
                                                    [
                                                        'name' => 'sensitive_temp',
                                                        'q' => 'Are your teeth sensitive to hot or cold?',
                                                    ],
                                                    [
                                                        'name' => 'sensitive_taste',
                                                        'q' => 'Are your teeth sensitive to sweets or sour?',
                                                    ],
                                                    [
                                                        'name' => 'tooth_pain',
                                                        'q' => 'Do you feel any pain in your teeth?',
                                                    ],
                                                    [
                                                        'name' => 'sores',
                                                        'q' => 'Do you have any sores/lumps in or near your mouth?',
                                                    ],
                                                    [
                                                        'name' => 'injuries',
                                                        'q' => 'Have you had any head, neck, or jaw injuries?',
                                                    ],
                                                ];
                                            @endphp
                                            @foreach ($dentalQ1 as $q)
                                                <div
                                                    class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 {{ !$loop->last ? 'border-b border-[#f0ebe6]' : '' }} text-sm text-[#1a1410]">
                                                    <span class="leading-snug question-text">{{ $q['q'] }}</span>
                                                    <input type="radio" name="{{ $q['name'] }}" value="YES"
                                                        class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                        required>
                                                    <input type="radio" name="{{ $q['name'] }}" value="NO"
                                                        class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="section-card">
                                            <p
                                                class="flex items-center gap-2 text-[0.78rem] font-bold text-[#8B0000] uppercase tracking-widest mb-3">
                                                <i class="fa-solid fa-circle-dot text-xs"></i> Jaw &amp; Bite Symptoms
                                                <span class="flex-1 h-px bg-[#f9e8e8]"></span>
                                            </p>
                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                                <span>Question</span><span class="text-center">YES</span><span
                                                    class="text-center">NO</span>
                                            </div>
                                            @php
                                                $dentalQ2 = [
                                                    ['name' => 'clicking', 'q' => 'Clicking'],
                                                    ['name' => 'joint_pain', 'q' => 'Pain (joint, side of the face)'],
                                                    [
                                                        'name' => 'difficulty_moving',
                                                        'q' => 'Difficulty in opening/closing',
                                                    ],
                                                    ['name' => 'difficulty_chewing', 'q' => 'Difficulty in chewing'],
                                                    ['name' => 'jaw_headaches', 'q' => 'Frequent headaches'],
                                                    [
                                                        'name' => 'clench_grind',
                                                        'q' => 'Do you clench or grind your teeth?',
                                                    ],
                                                    ['name' => 'biting', 'q' => 'Frequent lips/cheek biting'],
                                                    [
                                                        'name' => 'teeth_loosening',
                                                        'q' => 'Have you noticed loosening of your teeth?',
                                                    ],
                                                    [
                                                        'name' => 'food_teeth',
                                                        'q' => 'Does food get caught between your teeth?',
                                                    ],
                                                    [
                                                        'name' => 'med_reaction',
                                                        'q' =>
                                                            'Have you ever had a reaction to any medicine or dental anesthetic?',
                                                    ],
                                                ];
                                            @endphp
                                            @foreach ($dentalQ2 as $q)
                                                <div
                                                    class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 {{ !$loop->last ? 'border-b border-[#f0ebe6]' : '' }} text-sm text-[#1a1410]">
                                                    <span class="leading-snug question-text">{{ $q['q'] }}</span>
                                                    <input type="radio" name="{{ $q['name'] }}" value="YES"
                                                        class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                        required>
                                                    <input type="radio" name="{{ $q['name'] }}" value="NO"
                                                        class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                                </div>
                                            @endforeach
                                            <p class="text-xs text-[#8B0000] mt-2 italic pl-4">
                                                <i class="fa-solid fa-circle-info mr-1"></i> If <b>YES</b>, please
                                                provide
                                                details
                                                during your
                                                consultation.
                                            </p>
                                        </div>

                                        <div class="section-card">
                                            <p
                                                class="flex items-center gap-2 text-[0.78rem] font-bold text-[#8B0000] uppercase tracking-widest mb-3">
                                                <i class="fa-solid fa-notes-medical text-xs"></i> Dental Procedures
                                                <span class="flex-1 h-px bg-[#f9e8e8]"></span>
                                            </p>
                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                                <span>Question</span><span class="text-center">YES</span><span
                                                    class="text-center">NO</span>
                                            </div>

                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 border-b border-[#f0ebe6] text-sm">
                                                <span class="question-text">Have you had any periodontal (gum)
                                                    treatment?</span>
                                                <input type="radio" name="periodontal" value="YES"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                    required>
                                                <input type="radio" name="periodontal" value="NO"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                            </div>

                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 border-b border-[#f0ebe6] text-sm">
                                                <span class="question-text">Have you had a difficult tooth
                                                    extraction?</span>
                                                <input type="radio" name="difficult_extraction" value="YES"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                    required>
                                                <input type="radio" name="difficult_extraction" value="NO"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                            </div>
                                            <div class="ml-6 mt-2 mb-2 hidden" id="extraction_date_box">
                                                <label class="text-xs text-[#8B0000] italic block mb-1">Date of
                                                    extraction:</label>
                                                <div class="date-input-wrap compact">
                                                    <input type="text" id="extractionDate" name="extraction_date"
                                                        class="js-flatpickr-date-max-today w-full form-input voice-small border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none w-full"
                                                        placeholder="Select date" readonly>
                                                    <i class="fa-regular fa-calendar date-input-icon"></i>
                                                </div>
                                            </div>

                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 border-b border-[#f0ebe6] text-sm">
                                                <span class="question-text">Have you had prolonged bleeding following
                                                    tooth
                                                    extractions?</span>
                                                <input type="radio" name="prolonged_bleeding" value="YES"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                    required>
                                                <input type="radio" name="prolonged_bleeding" value="NO"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                            </div>

                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 border-b border-[#f0ebe6] text-sm">
                                                <span class="question-text">Do you wear complete or partial
                                                    dentures?</span>
                                                <input type="radio" name="dentures" value="YES"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                    required>
                                                <input type="radio" name="dentures" value="NO"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                            </div>
                                            <div class="ml-6 mt-2 mb-2 hidden" id="dentures_date_box">
                                                <label class="text-xs text-[#8B0000] italic block mb-1">Date of
                                                    placement:</label>
                                                <div class="date-input-wrap compact">
                                                    <input type="text" id="denturesDate" name="dentures_date"
                                                        class="js-flatpickr-date-max-today w-full form-input voice-small border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none w-full"
                                                        placeholder="Select date" readonly>
                                                    <i class="fa-regular fa-calendar date-input-icon"></i>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 text-sm">
                                                <span class="question-text">Have you had orthodontic treatment?</span>
                                                <input type="radio" name="ortho_treatment" value="YES"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                    required>
                                                <input type="radio" name="ortho_treatment" value="NO"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                            </div>
                                            <div class="ml-6 mt-2 mb-2 hidden" id="ortho_date_box">
                                                <label class="text-xs text-[#8B0000] italic block mb-1">Date of
                                                    completion:</label>
                                                <div class="date-input-wrap compact">
                                                    <input type="text" id="orthoDate" name="ortho_date"
                                                        class="js-flatpickr-date-max-today w-full form-input voice-small border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none w-full"
                                                        placeholder="Select date" readonly>
                                                    <i class="fa-regular fa-calendar date-input-icon"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section-card">
                                            <p
                                                class="flex items-center gap-2 text-[0.78rem] font-bold text-[#8B0000] uppercase tracking-widest mb-3">
                                                <i class="fa-regular fa-comment-dots text-xs"></i> Additional Concerns
                                                <span class="flex-1 h-px bg-[#f9e8e8]"></span>
                                            </p>
                                            <textarea name="additional_concerns" id="additional_concerns" rows="4" maxlength="150"
                                                class="form-input voice-full w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none resize-none"
                                                placeholder="Write any additional concerns here..."></textarea>
                                            <div class="flex justify-between items-center text-xs mt-1">
                                                <span id="concernWarning" class="text-red-500 hidden">
                                                    Character limit reached
                                                </span>
                                                <span id="concernCount" class="text-[#9e9690]">0/150</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>

                        <div class="step-content hidden">
                            <div class="booking-step-shell">
                                <div class="booking-step-header">
                                    <p class="booking-step-eyebrow">Step 4 of 5</p>
                                    <h2 class="booking-step-title">Medical History</h2>
                                    <p class="booking-step-subtitle">
                                        Provide important medical information so the clinic can prepare safe and
                                        proper
                                        dental care for you.
                                    </p>
                                </div>

                                <div class="booking-step-body">

                                    <div class="section-card">
                                        <p class="section-card-title">
                                            <i class="fa-solid fa-heart-pulse text-xs"></i> General Health
                                            <span class="section-card-title-line"></span>
                                        </p>
                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                            <span>Question</span><span class="text-center">YES</span><span
                                                class="text-center">NO</span>
                                        </div>

                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 border-b border-[#f0ebe6] text-sm">
                                            <span class="question-text">Are you in good health?</span>
                                            <input type="radio" name="good_health" value="YES"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                required>
                                            <input type="radio" name="good_health" value="NO"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                        </div>
                                        <div class="ml-6 mt-1 mb-2 hidden" id="good_health_box">
                                            <label class="text-xs text-[#8B0000] italic">If NO, please provide
                                                details:</label>
                                            <input type="text" name="good_health_details" maxlength="150"
                                                id="good_health_details"
                                                class="form-input mt-1 w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none"
                                                placeholder="Input here">
                                            <div class="text-right text-xs"><span id="goodHealthCount">0/150</span>
                                            </div>
                                        </div>

                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 border-b border-[#f0ebe6] text-sm">
                                            <span class="question-text">When was your last medical
                                                examination?</span>
                                            <input type="radio" name="had_medical_exam" value="YES"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                required>
                                            <input type="radio" name="had_medical_exam" value="NO"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                        </div>
                                        <div class="ml-6 mt-1 mb-2 hidden" id="medical_exam_box">
                                            <label class="text-xs text-[#8B0000] italic block mb-1">If YES, when was
                                                your
                                                last medical examination?</label>
                                            <div class="date-input-wrap compact">
                                                <input type="text" id="medicalExamDate" name="medical_exam_date"
                                                    class="js-flatpickr-date-max-today w-full form-input voice-small border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none w-full"
                                                    placeholder="Select date" readonly>
                                                <i class="fa-regular fa-calendar date-input-icon"></i>
                                            </div>
                                        </div>

                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 border-b border-[#f0ebe6] text-sm">
                                            <span class="question-text">Are you currently receiving treatment for
                                                any
                                                illness?</span>
                                            <input type="radio" name="under_treatment" value="YES"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                required>
                                            <input type="radio" name="under_treatment" value="NO"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                        </div>
                                        <div class="ml-6 mt-1 mb-2 hidden" id="treatment_box">
                                            <label class="text-xs text-[#8B0000] italic">If YES, please
                                                specify:</label>
                                            <input type="text" name="treatment_details" maxlength="150"
                                                id="treatment_details"
                                                class="form-input mt-1 w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none"
                                                placeholder="Input here">
                                            <div class="text-right text-xs"><span id="treatmentCount">0/150</span>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 text-sm">
                                            <span class="question-text">Have you ever been hospitalized?</span>
                                            <input type="radio" name="hospitalized" value="YES"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                required>
                                            <input type="radio" name="hospitalized" value="NO"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                        </div>
                                        <div class="ml-6 mt-1 mb-2 hidden" id="hospital_box">
                                            <label class="text-xs text-[#8B0000] italic">If YES, please provide
                                                details:</label>
                                            <input type="text" name="hospital_details" maxlength="150"
                                                id="hospital_details"
                                                class="form-input mt-1 w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none"
                                                placeholder="Input here">
                                            <div class="text-right text-xs"><span id="hospitalCount">0/150</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-card">
                                        <p class="section-card-title">
                                            <i class="fa-solid fa-triangle-exclamation text-xs"></i> Allergies
                                            <span class="section-card-title-line"></span>
                                        </p>
                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                            <span>Are you allergic to any of the following?</span><span
                                                class="text-center">YES</span><span class="text-center">NO</span>
                                        </div>
                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 border-b border-[#f0ebe6] text-sm">
                                            <span class="question-text">Medicines</span>
                                            <input type="radio" name="allergy_medicine" value="YES"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                required>
                                            <input type="radio" name="allergy_medicine" value="NO"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                        </div>
                                        <div class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 text-sm">
                                            <span class="question-text">Food</span>
                                            <input type="radio" name="allergy_food" value="YES"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                required>
                                            <input type="radio" name="allergy_food" value="NO"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                        </div>
                                        <div class="mt-3">
                                            <label class="text-xs text-[#8B0000] italic block mb-1">Others (please
                                                specify):</label>
                                            <input type="text" name="allergy_others"
                                                class="form-input voice-medium border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none w-full"
                                                placeholder="Input here">
                                        </div>
                                    </div>

                                    <div class="section-card">
                                        <p class="section-card-title">
                                            <i class="fa-solid fa-pills text-xs"></i> Medications
                                            <span class="section-card-title-line"></span>
                                        </p>
                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                            <span>Question</span><span class="text-center">YES</span><span
                                                class="text-center">NO</span>
                                        </div>
                                        <div class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 text-sm">
                                            <span class="question-text">Are you taking any prescription or
                                                non-prescription
                                                medication?</span>
                                            <input type="radio" name="medication" value="YES"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                required>
                                            <input type="radio" name="medication" value="NO"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                        </div>
                                        <div class="ml-6 mt-1 mb-2 hidden" id="medication_box">
                                            <label class="text-xs text-[#8B0000] italic">If YES, please
                                                specify:</label>
                                            <input type="text" name="medication_details" maxlength="150"
                                                id="medication_details"
                                                class="form-input mt-1 w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none"
                                                placeholder="Input here">
                                            <div class="text-right text-xs"><span id="medicationCount">0/150</span>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($isFemalePatient)
                                        <div class="section-card" id="forWomenSection">
                                            <p class="section-card-title">
                                                <i class="fa-solid fa-venus text-xs"></i> For Women Only
                                                <span class="section-card-title-line"></span>
                                            </p>
                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                                <span>Question</span><span class="text-center">YES</span><span
                                                    class="text-center">NO</span>
                                            </div>

                                            @foreach ([
            ['name' => 'pregnant', 'q' => 'Are you pregnant?'],
            ['name' => 'nursing', 'q' => 'Are you nursing?'],
            [
                'name' => 'birth_control',
                'q' => 'Are you taking birth
                                                                                                                                                                                                control pills?',
            ],
        ] as $i => $q)
                                                <div
                                                    class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 {{ $i < 2 ? 'border-b border-[#f0ebe6]' : '' }} text-sm">
                                                    <span class="question-text">{{ $q['q'] }}</span>
                                                    <input type="radio" name="{{ $q['name'] }}" value="YES"
                                                        class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                        required>
                                                    <input type="radio" name="{{ $q['name'] }}" value="NO"
                                                        class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <input type="hidden" name="pregnant" value="NO">
                                        <input type="hidden" name="nursing" value="NO">
                                        <input type="hidden" name="birth_control" value="NO">
                                    @endif

                                    <div class="section-card mt-5">
                                        <p class="section-card-title">
                                            <i class="fa-solid fa-stethoscope text-xs"></i> Medical Conditions
                                            <span class="section-card-title-line"></span>
                                        </p>
                                        <p class="text-xs text-[#5c5550] mb-3">Please indicate below if you
                                            presently
                                            have
                                            or have ever had any of the following:</p>
                                        <div
                                            class="medical-condition-grid grid grid-cols-1 sm:grid-cols-2 gap-y-2.5 gap-x-6">
                                            @foreach ($diseases as $d)
                                                <label class="flex items-center gap-2.5 cursor-pointer">
                                                    <input type="checkbox" name="diseases[]" value="{{ $d->code }}"
                                                        class="w-4 h-4 rounded border-2 border-[#e8e2dd] cursor-pointer accent-[#8B0000] flex-shrink-0">
                                                    <span class="text-[0.82rem] text-[#1a1410]">{{ $d->label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="section-card">
                                        <p class="section-card-title">
                                            <i class="fa-solid fa-smoking text-xs"></i> Tobacco Use
                                            <span class="section-card-title-line"></span>
                                        </p>
                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                            <span>Question</span><span class="text-center">YES</span><span
                                                class="text-center">NO</span>
                                        </div>
                                        <div class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 text-sm">
                                            <span class="question-text">Do you use tobacco products or any
                                                derivatives?</span>
                                            <input type="radio" name="tobacco_use" value="YES"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                required>
                                            <input type="radio" name="tobacco_use" value="NO"
                                                class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                        </div>
                                        <div id="tobacco_details" class="ml-6 mt-2 space-y-2 hidden text-sm">
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <span class="text-xs text-[#8B0000] italic w-28">How much per
                                                    day:</span>
                                                <input type="text" name="tobacco_per_day" placeholder="Input here"
                                                    class="form-input voice-small border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none w-full">
                                            </div>
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <span class="text-xs text-[#8B0000] italic w-28">Per week:</span>
                                                <input type="text" name="tobacco_per_week" placeholder="Input here"
                                                    class="form-input voice-small border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none w-full">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-card">
                                        <p class="section-card-title">
                                            <i class="fa-solid fa-head-side-mask text-xs"></i> Do You Suffer From
                                            <span class="section-card-title-line"></span>
                                        </p>
                                        <div
                                            class="grid grid-cols-[1fr_52px_52px] gap-2 text-[0.72rem] font-bold text-[#9e9690] uppercase tracking-widest pb-1">
                                            <span>Condition</span><span class="text-center">YES</span><span
                                                class="text-center">NO</span>
                                        </div>
                                        @foreach ([['name' => 'headaches', 'q' => 'Headaches'], ['name' => 'earaches', 'q' => 'Earaches'], ['name' => 'neck_aches', 'q' => 'Neck aches']] as $i => $q)
                                            <div
                                                class="grid grid-cols-[1fr_52px_52px] items-center gap-2 py-2.5 {{ $i < 2 ? 'border-b border-[#f0ebe6]' : '' }} text-sm">
                                                <span class="question-text">{{ $q['q'] }}</span>
                                                <input type="radio" name="{{ $q['name'] }}" value="YES"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer"
                                                    required>
                                                <input type="radio" name="{{ $q['name'] }}" value="NO"
                                                    class="q-radio appearance-none w-4 h-4 border-2 border-[#e8e2dd] rounded-full mx-auto cursor-pointer">
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="section-card">
                                        <p class="section-card-title">
                                            <i class="fa-solid fa-phone-volume text-xs"></i> Emergency Contact
                                            <span class="section-card-title-line"></span>
                                        </p>

                                        <div class="emergency-fields-stack">
                                            <div>
                                                <label class="block text-xs font-semibold text-[#333] mb-1.5">
                                                    Person to contact in case of emergency
                                                </label>
                                                <input type="text" name="emergency_person" maxlength="50"
                                                    class="form-input w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none"
                                                    placeholder="Full name" required>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-[#333] mb-1.5">
                                                    Contact Number
                                                </label>
                                                <input type="tel" id="emergency_number" name="emergency_number"
                                                    inputmode="numeric" autocomplete="tel" maxlength="13"
                                                    class="form-input border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none w-full"
                                                    placeholder="09xx xxx xxxx" required>
                                                <p id="emergency_number_feedback" class="field-help-text text-[#9e9690]">
                                                    Format: 09xx xxx xxxx
                                                </p>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-[#333] mb-1.5">
                                                    Relation to Patient <span class="required-star">*</span>
                                                </label>
                                                <div class="relative w-full">
                                                    <select id="emergency_relation" name="emergency_relation"
                                                        class="form-input w-full border border-[#e8e2dd] rounded-xl px-3 py-2 text-sm bg-white outline-none appearance-none pr-8"
                                                        required>
                                                        <option value="" disabled selected>Select relation
                                                        </option>
                                                        <option value="Mother">Mother</option>
                                                        <option value="Father">Father</option>
                                                        <option value="Sibling">Sibling</option>
                                                        <option value="Guardian">Guardian</option>
                                                        <option value="Spouse">Spouse</option>
                                                        <option value="Grandparent">Grandparent</option>
                                                        <option value="Aunt">Aunt</option>
                                                        <option value="Uncle">Uncle</option>
                                                        <option value="Cousin">Cousin</option>
                                                        <option value="Child">Child</option>
                                                    </select>
                                                    <div
                                                        class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                                        <i class="fa-solid fa-chevron-down text-[10px] text-[#5c5550]"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-card signature-section-card">
                                        <div class="signature-full-row">
                                            <label class="block text-xs font-semibold text-[#333] mb-1.5">
                                                Patient's Signature <span class="required-star">*</span>
                                            </label>

                                            {{-- Hidden file input only. No upload UI for walk-in.
                                                The drawn signature from the canvas will be converted to PNG and attached here. --}}
                                            <input type="file" name="patient_signature" id="patient_signature"
                                                class="hidden" accept=".png,image/png">

                                            <div class="signature-methods-grid signature-drawing-only">
                                                <div class="signature-draw-card">
                                                    <p class="signature-draw-title">Draw the patient's signature here</p>

                                                    <div class="signature-pad-wrap">
                                                        <canvas id="signatureCanvas"
                                                            class="signature-pad-canvas"></canvas>
                                                    </div>

                                                    <div class="signature-pad-footer">
                                                        <span class="signature-pad-help">
                                                            Use the drawing tablet, mouse, touch, or stylus. Click <b>Use
                                                                Drawn Signature</b> after signing.
                                                        </span>

                                                        <div class="signature-pad-actions">
                                                            <button type="button" id="signatureUndoBtn"
                                                                class="signature-pad-btn">
                                                                Undo
                                                            </button>

                                                            <button type="button" id="signatureClearBtn"
                                                                class="signature-pad-btn">
                                                                Clear Signature
                                                            </button>

                                                            <button type="button" id="signatureUseDrawnBtn"
                                                                class="signature-pad-btn primary">
                                                                Use Drawn Signature
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="signature_result_box"
                                                class="mt-3 hidden text-left w-full max-w-full">
                                                <p id="signature_filename"
                                                    class="text-xs text-[#5c5550] font-semibold truncate"></p>

                                                <div id="signature_error"
                                                    class="mt-1 hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 leading-5 font-semibold">
                                                </div>
                                            </div>

                                            @error('patient_signature')
                                                <p class="text-xs text-red-600 mt-3 font-semibold">
                                                    <i class="fa-solid fa-circle-exclamation mr-1"></i>
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="step-content hidden" id="step5">
                            <div id="summarySection">
                                <div class="booking-step-header">
                                    <p class="booking-step-eyebrow">Step 5 of 5</p>
                                    <h2 class="booking-step-title">Review Your Information</h2>
                                    <p class="booking-step-subtitle">
                                        Please review all the information you provided before proceeding to
                                        final
                                        confirmation.
                                    </p>
                                </div>

                                <div id="summaryBox" class="space-y-4"></div>

                                <div class="flex justify-center gap-3 mt-8 nav-btns-row">
                                    <button type="button" id="summaryBackBtn"
                                        class="btn-secondary-custom inline-flex items-center gap-2 border border-[#e8e2dd] rounded-xl px-6 py-2.5 text-sm font-semibold text-[#5c5550] bg-transparent">
                                        <i class="fa-solid fa-chevron-left text-xs"></i> Back
                                    </button>
                                    <button type="button" id="goToConfirmationBtn"
                                        class="btn-primary-custom inline-flex items-center gap-2 bg-[#8B0000] text-white rounded-xl px-6 py-2.5 text-sm font-bold">
                                        Proceed to Confirm <i class="fa-solid fa-chevron-right text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="confirmationSection" class="hidden">
                                <div class="booking-step-header">
                                    <p class="booking-step-eyebrow">Step 5 of 5</p>
                                    <h2 class="booking-step-title">Final Confirmation</h2>
                                    <p class="booking-step-subtitle">
                                        Confirm that the information is accurate and that you accept the clinic
                                        terms
                                        and privacy policy.
                                    </p>
                                </div>

                                <div class="section-card mb-2">
                                    <div class="flex items-start gap-2 mb-4">
                                        <i class="fa-solid fa-shield-halved text-[#8B0000] mt-0.5"></i>
                                        <p class="text-sm text-[#5c5550]">
                                            By submitting, you confirm that all the information provided is
                                            accurate
                                            and
                                            complete.
                                        </p>
                                    </div>
                                    <label
                                        class="confirm-checkbox-wrap flex items-start gap-3 p-4 rounded-xl border border-[#e8e2dd] bg-[#fafaf8] cursor-pointer">
                                        <input id="finalConfirm" type="checkbox"
                                            class="w-5 h-5 rounded border-2 border-[#e8e2dd] bg-white cursor-pointer flex-shrink-0 mt-0.5 accent-[#8B0000]"
                                            required>
                                        <span class="text-sm text-[#1a1410] leading-relaxed">
                                            I have reviewed my dental and medical information and I accept the
                                            <a href="/privacy-policy"
                                                class="text-[#8B0000] hover:underline font-semibold">Privacy
                                                Policy</a>
                                            and
                                            <a href="/terms-of-service"
                                                class="text-[#8B0000] hover:underline font-semibold">Terms of
                                                Service</a>.
                                        </span>
                                    </label>
                                </div>

                                <div class="flex justify-center gap-3 mt-8 nav-btns-row">
                                    <button type="button" id="confirmBackBtn"
                                        class="btn-secondary-custom inline-flex items-center gap-2 border border-[#e8e2dd] rounded-xl px-6 py-2.5 text-sm font-semibold text-[#5c5550] bg-transparent">
                                        <i class="fa-solid fa-chevron-left text-xs"></i> Back
                                    </button>
                                    <button type="button" id="finalSubmitBtn"
                                        class="btn-primary-custom inline-flex items-center gap-2 bg-[#8B0000] text-white rounded-xl px-6 py-2.5 text-sm font-bold">
                                        <i class="fa-solid fa-play"></i> Start Procedure
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="navBtns" class="flex justify-end mt-8 gap-3 nav-btns-row">
                        <button type="button" id="prevBtn" style="display:none;"
                            class="btn-secondary-custom inline-flex items-center gap-2 border border-[#e8e2dd] rounded-2xl px-6 py-3 text-sm font-semibold text-[#5c5550] bg-white shadow-sm">
                            <i class="fa-solid fa-chevron-left text-xs"></i> Previous
                        </button>

                        <button type="button" id="nextBtn"
                            class="btn-primary-custom inline-flex items-center gap-2 bg-[#8B0000] text-white rounded-2xl px-8 py-3 text-sm font-bold shadow-[0_10px_24px_rgba(139,0,0,0.18)]">
                            Next <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="miniTab" class="mini-tab">
            <i class="fa-solid fa-circle-exclamation text-red-400" aria-hidden="true"></i>
            <span id="miniTabText">Please complete all required fields.</span>
        </div>

        <dialog id="introBookingModal" class="m-auto w-[calc(100vw-1.5rem)] max-w-[620px]">
            <div class="intro-booking-modal-panel">
                <div class="intro-booking-modal-hero">
                    <p class="intro-booking-modal-eyebrow">PUP Taguig Dental Clinic</p>
                    <h2 class="intro-booking-modal-title">Let’s get your dental appointment ready.</h2>
                    <p class="intro-booking-modal-subtitle">
                        This form will help the clinic prepare safe and proper care for your visit.
                    </p>
                </div>

                <div class="intro-booking-modal-body">
                    <div class="intro-step-strip">
                        <div class="intro-step-pill">1<span>Patient</span></div>
                        <div class="intro-step-pill">2<span>Service</span></div>
                        <div class="intro-step-pill">3<span>Dental</span></div>
                        <div class="intro-step-pill">4<span>Medical</span></div>
                        <div class="intro-step-pill">5<span>Start</span></div>
                    </div>

                    <div class="intro-checklist">
                        <div class="intro-check-item">
                            <div class="intro-check-icon"><i class="fa-solid fa-check"></i></div>
                            <p>Fill out all required fields marked with <b>*</b>.</p>
                        </div>
                        <div class="intro-check-item">
                            <div class="intro-check-icon"><i class="fa-solid fa-phone-volume"></i></div>
                            <p>Prepare your emergency contact information.</p>
                        </div>
                        <div class="intro-check-item">
                            <div class="intro-check-icon"><i class="fa-solid fa-signature"></i></div>
                            <p>Prepare your patient signature image or draw it directly in the form.</p>
                        </div>
                        <div class="intro-check-item">
                            <div class="intro-check-icon"><i class="fa-solid fa-list-check"></i></div>
                            <p>Review your information before submitting.</p>
                        </div>
                    </div>

                    <div class="intro-modal-actions">
                        <button type="button" id="introStartBtn" class="intro-action-btn intro-action-primary">
                            <i class="fa-solid fa-play"></i>
                            <span>Start Booking</span>
                        </button>

                        <button type="button" id="introContinueDraftBtn"
                            class="hidden intro-action-btn intro-action-secondary">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Continue Draft</span>
                        </button>

                        <a href="{{ route('dentist.dentist.appointments') }}"
                            class="intro-action-btn intro-action-muted">
                            <i class="fa-solid fa-house"></i>
                            <span>Back Home</span>
                        </a>
                    </div>
                </div>
            </div>
        </dialog>

        <dialog id="confirmModal"
            class="border-0 p-0 rounded-2xl overflow-hidden shadow-[0_25px_60px_rgba(0,0,0,0.25)] max-w-[480px] w-[calc(100vw-2rem)]">
            <div class="bg-[#8B0000] px-8 py-10 text-center">
                <div
                    class="confirm-modal-icon w-16 h-16 rounded-full bg-white/15 flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-calendar-check text-white text-2xl"></i>
                </div>

                <h2 class="confirm-modal-title text-2xl font-extrabold text-white mb-4">
                    Appointment Confirmed!
                </h2>

                <p id="confirmMessage" class="confirm-modal-message text-white/85 text-sm leading-7 mb-6"></p>

                <button type="button" id="okBtn"
                    class="confirm-modal-button bg-white text-[#8B0000] border-0 px-8 py-2.5 rounded-xl font-bold text-sm cursor-pointer transition hover:scale-[1.03] hover:shadow-lg active:scale-[0.98]">
                    Back to Appointments
                </button>
            </div>
        </dialog>

        <dialog id="leaveModal"
            class="m-auto border-0 p-0 rounded-2xl overflow-hidden shadow-[0_25px_60px_rgba(0,0,0,0.25)] max-w-[440px] w-[calc(100vw-2rem)]">
            <div class="bg-[#8B0000] px-6 py-5">
                <h3 class="text-lg font-bold text-white mb-0.5">Leave this page?</h3>
                <p class="text-sm text-white/80">Your appointment form has unsaved changes. You can save your draft,
                    discard it, or continue editing.</p>
            </div>
            <div class="bg-white px-6 py-5 flex justify-end gap-3 flex-wrap">
                <button type="button" id="cancelLeaveBtn"
                    class="btn-secondary-custom inline-flex items-center gap-2 border border-[#e8e2dd] rounded-xl px-4 py-2 text-sm font-semibold text-[#5c5550] bg-transparent">
                    Cancel
                </button>
                <button type="button" id="discardDraftBtn"
                    class="btn-secondary-custom inline-flex items-center gap-2 border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 rounded-xl px-4 py-2 text-sm font-semibold transition-colors">
                    Discard
                </button>
                <button type="button" id="saveDraftBtn"
                    class="btn-primary-custom inline-flex items-center gap-2 bg-[#8B0000] text-white rounded-xl px-5 py-2 text-sm font-bold">
                    Save Draft
                </button>
            </div>
        </dialog>
        </div>
        </div>
    </main>

@endsection

@section('scripts')
    @include('partials.voice-logic')

    <script>
        const diseaseLabelByCode = @json($diseases->pluck('label', 'code'));
        const isFemalePatient = @json($isFemalePatient);
        const DRAFT_KEY = "dentistWalkInDraft:v1";

        const appointmentCountsPerDay = @json($appointmentCountsPerDay ?? []);
        const MAX_SLOTS_PER_DAY = 5;

        let selectedWalkInPatient = null;

        const accountTabs = document.querySelectorAll(".account-tab");
        const existingPanel = document.getElementById("existingPanel");
        const guestPanel = document.getElementById("guestPanel");

        const patientSearch = document.getElementById("patientSearch");
        const patientResults = document.getElementById("patientResults");

        const selectedPatientId = document.getElementById("selectedPatientId");
        const patientModeInput = document.getElementById("patientMode");
        const selectedPatientBox = document.getElementById("selectedPatientBox");
        const selectedPatientName = document.getElementById("selectedPatientName");
        const selectedPatientMeta = document.getElementById("selectedPatientMeta");

        const createGuestBtn = document.getElementById("createGuestBtn");
        const guestName = document.getElementById("guestName");
        const guestEmail = document.getElementById("guestEmail");
        const guestPhone = document.getElementById("guestPhone");

        function setGuestFieldsEnabled(enabled) {
            [guestName, guestEmail, guestPhone].forEach(input => {
                if (!input) return;

                input.disabled = !enabled;

                if (!enabled) {
                    input.required = false;
                    input.value = "";
                    input.classList.remove("input-invalid", "input-valid", "border-red-500");
                }
            });

            if (guestName) {
                guestName.required = enabled;
            }
        }

        function clearSelectedPatientUI() {
            selectedWalkInPatient = null;

            if (selectedPatientId) {
                selectedPatientId.value = "";
            }

            if (selectedPatientName) {
                selectedPatientName.textContent = "";
            }

            if (selectedPatientMeta) {
                selectedPatientMeta.textContent = "";
            }

            selectedPatientBox?.setAttribute("hidden", "hidden");

            document.querySelectorAll(".patient-result").forEach(result => {
                result.classList.remove("selected");

                const pill = result.querySelector(".patient-select-pill");
                if (pill) {
                    pill.innerHTML = `<i class="fa-solid fa-plus"></i> Select`;
                }
            });
        }

        function setPatientMode(mode, shouldClearSelected = false) {
            const normalizedMode = mode === "guest" ? "guest" : "existing";
            const isGuest = normalizedMode === "guest";

            if (patientModeInput) {
                patientModeInput.value = normalizedMode;
            }

            accountTabs.forEach(item => {
                item.classList.toggle("active", item.dataset.tab === normalizedMode);
            });

            existingPanel?.classList.toggle("active", !isGuest);
            guestPanel?.classList.toggle("active", isGuest);

            setGuestFieldsEnabled(isGuest);

            if (isGuest && shouldClearSelected) {
                clearSelectedPatientUI();
            }

            if (!isGuest) {
                [guestName, guestEmail, guestPhone].forEach(input => {
                    if (input) input.value = "";
                });
            }
        }

        function selectWalkInPatient(patient) {
            selectedWalkInPatient = {
                ...patient,
                mode: "existing",
            };

            setPatientMode("existing", false);

            if (selectedPatientId) {
                selectedPatientId.value = patient.id;
            }

            if (selectedPatientName) {
                selectedPatientName.textContent = patient.name || "Unnamed Patient";
            }

            if (selectedPatientMeta) {
                selectedPatientMeta.textContent =
                    `${patient.type || "Patient"}${patient.email ? " • " + patient.email : ""}`;
            }

            selectedPatientBox?.removeAttribute("hidden");

            if (patientSearch) {
                patientSearch.value = patient.name || "";
            }

            [guestName, guestEmail, guestPhone].forEach(input => {
                if (input) input.value = "";
            });

            markFormDirty();

            showMiniTab("Existing patient selected. Proceeding to service selection...");

            setTimeout(() => {
                if (typeof showStep === "function" && step === 0) {
                    if (!completedSteps.includes(0)) {
                        completedSteps.push(0);
                    }

                    showStep(1);
                }
            }, 350);
        }

        function selectGuestPatient(shouldProceed = true) {
            const name = guestName?.value?.trim() || "";
            const email = guestEmail?.value?.trim() || "";
            const phone = guestPhone?.value?.trim() || "";

            if (!name) {
                showMiniTab("Please enter the guest full name.");
                guestName?.classList.add("input-invalid", "border-red-500");
                guestName?.focus();
                return false;
            }

            guestName?.classList.remove("input-invalid", "border-red-500");

            selectedWalkInPatient = {
                id: null,
                mode: "guest",
                name,
                email,
                phone,
                type: "Guest Patient",
            };

            if (selectedPatientId) {
                selectedPatientId.value = "";
            }

            if (patientModeInput) {
                patientModeInput.value = "guest";
            }

            if (selectedPatientName) {
                selectedPatientName.textContent = name;
            }

            if (selectedPatientMeta) {
                const metaParts = ["Guest Patient", email, phone].filter(Boolean);
                selectedPatientMeta.textContent = metaParts.join(" • ");
            }

            selectedPatientBox?.removeAttribute("hidden");
            markFormDirty();

            if (shouldProceed) {
                showMiniTab("Guest patient details saved. Proceeding to service selection...");

                setTimeout(() => {
                    if (typeof showStep === "function" && step === 0) {
                        if (!completedSteps.includes(0)) {
                            completedSteps.push(0);
                        }

                        showStep(1);
                    }
                }, 350);
            }

            return true;
        }

        setPatientMode("existing");

        accountTabs.forEach(tab => {
            tab.addEventListener("click", function() {
                setPatientMode(this.dataset.tab, true);
                markFormDirty();
            });
        });

        createGuestBtn?.addEventListener("click", () => {
            setPatientMode("guest", false);
            selectGuestPatient(true);
        });

        let patientSearchTimer = null;
        let patientSearchLoadedOnFocus = false;

        function renderPatientMessage(message) {
            if (!patientResults) return;

            patientResults.innerHTML = `
        <div class="empty-state">
            ${message}
        </div>
    `;
        }

  function getPatientInitials(name) {
    const parts = String(name || "Patient")
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (!parts.length) return "P";

    return parts
        .slice(0, 2)
        .map(part => part.charAt(0).toUpperCase())
        .join("");
}

function safePatientText(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function renderPatients(patients) {
    if (!patientResults) return;

    if (!patients.length) {
        renderPatientMessage("No patient found.");
        return;
    }

    patientResults.innerHTML = "";

    patients.forEach(patient => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "patient-result";
        item.dataset.patientId = patient.id;

        const patientName = patient.name || "Unnamed Patient";
        const patientType = patient.type || "Patient";
        const patientEmail = patient.email || "";
        const studentNumber = patient.student_number || "";
        const program = patient.program || "";

        const metaParts = [
            patientType,
            studentNumber,
            program,
            patientEmail,
        ].filter(Boolean);

        item.innerHTML = `
            <span class="patient-avatar">${safePatientText(getPatientInitials(patientName))}</span>

            <span class="patient-result-main">
                <strong class="patient-result-name">${safePatientText(patientName)}</strong>
                <small class="patient-result-meta">${safePatientText(metaParts.join(" • "))}</small>
            </span>

            <span class="patient-select-pill">
                <i class="fa-solid fa-plus"></i>
                Select
            </span>
        `;

        item.addEventListener("click", function() {
            selectWalkInPatient(patient);

            document.querySelectorAll(".patient-result").forEach(result => {
                result.classList.remove("selected");

                const pill = result.querySelector(".patient-select-pill");
                if (pill) {
                    pill.innerHTML = `<i class="fa-solid fa-plus"></i> Select`;
                }
            });

            item.classList.add("selected");

            const pill = item.querySelector(".patient-select-pill");
            if (pill) {
                pill.innerHTML = `<i class="fa-solid fa-check"></i> Selected`;
            }
        });

        patientResults.appendChild(item);
    });
}

        async function loadPatients(query = "", showAll = false) {
            if (!patientResults) return;

            renderPatientMessage("Loading patient records...");

            try {
                const params = new URLSearchParams();

                if (query) {
                    params.set("q", query);
                }

                if (showAll) {
                    params.set("show_all", "1");
                }

                params.set("limit", "30");

                const response = await fetch(`{{ route('dentist.walk-in.search-patient') }}?${params.toString()}`, {
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                if (!response.ok) {
                    throw new Error(`Patient search failed. Status: ${response.status}`);
                }

                const patients = await response.json();
                renderPatients(Array.isArray(patients) ? patients : []);
            } catch (error) {
                renderPatientMessage("Unable to load patient records. Please try again.");
            }
        }

        patientSearch?.addEventListener("focus", function() {
            const query = this.value.trim();

            if (!query && !patientSearchLoadedOnFocus) {
                patientSearchLoadedOnFocus = true;
                loadPatients("", true);
            }
        });

        patientSearch?.addEventListener("click", function() {
            const query = this.value.trim();

            if (!query && !patientSearchLoadedOnFocus) {
                patientSearchLoadedOnFocus = true;
                loadPatients("", true);
            }
        });

        patientSearch?.addEventListener("input", function() {
            clearTimeout(patientSearchTimer);

            const query = this.value.trim();

            patientSearchTimer = setTimeout(function() {
                if (!query) {
                    loadPatients("", true);
                    return;
                }

                loadPatients(query, false);
            }, 350);
        });

        function hasSavedDraft() {
            const raw = localStorage.getItem(DRAFT_KEY);
            if (!raw) return false;

            try {
                const parsed = JSON.parse(raw);
                const values = Object.entries(parsed).filter(([key, value]) => {
                    if (key === "__meta") return false;
                    if (value === null || value === undefined) return false;
                    return String(value).trim() !== "";
                });

                return values.length >= 2;
            } catch {
                return false;
            }
        }

        function saveDraftData() {
            const form = document.getElementById("appointmentForm");
            if (!form) return;
            const data = new FormData(form),
                obj = {};
            for (const [key, value] of data.entries()) {
                if (key === "patient_signature") continue;
                if (obj[key] === undefined) obj[key] = value;
                else if (Array.isArray(obj[key])) obj[key].push(value);
                else obj[key] = [obj[key], value];
            }

            const draftFields = Object.keys(obj).filter(key => key !== "__meta");

            if (!draftFields.length) {
                clearDraft();
                return;
            }

            obj.__meta = {
                step: typeof step !== "undefined" ? step : 0,
                savedAt: new Date().toISOString(),
                fieldCount: draftFields.length
            };
            localStorage.setItem(DRAFT_KEY, JSON.stringify(obj));
        }

        function clearDraft() {
            localStorage.removeItem(DRAFT_KEY);
        }

        document.querySelectorAll('input[name="service_type"]').forEach(radio => {
            radio.addEventListener("change", markFormDirty);
        });

        async function restoreDraft() {
            const raw = localStorage.getItem(DRAFT_KEY);
            if (!raw) return;
            let obj;
            try {
                obj = JSON.parse(raw);
            } catch {
                return;
            }
            const form = document.getElementById("appointmentForm");
            if (!form) return;
            Object.keys(obj).forEach((name) => {
                if (name === "__meta") return;
                const value = obj[name];
                if (Array.isArray(value)) {
                    form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach((el) => {
                        if (el.type === "checkbox") el.checked = value.includes(el.value);
                    });
                    return;
                }
                form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach((el) => {
                    if (el.type === "radio") el.checked = (el.value === value);
                    else if (el.type === "checkbox") el.checked = (value === true || value === "on" ||
                        value === el.value);
                    else el.value = value;
                });
            });
            if (document.getElementById("emergency_relation")?.value === "Others") {
                const other = document.getElementById("relation_other");
                if (other) {
                    other.classList.remove("hidden");
                    other.setAttribute("required", "true");
                }
            }
            const restoredDate = document.getElementById("appointment_date")?.value;
            const restoredTime = document.getElementById("appointment_time")?.value;
            if (restoredDate) {
                const appointmentCount = appointmentCountsPerDay[restoredDate] || 0;
                if (appointmentCount >= MAX_SLOTS_PER_DAY) {
                    showSlotFullWarning(restoredDate);
                } else {
                    await selectDate(restoredDate);
                    if (restoredTime) {
                        const restoredTimeChip = Array.from(document.querySelectorAll(".slot-chip")).find(c =>
                            c.dataset.time === restoredTime && !c.classList.contains("disabled")
                        );

                        if (restoredTimeChip) {
                            restoredTimeChip.click();
                        } else {
                            const timeInput = document.getElementById("appointment_time");
                            if (timeInput) timeInput.value = "";
                        }
                    }
                }
            }
            const restoredMode = patientModeInput?.value || "existing";
            setPatientMode(restoredMode, false);

            if (restoredMode === "guest" && guestName?.value?.trim()) {
                selectGuestPatient(false);
            }

            formIsDirty = true;
        }

        const miniTab = document.getElementById("miniTab");
        const miniTabText = document.getElementById("miniTabText");

        function showMiniTab(msg) {
            if (!miniTab) return;
            miniTabText.textContent = msg || "Please complete all required fields.";
            miniTab.style.opacity = "1";
            miniTab.style.pointerEvents = "auto";
            miniTab.classList.add("show");

            clearTimeout(window.__mtTimer);
            window.__mtTimer = setTimeout(() => {
                miniTab.style.opacity = "0";
                miniTab.style.pointerEvents = "none";
                miniTab.classList.remove("show");
            }, 3200);
        }

        function showInputError(input) {
            if (!input) return;
            input.classList.add("border-red-500");
            input.style.animation = "shake 0.3s ease";
            setTimeout(() => input.style.animation = "", 400);
        }

        let step = 0,
            completedSteps = [];
        const steps = document.querySelectorAll(".step-content");
        const navBtns = document.getElementById("navBtns");
        const prevBtn = document.getElementById("prevBtn");
        const nextBtn = document.getElementById("nextBtn");
        const summarySection = document.getElementById("summarySection");
        const confirmationSection = document.getElementById("confirmationSection");

        function updateStepperUI(i) {
            for (let idx = 0; idx < 5; idx++) {
                const circle = document.getElementById(`sc${idx + 1}`);
                const label = document.getElementById(`sl${idx + 1}`);
                const conn = document.getElementById(`conn${idx + 1}`);
                if (!circle || !label) continue;
                circle.className =
                    "step-circle w-10 h-10 rounded-full border-2 flex items-center justify-center text-sm font-bold";
                label.className =
                    "step-label text-[0.65rem] font-semibold uppercase tracking-wide text-center block w-full mt-4";
                if (idx < i && completedSteps.includes(idx)) {
                    circle.className += " border-green-700 bg-green-700 text-white";
                    label.className += " text-green-700";
                    circle.innerHTML = `<i class="fa-solid fa-check text-xs"></i>`;
                } else if (idx === i) {
                    circle.className +=
                        " border-blue-600 bg-blue-600 text-white shadow-[0_0_0_6px_rgba(37,99,235,0.12)] scale-110";
                    label.className += " text-blue-600";
                    circle.innerHTML = String(idx + 1);
                } else {
                    circle.className += " border-[#e8e2dd] bg-white text-[#9e9690]";
                    label.className += " text-[#9e9690]";
                    circle.innerHTML = String(idx + 1);
                }
                if (conn) {
                    conn.className = "h-0.5 flex-shrink-0 self-start step-connector ";
                    conn.style.width = window.innerWidth < 640 ? "8px" : "40px";
                    conn.style.marginTop = "20px";
                    conn.className += (idx < i && completedSteps.includes(idx)) ? "bg-green-700" : (idx === i ?
                        "bg-blue-600" : "bg-[#e8e2dd]");
                }
            }
            const fill = document.getElementById("headerProgressFill");
            if (fill) fill.style.width = (((i + 1) / 5) * 100) + "%";
            const counter = document.getElementById("stepCounterText");
            if (counter) counter.textContent = i + 1;
        }

        function showStep(i) {
            steps.forEach((s, idx) => {
                s.classList.remove("show");
                s.classList.add("hidden");
                if (idx === i) {
                    s.classList.remove("hidden");
                    setTimeout(() => s.classList.add("show"), 40);
                }
            });
            const isLast = i === steps.length - 1;
            navBtns.style.display = isLast ? "none" : "flex";
            prevBtn.style.display = i === 0 ? "none" : "inline-flex";
            nextBtn.style.display = isLast ? "none" : "inline-flex";
            if (isLast) {
                buildSummary();
                resetStep5View();
            }
            updateStepperUI(i);
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
            if (i === 3 && typeof resizeSignatureCanvas === "function") {
                setTimeout(resizeSignatureCanvas, 120);
            }
            step = i;
        }

        function resetStep5View() {
            summarySection?.classList.remove("hidden");
            confirmationSection?.classList.add("hidden");
        }

        function scrollToInvalidTarget(target) {
            if (!target) return;

            const block =
                target.closest('.grid') ||
                target.closest('.ml-6') ||
                target.closest('.section-card') ||
                target.closest('.voice-input-wrap') ||
                target.closest('.date-input-wrap') ||
                target;

            block.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            if (typeof target.focus === "function" && !target.hasAttribute("readonly")) {
                setTimeout(() => target.focus(), 250);
            }
        }

        function isStepComplete(s) {


            const stepEl = steps[s];
            if (!stepEl) return true;

            if (s === 0) {
                const mode = patientModeInput?.value || "existing";
                const selectedPatientInput = document.getElementById("selectedPatientId");

                if (mode === "guest") {
                    if (!selectGuestPatient(false)) {
                        scrollToInvalidTarget(guestName || document.getElementById("patientAccountSection"));
                        return false;
                    }
                } else if (!selectedPatientInput?.value) {
                    showMiniTab("Please select an existing patient first, or use Guest Onboarding.");
                    scrollToInvalidTarget(document.getElementById("patientAccountSection"));
                    return false;
                }
            }

            if (s === 1) {
                const selectedService = stepEl.querySelector('input[name="service_type"]:checked');

                if (!selectedService) {
                    showMiniTab("Please select a service type.");
                    scrollToInvalidTarget(stepEl.querySelector(".service-step-grid"));
                    return false;
                }
            }

            const fields = stepEl.querySelectorAll(
                "input[required]:not([type='radio']):not([type='checkbox']):not([type='hidden']):not([type='file']), select[required], textarea[required]"
            );

            for (const input of fields) {
                if (!input.value || !input.value.trim()) {
                    scrollToInvalidTarget(input);
                    return false;
                }
            }

            const radios = stepEl.querySelectorAll("input[type='radio']");
            if (radios.length) {
                const groups = [...new Set([...radios].map(r => r.name))];

                for (const name of groups) {
                    const checked = stepEl.querySelector(`input[name="${name}"]:checked`);
                    if (!checked) {
                        const firstRadio = stepEl.querySelector(`input[name="${name}"]`);
                        scrollToInvalidTarget(firstRadio);
                        return false;
                    }
                }
            }

            const phone = stepEl.querySelector("#emergency_number");
            if (phone) {
                const digits = phone.value.replace(/\D/g, "");
                if (!/^09\d{9}$/.test(digits)) {
                    showMiniTab("Please enter a valid contact number.");
                    phone.classList.add("input-invalid");
                    setPhoneFeedback("Enter a valid mobile number in the format 09xx xxx xxxx.", "error");
                    scrollToInvalidTarget(phone);
                    return false;
                }
            }
            if (s === 3) {
                const signatureInput = document.getElementById("patient_signature");
                const signatureBlock = document.querySelector(".signature-section-card") || signatureInput;

                if (typeof isDrawnSignatureBlank === "function" && isDrawnSignatureBlank()) {
                    showMiniTab("Please draw the patient's signature first.");
                    scrollToInvalidTarget(signatureBlock);
                    return false;
                }

                if (!drawnSignatureWasUsed || !signatureInput?.files?.length) {
                    showMiniTab("Please click Use Drawn Signature before proceeding.");
                    scrollToInvalidTarget(signatureBlock);
                    return false;
                }
            }

            return true;
        }

        nextBtn?.addEventListener("click", () => {
            if (!isStepComplete(step)) {
                showMiniTab("Please complete all required fields before proceeding.");
                return;
            }
            if (!completedSteps.includes(step)) completedSteps.push(step);
            showStep(Math.min(step + 1, steps.length - 1));
        });
        prevBtn?.addEventListener("click", () => showStep(Math.max(step - 1, 0)));
        document.getElementById("summaryBackBtn")?.addEventListener("click", () => showStep(3));
        document.getElementById("goToConfirmationBtn")?.addEventListener("click", () => {
            summarySection?.classList.add("hidden");
            confirmationSection?.classList.remove("hidden");
            confirmationSection?.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        });
        document.getElementById("confirmBackBtn")?.addEventListener("click", () => {
            confirmationSection?.classList.add("hidden");
            summarySection?.classList.remove("hidden");
            summarySection?.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        });

        function setupCharLimit(inputId, counterId, max = 150, warningId = null) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            const warning = warningId ? document.getElementById(warningId) : null;

            if (!input || !counter) return;

            function updateUI() {
                let length = input.value.length;

                if (length > max) {
                    input.value = input.value.slice(0, max);
                    length = max;
                }

                counter.textContent = `${length}/${max}`;

                counter.classList.remove("text-red-600", "text-yellow-500");
                input.classList.remove("border-red-500", "ring-1", "ring-red-400");

                if (warning) warning.classList.add("hidden");

                if (length >= max) {
                    counter.classList.add("text-red-600");
                    input.classList.add("border-red-500", "ring-1", "ring-red-400");
                    if (warning) warning.classList.remove("hidden");
                } else if (length >= max - 10) {
                    counter.classList.add("text-yellow-500");
                }
            }

            input.addEventListener("input", updateUI);

            updateUI();
        }

        window.addEventListener("beforeunload", (e) => {
            if (formIsDirty && !formSubmitting) {
                e.preventDefault();
                e.returnValue = "";
            }
        });

        function markFormDirty() {
            formIsDirty = true;
        }

        setupCharLimit("additional_concerns", "concernCount", 150);

        function buildSummary() {
            const form = document.getElementById("appointmentForm");
            if (!form) return;

            const data = new FormData(form);
            const get = n => data.get(n) || "N/A";
            const getAll = n => data.getAll(n);

            const emergencyRelation = data.get("emergency_relation") || "N/A";

            const sigFile = data.get("patient_signature");
            let sigHTML = `<span class="text-[#9e9690] italic">Not uploaded</span>`;
            if (sigFile && sigFile.size > 0) {
                const url = URL.createObjectURL(sigFile);
                sigHTML = `
        <div class="space-y-2">
            <p><b class="text-[#5c5550] font-semibold">File:</b> ${sigFile.name}</p>
            <p class="text-emerald-700 font-semibold">
                <i class="fa-solid fa-circle-check mr-1"></i> Signature uploaded
            </p>
            <img src="${url}" alt="Signature" class="max-w-[220px] max-h-[130px] rounded-lg border border-[#e8e2dd]">
        </div>
    `;
            }

            const row = (label, val) =>
                `<p><b class="text-[#5c5550] font-semibold">${label}:</b> ${val && String(val).trim() !== "" ? val : '<span class="text-[#9e9690]">N/A</span>'}</p>`;

            const optionalRow = (label, val) => {
                if (!val || String(val).trim() === "" || val === "N/A") return "";
                return `<p><b class="text-[#5c5550] font-semibold">${label}:</b> ${val}</p>`;
            };

            const summaryCard = (title, icon, body) => `
                <div class="border border-[#e8e2dd] rounded-xl overflow-hidden bg-white">
                    <div class="bg-[#f9e8e8] px-4 py-2.5 text-xs font-bold text-[#8B0000] uppercase tracking-widest border-b border-[#e8e2dd]">
                        <i class="fa-solid ${icon} mr-2"></i>${title}
                    </div>
                    <div class="p-4 text-sm leading-7 text-[#1a1410] space-y-4">${body}</div>
                </div>
            `;

            const subSection = (title, body) => `
                <div class="rounded-xl border border-[#f1e8e3] bg-[#fffdfd] overflow-hidden">
                    <div class="px-4 py-2.5 bg-[#fff7f6] border-b border-[#f1e8e3] text-[0.72rem] font-extrabold uppercase tracking-[0.16em] text-[#8B0000]">
                        ${title}
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-1 sm-grid-1col">
                            ${body}
                        </div>
                    </div>
                </div>
            `;

            const fullWidthSection = (title, body) => `
                <div class="rounded-xl border border-[#f1e8e3] bg-[#fffdfd] overflow-hidden">
                    <div class="px-4 py-2.5 bg-[#fff7f6] border-b border-[#f1e8e3] text-[0.72rem] font-extrabold uppercase tracking-[0.16em] text-[#8B0000]">
                        ${title}
                    </div>
                    <div class="p-4 text-sm leading-7 text-[#1a1410]">
                        ${body}
                    </div>
                </div>
            `;

            const diseases = getAll("diseases[]");
            const diseaseText = diseases.length ?
                diseases.map(code => diseaseLabelByCode?.[code] ?? code).join(", ") :
                "None";

            const patientName = selectedWalkInPatient?.name || document.getElementById("selectedPatientName")
                ?.textContent || "N/A";
            const patientGender = selectedWalkInPatient?.gender || selectedWalkInPatient?.type || "N/A";
            const dentalHistoryBody = `
    ${subSection("Basic Info", `
                                                                                                                                                                                ${row("Last Dental Visit", get("last_dental_visit"))}
                                                                                                                                                                                ${row("Previous Dentist", get("previous_dentist"))}
                                                                                                                                                                            `)}

    ${subSection("Dental Symptoms", `
                                                                                                                                                                                ${row("Bleeding Gums", get("bleeding_gums"))}
                                                                                                                                                                                ${row("Sensitive (Hot/Cold)", get("sensitive_temp"))}
                                                                                                                                                                                ${row("Sensitive (Sweets/Sour)", get("sensitive_taste"))}
                                                                                                                                                                                ${row("Tooth Pain", get("tooth_pain"))}
                                                                                                                                                                                ${row("Sores/Lumps", get("sores"))}
                                                                                                                                                                                ${row("Jaw Injuries", get("injuries"))}
                                                                                                                                                                            `)}

    ${subSection("Jaw & Bite Symptoms", `
                                                                                                                                                                                ${row("Clicking", get("clicking"))}
                                                                                                                                                                                ${row("Joint Pain", get("joint_pain"))}
                                                                                                                                                                                ${row("Difficulty Moving", get("difficulty_moving"))}
                                                                                                                                                                                ${row("Difficulty Chewing", get("difficulty_chewing"))}
                                                                                                                                                                                ${row("Frequent Headaches", get("jaw_headaches"))}
                                                                                                                                                                                ${row("Grinding/Clenching", get("clench_grind"))}
                                                                                                                                                                                ${row("Lips/Cheek Biting", get("biting"))}
                                                                                                                                                                                ${row("Teeth Loosening", get("teeth_loosening"))}
                                                                                                                                                                                ${row("Food Caught Between Teeth", get("food_teeth"))}
                                                                                                                                                                                ${row("Medicine Reaction", get("med_reaction"))}
                                                                                                                                                                            `)}

    ${subSection("Dental Procedures", `
                                                                                                                                                                                ${row("Periodontal Treatment", get("periodontal"))}
                                                                                                                                                                                ${row("Difficult Extraction", get("difficult_extraction"))}
                                                                                                                                                                                ${get("difficult_extraction") === "YES" ? row("Extraction Date", get("extraction_date")) : ""}

                                                                                                                                                                                ${row("Prolonged Bleeding", get("prolonged_bleeding"))}
                                                                                                                                                                                ${row("Dentures", get("dentures"))}
                                                                                                                                                                                ${get("dentures") === "YES" ? row("Dentures Placement Date", get("dentures_date")) : ""}

                                                                                                                                                                                ${row("Orthodontic Treatment", get("ortho_treatment"))}
                                                                                                                                                                                ${get("ortho_treatment") === "YES" ? row("Orthodontic Completion Date", get("ortho_date")) : ""}
                                                                                                                                                                            `)}

    ${fullWidthSection("Additional Concerns", `
                                                                                                                                                                                ${get("additional_concerns") !== "N/A" && String(get("additional_concerns")).trim() !== ""
                                                                                                                                                                        ? get("additional_concerns")
                                                                                                                                                                        : '<span class="text-[#9e9690] italic">No additional concerns provided.</span>'}
                                                                                                                                                                            `)}
`;

            const medicalHistoryBody = `
    ${subSection("General Health", `
                                                                                                                                                                            ${row("Good Health", get("good_health"))}
                                                                                                                                                                            ${get("good_health") === "NO" ? row("Health Details", get("good_health_details")) : ""}

                                                                                                                                                                            ${row("Had Medical Exam", get("had_medical_exam"))}
                                                                                                                                                                            ${get("had_medical_exam") === "YES" ? row("Medical Exam Date", get("medical_exam_date")) : ""}

                                                                                                                                                                            ${row("Under Treatment", get("under_treatment"))}
                                                                                                                                                                            ${get("under_treatment") === "YES" ? row("Treatment Details", get("treatment_details")) : ""}

                                                                                                                                                                            ${row("Hospitalized", get("hospitalized"))}
                                                                                                                                                                            ${get("hospitalized") === "YES" ? row("Hospital Details", get("hospital_details")) : ""}
                                                                                                                                                                        `)}

    ${subSection("Allergies", `
                                                                                                                                                                            ${row("Allergy (Medicine)", get("allergy_medicine"))}
                                                                                                                                                                            ${row("Allergy (Food)", get("allergy_food"))}
                                                                                                                                                                            ${optionalRow("Allergy (Others)", get("allergy_others"))}
                                                                                                                                                                        `)}

    ${subSection("Medications", `
                                                                                                                                                                            ${row("Medication", get("medication"))}
                                                                                                                                                                            ${get("medication") === "YES" ? row("Medication Details", get("medication_details")) : ""}
                                                                                                                                                                        `)}

    ${isFemalePatient ? subSection("For Women Only", `
                                                                                                                                                                            ${row("Pregnant", get("pregnant"))}
                                                                                                                                                                            ${row("Nursing", get("nursing"))}
                                                                                                                                                                            ${row("Birth Control Pills", get("birth_control"))}
                                                                                                                                                                        `) : ""}

    ${fullWidthSection("Medical Conditions", `
                                                                                                                                                                            <b class="text-[#5c5550] dark:text-[#e5e5e5] font-semibold">Selected Conditions:</b> ${diseaseText}
                                                                                                                                                                        `)}

    ${subSection("Tobacco Use", `
                                                                                                                                                                            ${row("Tobacco Use", get("tobacco_use"))}
                                                                                                                                                                            ${get("tobacco_use") === "YES" ? row("Amount Per Day", get("tobacco_per_day")) : ""}
                                                                                                                                                                            ${get("tobacco_use") === "YES" ? row("Amount Per Week", get("tobacco_per_week")) : ""}
                                                                                                                                                                        `)}

    ${subSection("Do You Suffer From", `
                                                                                                                                                                            ${row("Headaches", get("headaches"))}
                                                                                                                                                                            ${row("Earaches", get("earaches"))}
                                                                                                                                                                            ${row("Neck Aches", get("neck_aches"))}
                                                                                                                                                                        `)}
`;

            document.getElementById("summaryBox").innerHTML = `
    ${summaryCard("Patient Information", "fa-user", `
                                                                                                                                                                            <div class="grid grid-cols-1 gap-y-1">
                                                                                                                                                                                ${row("Name", patientName)}
                                                                                                                                                                                ${row("Gender", patientGender)}
                                                                                                                                                                            </div>
                                                                                                                                                                        `)}

    <div class="grid grid-cols-2 gap-4 sm-grid-1col">
     ${summaryCard("Walk-in Schedule", "fa-clock", `
                <div class="grid grid-cols-1 gap-y-1">
                    <p><b class="text-[#5c5550] font-semibold">Date & Time:</b> Recorded automatically when Start Procedure is clicked.</p>
                </div>
            `)}

        ${summaryCard("Service", "fa-tooth", `
                                                                                                                                                                                <div class="grid grid-cols-1 gap-y-1">
                                                                                                                                                                                    ${row("Type", get("service_type"))}
                                                                                                                                                                                </div>
                                                                                                                                                                            `)}
    </div>

    ${summaryCard("Dental History", "fa-teeth", dentalHistoryBody)}

    ${summaryCard("Medical History", "fa-heart-pulse", medicalHistoryBody)}

    <div class="grid grid-cols-2 gap-4 sm-grid-1col">
        ${summaryCard("Emergency Contact", "fa-phone", `
                                                                                                                                                                                <div class="grid grid-cols-1 gap-y-1">
                                                                                                                                                                                    ${row("Name", get("emergency_person"))}
                                                                                                                                                                                    ${row("Number", get("emergency_number"))}
                                                                                                                                                                                    ${row("Relation", emergencyRelation)}
                                                                                                                                                                                </div>
                                                                                                                                                                            `)}

        ${summaryCard("Signature", "fa-signature", sigHTML)}
    </div>
`;

            document.querySelectorAll(".sm-grid-1col").forEach(el => {
                if (window.innerWidth < 640) el.style.gridTemplateColumns = "1fr";
            });
        }

        const confirmModal = document.getElementById("confirmModal");
        const confirmMessage = document.getElementById("confirmMessage");
        const okBtn = document.getElementById("okBtn");

        function showSlotFullWarning(date) {
            const slotFullModal = document.getElementById("slotFullModal");
            if (!slotFullModal) return;

            const dateDisplay = new Date(date + "T00:00:00").toLocaleDateString("en-US", {
                weekday: "short",
                year: "numeric",
                month: "short",
                day: "numeric"
            });

            const msg = document.getElementById("slotFullMessage");
            if (msg) {
                msg.innerHTML =
                    `<strong>The appointment slots for ${dateDisplay} are fully booked.</strong><br>Please select a different date to continue with your booking.`;
            }

            slotFullModal.showModal();
        }

        const finalSubmitBtn = document.getElementById('finalSubmitBtn');
        const finalConfirm = document.getElementById('finalConfirm');

        function replayConfirmModalAnimation() {
            if (!confirmModal) return;

            confirmModal.classList.remove("confirm-modal-animate");
            void confirmModal.offsetWidth;
            confirmModal.classList.add("confirm-modal-animate");

            confirmModal.querySelectorAll(
                ".confirm-modal-icon, .confirm-modal-title, .confirm-modal-message, .confirm-modal-button"
            ).forEach(el => {
                el.style.animation = "none";
                void el.offsetWidth;
                el.style.animation = "";
            });
        }
        if (finalSubmitBtn) {
            finalSubmitBtn.addEventListener("click", () => {
                if (!finalConfirm || !finalConfirm.checked) {
                    showMiniTab("Please confirm before starting the procedure.");
                    return;
                }

                clearDraft();
                formSubmitting = true;
                document.getElementById("appointmentForm").submit();
            });
        }

        if (okBtn) {
            okBtn.addEventListener("click", () => {
                clearDraft();
                formSubmitting = true;
                document.getElementById("appointmentForm").submit();
            });
        }

        var html = document.documentElement;
        var themeToggleContainer = document.getElementById("themeToggle");

        [{
            name: "difficult_extraction",
            boxId: "extraction_date_box",
            showOn: "YES"
        }, {
            name: "dentures",
            boxId: "dentures_date_box",
            showOn: "YES"
        }, {
            name: "ortho_treatment",
            boxId: "ortho_date_box",
            showOn: "YES"
        }].forEach(({
            name,
            boxId,
            showOn
        }) => {
            const radios = document.getElementsByName(name);
            const box = document.getElementById(boxId);
            if (!box || !radios.length) return;
            const inp = box.querySelector("input");
            radios.forEach(r => r.addEventListener("change", () => {
                if (r.checked && r.value === showOn) {
                    box.classList.remove("hidden");
                    if (inp) inp.required = true;
                } else if (r.checked) {
                    box.classList.add("hidden");
                    if (inp) {
                        inp.required = false;
                        inp.value = "";
                    }
                }
            }));
        });

        function syncMedicalExamBox() {
            const sel = document.querySelector('input[name="had_medical_exam"]:checked');
            const box = document.getElementById("medical_exam_box");
            const inp = document.getElementById("medicalExamDate");
            if (!sel || !box || !inp) return;
            if (sel.value === "YES") {
                box.classList.remove("hidden");
                inp.required = true;
            } else {
                box.classList.add("hidden");
                inp.required = false;
                inp.value = "";
            }
        }
        document.querySelectorAll('input[name="had_medical_exam"]').forEach(r => r.addEventListener("change",
            syncMedicalExamBox));
        syncMedicalExamBox();
        document.getElementById("emergency_relation")
            ?.addEventListener("change", function() {
                const other = document.getElementById("relation_other");
                if (!other) return;
                if (this.value === "Others") {
                    other.classList.remove("hidden");
                    other.setAttribute("required", "true");
                } else {
                    other.classList.add("hidden");
                    other.removeAttribute("required");
                    other.value = "";
                }
            });
        [{
            name: "good_health",
            boxId: "good_health_box",
            showOn: "NO"
        }, {
            name: "under_treatment",
            boxId: "treatment_box",
            showOn: "YES"
        }, {
            name: "hospitalized",
            boxId: "hospital_box",
            showOn: "YES"
        }, {
            name: "medication",
            boxId: "medication_box",
            showOn: "YES"
        }].forEach(({
            name,
            boxId,
            showOn
        }) => {
            const radios = document.getElementsByName(name);
            const box = document.getElementById(boxId);
            if (!box || !radios.length) return;
            radios.forEach(r => r.addEventListener("change", () => {
                const sel = [...radios].find(x => x.checked);
                const inputs = box.querySelectorAll("input");
                if (sel?.value === showOn) {
                    box.classList.remove("hidden");
                    inputs.forEach(i => i.required = true);
                } else {
                    box.classList.add("hidden");
                    inputs.forEach(i => {
                        i.required = false;
                        i.value = "";
                    });
                }
            }));
        });
        [...document.getElementsByName("tobacco_use")].forEach(r => r.addEventListener("change", () => {
            if (r.checked && r.value === "YES") document.getElementById("tobacco_details")?.classList
                .remove(
                    "hidden");
            else document.getElementById("tobacco_details")?.classList.add("hidden");
        }));
        const sigInput = document.getElementById("patient_signature");
        const sigName = document.getElementById("signature_filename");
        const sigError = document.getElementById("signature_error");
        const sigResultBox = document.getElementById("signature_result_box");

        const signatureCanvas = document.getElementById("signatureCanvas");
        const signatureCtx = signatureCanvas?.getContext("2d");
        const signatureUndoBtn = document.getElementById("signatureUndoBtn");
        const signatureClearBtn = document.getElementById("signatureClearBtn");
        const signatureUseDrawnBtn = document.getElementById("signatureUseDrawnBtn");

        let drawnSignatureStrokes = [];
        let drawnSignatureCurrentStroke = [];
        let drawnSignatureIsDrawing = false;
        let drawnSignatureWasUsed = false;

        function escapeHtml(value) {
            return String(value || "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");
        }

        function clearSignatureDisplay() {
            sigResultBox?.classList.add("hidden");

            if (sigName) {
                sigName.textContent = "";
                sigName.classList.remove("text-emerald-700", "text-red-600", "text-[#5c5550]");
            }

            if (sigError) {
                sigError.innerHTML = "";
                sigError.classList.add("hidden");
            }
        }

        function showSignatureStatus(fileName = "", message = "", type = "neutral") {
            sigResultBox?.classList.remove("hidden");

            if (sigName) {
                sigName.textContent = fileName;
                sigName.classList.remove("text-emerald-700", "text-red-600", "text-[#5c5550]");

                if (type === "success") {
                    sigName.classList.add("text-emerald-700");
                } else if (type === "error") {
                    sigName.classList.add("text-red-600");
                } else {
                    sigName.classList.add("text-[#5c5550]");
                }
            }

            if (sigError) {
                let icon = `<i class="fa-solid fa-circle-info mr-1"></i>`;

                if (type === "success") {
                    icon = `<i class="fa-solid fa-circle-check mr-1"></i>`;
                    sigError.className =
                        "mt-1 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700 leading-5 font-semibold";
                } else if (type === "error") {
                    icon = `<i class="fa-solid fa-circle-exclamation mr-1"></i>`;
                    sigError.className =
                        "mt-1 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 leading-5 font-semibold";
                } else {
                    sigError.className =
                        "mt-1 rounded-xl border border-[#e8e2dd] bg-white px-3 py-2 text-xs text-[#5c5550] leading-5 font-semibold";
                }

                sigError.innerHTML = `${icon}${escapeHtml(message)}`;
                sigError.classList.remove("hidden");
            }
        }

        function resizeSignatureCanvas() {
            if (!signatureCanvas || !signatureCtx) return;

            const rect = signatureCanvas.getBoundingClientRect();
            if (!rect.width) return;

            const dpr = Math.max(window.devicePixelRatio || 1, 1);
            const cssWidth = rect.width;
            const cssHeight = signatureCanvas.offsetHeight || 220;

            signatureCanvas.width = Math.round(cssWidth * dpr);
            signatureCanvas.height = Math.round(cssHeight * dpr);
            signatureCtx.setTransform(dpr, 0, 0, dpr, 0, 0);

            redrawSignatureCanvas();
        }

        function paintSignatureCanvasBackground() {
            if (!signatureCanvas || !signatureCtx) return;

            signatureCtx.save();
            signatureCtx.setTransform(1, 0, 0, 1, 0, 0);
            signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            signatureCtx.fillStyle = "#ffffff";
            signatureCtx.fillRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            signatureCtx.restore();
        }

        function drawSignatureStroke(stroke) {
            if (!signatureCtx || !stroke || !stroke.length) return;

            signatureCtx.lineWidth = 3.2;
            signatureCtx.lineCap = "round";
            signatureCtx.lineJoin = "round";
            signatureCtx.strokeStyle = "#111827";

            if (stroke.length === 1) {
                signatureCtx.beginPath();
                signatureCtx.arc(stroke[0].x, stroke[0].y, 1.8, 0, Math.PI * 2);
                signatureCtx.fillStyle = "#111827";
                signatureCtx.fill();
                return;
            }

            signatureCtx.beginPath();
            signatureCtx.moveTo(stroke[0].x, stroke[0].y);

            for (let i = 1; i < stroke.length - 1; i++) {
                const midX = (stroke[i].x + stroke[i + 1].x) / 2;
                const midY = (stroke[i].y + stroke[i + 1].y) / 2;
                signatureCtx.quadraticCurveTo(stroke[i].x, stroke[i].y, midX, midY);
            }

            const last = stroke[stroke.length - 1];
            signatureCtx.lineTo(last.x, last.y);
            signatureCtx.stroke();
        }

        function redrawSignatureCanvas() {
            if (!signatureCanvas || !signatureCtx) return;

            paintSignatureCanvasBackground();
            drawnSignatureStrokes.forEach(drawSignatureStroke);

            if (drawnSignatureCurrentStroke.length) {
                drawSignatureStroke(drawnSignatureCurrentStroke);
            }
        }

        function getSignaturePoint(event) {
            const rect = signatureCanvas.getBoundingClientRect();

            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top,
            };
        }

        function isDrawnSignatureBlank() {
            return drawnSignatureStrokes.length === 0 && drawnSignatureCurrentStroke.length === 0;
        }

        function invalidateDrawnSignatureAfterEdit() {
            if (!drawnSignatureWasUsed) return;

            drawnSignatureWasUsed = false;

            if (sigInput) {
                sigInput.value = "";
            }

            showSignatureStatus(
                "",
                "Signature changed. Click Use Drawn Signature again before proceeding.",
                "neutral"
            );
        }

        function startDrawnSignature(event) {
            if (!signatureCanvas) return;
            if (event.pointerType === "mouse" && event.button !== 0) return;

            resizeSignatureCanvas();
            event.preventDefault();

            drawnSignatureIsDrawing = true;
            drawnSignatureCurrentStroke = [getSignaturePoint(event)];
            signatureCanvas.setPointerCapture?.(event.pointerId);

            redrawSignatureCanvas();
        }

        function moveDrawnSignature(event) {
            if (!drawnSignatureIsDrawing) return;

            event.preventDefault();
            drawnSignatureCurrentStroke.push(getSignaturePoint(event));
            redrawSignatureCanvas();
        }

        function endDrawnSignature(event) {
            if (!drawnSignatureIsDrawing) return;

            event.preventDefault();

            if (drawnSignatureCurrentStroke.length) {
                drawnSignatureStrokes.push(drawnSignatureCurrentStroke);
            }

            drawnSignatureCurrentStroke = [];
            drawnSignatureIsDrawing = false;

            markFormDirty();
            invalidateDrawnSignatureAfterEdit();
            redrawSignatureCanvas();
        }

        function clearDrawnSignature() {
            drawnSignatureStrokes = [];
            drawnSignatureCurrentStroke = [];
            drawnSignatureIsDrawing = false;
            drawnSignatureWasUsed = false;

            if (sigInput) {
                sigInput.value = "";
            }

            redrawSignatureCanvas();
            clearSignatureDisplay();
            markFormDirty();
        }

        function undoDrawnSignature() {
            if (!drawnSignatureStrokes.length) return;

            drawnSignatureStrokes.pop();
            redrawSignatureCanvas();
            invalidateDrawnSignatureAfterEdit();
            markFormDirty();

            if (isDrawnSignatureBlank()) {
                drawnSignatureWasUsed = false;

                if (sigInput) {
                    sigInput.value = "";
                }

                clearSignatureDisplay();
            }
        }

        function attachDrawnSignatureToFileInput(file) {
            if (!sigInput) return false;

            if (typeof DataTransfer === "undefined") {
                showMiniTab("Your browser cannot attach the drawn signature. Please try again or use another browser.");
                return false;
            }

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            sigInput.files = dataTransfer.files;

            return true;
        }

        function useDrawnSignature() {
            if (!signatureCanvas) return;

            if (isDrawnSignatureBlank()) {
                showMiniTab("Please draw the patient's signature first.");
                return;
            }

            redrawSignatureCanvas();

            signatureCanvas.toBlob((blob) => {
                if (!blob) {
                    showMiniTab("Unable to process drawn signature. Please try again.");
                    return;
                }

                const file = new File(
                    [blob],
                    `walk-in-signature-${Date.now()}.png`, {
                        type: "image/png",
                        lastModified: Date.now(),
                    }
                );

                const attached = attachDrawnSignatureToFileInput(file);
                if (!attached) return;

                drawnSignatureWasUsed = true;
                showSignatureStatus(file.name, "Drawn signature saved and ready.", "success");
                markFormDirty();
            }, "image/png", 0.95);
        }

        signatureCanvas?.addEventListener("pointerdown", startDrawnSignature);
        signatureCanvas?.addEventListener("pointermove", moveDrawnSignature);
        signatureCanvas?.addEventListener("pointerup", endDrawnSignature);
        signatureCanvas?.addEventListener("pointercancel", endDrawnSignature);
        signatureCanvas?.addEventListener("pointerleave", endDrawnSignature);

        signatureUndoBtn?.addEventListener("click", undoDrawnSignature);
        signatureClearBtn?.addEventListener("click", clearDrawnSignature);
        signatureUseDrawnBtn?.addEventListener("click", useDrawnSignature);

        window.addEventListener("resize", () => {
            if (step === 3) {
                resizeSignatureCanvas();
            }
        });

        setTimeout(resizeSignatureCanvas, 300);

        const emergencyNumber = document.getElementById("emergency_number");
        const emergencyNumberFeedback = document.getElementById("emergency_number_feedback");

        function formatPhoneDisplay(rawDigits) {
            const digits = rawDigits.slice(0, 11);
            let out = "";

            if (digits.length > 0) out += digits.slice(0, 4);
            if (digits.length > 4) out += " " + digits.slice(4, 7);
            if (digits.length > 7) out += " " + digits.slice(7, 11);

            return out;
        }

        function setPhoneFeedback(message, type = "") {
            if (!emergencyNumberFeedback) return;
            emergencyNumberFeedback.textContent = message;
            emergencyNumberFeedback.classList.remove("error", "success", "text-[#9e9690]");
            if (type === "error") emergencyNumberFeedback.classList.add("error");
            else if (type === "success") emergencyNumberFeedback.classList.add("success");
            else emergencyNumberFeedback.classList.add("text-[#9e9690]");
        }

        function validateEmergencyNumber(showNeutral = false) {
            if (!emergencyNumber) return false;

            const digits = emergencyNumber.value.replace(/\D/g, "");

            emergencyNumber.classList.remove("input-invalid", "input-valid", "border-red-500", "border-green-600");

            if (!digits.length) {
                if (showNeutral) setPhoneFeedback("Format: 09xx xxx xxxx");
                else setPhoneFeedback("");
                return false;
            }

            if (!digits.startsWith("09")) {
                emergencyNumber.classList.add("input-invalid");
                setPhoneFeedback("Contact number must start with 09.", "error");
                return false;
            }

            if (digits.length < 11) {
                emergencyNumber.classList.add("input-invalid");
                setPhoneFeedback("Contact number must be 11 digits.", "error");
                return false;
            }

            if (digits.length === 11) {
                emergencyNumber.classList.add("input-valid");
                setPhoneFeedback("Valid contact number.", "success");
                return true;
            }

            emergencyNumber.classList.add("input-invalid");
            setPhoneFeedback("Contact number must be 11 digits only.", "error");
            return false;
        }

        emergencyNumber?.addEventListener("input", (e) => {
            const hadNonDigit = /[^\d\s]/.test(e.target.value);

            let digits = e.target.value.replace(/\D/g, "");

            if (digits.startsWith("9")) digits = "0" + digits;
            if (digits.length > 11) digits = digits.slice(0, 11);

            emergencyNumber.value = formatPhoneDisplay(digits);

            if (hadNonDigit) {
                showMiniTab("Contact number must contain digits only.");
            }

            validateEmergencyNumber(true);
            markFormDirty();
        });

        emergencyNumber?.addEventListener("focus", () => {
            validateEmergencyNumber(true);
        });

        emergencyNumber?.addEventListener("blur", () => {
            validateEmergencyNumber(true);
        });

        let formIsDirty = false;
        let formSubmitting = false;
        let pendingNavigation = null;

        const leaveModal = document.getElementById('leaveModal');

        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('input', () => {
                formIsDirty = true;
            });
        });
        document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', () => {
                formIsDirty = true;
            });
        });

        function openLeaveModal(onConfirm) {
            pendingNavigation = onConfirm;
            leaveModal.showModal();
        }

        function confirmReloadPage() {
            openLeaveModal(() => {
                window.location.reload();
            });
        }

        function closeLeaveModal() {
            if (leaveModal.open) leaveModal.close();
            pendingNavigation = null;
        }

        document.getElementById('cancelLeaveBtn')?.addEventListener('click', closeLeaveModal);

        document.getElementById('slotFullOkBtn')?.addEventListener('click', () => {
            const modal = document.getElementById('slotFullModal');
            if (modal) modal.close();
        });

        document.getElementById('saveDraftBtn')?.addEventListener('click', () => {
            saveDraftData();
            formIsDirty = false;
            leaveModal.close();

            if (typeof pendingNavigation === "function") {
                const action = pendingNavigation;
                pendingNavigation = null;
                action();
            }
        });

        document.getElementById('discardDraftBtn')?.addEventListener('click', () => {
            clearDraft();
            formIsDirty = false;
            leaveModal.close();

            if (typeof pendingNavigation === "function") {
                const action = pendingNavigation;
                pendingNavigation = null;
                action();
            }
        });

        document.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener("click", e => {
                const href = link.getAttribute("href") || "";

                if (href.startsWith("#") || href.startsWith("javascript:") || link.type === 'submit')
                    return;

                if (formIsDirty && !formSubmitting) {
                    e.preventDefault();
                    openLeaveModal(() => {
                        window.location.href = link.href;
                    });
                }
            });
        });

        const clearSlotSelectionBtn = document.getElementById("clearSlotSelection");

        function clearTimeSelectionOnly() {
            const timeInput = document.getElementById("appointment_time");
            const selectedSlotDisplay = document.getElementById("selectedSlotDisplay");
            const selectedSlotText = document.getElementById("selectedSlotText");

            if (timeInput) timeInput.value = "";
            if (selectedSlotText) selectedSlotText.textContent = "";

            selectedSlotDisplay?.classList.add("hidden");
            clearSlotSelectionBtn?.classList.add("hidden");

            document.querySelectorAll("#slotGrid .slot-chip").forEach(chip => {
                chip.classList.remove("selected", "bg-[#8B0000]", "text-white");
                chip.setAttribute("aria-pressed", "false");
            });

            markFormDirty();
        }

        function clearDateAndTimeSelection() {
            const dateInput = document.getElementById("appointment_date");
            const dateBanner = document.getElementById("dateBanner");
            const slotContainer = document.getElementById("slotContainer");
            const slotPlaceholder = document.getElementById("slotPlaceholder");
            const slotGrid = document.getElementById("slotGrid");
            const timePanel = document.querySelector(".time-panel");

            if (dateInput) dateInput.value = "";

            clearTimeSelectionOnly();

            if (slotGrid) slotGrid.innerHTML = "";

            dateBanner?.classList.add("hidden");

            slotContainer?.classList.add("hidden");
            slotContainer?.classList.remove("slot-fade-in", "slot-fade-out");

            slotPlaceholder?.classList.remove("hidden", "slot-fade-in", "slot-fade-out");
            slotPlaceholder?.style.removeProperty("display");
            slotPlaceholder?.style.removeProperty("transform");
            slotPlaceholder?.style.removeProperty("opacity");

            timePanel?.classList.add("is-empty");
        }

        clearSlotSelectionBtn?.addEventListener("click", clearTimeSelectionOnly);

        document.getElementById("appointment_date")?.addEventListener("change", function() {
            const slotPlaceholder = document.getElementById("slotPlaceholder");
            const slotContainer = document.getElementById("slotContainer");
            const timePanel = document.querySelector(".time-panel");

            if (!this.value) {
                clearDateAndTimeSelection();
                slotPlaceholder?.classList.remove("hidden");
                slotContainer?.classList.add("hidden");
                timePanel?.classList.add("is-empty");
            } else {
                slotPlaceholder?.classList.add("hidden");
                slotContainer?.classList.remove("hidden");
                timePanel?.classList.remove("is-empty");
                animateSlotsIn();
            }
        });

        const selectedSlotObserver = new MutationObserver(() => {
            const selectedSlotDisplay = document.getElementById("selectedSlotDisplay");
            const timeInput = document.getElementById("appointment_time");

            if (timeInput?.value && !selectedSlotDisplay?.classList.contains("hidden")) {
                clearSlotSelectionBtn?.classList.remove("hidden");
                document.querySelector(".time-panel")?.classList.remove("is-empty");
            } else {
                clearSlotSelectionBtn?.classList.add("hidden");
            }
        });

        const selectedSlotDisplayNode = document.getElementById("selectedSlotDisplay");

        if (selectedSlotDisplayNode) {
            selectedSlotObserver.observe(selectedSlotDisplayNode, {
                attributes: true,
                attributeFilter: ["class"]
            });
        }

        const slotContainer = document.getElementById("slotContainer");
        const slotPlaceholder = document.getElementById("slotPlaceholder");
        const timePanel = document.querySelector(".time-panel");

        let slotAnimationLock = false;

        function animateSlotsIn() {
            if (!slotContainer || slotAnimationLock) return;

            slotAnimationLock = true;

            timePanel?.classList.remove("is-empty");

            slotPlaceholder?.classList.add("hidden");
            slotPlaceholder?.style.setProperty("display", "none", "important");

            slotContainer.classList.remove("hidden", "slot-fade-out");
            slotContainer.classList.add("slot-fade-in");

            setTimeout(() => {
                slotContainer.classList.remove("slot-fade-in");
                slotAnimationLock = false;
            }, 320);
        }

        function initIntroBookingModal() {
            const modal = document.getElementById("introBookingModal");
            const startBtn = document.getElementById("introStartBtn");
            const continueDraftBtn = document.getElementById("introContinueDraftBtn");

            if (!modal) return;

            const hasDraft = hasSavedDraft();

            if (hasDraft) {
                continueDraftBtn?.classList.remove("hidden");
            } else {
                continueDraftBtn?.classList.add("hidden");
            }

            function lockPageScroll() {
                const scrollY = window.scrollY || window.pageYOffset || 0;
                document.body.dataset.lockedScrollY = String(scrollY);
                document.body.style.top = `-${scrollY}px`;
                document.documentElement.classList.add("intro-modal-open");
                document.body.classList.add("intro-modal-open");
            }

            function unlockPageScroll() {
                const scrollY = parseInt(document.body.dataset.lockedScrollY || "0", 10);
                document.documentElement.classList.remove("intro-modal-open");
                document.body.classList.remove("intro-modal-open");
                document.body.style.top = "";
                delete document.body.dataset.lockedScrollY;
                window.scrollTo(0, Number.isNaN(scrollY) ? 0 : scrollY);
            }

            modal.addEventListener("close", unlockPageScroll);

            startBtn?.addEventListener("click", () => {
                modal.close();
            });

            continueDraftBtn?.addEventListener("click", async () => {
                modal.close();
                await restoreDraft();
                showMiniTab("Saved draft loaded.");
            });

            setTimeout(() => {
                if (!modal.open) {
                    lockPageScroll();
                    modal.showModal();
                }
            }, 350);
        }

        showStep(0);
        initIntroBookingModal();

        window.addEventListener("resize", () => {
            document.querySelectorAll(".sm-grid-1col").forEach(el => {
                el.style.gridTemplateColumns = window.innerWidth < 640 ? "1fr" : "1fr 1fr";
            });
        });

        setupCharLimit("additional_concerns", "concernCount", 150, "concernWarning");
        setupCharLimit(
            "good_health_details", "goodHealthCount", 150);
        setupCharLimit("treatment_details", "treatmentCount",
            150);
        setupCharLimit("hospital_details", "hospitalCount", 150);
        setupCharLimit("medication_details",
            "medicationCount", 150);

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".question-text").forEach(q => {
                const row = q.closest("div");
                const hasRequiredRadio = row?.querySelector("input[required]");

                if (hasRequiredRadio && !q.querySelector(".required-star")) {
                    const star = document.createElement("span");
                    star.className = "required-star";
                    star.textContent = " *";
                    q.appendChild(star);
                }
            });

            document.querySelectorAll("input[required], select[required], textarea[required]").forEach(input => {
                if (input.tagName === "INPUT" && input.type === "hidden") return;

                let label = null;

                if (input.id) {
                    label = document.querySelector(`label[for="${input.id}"]`);
                }

                if (!label) {
                    label = input.closest("label");
                }

                if (!label) {
                    const fieldContainer = input.closest(
                        ".space-y-4 > div, .grid > div, .ml-6, .mt-3, .mt-2, .date-input-wrap, .voice-input-wrap"
                    );
                    label = fieldContainer?.parentElement?.querySelector(":scope > label") || null;
                }

                if (!label) {
                    const previous = input.previousElementSibling;
                    if (previous && previous.tagName === "LABEL") {
                        label = previous;
                    }
                }

                if (label && !label.querySelector(".required-star")) {
                    const star = document.createElement("span");
                    star.className = "required-star";
                    star.textContent = " *";
                    label.appendChild(star);
                }
            });
        });
    </script>
@endsection
