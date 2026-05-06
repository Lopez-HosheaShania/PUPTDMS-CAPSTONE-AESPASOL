@extends('layouts.dentist')

@section('title', 'Dentist Dashboard | PUP Taguig Dental Clinic')
@section('usesAppointmentCalendar', true)

@section('styles')
<style>
    .greeting-row {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: nowrap;
    }

    .greeting-title {
        font-size: clamp(1.25rem, 3.5vw, 2.1rem);
        line-height: 1.2;
        margin-bottom: 0 !important;
    }

    .greeting-title span {
        display: inline;
        word-break: break-word;
    }

    .greeting-banner {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 22px 24px;
        background:
            linear-gradient(135deg,
                rgba(139, 0, 0, 0.95) 0%,
                rgba(102, 0, 0, 0.92) 45%,
                rgba(139, 0, 0, 0.88) 100%);
        box-shadow: 0 14px 40px rgba(14, 116, 144, 0.18);
        border: 1px solid rgba(255, 255, 255, .28);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .greeting-banner::before,
    .greeting-banner::after {
        content: '';
        position: absolute;
        border-radius: 999px;
        background: rgba(255, 215, 0, 0.08);
        filter: blur(2px);
        pointer-events: none;
    }

    .greeting-banner::before {
        width: 280px;
        height: 280px;
        top: -170px;
        left: -60px;
    }

    .greeting-banner::after {
        width: 240px;
        height: 240px;
        right: -70px;
        bottom: -170px;
    }

    .greeting-banner-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .greeting-banner-copy h1 {
        color: #fff;
        font-size: clamp(1.45rem, 3.6vw, 2.35rem);
        line-height: 1.15;
        font-weight: 800;
        margin: 0;
    }

    .greeting-banner-copy p {
        color: rgba(255, 255, 255, .92);
        font-size: 0.95rem;
        margin-top: 0.45rem;
    }

    .greeting-banner-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .greeting-status-meta {
        text-align: right;
        color: white;
    }

    .greeting-status-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: rgba(255, 255, 255, .72);
        margin-bottom: 4px;
    }

    .greeting-status-text {
        font-size: 12px;
        font-weight: 600;
        color: rgba(255, 255, 255, .94);
        white-space: nowrap;
    }

    .status-btn-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, .28);
        background: rgba(255, 215, 0, 0.08);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .12);
    }

    .status-icon-badge {
        width: 42px;
        height: 42px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .24);
        flex-shrink: 0;
    }

    .status-icon-badge i {
        font-size: 15px;
    }

    #statusBtn.banner-status-btn {
        border-radius: 999px !important;
        padding: 10px 18px !important;
        min-width: 108px;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
    }

    .status-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #fff;
        border: 1px solid #fce8e8;
        box-shadow: 0 2px 12px rgba(139, 0, 0, .06);
        border-radius: 999px;
        padding: 6px 6px 6px 20px;
        flex-shrink: 0;
    }

    .status-card-labels {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 1px;
    }

    .status-card-eyebrow {
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #b0b7c3;
    }

    .status-card-text {
        font-size: 11px;
        font-weight: 600;
        color: #757575;
        white-space: nowrap;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .kpi-card {
        min-height: 135px;
        height: 135px;
        aspect-ratio: auto !important;
        padding: 1rem !important;
        border-radius: 1rem;
        overflow: hidden;
    }

    #scheduledTodayContainer .card {
        min-height: 250px;
        border-radius: 1.25rem;
    }

    .dashboard-side-card {
        min-height: 150px;
    }

    #scheduledTodayContainer>.card,
    #scheduledTodayContainer .card {
        min-height: 260px;
    }

    #gadAnalyticsContainer,
    #gadAnalyticsContainer > div,
    .row3-grid > .flex {
        height: 100%;
    }

    #gadAnalyticsContainer > div {
        min-height: 420px;
    }

    .row3-grid > .flex > div {
        flex: 1;
    }

    #medicalSuppliesContainer > div,
    #medicineSuppliesContainer > div {
        height: 100%;
    }

    .inventory-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    @media (min-width: 1024px) {

        .row2-grid {
            display: grid;
            grid-template-columns: minmax(0, 7fr) minmax(320px, 5fr);
            margin-bottom: 1.5rem !important;
            gap: 1.5rem;
            align-items: stretch;
        }

        .row3-grid {
            display: grid;
            grid-template-columns: minmax(0, 7fr) minmax(320px, 5fr);
            gap: 1rem;
            align-items: stretch;
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            height: 100%;
        }
    }

    .dashboard-side-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-width: 0;
        height: 100%;
    }

    #upcomingAppointmentsContainer {
        height: 100%;
    }

    .dashboard-side-card {
        background: #fff;
        border: 1px solid #f3e3e3;
        border-radius: 1.25rem;
        box-shadow: 0 10px 26px rgba(139, 0, 0, 0.07);
        overflow: hidden;
    }

    .dashboard-side-card-head {
        padding: 1rem 1rem 0.85rem;
        border-bottom: 1px solid #f4ecec;
        background: linear-gradient(180deg, #fffdfd, #fff8f7);
    }

    .dashboard-side-card-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        color: #8B0000;
        font-size: 0.95rem;
        font-weight: 800;
        margin: 0;
    }

    .dashboard-side-card-body {
        padding: 1rem;
    }

    .dashboard-quick-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.6rem;
    }

    .dashboard-quick-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        border-radius: 0.9rem;
        border: 1px solid #f1d9d9;
        padding: 0.8rem 0.9rem;
        font-size: 0.78rem;
        font-weight: 800;
        color: #7a0000;
        background: linear-gradient(180deg, #fffefe, #fff7f7);
        transition: all 0.18s ease;
    }

    .dashboard-quick-btn:hover {
        transform: translateY(-1px);
        border-color: #d79a9a;
        background: #fff1f1;
    }

    @media (max-width: 1023px) {
        #dentistCalendarContainer {
            min-height: 360px !important;
        }

        .dashboard-quick-actions {
            grid-template-columns: 1fr;
        }

        .inventory-scroll-wrap {
            position: relative;
        }

        .inventory-scroll-wrap::after {
            content: '';
            position: sticky;
            bottom: 0;
            display: block;
            height: 24px;
            background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, .9));
            pointer-events: none;
            margin-top: -24px;
        }

        .kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .kpi-card {
            height: 125px;
            min-height: 125px;
        }
    }

    .day-hover-card {
        transform: translateX(-50%) translateY(6px);
    }

    .group:hover .day-hover-card,
    .day-hover-card:hover {
        transform: translateX(-50%) translateY(0);
        opacity: 1 !important;
        visibility: visible !important;
        pointer-events: auto !important;
    }

    .greeting-heading {
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin: 0;
    }

    .greeting-line {
        display: block;
    }

    .greeting-name-line {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    #greetingText {
        font-weight: 500;
        font-size: 1.45rem;
    }

    .greeting-name-prefix {
        opacity: .96;
    }

    .upcoming-card {
        background: #fff;
        border: 1px solid #f3e3e3;
        border-radius: 1.25rem;
        box-shadow: 0 10px 26px rgba(139, 0, 0, 0.07);
        overflow: hidden;
        height: 100%;
        min-height: 420px;
        display: flex;
        flex-direction: column;
    }

    .upcoming-date-strip {
        display: flex;
        gap: 0.55rem;
        overflow-x: auto;
        overflow-y: visible;
        padding: 0.75rem 0.25rem 1rem;
        justify-content: center;
    }

    .upcoming-date-btn.active {
        position: relative;
        z-index: 2;
    }

    .upcoming-card .dashboard-side-card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .upcoming-list-area {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .upcoming-empty-state {
        flex: 1;
        min-height: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        transform: translateY(-28px);
    }

    .upcoming-date-btn {
        min-width: 54px;
        border-radius: 1rem;
        border: 1px solid #f1d9d9;
        background: #fff8f8;
        padding: 0.55rem 0.4rem;
        text-align: center;
        transition: all .18s ease;
    }

    .upcoming-date-btn.active {
        background: #8B0000;
        color: white;
        border-color: #8B0000;
        box-shadow: 0 8px 18px rgba(139, 0, 0, .25);
    }

    .upcoming-item {
        display: flex;
        position: relative;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 0.5rem;
    }

    .upcoming-status-line {
        width: 4px;
        border-radius: 999px;
        flex-shrink: 0;
    }

    .upcoming-status-confirmed {
        background: #22c55e;
    }

    .upcoming-status-pending {
        background: #eab308;
    }

    .upcoming-status-cancelled {
        background: #ef4444;
    }

    .upcoming-status-default {
        background: #8B0000;
    }

    .upcoming-avatar {
        width: 38px;
        height: 38px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #fff5f5;
        color: #8B0000;
        font-size: 0.75rem;
        font-weight: 900;
        border: 2px solid #8B0000;
        flex-shrink: 0;
    }

    .upcoming-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-confirmed { border-color: #22c55e; }
    .avatar-pending { border-color: #eab308; }
    .avatar-cancelled { border-color: #ef4444; }
    .avatar-default { border-color: #8B0000; }

    .upcoming-time-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: #8B0000;
        box-shadow: 0 0 0 4px #fff1f1;
        flex-shrink: 0;
    }

    @media (max-width: 767px) {

        .greeting-row {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.75rem !important;
        }

        .status-card {
            width: 100% !important;
            justify-content: space-between !important;
            padding: 8px 8px 8px 16px !important;
        }

        .status-card-labels {
            align-items: flex-start !important;
        }

        .kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
        }

        .kpi-grid>div {
            padding: 12px !important;
            border-radius: 14px !important;
        }

        .kpi-grid>div .w-10.h-10 {
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
        }

        .kpi-grid>div .w-10.h-10 i {
            font-size: 12px !important;
        }

        .kpi-grid>div .text-\[10px\] {
            font-size: 8px !important;
            padding: 2px 6px !important;
        }

        .kpi-grid>div p.text-3xl {
            font-size: 1.5rem !important;
            margin-top: 4px !important;
            margin-bottom: 2px !important;
        }

        .kpi-grid>div p.text-xs {
            font-size: 0.55rem !important;
            line-height: 1.2 !important;
            white-space: normal !important;
            letter-spacing: 0px !important;
        }

        .kpi-grid>div p.text-\[10px\] {
            font-size: 0.5rem !important;
            line-height: 1.2 !important;
        }

        #kpi-clock-hhmm {
            font-size: 1.35rem !important;
        }

        #kpi-clock-ss {
            font-size: 0.75rem !important;
        }

        #statusKpiLabel {
            font-size: 1.35rem !important;
        }

        .kpi-grid>div .absolute.w-24.h-24 {
            display: none !important;
        }

        .greeting-banner-inner {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .greeting-banner-actions {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .greeting-status-meta {
            text-align: left;
        }

        .greeting-status-text {
            white-space: normal;
        }

        .status-btn-wrap {
            width: 100%;
            justify-content: space-between;
        }

        #statusBtn.banner-status-btn {
            flex: 1 1 auto;
        }

        .greeting-banner {
            padding: 16px 14px;
            border-radius: 18px;
        }

        #greetingText {
            font-weight: 500;
            font-size: 1rem;
        }

        .greeting-banner-copy h1 {
            font-size: 1.1rem;
            line-height: 1.25;
            font-weight: 800;
        }

        .greeting-line:first-child {
            font-size: 0.85rem;
            font-weight: 600;
            opacity: 0.9;
            margin-bottom: 2px;
        }

        .greeting-name-line {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .greeting-name-prefix {
            opacity: 0.9;
        }

        .greeting-banner-copy p {
            font-size: 0.75rem;
            margin-top: 6px;
            opacity: 0.85;
        }

        .wave-hand {
            font-size: 0.9em;
        }
    }

    [data-theme="dark"] body,
    .dark body {
        background-color: var(--bg-main, #0D1117) !important;
        color: var(--text-primary, #F8FAFC) !important;
    }

    [data-theme="dark"] #mainContent,
    .dark #mainContent {
        background:
            radial-gradient(circle at 12% 4%, rgba(139, 0, 0, 0.18), transparent 30%),
            radial-gradient(circle at 88% 12%, rgba(252, 165, 165, 0.08), transparent 28%),
            var(--bg-main, #0D1117) !important;
        color: var(--text-primary, #F8FAFC) !important;
    }

    [data-theme="dark"] #mainContent .greeting-banner,
    .dark #mainContent .greeting-banner {
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, 0.30), transparent 34%),
            linear-gradient(135deg, rgba(13, 17, 23, 0.86), rgba(22, 27, 34, 0.76)) !important;
        border: 1px solid rgba(255, 255, 255, 0.10) !important;
        box-shadow: 0 18px 38px rgba(0, 0, 0, 0.38), inset 0 1px 0 rgba(255, 255, 255, 0.07) !important;
        backdrop-filter: blur(18px) saturate(145%) !important;
        -webkit-backdrop-filter: blur(18px) saturate(145%) !important;
    }

    [data-theme="dark"] #mainContent .kpi-card,
    [data-theme="dark"] #mainContent .upcoming-card,
    [data-theme="dark"] #mainContent .dashboard-side-card,
    [data-theme="dark"] #mainContent .cal-shell,
    [data-theme="dark"] #mainContent .skeleton-shell,
    [data-theme="dark"] #mainContent .bg-white,
    [data-theme="dark"] #mainContent #gadAnalyticsContainer > div,
    [data-theme="dark"] #mainContent #scheduledTodayContainer .card,
    [data-theme="dark"] #mainContent #medicalSuppliesContainer > div,
    [data-theme="dark"] #mainContent #medicineSuppliesContainer > div,
    .dark #mainContent .kpi-card,
    .dark #mainContent .upcoming-card,
    .dark #mainContent .dashboard-side-card,
    .dark #mainContent .cal-shell,
    .dark #mainContent .skeleton-shell,
    .dark #mainContent .bg-white,
    .dark #mainContent #gadAnalyticsContainer > div,
    .dark #mainContent #scheduledTodayContainer .card,
    .dark #mainContent #medicalSuppliesContainer > div,
    .dark #mainContent #medicineSuppliesContainer > div {
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, 0.18), transparent 36%),
            linear-gradient(145deg, rgba(13, 17, 23, 0.78), rgba(22, 27, 34, 0.66)) !important;
        border: 1px solid var(--border-dark, rgba(255, 255, 255, 0.10)) !important;
        box-shadow: 0 18px 38px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.07) !important;
        backdrop-filter: blur(18px) saturate(145%) !important;
        -webkit-backdrop-filter: blur(18px) saturate(145%) !important;
    }

    [data-theme="dark"] #mainContent .dashboard-side-card-head,
    .dark #mainContent .dashboard-side-card-head {
        background: linear-gradient(180deg, rgba(139, 0, 0, 0.16), rgba(22, 27, 34, 0.18)) !important;
        border-bottom: 1px solid var(--border-dark, rgba(255, 255, 255, 0.10)) !important;
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
    }

    [data-theme="dark"] #mainContent .bg-gradient-to-br,
    [data-theme="dark"] #mainContent .bg-gradient-to-b,
    [data-theme="dark"] #mainContent .bg-gradient-to-r,
    [data-theme="dark"] #mainContent .bg-gray-50,
    [data-theme="dark"] #mainContent .bg-gray-100,
    [data-theme="dark"] #mainContent .bg-red-50,
    [data-theme="dark"] #mainContent .bg-green-50,
    [data-theme="dark"] #mainContent .bg-emerald-50,
    [data-theme="dark"] #mainContent .bg-yellow-50,
    .dark #mainContent .bg-gradient-to-br,
    .dark #mainContent .bg-gradient-to-b,
    .dark #mainContent .bg-gradient-to-r,
    .dark #mainContent .bg-gray-50,
    .dark #mainContent .bg-gray-100,
    .dark #mainContent .bg-red-50,
    .dark #mainContent .bg-green-50,
    .dark #mainContent .bg-emerald-50,
    .dark #mainContent .bg-yellow-50 {
        background: var(--bg-panel, rgba(22, 27, 34, 0.72)) !important;
    }

    [data-theme="dark"] #mainContent .text-gray-900,
    [data-theme="dark"] #mainContent .text-gray-800,
    [data-theme="dark"] #mainContent .text-gray-700,
    [data-theme="dark"] #mainContent h3,
    .dark #mainContent .text-gray-900,
    .dark #mainContent .text-gray-800,
    .dark #mainContent .text-gray-700,
    .dark #mainContent h3 {
        color: var(--text-primary, #F8FAFC) !important;
    }

    [data-theme="dark"] #mainContent .text-gray-600,
    [data-theme="dark"] #mainContent .text-gray-500,
    [data-theme="dark"] #mainContent p,
    .dark #mainContent .text-gray-600,
    .dark #mainContent .text-gray-500,
    .dark #mainContent p {
        color: var(--text-secondary, #C9D1D9) !important;
    }

    [data-theme="dark"] #mainContent .text-gray-400,
    [data-theme="dark"] #mainContent .text-gray-300,
    .dark #mainContent .text-gray-400,
    .dark #mainContent .text-gray-300 {
        color: var(--text-muted, #8B949E) !important;
    }

    [data-theme="dark"] #mainContent .text-\[\#8B0000\],
    [data-theme="dark"] #mainContent .dashboard-side-card-title,
    [data-theme="dark"] #mainContent .cal-month-label,
    [data-theme="dark"] #mainContent .dashboard-quick-btn,
    .dark #mainContent .text-\[\#8B0000\],
    .dark #mainContent .dashboard-side-card-title,
    .dark #mainContent .cal-month-label,
    .dark #mainContent .dashboard-quick-btn {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent .border-gray-100,
    [data-theme="dark"] #mainContent .border-gray-200,
    [data-theme="dark"] #mainContent .border-red-100,
    [data-theme="dark"] #mainContent .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
    .dark #mainContent .border-gray-100,
    .dark #mainContent .border-gray-200,
    .dark #mainContent .border-red-100,
    .dark #mainContent .divide-gray-100 > :not([hidden]) ~ :not([hidden]) {
        border-color: var(--border-dark, rgba(255, 255, 255, 0.10)) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-date-btn,
    [data-theme="dark"] #mainContent .dashboard-quick-btn,
    [data-theme="dark"] #mainContent .cal-nav-btn,
    [data-theme="dark"] #mainContent .cal-cell,
    [data-theme="dark"] #mainContent .rounded-xl.border,
    .dark #mainContent .upcoming-date-btn,
    .dark #mainContent .dashboard-quick-btn,
    .dark #mainContent .cal-nav-btn,
    .dark #mainContent .cal-cell,
    .dark #mainContent .rounded-xl.border {
        background: rgba(22, 27, 34, 0.48) !important;
        border-color: var(--border-dark, rgba(255, 255, 255, 0.10)) !important;
        color: var(--text-secondary, #C9D1D9) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-date-btn.active,
    [data-theme="dark"] #mainContent .cal-cell.today,
    .dark #mainContent .upcoming-date-btn.active,
    .dark #mainContent .cal-cell.today {
        background: linear-gradient(135deg, #8B0000, #B91C1C) !important;
        color: #FFFFFF !important;
        border-color: rgba(252, 165, 165, 0.46) !important;
        box-shadow: 0 10px 24px rgba(139, 0, 0, 0.35) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-avatar,
    [data-theme="dark"] #mainContent .upcoming-time-dot,
    .dark #mainContent .upcoming-avatar,
    .dark #mainContent .upcoming-time-dot {
        background: rgba(139, 0, 0, 0.28) !important;
        color: #FCA5A5 !important;
        border-color: rgba(252, 165, 165, 0.42) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-empty-state i,
    .dark #mainContent .upcoming-empty-state i {
        color: rgba(252, 165, 165, 0.58) !important;
    }

    [data-theme="dark"] #mainContent .day-hover-card,
    .dark #mainContent .day-hover-card {
        background: rgba(13, 17, 23, 0.92) !important;
        border-color: var(--border-dark, rgba(255, 255, 255, 0.10)) !important;
        color: var(--text-primary, #F8FAFC) !important;
        box-shadow: 0 22px 50px rgba(0, 0, 0, 0.55) !important;
        backdrop-filter: blur(18px) saturate(145%) !important;
        -webkit-backdrop-filter: blur(18px) saturate(145%) !important;
    }

    .gad-analytics-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        min-height: 420px;
        border-radius: 28px;
        border: 1px solid rgba(139, 0, 0, 0.10);
        background:
            radial-gradient(circle at 12% 8%, rgba(139, 0, 0, 0.14), transparent 28%),
            radial-gradient(circle at 88% 18%, rgba(255, 215, 0, 0.12), transparent 24%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(255, 248, 248, 0.94));
        box-shadow: 0 18px 45px rgba(139, 0, 0, 0.10), inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }

    .gad-analytics-card::before,
    .gad-analytics-card::after {
        content: '';
        position: absolute;
        border-radius: 999px;
        pointer-events: none;
        z-index: -1;
    }

    .gad-analytics-card::before {
        width: 220px;
        height: 220px;
        right: -95px;
        top: -90px;
        background: rgba(139, 0, 0, 0.10);
    }

    .gad-analytics-card::after {
        width: 170px;
        height: 170px;
        left: -75px;
        bottom: -80px;
        background: rgba(255, 215, 0, 0.12);
    }

    .gad-header-badge {
        width: 44px;
        height: 44px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8B0000;
        background: linear-gradient(135deg, #fff3f3, #ffe8e8);
        border: 1px solid #f4caca;
        box-shadow: 0 10px 20px rgba(139, 0, 0, 0.10);
    }

    .gad-metric-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        padding: 0.48rem 0.72rem;
        font-size: 0.72rem;
        font-weight: 800;
        border: 1px solid rgba(139, 0, 0, 0.10);
        background: rgba(255, 255, 255, 0.72);
        color: #7a0000;
        box-shadow: 0 8px 18px rgba(139, 0, 0, 0.06);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .gad-empty-panel {
        width: min(100%, 620px);
        border-radius: 26px;
        border: 1px dashed rgba(139, 0, 0, 0.20);
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.80), rgba(255, 244, 244, 0.70));
        padding: 1.35rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }

    .gad-empty-icon-wrap {
        position: relative;
        width: 94px;
        height: 94px;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 28px;
        color: #8B0000;
        background: linear-gradient(135deg, #fff6f6, #ffe8e8);
        border: 1px solid #f3c9c9;
        box-shadow: 0 18px 34px rgba(139, 0, 0, 0.12);
    }

    .gad-empty-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .gad-empty-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.55rem 0.75rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #f0d5d5;
        color: #8B0000;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .gad-chart-shell {
        min-height: 250px;
        border-radius: 24px;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.62);
        border: 1px solid rgba(139, 0, 0, 0.08);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }

    [data-theme="dark"] #mainContent .gad-analytics-card,
    .dark #mainContent .gad-analytics-card {
        background:
            radial-gradient(circle at 12% 8%, rgba(139, 0, 0, 0.24), transparent 30%),
            radial-gradient(circle at 88% 18%, rgba(252, 165, 165, 0.10), transparent 24%),
            linear-gradient(145deg, rgba(13, 17, 23, 0.82), rgba(22, 27, 34, 0.70)) !important;
        border-color: rgba(255, 255, 255, 0.10) !important;
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.38), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
        backdrop-filter: blur(18px) saturate(145%) !important;
        -webkit-backdrop-filter: blur(18px) saturate(145%) !important;
    }

    [data-theme="dark"] #mainContent .gad-header-badge,
    .dark #mainContent .gad-header-badge{
        background:
            linear-gradient(
                135deg,
                rgba(255,245,245,.96),
                rgba(255,228,228,.88)
            ) !important;

        border: 1px solid rgba(252,165,165,.18) !important;

        box-shadow:
            0 10px 24px rgba(139,0,0,.22),
            0 0 18px rgba(252,165,165,.10),
            inset 0 1px 0 rgba(255,255,255,.55);

        color: #8B0000 !important;
    }

    [data-theme="dark"] #mainContent .gad-header-badge i,
        .dark #mainContent .gad-header-badge i{
            color: #8B0000 !important;

            filter:
                drop-shadow(0 0 6px rgba(139,0,0,.22));
        }

    [data-theme="dark"] #mainContent .gad-metric-chip:nth-child(1),
    .dark #mainContent .gad-metric-chip:nth-child(1){
        color: #E5B5B5 !important;
    }

    [data-theme="dark"] #mainContent .gad-metric-chip:nth-child(2),
    .dark #mainContent .gad-metric-chip:nth-child(2){
        color: #89CFF0 !important;
    }

    [data-theme="dark"] #mainContent .gad-metric-chip:nth-child(3),
    .dark #mainContent .gad-metric-chip:nth-child(3){
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent .upcoming-card,
    .dark #mainContent .upcoming-card {
        background: linear-gradient(145deg, #0D1117, #111827) !important;
        border-color: rgba(252, 165, 165, 0.18) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-date-btn,
    .dark #mainContent .upcoming-date-btn {
        background: rgba(255,255,255,0.04) !important;
        color: #C9D1D9 !important;
        border-color: rgba(255,255,255,0.12) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-date-btn.active,
    .dark #mainContent .upcoming-date-btn.active {
        background: linear-gradient(135deg, #B91C1C, #7F1D1D) !important;
        color: #fff !important;
        border-color: rgba(252, 165, 165, 0.45) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-empty-state p,
    .dark #mainContent .upcoming-empty-state p {
        color: #D1D5DB !important;
    }

    [data-theme="dark"] #mainContent .dashboard-side-card-title,
    .dark #mainContent .dashboard-side-card-title {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent .kpi-card i,
    [data-theme="dark"] #mainContent .dashboard-side-card-title i,
    [data-theme="dark"] #mainContent .gad-header-badge i,
    [data-theme="dark"] #mainContent .upcoming-empty-state i,
    [data-theme="dark"] #mainContent .status-icon-badge i,
    .dark #mainContent .kpi-card i,
    .dark #mainContent .dashboard-side-card-title i,
    .dark #mainContent .gad-header-badge i,
    .dark #mainContent .upcoming-empty-state i,
    .dark #mainContent .status-icon-badge i {
        filter:
            drop-shadow(0 0 6px rgba(252, 165, 165, 0.45))
            drop-shadow(0 0 12px rgba(139, 0, 0, 0.35));

        transition:
            transform 0.25s ease,
            filter 0.25s ease;
    }

    [data-theme="dark"] #mainContent .kpi-card:hover i,
    [data-theme="dark"] #mainContent .dashboard-side-card:hover i,
    [data-theme="dark"] #mainContent .gad-header-badge:hover i,
    .dark #mainContent .kpi-card:hover i,
    .dark #mainContent .dashboard-side-card:hover i,
    .dark #mainContent .gad-header-badge:hover i {
        transform: scale(1.08);

        filter:
            drop-shadow(0 0 8px rgba(252, 165, 165, 0.65))
            drop-shadow(0 0 18px rgba(185, 28, 28, 0.55));
    }

    [data-theme="dark"] #mainContent .w-9.h-9,
    [data-theme="dark"] #mainContent .gad-header-badge,
    [data-theme="dark"] #mainContent .status-icon-badge,
    .dark #mainContent .w-9.h-9,
    .dark #mainContent .gad-header-badge,
    .dark #mainContent .status-icon-badge {
        box-shadow:
            0 0 0 1px rgba(255,255,255,0.04),
            0 0 18px rgba(139, 0, 0, 0.22),
            inset 0 1px 0 rgba(255,255,255,0.05);
    }

    [data-theme="dark"] #mainContent .animate-pulse,
    .dark #mainContent .animate-pulse {
        box-shadow:
            0 0 8px rgba(255,255,255,0.6),
            0 0 16px rgba(252, 165, 165, 0.6);
    }

    .kpi-card .status-change-btn{
        background: linear-gradient(135deg,#8B0000,#660000) !important;
        color: #ffffff !important;
        border: 1px solid rgba(139,0,0,0.18) !important;

        transition:
            background 0.22s ease,
            color 0.22s ease,
            transform 0.22s ease,
            box-shadow 0.22s ease;

        box-shadow:
            0 8px 18px rgba(139,0,0,0.18),
            inset 0 1px 0 rgba(255,255,255,0.12);
    }

    .kpi-card .status-change-btn:hover{
        background: linear-gradient(135deg,#A40000,#7A0000) !important;
        color: #ffffff !important;
        transform: translateY(-2px) scale(1.03);

        box-shadow:
            0 10px 24px rgba(139,0,0,0.28),
            0 0 14px rgba(139,0,0,0.18);
    }

    [data-theme="dark"] .kpi-card .status-change-btn,
    .dark .kpi-card .status-change-btn{
        background: linear-gradient(135deg,#8B0000,#5F0000) !important;
        color:#fff !important;
        border:1px solid rgba(255,255,255,0.08) !important;

        box-shadow:
            0 8px 18px rgba(139,0,0,0.30),
            inset 0 1px 0 rgba(255,255,255,0.06);
    }

    [data-theme="dark"] .kpi-card .status-change-btn:hover,
    .dark .kpi-card .status-change-btn:hover{
        background: linear-gradient(135deg,#B91C1C,#7F1D1D) !important;

        box-shadow:
            0 10px 24px rgba(185,28,28,0.38),
            0 0 18px rgba(252,165,165,0.20);
    }

    .kpi-card button[onclick="openStatusModal()"]:active{
        transform: scale(0.97);
    }

    .greeting-subtitle{
        position: relative;
        min-height: 22px;
        transition: opacity .35s ease;
    }

    .greeting-icon{
        animation: greetingGlow 2.5s ease-in-out infinite;
    }

    @keyframes greetingGlow{
        0%,100%{
            transform: scale(1);
            opacity: 1;
        }
        50%{
            transform: scale(1.08);
            opacity: .82;
        }
    }

    .status-change-btn{
        background: #fff5f5;
        color: #8B0000;
        border: 1px solid rgba(139,0,0,0.08);
    }

    .status-change-btn:hover{
        background: #8B0000;
        color: #fff;
    }

    [data-theme="dark"] .status-change-btn,
    .dark .status-change-btn{
        background: linear-gradient(135deg,#8B0000,#5F0000) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255,255,255,0.08) !important;

        box-shadow:
            0 8px 18px rgba(139,0,0,0.30),
            inset 0 1px 0 rgba(255,255,255,0.06);
    }

    [data-theme="dark"] .status-change-btn:hover,
    .dark .status-change-btn:hover{
        background: linear-gradient(135deg,#B91C1C,#7F1D1D) !important;

        box-shadow:
            0 10px 22px rgba(185,28,28,0.40),
            0 0 16px rgba(252,165,165,0.18);
    }

    [data-theme="dark"] .status-change-btn,
    .dark .status-change-btn{
        background: linear-gradient(135deg,#8B0000,#5F0000) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255,255,255,0.08) !important;

        box-shadow:
            0 8px 18px rgba(139,0,0,0.30),
            inset 0 1px 0 rgba(255,255,255,0.06);
    }

    [data-theme="dark"] .status-change-btn:hover,
    .dark .status-change-btn:hover{
        background: linear-gradient(135deg,#B91C1C,#7F1D1D) !important;

        box-shadow:
            0 10px 22px rgba(185,28,28,0.40),
            0 0 16px rgba(252,165,165,0.18);
    }

    [data-theme="dark"] #mainContent #medicalSuppliesContainer > div,
    .dark #mainContent #medicalSuppliesContainer > div{
        background:
            radial-gradient(circle at top left,
                rgba(139,0,0,.20),
                transparent 34%),
            linear-gradient(
                145deg,
                rgba(10,14,20,.92),
                rgba(17,24,39,.86)
            ) !important;

        border: 1px solid rgba(255,255,255,.08) !important;

        box-shadow:
            0 20px 40px rgba(0,0,0,.42),
            0 0 20px rgba(139,0,0,.10),
            inset 0 1px 0 rgba(255,255,255,.05) !important;
    }

    [data-theme="dark"] #mainContent #medicalSuppliesContainer .w-9.h-9,
    .dark #mainContent #medicalSuppliesContainer .w-9.h-9{
        background:
            linear-gradient(
                135deg,
                rgba(139,0,0,.22),
                rgba(255,255,255,.04)
            ) !important;

        border: 1px solid rgba(252,165,165,.18) !important;

        box-shadow:
            0 0 18px rgba(139,0,0,.24),
            inset 0 1px 0 rgba(255,255,255,.06);
    }

    [data-theme="dark"] #mainContent #medicalSuppliesContainer .w-9.h-9 i,
    .dark #mainContent #medicalSuppliesContainer .w-9.h-9 i,
    [data-theme="dark"] #mainContent #medicalSuppliesContainer .fa-box-open,
    .dark #mainContent #medicalSuppliesContainer .fa-box-open {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent #medicalSuppliesContainer h3,
    .dark #mainContent #medicalSuppliesContainer h3{
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent #medicalSuppliesContainer a,
    .dark #mainContent #medicalSuppliesContainer a{
        background:
            linear-gradient(
                135deg,
                #8B0000,
                #5F0000
            ) !important;

        color: #fff !important;

        border: 1px solid rgba(255,255,255,.06);

        box-shadow:
            0 8px 18px rgba(139,0,0,.28),
            inset 0 1px 0 rgba(255,255,255,.08);

        transition: all .22s ease;
    }

    [data-theme="dark"] #mainContent #medicalSuppliesContainer a:hover,
    .dark #mainContent #medicalSuppliesContainer a:hover{
        transform: translateY(-2px);

        background:
            linear-gradient(
                135deg,
                #B91C1C,
                #7F1D1D
            ) !important;

        box-shadow:
            0 12px 24px rgba(185,28,28,.38),
            0 0 18px rgba(252,165,165,.18);
    }

    [data-theme="dark"] #mainContent #medicineSuppliesContainer > div,
    .dark #mainContent #medicineSuppliesContainer > div{
        background:
            radial-gradient(circle at top left,
                rgba(250,204,21,.18),
                transparent 34%),
            radial-gradient(circle at bottom right,
                rgba(202,138,4,.10),
                transparent 28%),
            linear-gradient(
                145deg,
                rgba(10,14,20,.92),
                rgba(17,24,39,.86)
            ) !important;

        border: 1px solid rgba(250,204,21,.14) !important;

        box-shadow:
            0 20px 40px rgba(0,0,0,.42),
            0 0 24px rgba(250,204,21,.10),
            inset 0 1px 0 rgba(255,255,255,.05) !important;
    }

    [data-theme="dark"] #mainContent #medicineSuppliesContainer .w-9.h-9,
    .dark #mainContent #medicineSuppliesContainer .w-9.h-9{
        background:
            linear-gradient(
                135deg,
                rgba(250,204,21,.20),
                rgba(202,138,4,.12)
            ) !important;

        border: 1px solid rgba(250,204,21,.28) !important;

        box-shadow:
            0 0 18px rgba(250,204,21,.18),
            inset 0 1px 0 rgba(255,255,255,.06);
    }

    [data-theme="dark"] #mainContent #medicineSuppliesContainer .w-9.h-9 i,
    .dark #mainContent #medicineSuppliesContainer .w-9.h-9 i{
        color: #FACC15 !important;

        filter:
            drop-shadow(0 0 8px rgba(250,204,21,.65))
            drop-shadow(0 0 18px rgba(202,138,4,.45));
    }

    [data-theme="dark"] #mainContent #medicineSuppliesContainer h3,
    .dark #mainContent #medicineSuppliesContainer h3{
        color: #FACC15 !important;
    }

    [data-theme="dark"] #mainContent #medicineSuppliesContainer a,
    .dark #mainContent #medicineSuppliesContainer a{
        background:
            linear-gradient(
                135deg,
                #FACC15,
                #CA8A04
            ) !important;

        color: #111827 !important;

        border: 1px solid rgba(250,204,21,.34) !important;

        box-shadow:
            0 10px 24px rgba(250,204,21,.24),
            0 0 18px rgba(250,204,21,.14),
            inset 0 1px 0 rgba(255,255,255,.22);

        transition: all .22s ease;
    }

    [data-theme="dark"] #mainContent #medicineSuppliesContainer a:hover,
    .dark #mainContent #medicineSuppliesContainer a:hover{
        transform: translateY(-2px);

        background:
            linear-gradient(
                135deg,
                #FDE047,
                #EAB308
            ) !important;

        color: #111827 !important;

        box-shadow:
            0 14px 28px rgba(250,204,21,.30),
            0 0 22px rgba(250,204,21,.20);
    }

    #medicineSuppliesContainer h3{
        color: #CA8A04 !important;
    }

    [data-theme="dark"] #mainContent #medicineSuppliesContainer p,
    .dark #mainContent #medicineSuppliesContainer p{
        color: var(--text-secondary, #C9D1D9) !important;
    }
    
    .gad-header-badge{
        background: linear-gradient(135deg,#fff3f3,#ffe8e8) !important;
        color:#8B0000 !important;
        border:1px solid #f4caca !important;
    }

    .gad-metric-chip{
        background: rgba(255,255,255,.82) !important;
        border:1px solid rgba(139,0,0,.10) !important;
        color:#8B0000 !important;
    }

    .gad-empty-panel{
        background:
            linear-gradient(135deg,rgba(255,255,255,.86),rgba(255,247,247,.76)) !important;
        border:1px dashed rgba(139,0,0,.20) !important;
        color:#1F2937 !important;
    }

    .gad-empty-panel p:first-of-type{
        color:#1F2937 !important;
    }

    .gad-empty-panel p:nth-of-type(2){
        color:#667085 !important;
    }

    .gad-empty-pill{
        background:rgba(255,255,255,.88) !important;
        color:#8B0000 !important;
        border:1px solid #f0d5d5 !important;
    }

    [data-theme="dark"] #mainContent .gad-empty-panel,
    .dark #mainContent .gad-empty-panel{
        background:
            radial-gradient(circle at top left, rgba(252,165,165,.08), transparent 35%),
            linear-gradient(145deg, rgba(13,17,23,.78), rgba(22,27,34,.62)) !important;

        border:1px dashed rgba(255,255,255,.13) !important;

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.06),
            0 18px 36px rgba(0,0,0,.20) !important;
    }

    [data-theme="dark"] #mainContent .gad-empty-panel p:first-of-type,
    .dark #mainContent .gad-empty-panel p:first-of-type{
        color:#F8FAFC !important;
    }

    [data-theme="dark"] #mainContent .gad-empty-panel p:nth-of-type(2),
    .dark #mainContent .gad-empty-panel p:nth-of-type(2){
        color:#C9D1D9 !important;
    }

    [data-theme="dark"] #mainContent .gad-empty-icon-wrap,
    .dark #mainContent .gad-empty-icon-wrap{
        background:rgba(255, 228, 228, 0.293) !important;

        color:#8B0000 !important;
        border:1px solid rgba(252,165,165,.25) !important;

        box-shadow:
            0 16px 34px rgba(250, 250, 250, 0.159),
            0 0 24px rgba(208, 208, 208, 0.14),
            inset 0 1px 0 rgba(255,255,255,.45) !important;
    }

    [data-theme="dark"] #mainContent .gad-empty-icon-wrap i,
    .dark #mainContent .gad-empty-icon-wrap i{
        color:#c6c6c6 !important;
        filter:drop-shadow(0 0 7px rgba(235, 235, 235, 0.34)) !important;
    }

    [data-theme="dark"] #mainContent .gad-empty-pill,
    .dark #mainContent .gad-empty-pill{
        background:rgba(13,17,23,.56) !important;
        border:1px solid rgba(255,255,255,.12) !important;
        color:#f5f5f5 !important;
    }

    [data-theme="dark"] #mainContent .gad-metric-chip,
    .dark #mainContent .gad-metric-chip{
        background:rgba(13,17,23,.58) !important;
        border:1px solid rgba(255,255,255,.12) !important;
        box-shadow:
            0 10px 22px rgba(0,0,0,.22),
            inset 0 1px 0 rgba(255,255,255,.06) !important;
    }

    [data-theme="dark"] #mainContent .gad-metric-chip:nth-child(1),
    .dark #mainContent .gad-metric-chip:nth-child(1){
        color:#E5B5B5 !important;
    }

    [data-theme="dark"] #mainContent .gad-metric-chip:nth-child(2),
    .dark #mainContent .gad-metric-chip:nth-child(2){
        color:#89CFF0 !important;
    }

    [data-theme="dark"] #mainContent .gad-metric-chip:nth-child(3),
    .dark #mainContent .gad-metric-chip:nth-child(3){
        color:#FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent #medicalSuppliesContainer .relative.z-10.flex.items-center.justify-between.mb-4 p,
    [data-theme="dark"] #mainContent #medicineSuppliesContainer .relative.z-10.flex.items-center.justify-between.mb-4 p,
    .dark #mainContent #medicalSuppliesContainer .relative.z-10.flex.items-center.justify-between.mb-4 p,
    .dark #mainContent #medicineSuppliesContainer .relative.z-10.flex.items-center.justify-between.mb-4 p{
        color: var(--text-secondary, #C9D1D9) !important;
    }
