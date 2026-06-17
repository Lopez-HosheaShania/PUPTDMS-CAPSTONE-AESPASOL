@extends('layouts.patient')

@section('title', 'PUP Taguig Dental Clinic | Appointment')

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

<main id="mainContent" class="patient-page-shell page-enter">
    <div id="appointmentPage" class="w-full">

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
        $recommendedHint = $daysUntilRecommended < 0 ? 'Due now' : ($daysUntilRecommended===0 ? 'Due today' : 'In ' .
            $daysUntilRecommended . ' days' ); @endphp <section class="appt-section-reveal mb-5">
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
                                <p
                                    class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-[#8B0000] dark:text-[#FCA5A5]">
                                    Monthly Highlights
                                </p>
                                <h3 class="mt-1 text-lg font-extrabold text-gray-900 dark:text-gray-100">Your Visit
                                    Patterns</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Personalized summary based on
                                    your appointment history.</p>

                                <div class="mt-4 space-y-2.5">
                                    <div class="appt-calendar-side-stat flex items-center justify-between">
                                        <span
                                            class="text-xs font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400">Most
                                            Visited Service</span>
                                        <span class="text-sm font-extrabold text-emerald-700 dark:text-emerald-300">{{
                                            $mostVisitedCount > 0 ? $mostVisitedCount . 'x' : '—' }}</span>
                                    </div>
                                    <p
                                        class="px-1 -mt-1 text-xs font-semibold text-gray-700 dark:text-gray-200 truncate">
                                        {{ $mostVisitedService }}</p>

                                    <div class="appt-calendar-side-stat flex items-center justify-between">
                                        <span
                                            class="text-xs font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400">Last
                                            Completed Visit</span>
                                        <span class="text-xs font-extrabold text-blue-700 dark:text-blue-300">{{
                                            $latestCompletedText }}</span>
                                    </div>

                                    <div class="appt-calendar-side-stat flex items-center justify-between">
                                        <span
                                            class="text-xs font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400">Next
                                            Recommended Checkup</span>
                                        <span class="text-xs font-extrabold text-amber-700 dark:text-amber-300">{{
                                            $nextRecommendedText }}</span>
                                    </div>
                                    <p class="px-1 -mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $recommendedHint
                                        }}</p>
                                </div>
                            </div>

                            <div class="appt-calendar-side-card">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-extrabold text-gray-800 dark:text-gray-200">6-Month Activity
                                    </p>
                                    <div class="flex items-center gap-2 text-[10px] font-bold">
                                        <span
                                            class="inline-flex items-center gap-1 text-emerald-700 dark:text-emerald-300"><i
                                                class="fa-solid fa-circle text-[8px]"></i>Completed</span>
                                        <span
                                            class="inline-flex items-center gap-1 text-orange-700 dark:text-orange-300"><i
                                                class="fa-solid fa-circle text-[8px]"></i>Cancelled</span>
                                    </div>
                                </div>

                                <div class="appt-mini-chart">
                                    <canvas id="apptActivityChart" aria-label="Appointment activity chart"
                                        role="img"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-8">
                        <div id="calendarSkeletonContainer" class="w-full h-full min-h-[420px] skeleton-fade-swap">
                        </div>
                    </div>
                </div>

                <div class="appt-quick-actions">
                    <a href="{{ route('patient.book.appointment') }}" class="appt-quick-btn">
                        <i class="fa-solid fa-calendar-plus"></i>
                        Rebook Appointment
                    </a>
                    <button type="button" class="appt-quick-btn"
                        onclick="apptShowPast(); document.getElementById('apptPastPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });">
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
                        <h2
                            class="text-2xl sm:text-3xl font-extrabold text-[#660000] dark:text-[#ff6b6b] leading-tight">
                            My
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
                    <div
                        class="text-xs font-bold tracking-[0.12em] uppercase text-gray-400 mb-4 flex items-center gap-3">
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
                                <span
                                    class="text-xs font-bold tracking-widest uppercase text-red-700 dark:text-red-400">{{
                                    $apptDate->format('M') }}</span>
                                <span class="text-[11px] text-gray-500">{{ $apptDate->format('Y') }}</span>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5 flex-1 flex flex-col gap-3 min-w-0 justify-center">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-base font-bold text-gray-800 dark:text-gray-200 truncate">{{
                                    $appt->service_type }}{{ $appt->other_services ? ' (' . $appt->other_services . ')'
                                    : ''
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

                                    <h3
                                        class="mt-1 text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-gray-100">
                                        No upcoming visit yet
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                                        You can book your next dental visit now or check the calendar for available
                                        dates.
                                    </p>
                                </div>

                                <div class="appt-timeline-path">
                                    <div class="appt-timeline-step">
                                        <span class="appt-timeline-dot active">
                                            <i class="fa-solid fa-user-check"></i>
                                        </span>
                                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Profile ready
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Your patient account is
                                            ready
                                            for
                                            booking.</p>
                                    </div>

                                    <div class="appt-timeline-step">
                                        <span class="appt-timeline-dot">
                                            <i class="fa-regular fa-calendar-plus"></i>
                                        </span>
                                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Choose a date
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Pick an available schedule
                                            from
                                            the
                                            clinic calendar.</p>
                                    </div>

                                    <div class="appt-timeline-step">
                                        <span class="appt-timeline-dot">
                                            <i class="fa-solid fa-hospital"></i>
                                        </span>
                                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Visit the
                                            clinic
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Your confirmed appointment
                                            will
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
                                        <p class="text-xs font-black uppercase tracking-[0.13em] text-white/70">
                                            Recommended
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
                    <div
                        class="text-xs font-bold tracking-[0.12em] uppercase text-gray-400 mb-4 flex items-center gap-3">
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
                                    $appt->service_type }}{{ $appt->other_services ? ' (' . $appt->other_services . ')'
                                    : ''
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

            @php
            $bookingServiceTypes = \App\Models\ServiceType::query()
            ->activeForBooking()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

            $serviceIconFor = function ($name) {
            $name = strtolower((string) $name);

            return match (true) {
            str_contains($name, 'clean') || str_contains($name, 'prophy') => 'fa-solid fa-broom',
            str_contains($name, 'check') || str_contains($name, 'consult') || str_contains($name, 'oral') => 'fa-solid
            fa-stethoscope',
            str_contains($name, 'restor') || str_contains($name, 'filling') || str_contains($name, 'crown') => 'fa-solid
            fa-tooth',
            str_contains($name, 'surgery') || str_contains($name, 'extraction') || str_contains($name, 'extract') =>
            'fa-solid fa-kit-medical',
            str_contains($name, 'clearance') || str_contains($name, 'certificate') => 'fa-solid fa-file-medical',
            default => 'fa-solid fa-tooth',
            };
            };
            @endphp

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
                    @forelse ($bookingServiceTypes as $service)
                    <div class="service-card">
                        <div class="service-card-icon">
                            <i class="{{ $serviceIconFor($service->name) }}"></i>
                        </div>

                        <h3 class="service-card-title">
                            {{ $service->name }}
                        </h3>

                        <p class="service-card-desc">
                            {{ $service->description ?: 'This dental service is currently available for patient
                            booking.' }}
                        </p>

                        <span class="service-card-tag">
                            Available for Booking
                        </span>
                    </div>
                    @empty
                    <div class="service-card-empty">
                        <div>
                            <div class="appt-empty-icon">
                                <i class="fa-solid fa-tooth text-3xl"></i>
                            </div>

                            <p class="text-lg font-extrabold text-gray-800 dark:text-gray-200 mt-2">
                                No Services Available
                            </p>

                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Active service types added by the admin will appear here.
                            </p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </section>

            <dialog id="activeAppointmentModal" class="modal">
                <div
                    class="modal-box p-8 rounded-[1.5rem] bg-white dark:bg-[#101111] text-center shadow-2xl w-[min(92vw,400px)]">
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