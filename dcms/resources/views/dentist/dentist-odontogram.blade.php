@extends('layouts.dentist')

@section('title', 'Odontogram Procedure')

@section('styles')
<style>
    #sidebar,
    .sidebar {
        display: none !important;
    }

    .glass-panel {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #E5E7EB;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .soft-card {
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        border: 1px solid #ECEEF2;
        border-radius: 18px;
    }

    .view-toggle-btn {
        width: 46px;
        min-width: 46px;
        height: 46px;
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        background: #ffffff;
        color: #4B5563;
        transition: all 0.2s ease;
    }

    .toolbar-soft-btn span,
    .view-toggle-btn span,
    .toolbar-label {
        display: none;
    }

    .toolbar-actions {
        gap: 0.5rem;
        flex-wrap: nowrap;
    }

    .odontogram-toolbar {
        align-items: center;
    }

    .toolbar-group {
        gap: 0;
    }

    .view-toggle-btn:hover {
        border-color: #FCA5A5;
        background: #FEF2F2;
        color: #8B0000;
    }

    .view-toggle-btn.active {
        background: #8B0000;
        color: #ffffff;
        border-color: #8B0000;
        box-shadow: 0 10px 18px rgba(139, 0, 0, 0.15);
    }

    .legend-btn {
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 12px;
        font-size: 13px;
        color: #333333;
        background: #ffffff;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        text-align: left;
        width: 100%;
    }

    .legend-btn:hover {
        background: #FEF2F2;
        border-color: #FCA5A5;
        transform: translateY(-1px);
    }

    .legend-btn.active {
        background-color: #FEE2E2;
        border: 1px solid #8B0000;
        color: #8B0000;
        font-weight: 700;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.08);
    }

    .legend-color-dot {
        width: 12px;
        height: 12px;
        min-width: 12px;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, 0.08);
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 999px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .treatment-chip {
        border: 1px solid #FCA5A5;
        background: #FFF1F2;
        color: #8B0000;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
    }

    .selected-tooth-card {
        background: linear-gradient(135deg, #FFF7F7 0%, #FFFDFD 100%);
        border: 1px solid #FECACA;
    }

    .legend-status-note {
        background: #FFF7ED;
        border: 1px solid #FED7AA;
        color: #9A3412;
    }

    .primary-action-btn {
        background: #8B0000;
        color: #ffffff;
        transition: all 0.2s ease;
    }

    .primary-action-btn:hover {
        background: #660000;
    }

    .primary-action-btn:disabled {
        background: #D1D5DB;
        color: #6B7280;
        cursor: not-allowed;
        box-shadow: none;
    }

    .tooth-tooltip {
        position: absolute;
        z-index: 30;
        pointer-events: none;
        min-width: 180px;
        max-width: 240px;
        background: rgba(17, 24, 39, 0.96);
        color: #ffffff;
        border-radius: 12px;
        padding: 10px 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18);
        transform: translate(-50%, calc(-100% - 14px));
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.15s ease, visibility 0.15s ease;
    }

    .tooth-tooltip.show {
        opacity: 1;
        visibility: visible;
    }

    .tooth-tooltip::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -6px;
        width: 12px;
        height: 12px;
        background: rgba(17, 24, 39, 0.96);
        transform: translateX(-50%) rotate(45deg);
    }

    .modal-backdrop {
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(2px);
    }

    .odontogram-layout {
        align-items: start;
    }

    .odontogram-left-panel {
        position: relative;
        top: auto;
        height: auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .left-view-shell {
        min-height: auto;
        display: flex;
        flex-direction: column;
    }

    .odontogram-right-shell {
        position: relative;
        top: auto;
        height: auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .odontogram-right-top {
        flex-shrink: 0;
    }

    .odontogram-right-scroll {
        overflow: visible;
        flex: unset;
        padding-right: 0;
    }

    .odontogram-right-bottom {
        flex-shrink: 0;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #F3F4F6;
        background: #ffffff;
    }

    .legend-drawer-open {
        overflow: hidden;
    }

    .legend-drawer-header {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    #legendDrawer {
        transform: translateX(100%);
    }

    #legendDrawer.open {
        transform: translateX(0);
    }

    .legend-category-block {
        border: 1px solid #F1F5F9;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        overflow: hidden;
    }

    .legend-category-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        background: #FAFAFA;
        border-bottom: 1px solid #F3F4F6;
    }

    .legend-category-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #legendDrawerBackdrop {
        display: none;
    }

    #legendDrawerBackdrop.show {
        display: block;
    }

    .legend-category-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #FEF2F2;
        color: #8B0000;
        flex-shrink: 0;
    }

    .legend-category-body {
        padding: 14px;
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .legend-category-count {
        font-size: 11px;
        font-weight: 700;
        color: #6B7280;
        background: #F3F4F6;
        border-radius: 999px;
        padding: 5px 10px;
    }

    .legend-btn {
        border-radius: 16px;
    }

    .legend-btn .legend-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        min-width: 0;
    }

    .legend-btn .legend-code {
        font-size: 11px;
        font-weight: 800;
        color: #8B0000;
        line-height: 1;
        margin-bottom: 3px;
    }

    .legend-btn .legend-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        line-height: 1.25;
        text-align: left;
    }

    .legend-btn.active .legend-label,
    .legend-btn.active .legend-code {
        color: #8B0000;
    }

    .mode-panel {
        display: none;
    }

    .mode-panel.active {
        display: flex;
        flex: 1;
        flex-direction: column;
        min-height: 0;
    }

    #odontogram2DPanel,
    #odontogram3DPanel {
        flex: 1;
        min-height: 0;
    }

    #canvas-container {
        flex: 1;
        min-height: 520px;
    }

    #canvas-container:active {
        cursor: grabbing;
    }

    .odontogram2d-shell {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .odontogram-left-panel,
    .left-view-shell,
    .mode-panel.active {
        min-height: 0;
    }

    .odontogram-board {
        min-width: fit-content;
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin: 0 auto;
        padding-bottom: 10px;
    }

    .odontogram-row {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 4px;
        flex-wrap: nowrap;
        min-width: fit-content;
    }

    .arch-divider {
        margin-bottom: 16px;
    }

    .tooth-unit {
        width: 40px;
        min-width: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
    }

    .status-box {
        width: 28px;
        height: 40px;
        border: 1.5px solid #8B0000;
        border-radius: 4px;
        background: #ffffff;
        position: relative;
        cursor: pointer;
        transition: all 0.18s ease;
        flex-shrink: 0;
    }

    .status-box::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1.5px;
        background: #8B0000;
        transform: translateY(-50%);
    }

    .status-box:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(139, 0, 0, 0.12);
        background: #FEF2F2;
    }

    .status-box.selected-target {
        outline: 2.5px solid rgba(139, 0, 0, 0.2);
        background: #FEE2E2;
    }

    .tooth-number {
        font-size: 10px;
        font-weight: 700;
        color: #4B5563;
        line-height: 1;
    }

    .tooth-2d-wrapper {
        width: 38px;
        height: 38px;
        min-height: 38px;
        border-radius: 50%;
        border: 1.5px solid #111827;
        background: #ffffff;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .tooth-svg {
        width: 100%;
        height: 100%;
        transform: scale(1.02);
    }

    .surface-part {
        fill: #ffffff;
        stroke: #111827;
        stroke-width: 3px;
        cursor: pointer;
        transition: fill 0.15s ease, stroke 0.15s ease;
    }

    .surface-part:hover {
        fill: #E5E7EB;
    }

    .surface-part.selected-target {
        fill: #FEE2E2;
        stroke: #8B0000;
        stroke-width: 4px;
    }

    .surface-center {
        stroke-width: 2.5px;
    }

    .status-label-left {
        width: 52px;
        min-width: 52px;
        text-align: right;
        font-weight: 800;
        font-size: 10px;
        line-height: 1.2;
        color: #8B0000;
        padding-right: 12px;
        text-transform: uppercase;
    }

    .status-label-right {
        width: 52px;
        min-width: 52px;
        text-align: left;
        font-weight: 800;
        font-size: 11px;
        line-height: 1.2;
        color: #8B0000;
        padding-left: 12px;
        text-transform: uppercase;
    }

    @media (max-width: 1280px) {
        #canvas-container {
            height: 420px;
        }

        .odontogram-left-panel,
        .odontogram-right-shell {
            position: static;
            top: auto;
            height: auto;
        }

        .odontogram-right-scroll {
            overflow-y: visible;
            padding-right: 0;
            margin-top: 1rem;
            max-height: none;
        }

        .odontogram-right-bottom {
            margin-top: 1rem;
        }

        .left-view-shell {
            min-height: auto;
        }
    }

    @media (max-width: 768px) {
        #canvas-container {
            height: 340px;
        }

        .odontogram2d-shell {
            padding: 12px;
        }

        .odontogram-board {
            min-width: 760px;
        }
    }

    .odontogram-hero {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .odontogram-hero-main {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(320px, 1fr);
        gap: 1rem;
        align-items: stretch;
    }

    .odontogram-hero-left {
        display: flex;
        gap: 1rem;
        align-items: stretch;
        min-width: 0;
        flex-wrap: wrap;
    }

    .hero-danger-btn {
        position: absolute;
        top: 18px;
        left: 18px;
        z-index: 5;

        width: 42px;
        height: 42px;
        padding: 0;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        border-radius: 999px;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #e5e7eb;
        color: #8b0000;
        text-decoration: none;
        overflow: visible;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        backdrop-filter: blur(6px);
        transition: all 0.2s ease;
    }

    .hero-danger-btn span {
        display: none;
    }


    .hero-danger-btn::after {
        content: attr(data-tooltip);
        position: absolute;
        top: 50%;
        left: calc(100% + 10px);
        transform: translateY(-50%) translateX(-4px);
        background: rgba(17, 24, 39, 0.96);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        line-height: 1;
        white-space: nowrap;
        padding: 8px 10px;
        border-radius: 10px;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: all 0.18s ease;
    }

    .hero-danger-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: calc(100% + 4px);
        width: 8px;
        height: 8px;
        background: rgba(17, 24, 39, 0.96);
        transform: translateY(-50%) rotate(45deg);
        opacity: 0;
        visibility: hidden;
        transition: all 0.18s ease;
    }

    .hero-danger-btn:hover::after,
    .hero-danger-btn:hover::before,
    .hero-danger-btn:focus-visible::after,
    .hero-danger-btn:focus-visible::before {
        opacity: 1;
        visibility: visible;
        transform: translateY(-50%) translateX(0);
    }

    @media (max-width: 768px) {

        .hero-danger-btn::after,
        .hero-danger-btn::before {
            display: none;
        }
    }

    .hero-danger-btn:hover {
        transform: translateY(-1px) scale(1.03);
        background: #fff1f2;
        border-color: #fca5a5;
        color: #7f1d1d;
        box-shadow: 0 14px 28px rgba(139, 0, 0, 0.14);
    }

    .hero-danger-btn:active {
        transform: scale(0.98);
    }

    .hero-patient-card {
        padding: 1rem 1.2rem;
        border-radius: 20px;
    }

    .hero-title-card,
    .hero-patient-card {
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .hero-title-card {
        position: relative;
        padding: 1.4rem 1.6rem 1.4rem 4.8rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1 1 420px;
        min-width: 0;
    }

    .hero-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: linear-gradient(135deg, #8b0000 0%, #b91c1c 100%);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        box-shadow: 0 10px 20px rgba(139, 0, 0, 0.18);
        flex-shrink: 0;
    }

    .hero-eyebrow {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #9ca3af;
        margin-bottom: 4px;
    }

    .hero-title {
        font-size: 2rem;
        line-height: 1;
        font-weight: 900;
        color: #8b0000;
        margin: 0 0 0.35rem;
    }

    .hero-subtitle {
        margin: 0;
        color: #6b7280;
        font-size: 1.05rem;
        font-weight: 600;
    }

    .hero-patient-label {
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #dc2626;
        margin-bottom: 0.5rem;
    }

    .hero-patient-name {
        margin: 0;
        color: #111827;
        font-size: 2rem;
        font-weight: 900;
        line-height: 1.05;
    }

    .hero-procedure-meta {
        display: flex;
        gap: 1rem;
        align-items: stretch;
    }

    .hero-stat {
        min-width: 122px;
        padding: 0.7rem 0.85rem;
        border-radius: 16px;
    }

    .hero-stat-label {
        display: block;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 6px;
    }

    .hero-stat-value {
        display: block;
        color: #8b0000;
        font-size: 1.35rem;
        font-weight: 900;
        line-height: 1;
    }

    .hero-stat-date {
        display: block;
        color: #4b5563;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .toolbar-group-tools {
        margin-right: auto;
    }

    .toolbar-group-view {
        margin-left: auto;
        align-items: flex-end;
    }

    .toolbar-group {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .toolbar-label {
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #9ca3af;
    }

    .toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .toolbar-soft-btn {
        width: 46px;
        min-width: 46px;
        height: 46px;
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #374151;
        font-size: 15px;
        font-weight: 800;
        transition: all 0.2s ease;
    }

    .toolbar-soft-btn:hover:not(:disabled) {
        background: #fff7f7;
        border-color: #fecaca;
        color: #8b0000;
    }

    .toolbar-soft-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .odontogram-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
        padding: 1rem 1.2rem;
        position: relative;
        transition:
            transform 0.32s ease,
            opacity 0.32s ease,
            box-shadow 0.32s ease,
            background 0.32s ease,
            border-color 0.32s ease;
        transform: translateY(0);
        will-change: transform, opacity;
    }

    .odontogram-toolbar-sentinel {
        width: 100%;
        height: 1px;
        pointer-events: none;
    }

    @media (max-width: 768px) {
        .odontogram-toolbar {
            flex-direction: column;
        }
    }

    .right-panel-sections {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .right-section-card {
        border: 1px solid #eceef2;
        border-radius: 20px;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        overflow: hidden;
    }

    .right-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1rem 0.85rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .right-section-body {
        padding: 1rem;
    }

    .right-section-eyebrow {
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 4px;
    }

    .right-section-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #111827;
    }

    @media (max-width: 1200px) {
        .odontogram-hero-main {
            grid-template-columns: 1fr;
        }

        .hero-patient-card {
            align-items: flex-start;
            flex-direction: column;
        }

        .hero-procedure-meta {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .odontogram-hero-left {
            flex-direction: column;
        }

        .hero-danger-btn {
            width: 40px;
            height: 40px;
            min-width: 40px;
            top: 14px;
            left: 14px;
        }

        .hero-title-card {
            padding: 1.1rem 1rem 1.1rem 4.1rem;
        }

        .hero-title {
            font-size: 1.5rem;
        }

        .hero-patient-name {
            font-size: 1.55rem;
        }

        .hero-procedure-meta {
            flex-direction: column;
        }

        .hero-stat {
            width: 100%;
            text-align: left;
        }

        .odontogram-toolbar {
            flex-direction: column;
        }
    }

    .right-section-body textarea {
        width: 100%;
        min-width: 100%;
        min-height: 160px;
    }

    .right-panel-sections .right-section-card {
        overflow: visible;
    }
</style>
@endsection

@section('content')
@php
use Carbon\Carbon;
$patientName = $patient->name ?? 'Unknown Patient';
$today = Carbon::now()->format('F d, Y');
@endphp

<main class="pt-[100px] px-3 md:px-6 pb-6">
    <div class="w-full fade-in">
        <div class="odontogram-hero mb-6">
            <div class="odontogram-hero-main">
                <div class="odontogram-hero-left">
                    <button type="button" id="cancelProcedureBtn" class="hero-danger-btn"
                        data-tooltip="Cancel Procedure" aria-label="Cancel Procedure">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Cancel Procedure</span>
                    </button>

                    <div class="hero-title-card">
                        <div class="hero-title-icon">
                            <i class="fa-solid fa-tooth"></i>
                        </div>
                        <div>
                            <p class="hero-eyebrow">Dental Procedure Workspace</p>
                            <h1 class="hero-title">Dentist Odontogram</h1>
                            <p class="hero-subtitle">2D / 3D Treatment &amp; Condition Mapping</p>
                        </div>
                    </div>
                </div>

                <div class="hero-patient-card">
                    <div class="hero-patient-meta">
                        <p class="hero-patient-label">Patient</p>
                        <h2 class="hero-patient-name">{{ $patientName }}</h2>
                    </div>

                    <div class="hero-procedure-meta">
                        <div class="hero-stat">
                            <span class="hero-stat-label">Procedure Time</span>
                            <span id="procedureTimer" class="hero-stat-value">00:00:00</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-label">Date</span>
                            <span class="hero-stat-date">{{ $today }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="odontogram-toolbar" id="odontogramToolbar">
                <div class="toolbar-group toolbar-group-tools">
                    <span class="toolbar-label">Selection Tools</span>
                    <div class="toolbar-actions">
                        <button type="button" id="clearSelectionBtn" class="toolbar-soft-btn"
                            data-tooltip="Clear Selection" title="Clear Selection" disabled>
                            <i class="fa-solid fa-arrow-pointer"></i>
                            <span>Clear Selection</span>
                        </button>

                        <button type="button" id="undoBtn" class="toolbar-soft-btn" data-tooltip="Undo" title="Undo"
                            disabled>
                            <span>Undo</span>
                        </button>

                        <button type="button" id="redoBtn" class="toolbar-soft-btn" data-tooltip="Redo" title="Redo"
                            disabled>
                            <i class="fa-solid fa-rotate-right"></i>
                            <span>Redo</span>
                        </button>
                    </div>
                </div>

                <div class="toolbar-group toolbar-group-view">
                    <span class="toolbar-label">View Mode</span>
                    <div class="toolbar-actions">
                        <button type="button" id="view2dBtn" class="view-toggle-btn active" data-tooltip="2D View"
                            title="2D View">
                            <i class="fa-regular fa-image"></i>
                            <span>2D View</span>
                        </button>

                        <button type="button" id="view3dBtn" class="view-toggle-btn" data-tooltip="3D View"
                            title="3D View">
                            <i class="fa-solid fa-cube"></i>
                            <span>3D View</span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="odontogramToolbarSentinel" class="odontogram-toolbar-sentinel" aria-hidden="true"></div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 odontogram-layout">
            <div class="xl:col-span-8 glass-panel p-4 odontogram-left-panel">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            Click a tooth surface in 2D or switch to 3D view
                        </p>
                    </div>
                </div>

                <div class="soft-card p-4 left-view-shell">
                    <div class="w-full flex flex-col sm:flex-row justify-between sm:items-center gap-3 mb-4 px-2">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                            <i class="fa-solid fa-tooth"></i>
                            <span id="viewInstructionText">Click the tooth surfaces or status box to assign a
                                condition</span>
                        </p>

                        <div
                            class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100 shadow-inner">
                            <i class="fa-solid fa-tooth text-[#8B0000]"></i>
                            <p id="toothHoverLabel" class="text-sm font-extrabold text-[#8B0000]">Select a tooth</p>
                        </div>
                    </div>

                    <div id="odontogram2DPanel" class="mode-panel active">
                        <div class="odontogram2d-shell custom-scrollbar">
                            <div id="odontogram2DBoard" class="odontogram-board"></div>
                        </div>
                    </div>

                    <div id="odontogram3DPanel" class="mode-panel">
                        <div id="canvas-container">
                            <div id="loadingOverlay"
                                class="absolute inset-0 bg-white flex flex-col gap-3 items-center justify-center z-10 rounded-xl transition-opacity duration-500">
                                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-[#8B0000]"></i>
                                <p class="text-sm font-semibold text-gray-600">Generating 3D Model...</p>
                            </div>

                            <div id="toothTooltip" class="tooth-tooltip">
                                <div id="toothTooltipContent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 glass-panel p-6 odontogram-right-shell">
                <div class="odontogram-right-top">
                    <div class="selected-tooth-card rounded-2xl p-4">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                Selected Target
                            </label>
                            <span id="selectedViewBadge"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-[#8B0000] text-[11px] font-bold border border-red-100">
                                2D View
                            </span>
                        </div>

                        <div id="selectedToothDisplay"
                            class="w-full bg-white border border-red-100 text-[#8B0000] text-base font-extrabold rounded-2xl px-4 py-3">
                            Click a tooth on the chart
                        </div>

                        <p id="selectedToothName" class="mt-2 text-sm font-bold text-gray-700">
                            No tooth selected
                        </p>

                        <div class="mt-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                                Current Treatment
                            </p>
                            <div id="selectedToothLegendList" class="flex flex-wrap gap-2">
                                <span class="text-[11px] text-gray-400 italic">No treatment assigned yet.</span>
                            </div>
                        </div>

                        <div id="legendStatusNote"
                            class="hidden mt-3 rounded-xl px-3 py-2 text-xs font-semibold legend-status-note">
                            Choose a treatment legend below, then click Apply Treatment.
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-3">
                            <button type="button" id="applyTreatmentBtn"
                                class="primary-action-btn w-full text-sm font-semibold py-2.5 rounded-xl shadow-sm"
                                disabled>
                                <i class="fa-solid fa-stethoscope mr-2"></i>
                                Apply Treatment
                            </button>

                            <button type="button" id="clearCurrentToothBtn"
                                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold py-2.5 rounded-xl transition"
                                disabled>
                                <i class="fa-solid fa-eraser mr-2"></i>
                                Clear Target
                            </button>
                        </div>
                    </div>
                </div>

                <div class="odontogram-right-scroll">
                    <div class="right-panel-sections">
                        <section class="right-section-card">
                            <div class="right-section-head">
                                <div>
                                    <p class="right-section-eyebrow">Treatment Legend</p>
                                    <h3 class="right-section-title">Legend Selection</h3>
                                </div>

                                <button type="button" id="openLegendDrawerBtn"
                                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-[#8B0000] text-sm font-semibold border border-red-100 transition">
                                    <i class="fa-solid fa-layer-group"></i>
                                    Open Legend
                                </button>
                            </div>

                            <div class="right-section-body">
                                <p id="selectedLegendPreview" class="text-sm text-[#8B0000] font-bold">
                                    No legend selected yet.
                                </p>
                            </div>
                        </section>

                        <section class="right-section-card">
                            <div class="right-section-head">
                                <div>
                                    <p class="right-section-eyebrow">Clinical Documentation</p>
                                    <h3 class="right-section-title">Oral Examination Notes</h3>
                                </div>
                            </div>

                            <div class="right-section-body space-y-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                        Oral Examination Notes
                                    </label>
                                    <textarea id="oralExaminationNotes" rows="5"
                                        class="w-full bg-gray-50 border border-gray-100 rounded-xl p-4 text-sm focus:ring-2 focus:ring-[#8B0000] focus:border-[#8B0000] resize-none transition"
                                        placeholder="Add examination notes here..."></textarea>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                        Diagnosis
                                    </label>
                                    <textarea id="diagnosisNotes" rows="5"
                                        class="w-full bg-gray-50 border border-gray-100 rounded-xl p-4 text-sm focus:ring-2 focus:ring-[#8B0000] focus:border-[#8B0000] resize-none transition"
                                        placeholder="Add diagnosis here..."></textarea>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                        Prescriptions <span class="normal-case text-gray-400">(Optional)</span>
                                    </label>
                                    <textarea id="prescriptionsNotes" rows="5"
                                        class="w-full bg-gray-50 border border-gray-100 rounded-xl p-4 text-sm focus:ring-2 focus:ring-[#8B0000] focus:border-[#8B0000] resize-none transition"
                                        placeholder="Add prescriptions here..."></textarea>
                                </div>
                            </div>
                        </section>

                        <input type="hidden" id="odontogramData" name="odontogram_data" value="[]">
                    </div>
                </div>

                <div class="odontogram-right-bottom space-y-3">
                    <button type="button" id="followUpBtn"
                        class="w-full flex justify-center items-center gap-2 bg-amber-100 hover:bg-amber-200 text-amber-800 text-sm font-semibold py-2.75 rounded-xl transition shadow-sm border border-amber-200">
                        <i class="fa-solid fa-calendar-plus"></i>
                        Follow-Up Appointment
                    </button>

                    <button type="button" id="finishProcedureBtn"
                        class="w-full flex justify-center items-center gap-2 bg-[#8B0000] hover:bg-[#660000] text-white text-sm font-semibold py-2.75 rounded-xl transition shadow-md">
                        <i class="fa-solid fa-check"></i>
                        Finish Procedure
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="legendDrawerBackdrop" class="fixed inset-0 bg-black/30 z-[60]"></div>

<div id="legendDrawer"
    class="fixed top-0 right-0 h-full w-full sm:w-[460px] bg-white z-[61] shadow-2xl border-l border-gray-200 transition-transform duration-300 flex flex-col">

    <div class="legend-drawer-header px-5 py-4 border-b border-gray-100 bg-white">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-red-50 text-[#8B0000]">
                        <i class="fa-solid fa-layer-group"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-extrabold text-[#8B0000] leading-tight">Treatment Legend</h3>
                        <p class="text-xs text-gray-500">Choose a legend and apply it to the selected target.</p>
                    </div>
                </div>
            </div>

            <button type="button" id="closeLegendDrawerBtn"
                class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 transition shrink-0">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="mt-4">
            <div class="soft-card px-4 py-3 border border-red-100 bg-red-50/40">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Selected Legend</p>
                <p id="drawerSelectedLegendPreview" class="text-sm font-semibold text-[#8B0000]">
                    No legend selected yet.
                </p>
            </div>
        </div>

        <div class="mt-4">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="legendSearchInput"
                    class="w-full pl-10 pr-10 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-[#8B0000] focus:border-[#8B0000]"
                    placeholder="Search legend code or label...">
                <button type="button" id="clearLegendSearchBtn"
                    class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Legend Categories</p>
            <p id="legendResultCount" class="text-xs font-semibold text-gray-500">0 results</p>
        </div>
    </div>

    <div class="p-5 overflow-y-auto custom-scrollbar flex-1">
        <div id="legendContainer" class="space-y-5"></div>
        <div id="legendEmptyState" class="hidden soft-card p-5 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mb-3">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <p class="text-sm font-semibold text-gray-700">No matching legend found.</p>
            <p class="text-xs text-gray-500 mt-1">Try a different keyword or clear the search.</p>
        </div>
    </div>
</div>

<div id="resetTreatmentModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4">
    <div class="absolute inset-0 modal-backdrop"></div>

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 p-6">
        <div class="flex items-start gap-4">
            <div
                class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <div class="flex-1">
                <h3 class="text-lg font-extrabold text-gray-900 mb-2">Reset Tooth Treatment</h3>
                <p id="resetTreatmentMessage" class="text-sm text-gray-600 leading-relaxed">
                    Are you sure you want to reset the treatment for this tooth?
                </p>
            </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <button type="button" id="confirmResetTreatmentBtn"
                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition">
                Yes, Reset
            </button>

            <button type="button" id="cancelResetTreatmentBtn"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<div id="cancelProcedureModal" class="fixed inset-0 z-[80] hidden items-center justify-center p-4">
    <div class="absolute inset-0 modal-backdrop"></div>

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 p-6">
        <div class="flex items-start gap-4">
            <div
                class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>

            <div class="flex-1">
                <h3 class="text-lg font-extrabold text-gray-900 mb-2">Cancel Procedure?</h3>
                <p class="text-sm text-gray-600 leading-relaxed">
                    Are you sure you want to cancel this procedure? Any unsaved progress in this session may be lost.
                </p>
            </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <button type="button" id="confirmCancelProcedureBtn"
                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition">
                Yes, Cancel
            </button>

            <button type="button" id="dismissCancelProcedureBtn"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition">
                No, Stay
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cancelProcedureBtn = document.getElementById('cancelProcedureBtn');
        const cancelProcedureModal = document.getElementById('cancelProcedureModal');
        const confirmCancelProcedureBtn = document.getElementById('confirmCancelProcedureBtn');
        const dismissCancelProcedureBtn = document.getElementById('dismissCancelProcedureBtn');
        const cancelProcedureRedirectUrl = @json(route('dentist.dentist.patient.profile', $patient -> id ?? 1));

        const legendSearchInput = document.getElementById('legendSearchInput');
        const clearLegendSearchBtn = document.getElementById('clearLegendSearchBtn');
        const legendResultCount = document.getElementById('legendResultCount');
        const legendEmptyState = document.getElementById('legendEmptyState');
        const drawerSelectedLegendPreview = document.getElementById('drawerSelectedLegendPreview');

        const procedureTimer = document.getElementById('procedureTimer');
        const container = document.getElementById('canvas-container');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const openLegendDrawerBtn = document.getElementById('openLegendDrawerBtn');
        const closeLegendDrawerBtn = document.getElementById('closeLegendDrawerBtn');
        const legendDrawer = document.getElementById('legendDrawer');
        const legendDrawerBackdrop = document.getElementById('legendDrawerBackdrop');

        const view2dBtn = document.getElementById('view2dBtn');
        const view3dBtn = document.getElementById('view3dBtn');
        const panel2d = document.getElementById('odontogram2DPanel');
        const panel3d = document.getElementById('odontogram3DPanel');
        const board2d = document.getElementById('odontogram2DBoard');
        const viewInstructionText = document.getElementById('viewInstructionText');
        const selectedViewBadge = document.getElementById('selectedViewBadge');

        const selectedToothDisplay = document.getElementById('selectedToothDisplay');
        const selectedToothName = document.getElementById('selectedToothName');
        const toothHoverLabel = document.getElementById('toothHoverLabel');
        const legendContainer = document.getElementById('legendContainer');
        const odontogramDataInput = document.getElementById('odontogramData');
        const selectedLegendPreview = document.getElementById('selectedLegendPreview');
        const selectedToothLegendList = document.getElementById('selectedToothLegendList');
        const legendStatusNote = document.getElementById('legendStatusNote');
        const applyTreatmentBtn = document.getElementById('applyTreatmentBtn');
        const clearCurrentToothBtn = document.getElementById('clearCurrentToothBtn');

        const clearSelectionBtn = document.getElementById('clearSelectionBtn');
        const undoBtn = document.getElementById('undoBtn');
        const redoBtn = document.getElementById('redoBtn');

        const toothTooltip = document.getElementById('toothTooltip');
        const toothTooltipContent = document.getElementById('toothTooltipContent');

        const resetTreatmentModal = document.getElementById('resetTreatmentModal');
        const resetTreatmentMessage = document.getElementById('resetTreatmentMessage');
        const confirmResetTreatmentBtn = document.getElementById('confirmResetTreatmentBtn');
        const cancelResetTreatmentBtn = document.getElementById('cancelResetTreatmentBtn');

        let currentView = '2d';
        let selectedTooth = null;
        let selectedLegend = null;
        let selectedTargetType = null;
        let selectedSurfaceKey = null;
        let selectedMesh = null;
        let hoveredMesh = null;
        let pendingResetPayload = null;

        let historyStack = [];
        let redoStack = [];
        const HISTORY_LIMIT = 50;

        let scene = null;
        let camera = null;
        let renderer = null;
        let controls = null;
        let raycaster = null;
        let mouse = null;
        let teethMeshes = [];
        let threeSceneInitialized = false;

        const legends = [{
            code: 'D',
            label: 'Decayed'
        },
        {
            code: 'M',
            label: 'Missing due to Caries'
        },
        {
            code: 'F',
            label: 'Filled'
        },
        {
            code: 'I',
            label: 'Indicated for Extraction'
        },
        {
            code: 'RF',
            label: 'Root Fragment'
        },
        {
            code: 'MO',
            label: 'Missing due to Other Causes'
        },
        {
            code: 'IM',
            label: 'Impacted Tooth'
        },
        {
            code: 'J',
            label: 'Jacket Crown'
        },
        {
            code: 'A',
            label: 'Amalgam Filling'
        },
        {
            code: 'AB',
            label: 'Abutment'
        },
        {
            code: 'P',
            label: 'Pontic'
        },
        {
            code: 'IN',
            label: 'Inlay'
        },
        {
            code: 'LC',
            label: 'Light Cure Composite'
        },
        {
            code: 'RM',
            label: 'Removable Denture'
        },
        {
            code: 'X',
            label: 'Extraction due to Caries'
        },
        {
            code: 'XO',
            label: 'Extraction due to Other Causes'
        },
        {
            code: 'PT',
            label: 'Present Tooth'
        },
        {
            code: 'CM',
            label: 'Congenitally Missing'
        },
        {
            code: 'SP',
            label: 'Supernumerary'
        }
        ];

        const legendColors = {
            D: '#ef4444',
            M: '#9ca3af',
            F: '#22c55e',
            I: '#f97316',
            RF: '#7c2d12',
            MO: '#6b7280',
            IM: '#9333ea',
            J: '#2563eb',
            A: '#0f766e',
            AB: '#14b8a6',
            P: '#6366f1',
            IN: '#10b981',
            LC: '#22d3ee',
            RM: '#94a3b8',
            X: '#b91c1c',
            XO: '#7f1d1d',
            PT: '#111111',
            CM: '#a1a1aa',
            SP: '#c084fc'
        };

        const legendIcons = {
            D: 'fa-solid fa-bug',
            M: 'fa-solid fa-ban',
            F: 'fa-solid fa-square-check',
            I: 'fa-solid fa-syringe',
            RF: 'fa-solid fa-teeth-open',
            MO: 'fa-solid fa-circle-xmark',
            IM: 'fa-solid fa-triangle-exclamation',
            J: 'fa-solid fa-crown',
            A: 'fa-solid fa-fill-drip',
            AB: 'fa-solid fa-anchor',
            P: 'fa-solid fa-link',
            IN: 'fa-solid fa-puzzle-piece',
            LC: 'fa-solid fa-wand-magic-sparkles',
            RM: 'fa-solid fa-teeth',
            X: 'fa-solid fa-xmark',
            XO: 'fa-solid fa-skull-crossbones',
            PT: 'fa-solid fa-check',
            CM: 'fa-solid fa-question',
            SP: 'fa-solid fa-plus'
        };

        const legendCategories = [{
            key: 'conditions',
            title: 'Conditions & Findings',
            icon: 'fa-solid fa-heart-pulse',
            items: ['D', 'RF', 'IM', 'CM', 'SP', 'PT']
        },
        {
            key: 'missing',
            title: 'Missing / Extraction',
            icon: 'fa-solid fa-ban',
            items: ['M', 'MO', 'X', 'XO', 'I']
        },
        {
            key: 'restorations',
            title: 'Restorations',
            icon: 'fa-solid fa-screwdriver-wrench',
            items: ['F', 'A', 'LC', 'IN', 'J']
        },
        {
            key: 'prosthetics',
            title: 'Prosthetics & Support',
            icon: 'fa-solid fa-link',
            items: ['AB', 'P', 'RM']
        }
        ];

        const odontogramState = {};

        const primaryUpperRight = [55, 54, 53, 52, 51];
        const primaryUpperLeft = [61, 62, 63, 64, 65];

        const adultUpperRight = [18, 17, 16, 15, 14, 13, 12, 11];
        const adultUpperLeft = [21, 22, 23, 24, 25, 26, 27, 28];

        const adultLowerRight = [48, 47, 46, 45, 44, 43, 42, 41];
        const adultLowerLeft = [31, 32, 33, 34, 35, 36, 37, 38];

        const primaryLowerRight = [85, 84, 83, 82, 81];
        const primaryLowerLeft = [71, 72, 73, 74, 75];

        const adultUpper = [...adultUpperRight, ...adultUpperLeft];
        const adultLower = [...adultLowerRight, ...adultLowerLeft];

        function getLegendByCode(code) {
            return legends.find(item => item.code === code) || null;
        }

        function getToothName(toothNumber) {
            const names = {
                18: 'Upper Right Third Molar',
                17: 'Upper Right Second Molar',
                16: 'Upper Right First Molar',
                15: 'Upper Right Second Premolar',
                14: 'Upper Right First Premolar',
                13: 'Upper Right Canine',
                12: 'Upper Right Lateral Incisor',
                11: 'Upper Right Central Incisor',
                21: 'Upper Left Central Incisor',
                22: 'Upper Left Lateral Incisor',
                23: 'Upper Left Canine',
                24: 'Upper Left First Premolar',
                25: 'Upper Left Second Premolar',
                26: 'Upper Left First Molar',
                27: 'Upper Left Second Molar',
                28: 'Upper Left Third Molar',
                48: 'Lower Right Third Molar',
                47: 'Lower Right Second Molar',
                46: 'Lower Right First Molar',
                45: 'Lower Right Second Premolar',
                44: 'Lower Right First Premolar',
                43: 'Lower Right Canine',
                42: 'Lower Right Lateral Incisor',
                41: 'Lower Right Central Incisor',
                31: 'Lower Left Central Incisor',
                32: 'Lower Left Lateral Incisor',
                33: 'Lower Left Canine',
                34: 'Lower Left First Premolar',
                35: 'Lower Left Second Premolar',
                36: 'Lower Left First Molar',
                37: 'Lower Left Second Molar',
                38: 'Lower Left Third Molar',

                55: 'Upper Right Second Molar (Primary)',
                54: 'Upper Right First Molar (Primary)',
                53: 'Upper Right Canine (Primary)',
                52: 'Upper Right Lateral Incisor (Primary)',
                51: 'Upper Right Central Incisor (Primary)',
                61: 'Upper Left Central Incisor (Primary)',
                62: 'Upper Left Lateral Incisor (Primary)',
                63: 'Upper Left Canine (Primary)',
                64: 'Upper Left First Molar (Primary)',
                65: 'Upper Left Second Molar (Primary)',

                85: 'Lower Right Second Molar (Primary)',
                84: 'Lower Right First Molar (Primary)',
                83: 'Lower Right Canine (Primary)',
                82: 'Lower Right Lateral Incisor (Primary)',
                81: 'Lower Right Central Incisor (Primary)',
                71: 'Lower Left Central Incisor (Primary)',
                72: 'Lower Left Lateral Incisor (Primary)',
                73: 'Lower Left Canine (Primary)',
                74: 'Lower Left First Molar (Primary)',
                75: 'Lower Left Second Molar (Primary)',
            };

            return names[toothNumber] || `Tooth #${toothNumber}`;
        }

        function openCancelProcedureModal() {
            cancelProcedureModal.classList.remove('hidden');
            cancelProcedureModal.classList.add('flex');
        }

        function closeCancelProcedureModal() {
            cancelProcedureModal.classList.add('hidden');
            cancelProcedureModal.classList.remove('flex');
        }

        function getSurfaceLabel(surface) {
            const map = {
                top: 'Top Surface',
                bottom: 'Bottom Surface',
                left: 'Left Surface',
                right: 'Right Surface',
                center: 'Center Surface',
                status: 'Status Box',
                whole: 'Whole Tooth'
            };
            return map[surface] || surface;
        }

        function ensureToothState(toothNumber) {
            if (!odontogramState[toothNumber]) {
                odontogramState[toothNumber] = {
                    tooth: toothNumber,
                    toothName: getToothName(toothNumber),
                    status: null,
                    surfaces: {
                        top: null,
                        left: null,
                        center: null,
                        right: null,
                        bottom: null
                    },
                    threeD: null
                };
            }
            return odontogramState[toothNumber];
        }

        function updateHiddenInput() {
            odontogramDataInput.value = JSON.stringify(Object.values(odontogramState));
        }

        function updateLegendActiveState() {
            document.querySelectorAll('.legend-btn').forEach(btn => {
                btn.classList.remove('active');
                if (selectedLegend && btn.dataset.code === selectedLegend) {
                    btn.classList.add('active');
                }
            });
        }

        function getSelectedRecord() {
            if (!selectedTooth || !selectedTargetType) return null;

            const state = ensureToothState(selectedTooth);

            if (selectedTargetType === 'status') return state.status;
            if (selectedTargetType === 'surface' && selectedSurfaceKey) return state.surfaces[
                selectedSurfaceKey];
            if (selectedTargetType === '3d') return state.threeD;

            return null;
        }

        function renderSelectedToothLegendList() {
            const selectedRecord = getSelectedRecord();

            if (!selectedRecord) {
                selectedToothLegendList.innerHTML =
                    '<span class="text-[11px] text-gray-400 italic">No treatment assigned yet.</span>';
                return;
            }

            selectedToothLegendList.innerHTML = `
                    <span class="treatment-chip inline-flex items-center gap-2">
                        <span class="legend-color-dot" style="background:${selectedRecord.colorHex};"></span>
                        ${selectedRecord.code} - ${selectedRecord.label}
                    </span>
                `;
        }

        function updateActionButtons() {
            const hasSelectedTarget = !!selectedTooth && !!selectedTargetType;
            const hasSelectedLegend = !!selectedLegend;
            const hasAssignedTreatment = !!getSelectedRecord();

            applyTreatmentBtn.disabled = !(hasSelectedTarget && hasSelectedLegend);
            clearCurrentToothBtn.disabled = !hasAssignedTreatment;
            updateHistoryButtons();
        }

        function updateSelectedToothUI() {
            if (!selectedTooth || !selectedTargetType) {
                drawerSelectedLegendPreview.textContent = selectedLegend ?
                    `${selectedLegend} - ${(getLegendByCode(selectedLegend)?.label || selectedLegend)}` :
                    'No legend selected yet';

                selectedToothDisplay.textContent = currentView === '2d' ?
                    'Click a tooth surface on the 2D odontogram' :
                    'Click a tooth on the 3D odontogram';

                selectedToothName.textContent = 'No tooth selected';
                toothHoverLabel.innerText = 'Select a tooth';
                legendStatusNote.classList.add('hidden');
                selectedLegend = null;
                selectedViewBadge.textContent = currentView === '2d' ? '2D View' : '3D View';

                updateLegendActiveState();
                renderSelectedToothLegendList();
                updateActionButtons();
                return;
            }

            const surfaceText = selectedTargetType === 'surface' ?
                getSurfaceLabel(selectedSurfaceKey) :
                selectedTargetType === 'status' ?
                    getSurfaceLabel('status') :
                    getSurfaceLabel('whole');

            selectedToothDisplay.textContent = `Tooth #${selectedTooth} • ${surfaceText}`;
            selectedToothName.textContent = getToothName(selectedTooth);
            toothHoverLabel.innerText = `Selected: #${selectedTooth}`;
            selectedViewBadge.textContent = currentView === '2d' ? '2D View' : '3D View';
            legendStatusNote.classList.remove('hidden');

            const record = getSelectedRecord();
            selectedLegend = record ? record.code : null;

            updateLegendActiveState();
            renderSelectedToothLegendList();
            updateActionButtons();
        }

        function openLegendDrawer() {
            legendSearchInput.value = '';
            clearLegendSearchBtn.classList.add('hidden');
            renderLegendButtons('');
            legendDrawer.classList.add('open');
            legendDrawerBackdrop.classList.add('show');
            document.body.classList.add('legend-drawer-open');
        }

        function closeLegendDrawer() {
            legendDrawer.classList.remove('open');
            legendDrawerBackdrop.classList.remove('show');
            document.body.classList.remove('legend-drawer-open');
        }

        openLegendDrawerBtn.addEventListener('click', openLegendDrawer);
        closeLegendDrawerBtn.addEventListener('click', closeLegendDrawer);
        legendDrawerBackdrop.addEventListener('click', closeLegendDrawer);

        legendSearchInput.addEventListener('input', function () {
            const value = legendSearchInput.value.trim();
            clearLegendSearchBtn.classList.toggle('hidden', value.length === 0);
            renderLegendButtons(value);
        });

        clearLegendSearchBtn.addEventListener('click', function () {
            legendSearchInput.value = '';
            clearLegendSearchBtn.classList.add('hidden');
            renderLegendButtons('');
            legendSearchInput.focus();
        });

        function renderLegendButtons(searchTerm = '') {
            legendContainer.innerHTML = '';

            const normalizedSearch = searchTerm.trim().toLowerCase();
            let totalResults = 0;

            legendCategories.forEach(category => {
                const categoryLegends = category.items
                    .map(code => legends.find(item => item.code === code))
                    .filter(Boolean)
                    .filter(legend => {
                        if (!normalizedSearch) return true;
                        return (
                            legend.code.toLowerCase().includes(normalizedSearch) ||
                            legend.label.toLowerCase().includes(normalizedSearch)
                        );
                    });

                if (!categoryLegends.length) return;

                totalResults += categoryLegends.length;

                const categoryBlock = document.createElement('div');
                categoryBlock.className = 'legend-category-block';

                categoryBlock.innerHTML = `
                        <div class="legend-category-head">
                            <div class="legend-category-title">
                                <span class="legend-category-icon">
                                    <i class="${category.icon}"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">${category.title}</p>
                                    <p class="text-[11px] text-gray-500">Choose a legend from this group</p>
                                </div>
                            </div>
                            <span class="legend-category-count">${categoryLegends.length}</span>
                        </div>
                    `;

                const body = document.createElement('div');
                body.className = 'legend-category-body';

                categoryLegends.forEach(legend => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.dataset.code = legend.code;
                    btn.className = 'legend-btn';

                    btn.innerHTML = `
                            <span class="legend-color-dot" style="background:${legendColors[legend.code]};"></span>
                            <i class="${legendIcons[legend.code]} text-[#8B0000] w-4 text-center"></i>
                            <span class="legend-meta">
                                <span class="legend-code">${legend.code}</span>
                                <span class="legend-label">${legend.label}</span>
                            </span>
                        `;

                    btn.addEventListener('click', function () {
                        if (!selectedTooth || !selectedTargetType) {
                            alert('Please select a tooth target first.');
                            return;
                        }

                        selectedLegend = legend.code;
                        selectedLegendPreview.textContent =
                            `${legend.code} - ${legend.label}`;
                        drawerSelectedLegendPreview.textContent =
                            `${legend.code} - ${legend.label}`;
                        updateLegendActiveState();
                        updateActionButtons();
                    });

                    body.appendChild(btn);
                });

                categoryBlock.appendChild(body);
                legendContainer.appendChild(categoryBlock);
            });

            legendResultCount.textContent = `${totalResults} result${totalResults === 1 ? '' : 's'}`;

            if (totalResults === 0) {
                legendEmptyState.classList.remove('hidden');
            } else {
                legendEmptyState.classList.add('hidden');
            }

            updateLegendActiveState();
        }

        function createLegendPayload(code) {
            const legendObj = getLegendByCode(code);
            return {
                code: code,
                label: legendObj ? legendObj.label : code,
                colorHex: legendColors[code] || '#ef4444'
            };
        }

        function isAdultTooth(toothNumber) {
            return adultUpper.includes(toothNumber) || adultLower.includes(toothNumber);
        }

        function getPreferredToothVisual(state) {
            if (!state) return null;

            if (state.status) return state.status;

            const surfacePriority = ['center', 'top', 'right', 'bottom', 'left'];
            for (const key of surfacePriority) {
                if (state.surfaces[key]) return state.surfaces[key];
            }

            return null;
        }

        function sync3DFrom2D(toothNumber) {
            const state = ensureToothState(toothNumber);

            if (!isAdultTooth(toothNumber)) {
                state.threeD = null;
                return;
            }

            state.threeD = getPreferredToothVisual(state);
        }

        function fillAll2DSurfacesFromLegend(state, payload) {
            state.status = payload;
            state.surfaces.top = payload;
            state.surfaces.left = payload;
            state.surfaces.center = payload;
            state.surfaces.right = payload;
            state.surfaces.bottom = payload;
        }

        function clearAll2DSurfaces(state) {
            state.status = null;
            state.surfaces.top = null;
            state.surfaces.left = null;
            state.surfaces.center = null;
            state.surfaces.right = null;
            state.surfaces.bottom = null;
        }

        function snapshotState() {
            return JSON.stringify(odontogramState);
        }

        function restoreStateFromSnapshot(snapshot) {
            const parsed = JSON.parse(snapshot);

            Object.keys(odontogramState).forEach(key => delete odontogramState[key]);
            Object.keys(parsed).forEach(key => {
                odontogramState[key] = parsed[key];
            });

            updateHiddenInput();
            render2DOdontogram();

            if (threeSceneInitialized) {
                renderThreeVisuals();
            }

            updateSelectedToothUI();
            updateLegendActiveState();
            renderSelectedToothLegendList();
            updateActionButtons();
            updateHistoryButtons();
        }

        function pushHistory() {
            historyStack.push(snapshotState());
            if (historyStack.length > HISTORY_LIMIT) {
                historyStack.shift();
            }
            redoStack = [];
            updateHistoryButtons();
        }

        function updateHistoryButtons() {
            if (undoBtn) undoBtn.disabled = historyStack.length === 0;
            if (redoBtn) redoBtn.disabled = redoStack.length === 0;
            if (clearSelectionBtn) clearSelectionBtn.disabled = !(selectedTooth && selectedTargetType);
        }

        function clearCurrentSelection() {
            selectedTooth = null;
            selectedTargetType = null;
            selectedSurfaceKey = null;
            selectedMesh = null;
            selectedLegend = null;

            render2DOdontogram();

            if (threeSceneInitialized) {
                renderThreeVisuals();
            }

            updateSelectedToothUI();
            updateLegendActiveState();
            updateActionButtons();
            updateHistoryButtons();
        }

        function undoLastAction() {
            if (!historyStack.length) return;

            redoStack.push(snapshotState());
            const previous = historyStack.pop();
            restoreStateFromSnapshot(previous);
        }

        function redoLastAction() {
            if (!redoStack.length) return;

            historyStack.push(snapshotState());
            const next = redoStack.pop();
            restoreStateFromSnapshot(next);
        }

        function applyLegendToSelectedTarget(code) {
            if (!selectedTooth || !selectedTargetType) return;
            pushHistory();

            const state = ensureToothState(selectedTooth);
            const payload = createLegendPayload(code);

            if (selectedTargetType === 'status') {
                state.status = payload;
                sync3DFrom2D(selectedTooth);
            } else if (selectedTargetType === 'surface' && selectedSurfaceKey) {
                state.surfaces[selectedSurfaceKey] = payload;
                sync3DFrom2D(selectedTooth);
            } else if (selectedTargetType === '3d') {
                state.threeD = payload;
                fillAll2DSurfacesFromLegend(state, payload);

                if (selectedMesh) {
                    selectedMesh.material.color.setStyle(payload.colorHex);
                    selectedMesh.userData.legend = code;
                    selectedMesh.userData.originalColor = payload.colorHex;
                    applySelectedMeshState(selectedMesh);
                }
            }

            updateHiddenInput();
            render2DOdontogram();

            if (threeSceneInitialized) {
                renderThreeVisuals();
            }

            renderSelectedToothLegendList();
            updateLegendActiveState();
            updateActionButtons();
            selectedLegendPreview.textContent = `${payload.code} - ${payload.label}`;
            drawerSelectedLegendPreview.textContent = `${payload.code} - ${payload.label}`;
            closeLegendDrawer();
        }

        function clearSelectedTargetTreatment() {
            if (!pendingResetPayload) return;
            pushHistory();

            const {
                tooth,
                targetType,
                surfaceKey
            } = pendingResetPayload;
            const state = ensureToothState(tooth);

            if (targetType === 'status') {
                state.status = null;
                sync3DFrom2D(tooth);
            } else if (targetType === 'surface' && surfaceKey) {
                state.surfaces[surfaceKey] = null;
                sync3DFrom2D(tooth);
            } else if (targetType === '3d') {
                state.threeD = null;
                clearAll2DSurfaces(state);

                if (selectedMesh && Number(selectedMesh.userData.tooth) === Number(tooth)) {
                    selectedMesh.material.color.setStyle('#FFFFF0');
                    selectedMesh.userData.originalColor = '#FFFFF0';
                    delete selectedMesh.userData.legend;
                    applySelectedMeshState(selectedMesh);
                }
            }

            selectedLegend = null;
            pendingResetPayload = null;

            selectedLegendPreview.textContent = 'No legend selected yet.';
            drawerSelectedLegendPreview.textContent = 'No legend selected yet.';

            updateHiddenInput();
            render2DOdontogram();

            if (threeSceneInitialized) {
                renderThreeVisuals();
            }

            renderSelectedToothLegendList();
            updateLegendActiveState();
            updateActionButtons();
            closeResetModal();
        }

        function openResetModal() {
            if (!selectedTooth || !selectedTargetType || !getSelectedRecord()) return;

            pendingResetPayload = {
                tooth: selectedTooth,
                targetType: selectedTargetType,
                surfaceKey: selectedSurfaceKey
            };

            const partText = selectedTargetType === 'surface' ?
                getSurfaceLabel(selectedSurfaceKey) :
                selectedTargetType === 'status' ?
                    'Status Box' :
                    'Whole Tooth';

            resetTreatmentMessage.textContent =
                `Are you sure you want to reset the treatment for tooth #${selectedTooth} (${getToothName(selectedTooth)}) - ${partText}?`;

            resetTreatmentModal.classList.remove('hidden');
            resetTreatmentModal.classList.add('flex');
        }

        function closeResetModal() {
            pendingResetPayload = null;
            resetTreatmentModal.classList.add('hidden');
            resetTreatmentModal.classList.remove('flex');
        }

        function createToothUnit(toothNumber, statusPlacement = 'top') {
            const state = ensureToothState(toothNumber);

            const wrap = document.createElement('div');
            wrap.className = 'tooth-unit';

            const statusBox = document.createElement('div');
            statusBox.className = 'status-box';
            statusBox.dataset.tooth = toothNumber;
            statusBox.dataset.target = 'status';

            if (state.status) {
                statusBox.style.background = state.status.colorHex;
                statusBox.style.borderColor = state.status.colorHex;
            }
            if (selectedTooth === toothNumber && selectedTargetType === 'status') {
                statusBox.classList.add('selected-target');
            }

            statusBox.addEventListener('click', function () {
                currentView = '2d';
                selectedTooth = toothNumber;
                selectedTargetType = 'status';
                selectedSurfaceKey = null;
                updateSelectedToothUI();
                render2DOdontogram();
            });

            const toothNumberEl = document.createElement('div');
            toothNumberEl.className = 'tooth-number';
            toothNumberEl.textContent = toothNumber;

            const toothFace = document.createElement('div');
            toothFace.className = 'tooth-2d-wrapper';

            toothFace.innerHTML = `
                    <svg viewBox="0 0 100 100" class="tooth-svg" preserveAspectRatio="xMidYMid meet">
                        <path class="surface-part surface-top" data-surface="top" d="M 0 0 L 100 0 L 50 50 Z" />
                        <path class="surface-part surface-right" data-surface="right" d="M 100 0 L 100 100 L 50 50 Z" />
                        <path class="surface-part surface-bottom" data-surface="bottom" d="M 100 100 L 0 100 L 50 50 Z" />
                        <path class="surface-part surface-left" data-surface="left" d="M 0 100 L 0 0 L 50 50 Z" />
                        <circle class="surface-part surface-center" data-surface="center" cx="50" cy="50" r="22" />
                    </svg>
                `;

            const surfaces = toothFace.querySelectorAll('.surface-part');
            surfaces.forEach(part => {
                const surface = part.dataset.surface;
                const surfaceRecord = state.surfaces[surface];

                if (surfaceRecord) {
                    part.style.fill = surfaceRecord.colorHex;
                }

                if (selectedTooth === toothNumber && selectedTargetType === 'surface' &&
                    selectedSurfaceKey === surface) {
                    part.classList.add('selected-target');
                }

                part.addEventListener('click', function () {
                    currentView = '2d';
                    selectedTooth = toothNumber;
                    selectedTargetType = 'surface';
                    selectedSurfaceKey = surface;
                    updateSelectedToothUI();
                    render2DOdontogram();
                });

                part.addEventListener('mouseenter', function () {
                    toothHoverLabel.innerText = `Tooth #${toothNumber}`;
                });

                part.addEventListener('mouseleave', function () {
                    toothHoverLabel.innerText = selectedTooth ? `Selected: #${selectedTooth}` :
                        'Select a tooth';
                });
            });

            if (statusPlacement === 'top') {
                wrap.appendChild(statusBox);
                wrap.appendChild(toothNumberEl);
                wrap.appendChild(toothFace);
            } else {
                wrap.appendChild(toothFace);
                wrap.appendChild(toothNumberEl);
                wrap.appendChild(statusBox);
            }

            return wrap;
        }

        function createRow(leftTeeth, rightTeeth, statusPlacement = 'top', leftLabel = '', rightLabel = '') {
            const row = document.createElement('div');
            row.className = 'odontogram-row';

            const left = document.createElement('div');
            left.className = 'status-label-left';
            left.innerHTML = leftLabel ? leftLabel : '&nbsp;';

            if (statusPlacement === 'top') {
                left.style.paddingTop = '10px';
            } else {
                left.style.alignSelf = 'flex-end';
                left.style.paddingBottom = '10px';
            }
            row.appendChild(left);

            leftTeeth.forEach(tooth => row.appendChild(createToothUnit(tooth, statusPlacement)));

            const spacer = document.createElement('div');
            spacer.className = 'w-3 md:w-5';
            row.appendChild(spacer);

            rightTeeth.forEach(tooth => row.appendChild(createToothUnit(tooth, statusPlacement)));

            const right = document.createElement('div');
            right.className = 'status-label-right';
            right.innerHTML = rightLabel ? rightLabel : '&nbsp;';

            if (statusPlacement === 'top') {
                right.style.paddingTop = '10px';
            } else {
                right.style.alignSelf = 'flex-end';
                right.style.paddingBottom = '10px';
            }
            row.appendChild(right);

            return row;
        }

        function render2DOdontogram() {
            board2d.innerHTML = '';

            const row1 = createRow(primaryUpperRight, primaryUpperLeft, 'top', 'STATUS<br>RIGHT', 'LEFT');
            row1.classList.add('arch-divider');
            board2d.appendChild(row1);

            const row2 = createRow(adultUpperRight, adultUpperLeft, 'top', '', '');
            row2.style.marginBottom = '24px';
            board2d.appendChild(row2);

            const row3 = createRow(adultLowerRight, adultLowerLeft, 'bottom', '', '');
            row3.classList.add('arch-divider');
            board2d.appendChild(row3);

            const row4 = createRow(primaryLowerRight, primaryLowerLeft, 'bottom', 'STATUS<br>RIGHT', 'LEFT');
            board2d.appendChild(row4);
        }

        function switchView(view) {
            currentView = view;

            if (view === '2d') {
                selectedMesh = null;
                panel2d.classList.add('active');
                panel3d.classList.remove('active');
                view2dBtn.classList.add('active');
                view3dBtn.classList.remove('active');
                viewInstructionText.textContent =
                    'Click the tooth surfaces or status box to assign a condition';
            } else {
                panel2d.classList.remove('active');
                panel3d.classList.add('active');
                view2dBtn.classList.remove('active');
                view3dBtn.classList.add('active');
                viewInstructionText.textContent = 'Click a tooth in the 3D model to assign a condition';

                if (!threeSceneInitialized) {
                    initThreeScene();
                } else if (renderer && camera) {
                    handleResize();
                    renderThreeVisuals();
                }
            }

            if (view === '3d' && threeSceneInitialized && selectedTooth) {
                selectedMesh = teethMeshes.find(mesh => Number(mesh.userData.tooth) === Number(
                    selectedTooth)) || null;
                renderThreeVisuals();
            }

            updateSelectedToothUI();
        }

        view2dBtn.addEventListener('click', () => switchView('2d'));
        view3dBtn.addEventListener('click', () => switchView('3d'));

        function initThreeScene() {
            const width = container.clientWidth || 700;
            const height = container.clientHeight || 480;

            scene = new THREE.Scene();
            scene.background = new THREE.Color('#F1F5F9');

            camera = new THREE.PerspectiveCamera(40, width / height, 0.1, 1000);
            camera.position.set(0, 10, 14);

            renderer = new THREE.WebGLRenderer({
                antialias: true,
                alpha: false
            });
            renderer.setPixelRatio(window.devicePixelRatio);
            renderer.setSize(width, height);
            renderer.shadowMap.enabled = true;
            renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            container.appendChild(renderer.domElement);

            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.07;
            controls.minDistance = 5;
            controls.maxDistance = 30;
            controls.maxPolarAngle = Math.PI / 1.8;

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.75);
            scene.add(ambientLight);

            const keyLight = new THREE.DirectionalLight(0xffffff, 0.8);
            keyLight.position.set(10, 15, 10);
            keyLight.castShadow = true;
            keyLight.shadow.mapSize.width = 1024;
            keyLight.shadow.mapSize.height = 1024;
            scene.add(keyLight);

            const backLight = new THREE.DirectionalLight(0xffffff, 0.45);
            backLight.position.set(-10, 5, -10);
            scene.add(backLight);

            const fillLight = new THREE.DirectionalLight(0xffffff, 0.35);
            fillLight.position.set(0, 8, 12);
            scene.add(fillLight);

            const toothGeometry = new THREE.CylinderGeometry(0.38, 0.22, 1.0, 32, 1);

            const enamelMaterialProps = {
                color: 0xFFFFF0,
                metalness: 0.05,
                roughness: 0.28,
                emissive: 0x111111,
                envMapIntensity: 1.0
            };

            function createArch(teethArray, yPosition) {
                const group = new THREE.Group();

                teethArray.forEach((toothNum, i) => {
                    const material = new THREE.MeshStandardMaterial(enamelMaterialProps);
                    const mesh = new THREE.Mesh(toothGeometry, material);
                    mesh.castShadow = true;
                    mesh.receiveShadow = true;

                    const angle = Math.PI - (i / (teethArray.length - 1)) * Math.PI;
                    const x = Math.cos(angle) * 4.0;
                    const z = Math.sin(angle) * 3.5;

                    mesh.position.set(x, yPosition, z);
                    mesh.lookAt(0, yPosition, 0);

                    mesh.userData = {
                        tooth: toothNum,
                        originalColor: '#FFFFF0'
                    };

                    group.add(mesh);
                    teethMeshes.push(mesh);
                });

                scene.add(group);
            }

            createArch(adultUpper, 0.6);
            createArch(adultLower, -0.6);

            const gumGeometry = new THREE.TorusGeometry(3.9, 0.5, 12, 50, Math.PI);
            const gumMaterial = new THREE.MeshStandardMaterial({
                color: 0xF5B7B1,
                roughness: 0.85,
                metalness: 0.0
            });

            const upperGums = new THREE.Mesh(gumGeometry, gumMaterial);
            upperGums.rotation.x = Math.PI / 2;
            upperGums.position.set(0, 1.1, 0);
            upperGums.receiveShadow = true;
            scene.add(upperGums);

            const lowerGums = new THREE.Mesh(gumGeometry, gumMaterial);
            lowerGums.rotation.x = Math.PI / 2;
            lowerGums.position.set(0, -1.1, 0);
            lowerGums.receiveShadow = true;
            scene.add(lowerGums);

            raycaster = new THREE.Raycaster();
            mouse = new THREE.Vector2();

            renderer.domElement.addEventListener('pointermove', onThreePointerMove);
            renderer.domElement.addEventListener('pointerleave', onThreePointerLeave);
            renderer.domElement.addEventListener('pointerdown', onThreePointerDown);

            function animate() {
                requestAnimationFrame(animate);
                controls.update();
                renderer.render(scene, camera);
            }

            animate();

            setTimeout(() => {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }, 600);

            threeSceneInitialized = true;
            renderThreeVisuals();
        }

        function handleResize() {
            if (!renderer || !camera) return;
            const newWidth = container.clientWidth || 700;
            const newHeight = container.clientHeight || 480;
            camera.aspect = newWidth / newHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(newWidth, newHeight);
        }

        function renderThreeVisuals() {
            if (!teethMeshes.length) return;

            teethMeshes.forEach(mesh => {
                const toothId = mesh.userData.tooth;
                const state = ensureToothState(toothId);

                mesh.scale.set(1, 1, 1);
                mesh.material.emissive.setHex(0x111111);
                mesh.material.emissiveIntensity = 0.12;

                if (state.threeD) {
                    mesh.material.color.setStyle(state.threeD.colorHex);
                    mesh.userData.originalColor = state.threeD.colorHex;
                    mesh.userData.legend = state.threeD.code;
                } else {
                    mesh.material.color.setStyle('#FFFFF0');
                    mesh.userData.originalColor = '#FFFFF0';
                    delete mesh.userData.legend;
                }
            });

            if (selectedMesh && selectedTargetType === '3d') {
                applySelectedMeshState(selectedMesh);
            }
        }

        function applySelectedMeshState(mesh) {
            if (!mesh) return;
            mesh.scale.set(1.18, 1.18, 1.18);
            mesh.material.emissive.setHex(0x8B0000);
            mesh.material.emissiveIntensity = 0.25;
        }

        function updateMousePosition(event) {
            const rect = renderer.domElement.getBoundingClientRect();
            mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        }

        function showTooltip(event, mesh) {
            const toothNumber = mesh.userData.tooth;
            const toothName = getToothName(toothNumber);
            const treatment = ensureToothState(toothNumber).threeD;

            toothTooltipContent.innerHTML = `
                    <div class="text-xs font-extrabold tracking-wide text-red-200 mb-1">Tooth #${toothNumber}</div>
                    <div class="text-sm font-bold leading-tight">${toothName}</div>
                    <div class="mt-2 text-xs ${treatment ? 'text-emerald-200' : 'text-gray-300'}">
                        ${treatment ? `${treatment.code} - ${treatment.label}` : 'No treatment assigned'}
                    </div>
                `;

            const rect = container.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            toothTooltip.style.left = `${x}px`;
            toothTooltip.style.top = `${y}px`;
            toothTooltip.classList.add('show');
        }

        function hideTooltip() {
            toothTooltip.classList.remove('show');
        }

        function onThreePointerMove(event) {
            updateMousePosition(event);
            raycaster.setFromCamera(mouse, camera);

            const intersects = raycaster.intersectObjects(teethMeshes);

            if (intersects.length > 0) {
                hoveredMesh = intersects[0].object;
                const hoveredToothNumber = hoveredMesh.userData.tooth;
                toothHoverLabel.innerText = selectedTooth && selectedTargetType === '3d' ?
                    `Selected: #${selectedTooth}` :
                    `Tooth #${hoveredToothNumber}`;

                showTooltip(event, hoveredMesh);
            } else {
                hoveredMesh = null;
                toothHoverLabel.innerText = selectedTooth ? `Selected: #${selectedTooth}` : 'Select a tooth';
                hideTooltip();
            }
        }

        function onThreePointerLeave() {
            hoveredMesh = null;
            toothHoverLabel.innerText = selectedTooth ? `Selected: #${selectedTooth}` : 'Select a tooth';
            hideTooltip();
        }

        function onThreePointerDown(event) {
            updateMousePosition(event);
            raycaster.setFromCamera(mouse, camera);

            const intersects = raycaster.intersectObjects(teethMeshes);

            if (intersects.length > 0) {
                selectedMesh = intersects[0].object;
                selectedTooth = selectedMesh.userData.tooth;
                selectedTargetType = '3d';
                selectedSurfaceKey = null;

                const state = ensureToothState(selectedTooth);
                selectedLegend = state.threeD ? state.threeD.code : null;

                renderThreeVisuals();
                updateSelectedToothUI();

                if (hoveredMesh) {
                    showTooltip(event, hoveredMesh);
                }
            }
        }

        const procedureStartTimestamp = Date.now();

        function formatElapsedTime(totalSeconds) {
            const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            return `${hours}:${minutes}:${seconds}`;
        }

        function updateProcedureTimer() {
            const elapsedSeconds = Math.floor((Date.now() - procedureStartTimestamp) / 1000);
            procedureTimer.textContent = formatElapsedTime(elapsedSeconds);
        }

        applyTreatmentBtn.addEventListener('click', function () {
            if (!selectedTooth || !selectedTargetType) {
                alert('Please select a tooth target first.');
                selectedLegendPreview.textContent = selectedLegend ?
                    `${selectedLegend} - ${(getLegendByCode(selectedLegend)?.label || selectedLegend)}` :
                    'No legend selected yet.';
                return;
            }

            if (!selectedLegend) {
                alert('Please choose a treatment legend first.');
                return;
            }

            applyLegendToSelectedTarget(selectedLegend);
        });

        clearCurrentToothBtn.addEventListener('click', function () {
            if (!selectedTooth || !selectedTargetType || !getSelectedRecord()) return;
            openResetModal();
        });

        confirmResetTreatmentBtn.addEventListener('click', clearSelectedTargetTreatment);
        cancelResetTreatmentBtn.addEventListener('click', closeResetModal);

        resetTreatmentModal.addEventListener('click', function (event) {
            if (event.target === resetTreatmentModal || event.target.classList.contains(
                'modal-backdrop')) {
                closeResetModal();
            }
        });

        window.addEventListener('resize', handleResize);

        document.getElementById('finishProcedureBtn').addEventListener('click', async function () {
            const finishBtn = this;
            const originalButtonHtml = finishBtn.innerHTML;

            const payload = {
                odontogram_data: JSON.parse(odontogramDataInput.value || '[]'),
                oral_examination: document.getElementById('oralExaminationNotes').value,
                diagnosis: document.getElementById('diagnosisNotes').value,
                prescriptions: document.getElementById('prescriptionsNotes').value,
            };

            finishBtn.disabled = true;
            finishBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving Procedure...';

            try {
                const response = await fetch(@json(route('dentist.odontogram.save', $patient->id)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();

                if (!response.ok) {
                    console.error(result);
                    alert(result.message || 'Failed to save odontogram. Please check your inputs and try again.');
                    return;
                }

                alert(result.message || 'Odontogram saved successfully.');

                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                }
            } catch (error) {
                console.error(error);
                alert('Something went wrong while saving the odontogram.');
            } finally {
                finishBtn.disabled = false;
                finishBtn.innerHTML = originalButtonHtml;
            }
        });

        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', clearCurrentSelection);
        }

        if (undoBtn) {
            undoBtn.addEventListener('click', undoLastAction);
        }

        if (redoBtn) {
            redoBtn.addEventListener('click', redoLastAction);
        }

        function handleHistoryKeyboardShortcuts(event) {
            const isTyping =
                event.target &&
                (
                    event.target.tagName === 'INPUT' ||
                    event.target.tagName === 'TEXTAREA' ||
                    event.target.isContentEditable
                );

            if (isTyping) return;

            const key = event.key.toLowerCase();
            const isCtrlOrCmd = event.ctrlKey || event.metaKey;

            if (!isCtrlOrCmd) return;

            if (key === 'z' && !event.shiftKey) {
                event.preventDefault();
                undoLastAction();
                return;
            }

            if (key === 'y' || (key === 'z' && event.shiftKey)) {
                event.preventDefault();
                redoLastAction();
            }
        }

        if (cancelProcedureBtn) {
            cancelProcedureBtn.addEventListener('click', openCancelProcedureModal);
        }

        if (dismissCancelProcedureBtn) {
            dismissCancelProcedureBtn.addEventListener('click', closeCancelProcedureModal);
        }

        if (confirmCancelProcedureBtn) {
            confirmCancelProcedureBtn.addEventListener('click', function () {
                window.location.href = cancelProcedureRedirectUrl;
            });
        }

        if (cancelProcedureModal) {
            cancelProcedureModal.addEventListener('click', function (event) {
                if (event.target === cancelProcedureModal || event.target.classList.contains('modal-backdrop')) {
                    closeCancelProcedureModal();
                }
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeResetModal();
                closeCancelProcedureModal();
            }
        });
        updateProcedureTimer();
        setInterval(updateProcedureTimer, 1000);
        renderLegendButtons('');
        render2DOdontogram();
        updateSelectedToothUI();
        updateHistoryButtons();

        document.addEventListener('keydown', handleHistoryKeyboardShortcuts);
    });
</script>
@endsection