@extends('layouts.patient')

@section('title', 'Dental Records | PUP Taguig Dental Clinic')

@section('styles')
<style>
    /* Animations */
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

    @keyframes slideUpRec {
        from {
            opacity: 0;
            transform: translateY(14px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-up {
        animation: fadeUp 0.6s ease-out forwards;
    }

    /* Page Header */
    .records-page-title {
        font-size: clamp(1.7rem, 3vw, 2.4rem);
        line-height: 1.1;
        font-weight: 800;
        margin: 0;
        background: linear-gradient(135deg, #660000, #8B0000);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Hero Section */
    .records-hero {
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.16), transparent 28%),
            linear-gradient(135deg, #7A0000, #8B0000, #661010);
        border-radius: 1.25rem;
        padding: 2rem 2rem 3rem;
        position: relative;
        overflow: hidden;
        margin-bottom: -2rem;
        box-shadow: 0 16px 32px rgba(139, 0, 0, 0.15);
    }

    .records-hero::after {
        content: '';
        position: absolute;
        right: -20px;
        bottom: -20px;
        width: 180px;
        height: 180px;
        background: rgba(255, 255, 255, .05);
        border-radius: 50%;
    }

    .hero-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: white;
        line-height: 1.15;
        position: relative;
        z-index: 1;
    }

    .hero-sub {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, .8);
        margin-top: 6px;
        position: relative;
        z-index: 1;
    }

    .hero-stats {
        display: flex;
        gap: 12px;
        margin-top: 1.2rem;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }

    .hero-stat {
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .25);
        border-radius: 999px;
        padding: 6px 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        color: white;
        backdrop-filter: blur(4px);
    }

    /* Main Body Content */
    .records-body {
        background: white;
        border-radius: 1.25rem;
        border: 1px solid rgba(139, 0, 0, 0.1);
        padding: 2rem 1.5rem;
        position: relative;
        z-index: 2;
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.06);
    }

    .records-section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 1.5rem;
    }

    .records-section-title span {
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #8B0000;
        white-space: nowrap;
    }

    .records-section-title div {
        flex: 1;
        height: 1px;
        background: rgba(139, 0, 0, 0.1);
    }

    /* Timeline & Record Cards */
    .rec-row {
        display: flex;
        align-items: stretch;
        gap: 0;
        animation: slideUpRec .4s ease both;
    }

    .rec-tl {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 36px;
        flex-shrink: 0;
        padding-top: 1.2rem;
    }

    .rec-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #8B0000;
        border: 2px solid white;
        box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.12);
        z-index: 1;
    }

    .rec-line {
        flex: 1;
        width: 2px;
        background: linear-gradient(to bottom, rgba(139, 0, 0, 0.2), rgba(139, 0, 0, 0.03));
        margin-top: 8px;
    }

    .rec-row:last-child .rec-line {
        display: none;
    }

    .rec-card {
        flex: 1;
        background: linear-gradient(145deg, #ffffff, #fffcfc);
        border: 1px solid rgba(139, 0, 0, 0.08);
        border-radius: 1.1rem;
        padding: 1.1rem 1.25rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        transition: all 0.25s ease;
    }

    .rec-card:hover {
        box-shadow: 0 12px 28px rgba(139, 0, 0, 0.07);
        border-color: rgba(139, 0, 0, 0.2);
        transform: translateY(-2px);
    }

    .rec-service {
        font-size: 1rem;
        font-weight: 800;
        color: #7a0000;
        margin-bottom: 0.4rem;
    }

    .rec-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .rec-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fff1f1;
        color: #8B0000;
        border: 1px solid #f3d6d6;
        border-radius: 999px;
        padding: 3px 10px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.02em;
    }

    .rec-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.65rem 1.15rem;
        border-radius: 999px;
        background: #fff;
        color: #8B0000;
        font-size: 0.75rem;
        font-weight: 800;
        border: 1px solid #f0cccc;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.22s ease;
    }

    .rec-btn:hover {
        background: #fff1f1;
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(139, 0, 0, 0.08);
    }

    /* Compact Empty State */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2.5rem 1.5rem;
        min-height: 220px;
        border-radius: 1.15rem;
        background: radial-gradient(circle at 50% 0%, rgba(139, 0, 0, 0.06), transparent 45%),
            linear-gradient(145deg, #ffffff, #fffafa);
        border: 1px dashed rgba(139, 0, 0, 0.25);
    }

    .empty-icon-wrap {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 1rem;
        background: rgba(139, 0, 0, 0.08);
        border: 1px solid rgba(139, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        color: #8B0000;
        font-size: 1.4rem;
    }

    .empty-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: #3b1c1c;
        margin-bottom: 0.25rem;
    }

    .empty-sub {
        font-size: 0.8rem;
        color: #8a6f6f;
        max-width: 280px;
        line-height: 1.5;
    }

    .empty-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 1.2rem;
        padding: 0.75rem 1.25rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #660000, #8B0000);
        color: white;
        font-size: 0.8rem;
        font-weight: 800;
        box-shadow: 0 10px 24px rgba(139, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .empty-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(139, 0, 0, 0.3);
    }

    .modal-inner {
        background: #fff8ef;
        border: 1px solid rgba(139, 0, 0, 0.18);
        border-radius: 1.35rem;
        width: min(92vw, 550px);
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.28);
    }

    .modal-eyebrow {
        font-size: 0.65rem;
        font-weight: 900;
        letter-spacing: .15em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .7);
        margin-bottom: 4px;
    }

    .modal-title {
        font-size: 1.4rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .modal-meta-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 1rem;
    }

    .modal-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .chip-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 1.25rem;
    }

    .chip-box {
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(139, 0, 0, 0.13);
        border-radius: 0.95rem;
        padding: 0.9rem 1rem;
    }

    .chip-lbl {
        font-size: 0.64rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #8B0000;
        margin-bottom: 0.4rem;
    }

    .chip-val {
        display: inline-flex;
        align-items: center;
        padding: 3px 12px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .msec {
        margin-bottom: 1rem;
    }

    .msec-head {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        margin-bottom: 0.5rem;
    }

    .msec-lbl {
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #8B0000;
    }

    .msec-rule {
        flex: 1;
        height: 1px;
        background: rgba(139, 0, 0, 0.18);
    }

    .msec-card {
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid rgba(139, 0, 0, 0.13);
        border-radius: 0.95rem;
        padding: 1rem;
        color: #5f4b4b;
        font-size: 0.85rem;
        line-height: 1.6;
    }

    .modal-footer {
        padding: 1rem 1.25rem;
        background: rgba(255, 248, 239, 0.92);
        border-top: 1px solid rgba(139, 0, 0, 0.12);
        display: flex;
        justify-content: flex-end;
    }

    /* Dashboard-style Upcoming Appointment Card */
    .upcoming-card-polished {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, 0.10), transparent 30%),
            radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.08), transparent 26%),
            linear-gradient(145deg, #ffffff, #fff7f7) !important;
    }

    .upcoming-card-polished::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, transparent, rgba(255, 255, 255, .28), transparent);
        transform: translateX(-120%) skewX(-18deg);
        pointer-events: none;
    }

    .upcoming-tooth-glass {
        position: relative;
        overflow: hidden;
        background:
            linear-gradient(135deg, rgba(139, 0, 0, 0.92) 0%, rgba(102, 0, 0, 0.82) 55%, rgba(179, 52, 18, 0.72) 100%);
        border: 1px solid rgba(255, 255, 255, 0.26);
        box-shadow:
            0 10px 24px rgba(139, 0, 0, 0.18),
            inset 0 1px 0 rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .upcoming-tooth-glass::before {
        content: '';
        position: absolute;
        inset: 1px;
        border-radius: inherit;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.20), rgba(255, 255, 255, 0.04));
        pointer-events: none;
    }

    .upcoming-tooth-glass::after {
        content: '';
        position: absolute;
        top: 7px;
        left: 8px;
        width: 52%;
        height: 34%;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        filter: blur(1px);
        pointer-events: none;
    }

    @keyframes upcomingGlowPulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.22);
            transform: scale(1);
        }
        50% {
            box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
            transform: scale(1.08);
        }
    }

    .upcoming-live-dot {
        animation: upcomingGlowPulse 2.2s ease-in-out infinite;
    }

    .upcoming-reminder-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        min-height: 42px;
        padding: 0.65rem 0.9rem;
        border-radius: 0.9rem;
        background: linear-gradient(135deg, rgba(255, 247, 247, 0.98) 0%, rgba(255, 252, 252, 1) 100%);
        border: 1px solid rgba(139, 0, 0, 0.10);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .upcoming-reminder-icon {
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(139, 0, 0, 0.08);
        color: #8B0000;
        flex-shrink: 0;
    }

    /* Dark Mode Overrides for Upcoming Card */
    [data-theme="dark"] #mainContent .upcoming-card-polished {
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, 0.22), transparent 34%),
            radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.08), transparent 28%),
            linear-gradient(145deg, #0D1117, #111827) !important;
        border-color: rgba(255, 255, 255, .10) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-reminder-chip {
        background: #1C2128 !important;
        border-color: rgba(255, 255, 255, 0.10) !important;
        box-shadow: none !important;
    }

    [data-theme="dark"] #mainContent .upcoming-reminder-icon {
        background: rgba(139, 0, 0, 0.18) !important;
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] body,
    [data-theme="dark"] #mainContent {
        background: #000D1A !important;
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .records-page-title {
        background: linear-gradient(135deg, #FCA5A5, #EF4444) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
    }

    [data-theme="dark"] .upcoming-card {
        background: radial-gradient(circle at top right, rgba(139, 0, 0, 0.2), transparent 40%),
            linear-gradient(145deg, #0D1117, #111827) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="dark"] .records-hero {
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.1), transparent 30%),
            linear-gradient(135deg, #7A0000, #8B0000, #5A0000) !important;
    }

    [data-theme="dark"] .records-body {
        background: #0D1117 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 18px 38px rgba(0, 0, 0, 0.35) !important;
    }

    [data-theme="dark"] .records-section-title span {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .records-section-title div {
        background: rgba(252, 165, 165, 0.18) !important;
    }

    [data-theme="dark"] .rec-line {
        background: linear-gradient(to bottom, #FCA5A5, rgba(252, 165, 165, 0.18)) !important;
    }

    [data-theme="dark"] .rec-dot {
        background: #c07f7f9b !important;
        border-color: rgba(218, 218, 218, 0.432) !important;
        box-shadow: 0 0 0 4px rgba(142, 142, 142, 0.25) !important;
    }

    [data-theme="dark"] .rec-card {
        background: #161B22 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="dark"] .rec-card:hover {
        border-color: rgba(252, 165, 165, 0.28) !important;
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.28) !important;
    }

    [data-theme="dark"] .rec-service {
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .rec-meta-chip {
        background: rgba(144, 144, 144, 0.2) !important;
        color: #F3F4F6 !important;
        border-color: rgba(255, 255, 255, 0.18) !important;
    }

    [data-theme="dark"] .rec-btn {
        background: #161B22 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .rec-btn:hover {
        background: rgba(252, 165, 165, 0.1) !important;
    }

    [data-theme="dark"] .empty-state {
        background: radial-gradient(circle at 50% 0%, rgba(139, 0, 0, 0.25), transparent 38%),
            linear-gradient(145deg, #0B1220, #0D1117) !important;
        border-color: rgba(252, 165, 165, 0.18) !important;
    }

    [data-theme="dark"] .empty-icon-wrap {
        background: rgba(139, 0, 0, 0.2) !important;
        border-color: rgba(252, 165, 165, 0.18) !important;
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] .empty-title {
        color: #F3F4F6 !important;
    }

    [data-theme="dark"] .empty-sub {
        color: #C9D1D9 !important;
    }
</style>
@endsection

@php
$notifications = collect($notifications ?? []);
$notifCount = $notifications->count();
@endphp

@section('content')

<main id="mainContent" class="page-enter pt-[90px] px-3 md:px-6 py-6 fade-in min-h-screen flex-1">
    <div class="w-full fade-up">

        @if (isset($upcomingAppointment) && $upcomingAppointment)
        @php
            $uDate = \Carbon\Carbon::parse($upcomingAppointment->appointment_date);
            $uTime = \Carbon\Carbon::parse($upcomingAppointment->appointment_time);
            $isRescheduled = strtolower($upcomingAppointment->status) === 'rescheduled';
            
            $statusPillCls = $isRescheduled ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-green-100 text-green-800 border-green-200';
            $statusDotCls = $isRescheduled ? 'bg-yellow-500' : 'bg-green-500';
            $statusDarkPill = $isRescheduled ? 'dark:bg-yellow-400/20 dark:text-yellow-100 dark:border-yellow-400/30' : 'dark:bg-emerald-500/20 dark:text-emerald-100 dark:border-emerald-500/30';
        @endphp
        <div class="mb-6 upcoming-card-polished bg-white dark:bg-[#161B22] rounded-[1rem] border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <div class="px-4 sm:px-5 py-4 sm:py-4.5">
                <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto] gap-4 xl:gap-5 items-center">

                    <div class="min-w-0">
                        <div class="flex items-start gap-3">
                            <div class="relative flex-shrink-0 mt-0.5">
                                <div class="upcoming-tooth-glass w-12 h-12 rounded-[0.95rem] text-white flex items-center justify-center">
                                    <i class="fa-solid fa-tooth text-[15px] relative z-[1] drop-shadow-[0_1px_2px_rgba(0,0,0,0.22)]"></i>
                                </div>
                                <span class="upcoming-live-dot absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full {{ $statusDotCls }} border-2 border-white dark:border-[#161B22]"></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg sm:text-[1.15rem] font-extrabold text-gray-900 dark:text-[#F3F4F6] leading-tight truncate">
                                        {{ $upcomingAppointment->service_type ?? '—' }}
                                    </h3>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $statusPillCls }} {{ $statusDarkPill }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDotCls }}"></span>
                                        {{ ucfirst($upcomingAppointment->status) }}
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-regular fa-calendar text-gray-400 dark:text-gray-500"></i>
                                        <span class="font-medium">{{ $uDate->format('M d, Y') }}</span>
                                    </span>

                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-regular fa-clock text-gray-400 dark:text-gray-500"></i>
                                        <span class="font-medium">{{ $uTime->format('g:i A') }}</span>
                                    </span>

                                    <span class="inline-flex items-center gap-2 min-w-0">
                                        <i class="fa-solid fa-user-doctor text-gray-400 dark:text-gray-500"></i>
                                        <span class="font-medium truncate">{{ $upcomingAppointment->dentist_name ?? 'Dr. Nelson P. Angeles' }}</span>
                                    </span>
                                </div>

                                <div class="mt-3 flex items-center gap-3">
                                    <div class="h-[2px] w-10 rounded-full bg-gradient-to-r from-red-200 to-red-300 dark:from-red-900/50 dark:to-red-800/50"></div>
                                    <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Upcoming Visit</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xl:min-w-[180px]">
                        <div class="flex flex-col sm:flex-row xl:flex-col items-stretch gap-2.5">
                            <div class="upcoming-reminder-chip">
                                <span class="upcoming-reminder-icon">
                                    <i class="fa-regular fa-bell text-[12px]"></i>
                                </span>
                                <span class="text-[0.76rem] font-bold text-[#7f1d1d] dark:text-[#FCA5A5]">Please arrive 10 minutes early</span>
                            </div>

                            <a href="{{ route('patient.appointment.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-[0.85rem] bg-[#8B0000] hover:bg-[#660000] text-white text-sm font-bold transition-all duration-300 shadow-sm hover:-translate-y-0.5">
                                <span>Manage Appointment</span>
                                <i class="fa-solid fa-arrow-right text-[11px]"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        @endif

        @php
        $totalRecords = isset($records) ? $records->count() : 0;
        $latestDate = $totalRecords
        ? \Carbon\Carbon::parse($records->first()->appointment_date)->format('M d, Y')
        : null;
        @endphp

        <div class="records-hero">
            <h2 class="hero-title">Dental Records Overview</h2>
            <p class="hero-sub">A complete history of your dental visits, treatments, and prescriptions.</p>

            <div class="hero-stats">
                <div class="hero-stat">
                    <i class="fa-solid fa-list-check"></i>
                    {{ $totalRecords }} {{ $totalRecords === 1 ? 'Total Visit' : 'Total Visits' }}
                </div>
                @if ($latestDate)
                <div class="hero-stat">
                    <i class="fa-regular fa-calendar-check"></i>
                    Latest: {{ $latestDate }}
                </div>
                @endif
            </div>
        </div>

        <div class="records-body">
            @if ($totalRecords)
            <div class="records-section-title">
                <span><i class="fa-solid fa-clock-rotate-left mr-1"></i> Visit History</span>
                <div></div>
            </div>

            <div class="space-y-0">
                @foreach ($records as $i => $record)
                @php
                $apptDate = \Carbon\Carbon::parse($record->appointment_date);
                $apptTime = \Carbon\Carbon::parse($record->appointment_time);
                $fmtDate = $apptDate->format('F d, Y'); // Binago sa 'F' para sa Full Month (e.g., May)
                $fmtTime = $apptTime->format('g:i A');
                $fmtRange = $fmtTime . ' – ' . $apptTime->copy()->addHour()->format('g:i A');
                @endphp
                <div class="rec-row" style="animation-delay:{{ $i * 0.08 }}s;">
                    <div class="rec-tl">
                        <div class="rec-dot"></div>
                        <div class="rec-line"></div>
                    </div>
                    <div class="rec-card">
                        <div class="rec-card-left">
                            <div class="rec-service">{{ $record->service_type }}</div>
                            <div class="rec-meta">
                                <span class="rec-meta-chip">
                                    <i class="fa-regular fa-calendar-check text-[10px] mr-1.5"></i>{{ $fmtDate }}
                                </span>
                                <span class="rec-meta-chip">
                                    <i class="fa-regular fa-clock text-[10px]"></i>{{ $fmtTime }}
                                </span>
                            </div>
                        </div>
                        <button class="rec-btn" onclick="openRecordModal(this)"
                            data-service="{{ $record->service_type }}" data-date="{{ $fmtDate }}"
                            data-time="{{ $record->appointment_time }}" data-status="{{ $record->status }}"
                            data-duration="{{ $record->duration }}" data-remarks="{{ $record->remarks }}"
                            data-oral="{{ $record->oral_examination }}" data-diagnosis="{{ $record->diagnosis }}"
                            data-prescription="{{ $record->prescription }}">
                            View Details
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state fade-up">
                <div class="empty-icon-wrap">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <p class="empty-title">No records yet</p>
                <p class="empty-sub">Completed appointment records will appear here after your first dental visit.</p>
                <a href="{{ route('patient.book.appointment') }}" class="empty-btn">
                    <i class="fa-solid fa-calendar-plus"></i> Book First Appointment
                </a>
            </div>
            @endif
        </div>

    </div>
</main>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initRecordModal();
    });
</script>
@endsection