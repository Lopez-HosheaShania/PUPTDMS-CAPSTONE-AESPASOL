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
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    @media (min-width: 640px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .kpi-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (min-width: 1200px) {
        .kpi-grid {
            grid-template-columns: repeat(5, 1fr);
        }
    }

    .row2-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    @media (min-width: 1024px) {
        .row2-grid {
            grid-template-columns: 7fr 5fr;
            align-items: stretch;
        }
    }

    .row3-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 1024px) {
        .row3-grid {
            grid-template-columns: 7fr 5fr;
        }
    }

    @media (max-width: 1023px) {
        #dentistCalendarContainer {
            min-height: 360px !important;
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
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
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

        .kpi-grid>div p.text-4xl {
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

<main id="mainContent" class="pt-[100px] px-3 md:px-6 py-6 fade-in min-h-screen flex-1">
    <div class="w-full fade-in">

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
                                <i class="fa-solid fa-hand text-yellow-300 wave-hand"></i>
                            </span>
                        </h1>
                        <p>Wishing you a healthy and happy day!</p>
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

        <div class="kpi-grid">

            {{-- KPI 1: Dental Cases --}}
            <div class="relative overflow-hidden rounded-2xl p-5 text-white"
                style="background:linear-gradient(135deg,#8B0000 0%,#5a0000 100%);box-shadow:0 4px 20px rgba(139,0,0,.35);">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                        style="background:rgba(255,255,255,.15);">
                        <i class="fa-solid fa-tooth text-yellow-300 text-lg"></i>
                    </div>
                    @if (!is_null($dentalCasesDelta))
                    <span class="text-[10px] font-bold px-2 py-1 rounded-full"
                        style="background:rgba(255,255,255,.15);">
                        {{ $dentalCasesDelta >= 0 ? '+' : '' }}{{ $dentalCasesDelta }}%
                    </span>
                    @endif
                </div>
                <p class="text-4xl font-extrabold leading-none tracking-tight">{{ $dentalCasesThisMonth }}</p>
                <p class="text-xs font-semibold mt-1 uppercase tracking-widest" style="opacity:.65;">Dental Cases</p>
                <p class="text-xs mt-0.5" style="opacity:.45;">{{ now()->format('F Y') }}</p>
                <div class="absolute -bottom-5 -right-5 w-24 h-24 rounded-full"
                    style="background:rgba(255,255,255,.05);"></div>
            </div>

            {{-- KPI 2: Total Appointments --}}
            <div class="relative overflow-hidden rounded-2xl p-5 bg-white"
                style="border:1.5px solid #fce8e8;box-shadow:0 2px 12px rgba(139,0,0,.08);">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                        <i class="fa-regular fa-calendar-check text-[#8B0000] text-lg"></i>
                    </div>
                    @if (!is_null($totalApptsDelta))
                    <span
                        class="text-[10px] font-bold px-2 py-1 rounded-full {{ $totalApptsDelta >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                        {{ $totalApptsDelta >= 0 ? '+' : '' }}{{ $totalApptsDelta }}%
                    </span>
                    @endif
                </div>
                <p class="text-4xl font-extrabold leading-none tracking-tight text-[#8B0000]">{{ $totalApptsThisMonth }}
                </p>
                <p class="text-xs font-semibold mt-1 uppercase tracking-widest text-[#8B0000]/50">Total Appointments</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ now()->format('F Y') }}</p>
                <div class="absolute -bottom-5 -right-5 w-24 h-24 rounded-full bg-red-50/40"></div>
            </div>

            {{-- KPI 3: Today's Patients --}}
            <div class="relative overflow-hidden rounded-2xl p-5 bg-white"
                style="border:1.5px solid #fce8e8;box-shadow:0 2px 12px rgba(139,0,0,.08);">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex-shrink-0"
                        style="display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-user-clock text-[#8B0000] text-lg"></i>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-green-50 text-green-700">Today</span>
                </div>
                <p class="text-4xl font-extrabold leading-none tracking-tight text-[#8B0000]">
                    {{ $todayAppointments->count() }}</p>
                <p class="text-xs font-semibold mt-1 uppercase tracking-widest text-[#8B0000]/50">Today's Patients</p>
                <p class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                    <span class="text-green-500 font-bold">{{ $todayAppointments->where('status', 'confirmed')->count()
                        }}
                        confirmed</span>
                    <span class="text-gray-300">·</span>
                    <span class="text-yellow-500 font-bold">{{ $todayAppointments->where('status', 'pending')->count()
                        }}
                        pending</span>
                </p>
                <div class="absolute -bottom-5 -right-5 w-24 h-24 rounded-full bg-red-50/40"></div>
            </div>

            {{-- KPI 4: Live Clock --}}
            <div class="relative overflow-hidden rounded-2xl p-5"
                style="background:linear-gradient(135deg,#7b0c0c 0%,#4a0606 100%);box-shadow:0 4px 20px rgba(100,0,0,.3);">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0">
                        <i id="kpi-clock-icon" class="fa-solid fa-sun text-yellow-300 text-lg"></i>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-full text-white"
                        style="background:rgba(255,255,255,.15);">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse mr-1"
                            style="vertical-align:middle;"></span>Live
                    </span>
                </div>
                <p class="leading-none text-white">
                    <span id="kpi-clock-hhmm" class="text-3xl font-extrabold tracking-tight"
                        style="font-variant-numeric:tabular-nums;">00:00</span>
                    <span id="kpi-clock-ss" class="text-xl font-semibold opacity-50"
                        style="font-variant-numeric:tabular-nums;">:00</span>
                    <span id="kpi-clock-ampm" class="text-xs font-bold opacity-60 ml-1 align-super">AM</span>
                </p>
                <p class="text-xs font-semibold mt-1 uppercase tracking-widest text-white" style="opacity:.55;">Live
                    Time</p>
                <p class="text-xs mt-0.5 text-white flex items-center gap-1.5" style="opacity:.45;">
                    <i id="kpi-clock-dayicon" class="fa-solid fa-sun text-xs flex-shrink-0" style="color:#fde68a;"></i>
                    <span id="kpi-clock-date"></span>
                </p>
                <div class="absolute -bottom-5 -right-5 w-24 h-24 rounded-full"
                    style="background:rgba(255,255,255,.04);"></div>
            </div>

            {{-- KPI 5: Clinic Status --}}
            <div class="relative overflow-hidden rounded-2xl p-5 bg-white"
                style="border:1.5px solid #d1fae5;box-shadow:0 2px 12px rgba(16,185,129,.08);">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl flex-shrink-0"
                        style="background:#e6f4ea; display:flex; align-items:center; justify-content:center;">
                        <i id="statusKpiIcon" class="fa-solid fa-door-open text-lg" style="color:#00A96E;"></i>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-full"
                        style="background:#e6f4ea; color:#00A96E;">Live</span>
                </div>
                <p id="statusKpiLabel" class="text-4xl font-extrabold leading-none tracking-tight"
                    style="color:#00A96E;">Open</p>
                <p class="text-xs font-semibold mt-1 uppercase tracking-widest text-[#8B0000]/50">Clinic Status</p>
                <button onclick="openStatusModal()"
                    class="mt-2 text-[11px] font-bold text-[#8B0000] hover:text-[#660000] transition flex items-center gap-1">
                    Change <i class="fa-solid fa-arrow-right"></i>
                </button>
                <div class="absolute -bottom-5 -right-5 w-24 h-24 rounded-full"
                    style="background:#e6f4ea; opacity: 0.5;"></div>
            </div>

        </div>

        <div class="row2-grid">
            <div>
                <div id="dentistCalendarContainer" class="w-full h-full min-h-[420px]">
                    <div class="cal-shell skeleton-shell p-5 sm:p-6 h-full border-none shadow-sm">
                        <div class="animate-pulse space-y-4">
                            <div class="h-6 w-40 bg-gray-200 rounded mx-auto"></div>
                            <div class="grid grid-cols-7 gap-2">
                                @for ($i = 0; $i < 35; $i++) <div class="h-9 bg-gray-200 rounded-lg">
                            </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="card bg-gradient-to-b from-[#8B0000] to-[#660000] text-white shadow rounded-xl h-full">
                <div class="card-body flex flex-col">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-bold flex items-center gap-2">
                            <i class="fa-regular fa-clock text-yellow-300"></i> Scheduled Today
                        </h2>
                        <span class="badge bg-yellow-400 text-[#660000] font-bold border-none px-3">
                            {{ $todayAppointments->count() }}
                            {{ \Illuminate\Support\Str::plural('patient', $todayAppointments->count()) }}
                        </span>
                    </div>
                    <div class="space-y-2 flex-1 overflow-y-auto pr-1">
                        @forelse($todayAppointments as $appointment)
                        @php
                        $name = $appointment->patient->name ?? 'Unknown Patient';
                        $time = \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A');
                        $service =
                        $appointment->service_type === 'others'
                        ? $appointment->other_services ?? 'Other Service'
                        : $appointment->service_type;
                        $isConfirmed = $appointment->status === 'confirmed';
                        @endphp
                        <a href="{{ route('dentist.dentist.appointments') }}"
                            class="flex items-center gap-3 bg-white/10 hover:bg-white/20 border border-white/20 p-3 rounded-xl w-full transition duration-200 hover:scale-[1.01]">
                            <div
                                class="rounded-full w-9 h-9 border-2 border-yellow-300 bg-white/20 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                {{ strtoupper(substr($name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm truncate">{{ $name }}</p>
                                <p class="text-xs opacity-70 truncate flex items-center gap-1">
                                    <i class="fa-solid fa-stethoscope text-yellow-300 flex-shrink-0 text-[10px]"></i>
                                    {{ ucwords($service) }} · {{ $time }}
                                </p>
                            </div>
                            @if ($isConfirmed)
                            <span
                                class="badge badge-sm bg-green-400 text-white border-none flex-shrink-0 text-[10px]">✓</span>
                            @else
                            <span
                                class="badge badge-sm bg-yellow-400 text-[#660000] border-none flex-shrink-0 text-[10px]">!</span>
                            @endif
                        </a>
                        @empty
                        <div class="flex flex-col items-center justify-center py-10 opacity-60">
                            <i class="fa-regular fa-calendar-xmark text-4xl mb-3 text-yellow-300"></i>
                            <p class="text-sm font-semibold">No appointments today</p>
                            <p class="text-xs opacity-70 mt-1">Enjoy your free day, Doctor!</p>
                        </div>
                        @endforelse
                    </div>
                    <a href="{{ route('dentist.dentist.appointments') }}"
                        class="mt-4 flex items-center justify-center gap-2 text-xs font-semibold text-yellow-300 hover:text-yellow-200 transition border-t border-white/10 pt-3">
                        View all appointments <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row3-grid">

        <div class="relative bg-white p-5 rounded-3xl shadow-sm flex flex-col shadow-xl">

            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-base font-bold text-[#8B0000]">GAD Analytics</h3>
                    <p class="text-sm text-gray-600">Gender and Development Data</p>
                    <p class="text-xs text-gray-400 mt-0.5">Patient cases by category and sex —
                        {{ now()->format('F Y') }}</p>
                </div>

                <div class="flex items-center justify-end space-x-4 flex-wrap gap-y-1">
                    <div class="flex items-center justify-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#E5B5B5] flex-shrink-0"></span>
                        <span class="text-xs text-gray-600 leading-none">Female</span>
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#89CFF0] flex-shrink-0"></span>
                        <span class="text-xs text-gray-600 leading-none">Male</span>
                    </div>
                </div>
            </div>

            <div class="flex-grow flex items-center justify-center w-full min-h-[200px]">

                <canvas id="gadChart" style="display: block; width: 100%; height: 100%;"></canvas>

                <div id="empty-state-container" class="flex flex-col items-center justify-center text-gray-500 gap-4"
                    style="display: none;">

                    <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                        <text x="12" y="16" text-anchor="middle" font-size="6" fill="currentColor"
                            font-weight="bold">?</text>
                    </svg>

                    <div class="text-center flex flex-col gap-1 items-center">
                        <p class="font-semibold text-base text-gray-700 leading-tight">No Treatments Recorded</p>
                        <p class="text-sm text-gray-400 max-w-xs">There are no completed treatment records for this
                            month yet.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5">
            <div
                class="relative bg-white p-5 rounded-3xl shadow-sm flex flex-col overflow-hidden group border border-gray-100">
                <div
                    class="absolute -top-6 -right-6 w-24 h-24 bg-red-50 rounded-full z-0 opacity-70 group-hover:scale-110 transition-transform duration-500">
                </div>

                <div class="relative z-10 flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-[#8B0000] border border-red-100 flex-shrink-0">
                            <i class="fa-solid fa-boxes-stacked text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-[#8B0000] text-sm">Medical Supplies</h3>
                            <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Top
                                Inventory
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('dentist.dentist.inventory') }}"
                        class="text-[11px] font-bold text-[#8B0000] bg-red-50 hover:bg-[#8B0000] hover:text-white px-3 py-1.5 rounded-full transition-all duration-200 flex items-center gap-1.5">
                        View All <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                @if ($medicalSupplies->count() > 0)
                <div class="relative z-10 overflow-y-auto overflow-x-auto pr-1" style="max-height: 180px;">
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
                            @foreach ($medicalSupplies as $item)
                            @php
                            $balance = $item->qty - $item->used;
                            $pct = $item->qty > 0 ? ($balance / $item->qty) * 100 : 100;
                            $isLow = $pct <= 30; $badgeCls=$isLow
                                ? 'bg-red-50 text-red-600 border-red-200 animate-pulse'
                                : 'bg-green-50 text-green-600 border-green-200' ; @endphp <tr
                                class="border-b border-gray-50 last:border-none hover:bg-gray-50/50 transition-colors">
                                <td class="py-2.5 max-w-[140px] truncate font-semibold text-gray-800">
                                    {{ $item->name }}
                                </td>
                                <td class="py-2.5 text-center font-medium">{{ $item->qty }}</td>
                                <td class="py-2.5 text-center text-gray-500">{{ $item->used }}</td>
                                <td class="py-2.5 text-right">
                                    <span class="px-2 py-1 rounded-md border text-[10px] font-bold {{ $badgeCls }}">
                                        {{ $balance }}{{ $isLow ? ' ⚠' : '' }}
                                    </span>
                                </td>
                                </tr>
                                @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div
                    class="relative z-10 flex flex-col items-center justify-center py-6 text-center opacity-60 bg-gray-50 rounded-xl mt-2 border border-dashed border-gray-200">
                    <i class="fa-solid fa-box-open text-3xl mb-2 text-[#8B0000]/50"></i>
                    <p class="text-xs font-semibold text-gray-500">No medical supplies yet</p>
                </div>
                @endif
            </div>

            <div
                class="relative bg-white p-5 rounded-3xl shadow-sm flex flex-col overflow-hidden group border border-gray-100">
                <div
                    class="absolute -bottom-6 -right-6 w-24 h-24 bg-yellow-50 rounded-full z-0 opacity-70 group-hover:scale-110 transition-transform duration-500">
                </div>

                <div class="relative z-10 flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center text-[#8B0000] border border-yellow-100 flex-shrink-0">
                            <i class="fa-solid fa-pills text-lg text-yellow-600"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-[#8B0000] text-sm">Medicine Supplies</h3>
                            <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Top
                                Inventory
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('dentist.dentist.inventory') }}"
                        class="text-[11px] font-bold text-yellow-700 bg-yellow-50 hover:bg-yellow-400 hover:text-[#8B0000] px-3 py-1.5 rounded-full transition-all duration-200 flex items-center gap-1.5">
                        View All <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                @if ($medicineSupplies->count() > 0)
                <div class="relative z-10 overflow-y-auto overflow-x-auto pr-1" style="max-height: 180px;">
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
                            @foreach ($medicineSupplies as $item)
                            @php
                            $balance = $item->qty - $item->used;
                            $pct = $item->qty > 0 ? ($balance / $item->qty) * 100 : 100;
                            $isLow = $pct <= 30; $badgeCls=$isLow
                                ? 'bg-red-50 text-red-600 border-red-200 animate-pulse'
                                : 'bg-green-50 text-green-600 border-green-200' ; @endphp <tr
                                class="border-b border-gray-50 last:border-none hover:bg-gray-50/50 transition-colors">
                                <td class="py-2.5 max-w-[120px] truncate font-semibold text-gray-800">
                                    {{ $item->name }}
                                </td>
                                <td class="py-2.5 text-center text-gray-500">{{ $item->form ?? '—' }}</td>
                                <td class="py-2.5 text-center font-medium">{{ $item->qty }}</td>
                                <td class="py-2.5 text-right">
                                    <span class="px-2 py-1 rounded-md border text-[10px] font-bold {{ $badgeCls }}">
                                        {{ $balance }}{{ $isLow ? ' ⚠' : '' }}
                                    </span>
                                </td>
                                </tr>
                                @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div
                    class="relative z-10 flex flex-col items-center justify-center py-6 text-center opacity-60 bg-gray-50 rounded-xl mt-2 border border-dashed border-gray-200">
                    <i class="fa-solid fa-prescription-bottle-medical text-3xl mb-2 text-[#8B0000]/50"></i>
                    <p class="text-xs font-semibold text-gray-500">No medicine items yet</p>
                </div>
                @endif
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
                    <h2 class="text-lg font-extrabold">Scheduled Patients</h2>
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

