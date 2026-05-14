@extends('layouts.admin')

@section('title', 'Appointments | PUP Taguig Dental Clinic')

@section('content')
@php
use Carbon\Carbon;
use Illuminate\Support\Str;

$upcomingAppointments = collect($upcomingAppointments ?? []);
$pastAppointments = collect($pastAppointments ?? []);
$today = $today ?? Carbon::today()->toDateString();

$todayAppts = $upcomingAppointments->filter(fn($a) => ($a->appointment_date ?? null) === $today);
$todayCount = $todayAppts->count();

$nextAppt = $upcomingAppointments->sortBy('appointment_date')->first();
$nextName = $nextAppt ? (optional($nextAppt->patient)->name ?? 'Unknown') : null;
$nextTime = $nextAppt ? Carbon::parse($nextAppt->appointment_time)->format('g:i A') : null;
$nextDate = $nextAppt ? Carbon::parse($nextAppt->appointment_date)->format('M j') : null;

$upcomingGrouped = $upcomingAppointments->groupBy(fn($a) => Carbon::parse($a->appointment_date)->format('F'));
$pastGrouped = $pastAppointments->groupBy(fn($a) => Carbon::parse($a->appointment_date)->format('F'));

$upcomingTotal = $upcomingAppointments->count();
$pastTotal = $pastAppointments->count();
@endphp

