@extends('layouts.patient')

@section('title', 'Dental Records | PUP Taguig Dental Clinic')

@section('styles')
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #111827;
            overflow-x: hidden;
        }

        /* Animations */
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUpRec {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
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

        /* Upcoming Appointment Card */
        .upcoming-card {
            border-radius: 1.25rem;
            overflow: hidden;
            background: linear-gradient(145deg, #ffffff, #fffafa);
            border: 1px solid rgba(139, 0, 0, 0.12);
            box-shadow: 0 10px 24px rgba(139, 0, 0, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .upcoming-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(139, 0, 0, 0.12);
        }

        .upcoming-header {
            background: linear-gradient(135deg, #8B0000, #660000);
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
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

        .rec-row:last-child .rec-line { display: none; }

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

        /* Modal Redesign */
        dialog#record_modal {
            padding: 0;
            border: none;
            background: transparent;
        }

        dialog#record_modal::backdrop {
            background: rgba(16, 16, 16, 0.55);
            backdrop-filter: blur(2px);
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

        .modal-head {
            background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.16), transparent 28%),
                        linear-gradient(135deg, #7A0000, #8B0000, #661010);
            padding: 1.5rem;
            color: white;
            position: relative;
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

        .modal-close-btn {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .modal-close-btn:hover { background: rgba(255, 255, 255, 0.25); }

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

        .modal-body { padding: 1.25rem; }

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

        .msec { margin-bottom: 1rem; }
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

        .modal-close-main {
            padding: 0.7rem 1.5rem;
            border-radius: 999px;
            background: #fff;
            color: #8B0000;
            font-size: 0.8rem;
            font-weight: 800;
            border: 1px solid #f0cccc;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-close-main:hover {
            background: #fff1f1;
            transform: translateY(-1px);
        }

        /* 
        ========================================
           DARK MODE OVERRIDES
        ========================================
        */
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

        [data-theme="dark"] .upcoming-card .bg-white {
            background: #161B22 !important;
        }

        [data-theme="dark"] .upcoming-header {
            background: linear-gradient(135deg, #7A0000, #4A0000) !important;
        }

        [data-theme="dark"] .upcoming-service-text {color: #FCA5A5 !important;}
        [data-theme="dark"] .upcoming-card .text-\\[\\#1C1410\\] { color: #F3F4F6 !important; }

        [data-theme="dark"] .upcoming-card > div.bg-white > div:nth-child(2) p:last-child,
        [data-theme="dark"] .upcoming-card > div.bg-white > div:nth-child(3) p:last-child {
            color: #F3F4F6 !important;
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

        [data-theme="dark"] .records-section-title span { color: #FCA5A5 !important; }
        [data-theme="dark"] .records-section-title div { background: rgba(252, 165, 165, 0.18) !important; }

        [data-theme="dark"] .rec-line {
            background: linear-gradient(to bottom, #FCA5A5, rgba(252, 165, 165, 0.18)) !important;
        }

        [data-theme="dark"] .rec-dot {
            background: #161B22 !important;
            border-color: rgba(252, 165, 165, 0.28) !important;
            box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.25) !important;
        }

        [data-theme="dark"] .rec-card {
            background: #161B22 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-theme="dark"] .rec-card:hover {
            border-color: rgba(252, 165, 165, 0.28) !important;
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.28) !important;
        }

        [data-theme="dark"] .rec-service { color: #FCA5A5 !important; }
        [data-theme="dark"] .rec-meta-chip {
            background: rgba(139, 0, 0, 0.2) !important;
            color: #FCA5A5 !important;
            border-color: rgba(252, 165, 165, 0.18) !important;
        }

        [data-theme="dark"] .rec-btn {
            background: #161B22 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .rec-btn:hover { background: rgba(252, 165, 165, 0.1) !important; }

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

        [data-theme="dark"] .empty-title { color: #F3F4F6 !important; }
        [data-theme="dark"] .empty-sub { color: #C9D1D9 !important; }

        /* Modal Dark Mode */
        [data-theme="dark"] .modal-inner {
            background: #0D1117 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-theme="dark"] .chip-box,
        [data-theme="dark"] .msec-card {
            background: #161B22 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #C9D1D9 !important;
        }

        [data-theme="dark"] .chip-lbl,
        [data-theme="dark"] .msec-lbl { color: #FCA5A5 !important; }
        [data-theme="dark"] .msec-rule { background: rgba(252, 165, 165, 0.18) !important; }

        [data-theme="dark"] .modal-footer {
            background: #0D1117 !important;
            border-top-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-theme="dark"] .modal-close-main {
            background: #161B22 !important;
            color: #FCA5A5 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        [data-theme="dark"] .modal-close-main:hover { background: #21262d !important; }

        /* Chips Dark Mode */
        [data-theme="dark"] .bg-emerald-100 { background: rgba(16, 185, 129, 0.15) !important; }
        [data-theme="dark"] .text-emerald-800 { color: #6EE7B7 !important; }
        [data-theme="dark"] .bg-yellow-100 { background: rgba(245, 158, 11, 0.15) !important; }
        [data-theme="dark"] .text-yellow-800 { color: #FCD34D !important; }
        [data-theme="dark"] .bg-red-100 { background: rgba(239, 68, 68, 0.15) !important; }
        [data-theme="dark"] .text-red-800 { color: #FCA5A5 !important; }
        [data-theme="dark"] .bg-gray-100 { background: rgba(255, 255, 255, 0.05) !important; }
        [data-theme="dark"] .text-gray-700 { color: #D1D5DB !important; }

        /* Mobile specific fixes */
        @media (max-width: 640px) {
            .records-hero { padding: 1.5rem 1.5rem 2.5rem; }
            .hero-title { font-size: 1.4rem; }
            .records-body { padding: 1.25rem 1rem; }
            .rec-card { flex-wrap: wrap; padding: 1rem; }
            .rec-btn { width: 100%; justify-content: center; margin-top: 0.5rem; }
            dialog#record_modal {
                position: fixed; bottom: 0; left: 0; right: 0; top: auto;
                width: 100%; margin: 0; max-width: 100%;
                border-radius: 1.5rem 1.5rem 0 0;
            }
            .modal-inner { border-radius: 1.5rem 1.5rem 0 0; width: 100%; border-bottom: none; }
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
                @endphp
                <div class="mb-6 upcoming-card">
                    <div class="upcoming-header">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0">
                                <i class="fa-regular fa-calendar-check text-white text-sm"></i>
                            </div>
                            <span class="text-white font-extrabold text-sm tracking-wide">Upcoming Appointment</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest flex-shrink-0
                            {{ $isRescheduled ? 'bg-yellow-400/20 text-yellow-100' : 'bg-emerald-500/20 text-emerald-100' }}">
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $isRescheduled ? 'bg-yellow-300' : 'bg-emerald-400' }}"></span>
                            {{ ucfirst($upcomingAppointment->status) }}
                        </span>
                    </div>
                    <div class="bg-white px-5 py-4 grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400 mb-1">Service</p>
                            <p class="text-sm font-extrabold text-[#8B0000] upcoming-service-text">
                                {{ $upcomingAppointment->service_type ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400 mb-1">Date & Time</p>
                            <p class="text-sm font-extrabold text-[#1C1410]">
                                {{ $uDate->format('M d, Y') }}
                                <span class="text-[#8B0000] mx-0.5">·</span>
                                {{ $uTime->format('g:i A') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400 mb-1">Dentist</p>
                            <p class="text-sm font-extrabold text-[#1C1410]">
                                {{ $upcomingAppointment->dentist_name ?? 'Dr. Nelson P. Angeles' }}</p>
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
                                $fmtDate = $apptDate->format('M d, Y');
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
                                                <i class="fa-regular fa-calendar text-[10px]"></i>{{ $fmtDate }}
                                            </span>
                                            <span class="rec-meta-chip">
                                                <i class="fa-regular fa-clock text-[10px]"></i>{{ $fmtTime }}
                                            </span>
                                        </div>
                                    </div>
                                    <button type="button" class="rec-btn" onclick="openRecordModal(this)"
                                        data-service="{{ $record->service_type }}"
                                        data-date="{{ $apptDate->format('F d, Y') }}" data-time="{{ $fmtRange }}"
                                        data-status="{{ strtolower($record->status ?? 'completed') }}"
                                        data-remarks="{{ $record->remarks ?? '' }}"
                                        data-oral="{{ $record->oral_examination ?? '' }}"
                                        data-diagnosis="{{ $record->diagnosis ?? '' }}"
                                        data-prescription="{{ $record->prescription ?? '' }}">
                                        <i class="fa-regular fa-eye"></i> View Details
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

    <!-- Refined Modal Layout -->
    <dialog id="record_modal">
        <div class="modal-inner">
            <div class="modal-head">
                <button class="modal-close-btn" id="modalCloseBtn" type="button">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <p class="modal-eyebrow"><i class="fa-solid fa-circle-check mr-1"></i> Dental Record</p>
                <h3 class="modal-title" id="m_service">—</h3>
                
                <div class="modal-meta-strip">
                    <span class="modal-meta-chip"><i class="fa-regular fa-calendar"></i> <span id="m_date">—</span></span>
                    <span class="modal-meta-chip"><i class="fa-regular fa-clock"></i> <span id="m_time">—</span></span>
                </div>
            </div>

            <div class="modal-body">
                <div class="chip-row">
                    <div class="chip-box">
                        <div class="chip-lbl"><i class="fa-solid fa-shield-halved mr-1"></i> Status</div>
                        <span id="m_status" class="chip-val bg-emerald-100 text-emerald-800">—</span>
                    </div>
                    <div class="chip-box">
                        <div class="chip-lbl"><i class="fa-regular fa-hourglass-half mr-1"></i> Duration</div>
                        <span id="m_duration" class="chip-val bg-gray-100 text-gray-700">—</span>
                    </div>
                </div>

                <div class="msec">
                    <div class="msec-head">
                        <span class="msec-lbl"><i class="fa-solid fa-clipboard-list mr-1"></i> Treatment Notes</span>
                        <div class="msec-rule"></div>
                    </div>
                    <div class="msec-card"><span id="m_remarks">—</span></div>
                </div>

                <div class="msec">
                    <div class="msec-head">
                        <span class="msec-lbl"><i class="fa-solid fa-eye mr-1"></i> Oral Examination</span>
                        <div class="msec-rule"></div>
                    </div>
                    <div class="msec-card"><span id="m_oral">—</span></div>
                </div>

                <div class="msec">
                    <div class="msec-head">
                        <span class="msec-lbl"><i class="fa-solid fa-circle-info mr-1"></i> Diagnosis</span>
                        <div class="msec-rule"></div>
                    </div>
                    <div class="msec-card"><span id="m_diagnosis">—</span></div>
                </div>

                <div class="msec">
                    <div class="msec-head">
                        <span class="msec-lbl"><i class="fa-solid fa-prescription-bottle-medical mr-1"></i> Prescription</span>
                        <div class="msec-rule"></div>
                    </div>
                    <div class="msec-card"><span id="m_prescription">—</span></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="modal-close-main" id="modalCloseFooter">Close Record</button>
            </div>
        </div>
    </dialog>
@endsection

@section('scripts')
    <script>
        var recModal = document.getElementById('record_modal');

        function openRecordModal(btn) {
            document.getElementById('m_service').textContent = btn.dataset.service || '—';
            document.getElementById('m_date').textContent = btn.dataset.date || '—';
            document.getElementById('m_time').textContent = btn.dataset.time || '—';

            var st = (btn.dataset.status || 'completed').trim().toLowerCase();
            var sEl = document.getElementById('m_status');
            sEl.textContent = st.charAt(0).toUpperCase() + st.slice(1);
            sEl.className = 'chip-val';
            
            // Re-apply classes dynamically depending on status
            if (st === 'completed') sEl.classList.add('bg-emerald-100', 'text-emerald-800');
            else if (st === 'rescheduled') sEl.classList.add('bg-yellow-100', 'text-yellow-800');
            else if (st === 'cancelled') sEl.classList.add('bg-red-100', 'text-red-800');
            else sEl.classList.add('bg-gray-100', 'text-gray-700');

            var dEl = document.getElementById('m_duration');
            dEl.textContent = (btn.dataset.duration || '—').trim();
            dEl.className = 'chip-val bg-gray-100 text-gray-700';

            document.getElementById('m_remarks').textContent = (btn.dataset.remarks || '').trim() || '—';
            document.getElementById('m_oral').textContent = (btn.dataset.oral || '').trim() || '—';
            document.getElementById('m_diagnosis').textContent = (btn.dataset.diagnosis || '').trim() || '—';
            document.getElementById('m_prescription').textContent = (btn.dataset.prescription || '').trim() || '—';

            recModal.showModal();
        }

        function closeRecModal() {
            recModal.close();
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalCloseBtn').addEventListener('click', closeRecModal);
            document.getElementById('modalCloseFooter').addEventListener('click', closeRecModal);

            // Close when clicking outside of the modal dialog
            recModal.addEventListener('click', function(e) {
                var r = recModal.getBoundingClientRect();
                if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom)
                    closeRecModal();
            });
        });
    </script>
@endsection