</style>
@endsection

@section('content')
@php
$dentalCasesThisMonth = $dentalCasesThisMonth ?? 0;
$totalApptsThisMonth = $totalApptsThisMonth ?? 0;

$dentalCasesDelta = $dentalCasesDelta ?? null;
$totalApptsDelta = $totalApptsDelta ?? null;

$gadLabels = $gadLabels ?? ['Student', 'Administrative', 'Faculty', 'Dependent'];
$gadFemale = $gadFemale ?? [0, 0, 0, 0];
$gadMale = $gadMale ?? [0, 0, 0, 0];

$appointmentCountsPerDay = $appointmentCountsPerDay ?? [];
$blockedDates = $blockedDates ?? [];
$philippineHolidays = $philippineHolidays ?? [];
$todayAppointments = $todayAppointments ?? collect();

$medicalSupplies = $medicalSupplies ?? collect();
$medicineSupplies = $medicineSupplies ?? collect();

$calendarAppointmentCounts = $appointmentCountsPerDay ?? [];
$calendarAppointmentDetails = $calendarAppointmentDetails ?? [];
@endphp

<main id="mainContent"
    class="pt-[78px] px-3 md:px-5 xl:px-6 pb-5 page-enter min-h-screen flex-1 bg-gray-50 dark:bg-[#000D1A] text-gray-900 dark:text-[#F3F4F6]">
    <div class="w-full">

        <x-dashboard-loading-status />

        <div class="greeting-row">
            <div class="greeting-banner w-full">
                <div class="greeting-banner-inner">
                    <div class="greeting-banner-copy min-w-0">
                        <h1 class="greeting-heading">
                            <span class="greeting-line">
                                <span id="greetingText"></span>
                            </span>
                            <span class="greeting-line greeting-name-line">
                                <span class="greeting-name-prefix">Dr.</span>
                                <span id="dentistName"></span>
                                <i class="fa-solid fa-heart-pulse text-rose-300"></i>
                            </span>
                        </h1>
                        <p id="rotatingSubtitle" class="greeting-subtitle"></p>
                    </div>

                    <div class="greeting-banner-actions">
                        <div class="greeting-status-meta">
                            <div class="greeting-status-eyebrow">
                                <i class="fa-solid fa-circle-plus"></i>
                                Clinic Status
                            </div>
                            <div class="greeting-status-text">The Dentist is currently</div>
                        </div>

                        <div class="status-btn-wrap">
                            <div class="status-icon-badge">
                                <i class="fa-solid fa-stethoscope"></i>
                            </div>

                            <button id="statusBtn" onclick="openStatusModal()"
                                class="banner-status-btn font-bold text-white flex items-center gap-2 transition-colors duration-300"
                                style="background:#00A96E; font-size:13px; border:none;">
                                <span id="statusLabel" class="flex items-center gap-2">
                                    <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span> IN
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="kpiGridContainer" class="kpi-grid skeleton-section skeleton-fade-swap">
            <div class="skeleton-shell space-y-4">
                @for ($i = 0; $i <5; $i++) <div class="rounded-2xl h-32 skeleton-block">
            </div>
            @endfor
        </div>
    </div>

    <div class="row2-grid">
        {{-- LEFT: Calendar --}}
        <div class="min-w-0">
            <div id="dentistCalendarContainer" class="w-full h-full min-h-[420px] skeleton-section skeleton-fade-swap">
                <div class="cal-shell skeleton-shell p-5 sm:p-6 h-full border-none shadow-sm">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="w-8 h-8 rounded-full skeleton-block"></div>
                            <div class="text-center space-y-2">
                                <div class="h-5 w-28 skeleton-block rounded mx-auto"></div>
                                <div class="h-3 w-16 skeleton-line rounded mx-auto"></div>
                            </div>
                            <div class="w-8 h-8 rounded-full skeleton-block"></div>
                        </div>

                        <div class="border-t border-gray-100 mb-3"></div>

                        <div class="grid grid-cols-7 gap-0.5 mb-2">
                            @for ($i = 0; $i <7; $i++) <div class="h-4 skeleton-line rounded mx-2">
                        </div>
                        @endfor
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        @for ($i = 0; $i <35; $i++) <div class="flex items-center justify-center py-1.5">
                            <div class="w-9 h-9 rounded-xl skeleton-line"></div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="dashboard-side-stack min-w-0">
        <div id="upcomingAppointmentsContainer" class="skeleton-section skeleton-fade-swap h-full">
            <div class="skeleton-shell bg-white rounded-xl p-4 h-full flex flex-col">
                <div class="space-y-3 flex flex-col h-full">

                    <div class="h-5 w-40 skeleton-block rounded"></div>

                    <div class="flex gap-2 justify-center">
                        @for ($i = 0; $i <5; $i++) <div class="h-14 w-14 skeleton-line rounded-xl">
                    </div>
                    @endfor
                </div>

                <div class="flex-1 space-y-2 mt-2">
                    @for ($i = 0; $i <3; $i++) <div class="h-14 skeleton-block rounded-xl">
                </div>
                @endfor
            </div>

            <div class="h-6 w-40 mx-auto skeleton-line rounded"></div>

        </div>
    </div>
    </div>
    </div>
    </div>
    </div>

    <div class="row3-grid">

        <div id="gadAnalyticsContainer" class="skeleton-section skeleton-fade-swap">
            <div class="skeleton-shell bg-white p-5 rounded-3xl shadow-sm flex flex-col">
                <div class="space-y-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="space-y-2 flex-1">
                            <div class="h-5 w-32 skeleton-block rounded"></div>
                            <div class="h-4 w-24 skeleton-line rounded"></div>
                            <div class="h-3 w-40 skeleton-line rounded"></div>
                        </div>
                        <div class="flex gap-3">
                            <div class="h-6 w-24 skeleton-block rounded"></div>
                            <div class="h-6 w-24 skeleton-block rounded"></div>
                        </div>
                    </div>
                    <div class="h-48 skeleton-block rounded"></div>
                </div>
            </div>
        </div>

        <div class="inventory-grid">
            <div id="medicalSuppliesContainer" class="skeleton-section skeleton-fade-swap">
                <div class="skeleton-shell bg-white p-5 rounded-3xl shadow-sm flex flex-col">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="space-y-2 flex-1">
                                <div class="h-5 w-32 skeleton-block rounded"></div>
                                <div class="h-3 w-24 skeleton-line rounded"></div>
                            </div>
                            <div class="h-6 w-20 skeleton-block rounded"></div>
                        </div>
                        @for ($i = 0; $i <3; $i++) <div class="h-10 skeleton-block rounded">
                    </div>
                    @endfor
                </div>
            </div>
        </div>

        <div id="medicineSuppliesContainer" class="skeleton-section skeleton-fade-swap">
            <div class="skeleton-shell bg-white p-5 rounded-3xl shadow-sm flex flex-col">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="space-y-2 flex-1">
                            <div class="h-5 w-32 skeleton-block rounded"></div>
                            <div class="h-3 w-24 skeleton-line rounded"></div>
                        </div>
                        <div class="h-6 w-20 skeleton-block rounded"></div>
                    </div>
                    @for ($i = 0; $i <3; $i++) <div class="h-10 skeleton-block rounded">
                </div>
                @endfor
            </div>
        </div>
    </div>

    </div>
    </div>

    </div>
