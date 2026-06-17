@extends('layouts.patient')

@section('title', 'Dental Records | PUP Taguig Dental Clinic')

@php
$notifications = collect($notifications ?? []);
$notifCount = $notifications->count();
@endphp

@section('content')

<main id="mainContent" class="patient-page-shell page-enter patient-records-page">
    <div class="w-full">

        @if (isset($upcomingAppointment) && $upcomingAppointment)
        @php
        $uDate = \Carbon\Carbon::parse($upcomingAppointment->appointment_date);
        $uTime = \Carbon\Carbon::parse($upcomingAppointment->appointment_time);
        $isRescheduled = strtolower($upcomingAppointment->status) === 'rescheduled';

        $statusPillCls = $isRescheduled
        ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
        : 'bg-green-100 text-green-800 border-green-200';
        $statusDotCls = $isRescheduled ? 'bg-yellow-500' : 'bg-green-500';
        $statusDarkPill = $isRescheduled
        ? 'dark:bg-yellow-400/20 dark:text-yellow-100 dark:border-yellow-400/30'
        : 'dark:bg-emerald-500/20 dark:text-emerald-100 dark:border-emerald-500/30';
        @endphp
        <div
            class="mb-6 upcoming-card-polished bg-white dark:bg-[#161B22] rounded-[1rem] border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <div class="px-4 sm:px-5 py-4 sm:py-4.5">
                <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto] gap-4 xl:gap-5 items-center">

                    <div class="min-w-0">
                        <div class="flex items-start gap-3">
                            <div class="relative flex-shrink-0 mt-0.5">
                                <div
                                    class="upcoming-tooth-glass w-12 h-12 rounded-[0.95rem] text-white flex items-center justify-center">
                                    <i
                                        class="fa-solid fa-tooth text-[15px] relative z-[1] drop-shadow-[0_1px_2px_rgba(0,0,0,0.22)]"></i>
                                </div>
                                <span
                                    class="upcoming-live-dot absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full {{ $statusDotCls }} border-2 border-white dark:border-[#161B22]"></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="text-lg sm:text-[1.15rem] font-extrabold text-gray-900 dark:text-[#F3F4F6] leading-tight truncate">
                                        {{ $upcomingAppointment->service_type ?? '—' }}
                                    </h3>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $statusPillCls }} {{ $statusDarkPill }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDotCls }}"></span>
                                        {{ ucfirst($upcomingAppointment->status) }}
                                    </span>
                                </div>

                                <div
                                    class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600 dark:text-gray-400">
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
                                        <span class="font-medium truncate">{{ $upcomingAppointment->dentist_name ?? 'Dr.
                                            Nelson P. Angeles' }}</span>
                                    </span>
                                </div>

                                <div class="mt-3 flex items-center gap-3">
                                    <div
                                        class="h-[2px] w-10 rounded-full bg-gradient-to-r from-red-200 to-red-300 dark:from-red-900/50 dark:to-red-800/50">
                                    </div>
                                    <span
                                        class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Upcoming
                                        Visit</span>
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
                                <span class="text-[0.76rem] font-bold text-[#7f1d1d] dark:text-[#FCA5A5]">Please
                                    arrive 10 minutes early</span>
                            </div>

                            <a href="{{ route('patient.appointment.index') }}"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-[0.85rem] bg-[#8B0000] hover:bg-[#660000] text-white text-sm font-bold transition-all duration-300 shadow-sm hover:-translate-y-0.5">
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
                <div class="empty-state-icon">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <p class="empty-state-title">No records yet</p>
                <p class="empty-state-sub">Completed appointment records will appear here after your first dental visit.
                </p>
                <a href="{{ route('patient.book.appointment') }}" class="empty-state-btn">
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