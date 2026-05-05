@extends('layouts.patient')

@section('title', 'PUP Taguig Dental Clinic | Appointment')

@section('styles')
<style>
    @keyframes fadeUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-up {
        animation: fadeUp 0.6s ease-out forwards;
    }

    @keyframes apptSlideUp {
        from {
            opacity: 0;
            transform: translateY(14px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes apptPulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.35;
        }
    }

    #mainContent {
        margin-left: 220px;
        transition: margin-left .3s ease;
    }

    @media (max-width: 767px) {
        #mainContent {
            margin-left: 0 !important;
            padding-bottom: 110px;
        }
    }

    .appt-tab.appt-active {
        background: #8B0000;
        color: white;
        box-shadow: 0 4px 12px rgba(139, 0, 0, 0.2);
    }

    .appt-tab.appt-active .appt-count {
        background: rgba(255, 255, 255, 0.25);
        color: white;
    }

    .services-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .service-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        border: 1px solid #f3d6d6;
        background: linear-gradient(135deg, #fff7f7 0%, #fff 100%);
        padding: 1rem 1rem 0.95rem;
        min-height: 150px;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        box-shadow: 0 10px 24px rgba(139, 0, 0, 0.06);
    }

    .service-card:hover {
        transform: translateY(-2px);
        border-color: #e8b4b4;
        box-shadow: 0 14px 30px rgba(139, 0, 0, 0.12);
    }

    .service-card-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #8B0000, #660000);
        box-shadow: 0 10px 20px rgba(139, 0, 0, 0.18);
        margin-bottom: 0.85rem;
    }

    .service-card-icon img {
        width: 1.65rem;
        height: 1.65rem;
        object-fit: contain;
        filter: brightness(0) invert(1);
        opacity: 0.96;
    }

    .service-card-title {
        font-size: 1.15rem;
        line-height: 1.2;
        font-weight: 800;
        color: #7a0000;
        margin-bottom: 0.35rem;
    }

    .service-card-desc {
        font-size: 0.88rem;
        line-height: 1.5;
        color: #6b7280;
        max-width: 100%;
    }

    .service-card-tag {
        display: inline-flex;
        align-items: center;
        margin-top: 0.85rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        background: #fff1f1;
        color: #8B0000;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border: 1px solid #f3d6d6;
    }

    @media (max-width: 767px) {
        .services-grid {
            grid-template-columns: 1fr;
            gap: 0.8rem;
        }

        .service-card {
            min-height: unset;
            padding: 0.9rem;
            border-radius: 1rem;
        }

        .service-card-icon {
            width: 2.65rem;
            height: 2.65rem;
            border-radius: 0.85rem;
            margin-bottom: 0.7rem;
        }

        .service-card-icon img {
            width: 1.45rem;
            height: 1.45rem;
        }

        .service-card-title {
            font-size: 1rem;
        }

        .service-card-desc {
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .service-card-tag {
            margin-top: 0.7rem;
            font-size: 0.65rem;
            padding: 0.3rem 0.58rem;
        }
    }

    .appt-calendar-side {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .appt-calendar-side-card {
        border-radius: 1rem;
        border: 1px solid #f1e4e4;
        background: linear-gradient(145deg, #fffefe, #fff7f7);
        padding: 1rem;
        box-shadow: 0 8px 22px rgba(139, 0, 0, 0.06);
    }

    .appt-calendar-side-stat {
        border-radius: 0.85rem;
        border: 1px solid #f2e8e8;
        background: #ffffff;
        padding: 0.7rem 0.85rem;
    }

    .appt-mini-chart {
        margin-top: 0.75rem;
        border-radius: 0.9rem;
        border: 1px solid #f2e8e8;
        background: #ffffff;
        padding: 0.7rem 0.8rem;
    }

    .appt-mini-chart canvas {
        width: 100% !important;
        height: 150px !important;
    }

    .appt-mini-chart-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 0.45rem;
        align-items: end;
    }

    .appt-mini-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }

    .appt-mini-bars {
        height: 76px;
        width: 100%;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: 3px;
    }

    .appt-mini-bar {
        width: 8px;
        border-radius: 999px;
    }

    .appt-mini-bar-completed {
        background: linear-gradient(180deg, #10b981, #059669);
    }

    .appt-mini-bar-cancelled {
        background: linear-gradient(180deg, #f97316, #ea580c);
    }

    .appt-quick-actions {
        margin-top: 0.95rem;
        border-radius: 1rem;
        border: 1px solid #f1e4e4;
        background: linear-gradient(145deg, #fffefe, #fff7f7);
        padding: 0.8rem;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.6rem;
    }

    .appt-quick-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        border-radius: 0.8rem;
        border: 1px solid #f1d7d7;
        padding: 0.65rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 800;
        color: #7a0000;
        background: #fff;
        transition: all 0.18s ease;
    }

    .appt-quick-btn:hover {
        transform: translateY(-1px);
        border-color: #d79a9a;
        background: #fff4f4;
    }

    [data-theme="dark"] .appt-calendar-side-card {
        border-color: rgba(255, 255, 255, 0.1);
        background: linear-gradient(145deg, rgba(13, 17, 23, 0.9), rgba(32, 11, 11, 0.9));
    }

    [data-theme="dark"] .appt-calendar-side-stat {
        border-color: rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.03);
    }

    [data-theme="dark"] .appt-mini-chart {
        border-color: rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.03);
    }

    [data-theme="dark"] .appt-quick-actions {
        border-color: rgba(255, 255, 255, 0.1);
        background: linear-gradient(145deg, rgba(13, 17, 23, 0.9), rgba(32, 11, 11, 0.9));
    }

    [data-theme="dark"] .appt-quick-btn {
        border-color: rgba(252, 165, 165, 0.25);
        color: #fecaca;
        background: rgba(255, 255, 255, 0.03);
    }

    [data-theme="dark"] .appt-quick-btn:hover {
        border-color: rgba(252, 165, 165, 0.4);
        background: rgba(127, 29, 29, 0.24);
    }

    @media (max-width: 1023px) {
        .appt-quick-actions {
            grid-template-columns: 1fr;
        }
    }

    @media only screen and (min-width: 1200px) {
        #calendarSkeletonContainer {
            min-height: 100%;
        }

        .appt-calendar-side {
            min-height: 100%;
        }
    }

    dialog#appt_detail_modal::backdrop {
        background: rgba(16, 16, 16, .45);
    }

    @keyframes apptFloat {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-6px);
        }
    }

    @keyframes apptGlowPulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(139, 0, 0, .32);
        }

        50% {
            box-shadow: 0 0 0 14px rgba(139, 0, 0, 0);
        }
    }

    @keyframes apptSoftReveal {
        from {
            opacity: 0;
            transform: translateY(14px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .appt-section-reveal {
        animation: apptSoftReveal .55s ease both;
    }

    .appt-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .9rem;
    }

    .appt-summary-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.1rem;
        padding: 0.85rem 0.95rem;
        background:
            radial-gradient(circle at top right, rgba(139, 0, 0, .10), transparent 38%),
            linear-gradient(145deg, #ffffff, #fff7f7);
        border: 1px solid #f1d7d7;
        box-shadow: 0 12px 28px rgba(139, 0, 0, .07);
    }

    .appt-summary-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, transparent, rgba(255, 255, 255, .35), transparent);
        transform: translateX(-120%) skewX(-18deg);
        transition: transform .6s ease;
    }

    .appt-summary-card:hover::before {
        transform: translateX(120%) skewX(-18deg);
    }

    .appt-summary-card h3 {
        margin-top: 2px;
    }

    .appt-summary-icon {
        width: 2.4rem;
        height: 2.4rem;
        border-radius: .9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #660000, #8B0000, #b30000);
        box-shadow: 0 10px 22px rgba(139, 0, 0, .20);
    }

    .appt-tip-card {
        border-radius: 1.15rem;
        padding: 0.75rem 0.95rem;
        background:
            radial-gradient(circle at top left, rgba(245, 158, 11, .13), transparent 28%),
            linear-gradient(145deg, #fffaf0, #fff7f7);
        border: 1px solid rgba(245, 158, 11, .22);
    }

    .appt-empty-state {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        min-height: 250px;
        padding: 2.25rem 1.5rem;
        background:
            radial-gradient(circle at 50% 0%, rgba(139, 0, 0, .10), transparent 36%),
            linear-gradient(145deg, #ffffff, #fffafa);
        border: 1px dashed rgba(139, 0, 0, .22);
    }

    .appt-empty-icon {
        width: 5.25rem;
        height: 5.25rem;
        border-radius: 1.35rem;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            linear-gradient(145deg, rgba(139, 0, 0, .10), rgba(139, 0, 0, .04));
        color: #8B0000;
        border: 1px solid rgba(139, 0, 0, .12);
        animation: apptFloat 3s ease-in-out infinite;
    }

    .appt-primary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        border-radius: 999px;
        padding: .8rem 1.15rem;
        font-size: .88rem;
        font-weight: 800;
        color: white;
        background: linear-gradient(135deg, #660000, #8B0000, #b30000);
        box-shadow: 0 12px 28px rgba(139, 0, 0, .22);
        transition: transform .22s ease, box-shadow .22s ease;
    }

    .appt-primary-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 36px rgba(139, 0, 0, .32);
    }

    .appt-secondary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        border-radius: 999px;
        padding: .8rem 1.15rem;
        font-size: .88rem;
        font-weight: 800;
        color: #8B0000;
        background: #fff;
        border: 1px solid #f0cccc;
        transition: all .22s ease;
    }

    .appt-secondary-btn:hover {
        background: #fff1f1;
        transform: translateY(-2px);
    }

    .appt-fab {
        position: fixed;
        right: 1.4rem;
        bottom: 9.4rem;
        z-index: 30;
        display: inline-flex;
        align-items: center;
        gap: .65rem;
        padding: .9rem 1.1rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #660000, #8B0000, #b30000);
        color: #fff;
        font-weight: 900;
        font-size: .88rem;
        box-shadow: 0 18px 38px rgba(139, 0, 0, .35);
        animation: apptGlowPulse 2.6s ease-in-out infinite;
    }

    .appt-fab:hover {
        transform: translateY(-2px);
    }

    [data-theme="dark"] #mainContent {
        background: #000D1A !important;
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] #mainContent .appt-summary-card,
    [data-theme="dark"] #mainContent .appt-empty-state,
    [data-theme="dark"] #mainContent .service-card,
    [data-theme="dark"] #mainContent .appt-tip-card {
        background:
            radial-gradient(circle at top right, rgba(139, 0, 0, .20), transparent 40%),
            linear-gradient(145deg, #0D1117, #111827) !important;
        border-color: rgba(255, 255, 255, .10) !important;
        box-shadow: 0 18px 38px rgba(0, 0, 0, .35) !important;
    }

    [data-theme="dark"] #mainContent .appt-empty-state {
        border-color: rgba(252, 165, 165, .20) !important;
    }

    [data-theme="dark"] #mainContent .appt-empty-icon {
        background: rgba(139, 0, 0, .20) !important;
        color: #FCA5A5 !important;
        border-color: rgba(252, 165, 165, .18) !important;
    }

    [data-theme="dark"] #mainContent .appt-secondary-btn {
        background: #161B22 !important;
        border-color: rgba(255, 255, 255, .10) !important;
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent .appt-tip-card {
        background:
            radial-gradient(circle at top left, rgba(245, 158, 11, .10), transparent 32%),
            linear-gradient(145deg, #0D1117, #161B22) !important;
    }

    [data-theme="dark"] #mainContent .service-card-tag {
        background: rgba(139, 0, 0, .20) !important;
        color: #FCA5A5 !important;
        border-color: rgba(252, 165, 165, .18) !important;
    }

    [data-theme="dark"] #mainContent .text-gray-900,
    [data-theme="dark"] #mainContent .text-gray-800,
    [data-theme="dark"] #mainContent .text-gray-700 {
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] #mainContent .text-gray-600,
    [data-theme="dark"] #mainContent .text-gray-500,
    [data-theme="dark"] #mainContent .text-gray-400 {
        color: #C9D1D9 !important;
    }

    .appt-summary-grid {
        gap: 0.7rem;
    }

    @media (min-width: 768px) {
        .appt-summary-grid {
            gap: 0.9rem;
        }
    }

    @media (max-width: 767px) {
        .appt-summary-grid {
            grid-template-columns: 1fr;
        }

        .appt-fab {
            right: 1rem;
            bottom: 7.5rem;
            padding: .85rem;
            width: 3.35rem;
            height: 3.35rem;
        }

        .appt-fab span {
            display: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .appt-page-enter,
        .appt-section-reveal,
        .appt-empty-icon,
        .appt-fab {
            animation: none !important;
        }
    }

    .appt-record-modal::backdrop {
        background: rgba(20, 10, 10, .62);
    }

    .appt-record-box {
        width: min(94vw, 820px);
        max-height: 90vh;
        overflow: hidden;
        border-radius: 1.35rem;
        background: #fff8ef;
        border: 1px solid rgba(139, 0, 0, .18);
        will-change: transform, opacity;
    }

    .appt-record-scroll {
        max-height: calc(90vh - 82px);
        overflow-y: auto;
    }

    .appt-record-hero {
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, .16), transparent 28%),
            linear-gradient(135deg, #7A0000, #8B0000, #661010);
        color: #fff;
        padding: 1.15rem 1.35rem;
    }

    .appt-record-hero h3 {
        font-size: 1.35rem !important;
    }

    .appt-record-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .28rem .65rem;
        border-radius: 999px;
        background: rgba(255, 214, 130, .16);
        border: 1px solid rgba(255, 214, 130, .28);
        color: #ffd58a;
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .appt-record-close {
        position: absolute;
        top: 1.05rem;
        right: 1.05rem;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .13);
        border: 1px solid rgba(255, 255, 255, .18);
        color: #fff;
    }

    .appt-record-body {
        padding: 1.25rem 1.75rem 1.35rem;
    }

    .appt-record-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
    }

    .appt-info-card,
    .appt-dentist-card,
    .appt-record-accordion,
    .vo-card,
    .vo-tooth-main,
    .vo-info-box {
        background: rgba(255, 255, 255, .82);
        border: 1px solid rgba(139, 0, 0, .13);
        border-radius: .95rem;
        box-shadow: none !important;
    }

    .appt-info-card {
        padding: .9rem 1rem;
    }

    .appt-info-label {
        font-size: .64rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #8B0000;
    }

    .appt-info-value {
        margin-top: .3rem;
        font-size: .95rem;
        font-weight: 800;
        color: #3b1c1c;
    }

    .appt-section-title {
        display: flex;
        align-items: center;
        gap: .7rem;
        margin: 1.25rem 0 .75rem;
        color: #8B0000;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .appt-section-title::after {
        content: '';
        height: 1px;
        flex: 1;
        background: rgba(139, 0, 0, .18);
    }

    .appt-dentist-card {
        padding: .95rem 1rem;
        display: flex;
        align-items: center;
        gap: .9rem;
    }

    .appt-dentist-avatar {
        width: 3rem;
        height: 3rem;
        border-radius: 999px;
        background: #8B0000;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
    }

    .appt-record-accordion {
        margin-bottom: .7rem;
        overflow: hidden;
    }

    .appt-record-accordion summary {
        list-style: none;
        cursor: pointer;
        padding: .95rem 1rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        font-weight: 900;
        color: #2b1515;
    }

    .appt-record-accordion summary::-webkit-details-marker {
        display: none;
    }

    .appt-record-chevron {
        margin-left: auto;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #8B0000;
        background: rgba(139, 0, 0, .07);
        transition:
            transform .28s cubic-bezier(.22, 1, .36, 1),
            background .22s ease,
            color .22s ease;
    }

    .appt-record-accordion[open] .appt-record-chevron {
        transform: rotate(180deg);
        background: rgba(139, 0, 0, .13);
    }

    .appt-record-accordion summary {
        transition: background .22s ease, padding-left .22s ease;
    }

    .appt-record-accordion summary:hover {
        background: rgba(139, 0, 0, .035);
        padding-left: 1.15rem;
    }

    .appt-record-accordion-icon {
        transition: transform .22s ease, background .22s ease, color .22s ease;
    }

    .appt-record-accordion:hover .appt-record-accordion-icon {
        transform: scale(1.06);
        background: rgba(139, 0, 0, .13);
    }

    .appt-record-panel {
        overflow: hidden;
    }

    [data-theme="dark"] .appt-record-chevron {
        color: #FCA5A5 !important;
        background: rgba(252, 165, 165, .08) !important;
    }

    [data-theme="dark"] .appt-record-accordion[open] .appt-record-chevron,
    [data-theme="dark"] .appt-record-accordion summary:hover {
        background: rgba(252, 165, 165, .12) !important;
    }

    [data-theme="dark"] .appt-record-accordion:hover .appt-record-accordion-icon {
        background: rgba(252, 165, 165, .13) !important;
    }

    .appt-record-accordion-icon {
        width: 2.15rem;
        height: 2.15rem;
        border-radius: .65rem;
        background: #fff0f0;
        color: #8B0000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .appt-record-panel {
        border-top: 1px solid rgba(139, 0, 0, .12);
        padding: 1rem;
        color: #5f4b4b;
        font-size: .9rem;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .appt-empty-mini {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        min-height: 105px;
        border: 1px dashed rgba(139, 0, 0, .22);
        background:
            radial-gradient(circle at top, rgba(139, 0, 0, .08), transparent 55%),
            rgba(139, 0, 0, .035);
        border-radius: .9rem;
        padding: 1rem;
        text-align: center;
    }

    .appt-empty-mini-icon {
        width: 2.45rem;
        height: 2.45rem;
        border-radius: .8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8B0000;
        background: rgba(139, 0, 0, .10);
    }

    .appt-empty-mini-title {
        font-size: .86rem;
        font-weight: 900;
        color: #3b1c1c;
    }

    .appt-empty-mini-text {
        font-size: .75rem;
        font-weight: 600;
        color: #8a6f6f;
    }

    [data-theme="dark"] .appt-empty-mini {
        background: rgba(139, 0, 0, .12) !important;
        border-color: rgba(252, 165, 165, .22) !important;
    }

    [data-theme="dark"] .appt-empty-mini-icon {
        background: rgba(252, 165, 165, .12);
        color: #FCA5A5;
    }

    [data-theme="dark"] .appt-empty-mini-title {
        color: #F3F4F6;
    }

    [data-theme="dark"] .appt-empty-mini-text {
        color: #C9D1D9;
    }

    .appt-record-footer {
        position: sticky;
        bottom: 0;
        background: rgba(255, 248, 239, .92);
        backdrop-filter: blur(12px);
        border-top: 1px solid rgba(139, 0, 0, .12);
        padding: 1rem 1.75rem;
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
    }

    [data-theme="dark"] .appt-record-box {
        background: #0D1117;
        border-color: rgba(255, 255, 255, .10);
    }

    [data-theme="dark"] .appt-record-body,
    [data-theme="dark"] .appt-record-footer {
        background: #0D1117;
    }

    [data-theme="dark"] .appt-info-card,
    [data-theme="dark"] .appt-dentist-card,
    [data-theme="dark"] .appt-record-accordion {
        background: #161B22;
        border-color: rgba(255, 255, 255, .10);
    }

    [data-theme="dark"] .appt-info-value,
    [data-theme="dark"] .appt-record-accordion summary {
        color: #F3F4F6;
    }

    [data-theme="dark"] .appt-record-panel,
    [data-theme="dark"] .appt-empty-mini {
        color: #C9D1D9;
    }

    @keyframes apptTabPop {
        0% {
            transform: scale(.96);
        }

        60% {
            transform: scale(1.035);
        }

        100% {
            transform: scale(1);
        }
    }

    .appt-tab {
        position: relative;
        overflow: hidden;
        transition: transform .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease;
    }

    .appt-tab:hover {
        transform: translateY(-1px);
    }

    .appt-tab.appt-active {
        animation: apptTabPop .28s ease both;
    }

    .appt-tab::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, transparent, rgba(255, 255, 255, .18), transparent);
        transform: translateX(-120%) skewX(-20deg);
    }

    .appt-tab.appt-active::after {
        animation: apptShine .65s ease;
    }

    @keyframes apptShine {
        to {
            transform: translateX(120%) skewX(-20deg);
        }
    }

    .appt-count {
        transition: transform .2s ease, background .2s ease, color .2s ease;
    }

    .appt-tab:hover .appt-count {
        transform: scale(1.08);
    }

    .appt-record-modal[open] #d_service {
        animation-delay: .05s;
    }

    .appt-record-modal[open] .appt-record-hero .flex {
        animation-delay: .09s;
    }

    .appt-record-modal[open] .appt-info-card:nth-child(1) {
        animation-delay: .12s;
    }

    .appt-record-modal[open] .appt-info-card:nth-child(2) {
        animation-delay: .16s;
    }

    .appt-record-modal[open] .appt-dentist-card {
        animation-delay: .2s;
    }

    .appt-record-modal[open] .appt-record-accordion:nth-of-type(1) {
        animation-delay: .24s;
    }

    .appt-record-modal[open] .appt-record-accordion:nth-of-type(2) {
        animation-delay: .28s;
    }

    .appt-record-modal[open] .appt-record-accordion:nth-of-type(3) {
        animation-delay: .32s;
    }

    .appt-record-modal[open] .appt-record-accordion:nth-of-type(4) {
        animation-delay: .36s;
    }

    .appt-record-accordion {
        transition: transform .2s ease, border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }

    .appt-record-accordion:hover {
        transform: none !important;
        box-shadow: none !important;
        background: rgba(139, 0, 0, .035) !important;
    }

    .appt-record-accordion[open] {
        border-color: rgba(139, 0, 0, .35);
    }

    .appt-record-panel {
        animation: apptModalItemIn .25s ease both;
    }

    [data-theme="dark"] .appt-record-hero {
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, .10), transparent 30%),
            linear-gradient(135deg, #7A0000, #8B0000, #5A0000) !important;
    }

    [data-theme="dark"] .appt-record-chip {
        background: rgba(251, 191, 36, .14) !important;
        border-color: rgba(251, 191, 36, .30) !important;
        color: #FCD34D !important;
    }

    [data-theme="dark"] .appt-info-label,
    [data-theme="dark"] .appt-section-title {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .appt-section-title::after {
        background: rgba(252, 165, 165, .18) !important;
    }

    [data-theme="dark"] .appt-record-accordion-icon {
        background: rgba(139, 0, 0, .22) !important;
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .appt-record-accordion summary span,
    [data-theme="dark"] .appt-record-accordion summary {
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .appt-record-accordion summary .text-gray-400 {
        color: #9CA3AF !important;
    }

    [data-theme="dark"] .appt-record-accordion summary::after {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .appt-record-panel {
        background: rgba(255, 255, 255, .025) !important;
        border-top-color: rgba(255, 255, 255, .08) !important;
        color: #D1D5DB !important;
    }

    [data-theme="dark"] .appt-empty-mini {
        background: rgba(139, 0, 0, .12) !important;
        border-color: rgba(252, 165, 165, .22) !important;
        color: #D1D5DB !important;
    }

    [data-theme="dark"] .appt-dentist-avatar {
        background: linear-gradient(135deg, #8B0000, #B91C1C) !important;
        color: #fff !important;
    }

    [data-theme="dark"] .appt-record-close {
        background: rgba(255, 255, 255, .10) !important;
        border-color: rgba(255, 255, 255, .18) !important;
    }

    [data-theme="dark"] .appt-record-footer {
        background: rgba(13, 17, 23, .92) !important;
        border-top-color: rgba(255, 255, 255, .10) !important;
    }

    @media (prefers-reduced-motion: reduce) {

        .appt-tab,
        .appt-tab.appt-active,
        .appt-record-modal[open] .appt-record-box,
        .appt-record-modal[open] .appt-record-chip,
        .appt-record-modal[open] #d_service,
        .appt-record-modal[open] .appt-record-hero .flex,
        .appt-record-modal[open] .appt-info-card,
        .appt-record-modal[open] .appt-section-title,
        .appt-record-modal[open] .appt-dentist-card,
        .appt-record-modal[open] .appt-record-accordion,
        .appt-record-panel {
            animation: none !important;
            transition: none !important;
            transform: none !important;
            filter: none !important;
        }
    }

    .appt-record-panel {
        height: 0;
        opacity: 0;
        overflow: hidden;
        transition:
            height 0.32s cubic-bezier(.22, 1, .36, 1),
            opacity 0.22s ease;
    }

    .appt-record-modal::backdrop {
        transition: opacity .26s ease;
    }

    .appt-record-modal::backdrop {
        transition: opacity .24s ease;
    }

    .appt-record-box {
        transform-origin: center;
        will-change: transform, opacity, filter;
    }

    .appt-record-modal {
        padding: 0;
    }

    .appt-record-modal::backdrop {
        background: rgba(10, 10, 10, .56);
    }

    .appt-record-box {
        width: min(94vw, 820px);
        max-height: 90vh;
        overflow: hidden;
        border-radius: 1.35rem;
        transform-origin: center;
        will-change: transform, opacity;
    }

    .appt-record-modal[open]:not(.is-closing) .appt-record-box {
        animation: apptModalDesktopIn .28s cubic-bezier(.22, 1, .36, 1) both;
    }

    .appt-record-modal.is-closing .appt-record-box {
        animation: apptModalDesktopOut .2s ease both;
    }

    .appt-record-modal.is-closing::backdrop {
        opacity: 0;
    }

    .appt-record-panel {
        height: 0;
        opacity: 0;
        overflow: hidden;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        transition:
            height .3s cubic-bezier(.22, 1, .36, 1),
            opacity .2s ease,
            padding .2s ease;
    }

    .appt-record-accordion.is-open .appt-record-panel {
        opacity: 1;
        padding: 1rem !important;
    }

    @keyframes apptModalDesktopIn {
        from {
            opacity: 0;
            transform: translateY(14px) scale(.97);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes apptModalDesktopOut {
        from {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        to {
            opacity: 0;
            transform: translateY(10px) scale(.98);
        }
    }

    @keyframes apptSheetIn {
        from {
            transform: translateY(100%);
        }

        to {
            transform: translateY(0);
        }
    }

    @keyframes apptSheetOut {
        from {
            transform: translateY(0);
        }

        to {
            transform: translateY(100%);
        }
    }

    @media (min-width: 768px) and (max-width: 1024px) {
        #mainContent {
            margin-left: 0 !important;
            padding: 5rem 1rem 7rem !important;
        }

        .appt-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .7rem;
        }

        .appt-summary-card {
            padding: .75rem;
            border-radius: 1rem;
        }

        .appt-summary-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: .75rem;
            font-size: .85rem;
        }

        .appt-tip-card {
            padding: .85rem;
            border-radius: 1rem;
        }
    }

    @media (max-width: 480px) {
        #mainContent {
            padding: 4.8rem .85rem 7rem !important;
            margin-left: 0 !important;
        }

        .appt-section-reveal {
            margin-bottom: .9rem !important;
        }

        .appt-summary-grid {
            grid-template-columns: 1fr;
            gap: .65rem;
        }

        .appt-summary-card {
            padding: .75rem .8rem;
            border-radius: .95rem;
            min-height: unset;
        }

        .appt-summary-card .flex {
            gap: .75rem !important;
        }

        .appt-summary-icon {
            width: 2.15rem;
            height: 2.15rem;
            border-radius: .7rem;
            font-size: .8rem;
            flex-shrink: 0;
        }

        .appt-summary-card p {
            font-size: .62rem !important;
            letter-spacing: .13em !important;
        }

        .appt-summary-card h3 {
            font-size: 1rem !important;
            line-height: 1.15;
        }

        .appt-tip-card {
            padding: .8rem;
            border-radius: .95rem;
            gap: .75rem !important;
        }

        .appt-tip-card .w-10 {
            width: 2.15rem !important;
            height: 2.15rem !important;
            font-size: .8rem;
        }

        .appt-tip-card p.text-sm {
            font-size: .78rem !important;
            line-height: 1.35;
        }

        .appt-secondary-btn,
        .appt-primary-btn {
            padding: .62rem .85rem;
            font-size: .76rem;
        }

        .fade-up {
            animation-duration: .42s;
        }

        .services-grid {
            gap: .65rem;
        }

        .service-card {
            padding: .75rem;
            border-radius: .9rem;
        }

        .service-card-icon {
            width: 2.25rem;
            height: 2.25rem;
            margin-bottom: .55rem;
        }

        .service-card-title {
            font-size: .92rem;
        }

        .service-card-desc {
            font-size: .74rem;
        }
    }

    @media (max-width: 340px) {
        #mainContent {
            padding-left: .65rem !important;
            padding-right: .65rem !important;
        }

        .appt-summary-card {
            padding: .65rem;
        }

        .appt-summary-icon {
            width: 2rem;
            height: 2rem;
        }

        .appt-tip-card {
            padding: .7rem;
        }

        .appt-secondary-btn {
            width: 100%;
            justify-content: center;
            font-size: .72rem;
        }
    }

    .appt-timeline-empty {
        position: relative;
        overflow: hidden;
        border-radius: 1.4rem;
        padding: 1.35rem;
        border: 1px solid rgba(139, 0, 0, .14);
        background:
            radial-gradient(circle at top right, rgba(139, 0, 0, .12), transparent 36%),
            linear-gradient(145deg, #ffffff, #fff8f8);
        box-shadow: 0 16px 35px rgba(139, 0, 0, .07);
    }

    .appt-timeline-empty-grid {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 1.25rem;
        align-items: center;
    }

    .appt-timeline-path {
        position: relative;
        padding-left: 2.2rem;
    }

    .appt-timeline-path::before {
        content: '';
        position: absolute;
        left: .65rem;
        top: .45rem;
        bottom: .45rem;
        width: 2px;
        background: linear-gradient(to bottom, #8B0000, rgba(139, 0, 0, .14));
    }

    .appt-timeline-step {
        position: relative;
        padding: .15rem 0 1.05rem;
    }

    .appt-timeline-step:last-child {
        padding-bottom: 0;
    }

    .appt-timeline-dot {
        position: absolute;
        left: -2rem;
        top: .2rem;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 2px solid rgba(139, 0, 0, .22);
        color: #8B0000;
        font-size: .62rem;
    }

    .appt-timeline-dot.active {
        background: #8B0000;
        color: #fff;
        border-color: #8B0000;
        box-shadow: 0 0 0 7px rgba(139, 0, 0, .08);
    }

    .appt-recommended-card {
        min-width: 270px;
        border-radius: 1.15rem;
        padding: 1rem;
        background: linear-gradient(135deg, #8B0000, #B91C1C);
        color: #fff;
        box-shadow: 0 18px 35px rgba(139, 0, 0, .24);
    }

    .appt-recommended-date {
        width: 4.25rem;
        height: 4.25rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .18);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .appt-recommended-btn {
        margin-top: .9rem;
        display: inline-flex;
        width: 100%;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        border-radius: 999px;
        padding: .72rem 1rem;
        background: #fff;
        color: #8B0000;
        font-size: .82rem;
        font-weight: 900;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .appt-recommended-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(0, 0, 0, .16);
    }

    [data-theme="dark"] .appt-timeline-empty {
        background:
            radial-gradient(circle at 50% 0%, rgba(139, 0, 0, .25), transparent 38%),
            linear-gradient(145deg, #0B1220, #0D1117) !important;
        border: 1px solid rgba(252, 165, 165, .18) !important;
        box-shadow: 0 18px 40px rgba(0, 0, 0, .45) !important;
    }

    [data-theme="dark"] .appt-timeline-path::before {
        background: linear-gradient(to bottom,
                #FCA5A5,
                rgba(252, 165, 165, .18)) !important;
    }

    [data-theme="dark"] .appt-timeline-dot {
        background: #161B22 !important;
        border-color: rgba(252, 165, 165, .28) !important;
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .appt-timeline-dot.active {
        background: #8B0000 !important;
        color: #fff !important;
        border-color: #8B0000 !important;
        box-shadow: 0 0 0 8px rgba(139, 0, 0, .25) !important;
    }

    /* text contrast fix */
    [data-theme="dark"] .appt-timeline-step p:first-of-type {
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .appt-timeline-step p:last-of-type {
        color: #C9D1D9 !important;
    }

    @media (max-width: 767px) {
        .appt-timeline-empty {
            padding: 1rem;
            border-radius: 1.05rem;
        }

        .appt-timeline-empty-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .appt-recommended-card {
            min-width: 0;
            width: 100%;
            padding: .9rem;
            border-radius: 1rem;
        }

        .appt-recommended-date {
            width: 3.65rem;
            height: 3.65rem;
        }

        .appt-timeline-path {
            padding-left: 1.85rem;
        }

        .appt-timeline-dot {
            left: -1.75rem;
        }
    }

    [data-theme="dark"] .appt-side-container {
        background: #0D1117;
    }

    @media (max-width: 1100px) {
        .appt-side-container {
            width: min(680px, 100vw);
        }
    }

    .appt-sheet-handle {
        display: none;
    }

    .appt-detail-modal {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .appt-detail-modal.hidden {
        display: none;
    }

    .appt-detail-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(20, 10, 10, .50);
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .appt-detail-box {
        position: relative;
        display: flex;
        flex-direction: column;
        width: min(92vw, 860px);
        max-height: 88vh;
        overflow: hidden;
        border-radius: 1.2rem;
        background: #fff8ef;
        border: 1px solid rgba(139, 0, 0, .18);
        transform: translateZ(0);
        backface-visibility: hidden;
        contain: layout paint;
        box-shadow: 0 28px 80px rgba(0, 0, 0, .32);
        animation: apptDetailModalIn .24s cubic-bezier(.22, 1, .36, 1) both;
    }

    .appt-detail-box .appt-record-body {
        max-height: calc(88vh - 112px);
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding: 1rem 1.2rem 1.3rem;
    }

    @keyframes apptDetailModalIn {
        from {
            opacity: 0;
            transform: translateY(14px) scale(.97);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    [data-theme="dark"] .appt-detail-box {
        background: #0D1117;
        border-color: rgba(255, 255, 255, .10);
    }

    .appt-empty-hero-icon {
        width: 4.25rem;
        height: 4.25rem;
        border-radius: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        color: #FCA5A5;
        background:
            radial-gradient(circle at top right, rgba(252, 165, 165, .22), transparent 45%),
            rgba(139, 0, 0, .14);
        border: 1px solid rgba(252, 165, 165, .18);
        box-shadow: 0 14px 30px rgba(139, 0, 0, .12);
    }

    .appt-empty-hero-icon i {
        font-size: 1.75rem;
    }

    .vo-card {
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid rgba(139, 0, 0, .14);
        background: rgba(255, 255, 255, .72);
        padding: .9rem;
    }

    .vo-head {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .75rem;
    }

    .vo-label {
        color: #8B0000;
        font-size: .7rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .vo-sub {
        color: #8a6f6f;
        font-size: .72rem;
        font-weight: 700;
    }

    .vo-mini-legend {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem .6rem;
        justify-content: flex-end;
        font-size: .65rem;
        font-weight: 800;
        color: #6b7280;
    }

    .vo-dot {
        width: .65rem;
        height: .65rem;
        border-radius: .2rem;
        display: inline-block;
        margin-right: .25rem;
        border: 1px solid rgba(0, 0, 0, .14);
    }

    .vo-dot.healthy {
        background: #fff;
    }

    .vo-dot.extracted {
        background: #fecaca;
        border-color: #991b1b;
    }

    .vo-dot.filled {
        background: #bae6fd;
        border-color: #0369a1;
    }

    .vo-dot.decay {
        background: #fde68a;
        border-color: #b45309;
    }

    .vo-dot.crown {
        background: #dcfce7;
        border-color: #15803d;
    }

    .vo-dot.missing {
        background: repeating-linear-gradient(45deg, #f3f4f6, #f3f4f6 3px, #d1d5db 3px, #d1d5db 6px);
    }

    .vo-board-wrap {
        overflow-x: auto;
        padding-bottom: .35rem;
    }

    .vo-board {
        min-width: 520px;
    }

    .vo-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .34rem;
        margin-bottom: .75rem;
    }

    .vo-tooth {
        width: 1.65rem;
        text-align: center;
        cursor: pointer;
    }

    .vo-num {
        font-size: .58rem;
        font-weight: 900;
        color: #7a5c5c;
        margin-bottom: .15rem;
    }

    .vo-box {
        width: 1.55rem;
        height: 1.15rem;
        border-radius: .28rem;
        border: 1.5px solid #cfc7c0;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .vo-tooth:hover .vo-box {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(139, 0, 0, .14);
    }

    .vo-root {
        width: 1px;
        height: 1.2rem;
        background: #cfc7c0;
        margin: 0 auto;
    }

    .vo-tooth.extracted .vo-box {
        background: #fecaca;
        border-color: #991b1b;
        color: #7f1d1d;
    }

    .vo-tooth.filled .vo-box {
        background: #bae6fd;
        border-color: #0369a1;
    }

    .vo-tooth.decay .vo-box {
        background: #fde68a;
        border-color: #b45309;
    }

    .vo-tooth.crown .vo-box {
        background: #dcfce7;
        border-color: #15803d;
    }

    .vo-tooth.missing .vo-box {
        background: repeating-linear-gradient(45deg, #f3f4f6, #f3f4f6 3px, #d1d5db 3px, #d1d5db 6px);
        border-style: dashed;
    }

    .vo-arch-line {
        text-align: center;
        font-size: .62rem;
        font-weight: 900;
        color: #9b7777;
        letter-spacing: .12em;
        text-transform: uppercase;
        border-top: 1px dashed rgba(139, 0, 0, .18);
        border-bottom: 1px dashed rgba(139, 0, 0, .18);
        padding: .35rem 0;
        margin: .45rem 0 .8rem;
    }

    .vo-tooth-modal {
        position: fixed;
        inset: 0;
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vo-tooth-modal.hidden {
        display: none;
    }

    .vo-tooth-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(38, 14, 14, .48);
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .vo-tooth-card {
        position: relative;
        width: min(92vw, 620px);
        max-height: 86vh;
        overflow-y: auto;
        transform: translateZ(0);
        border-radius: 1.25rem;
        background: #fff8ef;
        border: 1px solid rgba(139, 0, 0, .18);
        box-shadow: 0 25px 70px rgba(0, 0, 0, .28);
    }

    .vo-tooth-hero {
        position: relative;
        padding: 1.25rem;
        color: #fff;
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, .14), transparent 30%),
            linear-gradient(135deg, #7A0000, #8B0000, #661010);
    }

    .vo-tooth-hero h3 {
        font-size: 1.35rem;
        font-weight: 900;
    }

    .vo-tooth-hero p {
        font-size: .82rem;
        opacity: .85;
        font-weight: 700;
    }

    .vo-tooth-close {
        position: absolute;
        top: .85rem;
        right: .85rem;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .18);
    }

    .vo-tooth-body {
        padding: .9rem;
    }

    .vo-tooth-main {
        border-radius: 1rem;
        border: 1px solid rgba(139, 0, 0, .13);
        background: #fff;
        display: grid;
        grid-template-columns: 150px 1fr;
        padding: .85rem;
        gap: 1rem;
        align-items: center;
    }

    .vo-tooth-visual {
        min-height: 96px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vo-big-tooth {
        width: 4.2rem;
        height: 2.35rem;
        border-radius: .55rem;
        border: 3px solid #9b1c1c;
        background: #fecaca;
        color: #7f1d1d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        position: relative;
        box-shadow: 0 5px 0 rgba(127, 29, 29, .35);
    }

    .vo-big-tooth::before,
    .vo-big-tooth::after {
        content: '';
        position: absolute;
        bottom: -2.95rem;
        width: 3px;
        height: 2.6rem;
        background: #c9b9aa;
        border-radius: 999px;
    }

    .vo-big-tooth::before {
        left: 1.5rem;
        transform: rotate(4deg);
    }

    .vo-big-tooth::after {
        right: 1.5rem;
        transform: rotate(-4deg);
    }

    .vo-info-label {
        color: #9b7777;
        font-size: .62rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .vo-condition-pill,
    .vo-treated-badge {
        margin-top: .4rem;
        border-radius: .45rem;
        padding: .45rem .65rem;
        font-size: .78rem;
        font-weight: 800;
        border: 1px solid rgba(139, 0, 0, .28);
        background: #fee2e2;
        color: #7f1d1d;
    }

    .vo-treated-badge {
        background: #fff5f5;
    }

    .vo-tooth-name {
        margin: .7rem 0;
        color: #7b6b6b;
        font-size: .82rem;
        font-weight: 700;
    }

    .vo-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .5rem;
    }

    .vo-info-box {
        border-radius: .7rem;
        border: 1px solid rgba(139, 0, 0, .13);
        background: #fff;
        padding: .6rem .7rem !important;
    }

    .vo-info-box p {
        color: #9b7777;
        font-size: .58rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .vo-info-box strong {
        display: block;
        margin-top: .25rem;
        font-size: .82rem;
        color: #2b1515;
    }

    .vo-history {
        margin-top: .7rem;
    }

    .vo-history .appt-empty-mini {
        min-height: 78px;
        padding: .75rem;
    }

    .vo-history-item {
        margin-top: .65rem;
        display: flex;
        gap: .65rem;
        align-items: center;
        font-size: .8rem;
        color: #4b3b3b;
    }

    .vo-history-dot {
        width: .5rem;
        height: .5rem;
        border-radius: 999px;
        background: #fecaca;
        border: 1px solid #fca5a5;
    }

    [data-theme="dark"] .vo-card,
    [data-theme="dark"] .vo-tooth-card {
        background: #0D1117;
        border-color: rgba(255, 255, 255, .10);
    }

    [data-theme="dark"] .vo-tooth-main,
    [data-theme="dark"] .vo-info-box {
        background: #161B22;
        border-color: rgba(255, 255, 255, .10);
    }

    [data-theme="dark"] .vo-info-box strong,
    [data-theme="dark"] .vo-tooth-name {
        color: #F3F4F6;
    }

    [data-theme="dark"] .appt-sheet-handle {
        background: #0D1117;
    }

    [data-theme="dark"] .appt-sheet-handle span {
        background: rgba(252, 165, 165, .38);
    }

    [data-theme="dark"] .appt-sheet-handle p {
        color: #FCA5A5;
    }

    @media (max-width: 640px) {
        .appt-empty-hero-icon {
            margin-left: auto;
            margin-right: auto;
        }

        .appt-detail-modal {
            align-items: end;
            padding: 0;
        }

        .appt-record-hero h3 {
            font-size: 1.15rem !important;
        }

        .appt-detail-box {
            width: 100%;
            height: 92dvh;
            max-height: 92dvh;
            border-radius: 1.25rem 1.25rem 0 0;
        }

        .appt-detail-box .appt-record-body {
            max-height: none;
            padding: .9rem;
        }

        .appt-record-modal {
            align-items: end !important;
        }

        .appt-record-box {
            width: 100vw;
            max-width: 100vw;
            max-height: 92vh;
            margin: 0;
            border-radius: 1.25rem 1.25rem 0 0;
        }

        .appt-record-scroll {
            max-height: calc(92vh - 78px);
        }

        .appt-record-modal[open]:not(.is-closing) .appt-record-box {
            animation: apptSheetIn .32s cubic-bezier(.22, 1, .36, 1) both;
        }

        .appt-record-modal.is-closing .appt-record-box {
            animation: apptSheetOut .24s ease both;
        }

        .appt-sheet-handle {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
            padding: .65rem 0 .35rem;
            background: #fff8ef;
            border-radius: 1.5rem 1.5rem 0 0;
        }

        .appt-sheet-handle span {
            width: 42px;
            height: 5px;
            border-radius: 999px;
            background: rgba(139, 0, 0, .28);
        }

        .appt-sheet-handle p {
            font-size: .68rem;
            font-weight: 800;
            color: #8B0000;
            letter-spacing: .04em;
        }

        .appt-record-hero {
            padding: 1rem;
        }

        .appt-record-body {
            padding: 1rem;
        }

        .appt-record-info-grid,
        .vo-tooth-main {
            grid-template-columns: 1fr;
        }

        .appt-record-footer {
            padding: .85rem 1rem;
            flex-direction: column;
        }

        .appt-record-footer button,
        .appt-record-footer a {
            width: 100%;
            justify-content: center;
        }

        .vo-tooth-card {
            width: 94vw;
            max-height: 84dvh;
        }

        .vo-info-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    [data-theme="dark"] #mainContent .service-card {
        background:
            radial-gradient(circle at top right, rgba(139, 0, 0, .18), transparent 38%),
            linear-gradient(145deg, #0D1117, #07111f) !important;
        border-color: rgba(252, 165, 165, .14) !important;
        box-shadow: none !important;
    }

    [data-theme="dark"] #mainContent .service-card:hover {
        border-color: rgba(252, 165, 165, .28) !important;
        box-shadow: 0 14px 30px rgba(0, 0, 0, .28) !important;
    }

    [data-theme="dark"] #mainContent .service-card-title {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent .service-card-desc {
        color: #C9D1D9 !important;
    }

    [data-theme="dark"] #mainContent .service-card-icon {
        background: linear-gradient(135deg, #8B0000, #B91C1C) !important;
        box-shadow: 0 10px 22px rgba(139, 0, 0, .28) !important;
    }

    [data-theme="dark"] #mainContent .service-card-tag {
        background: rgba(252, 165, 165, .10) !important;
        color: #FECACA !important;
        border-color: rgba(252, 165, 165, .22) !important;
    }

    [data-theme="dark"] #mainContent section h2 {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent section p {
        color: #C9D1D9;
    }

    @keyframes apptPanelFadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
            filter: blur(4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
            filter: blur(0);
        }
    }

    .appt-panel-fade {
        animation: apptPanelFadeIn .32s cubic-bezier(.22, 1, .36, 1) both;
    }
</style>
@endsection

@section('content')

@php
$calendarAppointments = [];
foreach (
collect($appointments ?? [])->filter(function ($appt) {
$status = strtolower($appt->status ?? '');
return !in_array($status, ['completed', 'cancelled']);
})
as $appt
) {
$calendarAppointments[\Carbon\Carbon::parse($appt->appointment_date)->format('Y-m-d')] =
'My Appointment: ' .
$appt->service_type .
' • ' .
\Carbon\Carbon::parse($appt->appointment_time)->format('g:i A');
}
@endphp

<script>
    window.patientOdontogramTeeth = @json($odontogramTeeth ?? []);
    window.apptActivityChartData = @json($appointmentActivityChart ?? []);
</script>

<main id="mainContent"
    class="pt-[90px] px-3 md:px-6 py-6 page-enter min-h-screen flex-1 bg-gray-50 dark:bg-[#000D1A] text-gray-900 dark:text-[#F3F4F6]">
    <div id="appointmentPage" class="w-full fade-in">

        @php
        $latestPastVisit = $pastVisits->first();
        $latestPastDate =
        $latestPastVisit && $latestPastVisit->appointment_date
        ? \Carbon\Carbon::parse($latestPastVisit->appointment_date)->format('M d, Y')
        : 'No record yet';

        $allAppointments = collect($appointments ?? [])->filter(fn($appt) => !empty($appt->appointment_date));

        $serviceStats = $allAppointments
            ->groupBy(fn($appt) => trim((string)($appt->service_type ?? 'General Consultation')))
            ->map(fn($group) => $group->count())
            ->sortDesc();

        $mostVisitedService = $serviceStats->keys()->first() ?: 'No visit yet';
        $mostVisitedCount = (int) ($serviceStats->first() ?? 0);

        $latestCompleted = collect($pastVisits ?? [])
            ->filter(function ($appt) {
                $status = strtolower(trim((string) ($appt->status ?? '')));
                return !empty($appt->appointment_date) && $status === 'completed';
            })
            ->sortByDesc('appointment_date')
            ->first();

        $latestCompletedText = $latestCompleted && $latestCompleted->appointment_date
            ? \Carbon\Carbon::parse($latestCompleted->appointment_date)->format('M d, Y')
            : 'No completed visit yet';

        $nextRecommendedDate = $latestCompleted && $latestCompleted->appointment_date
            ? \Carbon\Carbon::parse($latestCompleted->appointment_date)->addMonthsNoOverflow(6)
            : \Carbon\Carbon::today()->addMonthsNoOverflow(6);

        $nextRecommendedText = $nextRecommendedDate->format('M d, Y');
        $daysUntilRecommended = \Carbon\Carbon::today()->diffInDays($nextRecommendedDate->copy()->startOfDay(), false);
        $recommendedHint = $daysUntilRecommended < 0
            ? 'Due now'
            : ($daysUntilRecommended === 0 ? 'Due today' : 'In ' . $daysUntilRecommended . ' days');
        @endphp

        <section class="appt-section-reveal mb-5">
            <div class="appt-summary-grid">
                <div class="appt-summary-card">
                    <div class="flex items-center gap-4">
                        <div class="appt-summary-icon">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-gray-400">Next Visit
                            </p>
                            <h3 class="mt-1 text-lg font-extrabold text-gray-900 dark:text-gray-100">
                                {{ $futureVisits->count()
                                ? \Carbon\Carbon::parse($futureVisits->first()->appointment_date)->format('M d, Y')
                                : 'None' }}
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="appt-summary-card">
                    <div class="flex items-center gap-4">
                        <div class="appt-summary-icon">
                            <i class="fa-solid fa-tooth"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-gray-400">Total Visits
                            </p>
                            <h3 class="mt-1 text-lg font-extrabold text-gray-900 dark:text-gray-100">
                                {{ $pastVisits->count() }}
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="appt-summary-card">
                    <div class="flex items-center gap-4">
                        <div class="appt-summary-icon">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-gray-400">Last Visit
                            </p>
                            <h3 class="mt-1 text-lg font-extrabold text-gray-900 dark:text-gray-100">
                                {{ $latestPastDate }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="appt-tip-card mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div
                        class="w-10 h-10 rounded-full bg-[#8B0000]/10 text-[#8B0000] dark:text-[#FCA5A5] flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Dental Care Tip</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Regular check-ups are recommended every 6 months to keep your oral health on track.
                        </p>
                    </div>
                </div>

                <a href="{{ route('patient.book.appointment') }}" class="appt-secondary-btn">
                    <i class="fa-solid fa-calendar-plus"></i>
                    Schedule Check-Up
                </a>
            </div>
        </section>

        <section class="fade-up mb-6 sm:mb-8">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-stretch">
                <div class="xl:col-span-4">
                    <div class="appt-calendar-side">
                        <div class="appt-calendar-side-card">
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-[#8B0000] dark:text-[#FCA5A5]">
                                Monthly Highlights
                            </p>
                            <h3 class="mt-1 text-lg font-extrabold text-gray-900 dark:text-gray-100">Your Visit Patterns</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Personalized summary based on your appointment history.</p>

                            <div class="mt-4 space-y-2.5">
                                <div class="appt-calendar-side-stat flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400">Most Visited Service</span>
                                    <span class="text-sm font-extrabold text-emerald-700 dark:text-emerald-300">{{ $mostVisitedCount > 0 ? $mostVisitedCount . 'x' : '—' }}</span>
                                </div>
                                <p class="px-1 -mt-1 text-xs font-semibold text-gray-700 dark:text-gray-200 truncate">{{ $mostVisitedService }}</p>

                                <div class="appt-calendar-side-stat flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400">Last Completed Visit</span>
                                    <span class="text-xs font-extrabold text-blue-700 dark:text-blue-300">{{ $latestCompletedText }}</span>
                                </div>

                                <div class="appt-calendar-side-stat flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400">Next Recommended Checkup</span>
                                    <span class="text-xs font-extrabold text-amber-700 dark:text-amber-300">{{ $nextRecommendedText }}</span>
                                </div>
                                <p class="px-1 -mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $recommendedHint }}</p>
                            </div>
                        </div>

                        <div class="appt-calendar-side-card">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-extrabold text-gray-800 dark:text-gray-200">6-Month Activity</p>
                                <div class="flex items-center gap-2 text-[10px] font-bold">
                                    <span class="inline-flex items-center gap-1 text-emerald-700 dark:text-emerald-300"><i class="fa-solid fa-circle text-[8px]"></i>Completed</span>
                                    <span class="inline-flex items-center gap-1 text-orange-700 dark:text-orange-300"><i class="fa-solid fa-circle text-[8px]"></i>Cancelled</span>
                                </div>
                            </div>

                            <div class="appt-mini-chart">
                                <canvas id="apptActivityChart" aria-label="Appointment activity chart" role="img"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-8">
                    <div id="calendarSkeletonContainer" class="w-full h-full min-h-[420px] skeleton-fade-swap"></div>
                </div>
            </div>

            <div class="appt-quick-actions">
                <a href="{{ route('patient.book.appointment') }}" class="appt-quick-btn">
                    <i class="fa-solid fa-calendar-plus"></i>
                    Rebook Appointment
                </a>
                <button type="button" class="appt-quick-btn" onclick="apptShowPast(); document.getElementById('apptPastPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    View Past Visits
                </button>
                <button type="button" class="appt-quick-btn" onclick="scrollToAppointmentCalendar()">
                    <i class="fa-solid fa-calendar-days"></i>
                    Focus Calendar
                </button>
            </div>
        </section>

        <section class="fade-up mb-8 sm:mb-10">

            <div class="flex flex-col md:flex-row md:items-end justify-between mb-6 gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#660000] dark:text-[#ff6b6b] leading-tight">My
                        Appointments</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        You have {{ $futureVisits->count() }} upcoming
                        {{ $futureVisits->count() === 1 ? 'visit' : 'visits' }} scheduled
                    </p>
                </div>
            </div>

            @php
            $futureCount = $futureVisits->count();
            $pastCount = $pastVisits->count();
            @endphp

            <div
                class="flex flex-row bg-white dark:bg-[#07111f] rounded-xl p-1 shadow-sm border border-gray-200 dark:border-[#1f2d3d] mb-6 w-full md:w-max">
                <button
                    class="appt-tab appt-active flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 text-[13px] font-bold rounded-lg text-gray-500 transition-all"
                    id="apptFutureTab" onclick="apptShowFuture()">
                    Future Visits
                    <span
                        class="appt-count text-[11px] font-extrabold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 transition-all">{{
                        $futureCount }}</span>
                </button>
                <button
                    class="appt-tab flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 text-[13px] font-bold rounded-lg text-gray-500 transition-all"
                    id="apptPastTab" onclick="apptShowPast()">
                    Past Visits
                    <span
                        class="appt-count text-[11px] font-extrabold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 transition-all">{{
                        $pastCount }}</span>
                </button>
            </div>

            <div id="apptFuturePanel">
                @if ($futureVisits->count())
                <div class="text-xs font-bold tracking-[0.12em] uppercase text-gray-400 mb-4 flex items-center gap-3">
                    Upcoming <div class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></div>
                </div>

                @foreach ($futureVisits as $index => $appt)
                @php
                $apptDate = \Carbon\Carbon::parse($appt->appointment_date);
                $apptTime = \Carbon\Carbon::parse($appt->appointment_time);
                $now = \Carbon\Carbon::now();
                $diffDays = (int) $now
                ->startOfDay()
                ->diffInDays($apptDate->copy()->startOfDay(), false);
                if ($diffDays === 0) {
                $countdown = 'Today';
                } elseif ($diffDays === 1) {
                $countdown = 'Tomorrow';
                } else {
                $countdown = 'In ' . $diffDays . ' days';
                }

                $rawStatus = strtolower($appt->status ?? 'scheduled');
                $badgeColors = match ($rawStatus) {
                'upcoming' => 'bg-orange-100 text-orange-700',
                'confirmed' => 'bg-emerald-100 text-emerald-700',
                default => 'bg-orange-100 text-orange-700',
                };
                $showDot = in_array($rawStatus, ['upcoming', 'scheduled']);
                @endphp

                <div class="bg-white dark:bg-[#0a1628] border border-gray-100 dark:border-[#1a2a3a] rounded-[1.25rem] shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden flex flex-col sm:flex-row mb-4 animate-[apptSlideUp_0.35s_ease_backwards]"
                    style="animation-delay: {{ $index * 0.08 }}s">

                    <div
                        class="bg-red-50/70 dark:bg-[#111827] flex flex-row sm:flex-col items-center sm:justify-center px-5 py-4 sm:py-6 sm:w-[100px] border-b sm:border-b-0 sm:border-r border-red-100 dark:border-[#1a2a3a] gap-3 sm:gap-0 flex-shrink-0">
                        <span
                            class="text-3xl sm:text-4xl font-extrabold text-[#8B0000] dark:text-[#ff6b6b] leading-none">{{
                            $apptDate->format('d') }}</span>
                        <div class="flex sm:flex-col items-baseline sm:items-center gap-1 sm:gap-0 mt-0 sm:mt-1">
                            <span class="text-xs font-bold tracking-widest uppercase text-red-700 dark:text-red-400">{{
                                $apptDate->format('M') }}</span>
                            <span class="text-[11px] text-gray-500">{{ $apptDate->format('Y') }}</span>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 flex-1 flex flex-col gap-3 min-w-0 justify-center">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-base font-bold text-gray-800 dark:text-gray-200 truncate">{{
                                $appt->service_type }}{{ $appt->other_services ? ' (' . $appt->other_services . ')' : ''
                                }}</span>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase {{ $badgeColors }}">
                                @if ($showDot)
                                <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
                                @endif
                                {{ ucfirst($rawStatus) }}
                            </span>
                        </div>
                        <div
                            class="flex flex-wrap items-center gap-4 text-sm font-medium text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1.5"><i
                                    class="fa-regular fa-clock text-red-700/70 dark:text-red-400"></i> <strong
                                    class="text-gray-700 dark:text-gray-300">{{ $apptTime->format('g:i A') }} –
                                    {{ $apptTime->copy()->addHour()->format('g:i A') }}</strong></div>
                            <div class="flex items-center gap-1.5"><i
                                    class="fa-regular fa-user text-red-700/70 dark:text-red-400"></i> Dr. Nelson
                                Angeles</div>
                        </div>
                    </div>

                    <div
                        class="p-4 sm:p-5 border-t sm:border-t-0 border-gray-50 dark:border-[#1a2a3a] flex flex-row sm:flex-col items-center sm:items-end justify-between gap-3 flex-shrink-0 bg-gray-50/30 dark:bg-transparent">
                        <button
                            class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-[#8B0000] hover:bg-red-50 dark:hover:bg-gray-800 transition-colors">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <span
                            class="px-3 py-1.5 rounded-full bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/50 text-[#8B0000] dark:text-red-400 text-xs font-bold whitespace-nowrap">{{
                            $countdown }}</span>
                    </div>
                </div>
                @endforeach
                @else
                <div class="appt-timeline-empty">
                    <div class="appt-timeline-empty-grid">
                        <div>
                            <div class="mb-4 text-center sm:text-left">
                                <div class="appt-empty-hero-icon">
                                    <i class="fa-regular fa-calendar-xmark"></i>
                                </div>

                                <p
                                    class="text-xs font-extrabold uppercase tracking-[0.16em] text-[#8B0000] dark:text-[#FCA5A5]">
                                    Appointment Timeline
                                </p>

                                <h3 class="mt-1 text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-gray-100">
                                    No upcoming visit yet
                                </h3>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                                    You can book your next dental visit now or check the calendar for available dates.
                                </p>
                            </div>

                            <div class="appt-timeline-path">
                                <div class="appt-timeline-step">
                                    <span class="appt-timeline-dot active">
                                        <i class="fa-solid fa-user-check"></i>
                                    </span>
                                    <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Profile ready</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Your patient account is ready
                                        for
                                        booking.</p>
                                </div>

                                <div class="appt-timeline-step">
                                    <span class="appt-timeline-dot">
                                        <i class="fa-regular fa-calendar-plus"></i>
                                    </span>
                                    <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Choose a date</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Pick an available schedule from
                                        the
                                        clinic calendar.</p>
                                </div>

                                <div class="appt-timeline-step">
                                    <span class="appt-timeline-dot">
                                        <i class="fa-solid fa-hospital"></i>
                                    </span>
                                    <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Visit the clinic
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Your confirmed appointment will
                                        appear here.</p>
                                </div>
                            </div>
                        </div>

                        <div class="appt-recommended-card">
                            <div class="flex items-center gap-3">
                                <div class="appt-recommended-date">
                                    <span class="text-[10px] font-black uppercase opacity-80">Next</span>
                                    <span class="text-xl font-black">6</span>
                                    <span class="text-[10px] font-black uppercase opacity-80">Months</span>
                                </div>

                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.13em] text-white/70">Recommended
                                    </p>
                                    <h4 class="mt-1 text-lg font-extrabold leading-tight">Routine Check-Up</h4>
                                    <p class="mt-1 text-xs text-white/75">Keep your oral health on track.</p>
                                </div>
                            </div>

                            <a href="{{ route('patient.book.appointment') }}" class="appt-recommended-btn">
                                <i class="fa-solid fa-calendar-plus"></i>
                                Schedule Now
                            </a>

                            <button type="button" onclick="scrollToAppointmentCalendar()"
                                class="mt-2 w-full text-xs font-bold text-white/80 hover:text-white transition">
                                Check available dates
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div id="apptPastPanel" style="display:none;">
                @if ($pastVisits->count())
                <div class="text-xs font-bold tracking-[0.12em] uppercase text-gray-400 mb-4 flex items-center gap-3">
                    Recent History <div class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></div>
                </div>

                @foreach ($pastVisits as $index => $appt)
                @php
                $apptDate = \Carbon\Carbon::parse($appt->appointment_date);
                $apptTime = \Carbon\Carbon::parse($appt->appointment_time);
                $rawStatus = 'completed';
                $dentistName = optional($clinicDentist)->name ?? 'Assigned Dentist';
                $dentistRole = optional(optional($clinicDentist)->role)->name ?? 'Dentist';

                $modalPayload = [
                'service' => $appt->service_type ?? '—',
                'date' => $appt->appointment_date
                ? \Carbon\Carbon::parse($appt->appointment_date)->format('F d, Y')
                : '—',
                'time' => $appt->appointment_time
                ? \Carbon\Carbon::parse($appt->appointment_time)->format('g:i A')
                : '—',
                'status' => $rawStatus,
                'duration' => $appt->duration ?? '—',
                'remarks' => $appt->remarks ?? '—',
                'oral' => $appt->oral_examination ?? '—',
                'diagnosis' => $appt->diagnosis ?? '—',
                'prescription' => $appt->prescription ?? '—',
                'dentist_name' => $dentistName,
                'dentist_label' => $dentistRole,
                'dentist_initials' => collect(explode(' ', $dentistName))
                ->filter()
                ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                ->take(2)
                ->implode(''),
                'odontogram' => $odontogramTeeth ?? [],
                ];
                @endphp

                <div class="bg-white dark:bg-[#0a1628] border border-gray-100 dark:border-[#1a2a3a] rounded-[1.25rem] shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden flex flex-col sm:flex-row mb-4 animate-[apptSlideUp_0.35s_ease_backwards]"
                    style="animation-delay: {{ $index * 0.08 }}s">

                    <div
                        class="bg-gray-50 dark:bg-[#111827] flex flex-row sm:flex-col items-center sm:justify-center px-5 py-4 sm:py-6 sm:w-[100px] border-b sm:border-b-0 sm:border-r border-gray-100 dark:border-[#1a2a3a] gap-3 sm:gap-0 flex-shrink-0 opacity-80">
                        <span class="text-3xl sm:text-4xl font-extrabold text-gray-500 leading-none">{{
                            $apptDate->format('d') }}</span>
                        <div class="flex sm:flex-col items-baseline sm:items-center gap-1 sm:gap-0 mt-0 sm:mt-1">
                            <span class="text-xs font-bold tracking-widest uppercase text-gray-400">{{
                                $apptDate->format('M') }}</span>
                            <span class="text-[11px] text-gray-400">{{ $apptDate->format('Y') }}</span>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 flex-1 flex flex-col gap-3 min-w-0 justify-center">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-base font-bold text-gray-600 dark:text-gray-400 truncate">{{
                                $appt->service_type }}{{ $appt->other_services ? ' (' . $appt->other_services . ')' : ''
                                }}</span>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                                <i class="fa-solid fa-user-check"></i> Completed
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-sm font-medium text-gray-400">
                            <div class="flex items-center gap-1.5"><i class="fa-regular fa-clock"></i> <strong
                                    class="text-gray-500 dark:text-gray-400">{{ $apptTime->format('g:i A') }}
                                    –
                                    {{ $apptTime->copy()->addHour()->format('g:i A') }}</strong></div>
                            <div class="flex items-center gap-1.5"><i class="fa-regular fa-user"></i> Dr.
                                Nelson
                                Angeles</div>
                        </div>
                    </div>

                    <div
                        class="p-4 sm:p-5 border-t sm:border-t-0 border-gray-50 dark:border-[#1a2a3a] flex flex-row sm:flex-col items-center sm:items-end justify-between gap-3 flex-shrink-0 bg-gray-50/30 dark:bg-transparent">
                        <button
                            class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-[#8B0000] hover:bg-red-50 dark:hover:bg-gray-800 transition-colors hidden sm:flex">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <button type="button"
                            class="px-4 py-1.5 rounded-full bg-white dark:bg-[#111827] border border-gray-200 dark:border-[#21262d] text-gray-700 dark:text-gray-300 hover:bg-[#8B0000] hover:text-white hover:border-[#8B0000] transition-colors text-xs font-bold"
                            data-appt='@json($modalPayload)' onclick="openApptDetailModal(this)">
                            View Details
                        </button>
                    </div>
                </div>
                @endforeach
                @else
                <div class="appt-empty-state text-center">
                    <div class="appt-empty-icon">
                        <i class="fa-regular fa-folder-open text-3xl"></i>
                    </div>

                    <p class="text-lg font-extrabold text-gray-800 dark:text-gray-200 mt-2">
                        No Past Visits Yet
                    </p>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Your completed appointments will appear here.
                    </p>

                    <button onclick="apptShowFuture()" class="appt-secondary-btn mt-4">
                        <i class="fa-solid fa-calendar-plus"></i>
                        Book First Appointment
                    </button>
                </div>
                @endif
            </div>

        </section>

        <section class="mt-2 mb-8 fade-up">
            <div class="flex items-end justify-between gap-3 mb-4 sm:mb-5">
                <div>
                    <h2 class="text-[1.6rem] sm:text-[2rem] font-extrabold text-[#8B0000] leading-tight">
                        Services Offered
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Available dental care services at the clinic.
                    </p>
                </div>
            </div>

            <div class="services-grid">
                <div class="service-card">
                    <div class="service-card-icon">
                        <img src="{{ asset('images/oral-checkup.png') }}" alt="Oral Checkup">
                    </div>
                    <h3 class="service-card-title">Oral Check-Up</h3>
                    <p class="service-card-desc">
                        Routine oral examination and dental consultation for general assessment.
                    </p>
                    <span class="service-card-tag">Consultation</span>
                </div>

                <div class="service-card">
                    <div class="service-card-icon">
                        <img src="{{ asset('images/dental-cleaning.png') }}" alt="Dental Cleaning">
                    </div>
                    <h3 class="service-card-title">Dental Cleaning</h3>
                    <p class="service-card-desc">
                        Oral hygiene treatment focused on removing plaque and surface buildup.
                    </p>
                    <span class="service-card-tag">Preventive Care</span>
                </div>

                <div class="service-card">
                    <div class="service-card-icon">
                        <img src="{{ asset('images/restoration-prosthesis.png') }}" alt="Dental Restoration">
                    </div>
                    <h3 class="service-card-title">Dental Restoration</h3>
                    <p class="service-card-desc">
                        Restorative procedures for damaged teeth including fillings and crowns.
                    </p>
                    <span class="service-card-tag">Restoration</span>
                </div>

                <div class="service-card">
                    <div class="service-card-icon">
                        <img src="{{ asset('images/dental-surgery.png') }}" alt="Dental Surgery">
                    </div>
                    <h3 class="service-card-title">Dental Surgery</h3>
                    <p class="service-card-desc">
                        Surgical dental treatment such as tooth extraction and related procedures.
                    </p>
                    <span class="service-card-tag">Surgical Care</span>
                </div>
            </div>
        </section>

        <dialog id="activeAppointmentModal" class="modal">
            <div
                class="modal-box p-8 rounded-[1.5rem] bg-white dark:bg-[#000D1A] text-center shadow-2xl w-[min(92vw,400px)]">
                <div
                    class="mx-auto mb-5 w-20 h-20 rounded-full bg-red-50 flex items-center justify-center border-4 border-white shadow-sm">
                    <i class="fa-solid fa-calendar-xmark text-[#8B0000] text-3xl"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-800 dark:text-gray-100 mb-3">One Appointment at a Time
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">
                    {{ session('activeAppointmentMsg') ??
                    'You already have an active appointment. Please wait until it is
                    completed before booking another one.' }}
                </p>
                <div class="flex flex-col gap-3">
                    <a href="{{ route('patient.appointment.index') }}"
                        class="w-full py-3.5 rounded-xl bg-[#8B0000] text-white font-bold hover:bg-[#660000] transition-colors shadow-md">
                        <i class="fa-regular fa-calendar-check mr-2"></i> View My Appointment
                    </a>
                    <button type="button" id="closeActiveApptModalBtn"
                        class="w-full py-3.5 rounded-xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition-colors">Close</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>
    </div>
</main>

<div id="appt_detail_modal" class="appt-detail-modal hidden">
    <div class="appt-detail-backdrop" onclick="closeApptDetailModal()"></div>

    <div class="appt-detail-box">
        <div class="appt-sheet-handle">
            <span></span>
            <p>Swipe down to close</p>
        </div>
        <!-- HEADER -->
        <div class="appt-record-hero relative">
            <button onclick="closeApptDetailModal()" class="appt-record-close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <span class="appt-record-chip">
                <i class="fa-solid fa-circle-check"></i>
                Dental Record
            </span>

            <h3 id="d_service" class="mt-3 text-2xl font-extrabold">—</h3>

            <div class="mt-2 text-sm opacity-90">
                <i class="fa-regular fa-calendar"></i>
                <span id="d_date"></span> •
                <i class="fa-regular fa-clock"></i>
                <span id="d_time"></span>
            </div>
        </div>

        <!-- BODY -->
        <div class="appt-side-scroll appt-record-body">

            <div class="appt-record-info-grid">
                <div class="appt-info-card">
                    <p class="appt-info-label">Status</p>
                    <p id="d_status_text" class="appt-info-value">—</p>
                </div>

                <div class="appt-info-card">
                    <p class="appt-info-label">Duration</p>
                    <p id="d_duration" class="appt-info-value">—</p>
                </div>
            </div>

            <div class="appt-section-title">Attending Dentist</div>

            <div class="appt-dentist-card">
                <div id="d_dentist_initials" class="appt-dentist-avatar">AD</div>
                <div>
                    <p id="d_dentist_name" class="font-extrabold">Assigned Dentist</p>
                    <p id="d_dentist_label" class="text-sm opacity-70">Dental Clinic Dentist</p>
                </div>
            </div>

            <div id="apptOdontogramSection" class="hidden">
                <div class="appt-section-title">Odontogram</div>

                @include('components.view-odontogram')
            </div>

            <div class="appt-section-title">Clinical Details</div>

            <details class="appt-record-accordion" open>
                <summary>
                    <span class="appt-record-accordion-icon">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </span>

                    <span class="flex flex-col sm:flex-row sm:items-center gap-0 sm:gap-2">
                        <span>Treatment</span>
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-400">remarks</span>
                    </span>

                    <span class="appt-record-chevron">
                        <i class="fa-solid fa-angle-down"></i>
                    </span>
                </summary>

                <div class="appt-record-panel">
                    <div id="d_remarks">—</div>
                </div>
            </details>

            <details class="appt-record-accordion">
                <summary>
                    <span class="appt-record-accordion-icon">
                        <i class="fa-solid fa-eye"></i>
                    </span>

                    <span>Oral Examination</span>

                    <span class="appt-record-chevron">
                        <i class="fa-solid fa-angle-down"></i>
                    </span>
                </summary>

                <div class="appt-record-panel">
                    <div id="d_oral">—</div>
                </div>
            </details>

            <details class="appt-record-accordion">
                <summary>
                    <span class="appt-record-accordion-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </span>

                    <span>Diagnosis</span>

                    <span class="appt-record-chevron">
                        <i class="fa-solid fa-angle-down"></i>
                    </span>
                </summary>

                <div class="appt-record-panel">
                    <div id="d_diagnosis">—</div>
                </div>
            </details>

            <details class="appt-record-accordion">
                <summary>
                    <span class="appt-record-accordion-icon">
                        <i class="fa-solid fa-prescription-bottle-medical"></i>
                    </span>

                    <span>Prescription</span>

                    <span class="appt-record-chevron">
                        <i class="fa-solid fa-angle-down"></i>
                    </span>
                </summary>

                <div class="appt-record-panel">
                    <div id="d_prescription">—</div>
                </div>
            </details>
        </div>
    </div>
</div>
@include('components.appointment-calendar-script', [
'mode' => 'patient-appointment',
'renderStyle' => 'patient',
'calendarContainerId' => 'calendarSkeletonContainer',
'dateInputId' => null,
'timeInputId' => null,
'slotEndpoint' => route('book.appointment.slots'),
'blockedDates' => $unavailableDates ?? [],
'appointmentCountsPerDay' => $appointmentCountsPerDay ?? [],
'philippineHolidays' => $philippineHolidays ?? [],
'personalAppointments' => $calendarAppointments ?? [],
'useDynamicScheduleRules' => false,
'disallowToday' => false,
'allowToggleOffDate' => false,
])
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    let apptActivityChartInstance = null;

    function initAppointmentActivityChart() {
        const canvas = document.getElementById('apptActivityChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const dataRows = Array.isArray(window.apptActivityChartData) ? window.apptActivityChartData : [];
        const labels = dataRows.map(row => row.label || '');
        const completed = dataRows.map(row => Number(row.completed || 0));
        const cancelled = dataRows.map(row => Number(row.cancelled || 0));

        if (apptActivityChartInstance) {
            apptActivityChartInstance.destroy();
        }

        apptActivityChartInstance = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Completed',
                        data: completed,
                        backgroundColor: '#10B981',
                        hoverBackgroundColor: '#059669',
                        borderColor: 'rgba(4, 120, 87, 0.18)',
                        hoverBorderColor: '#047857',
                        borderWidth: 1,
                        hoverBorderWidth: 2,
                        borderRadius: 8,
                        maxBarThickness: 16,
                    },
                    {
                        label: 'Cancelled',
                        data: cancelled,
                        backgroundColor: '#F97316',
                        hoverBackgroundColor: '#EA580C',
                        borderColor: 'rgba(194, 65, 12, 0.18)',
                        hoverBorderColor: '#C2410C',
                        borderWidth: 1,
                        hoverBorderWidth: 2,
                        borderRadius: 8,
                        maxBarThickness: 16,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#6B7280',
                            font: { size: 10, weight: '700' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#9CA3AF',
                            font: { size: 10 }
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)'
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        borderColor: 'rgba(255, 255, 255, 0.16)',
                        borderWidth: 1,
                        titleColor: '#F9FAFB',
                        bodyColor: '#E5E7EB',
                        cornerRadius: 10,
                        padding: 10,
                        boxPadding: 4,
                        displayColors: true,
                        usePointStyle: true,
                    }
                }
            }
        });
    }

    function apptAnimatePanel(panel) {
        if (!panel) return;

        panel.classList.remove('appt-panel-fade');
        void panel.offsetWidth;
        panel.classList.add('appt-panel-fade');
    }

    function apptShowFuture() {
        const futurePanel = document.getElementById('apptFuturePanel');
        const pastPanel = document.getElementById('apptPastPanel');

        futurePanel.style.display = '';
        pastPanel.style.display = 'none';

        document.getElementById('apptFutureTab').classList.add('appt-active');
        document.getElementById('apptPastTab').classList.remove('appt-active');

        apptAnimatePanel(futurePanel);
    }

    function apptShowPast() {
        const futurePanel = document.getElementById('apptFuturePanel');
        const pastPanel = document.getElementById('apptPastPanel');

        futurePanel.style.display = 'none';
        pastPanel.style.display = '';

        document.getElementById('apptPastTab').classList.add('appt-active');
        document.getElementById('apptFutureTab').classList.remove('appt-active');

        apptAnimatePanel(pastPanel);
    }

    function initApptAccordions() {
        document.querySelectorAll('.appt-record-accordion').forEach(function (acc) {
            const summary = acc.querySelector('summary');
            const panel = acc.querySelector('.appt-record-panel');

            if (!summary || !panel || acc.dataset.ready === 'true') return;
            acc.dataset.ready = 'true';

            if (acc.hasAttribute('open')) {
                acc.classList.add('is-open');
                panel.style.height = 'auto';
                panel.style.opacity = '1';
            }

            summary.addEventListener('click', function (e) {
                e.preventDefault();

                if (acc.classList.contains('is-animating')) return;

                if (acc.hasAttribute('open')) {
                    closeAccordion(acc);
                } else {
                    openAccordion(acc);
                }
            });
        });
    }

    function openAccordion(acc) {
        const panel = acc.querySelector('.appt-record-panel');
        if (!panel) return;

        acc.classList.add('is-animating');
        acc.setAttribute('open', true);
        acc.classList.add('is-open');

        panel.style.height = '0px';
        panel.style.opacity = '0';

        requestAnimationFrame(function () {
            panel.style.height = panel.scrollHeight + 'px';
            panel.style.opacity = '1';
        });

        setTimeout(function () {
            panel.style.height = 'auto';
            acc.classList.remove('is-animating');
        }, 310);
    }

    function closeAccordion(acc) {
        const panel = acc.querySelector('.appt-record-panel');
        if (!panel) return;

        acc.classList.add('is-animating');

        panel.style.height = panel.scrollHeight + 'px';

        requestAnimationFrame(function () {
            acc.classList.remove('is-open');
            panel.style.height = '0px';
            panel.style.opacity = '0';
        });

        setTimeout(function () {
            acc.removeAttribute('open');
            acc.classList.remove('is-animating');
        }, 310);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initApptAccordions();
        initAppointmentActivityChart();
    });

    let viewOdontogramData = [];

    const voTeeth = {
        upperRight: [18, 17, 16, 15, 14, 13, 12, 11],
        upperLeft: [21, 22, 23, 24, 25, 26, 27, 28],
        lowerRight: [48, 47, 46, 45, 44, 43, 42, 41],
        lowerLeft: [31, 32, 33, 34, 35, 36, 37, 38],
    };

    function voToothName(n) {
        const names = {
            18: 'Upper Right · 3rd Molar',
            17: 'Upper Right · 2nd Molar',
            16: 'Upper Right · 1st Molar',
            15: 'Upper Right · 2nd Premolar',
            14: 'Upper Right · 1st Premolar',
            13: 'Upper Right · Canine',
            12: 'Upper Right · Lateral Incisor',
            11: 'Upper Right · Central Incisor',
            21: 'Upper Left · Central Incisor',
            22: 'Upper Left · Lateral Incisor',
            23: 'Upper Left · Canine',
            24: 'Upper Left · 1st Premolar',
            25: 'Upper Left · 2nd Premolar',
            26: 'Upper Left · 1st Molar',
            27: 'Upper Left · 2nd Molar',
            28: 'Upper Left · 3rd Molar',
            48: 'Lower Right · 3rd Molar',
            47: 'Lower Right · 2nd Molar',
            46: 'Lower Right · 1st Molar',
            45: 'Lower Right · 2nd Premolar',
            44: 'Lower Right · 1st Premolar',
            43: 'Lower Right · Canine',
            42: 'Lower Right · Lateral Incisor',
            41: 'Lower Right · Central Incisor',
            31: 'Lower Left · Central Incisor',
            32: 'Lower Left · Lateral Incisor',
            33: 'Lower Left · Canine',
            34: 'Lower Left · 1st Premolar',
            35: 'Lower Left · 2nd Premolar',
            36: 'Lower Left · 1st Molar',
            37: 'Lower Left · 2nd Molar',
            38: 'Lower Left · 3rd Molar',
        };

        return names[n] || `Tooth #${n}`;
    }

    function voConditionFromRecord(record) {
        if (!record) return 'healthy';

        const legends = Array.isArray(record.legends) ? record.legends : [];

        const allCodes = legends
            .map(l => String(l.code || l.description || '').toLowerCase())
            .join(' ');

        if (allCodes.includes('x') || allCodes.includes('extract')) return 'extracted';
        if (allCodes.includes('m') || allCodes.includes('missing')) return 'missing';
        if (allCodes.includes('f') || allCodes.includes('fill')) return 'filled';
        if (allCodes.includes('d') || allCodes.includes('decay') || allCodes.includes('caries')) return 'decay';
        if (allCodes.includes('jc') || allCodes.includes('crown')) return 'crown';

        return 'healthy';
    }

    function voFindRecord(tooth) {
        return viewOdontogramData.find(item => Number(item.tooth) === Number(tooth)) || null;
    }

    function renderViewOdontogram(rawData) {
        const board = document.getElementById('viewOdontogramBoard');
        if (!board) return;

        try {
            viewOdontogramData = typeof rawData === 'string' ? JSON.parse(rawData || '[]') : (rawData || []);
        } catch (e) {
            viewOdontogramData = [];
        }

        function toothHtml(n, bottom = false) {
            const record = voFindRecord(n);
            const condition = voConditionFromRecord(record);

            return `
            <button type="button" class="vo-tooth ${condition}" onclick="openViewToothModal(${n})">
                ${bottom ? '<div class="vo-root"></div>' : ''}
                <div class="vo-num">${n}</div>
                <div class="vo-box">${condition === 'extracted' ? '<i class="fa-solid fa-xmark"></i>' : ''}</div>
                ${!bottom ? '<div class="vo-root"></div>' : ''}
            </button>
        `;
        }

        const upper = [...voTeeth.upperRight, ...voTeeth.upperLeft].map(n => toothHtml(n)).join('');
        const lower = [...voTeeth.lowerRight, ...voTeeth.lowerLeft].map(n => toothHtml(n, true)).join('');

        board.innerHTML = `
        <div class="vo-row">${upper}</div>
        <div class="vo-arch-line">Maxilla · Mandible</div>
        <div class="vo-row">${lower}</div>
    `;
    }

    function openViewToothModal(tooth) {
        const record = voFindRecord(tooth);
        const condition = voConditionFromRecord(record);
        const name = voToothName(tooth);
        const parts = name.split('·').map(x => x.trim());

        document.getElementById('voToothTitle').textContent = `Tooth #${tooth}`;
        document.getElementById('voToothSubtitle').textContent = name;
        document.getElementById('voToothName').textContent = `#${tooth} — ${parts[1] || 'Tooth'}`;
        document.getElementById('voFdi').textContent = `#${tooth}`;
        document.getElementById('voQuadrant').textContent = parts[0] || '—';
        document.getElementById('voToothType').textContent = parts[1] || '—';
        document.getElementById('voArch').textContent = String(tooth).startsWith('1') || String(tooth).startsWith('2')
            ? 'Maxillary (Upper)'
            : 'Mandibular (Lower)';

        document.getElementById('voToothCondition').textContent =
            condition.charAt(0).toUpperCase() + condition.slice(1);

        document.getElementById('voTreatedBadge').classList.toggle('hidden', !record);

        document.getElementById('voToothVisual').innerHTML = `
        <div class="vo-big-tooth">
            ${condition === 'extracted' ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-tooth"></i>'}
        </div>
    `;

        document.getElementById('voTreatmentHistory').innerHTML = record
            ? `
            <div class="vo-history-item">
                <span class="vo-history-dot"></span>
                <span>${document.getElementById('d_date')?.textContent || 'Visit date'}</span>
                <strong>${document.getElementById('d_service')?.textContent || 'Dental Treatment'}</strong>
            </div>
        `
            : `
            <div class="appt-empty-mini">
                <div class="appt-empty-mini-icon">
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <p class="appt-empty-mini-title">No treatment history</p>
                <p class="appt-empty-mini-text">No saved treatment for this tooth yet.</p>
            </div>
        `;

        document.getElementById('viewToothModal').classList.remove('hidden');
    }

    function closeViewToothModal() {
        document.getElementById('viewToothModal')?.classList.add('hidden');
    }

    function openApptDetailModal(btn) {
        const modal = document.getElementById('appt_detail_modal');
        if (!modal) return;

        let data = {};
        try {
            data = JSON.parse(btn.getAttribute('data-appt') || '{}');
        } catch (e) { }

        function set(id, val, fallback = '—') {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = val && val !== '—' ? val : fallback;
        }

        function setPanel(id, val, title, icon) {
            const el = document.getElementById(id);
            if (!el) return;

            const clean = val && val !== '—' && String(val).trim() !== '' ? String(val).trim() : '';

            if (clean) {
                el.textContent = clean;
                return;
            }

            el.innerHTML = `
                <div class="appt-empty-mini">
                    <div class="appt-empty-mini-icon">
                        <i class="${icon}"></i>
                    </div>
                    <p class="appt-empty-mini-title">No ${title} recorded</p>
                    <p class="appt-empty-mini-text">This section has no saved details yet.</p>
                </div>
            `;
        }

        set('d_service', data.service);
        set('d_date', data.date);
        set('d_time', data.time);
        set('d_duration', data.duration);
        set('d_status_text', data.status);
        set('d_dentist_name', data.dentist_name, 'Assigned Dentist');
        set('d_dentist_label', data.dentist_label, 'Dentist');
        set('d_dentist_initials', data.dentist_initials, 'AD');

        const odoSection = document.getElementById('apptOdontogramSection');
        const isCompleted = String(data.status || '').toLowerCase() === 'completed';

        if (odoSection) {
            odoSection.classList.toggle('hidden', !isCompleted);

            if (isCompleted) {
                renderViewOdontogram(data.odontogram || window.patientOdontogramTeeth || []);
            }
        }

        setPanel('d_remarks', data.remarks, 'treatment remarks', 'fa-solid fa-clipboard-list');
        setPanel('d_oral', data.oral, 'oral examination', 'fa-solid fa-eye');
        setPanel('d_diagnosis', data.diagnosis, 'diagnosis', 'fa-solid fa-circle-info');
        setPanel('d_prescription', data.prescription, 'prescription', 'fa-solid fa-prescription-bottle-medical');

        modal.classList.remove('hidden');

        const sheet = modal.querySelector('.appt-detail-box');
        const backdrop = modal.querySelector('.appt-detail-backdrop');

        if (sheet) {
            sheet.style.opacity = '';
            sheet.style.transform = '';
        }

        if (backdrop) {
            backdrop.style.opacity = '';
        }

        document.documentElement.style.overflow = 'hidden';

        initApptAccordions();
        initApptSwipeToClose();
    }

    function closeApptDetailModal() {
        const modal = document.getElementById('appt_detail_modal');
        const sheet = modal ? modal.querySelector('.appt-detail-box') : null;
        const backdrop = modal ? modal.querySelector('.appt-detail-backdrop') : null;

        if (!modal || modal.classList.contains('hidden')) return;

        if (sheet) {
            sheet.style.transition = 'transform .22s ease, opacity .18s ease';
            sheet.style.transform = window.innerWidth <= 640
                ? 'translateY(100%)'
                : 'translateY(10px) scale(.98)';
            sheet.style.opacity = '0';
        }

        if (backdrop) {
            backdrop.style.transition = 'opacity .18s ease';
            backdrop.style.opacity = '0';
        }

        setTimeout(function () {
            modal.classList.add('hidden');
            document.documentElement.style.overflow = '';

            if (sheet) {
                sheet.style.transition = '';
                sheet.style.transform = '';
                sheet.style.opacity = '';
            }

            if (backdrop) {
                backdrop.style.transition = '';
                backdrop.style.opacity = '';
            }
        }, 220);
    }

    let apptTouchStartY = 0;
    let apptTouchCurrentY = 0;
    let apptIsDragging = false;

    function initApptSwipeToClose() {
        const modal = document.getElementById('appt_detail_modal');
        const sheet = modal ? modal.querySelector('.appt-detail-box') : null;
        const
            dragArea = modal ? modal.querySelector('.appt-sheet-handle') : null;
        const backdrop = modal ? modal.querySelector('.appt-detail-backdrop') : null;

        if (!modal || !sheet || !dragArea || sheet.dataset.swipeReady === 'true') return;

        sheet.dataset.swipeReady = 'true';
        dragArea.style.touchAction = 'none';

        function resetSheet() {
            sheet.style.transition = '';
            sheet.style.transform = '';
            if (backdrop) backdrop.style.opacity = '';
        }

        dragArea.addEventListener('pointerdown', function (e) {
            if (window.innerWidth > 640) return;

            apptTouchStartY = e.clientY;
            apptTouchCurrentY = e.clientY;
            apptIsDragging = true;

            sheet.style.animation = 'none';
            sheet.style.transition = 'none';
            if (backdrop) backdrop.style.transition = 'none';

            dragArea.setPointerCapture(e.pointerId);
        });

        dragArea.addEventListener('pointermove', function (e) {
            if (!apptIsDragging || window.innerWidth > 640) return;

            apptTouchCurrentY = e.clientY;
            const diff = Math.max(0, apptTouchCurrentY - apptTouchStartY);
            const opacity = Math.max(0.15, 1 - diff / 280);

            sheet.style.transform = `translateY(${diff}px)`;
            if (backdrop) backdrop.style.opacity = opacity;
        });

        dragArea.addEventListener('pointerup', function (e) {
            if (!apptIsDragging || window.innerWidth > 640) return;

            const diff = Math.max(0, apptTouchCurrentY - apptTouchStartY);
            apptIsDragging = false;

            sheet.style.transition = 'transform .22s cubic-bezier(.22,1,.36,1)';
            if (backdrop) backdrop.style.transition = 'opacity .22s ease';

            if (diff > 75) {
                sheet.style.transform = 'translateY(110%)';
                if (backdrop) backdrop.style.opacity = '0';

                setTimeout(function () {
                    closeApptDetailModal();
                    resetSheet();
                }, 220);
            } else {
                resetSheet();
            }

            try {
                dragArea.releasePointerCapture(e.pointerId);
            } catch (err) { }
        });

        dragArea.addEventListener('pointercancel', resetSheet);
    }

    document.addEventListener('DOMContentLoaded', initApptSwipeToClose);

    function scrollToAppointmentCalendar() {
        var calendar = document.getElementById('calendarSkeletonContainer');
        if (!calendar) return;

        calendar.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        calendar.classList.add('calendar-focus-pulse');

        setTimeout(function () {
            calendar.classList.remove('calendar-focus-pulse');
        }, 1200);
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if (session('activeAppointmentModal'))
            var activeModal = document.getElementById("activeAppointmentModal");
        var closeActiveBtn = document.getElementById("closeActiveApptModalBtn");
        if (activeModal) {
            activeModal.showModal();
            activeModal.addEventListener('click', function (e) {
                var box = activeModal.querySelector('.modal-box');
                if (box && !box.contains(e.target)) e.preventDefault();
            });
            activeModal.addEventListener('cancel', function (e) {
                e.preventDefault();
            });
            if (closeActiveBtn) {
                closeActiveBtn.addEventListener("click", function () {
                    activeModal.close();
                });
            }
        }
        @endif
    });
</script>
@endsection