</main>

<div id="statusModal"
    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
    <div id="statusModalBox"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-0 overflow-hidden scale-90 transition-all duration-300">
        <div id="modalBanner"
            class="bg-gradient-to-r from-[#660000] to-[#8B0000] px-6 pt-6 pb-4 text-white text-center">
            <div id="modalIcon"
                class="w-16 h-16 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl bg-white/20">
                <i class="fa-solid fa-door-closed"></i>
            </div>
            <h2 id="modalTitle" class="text-xl font-extrabold">Close the Clinic?</h2>
            <p id="modalSubtitle" class="text-sm opacity-80 mt-1">You are about to mark yourself as
                <strong>OUT</strong>
            </p>
        </div>
        <div class="px-6 py-5">
            <p id="modalBody" class="text-sm text-[#555] text-center leading-relaxed">
                This will indicate that the clinic is <span class="font-semibold text-red-700">currently closed</span>.
                Patients will not be able to book new appointments while you are out.
            </p>
            <div class="flex gap-3 mt-5">
                <button onclick="closeStatusModal()"
                    class="flex-1 btn btn-ghost border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button id="confirmStatusBtn" onclick="confirmStatus()"
                    class="flex-1 btn rounded-xl font-bold text-white bg-[#8B0000] hover:bg-[#660000] border-none shadow">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div id="dayAppointmentsModal"
    class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/50 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
    <div id="dayAppointmentsModalBox"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-0 overflow-hidden scale-90 transition-all duration-300">

        <div class="bg-gradient-to-r from-[#660000] to-[#8B0000] px-6 py-4 text-white">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-extrabold">Scheduled Patients</h2>
                    <p id="dayAppointmentsModalDate" class="text-sm opacity-80 mt-1"></p>
                </div>
                <button type="button" onclick="closeDayAppointmentsModal()"
                    class="text-white/80 hover:text-white text-2xl leading-none">
                    &times;
                </button>
            </div>
        </div>

        <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
            <div id="dayAppointmentsModalList" class="space-y-3"></div>
        </div>

        <div class="px-6 pb-5">
            <button type="button" onclick="closeDayAppointmentsModal()"
                class="w-full btn rounded-xl font-semibold border-none bg-gray-100 text-gray-700 hover:bg-gray-200">
                Close
            </button>
        </div>
    </div>