@if (session('activeAppointmentModal'))
<dialog id="activeAppointmentModal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg">Active Appointment!</h3>
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
@endif
@endsection

@section('scripts')
<script>
    const dashboardData = {
        gadLabels: @json($gadLabels),
        gadFemale: @json($gadFemale),
        gadMale: @json($gadMale),
        apptCounts: @json($calendarAppointmentCounts),
        apptDetails: @json($calendarAppointmentDetails),
        unavailableDates: @json($blockedDates ?? []),
        holidays: @json($philippineHolidays ?? []),
    };

    const GAD_LABELS = dashboardData.gadLabels;
    const GAD_FEMALE = dashboardData.gadFemale;
    const GAD_MALE = dashboardData.gadMale;

    document.addEventListener("DOMContentLoaded", () => {
        const nameEl = document.getElementById("dentistName");
        const greetingEl = document.getElementById("greetingText");

        nameEl.textContent = "{{ auth()->user()->name ?? 'Doctor' }}";

        const h = new Date().getHours();
        let greeting = "";

        if (h < 12) {
            greeting = "Good Morning,";
        } else if (h < 18) {
            greeting = "Good Afternoon,";
        } else {
            greeting = "Good Evening,";
        }

        greetingEl.textContent = greeting + " ";
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
            const isDaytime = h >= 6 && h < 18;
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
                bigicon.className = isDaytime ? 'fa-solid fa-sun text-lg' : 'fa-solid fa-moon text-lg';
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
                kpiIcon.className = 'fa-solid fa-door-open text-lg';
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
                kpiIcon.className = 'fa-solid fa-door-closed text-lg';
                kpiIcon.style.color = '#EF4444';
            }
        }
        closeStatusModal();
    }

    document.getElementById('statusModal').addEventListener('click', function (e) {
        if (e.target === this) closeStatusModal();
    });

    document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            const ctx = document.getElementById('gadChart');
            if (!ctx) return;

            if (typeof Chart === 'undefined') {
                console.warn('Chart.js is not loaded');
                return;
            }

            const hasData = [...GAD_FEMALE, ...GAD_MALE].some(v => v > 0);
            const emptyState = document.getElementById('empty-state-container');

            if (!hasData) {
                ctx.style.display = 'none';
                if (emptyState) emptyState.style.display = 'flex';
                return;
            }

            ctx.style.display = 'block';
            if (emptyState) emptyState.style.display = 'none';

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: GAD_LABELS,
                    datasets: [{
                        label: 'Female',
                        data: GAD_FEMALE,
                        backgroundColor: 'rgba(255,192,203,0.85)',
                        borderColor: '#FFB6C1',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Male',
                        data: GAD_MALE,
                        backgroundColor: 'rgba(137,207,240,0.85)',
                        borderColor: '#7EC8E3',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }, 150);

        loadDentistCalendar();
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
        <div class="day-hover-card absolute left-1/2 top-full z-[70] mt-1 w-[320px] -translate-x-1/2 rounded-2xl border border-[#efe6df] bg-white p-4 shadow-2xl opacity-0 invisible pointer-events-none transition-all duration-150 group-hover:opacity-100 group-hover:visible group-hover:pointer-events-auto">
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
        </div>
        `;
        }

        const items = safeAppointments.slice(0, 3).map(appt => {
            const status = String(appt.status || '').toLowerCase();
            const canReschedule = ['pending', 'confirmed', 'upcoming', 'rescheduled'].includes(status);
            const canCancel = ['pending', 'confirmed', 'upcoming', 'rescheduled'].includes(status);

            const safeName = escJs(appt.name || 'Unknown Patient');
            const safeService = escJs(appt.service || 'General Service');
            const safeSchedule = escJs(`${formatModalDate(appt.date || dateStr)} • ${appt.time || '—'}`);
            const rawProfileUrl = appt.patientProfileUrl || '#';
            const profileUrl = `${rawProfileUrl}${rawProfileUrl.includes('?') ? '&' : '?'}from=dashboard`;

            return `
        <div class="rounded-xl border border-gray-100 p-3 bg-white">
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
        </div>
        `;
        }).join('');

        return `
        <div class="day-hover-card absolute left-1/2 top-full z-[70] mt-1 w-[340px] -translate-x-1/2 rounded-2xl border border-[#efe6df] bg-white p-3 shadow-2xl opacity-0 invisible pointer-events-none transition-all duration-150 group-hover:opacity-100 group-hover:visible group-hover:pointer-events-auto">
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
        </div>
        `;
    }

    function loadDentistCalendar() {

        function renderUnifiedCalendarLegend(mode) {
            if (mode !== 'dentist') return '';

            return `
        <div class="cal-legend mt-4">
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
        </div>
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
                `<div class="text-center text-[0.6rem] font-bold py-1 pb-2 uppercase tracking-widest ${(i === 0 || i === 6) ? 'cal-day-weekend' : 'cal-day-label'}">${l}</div>`
            ).join('');

            let cells = '';
            for (let i = 0; i < firstDow; i++) cells += `<div></div>`;

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

                // 1. Tooltip and Badge Evaluation
                if (holiday) {
                    badgeHtml = `
                <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-yellow-400 text-[10px] leading-none flex items-center justify-center text-white shadow-[0_2px_8px_rgba(0,0,0,0.18)] border border-white">
                    <i class="fa-solid fa-star text-[8px]"></i>
                </span>`;
                    tooltipTxt = `<i class="fa-solid fa-star mr-1 text-white"></i>${holiday}`;
                    tooltipTone = 'yellow';
                }

                if (hasAppts && !isUnavail) {
                    if (isFull) {
                        tooltipTxt = `<i class="fa-solid fa-circle-xmark mr-1 text-red-300"></i>Fully booked — ${count} patient${count > 1 ? 's' : ''}`;
                        tooltipTone = 'red';
                    } else {
                        tooltipTxt = `<i class="fa-solid fa-user-clock mr-1 text-emerald-300"></i>${count} patient${count > 1 ? 's' : ''} scheduled`;
                        tooltipTone = 'green';
                    }
                    const dotClass = isFull ? 'bg-red-600' : 'bg-emerald-600';
                    badgeHtml = `
                <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full ${dotClass} text-[9px] font-bold leading-none flex items-center justify-center text-white shadow-[0_2px_8px_rgba(0,0,0,0.18)] border border-white">
                    ${count}
                </span>`;
                }

                if (isToday && !hasAppts && !holiday) {
                    tooltipTxt = `<i class="fa-solid fa-calendar-day mr-1 text-white/90"></i>Today`;
                    tooltipTone = 'today';
                } else if (isUnavail && !holiday && !hasAppts) {
                    badgeHtml = `
                <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-gray-500 text-[10px] leading-none flex items-center justify-center text-white shadow-[0_2px_8px_rgba(0,0,0,0.18)] border border-white">
                    <i class="fa-solid fa-minus text-[8px]"></i>
                </span>`;
                    tooltipTxt = weekend ? `<i class="fa-solid fa-ban mr-1 text-gray-300"></i>Clinic closed` : `<i class="fa-solid fa-ban mr-1 text-gray-300"></i>Not available`;
                    tooltipTone = 'gray';
                } else if (!hasAppts && !holiday && !isToday && !isUnavail) {
                    tooltipTxt = `<i class="fa-regular fa-calendar mr-1 text-gray-300"></i>No scheduled patients`;
                    tooltipTone = 'gray';
                }

                // 2. Exact unified cal-cell classes
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

                // 3. Perfecting the Smart Tooltip HTML
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
                <div class="day-smart-tooltip ${tooltipSide} absolute bottom-[calc(100%+10px)] z-[9999] pointer-events-none">
                    <div class="${palette.bg} relative text-white text-[0.65rem] font-bold px-3 py-2 rounded-lg whitespace-nowrap shadow-xl
                        after:content-[''] after:absolute after:top-full after:border-4 after:border-transparent ${palette.arrow}">
                        ${tooltipTxt}
                    </div>
                </div>
            ` : '';

                const hoverCardHtml = showHoverCard ? buildDayHoverCard(dateStr, dayAppointments) : '';
                const clickOpen = canOpenModal && !isHoverDevice ? `onclick="openDayAppointmentsModal('${dateStr}', decodeURIComponent('${encodedAppointments}'))"` : '';

                // 4. Using unified cal-cell-wrap 
                cells += `
                <div class="cal-cell-wrap relative flex items-center justify-center group" ${clickOpen}>
                    ${tooltipHtml}
                    ${hoverCardHtml}
                    <div class="${cellClass}" data-date="${dateStr}">
                        <span>${d}</span>
                        ${badgeHtml}
                    </div>
                </div>`;
            }

            const container = document.getElementById('dentistCalendarContainer');
            if (container) {
                // Replicates exactly the Patient UI structure (.cal-shell, .cal-nav-btn, .cal-month-label)
                container.innerHTML = `
            <div class="cal-shell flex flex-col justify-between h-full bg-white shadow-sm border-none p-5 sm:p-6">
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
                ${renderUnifiedCalendarLegend('dentist')}
            </div>`;
            }
        }

        window.changeDentistMonth = function (dir) {
            currentMonth += dir;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            if (currentMonth < 0) {
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
<div class="flex flex-col items-center justify-center py-10 text-center opacity-60">
<i class="fa-regular fa-calendar-xmark text-4xl mb-3 text-[#8B0000]"></i>
<p class="text-sm font-semibold text-gray-700">No appointments for this date</p>
</div>
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
                const safeSchedule = `${formatModalDate(appt.date || dateStr)} • ${appt.time || '—'}`.replace(
                    /'/g, "\'");

                return `
<div class="border border-gray-100 rounded-xl p-3 hover:bg-gray-50 transition">
<div class="flex items-center gap-3">
<a href="${profileUrl}${profileUrl.includes('?') ? '&' : '?'}from=dashboard" onclick="closeDayAppointmentsModal()"
class="rounded-full w-10 h-10 border-2 border-[#8B0000]/10 bg-[#8B0000]/10 flex items-center justify-center font-bold text-sm text-[#8B0000] flex-shrink-0">
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
</div>
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