<main id="mainContent" class="pt-[80px] sm:pt-[88px] px-3 sm:px-6 pb-6 min-h-screen">
    <div class="max-w-7xl mx-auto">

        <div class="page-banner mt-2">
            <div class="page-banner-inner">
                <div class="appointment-banner-title-wrap">
                    <h1 class="page-title">Appointment Management</h1>
                </div>

                <div class="appointment-banner-actions">

                    <div class="tab-toggle-wrap">
                        <button id="btnUpcoming" class="tab-btn-toggle active">
                            <i class="fa-solid fa-calendar-days text-xs"></i>
                            Upcoming
                            <span class="tab-count-badge">{{ $upcomingTotal }}</span>
                        </button>
                        <button id="btnPast" class="tab-btn-toggle">
                            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                            Past
                            <span class="tab-count-badge">{{ $pastTotal }}</span>
                        </button>
                    </div>

                    <div class="view-toggle" id="appointmentViewToggle">
                        <button type="button" class="view-toggle-btn active" id="appointmentListViewBtn"
                            title="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="view-toggle-btn" id="appointmentGridViewBtn" title="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar">
            <i class="fa-solid fa-circle-info text-white/60 text-sm"></i>
            <span class="text-white/70 text-xs font-medium hidden sm:inline">Today's snapshot:</span>

            @if($todayCount > 0)
            <span class="summary-chip summary-chip-highlight">
                <i class="fa-solid fa-calendar-check text-xs"></i>
                {{ $todayCount }} appt{{ $todayCount > 1 ? 's' : '' }} today
            </span>
            @else
            <span class="summary-chip">
                <i class="fa-regular fa-calendar text-xs"></i>
                No appointments today
            </span>
            @endif

            @if($nextAppt)
            <span class="summary-chip hidden sm:inline-flex">
                <i class="fa-solid fa-clock text-xs"></i>
                Next: <strong>{{ $nextName }}</strong> — {{ $nextDate }} at {{ $nextTime }}
            </span>
            @endif
        </div>

        {{-- ── Upcoming Section ── --}}
        <section id="upcomingSection" class="pb-16 mt-2">
            @forelse($upcomingGrouped as $month => $items)
            <div class="mb-10 sm:mb-14">
                <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-5">
                    <div class="timeline-dot"></div>
                    <h3 class="text-lg sm:text-xl font-bold text-[#8b0000]">{{ $month }}</h3>
                    <span
                        class="bg-[#f9f0f0] text-[#8b0000] text-xs font-semibold px-3 py-1 rounded-full border border-[#8b0000]/15">
                        {{ $items->count() }} {{ Str::plural('appointment', $items->count()) }}
                    </span>
                </div>

                <div class="appointments-list-view">
                    <div class="desktop-appointments-table relative pl-10">
                        <div
                            class="absolute left-[8px] top-0 bottom-0 w-[2px] bg-gradient-to-b from-[#8b0000]/30 to-[#8b0000]/05 rounded-full">
                        </div>

                        <div
                            class="grid grid-cols-[1.2fr_0.9fr_1.3fr_1.3fr_0.8fr_0.8fr_auto] text-[11px] font-semibold uppercase tracking-wider text-gray-600 pb-2 border-b border-gray-200 mb-3 px-5">
                            <span class="flex items-center gap-1.5"><i
                                    class="fa-regular fa-calendar text-[10px]"></i>Date</span>
                            <span class="flex items-center gap-1.5"><i
                                    class="fa-regular fa-clock text-[10px]"></i>Time</span>
                            <span>Service</span>
                            <span>Patient</span>
                            <span>Program</span>
                            <span>Status</span>
                            <span class="text-right">Actions</span>
                        </div>

                        <div class="space-y-2.5">
                            @foreach($items as $i => $appt)
                            @php
                            $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                            $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                            $dateLabel = Carbon::parse($appt->appointment_date)->format('F j, Y');
                            $weekday = Carbon::parse($appt->appointment_date)->format('l');
                            $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i
                            A') : '—';
                            $serviceLabel = ($appt->service_type ?? '') === 'Others'
                            ? (($appt->other_services ?? '') ?: 'Others')
                            : ($appt->service_type ?? '—');
                            $isToday = ($appt->appointment_date ?? null) === $today;

                            $serviceLower = strtolower($serviceLabel);
                            $badgeClass = 'service-badge-default';
                            if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                            elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                            elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                            elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                            @endphp

                            <div class="appt-card {{ $isToday ? 'is-today' : '' }}" data-appt-id="{{ $appt->id }}"
                                style="animation-delay:{{ $i * 0.04 }}s">
                                <div
                                    class="grid grid-cols-[1.2fr_0.9fr_1.3fr_1.3fr_0.8fr_0.8fr_auto] items-center px-5 py-3.5 gap-2">
                                    <div>
                                        <p class="text-[13px] font-semibold text-gray-800">{{ $dateLabel }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $weekday }}</p>
                                        @if($isToday)
                                        <span
                                            class="inline-block mt-1 text-[9px] font-bold uppercase tracking-wide bg-green-500 text-white px-2 py-0.5 rounded-md">Today</span>
                                        @endif
                                    </div>

                                    <div>
                                        <span class="time-chip"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel
                                            }}</span>
                                    </div>

                                    <div>
                                        <span class="service-badge {{ $badgeClass }}">{{ $serviceLabel }}</span>
                                    </div>

                                    <div>
                                        <p class="text-[13px] font-semibold text-gray-800">{{ $patientName }}</p>
                                    </div>

                                    <div>
                                        @if($program === '—')
                                        <span class="text-[12px] text-gray-400">—</span>
                                        @else
                                        <span
                                            class="inline-block bg-gray-100 text-gray-500 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">
                                            {{ $program }}
                                        </span>
                                        @endif
                                    </div>

                                    <div>
                                        @if($isToday)
                                        <span class="status-pill status-confirmed"><span
                                                class="status-dot"></span>Confirmed</span>
                                        @else
                                        <span class="status-pill status-pending"><span
                                                class="status-dot"></span>Upcoming</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-end gap-1 flex-nowrap">
                                        <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                                            class="action-btn action-btn-view">
                                            <i class="fa-regular fa-user text-[9px]"></i> View
                                        </a>

                                        <button type="button" class="action-btn action-btn-start"
                                            onclick="openStartProcedureModal(this)" data-id="{{ $appt->id }}"
                                            data-name="{{ $patientName }}"
                                            data-datetime="{{ $dateLabel }} | {{ $timeLabel }}" {{ $isToday ? ''
                                            : 'disabled' }}>
                                            <i class="fa-solid fa-play text-[9px]"></i> Start
                                        </button>

                                        <button type="button" class="action-btn action-btn-reschedule"
                                            onclick="openRescheduleModal(this)" data-id="{{ $appt->id }}"
                                            data-name="{{ $patientName }}"
                                            data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                            <i class="fa-solid fa-rotate-right text-[9px]"></i> Reschedule
                                        </button>

                                        <button type="button" class="action-btn action-btn-cancel"
                                            onclick="openCancelAppointmentModal(this)" data-id="{{ $appt->id }}"
                                            data-name="{{ $patientName }}"
                                            data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                            <i class="fa-solid fa-xmark text-[9px]"></i> Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="appointments-grid-view">
                    <div class="appointments-grid">
                        @foreach($items as $i => $appt)
                        @php
                        $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                        $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                        $dateLabel = Carbon::parse($appt->appointment_date)->format('M j, Y');
                        $weekday = Carbon::parse($appt->appointment_date)->format('l');
                        $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') :
                        '—';
                        $serviceLabel = ($appt->service_type ?? '') === 'Others'
                        ? (($appt->other_services ?? '') ?: 'Others')
                        : ($appt->service_type ?? '—');
                        $isToday = ($appt->appointment_date ?? null) === $today;

                        $serviceLower = strtolower($serviceLabel);
                        $badgeClass = 'service-badge-default';
                        if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                        elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                        elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                        elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                        @endphp

                        <div class="mobile-appt-card {{ $isToday ? 'is-today' : '' }}" data-appt-id="{{ $appt->id }}"
                            style="animation-delay:{{ $i * 0.04 }}s">
                            <div class="mobile-appt-top">
                                <div class="mobile-appt-patient">
                                    <div class="mobile-appt-name">{{ $patientName }}</div>
                                    <div class="mobile-appt-sub">{{ $weekday }}, {{ $dateLabel }}</div>
                                </div>

                                @if($isToday)
                                <span class="status-pill status-confirmed flex-shrink-0"><span
                                        class="status-dot"></span>Confirmed</span>
                                @else
                                <span class="status-pill status-pending flex-shrink-0"><span
                                        class="status-dot"></span>Upcoming</span>
                                @endif
                            </div>

                            <div class="mobile-appt-meta">
                                <div class="mobile-appt-field">
                                    <div class="mobile-appt-label">Appointment Details</div>
                                    <div class="mobile-appt-badges">
                                        <span class="time-chip text-xs"><i class="fa-regular fa-clock text-xs"></i>{{
                                            $timeLabel }}</span>
                                        <span class="service-badge {{ $badgeClass }} text-xs">{{ $serviceLabel }}</span>
                                        @if($program !== '—')
                                        <span
                                            class="inline-block bg-gray-100 text-gray-500 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">
                                            {{ $program }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mobile-appt-actions">
                                <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                                    class="action-btn action-btn-view">
                                    <i class="fa-regular fa-user text-[9px]"></i> View
                                </a>

                                <button type="button" class="action-btn action-btn-start"
                                    onclick="openStartProcedureModal(this)" data-id="{{ $appt->id }}"
                                    data-name="{{ $patientName }}" data-datetime="{{ $dateLabel }} | {{ $timeLabel }}"
                                    {{ $isToday ? '' : 'disabled' }}>
                                    <i class="fa-solid fa-play text-[9px]"></i> Start
                                </button>

                                <button type="button" class="action-btn action-btn-reschedule"
                                    onclick="openRescheduleModal(this)" data-id="{{ $appt->id }}"
                                    data-name="{{ $patientName }}" data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                    <i class="fa-solid fa-rotate-right text-[9px]"></i> Reschedule
                                </button>

                                <button type="button" class="action-btn action-btn-cancel"
                                    onclick="openCancelAppointmentModal(this)" data-id="{{ $appt->id }}"
                                    data-name="{{ $patientName }}" data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                    <i class="fa-solid fa-xmark text-[9px]"></i> Cancel
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mobile-appointments-list space-y-3">
                    @foreach($items as $i => $appt)
                    @php
                    $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                    $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                    $dateLabel = Carbon::parse($appt->appointment_date)->format('M j, Y');
                    $weekday = Carbon::parse($appt->appointment_date)->format('l');
                    $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') :
                    '—';
                    $serviceLabel = ($appt->service_type ?? '') === 'Others'
                    ? (($appt->other_services ?? '') ?: 'Others')
                    : ($appt->service_type ?? '—');
                    $isToday = ($appt->appointment_date ?? null) === $today;

                    $serviceLower = strtolower($serviceLabel);
                    $badgeClass = 'service-badge-default';
                    if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                    elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                    elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                    elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                    @endphp

                    <div class="mobile-appt-card {{ $isToday ? 'is-today' : '' }}" data-appt-id="{{ $appt->id }}"
                        style="animation-delay:{{ $i * 0.04 }}s">
                        <div class="flex items-start justify-between gap-2 mb-3 pl-2">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-[13px] font-bold text-gray-800">{{ $patientName }}</p>
                                    @if($isToday)
                                    <span
                                        class="text-[9px] font-bold uppercase bg-green-500 text-white px-2 py-0.5 rounded-md">Today</span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $weekday }}, {{ $dateLabel }}</p>
                            </div>

                            @if($isToday)
                            <span class="status-pill status-confirmed flex-shrink-0"><span
                                    class="status-dot"></span>Confirmed</span>
                            @else
                            <span class="status-pill status-pending flex-shrink-0"><span
                                    class="status-dot"></span>Upcoming</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mb-3 pl-2">
                            <span class="time-chip text-xs"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel
                                }}</span>
                            <span class="service-badge {{ $badgeClass }} text-xs">{{ $serviceLabel }}</span>
                            @if($program !== '—')
                            <span
                                class="inline-block bg-gray-100 text-gray-500 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">
                                {{ $program }}
                            </span>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2 pl-2">
                            <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                                class="action-btn action-btn-view text-xs">
                                <i class="fa-regular fa-user text-[9px]"></i> View
                            </a>

                            <button type="button" class="action-btn action-btn-start text-xs"
                                onclick="openStartProcedureModal(this)" data-id="{{ $appt->id }}"
                                data-name="{{ $patientName }}" data-datetime="{{ $dateLabel }} | {{ $timeLabel }}" {{
                                $isToday ? '' : 'disabled' }}>
                                <i class="fa-solid fa-play text-[9px]"></i> Start
                            </button>

                            <button type="button" class="action-btn action-btn-reschedule text-xs"
                                onclick="openRescheduleModal(this)" data-id="{{ $appt->id }}"
                                data-name="{{ $patientName }}" data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                <i class="fa-solid fa-rotate-right text-[9px]"></i> Reschedule
                            </button>

                            <button type="button" class="action-btn action-btn-cancel text-xs"
                                onclick="openCancelAppointmentModal(this)" data-id="{{ $appt->id }}"
                                data-name="{{ $patientName }}" data-datetime="{{ $dateLabel }} | {{ $timeLabel }}">
                                <i class="fa-solid fa-xmark text-[9px]"></i> Cancel
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                <i class="fa-regular fa-calendar-xmark text-4xl sm:text-5xl mb-4 text-gray-300"></i>
                <p class="text-base font-semibold text-gray-500">No upcoming appointments</p>
                <p class="text-sm mt-1 text-center px-4">New appointments will appear here once scheduled.</p>
            </div>
            @endforelse
        </section>

        {{-- ── Past Section ── --}}
        <section id="pastSection" class="pb-16 mt-2 hidden">
            @forelse($pastGrouped as $month => $items)
            <div class="mb-10 sm:mb-14">
                <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-5 pl-2">
                    <div class="timeline-dot-past"></div>
                    <h3 class="text-lg sm:text-xl font-bold text-gray-400">{{ $month }}</h3>
                    <span class="bg-gray-100 text-gray-400 text-xs font-semibold px-3 py-1 rounded-full">
                        {{ $items->count() }} {{ Str::plural('appointment', $items->count()) }}
                    </span>
                </div>

                <div class="appointments-list-view">
                    <div class="desktop-appointments-table relative pl-10">
                        <div class="absolute left-[8px] top-0 bottom-0 w-[2px] bg-gray-200 rounded-full"></div>

                        <div
                            class="grid grid-cols-[1.4fr_1fr_1.5fr_1.5fr_1fr] text-[11px] font-semibold uppercase tracking-wider text-gray-400 pb-2 border-b border-gray-200 mb-3 px-5">
                            <span class="flex items-center gap-1.5"><i
                                    class="fa-regular fa-calendar text-[10px]"></i>Date</span>
                            <span class="flex items-center gap-1.5"><i
                                    class="fa-regular fa-clock text-[10px]"></i>Time</span>
                            <span>Service</span>
                            <span>Patient</span>
                            <span>Program</span>
                        </div>

                        <div class="space-y-2.5">
                            @foreach($items as $i => $appt)
                            @php
                            $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                            $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                            $dateLabel = Carbon::parse($appt->appointment_date)->format('F j, Y');
                            $weekday = Carbon::parse($appt->appointment_date)->format('l');
                            $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i
                            A') : '—';
                            $serviceLabel = ($appt->service_type ?? '') === 'Others'
                            ? (($appt->other_services ?? '') ?: 'Others')
                            : ($appt->service_type ?? '—');

                            $serviceLower = strtolower($serviceLabel);
                            $badgeClass = 'service-badge-default';
                            if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                            elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                            elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                            elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                            @endphp

                            <div class="appt-card opacity-70" style="animation-delay:{{ $i * 0.04 }}s">
                                <div class="grid grid-cols-[1.4fr_1fr_1.5fr_1.5fr_1fr] items-center px-5 py-3.5 gap-2">
                                    <div>
                                        <p class="text-[13px] font-semibold text-gray-500">{{ $dateLabel }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $weekday }}</p>
                                    </div>

                                    <div>
                                        <span class="time-chip text-gray-400"><i
                                                class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}</span>
                                    </div>

                                    <div>
                                        <span class="service-badge {{ $badgeClass }} opacity-70">{{ $serviceLabel
                                            }}</span>
                                    </div>

                                    <div>
                                        <p class="text-[13px] font-medium text-gray-500">{{ $patientName }}</p>
                                    </div>

                                    <div>
                                        @if($program === '—')
                                        <span class="text-[12px] text-gray-400">—</span>
                                        @else
                                        <span
                                            class="inline-block bg-gray-100 text-gray-400 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">
                                            {{ $program }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="appointments-grid-view">
                    <div class="appointments-grid">
                        @foreach($items as $i => $appt)
                        @php
                        $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                        $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                        $dateLabel = Carbon::parse($appt->appointment_date)->format('M j, Y');
                        $weekday = Carbon::parse($appt->appointment_date)->format('l');
                        $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') :
                        '—';
                        $serviceLabel = ($appt->service_type ?? '') === 'Others'
                        ? (($appt->other_services ?? '') ?: 'Others')
                        : ($appt->service_type ?? '—');

                        $serviceLower = strtolower($serviceLabel);
                        $badgeClass = 'service-badge-default';
                        if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                        elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                        elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                        elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                        @endphp

                        <div class="mobile-appt-card opacity-70 border-gray-200"
                            style="animation-delay:{{ $i * 0.04 }}s">
                            <div class="mobile-appt-top">
                                <div class="mobile-appt-patient">
                                    <div class="mobile-appt-name text-gray-500">{{ $patientName }}</div>
                                    <div class="mobile-appt-sub">{{ $weekday }}, {{ $dateLabel }}</div>
                                </div>

                                <span class="status-pill status-completed flex-shrink-0">
                                    <span class="status-dot"></span>Past
                                </span>
                            </div>

                            <div class="mobile-appt-meta">
                                <div class="mobile-appt-field">
                                    <div class="mobile-appt-label">Appointment Details</div>
                                    <div class="mobile-appt-badges">
                                        <span class="time-chip text-xs text-gray-400">
                                            <i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}
                                        </span>
                                        <span class="service-badge {{ $badgeClass }} opacity-70 text-xs">
                                            {{ $serviceLabel }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mobile-appt-field">
                                    <div class="mobile-appt-label">Program</div>
                                    <div class="mobile-appt-value text-gray-400">
                                        @if($program === '—')
                                        —
                                        @else
                                        <span
                                            class="inline-block bg-gray-100 text-gray-400 text-[11px] px-2.5 py-1 rounded-full border border-gray-200">
                                            {{ $program }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mobile-appointments-list space-y-3">
                    @foreach($items as $i => $appt)
                    @php
                    $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
                    $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
                    $dateLabel = Carbon::parse($appt->appointment_date)->format('M j, Y');
                    $weekday = Carbon::parse($appt->appointment_date)->format('l');
                    $timeLabel = $appt->appointment_time ? Carbon::parse($appt->appointment_time)->format('g:i A') :
                    '—';
                    $serviceLabel = ($appt->service_type ?? '') === 'Others'
                    ? (($appt->other_services ?? '') ?: 'Others')
                    : ($appt->service_type ?? '—');

                    $serviceLower = strtolower($serviceLabel);
                    $badgeClass = 'service-badge-default';
                    if (str_contains($serviceLower, 'surgery')) $badgeClass = 'service-badge-surgery';
                    elseif (str_contains($serviceLower, 'check')) $badgeClass = 'service-badge-checkup';
                    elseif (str_contains($serviceLower, 'whiten')) $badgeClass = 'service-badge-whitening';
                    elseif (str_contains($serviceLower, 'extrac')) $badgeClass = 'service-badge-extraction';
                    @endphp

                    <div class="mobile-appt-card opacity-70 border-gray-200" style="animation-delay:{{ $i * 0.04 }}s">
                        <div class="mobile-appt-top">
                            <div class="mobile-appt-patient">
                                <div class="mobile-appt-name text-gray-500">{{ $patientName }}</div>
                                <div class="mobile-appt-sub">{{ $weekday }}, {{ $dateLabel }}</div>
                            </div>

                            <span class="status-pill status-completed flex-shrink-0">
                                <span class="status-dot"></span>Past
                            </span>
                        </div>

                        <div class="mobile-appt-meta">
                            <div class="mobile-appt-field">
                                <div class="mobile-appt-label">Appointment Details</div>
                                <div class="mobile-appt-badges">
                                    <span class="time-chip text-xs text-gray-400">
                                        <i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}
                                    </span>
                                    <span class="service-badge {{ $badgeClass }} opacity-70 text-xs">
                                        {{ $serviceLabel }}
                                    </span>
                                </div>
                            </div>

                            <div class="mobile-appt-field">
                                <div class="mobile-appt-label">Program</div>
                                <div class="mobile-appt-value text-gray-400">
                                    @if($program === '—')
                                    —
                                    @else
                                    <span
                                        class="inline-block bg-gray-100 text-gray-400 text-[11px] px-2.5 py-1 rounded-full border border-gray-200">
                                        {{ $program }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                <i class="fa-regular fa-calendar-check text-4xl sm:text-5xl mb-4 text-gray-300"></i>
                <p class="text-base font-semibold text-gray-500">No past appointments</p>
                <p class="text-sm mt-1">Completed appointments will appear here.</p>
            </div>
            @endforelse
        </section>

    </div>
</main>

<div id="toastContainer"></div>

{{-- ── Start Procedure Modal ── --}}
<div id="startProcedureModal" class="fixed inset-0 bg-black/45 hidden z-[1000] flex items-center justify-center p-4"
    onclick="handleStartBackdropClick(event)">
    <div class="start-modal-panel bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-[#8B0000]">Start Procedure</h3>
            <p class="text-sm text-gray-500 mt-1">Confirm that you want to start this appointment.</p>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-500">Patient</p>
            <p id="startPatientName" class="font-semibold text-gray-800">—</p>
            <p class="text-sm text-gray-500 mt-4">Appointment</p>
            <p id="startAppointmentDate" class="font-semibold text-gray-800">—</p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button type="button" onclick="closeStartProcedureModal()"
                class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600">Cancel</button>
            <button type="button" onclick="confirmStartProcedure()"
                class="px-4 py-2 rounded-lg bg-green-600 text-white font-semibold">Start</button>
        </div>
    </div>
</div>

{{-- ── Reschedule Modal ── --}}
<div id="rescheduleModal" class="fixed inset-0 bg-black/45 hidden z-[1000] flex items-center justify-center p-4"
    onclick="handleRescheduleBackdropClick(event)">
    <div class="reschedule-modal-panel bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-[#8B0000]">Reschedule Appointment</h3>
            <p class="text-sm text-gray-500 mt-1">You are about to reschedule this appointment.</p>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-500">Patient</p>
            <p id="resPatientName" class="font-semibold text-gray-800">—</p>
            <p class="text-sm text-gray-500 mt-4">Appointment</p>
            <p id="resAppointmentDate" class="font-semibold text-gray-800">—</p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button type="button" onclick="closeRescheduleModal()"
                class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600">Close</button>
            <button type="button" onclick="confirmReschedule()"
                class="px-4 py-2 rounded-lg bg-[#8B0000] text-white font-semibold">Continue</button>
        </div>
    </div>
</div>

{{-- ── Cancel Modal ── --}}
<div id="cancelAppointmentModal" class="fixed inset-0 bg-black/45 hidden z-[1000] flex items-center justify-center p-4"
    onclick="handleCancelBackdropClick(event)">
    <div class="cancel-modal-panel bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-[#8B0000]">Cancel Appointment</h3>
            <p class="text-sm text-gray-500 mt-1">Select a reason before cancelling this appointment.</p>
        </div>

        <div class="px-6 py-5">
            <p class="text-sm text-gray-500">Patient</p>
            <p id="cancelPatientName" class="font-semibold text-gray-800">—</p>
            <p class="text-sm text-gray-500 mt-4">Appointment</p>
            <p id="cancelAppointmentDate" class="font-semibold text-gray-800">—</p>

            <div class="mt-6">
                <p class="text-sm font-semibold text-gray-700 mb-3">Reason</p>
                <div id="cancelReasonChips" class="flex flex-wrap gap-2">
                    @foreach (['Patient no-show', 'Requested by patient', 'Conflict in schedule', 'Emergency case',
                    'Other'] as $reason)
                    <div class="reason-chip">
                        <input type="radio" name="cancelReason" id="reason_{{ \Illuminate\Support\Str::slug($reason) }}"
                            value="{{ $reason }}">
                        <label for="reason_{{ \Illuminate\Support\Str::slug($reason) }}">{{ $reason }}</label>
                    </div>
                    @endforeach
                </div>
                <p id="reasonError" class="hidden text-sm text-red-600 mt-3">Please select a cancellation reason.</p>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button type="button" onclick="closeCancelAppointmentModal()"
                class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600">Close</button>
            <button type="button" id="confirmCancelBtn" onclick="confirmCancelAppointment()"
                class="px-4 py-2 rounded-lg bg-red-600 text-white font-semibold">
                <i class="fa-solid fa-ban text-xs mr-1.5"></i>Yes, Cancel
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const btnUpcoming = document.getElementById('btnUpcoming');
    const btnPast = document.getElementById('btnPast');
    const upcomingSection = document.getElementById('upcomingSection');
    const pastSection = document.getElementById('pastSection');

    if (btnUpcoming && btnPast && upcomingSection && pastSection) {
        btnUpcoming.addEventListener('click', function () {
            btnUpcoming.classList.add('active');
            btnPast.classList.remove('active');
            upcomingSection.classList.remove('hidden');
            pastSection.classList.add('hidden');
        });

        btnPast.addEventListener('click', function () {
            btnPast.classList.add('active');
            btnUpcoming.classList.remove('active');
            pastSection.classList.remove('hidden');
            upcomingSection.classList.add('hidden');
        });
    }

    function getPreferredAppointmentView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem('appointmentView') || 'list';
    }

    function applyAppointmentView(view, save = true) {
        const listViews = document.querySelectorAll('.appointments-list-view');
        const gridViews = document.querySelectorAll('.appointments-grid-view');
        const listBtn = document.getElementById('appointmentListViewBtn');
        const gridBtn = document.getElementById('appointmentGridViewBtn');

        const finalView = window.innerWidth <= 767 ? 'grid' : view;

        if (window.innerWidth <= 767) {
            listViews.forEach(el => el.hidden = true);
            gridViews.forEach(el => el.hidden = true);
        } else {
            listViews.forEach(el => el.hidden = finalView !== 'list');
            gridViews.forEach(el => el.hidden = finalView !== 'grid');
        }

        if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
        if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

        if (save && window.innerWidth > 767) {
            localStorage.setItem('appointmentView', finalView);
        }
    }

    function initAppointmentViewToggle() {
        const listBtn = document.getElementById('appointmentListViewBtn');
        const gridBtn = document.getElementById('appointmentGridViewBtn');

        applyAppointmentView(getPreferredAppointmentView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', () => applyAppointmentView('list', true));
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', () => applyAppointmentView('grid', true));
        }
    }

    function showToast({ title = 'Notice', message = '', duration = 4000 }) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast-item';
        toast.innerHTML = `
            <div class="toast-icon-wrap"><i class="fa-solid fa-ban text-red-400 text-sm"></i></div>
            <div class="flex-1 min-w-0">
                <div class="toast-title">${title}</div>
                ${message ? `<div class="toast-message">${message}</div>` : ''}
            </div>
            <button class="toast-close" onclick="dismissToast(this.closest('.toast-item'))"><i class="fa-solid fa-xmark"></i></button>
            <div class="toast-progress" style="animation-duration:${duration}ms;"></div>
        `;
        container.appendChild(toast);
        setTimeout(() => dismissToast(toast), duration);
    }

    function dismissToast(toast) {
        if (!toast || toast.classList.contains('toast-exit')) return;
        toast.classList.add('toast-exit');
        setTimeout(() => toast.remove(), 350);
    }

    let selectedApptId = null;
    let cancelPatientNameCache = '';

    function openRescheduleModal(btn) {
        selectedApptId = btn.dataset.id;
        document.getElementById('resPatientName').textContent = btn.dataset.name || '—';
        document.getElementById('resAppointmentDate').textContent = btn.dataset.datetime || '—';

        const modal = document.getElementById('rescheduleModal');
        modal.classList.remove('hidden');

        const panel = modal.querySelector('.reschedule-modal-panel');
        if (panel) {
            panel.style.animation = 'none';
            requestAnimationFrame(() => requestAnimationFrame(() => {
                panel.style.animation = '';
            }));
        }
    }

    function closeRescheduleModal() {
        document.getElementById('rescheduleModal').classList.add('hidden');
        selectedApptId = null;
    }

    function handleRescheduleBackdropClick(e) {
        if (e.target === document.getElementById('rescheduleModal')) {
            closeRescheduleModal();
        }
    }

    function confirmReschedule() {
        if (!selectedApptId) return;
        var url = "{{ route('admin.admin.appointments.reschedule', ['id' => ':id']) }}".replace(':id', selectedApptId);
        window.location.href = url;
    }

    function openStartProcedureModal(btn) {
        selectedApptId = btn.dataset.id;
        document.getElementById('startPatientName').textContent = btn.dataset.name || '—';
        document.getElementById('startAppointmentDate').textContent = btn.dataset.datetime || '—';
        document.getElementById('startProcedureModal').classList.remove('hidden');
    }

    function closeStartProcedureModal() {
        document.getElementById('startProcedureModal').classList.add('hidden');
        selectedApptId = null;
    }

    function handleStartBackdropClick(e) {
        if (e.target === document.getElementById('startProcedureModal')) {
            closeStartProcedureModal();
        }
    }

    function confirmStartProcedure() {
        if (!selectedApptId) return;
        window.location.href = `/admin/appointments/${selectedApptId}/start`;
    }

    function openCancelAppointmentModal(btn) {
        selectedApptId = btn.dataset.id;
        cancelPatientNameCache = btn.dataset.name || 'this patient';

        document.getElementById('cancelPatientName').textContent = btn.dataset.name || '—';
        document.getElementById('cancelAppointmentDate').textContent = btn.dataset.datetime || '—';

        document.querySelectorAll('input[name="cancelReason"]').forEach(r => r.checked = false);
        clearReasonError();

        const confirmBtn = document.getElementById('confirmCancelBtn');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa-solid fa-ban text-xs mr-1.5"></i>Yes, Cancel';
        }

        const modal = document.getElementById('cancelAppointmentModal');
        modal.classList.remove('hidden');

        const panel = modal.querySelector('.cancel-modal-panel');
        if (panel) {
            panel.style.animation = 'none';
            requestAnimationFrame(() => requestAnimationFrame(() => {
                panel.style.animation = '';
            }));
        }
    }

    function closeCancelAppointmentModal() {
        document.getElementById('cancelAppointmentModal').classList.add('hidden');
        selectedApptId = null;
    }

    function handleCancelBackdropClick(e) {
        if (e.target === document.getElementById('cancelAppointmentModal')) {
            closeCancelAppointmentModal();
        }
    }

    function clearReasonError() {
        const chips = document.getElementById('cancelReasonChips');
        const error = document.getElementById('reasonError');

        if (chips) chips.classList.remove('invalid', 'chips-error-shake');
        if (error) error.classList.add('hidden');
    }

    document.querySelectorAll('input[name="cancelReason"]').forEach(r => {
        r.addEventListener('change', clearReasonError);
    });

    function confirmCancelAppointment() {
        var selectedReason = document.querySelector('input[name="cancelReason"]:checked')?.value || null;

        if (!selectedReason) {
            var chips = document.getElementById('cancelReasonChips');
            var error = document.getElementById('reasonError');

            if (error) error.classList.remove('hidden');
            if (chips) {
                chips.classList.add('invalid');
                chips.classList.remove('chips-error-shake');
                void chips.offsetWidth;
                chips.classList.add('chips-error-shake');
            }
            return;
        }

        var btn = document.getElementById('confirmCancelBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-xs mr-1.5"></i>Cancelling…';
        }

        var patientName = cancelPatientNameCache;
        var apptId = selectedApptId;

        closeCancelAppointmentModal();

        fetch(`/admin/appointments/${apptId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                reason: selectedReason
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll(`[data-appt-id="${apptId}"]`).forEach(card => {
                        card.style.transition = 'all 0.35s cubic-bezier(.4,0,.2,1)';
                        card.style.overflow = 'hidden';

                        requestAnimationFrame(() => {
                            card.style.maxHeight = card.offsetHeight + 'px';
                            requestAnimationFrame(() => {
                                card.style.maxHeight = '0';
                                card.style.opacity = '0';
                                card.style.transform = 'scaleY(0.85) translateX(-8px)';
                                card.style.marginBottom = '0';
                                card.style.paddingTop = '0';
                                card.style.paddingBottom = '0';
                            });
                        });

                        setTimeout(() => card.remove(), 380);
                    });

                    showToast({
                        title: 'Appointment Cancelled',
                        message: `${patientName}'s appointment has been successfully cancelled.`,
                        duration: 5000
                    });
                } else {
                    showToast({
                        title: 'Cancel Failed',
                        message: data.message || 'Something went wrong.',
                        duration: 4000
                    });
                }
            })
            .catch(() => {
                showToast({
                    title: 'Network Error',
                    message: 'Could not reach the server.',
                    duration: 4000
                });
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAppointmentViewToggle();

        window.addEventListener('resize', function () {
            applyAppointmentView(getPreferredAppointmentView(), false);
        });
    });
</script>
@endsection