</div>

{{-- @if (session('activeAppointmentModal'))
<dialog id="activeAppointmentModal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-base">Active Appointment!</h3>
        <p class="py-4">You have an ongoing appointment setup.</p>
        <div class="modal-action">
            <form method="dialog">
                <button class="btn" id="closeActiveApptModalBtn">Close</button>
            </form>
        </div>
    </div>
</dialog>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var modal = document.getElementById("activeAppointmentModal");
        if (modal) modal.showModal();
    });
</script>
@endif --}}
@endsection

@section('scripts')
<script>
    function buildUpcomingAppointments() {
        const apptDetails = dashboardData.apptDetails || {};
        const today = new Date();

        const pad = n => String(n).padStart(2, '0');
        const dateKey = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

        const days = Array.from({ length: 7 }, (_, i) => {
            const d = new Date(today);
            d.setDate(today.getDate() + i);
            return d;
        });

        let selectedKey = dateKey(today);

        function statusClass(status = '') {
            const s = String(status).toLowerCase();
            if (s.includes('confirmed')) return 'upcoming-status-confirmed';
            if (s.includes('pending')) return 'upcoming-status-pending';
            if (s.includes('cancel')) return 'upcoming-status-cancelled';
            return 'upcoming-status-default';
        }

        function avatarClass(status = '') {
            const s = String(status).toLowerCase();
            if (s.includes('confirmed')) return 'avatar-confirmed';
            if (s.includes('pending')) return 'avatar-pending';
            if (s.includes('cancel')) return 'avatar-cancelled';
            return 'avatar-default';
        }

        function getInitials(name = 'Unknown') {
            return String(name)
                .trim()
                .split(/\s+/)
                .map(n => n[0])
                .slice(0, 2)
                .join('')
                .toUpperCase();
        }

        function render(selectedDateKey) {
            selectedKey = selectedDateKey;
            const appointments = apptDetails[selectedKey] || [];

            const dateButtons = days.map(d => {
                const key = dateKey(d);
                const active = key === selectedKey;
                return `
                <button type="button" onclick="renderUpcomingAppointmentsDay('${key}')"
                    class="upcoming-date-btn ${active ? 'active' : ''}">
                    <div class="text-sm font-extrabold">${d.getDate()}</div>
                    <div class="text-[10px] font-semibold opacity-70">
                        ${d.toLocaleDateString('en-US', { weekday: 'short' })}
                    </div>
                </button>
            `;
            }).join('');

            const items = appointments.length
                ? appointments.slice(0, 3).map(appt => {
                    const name = appt.name || 'Unknown Patient';
const initials = getInitials(name);
const photo = appt.patientPhotoUrl || appt.profile_photo_url || appt.avatar || null;

return `
    <a href="${appt.patientProfileUrl || '{{ route('dentist.dentist.appointments') }}'}"
        class="upcoming-item hover:bg-red-50/40 rounded-xl transition">

        <span class="upcoming-time-dot"></span>

        <div class="upcoming-avatar ${avatarClass(appt.status)}">
            ${
                photo
                    ? `<img src="${escHtml(photo)}" alt="${escHtml(name)}">`
                    : `<span>${escHtml(initials)}</span>`
            }
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-bold text-gray-800 truncate">${escHtml(name)}</p>
                <span class="text-[11px] font-bold text-[#8B0000] flex-shrink-0">${escHtml(appt.time || '—')}</span>
            </div>
            <p class="text-xs text-gray-500 truncate">${escHtml(appt.service || 'General Service')}</p>
        </div>
    </a>
`;
    }).join('')
    : `
                <div class="upcoming-empty-state text-gray-400">
        <div>
            <i class="fa-regular fa-calendar-xmark text-3xl mb-3 text-[#8B0000]/40"></i>
            <p class="text-sm font-semibold">No appointments for this day</p>
        </div>
    </div>
                `;

        const html = `
                <div class="upcoming-card" >
                <div class="dashboard-side-card-head">
                    <h3 class="dashboard-side-card-title">
                        <i class="fa-regular fa-calendar-days"></i> Upcoming Appointments
                    </h3>
                </div>

                <div class="dashboard-side-card-body">
                    <div class="upcoming-date-strip mb-3">
                        ${dateButtons}
                    </div>

                    <div class="upcoming-list-area divide-y divide-gray-100">
                        ${items}
                    </div>

                    <a href="{{ route('dentist.dentist.appointments') }}"
                        class="mt-2 pt-3 border-t border-gray-100 flex items-center justify-center gap-2 text-xs font-bold text-[#8B0000] hover:text-[#660000]">
                        View all appointments <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div >
                `;

        const container = document.getElementById('upcomingAppointmentsContainer');

        if (container) {
            if (!container.dataset.loaded) {
                swapSkeletonContent('upcomingAppointmentsContainer', html);
                container.dataset.loaded = 'true';
            } else {
                container.innerHTML = html;
            }
        }
    }

    window.renderUpcomingAppointmentsDay = render;
    render(selectedKey);
}

    const dashboardData = {
        gadLabels: {!! json_encode($gadLabels) !!},
        gadFemale: {!! json_encode($gadFemale) !!},
        gadMale: {!! json_encode($gadMale) !!},
        apptCounts: {!! json_encode($calendarAppointmentCounts) !!},
        apptDetails: {!! json_encode($calendarAppointmentDetails) !!},
        unavailableDates: {!! json_encode($blockedDates ?? []) !!},
        holidays: {!! json_encode($philippineHolidays ?? []) !!},
    };

    const KPI_DATA = {!! json_encode((object)[
        'dentalCases' => $dentalCasesThisMonth ?? 0,
        'dentalCasesDelta' => $dentalCasesDelta,
        'totalAppts' => $totalApptsThisMonth ?? 0,
        'totalApptsDelta' => $totalApptsDelta,
        'todayCount' => ($todayAppointments?->whereNotIn('status', ['cancelled'])?->count() ?? 0),
        'todayUpcoming' => ($todayAppointments?->whereIn('status', ['upcoming', 'rescheduled'])?->count() ?? 0),
        'todayCompleted' => ($todayAppointments?->where('status', 'completed')?->count() ?? 0),
    ]) !!};

    const TODAY_APPOINTMENTS = {!! json_encode($todayAppointments ?? []) !!};
    const MEDICAL_SUPPLIES = {!! json_encode($medicalSupplies ?? []) !!};
    const MEDICINE_SUPPLIES = {!! json_encode($medicineSupplies ?? []) !!};

    const GAD_LABELS = dashboardData.gadLabels;
    const GAD_FEMALE = dashboardData.gadFemale;
    const GAD_MALE = dashboardData.gadMale;

    function renderGadChart() {
        const monthLabel = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const totalFemale = GAD_FEMALE.reduce((sum, value) => sum + Number(value || 0), 0);
        const totalMale = GAD_MALE.reduce((sum, value) => sum + Number(value || 0), 0);
        const totalCases = totalFemale + totalMale;
        const hasData = [...GAD_FEMALE, ...GAD_MALE].some(v => Number(v || 0) > 0);

        const cardHeader = `
            <div class="relative z-10 flex items-start justify-between mb-5 flex-wrap gap-4">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="gad-header-badge flex-shrink-0">
                        <i class="fa-solid fa-chart-simple text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-extrabold text-[#8B0000] leading-tight">GAD Analytics</h3>
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-full bg-[#8B0000]/10 text-[#8B0000]">${monthLabel}</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Gender and Development Data</p>
                        <p class="text-xs text-gray-400 mt-0.5">Patient cases by category and sex</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 flex-wrap">
                    <div class="gad-metric-chip">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#E5B5B5]"></span>
                        Female <span class="font-semibold">${totalFemale}</span>
                    </div>
                    <div class="gad-metric-chip">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#89CFF0]"></span>
                        Male <span class="font-semibold">${totalMale}</span>
                    </div>
                    <div class="gad-metric-chip">
                        <i class="fa-solid fa-users text-[11px]"></i>
                        Total <span class="font-semibold">${totalCases}</span>
                    </div>
                </div>
            </div>
        `;

        if (!hasData) {
            const html = `
                <div class="gad-analytics-card p-5 sm:p-6 flex flex-col">
                    ${cardHeader}
                    <div class="relative z-10 flex-grow flex items-center justify-center w-full min-h-[255px]">
                        <div class="gad-empty-panel text-center">
                            <div class="gad-empty-icon-wrap">
                                <i class="fa-regular fa-clipboard text-5xl"></i>
                            </div>
                            <p class="font-extrabold text-base text-gray-800 leading-tight">No Treatments Recorded</p>
                            <p class="text-sm text-gray-500 max-w-md mx-auto mt-2">
                                Completed treatment records for this month will appear here once available.
                            </p>
                            <div class="gad-empty-actions">
                                <span class="gad-empty-pill"><i class="fa-solid fa-chart-column"></i> Category breakdown</span>
                                <span class="gad-empty-pill"><i class="fa-solid fa-venus-mars"></i> Sex-disaggregated data</span>
                                <span class="gad-empty-pill"><i class="fa-regular fa-calendar"></i> Monthly view</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            swapSkeletonContent('gadAnalyticsContainer', html);
            return true;
        }

        const chartHtml = `
            <div class="gad-analytics-card p-5 sm:p-6 flex flex-col">
                ${cardHeader}
                <div class="gad-chart-shell relative z-10 flex-grow flex items-center justify-center w-full">
                    <canvas id="gadChart" style="display:block;width:100%;height:100%;min-height:240px;"></canvas>
                </div>
            </div>
        `;

        swapSkeletonContent('gadAnalyticsContainer', chartHtml);

        if (typeof Chart === 'undefined') {
            console.warn('Chart.js is not loaded');
            return false;
        }

        setTimeout(() => {
            const newCtx = document.getElementById('gadChart');
            if (newCtx) {
                new Chart(newCtx, {
                    type: 'bar',
                    data: {
                        labels: GAD_LABELS,
                        datasets: [{
                            label: 'Female',
                            data: GAD_FEMALE,
                            backgroundColor: 'rgba(229,181,181,0.88)',
                            borderColor: '#E5B5B5',
                            borderWidth: 1,
                            borderRadius: 10,
                            borderSkipped: false
                        },
                        {
                            label: 'Male',
                            data: GAD_MALE,
                            backgroundColor: 'rgba(137,207,240,0.88)',
                            borderColor: '#89CFF0',
                            borderWidth: 1,
                            borderRadius: 10,
                            borderSkipped: false
                        }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(13,17,23,0.92)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                padding: 12,
                                cornerRadius: 12
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#6B7280', font: { weight: '700' } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(139,0,0,0.08)' },
                                ticks: { precision: 0, color: '#9CA3AF' }
                            }
                        }
                    }
                });
            }
        }, 160);

        return true;
    }

    function buildKpiGrid() {
        const kpiData = KPI_DATA;

        const html = `
            <div class="kpi-card relative overflow-hidden rounded-xl p-4 text-white" style="background:linear-gradient(135deg,#8B0000 0%,#5a0000 100%);box-shadow:0 4px 15px rgba(139,0,0,.2);">
                <div class="z-10 flex items-start justify-between w-full">
                    <div class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-tooth text-yellow-300 text-sm"></i>
                    </div>
                    ${kpiData.dentalCasesDelta !== null ? `<span class="text-[9px] font-bold px-2 py-1 rounded-full bg-white/20">${kpiData.dentalCasesDelta >= 0 ? '+' : ''}${kpiData.dentalCasesDelta}%</span>` : ''}
                </div>
                <div class="z-10 mt-4">
                    <p class="text-3xl font-extrabold leading-none">${kpiData.dentalCases}</p>
                    <p class="text-[10px] font-bold uppercase tracking-widest mt-1 opacity-80">Dental Cases</p>
                </div>
                <div class="absolute -bottom-4 -right-4 w-20 h-20 rounded-full bg-white/5"></div>
            </div>

            <div class="kpi-card relative overflow-hidden rounded-xl p-4 bg-white border border-red-50 shadow-sm">
                <div class="z-10 flex items-start justify-between w-full">
                    <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                        <i class="fa-regular fa-calendar-check text-[#8B0000] text-sm"></i>
                    </div>
                    ${kpiData.totalApptsDelta !== null ? `<span class="text-[9px] font-bold px-2 py-1 rounded-full ${kpiData.totalApptsDelta >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}">${kpiData.totalApptsDelta >= 0 ? '+' : ''}${kpiData.totalApptsDelta}%</span>` : ''}
                </div>
                <div class="z-10 mt-4">
                    <p class="text-3xl font-extrabold leading-none text-[#8B0000]">${kpiData.totalAppts}</p>
                    <p class="text-[10px] font-bold uppercase tracking-widest mt-1 text-[#8B0000]/60">Appointments</p>
                </div>
            </div>

            <div class="kpi-card relative overflow-hidden rounded-xl p-4 bg-white border border-red-50 shadow-sm">
                <div class="z-10 flex items-start justify-between w-full">
                    <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-user-clock text-[#8B0000] text-sm"></i>
                    </div>
                    <span class="text-[9px] font-bold px-2 py-1 rounded-full bg-green-100 text-green-700">Today</span>
                </div>
                <div class="z-10 mt-4 flex items-end justify-between w-full">
                    <div>
                        <p class="text-3xl font-extrabold leading-none text-[#8B0000]">${kpiData.todayCount}</p>
                        <p class="text-[10px] font-bold uppercase tracking-widest mt-1 text-[#8B0000]/60">Today's Patients</p>
                    </div>
                    <div class="flex flex-col text-right">
                        <span class="text-[9px] text-green-600 font-bold">${kpiData.todayCompleted} Completed</span>
                        <span class="text-[9px] text-blue-600 font-bold">${kpiData.todayUpcoming} Upcoming</span>
                    </div>
                </div>
            </div>

            <div class="kpi-card relative overflow-hidden rounded-xl p-4 bg-white border shadow-sm" style="border-color:#d1fae5;">
                <div class="z-10 flex items-start justify-between w-full">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#e6f4ea;">
                        <i id="statusKpiIcon" class="fa-solid fa-door-open text-sm" style="color:#00A96E;"></i>
                    </div>
                    
                    <div class="text-right">
                        <span class="flex items-center justify-end gap-1 text-[9px] font-bold text-gray-400 uppercase tracking-widest">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span> Live
                        </span>
                        <div class="text-gray-800 flex items-baseline justify-end mt-0.5">
                            <span id="kpi-clock-hhmm" class="text-lg font-extrabold tabular-nums">00:00</span>
                            <span id="kpi-clock-ampm" class="text-[9px] font-bold text-gray-500 ml-0.5">AM</span>
                            <span id="kpi-clock-ss" class="hidden"></span>
                        </div>
                    </div>
                </div>
                
                <div class="z-10 mt-3 flex items-end justify-between w-full border-t border-gray-200 dark:border-gray-600 pt-2">
                    <div>
                        <p id="statusKpiLabel" class="text-2xl font-extrabold leading-none" style="color:#00A96E;">Open</p>
                        <p class="text-[10px] font-bold uppercase tracking-widest mt-1 text-[#8B0000]/50">Clinic Status</p>
                    </div>
                    <button onclick="openStatusModal()"
                        class="status-change-btn text-[9px] font-bold px-3 py-1.5 rounded-lg transition-all duration-200">
                        Change
                    </button>
                </div>
            </div>
        `;

        swapSkeletonContent('kpiGridContainer', html);
    }

    function buildScheduledToday() {
        const todayAppointments = TODAY_APPOINTMENTS;

        if (todayAppointments.length === 0) {
            const html = `
                <div class="card bg-gradient-to-b from-[#8B0000] to-[#660000] text-white shadow rounded-xl h-full" >
                    <div class="card-body flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-base font-bold flex items-center gap-2">
                                <i class="fa-regular fa-clock text-yellow-300"></i> Scheduled Today
                            </h2>
                            <span class="badge bg-yellow-400 text-[#660000] font-bold border-none px-3">0 patients</span>
                        </div>
                        <div class="flex flex-col items-center justify-center py-10 opacity-60 flex-1">
                            <i class="fa-regular fa-calendar-xmark text-3xl mb-3 text-yellow-300"></i>
                            <p class="text-sm font-semibold">No appointments today</p>
                            <p class="text-xs opacity-70 mt-1">Enjoy your free day, Doctor!</p>
                        </div>
                        <a href="{{ route('dentist.dentist.appointments') }}" class="mt-4 flex items-center justify-center gap-2 text-xs font-semibold text-yellow-300 hover:text-yellow-200 transition border-t border-white/10 pt-3">
                            View all appointments <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div >
                `;
            swapSkeletonContent('scheduledTodayContainer', html);
        } else {
            const items = todayAppointments.map(appointment => {
                const name = appointment.patient?.name || 'Unknown Patient';
                const time = new Date(appointment.appointment_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                const service = appointment.service_type === 'others' ? appointment.other_services || 'Other Service' : appointment.service_type;
                const isConfirmed = appointment.status === 'confirmed';
                const initial = name.charAt(0).toUpperCase();

                return `
                <a href = "{{ route('dentist.dentist.appointments') }}" class="flex items-center gap-3 bg-white/10 hover:bg-white/20 border border-white/20 p-3 rounded-xl w-full transition duration-200 hover:scale-[1.01]" >
                        <div class="rounded-full w-9 h-9 border-2 border-yellow-300 bg-white/20 flex items-center justify-center font-bold text-sm flex-shrink-0">${initial}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">${name}</p>
                            <p class="text-xs opacity-70 truncate flex items-center gap-1">
                                <i class="fa-solid fa-stethoscope text-yellow-300 flex-shrink-0 text-[10px]"></i>
                                ${service} · ${time}
                            </p>
                        </div>
                        <span class="badge badge-sm ${isConfirmed ? 'bg-green-400 text-white' : 'bg-yellow-400 text-[#660000]'} border-none flex-shrink-0 text-[10px]">${isConfirmed ? '✓' : '!'}</span>
                    </a >
                `;
            }).join('');

            const html = `
                <div class="card bg-gradient-to-b from-[#8B0000] to-[#660000] text-white shadow rounded-xl h-full" >
                    <div class="card-body flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-base font-bold flex items-center gap-2">
                                <i class="fa-regular fa-clock text-yellow-300"></i> Scheduled Today
                            </h2>
                            <span class="badge bg-yellow-400 text-[#660000] font-bold border-none px-3">${todayAppointments.length} ${todayAppointments.length === 1 ? 'patient' : 'patients'}</span>
                        </div>
                        <div class="space-y-2 flex-1 overflow-y-auto pr-1">
                            ${items}
                        </div>
                        <a href="{{ route('dentist.dentist.appointments') }}" class="mt-4 flex items-center justify-center gap-2 text-xs font-semibold text-yellow-300 hover:text-yellow-200 transition border-t border-white/10 pt-3">
                            View all appointments <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div >
                `;
            swapSkeletonContent('scheduledTodayContainer', html);
        }
    }

    function buildMedicalSupplies() {
        const medicalSupplies = MEDICAL_SUPPLIES;

        let tableHtml = '';
        if (medicalSupplies.length > 0) {
            const rows = medicalSupplies.map(item => {
                const balance = item.qty - item.used;
                const pct = item.qty > 0 ? (balance / item.qty) * 100 : 100;
                const isLow = pct <= 30;
                const badgeCls = isLow ? 'bg-red-50 text-red-600 border-red-200 animate-pulse' : 'bg-green-50 text-green-600 border-green-200';

                return `
                <tr class="border-b border-gray-50 last:border-none hover:bg-gray-50/50 transition-colors" >
                        <td class="py-2.5 max-w-[140px] truncate font-semibold text-gray-800">${item.name}</td>
                        <td class="py-2.5 text-center font-medium">${item.qty}</td>
                        <td class="py-2.5 text-center text-gray-500">${item.used}</td>
                        <td class="py-2.5 text-right">
                            <span class="px-2 py-1 rounded-md border text-[10px] font-bold ${badgeCls}">
                                ${balance}${isLow ? ' ⚠' : ''}
                            </span>
                        </td>
                    </tr >
                `;
            }).join('');

            tableHtml = `
                <div class="relative z-10 overflow-y-auto overflow-x-auto pr-1" style = "max-height: 180px;" >
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-white/90 backdrop-blur-sm z-10">
                            <tr class="text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                <th class="pb-2 font-bold">Item</th>
                                <th class="pb-2 text-center font-bold">Stock</th>
                                <th class="pb-2 text-center font-bold">Used</th>
                                <th class="pb-2 text-right font-bold">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs text-gray-700">
                            ${rows}
                        </tbody>
                    </table>
                </div >
                `;
        } else {
            tableHtml = `
                <div class="relative z-10 flex flex-col items-center justify-center py-6 text-center opacity-60 bg-gray-50 rounded-xl mt-2 border border-dashed border-gray-200" >
                    <i class="fa-solid fa-box-open text-3xl mb-2 text-[#8B0000]/50"></i>
                    <p class="text-xs font-semibold text-gray-500">No medical supplies yet</p>
                </div >
                `;
        }

        const html = `
                <div class="relative bg-white p-5 rounded-3xl shadow-sm flex flex-col overflow-hidden group border border-gray-100" >
                <div class="absolute -top-6 -right-6 w-24 h-24 bg-red-50 rounded-full z-0 opacity-70 group-hover:scale-110 transition-transform duration-500"></div>
                <div class="relative z-10 flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-[#8B0000] border border-red-100 flex-shrink-0">
                            <i class="fa-solid fa-boxes-stacked text-base"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-[#8B0000] text-sm">Medical Supplies</h3>
                            <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Top Inventory</p>
                        </div>
                    </div>
                    <a href="{{ route('dentist.dentist.inventory') }}" class="text-[11px] font-bold text-[#8B0000] bg-red-50 hover:bg-[#8B0000] hover:text-white px-3 py-1.5 rounded-full transition-all duration-200 flex items-center gap-1.5">
                        View All <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                ${ tableHtml }
            </div >
                `;

        swapSkeletonContent('medicalSuppliesContainer', html);
    }

    function buildMedicineSupplies() {
        const medicineSupplies = MEDICINE_SUPPLIES;

        let tableHtml = '';
        if (medicineSupplies.length > 0) {
            const rows = medicineSupplies.map(item => {
                const balance = item.qty - item.used;
                const pct = item.qty > 0 ? (balance / item.qty) * 100 : 100;
                const isLow = pct <= 30;
                const badgeCls = isLow ? 'bg-red-50 text-red-600 border-red-200 animate-pulse' : 'bg-green-50 text-green-600 border-green-200';

                return `
                <tr class="border-b border-gray-50 last:border-none hover:bg-gray-50/50 transition-colors" >
                        <td class="py-2.5 max-w-[120px] truncate font-semibold text-gray-800">${item.name}</td>
                        <td class="py-2.5 text-center text-gray-500">${item.form || '—'}</td>
                        <td class="py-2.5 text-center font-medium">${item.qty}</td>
                        <td class="py-2.5 text-right">
                            <span class="px-2 py-1 rounded-md border text-[10px] font-bold ${badgeCls}">
                                ${balance}${isLow ? ' ⚠' : ''}
                            </span>
                        </td>
                    </tr >
                `;
            }).join('');

            tableHtml = `
                <div class="relative z-10 overflow-y-auto overflow-x-auto pr-1" style = "max-height: 180px;" >
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-white/90 backdrop-blur-sm z-10">
                            <tr class="text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                                <th class="pb-2 font-bold">Medicine</th>
                                <th class="pb-2 text-center font-bold">Form</th>
                                <th class="pb-2 text-center font-bold">Stock</th>
                                <th class="pb-2 text-right font-bold">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs text-gray-700">
                            ${rows}
                        </tbody>
                    </table>
                </div >
                `;
        } else {
            tableHtml = `
                <div class="relative z-10 flex flex-col items-center justify-center py-6 text-center opacity-60 bg-gray-50 rounded-xl mt-2 border border-dashed border-gray-200" >
                    <i class="fa-solid fa-prescription-bottle-medical text-3xl mb-2 text-[#FACC15]"></i>
                    <p class="text-xs font-semibold text-[#9CA3AF]">No medicine items yet</p>
                </div >
                `;
        }

        const html = `
                <div class="relative bg-white p-5 rounded-3xl shadow-sm flex flex-col overflow-hidden group border border-gray-100" >
                <div class="absolute -bottom-6 -right-6 w-24 h-24 bg-yellow-50 rounded-full z-0 opacity-70 group-hover:scale-110 transition-transform duration-500"></div>
                <div class="relative z-10 flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-yellow-50 flex items-center justify-center text-[#8B0000] border border-yellow-100 flex-shrink-0">
                            <i class="fa-solid fa-pills text-base text-yellow-600"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-[#8B0000] text-sm">Medicine Supplies</h3>
                            <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Top Inventory</p>
                        </div>
                    </div>
                    <a href="{{ route('dentist.dentist.inventory') }}" class="text-[11px] font-bold text-yellow-700 bg-yellow-50 hover:bg-yellow-400 hover:text-[#8B0000] px-3 py-1.5 rounded-full transition-all duration-200 flex items-center gap-1.5">
                        View All <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                ${ tableHtml }
            </div >
                `;

        swapSkeletonContent('medicineSuppliesContainer', html);
    }

    function scrollToTodayPatients() {
        document.getElementById('scheduledTodayContainer')?.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    function scrollToDashboardAnalytics() {
        document.getElementById('gadAnalyticsContainer')?.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        const nameEl = document.getElementById("dentistName");
        const greetingEl = document.getElementById("greetingText");

        nameEl.textContent = "{{ auth()->user()->name ?? 'Doctor' }}";

        const h = new Date().getHours();
        let greeting = "";

        if (h <12) {
            greeting = "Good Morning,";
        } else if (h <18) {
            greeting = "Good Afternoon,";
        } else {
            greeting = "Good Evening,";
        }

        greetingEl.textContent = greeting + " ";

        const subtitleEl = document.getElementById('rotatingSubtitle');

        const subtitles = [
            'Delivering brighter smiles today.',
            'Healthy smiles start here.',
            'Providing quality dental care.',
            'Creating confident smiles every day.',
            'Another day of patient care excellence.',
            'Ready for today’s consultations.',
            'Compassionate care for every patient.',
            'Helping every patient smile with confidence.'
        ];

        if (subtitleEl) {
            const randomIndex = Math.floor(Math.random() * subtitles.length);
            subtitleEl.textContent = subtitles[randomIndex];
        }

        const loadingPhases = [
            {
                label: 'Loading clinic dashboard',
                tasks: [buildKpiGrid]
            },
            {
                label: 'Loading charts and summaries',
                tasks: [renderGadChart, buildScheduledToday, buildUpcomingAppointments]
            },
            {
                label: 'Loading inventory',
                tasks: [buildMedicalSupplies, buildMedicineSupplies]
            },
            {
                label: 'Loading appointments calendar',
                tasks: [loadDentistCalendar]
            }
        ];

        if (typeof window.runEnterpriseLoading === 'function') {
            window.runEnterpriseLoading(loadingPhases, {
                initialDelay: 80,
                phaseGap: 220,
                taskGap: 120
            });
            return;
        }

        if (typeof window.setDashboardLoadingStatus === 'function') {
            window.setDashboardLoadingStatus('Loading clinic dashboard', 18);
        }

        setTimeout(() => {
            buildKpiGrid();

            if (typeof window.setDashboardLoadingStatus === 'function') {
                window.setDashboardLoadingStatus('Loading charts and summaries', 62);
            }

            renderGadChart();
            buildScheduledToday();

            if (typeof window.setDashboardLoadingStatus === 'function') {
                window.setDashboardLoadingStatus('Loading inventory', 70);
            }

            buildMedicalSupplies();
            buildMedicineSupplies();

            if (typeof window.setDashboardLoadingStatus === 'function') {
                window.setDashboardLoadingStatus('Loading appointments calendar', 44);
            }

            loadDentistCalendar();

            if (typeof window.finishDashboardLoading === 'function') {
                window.finishDashboardLoading();
            }
        }, 80);
    });

    (function () {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        function tickClock() {
            const now = new Date();
            let h = now.getHours(),
                m = now.getMinutes(),
                s = now.getSeconds();
            const ampm = h >= 12 ? 'PM' : 'AM';
            const isDaytime = h >= 6 && h <18;
            const displayHour = h % 12 || 12;

            const hmEl = document.getElementById('kpi-clock-hhmm');
            if (!hmEl) return;

            hmEl.textContent = String(displayHour).padStart(2, '0') + ':' + String(m).padStart(2, '0');
            document.getElementById('kpi-clock-ss').textContent = ':' + String(s).padStart(2, '0');
            document.getElementById('kpi-clock-ampm').textContent = ampm;
            document.getElementById('kpi-clock-date').textContent = days[now.getDay()] + ', ' + months[now
                .getMonth()] + ' ' + now.getDate();

            const dayicon = document.getElementById('kpi-clock-dayicon');
            const bigicon = document.getElementById('kpi-clock-icon');
            if (dayicon && bigicon) {
                dayicon.className = isDaytime ? 'fa-solid fa-sun text-xs flex-shrink-0' :
                    'fa-solid fa-moon text-xs flex-shrink-0';
                dayicon.style.color = isDaytime ? '#fde68a' : '#bfdbfe';
                bigicon.className = isDaytime ? 'fa-solid fa-sun text-base' : 'fa-solid fa-moon text-base';
                bigicon.style.color = isDaytime ? '#fde68a' : '#bfdbfe';
            }
        }
        tickClock();
        setInterval(tickClock, 1000);
    })();

    let dentistIsIn = true;

    function openStatusModal() {
        const modal = document.getElementById('statusModal');
        const box = document.getElementById('statusModalBox');
        const banner = document.getElementById('modalBanner');
        const icon = document.getElementById('modalIcon');
        const title = document.getElementById('modalTitle');
        const sub = document.getElementById('modalSubtitle');
        const body = document.getElementById('modalBody');

        if (dentistIsIn) {
            banner.className = 'bg-gradient-to-r from-[#660000] to-[#8B0000] px-6 pt-6 pb-4 text-white text-center';
            icon.innerHTML = '<i class="fa-solid fa-door-closed"></i>';
            title.textContent = 'Close the Clinic?';
            sub.innerHTML = 'You are about to mark yourself as <strong>OUT</strong>';
            body.innerHTML =
                'This will indicate that the clinic is <span class="font-semibold text-red-700">currently closed</span>. Patients will not be able to book new appointments while you are out.';
        } else {
            banner.className = 'bg-gradient-to-r from-green-600 to-green-700 px-6 pt-6 pb-4 text-white text-center';
            icon.innerHTML = '<i class="fa-solid fa-door-open"></i>';
            title.textContent = 'Open the Clinic?';
            sub.innerHTML = 'You are about to mark yourself as <strong>IN</strong>';
            body.innerHTML =
                'This will indicate that the clinic is <span class="font-semibold text-green-700">now open</span>. Patients will be able to see your availability and book appointments.';
        }

        modal.classList.remove('opacity-0', 'pointer-events-none');
        modal.classList.add('opacity-100');
        setTimeout(() => box.classList.replace('scale-90', 'scale-100'), 10);
    }

    function closeStatusModal() {
        const modal = document.getElementById('statusModal');
        const box = document.getElementById('statusModalBox');
        box.classList.replace('scale-100', 'scale-90');
        setTimeout(() => {
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.classList.remove('opacity-100');
        }, 150);
    }

    function confirmStatus() {
        const btn = document.getElementById('statusBtn');
        const label = document.getElementById('statusLabel');
        const kpiLabel = document.getElementById('statusKpiLabel');
        const kpiIcon = document.getElementById('statusKpiIcon');

        dentistIsIn = !dentistIsIn;

        if (dentistIsIn) {
            btn.style.background = '#00A96E';
            label.innerHTML = '<span class="w-2 h-2 bg-white rounded-full animate-pulse"></span> IN';
            if (kpiLabel) {
                kpiLabel.textContent = 'Open';
                kpiLabel.style.color = '#00A96E';
            }
            if (kpiIcon) {
                kpiIcon.className = 'fa-solid fa-door-open text-base';
                kpiIcon.style.color = '#00A96E';
            }
        } else {
            btn.style.background = '#EF4444';
            label.innerHTML = '<span class="w-2 h-2 bg-white rounded-full"></span> OUT';
            if (kpiLabel) {
                kpiLabel.textContent = 'Closed';
                kpiLabel.style.color = '#EF4444';
            }
            if (kpiIcon) {
                kpiIcon.className = 'fa-solid fa-door-closed text-base';
                kpiIcon.style.color = '#EF4444';
            }
        }
        closeStatusModal();
    }

    document.getElementById('statusModal').addEventListener('click', function (e) {
        if (e.target === this) closeStatusModal();
    });

    function escHtml(str = '') {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escJs(str = '') {
        return String(str)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'");
    }


    function getSmartTooltipClass(dayNumber, firstDow, totalDays) {
        const col = (firstDow + dayNumber - 1) % 7;

        if (col >= 5) return 'tooltip-left';
        if (col <= 1) return 'tooltip-right';
        return 'tooltip-center';
    }

    function buildDayHoverCard(dateStr, appointments) {
        const safeAppointments = Array.isArray(appointments) ? appointments : [];

        if (!safeAppointments.length) {
            return `
                <div class="day-hover-card absolute left-1/2 top-full z-[70] mt-1 w-[320px] -translate-x-1/2 rounded-2xl border border-[#efe6df] bg-white p-4 shadow-2xl opacity-0 invisible pointer-events-none transition-all duration-150 group-hover:opacity-100 group-hover:visible group-hover:pointer-events-auto" >
            <div class="absolute -top-3 left-0 right-0 h-3"></div>
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#8B0000]">Scheduled Patients</p>
                    <p class="text-[11px] text-gray-500">${escHtml(formatModalDate(dateStr))}</p>
                </div>
            </div>
            <div class="rounded-xl border border-dashed border-gray-200 bg-[#fafafa] px-4 py-6 text-center">
                <div class="w-11 h-11 mx-auto mb-3 rounded-full bg-[#fff5f5] flex items-center justify-center text-[#8B0000]">
                    <i class="fa-regular fa-calendar-xmark"></i>
                </div>
                <p class="text-sm font-semibold text-gray-700">No scheduled patients</p>
                <p class="text-[12px] text-gray-500 mt-1">This date has no booked appointments.</p>
            </div>
        </div >
                `;
        }

        const items = safeAppointments.slice(0, 3).map(appt => {
            const status = String(appt.status || '').toLowerCase();
            const canReschedule = ['pending', 'confirmed', 'upcoming', 'rescheduled'].includes(status);
            const canCancel = ['pending', 'confirmed', 'upcoming', 'rescheduled'].includes(status);

            const safeName = escJs(appt.name || 'Unknown Patient');
            const safeService = escJs(appt.service || 'General Service');
            const safeSchedule = escJs(`${ formatModalDate(appt.date || dateStr) } • ${ appt.time || '—' } `);
            const rawProfileUrl = appt.patientProfileUrl || '#';
            const profileUrl = `${ rawProfileUrl }${ rawProfileUrl.includes('?') ? '&' : '?' } from = dashboard`;

            return `
                <div class="rounded-xl border border-gray-100 p-3 bg-white" >
            <div class="flex items-center justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-gray-800 truncate">${escHtml(appt.name || 'Unknown Patient')}</p>
                    <p class="text-[11px] text-gray-500 truncate">${escHtml(appt.service || 'General Service')} · ${escHtml(appt.time || '—')}</p>
                </div>
                <a href="${escHtml(profileUrl)}"
                class="text-[11px] font-semibold text-[#8B0000] hover:text-[#660000]">
                    View
                </a>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="${escHtml(profileUrl)}"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-[#8B0000] text-white text-[10px] font-semibold hover:bg-[#660000] transition">
                    <i class="fa-regular fa-user text-[10px]"></i> Profile
                </a>

                ${canReschedule ? `
                            <button type="button"
                                onclick="event.stopPropagation(); openRescheduleModalFromDay('${escJs(appt.id)}', '${safeName}', '${safeSchedule}', '${safeService}', '${escJs(appt.rescheduleUrl || '#')}')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-amber-100 text-amber-700 text-[10px] font-semibold hover:bg-amber-200 transition">
                                <i class="fa-solid fa-rotate-right text-[10px]"></i> Reschedule
                            </button>
                        ` : ''}

                ${canCancel ? `
                            <button type="button"
                                onclick="event.stopPropagation(); cancelAppointmentFromModal('${escJs(appt.cancelUrl || '#')}', '${safeName}', '${safeSchedule}')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-red-100 text-red-700 text-[10px] font-semibold hover:bg-red-200 transition">
                                <i class="fa-solid fa-ban text-[10px]"></i> Cancel
                            </button>
                        ` : ''}
            </div>
        </div >
                `;
        }).join('');

        return `
                <div class="day-hover-card absolute left-1/2 top-full z-[70] mt-1 w-[340px] -translate-x-1/2 rounded-2xl border border-[#efe6df] bg-white p-3 shadow-2xl opacity-0 invisible pointer-events-none transition-all duration-150 group-hover:opacity-100 group-hover:visible group-hover:pointer-events-auto" >
            <div class="absolute -top-3 left-0 right-0 h-3"></div>
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#8B0000]">Scheduled Patients</p>
                    <p class="text-[11px] text-gray-500">${escHtml(formatModalDate(dateStr))}</p>
                </div>
                <a href="{{ route('dentist.dentist.appointments') }}"
                class="text-[11px] font-semibold text-[#8B0000] hover:text-[#660000]">
                    View all
                </a>
            </div>
            <div class="space-y-2">
                ${items}
            </div>
        </div >
                `;
    }

    function loadDentistCalendar() {

        function renderUnifiedCalendarLegend(mode) {
            if (mode !== 'dentist') return '';

            return `
                <div class="cal-legend mt-4" >
            <div class="cal-legend-item">
                <span class="cal-pill cal-pill-maroon">
                    <i class="fa-solid fa-calendar-day text-[10px]"></i> Today
                </span>
            </div>
            <div class="cal-legend-item">
                <span class="cal-pill cal-pill-green">
                    <i class="fa-solid fa-user-check text-[10px]"></i> Has Patients
                </span>
            </div>
            <div class="cal-legend-item">
                <span class="cal-pill cal-pill-red">
                    <i class="fa-solid fa-ban text-[10px]"></i> Fully Booked
                </span>
            </div>
            <div class="cal-legend-item">
                <span class="cal-pill cal-pill-yellow">
                    <i class="fa-solid fa-star text-[10px]"></i> Holiday
                </span>
            </div>
            <div class="cal-legend-item">
                <span class="cal-pill cal-pill-gray">
                    <i class="fa-solid fa-circle-minus text-[10px]"></i> Unavailable
                </span>
            </div>
        </div >
                `;
        }

        const MAX_PER_DAY = 5;
        const apptCounts = dashboardData.apptCounts || {};
        const apptDetails = dashboardData.apptDetails || {};
        const unavailableDates = dashboardData.unavailableDates || [];
        const allHolidays = dashboardData.holidays || {};

        const today = new Date();
        let currentYear = today.getFullYear(),
            currentMonth = today.getMonth();

        function pad(n) { return String(n).padStart(2, '0'); }

        function isWeekend(y, m, d) {
            const dow = new Date(y, m, d).getDay();
            return dow === 0 || dow === 6;
        }

        function getHolidaysForMonth(year, month) {
            const out = {};
            Object.keys(allHolidays).forEach(ds => {
                const [y, m] = ds.split('-').map(Number);
                if (y === year && m === month + 1) out[ds] = allHolidays[ds];
            });
            return out;
        }

        const isHoverDevice = window.matchMedia('(hover:hover) and (pointer:fine)').matches;

        function renderDentistCalendar(year, month) {
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const dayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            const firstDow = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();
            const holidays = getHolidaysForMonth(year, month);

            const headerHtml = dayLabels.map((l, i) =>
                `<div class="text-center text-[0.6rem] font-bold py-1 pb-2 uppercase tracking-widest ${(i === 0 || i === 6) ? 'cal-day-weekend' : 'cal-day-label'}" > ${ l }</div > `
            ).join('');

            let cells = '';
            for (let i = 0; i <firstDow; i++) cells += `<div ></div > `;

            for (let d = 1; d <= totalDays; d++) {
                const dateStr = `${year}-${pad(month + 1)}-${pad(d)}`;
                const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                const weekend = isWeekend(year, month, d);
                const holiday = holidays[dateStr] || null;
                const count = apptCounts[dateStr] || 0;
                const isFull = count >= MAX_PER_DAY;
                const isUnavail = unavailableDates.includes(dateStr) || weekend;
                const hasAppts = count > 0;

                const dayAppointments = apptDetails[dateStr] || [];
                const canOpenModal = dayAppointments.length > 0;
                const encodedAppointments = encodeURIComponent(JSON.stringify(dayAppointments));

                let badgeHtml = '', tooltipTxt = '', tooltipTone = 'dark';

                if (holiday) {
                    badgeHtml = `
                <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-yellow-400 text-[10px] leading-none flex items-center justify-center text-white shadow-[0_2px_8px_rgba(0,0,0,0.18)] border border-white" >
                    <i class="fa-solid fa-star text-[8px]"></i>
                </span > `;
                    tooltipTxt = `<i class="fa-solid fa-star mr-1 text-white" ></i > ${ holiday } `;
                    tooltipTone = 'yellow';
                }

                if (hasAppts && !isUnavail) {
                    if (isFull) {
                        tooltipTxt = `<i class="fa-solid fa-circle-xmark mr-1 text-red-300" ></i > Fully booked — ${ count } patient${ count > 1 ? 's' : '' } `;
                        tooltipTone = 'red';
                    } else {
                        tooltipTxt = `<i class="fa-solid fa-user-clock mr-1 text-emerald-300" ></i > ${ count } patient${ count > 1 ? 's' : '' } scheduled`;
                        tooltipTone = 'green';
                    }
                    const dotClass = isFull ? 'bg-red-600' : 'bg-emerald-600';
                    badgeHtml = `
                <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full ${dotClass} text-[9px] font-bold leading-none flex items-center justify-center text-white shadow-[0_2px_8px_rgba(0,0,0,0.18)] border border-white" >
                    ${ count }
                </span > `;
                }

                if (isToday && !hasAppts && !holiday) {
                    tooltipTxt = `<i class="fa-solid fa-calendar-day mr-1 text-white/90" ></i > Today`;
                    tooltipTone = 'today';
                } else if (isUnavail && !holiday && !hasAppts) {
                    badgeHtml = `
                <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-gray-500 text-[10px] leading-none flex items-center justify-center text-white shadow-[0_2px_8px_rgba(0,0,0,0.18)] border border-white" >
                    <i class="fa-solid fa-minus text-[8px]"></i>
                </span > `;
                    tooltipTxt = weekend ? `<i class="fa-solid fa-ban mr-1 text-gray-300" ></i > Clinic closed` : ` <i class="fa-solid fa-ban mr-1 text-gray-300" ></i > Not available`;
                    tooltipTone = 'gray';
                } else if (!hasAppts && !holiday && !isToday && !isUnavail) {
                    tooltipTxt = `<i class="fa-regular fa-calendar mr-1 text-gray-300" ></i > No scheduled patients`;
                    tooltipTone = 'gray';
                }

                let cellClass = "cal-cell";
                if (isToday) {
                    cellClass += " today";
                } else if (holiday) {
                    cellClass += " holiday disabled";
                } else if (isFull) {
                    cellClass += " full";
                } else if (hasAppts) {
                    cellClass += " bg-emerald-50 text-emerald-700 font-bold border-emerald-200 cursor-pointer hover:bg-emerald-100 transition-colors";
                } else if (isUnavail) {
                    cellClass += " disabled text-gray-400";
                } else {
                    cellClass += " cursor-pointer hover:bg-gray-100 text-[#333]";
                }

                const col = (firstDow + d - 1) % 7;
                const tooltipSide = col >= 5 ? "tooltip-left" : col <= 1 ? "tooltip-right" : "tooltip-center";
                const tooltipPalette = {
                    dark: { bg: 'bg-[#1a1410]', arrow: 'after:border-t-[#1a1410]' },
                    gray: { bg: 'bg-gray-600', arrow: 'after:border-t-gray-600' },
                    red: { bg: 'bg-red-500', arrow: 'after:border-t-red-500' },
                    green: { bg: 'bg-[#008440]', arrow: 'after:border-t-[#008440]' },
                    yellow: { bg: 'bg-yellow-500', arrow: 'after:border-t-yellow-500' },
                    today: { bg: 'bg-[#8B0000]', arrow: 'after:border-t-[#8B0000]' }
                };

                const palette = tooltipPalette[tooltipTone] || tooltipPalette.dark;
                const showHoverCard = isHoverDevice && hasAppts && !holiday;

                const tooltipHtml = (!showHoverCard && tooltipTxt) ? `
                <div class="day-smart-tooltip ${tooltipSide} absolute bottom-[calc(100%+10px)] z-[9999] pointer-events-none" >
                    <div class="${palette.bg} relative text-white text-[0.65rem] font-bold px-3 py-2 rounded-lg whitespace-nowrap shadow-xl
                        after:content-[''] after:absolute after:top-full after:border-4 after:border-transparent ${palette.arrow}">
                        ${tooltipTxt}
                    </div>
                </div >
                ` : '';

                const hoverCardHtml = showHoverCard ? buildDayHoverCard(dateStr, dayAppointments) : '';
                const clickOpen = canOpenModal && !isHoverDevice ? `onclick = "openDayAppointmentsModal('${dateStr}', decodeURIComponent('${encodedAppointments}'))"` : '';

                cells += `
                <div class="cal-cell-wrap relative flex items-center justify-center group" ${ clickOpen }>
                    ${ tooltipHtml }
                    ${ hoverCardHtml }
            <div class="${cellClass}" data-date="${dateStr}">
                <span>${d}</span>
                ${badgeHtml}
            </div>
                </div > `;
            }

            const container = document.getElementById('dentistCalendarContainer');
            if (container) {
                const html = `
                <div class="cal-shell flex flex-col justify-between h-full bg-white shadow-sm border-none p-5 sm:p-6" >
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <button onclick="changeDentistMonth(-1)" class="cal-nav-btn w-8 h-8 rounded-full border border-[#e8e2dd] flex items-center justify-center text-[#8B0000] text-xs transition-colors"><i class="fa-solid fa-chevron-left"></i></button>
                            <div class="text-center">
                                <p class="cal-month-label text-base font-extrabold">${monthNames[month]}</p>
                                <p class="text-[0.65rem] text-[#9e9690] font-semibold tracking-widest">${year}</p>
                            </div>
                            <button onclick="changeDentistMonth(1)" class="cal-nav-btn w-8 h-8 rounded-full border border-[#e8e2dd] flex items-center justify-center text-[#8B0000] text-xs transition-colors"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                        <hr class="border-[#f0ebe6] mb-3">
                            <div class="cal-grid">${headerHtml}</div>
                            <div class="cal-grid" style="row-gap: 0.5rem;">${cells}</div>
                    </div>
                ${ renderUnifiedCalendarLegend('dentist') }
            </div > `;

                swapSkeletonContent('dentistCalendarContainer', html);
            }
        }

        window.changeDentistMonth = function (dir) {
            currentMonth += dir;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            if (currentMonth <0) {
                currentMonth = 11;
                currentYear--;
            }
            renderDentistCalendar(currentYear, currentMonth);
        };

        renderDentistCalendar(currentYear, currentMonth);
    }

    function formatModalDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('en-PH', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function getStatusBadgeClass(status) {
        const s = String(status || '').toLowerCase();

        if (s.includes('confirmed') || s.includes('completed')) {
            return 'bg-green-100 text-green-700';
        } else if (s.includes('pending')) {
            return 'bg-yellow-100 text-yellow-700';
        } else if (s.includes('cancelled')) {
            return 'bg-red-100 text-red-700';
        } else if (s.includes('rescheduled')) {
            return 'bg-blue-100 text-blue-700';
        }

        return 'bg-gray-100 text-gray-700';
    }

    function openDayAppointmentsModal(dateStr, appointmentsJson) {
        const modal = document.getElementById('dayAppointmentsModal');
        const box = document.getElementById('dayAppointmentsModalBox');
        const dateEl = document.getElementById('dayAppointmentsModalDate');
        const listEl = document.getElementById('dayAppointmentsModalList');

        let appointments = [];

        try {
            appointments = JSON.parse(appointmentsJson);
        } catch (e) {
            appointments = [];
        }

        dateEl.textContent = formatModalDate(dateStr);

        if (!appointments.length) {
            listEl.innerHTML = `
                <div class="flex flex-col items-center justify-center py-10 text-center opacity-60" >
<i class="fa-regular fa-calendar-xmark text-3xl mb-3 text-[#8B0000]"></i>
<p class="text-sm font-semibold text-gray-700">No appointments for this date</p>
</div >
                `;
        } else {
            listEl.innerHTML = appointments.map(appt => {
                const badgeClass = getStatusBadgeClass(appt.status);
                const initial = (appt.name || '?').charAt(0).toUpperCase();
                const profileUrl = appt.patientProfileUrl || '#';
                const rescheduleUrl = appt.rescheduleUrl || '#';
                const cancelUrl = appt.cancelUrl || '#';
                const status = String(appt.status || '').toLowerCase();
                const canReschedule = ['pending', 'confirmed', 'upcoming', 'rescheduled'].includes(status);
                const canCancel = ['pending', 'confirmed', 'upcoming', 'rescheduled'].includes(status);
                const safeName = (appt.name || 'Unknown Patient').replace(/'/g, "\'");
                const safeSchedule = `${ formatModalDate(appt.date || dateStr) } • ${ appt.time || '—' } `.replace(
                    /'/g, "\'");

                return `
                <div class="border border-gray-100 rounded-xl p-3 hover:bg-gray-50 transition" >
<div class="flex items-center gap-3">
<a href="${profileUrl}${profileUrl.includes('?') ? '&' : '?'}from=dashboard" onclick="closeDayAppointmentsModal()"
class="rounded-full w-9 h-9 border-2 border-[#8B0000]/10 bg-[#8B0000]/10 flex items-center justify-center font-bold text-sm text-[#8B0000] flex-shrink-0">
${initial}
</a>

<div class="flex-1 min-w-0">
<a href="${profileUrl}${profileUrl.includes('?') ? '&' : '?'}from=dashboard" onclick="closeDayAppointmentsModal()"
class="font-semibold text-sm text-gray-800 truncate hover:text-[#8B0000] transition block">
${appt.name || 'Unknown Patient'}
</a>
<p class="text-xs text-gray-500 truncate flex items-center gap-1">
<i class="fa-solid fa-stethoscope text-[10px]"></i>
${appt.service || 'General Service'} · ${appt.time || '—'}
</p>
</div>

<span class="px-2 py-1 rounded-full text-[10px] font-bold ${badgeClass}">
${appt.status || 'Pending'}
</span>
</div>

<div class="mt-3 flex flex-wrap items-center gap-2">
<a href="${profileUrl}${profileUrl.includes('?') ? '&' : '?'}from=dashboard" onclick="closeDayAppointmentsModal()"
class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#8B0000] text-white text-[11px] font-semibold hover:bg-[#660000] transition">
<i class="fa-regular fa-user text-[10px]"></i>
View Profile
</a>

${canReschedule ? `
            <button type="button"
            onclick="openRescheduleModalFromDay('${appt.id}', '${safeName}', '${safeSchedule}', '${appt.service || ''}', '${rescheduleUrl}')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-100 text-amber-700 text-[11px] font-semibold hover:bg-amber-200 transition">
            <i class="fa-solid fa-rotate-right text-[10px]"></i>
            Reschedule
            </button>
            ` : ''}

${canCancel ? `
            <button type="button"
            onclick="cancelAppointmentFromModal('${cancelUrl}', '${safeName}', '${safeSchedule}')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-100 text-red-700 text-[11px] font-semibold hover:bg-red-200 transition">
            <i class="fa-solid fa-ban text-[10px]"></i>
            Cancel
            </button>
            ` : ''}
</div>
</div >
`;
            }).join('');
        }

        modal.classList.remove('opacity-0', 'pointer-events-none');
        modal.classList.add('opacity-100');

        setTimeout(() => {
            box.classList.replace('scale-90', 'scale-100');
        }, 10);
    }

    function closeDayAppointmentsModal() {
        const modal = document.getElementById('dayAppointmentsModal');
        const box = document.getElementById('dayAppointmentsModalBox');

        box.classList.replace('scale-100', 'scale-90');

        setTimeout(() => {
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.classList.remove('opacity-100');
        }, 150);
    }

    document.addEventListener('click', function (e) {
        const modal = document.getElementById('dayAppointmentsModal');
        if (e.target === modal) {
            closeDayAppointmentsModal();
        }
    });
</script>
@endsection