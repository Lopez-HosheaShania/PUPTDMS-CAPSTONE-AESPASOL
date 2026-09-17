@extends('layouts.app')

@section('layout-role', $layoutRole ?? 'admin')

@section('title', $pageTitle ?? 'Appointments')

@section('usesAppointmentCalendar', true)

@section('styles')
    @vite('resources/css/pages/shared/appointments.css')
@endsection

@section('content')

    @php
        use Carbon\Carbon;
        use Illuminate\Support\Str;

        $layoutRole = $layoutRole ?? 'admin';
        $pageTitle = $pageTitle ?? 'Appointments';

        $isDentistView = $isDentistView ?? false;
        $isAdminView = !$isDentistView;

        $pageShellClass = $pageShellClass ?? ($isDentistView ? 'app-page-shell' : 'app-page-shell');

        $patientProfileRouteName =
            $patientProfileRouteName ??
            ($isDentistView ? 'dentist.dentist.patient.profile' : 'admin.admin.patient.profile');

        $canStartProcedure = $canStartProcedure ?? false;
        $canRescheduleAppointment = $canRescheduleAppointment ?? false;
        $canCancelAppointment = $canCancelAppointment ?? false;
        $canViewTreatmentRecord = $canViewTreatmentRecord ?? false;
        $canScheduleFollowUp = $canScheduleFollowUp ?? false;

        $resolveStartState = function ($appointment, bool $isToday): array {
            $appointmentStatus = strtolower(trim((string) ($appointment->status ?? 'upcoming')));
            $isActiveAppointment = in_array(
                $appointmentStatus,
                ['pending', 'confirmed', 'upcoming', 'reschedule', 'rescheduled'],
                true,
            );

            if (!$isActiveAppointment) {
                return [false, 'Only upcoming or rescheduled appointments can be started'];
            }

            if (!$isToday) {
                return [false, 'Start procedure is available on the appointment date only'];
            }

            if (!$appointment->reserved_booking_period_id) {
                return [true, 'Start procedure'];
            }

            if ($appointment->reservedProcedureWindowIsOpen()) {
                return [true, 'Start reserved procedure'];
            }

            $period = $appointment->reservedBookingPeriod;
            $window = $period
                ? Carbon::parse($period->start_time)->format('g:i A') .
                    ' to ' .
                    Carbon::parse($period->end_time)->format('g:i A')
                : 'the reserved period';

            return [false, 'Start is available during ' . $window];
        };

        $upcomingAppointments = collect($upcomingAppointments ?? []);
        $pastAppointments = collect($pastAppointments ?? []);
        $today = $today ?? Carbon::today()->toDateString();
        $todayAppts = $upcomingAppointments->filter(fn($a) => ($a->appointment_date ?? null) === $today);
        $todayCount = $todayAppts->count();
        $firstTodayAppt = $todayAppts
            ->sortBy(fn($a) => ($a->appointment_date ?? '') . ' ' . ($a->appointment_time ?? '23:59:59'))
            ->first();

        $firstTodayName = $firstTodayAppt ? optional($firstTodayAppt->patient)->name ?? 'Unknown Patient' : null;

        $firstTodayTime =
            $firstTodayAppt && $firstTodayAppt->appointment_time
                ? \Carbon\Carbon::parse($firstTodayAppt->appointment_time)->format('g:i A')
                : null;

        $firstTodayService = $firstTodayAppt
            ? (($firstTodayAppt->service_type_name ?? '') === 'Others'
                ? ($firstTodayAppt->other_services ?:
                'Others')
                : $firstTodayAppt->service_type_name ?? 'Appointment')
            : null;

        $nextAppt = $upcomingAppointments
            ->sortBy(fn($a) => ($a->appointment_date ?? '') . ' ' . ($a->appointment_time ?? '23:59:59'))
            ->first();

        $nextName = $nextAppt ? optional($nextAppt->patient)->name ?? 'Unknown Patient' : null;
        $nextPatientImage =
            $nextAppt && optional($nextAppt->patient)->profile_image
                ? asset('storage/' . optional($nextAppt->patient)->profile_image)
                : '';

        $nextTime =
            $nextAppt && $nextAppt->appointment_time
                ? \Carbon\Carbon::parse($nextAppt->appointment_time)->format('g:i A')
                : null;

        $nextDate = $nextAppt ? \Carbon\Carbon::parse($nextAppt->appointment_date)->format('F j, Y') : null;

        $nextService = $nextAppt
            ? (($nextAppt->service_type_name ?? '') === 'Others'
                ? ($nextAppt->other_services ?:
                'Others')
                : $nextAppt->service_type_name ?? 'Appointment')
            : null;

        $nextIsToday = $nextAppt && ($nextAppt->appointment_date ?? null) === $today;

        $upcomingTotal = $upcomingAppointments->count();
        $pastTotal = $pastAppointments->count();

        $normalizeAppointmentStatus = function ($status) {
            $normalized = strtolower(trim((string) ($status ?? '')));

            return match ($normalized) {
                'pending', 'confirmed', 'upcoming' => 'upcoming',
                'reschedule', 'rescheduled' => 'rescheduled',
                'canceled', 'cancelled' => 'cancelled',
                'completed' => 'completed',
                default => $normalized ?: 'upcoming',
            };
        };

        $allAppointments = $upcomingAppointments
            ->merge($pastAppointments)
            ->unique('id')
            ->sort(function ($a, $b) use ($normalizeAppointmentStatus) {
                $aStatus = $normalizeAppointmentStatus($a->status ?? 'upcoming');

                $bStatus = $normalizeAppointmentStatus($b->status ?? 'upcoming');

                $aIsActive = in_array($aStatus, ['upcoming', 'rescheduled'], true);

                $bIsActive = in_array($bStatus, ['upcoming', 'rescheduled'], true);

                if ($aIsActive !== $bIsActive) {
                    return $aIsActive ? -1 : 1;
                }

                $aDateTime = Carbon::parse(
                    ($a->appointment_date ?? '1970-01-01') . ' ' . ($a->appointment_time ?? '00:00:00'),
                );

                $bDateTime = Carbon::parse(
                    ($b->appointment_date ?? '1970-01-01') . ' ' . ($b->appointment_time ?? '00:00:00'),
                );

                if ($aIsActive && $bIsActive) {
                    return $aDateTime <=> $bDateTime;
                }

                return $bDateTime <=> $aDateTime;
            })
            ->values();

        $appointmentsGrouped = $allAppointments->groupBy(function ($appt) {
            return \Carbon\Carbon::parse($appt->appointment_date)->format('Y-m');
        });
        $appointmentRefreshItems = $allAppointments
            ->map(
                fn($appointment) => [
                    'id' => $appointment->id,
                    'updated_at' => optional($appointment->updated_at)->toISOString(),
                ],
            )
            ->values();

        $statusCounts = [
            'all' => $allAppointments->count(),
            'upcoming' => $upcomingAppointments
                ->filter(fn($a) => $normalizeAppointmentStatus($a->status ?? 'upcoming') === 'upcoming')
                ->count(),
            'rescheduled' => $upcomingAppointments
                ->filter(fn($a) => $normalizeAppointmentStatus($a->status ?? '') === 'rescheduled')
                ->count(),
            'completed' => $pastAppointments
                ->filter(fn($a) => $normalizeAppointmentStatus($a->status ?? '') === 'completed')
                ->count(),
            'cancelled' => $pastAppointments
                ->filter(fn($a) => $normalizeAppointmentStatus($a->status ?? '') === 'cancelled')
                ->count(),
        ];

        $appointmentStatusOptions = [
            [
                'value' => 'all',
                'label' => 'All statuses',
                'icon' => 'fa-layer-group',
                'tone' => 'status-all',
                'count' => $statusCounts['all'] ?? 0,
            ],
            [
                'value' => 'upcoming',
                'label' => 'Upcoming',
                'icon' => 'fa-calendar-check',
                'tone' => 'status-upcoming',
                'count' => $statusCounts['upcoming'] ?? 0,
            ],
            [
                'value' => 'rescheduled',
                'label' => 'Rescheduled',
                'icon' => 'fa-rotate-right',
                'tone' => 'status-rescheduled',
                'count' => $statusCounts['rescheduled'] ?? 0,
            ],
            [
                'value' => 'completed',
                'label' => 'Completed',
                'icon' => 'fa-circle-check',
                'tone' => 'status-completed',
                'count' => $statusCounts['completed'] ?? 0,
            ],
            [
                'value' => 'cancelled',
                'label' => 'Cancelled',
                'icon' => 'fa-circle-xmark',
                'tone' => 'status-cancelled',
                'count' => $statusCounts['cancelled'] ?? 0,
            ],
        ];

        $notifications = collect($notifications ?? []);
        $notifCount = $notifications->count();
    @endphp

    <main id="mainContent"
        class="{{ $pageShellClass }}
           shared-appointments-page
           {{ $isDentistView ? 'dentist-appointments-view' : 'admin-appointments-view' }}
           page-enter
           mode-list">
        <div class="w-full">

            <div class="appointment-header-wrap">

                @if ($isDentistView)
                    <div class="dentist-hero">
                        <div class="dentist-hero-content">
                            <div class="dentist-hero-icon">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>

                            <div class="min-w-0">
                                <div class="dentist-hero-eyebrow">
                                    <i class="fa-solid fa-tooth"></i>
                                    Appointment Management
                                </div>

                                <h2 class="dentist-hero-title">
                                    Appointments
                                </h2>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="page-banner admin-appointment-banner">
                        <div class="page-banner-inner">
                            <div class="appointment-banner-title-wrap">
                                <h1 class="page-title">
                                    Appointment Management
                                </h1>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="today-snapshot-card">

                    <div class="today-snapshot-top">
                        <div>
                            <span class="today-snapshot-kicker">
                                Today’s Snapshot
                            </span>

                            <p class="today-snapshot-caption">
                                Quick overview of today’s clinic schedule
                            </p>
                        </div>

                        <span class="today-snapshot-date">
                            <i class="fa-regular fa-calendar"></i>
                            {{ Carbon::parse($today)->format('F j, Y') }}
                        </span>
                    </div>

                    <div class="today-snapshot-content">

                        <div class="today-snapshot-primary">
                            <div class="today-snapshot-primary-icon">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>

                            <div class="today-snapshot-primary-copy">
                                <span class="today-snapshot-label">
                                    Today
                                </span>

                                @if ($firstTodayAppt)
                                    <div class="today-snapshot-count">
                                        {{ $todayCount }}
                                    </div>

                                    <div class="today-snapshot-count-copy">
                                        {{ Str::plural('appointment', $todayCount) }}
                                        scheduled today
                                    </div>

                                    <div class="today-snapshot-first">
                                        First visit at
                                        <strong>
                                            {{ $firstTodayTime }}
                                        </strong>
                                    </div>
                                @else
                                    <div class="today-snapshot-empty-title">
                                        No appointments today
                                    </div>

                                    <div class="today-snapshot-first">
                                        Your schedule is clear for today.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="today-snapshot-divider"></div>

                        <div class="today-snapshot-next-block">
                            <div class="today-snapshot-next-heading">
                                <span class="today-snapshot-label">
                                    Next Appointment
                                </span>

                                @if ($nextIsToday)
                                    <span class="status-pill status-today">
                                        <span class="status-dot"></span>
                                        Today
                                    </span>
                                @endif
                            </div>

                            @if ($nextAppt)
                                <div class="today-snapshot-next-main">
                                    <span class="patient-avatar patient-avatar-md" data-patient-avatar
                                        data-patient-name="{{ $nextName }}"
                                        data-patient-url="{{ $nextPatientImage }}"></span>

                                    <div class="today-snapshot-next-copy">
                                        <strong class="today-snapshot-next-name" data-patient-name>
                                            {{ $nextName }}
                                        </strong>

                                        <div class="today-snapshot-next-meta">
                                            <span>
                                                <i class="fa-regular fa-calendar"></i>
                                                {{ $nextDate }}
                                            </span>

                                            @if ($nextTime)
                                                <span>
                                                    <i class="fa-regular fa-clock"></i>
                                                    {{ $nextTime }}
                                                </span>
                                            @endif
                                        </div>

                                        <span class="today-snapshot-service">
                                            <i class="fa-solid fa-tooth"></i>
                                            {{ $nextService }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <div class="today-snapshot-next-empty">
                                    <i class="fa-regular fa-calendar-check"></i>

                                    <span>
                                        No upcoming appointments
                                    </span>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                <div class="appointment-controls-bar">

                    <div class="appointment-filter-wrap">
                        <div class="appointment-search-row voice-search-row">
                            <x-search-bar id="apptSearchInput" placeholder="Search patient"
                                callback="handleAppointmentSearch" :debounce="250" class="flex-1" />

                            <x-voice-input target="#apptSearchInput" status-id="apptVoiceStatus"
                                label="Voice search appointments" title="Voice search" />
                        </div>
                    </div>

                    <div class="appointment-controls-actions">

                        <x-filter-select id="appointmentStatusFilter" name="appointment_status" label="Status"
                            value="" :options="$appointmentStatusOptions" callback="handleAppointmentStatusSelect" searchable multiple
                            search-placeholder="Search status..." />

                        <div class="appointment-filter-actions">
                            <button id="appointmentFilterBtn" type="button" class="global-filter-btn"
                                aria-label="Filter appointments" data-tooltip="Filter" data-tooltip-tone="neutral">
                                <i class="fa-solid fa-sliders"></i>

                                <span>Filter</span>

                                <span id="appointmentFilterBadge" class="filter-badge" style="display:none;"></span>
                            </button>
                        </div>

                        <x-view-toggle id="appointmentsViewToggle" root="#mainContent" storage-key="appointmentsViewMode"
                            list-label="List" grid-label="Grid" />

                        <button id="appointmentClearFilterBtn" type="button" onclick="resetAppointmentFilters()"
                            class="global-filter-reset-btn hidden" aria-label="Reset filters" data-tooltip="Reset filters"
                            data-tooltip-tone="neutral">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>

                    </div>
                </div>
            </div>
        </div>

        <section id="appointmentsSection">
            @forelse($appointmentsGrouped as $monthKey => $items)

                @php
                    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m-d', $monthKey . '-01')->format('F Y');
                @endphp

                <details class="appt-month-group" data-month="{{ $monthKey }}" data-show-more data-show-more-step="7"
                    data-show-more-label="appointments" open>
                    <summary class="appt-month-summary">
                        <span class="appt-month-left">
                            <span class="timeline-dot"></span>

                            <span class="appt-month-title">
                                {{ $monthLabel }}
                            </span>

                            <span class="global-info-group">
                                <span class="appt-type-icon" aria-hidden="true">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </span>

                                <span class="global-info-subvalue" data-month-count data-show-more-count
                                    data-show-more-count-label="appointments" data-show-more-count-singular="appointment">

                                    {{ $items->count() }}
                                    {{ Str::plural('appointment', $items->count()) }}
                                </span>
                            </span>
                        </span>

                        <i class="fa-solid fa-chevron-down appt-month-chevron"></i>
                    </summary>

                    <div class="appt-month-body">

                        <div class="desktop-appointments-table relative pl-10">

                            <div class="appointment-timeline-line"></div>

                            <div
                                class="table-list-header
                               appt-table-head
                               appointment-table-grid
                               grid gap-4 py-3 px-5
                               rounded-t-2xl mb-3">
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar text-[10px]"></i>
                                    Date
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-clock text-[10px]"></i>
                                    Time
                                </div>

                                <div class="appt-service-heading">
                                    Service
                                </div>

                                <div class="appt-patient-heading">
                                    Patient
                                </div>

                                <div class="appt-program-heading">
                                    Program
                                </div>

                                <div class="appt-status-heading">
                                    Status
                                </div>

                                <div class="appt-actions-heading">
                                    Actions
                                </div>
                            </div>

                            <div class="space-y-2.5" data-month-list data-show-more-list data-show-more-reference="true">
                                @foreach ($items as $i => $appt)
                                    @php
                                        $patientName = optional($appt->patient)->name ?? 'Unknown Patient';

                                        $patient = $appt->patient;

                                        $profilePatientId = optional($appt->patient)->id ?? ($appt->patient_id ?? null);

                                        $profileUrl = $profilePatientId
                                            ? route($patientProfileRouteName, ['patient' => $profilePatientId])
                                            : null;

                                        if ($isDentistView && $profilePatientId) {
                                            $profileUrl =
                                                route($patientProfileRouteName, $profilePatientId) .
                                                '?from=appointments';
                                        }

                                        $isWalkInAppointment = (bool) ($appt->is_walk_in ?? false);

                                        $isFollowUpAppointment = (bool) ($appt->is_follow_up ?? false);

                                        $studentNumber = filled($patient?->student_no)
                                            ? $patient->student_no
                                            : (filled($patient?->faculty_code)
                                                ? 'Faculty: ' . $patient->faculty_code
                                                : 'No identity number');

                                        $courseCode = trim((string) ($patient?->course_code ?? ''));

                                        $courseName = trim((string) ($patient?->course_name ?? ''));

                                        $isFacultyPatient = filled($patient?->faculty_code);

                                        if ($isFacultyPatient) {
                                            $program = 'Faculty';
                                            $programFull = 'Faculty';
                                        } else {
                                            $program =
                                                $courseCode !== ''
                                                    ? $courseCode
                                                    : ($courseName !== ''
                                                        ? $courseName
                                                        : 'No program');

                                            $programFull = collect([
                                                $courseCode,
                                                $courseName !== $courseCode ? $courseName : null,
                                            ])
                                                ->filter()
                                                ->implode(' — ');

                                            if ($programFull === '') {
                                                $programFull = 'No program';
                                            }
                                        }

                                        $dateLabel = \Carbon\Carbon::parse($appt->appointment_date)->format('F j, Y');

                                        $mobileDateLabel = \Carbon\Carbon::parse($appt->appointment_date)->format(
                                            'F j, Y',
                                        );

                                        $weekday = \Carbon\Carbon::parse($appt->appointment_date)->format('l');

                                        $timeLabel = $appt->appointment_time
                                            ? \Carbon\Carbon::parse($appt->appointment_time)->format('g:i A')
                                            : '—';

                                        $serviceLabel =
                                            ($appt->service_type_name ?? '') === 'Others'
                                                ? ($appt->other_services ?:
                                                'Others')
                                                : $appt->service_type_name ?? '—';

                                        $serviceLower = strtolower($serviceLabel);

                                        $badgeClass = 'service-badge-default';

                                        if (str_contains($serviceLower, 'surgery')) {
                                            $badgeClass = 'service-badge-surgery';
                                        } elseif (str_contains($serviceLower, 'check')) {
                                            $badgeClass = 'service-badge-checkup';
                                        } elseif (str_contains($serviceLower, 'whiten')) {
                                            $badgeClass = 'service-badge-whitening';
                                        } elseif (str_contains($serviceLower, 'extrac')) {
                                            $badgeClass = 'service-badge-extraction';
                                        }

                                        $isToday = ($appt->appointment_date ?? null) === $today;

                                        [$canStartThisAppointment, $startTooltip] = $resolveStartState($appt, $isToday);

                                        $rawStatus = strtolower(trim((string) ($appt->status ?? 'upcoming')));

                                        $normalizedStatus = match ($rawStatus) {
                                            'pending', 'confirmed', 'upcoming' => 'upcoming',

                                            'reschedule', 'rescheduled' => 'rescheduled',

                                            'canceled', 'cancelled' => 'cancelled',

                                            'completed' => 'completed',

                                            default => 'upcoming',
                                        };

                                        $statusMap = [
                                            'upcoming' => [
                                                'label' => 'Upcoming',
                                                'class' => 'status-upcoming',
                                            ],

                                            'rescheduled' => [
                                                'label' => 'Rescheduled',
                                                'class' => 'status-rescheduled',
                                            ],

                                            'completed' => [
                                                'label' => 'Completed',
                                                'class' => 'status-completed',
                                            ],

                                            'cancelled' => [
                                                'label' => 'Cancelled',
                                                'class' => 'status-cancelled',
                                            ],
                                        ];

                                        $statusMeta = $statusMap[$normalizedStatus] ?? $statusMap['upcoming'];

                                        $isActiveAppointment = in_array(
                                            $normalizedStatus,
                                            ['upcoming', 'rescheduled'],
                                            true,
                                        );

                                        $shouldHighlightToday =
                                            $isToday &&
                                            ($isActiveAppointment ||
                                                ($isFollowUpAppointment &&
                                                    !in_array($normalizedStatus, ['completed', 'cancelled'], true)));

                                        $daysUntilAppointment =
                                            $isActiveAppointment && !$isToday
                                                ? (int) Carbon::today()->diffInDays(
                                                    Carbon::parse($appt->appointment_date)->startOfDay(),
                                                    false,
                                                )
                                                : null;

                                        $appointmentUrgencyClass =
                                            $normalizedStatus === 'rescheduled'
                                                ? 'status-rescheduled'
                                                : ($daysUntilAppointment === 1
                                                    ? 'urgency-tomorrow'
                                                    : 'urgency-upcoming');

                                        $period = in_array($normalizedStatus, ['completed', 'cancelled'], true)
                                            ? 'past'
                                            : 'upcoming';

                                        $modalDatetime =
                                            \Carbon\Carbon::parse($appt->appointment_date)->format('l, F j, Y') .
                                            ' • ' .
                                            $timeLabel;

                                        $recordProcedure = $appt->procedure;

                                        $recordFollowUp = $appt->followUpAppointments
                                            ?->sortBy(function ($followUpAppt) {
                                                return sprintf(
                                                    '%s %s',
                                                    $followUpAppt->appointment_date ?? '',
                                                    $followUpAppt->appointment_time ?? '',
                                                );
                                            })
                                            ?->first();

                                        $recordDuration = $recordProcedure?->procedure_duration_seconds;

                                        $recordRemarks =
                                            $recordProcedure?->completion_action ??
                                            ($appt->remarks ?? ($appt->treatment_notes ?? ($appt->notes ?? '')));

                                        $recordOral = $recordProcedure?->oral_examination ?? '';

                                        $recordDiagnosis = $recordProcedure?->diagnosis ?? '';

                                        $recordPrescription = $recordProcedure?->prescriptions ?? '';

                                        $recordFollowUpPayload = $recordFollowUp
                                            ? [
                                                'date' => $recordFollowUp->appointment_date
                                                    ? \Carbon\Carbon::parse($recordFollowUp->appointment_date)->format(
                                                        'F j, Y',
                                                    )
                                                    : 'N/A',

                                                'time' => $recordFollowUp->appointment_time
                                                    ? \Carbon\Carbon::parse($recordFollowUp->appointment_time)->format(
                                                        'g:i A',
                                                    )
                                                    : 'N/A',

                                                'service' => $recordFollowUp->service_type_name ?? 'Follow-up',

                                                'status' => $recordFollowUp->status ?? 'upcoming',

                                                'reason' => $recordFollowUp->follow_up_reason,
                                            ]
                                            : null;

                                        $recordOdontogramData = $recordProcedure?->odontogram_data ?? [];
                                    @endphp

                                    <div class="appt-card {{ $shouldHighlightToday ? 'is-today' : '' }}"
                                        data-appt-id="{{ $appt->id }}" data-period="{{ $period }}"
                                        data-date="{{ $appt->appointment_date }}"
                                        data-time="{{ $appt->appointment_time ?? '' }}"
                                        data-patient="{{ strtolower($patientName) }}"
                                        data-student-no="{{ strtolower($studentNumber) }}"
                                        data-program="{{ strtolower($programFull) }}"
                                        data-service="{{ strtolower($serviceLabel) }}"
                                        data-patient-id="{{ strtolower((string) ($appt->patient_id ?? '')) }}"
                                        data-status="{{ $normalizedStatus }}"
                                        data-follow-up="{{ $isFollowUpAppointment ? '1' : '0' }}" data-show-more-item
                                        style="animation-delay:{{ $i * 0.04 }}s">
                                        <div
                                            class="appointment-table-grid
                                           grid gap-4 items-center
                                           px-5 py-3.5">

                                            <div class="appt-row-date">
                                                <p class="date-main">
                                                    {{ $dateLabel }}
                                                </p>

                                                <p class="date-sub">
                                                    {{ $weekday }}
                                                </p>
                                            </div>

                                            <div>
                                                <span class="time-chip">
                                                    <i class="fa-regular fa-clock text-xs"></i>
                                                    {{ $timeLabel }}
                                                </span>
                                            </div>

                                            <div class="appt-service-cell flex items-center justify-start">
                                                <span class="service-badge  {{ $badgeClass }}">
                                                    {{ $serviceLabel }}
                                                </span>
                                            </div>

                                            <div class="appt-patient-cell flex items-center justify-start gap-3">

                                                @php
                                                    $patientImage = optional($appt->patient)->profile_image
                                                        ? asset('storage/' . optional($appt->patient)->profile_image)
                                                        : '';
                                                @endphp

                                                <span class="patient-avatar patient-avatar-md" data-patient-avatar
                                                    data-patient-name="{{ $patientName }}"
                                                    data-patient-url="{{ $patientImage }}"></span>

                                                <div class="text-left min-w-0">

                                                    <div class="appt-patient-name-row">

                                                        <p class="appt-patient-name" data-patient-name>
                                                            {{ $patientName }}
                                                        </p>

                                                        @if ($isWalkInAppointment)
                                                            <span class="appt-type-icon"
                                                                data-tooltip="Walk-in appointment"
                                                                data-tooltip-tone="neutral"
                                                                aria-label="Walk-in appointment" tabindex="0">
                                                                <i class="fa-solid fa-person-walking"></i>
                                                            </span>
                                                        @endif

                                                        @if ($isFollowUpAppointment)
                                                            <span class="appt-type-icon"
                                                                data-tooltip="Follow-up appointment"
                                                                data-tooltip-tone="neutral"
                                                                aria-label="Follow-up appointment" tabindex="0">
                                                                <i class="fa-solid fa-calendar-plus"></i>
                                                            </span>
                                                        @endif

                                                        @if ($isDentistView && $appt->reserved_booking_period_id)
                                                            <span class="status-pill status-today"
                                                                data-tooltip="Reserved: {{ $appt->reservedBookingPeriod?->title }}"
                                                                data-tooltip-tone="neutral"
                                                                aria-label="Reserved appointment" tabindex="0">
                                                                <i class="fa-solid fa-calendar-check"></i>
                                                                <span>Reserved</span>
                                                            </span>
                                                        @endif

                                                    </div>

                                                    <div class="global-info-group">
                                                        <span class="global-info-pill">
                                                            <i class="fa-regular fa-id-card"></i>
                                                            {{ $studentNumber }}
                                                        </span>

                                                        <span class="global-info-pill appt-program-mobile"
                                                            title="{{ $programFull }}">
                                                            <i class="fa-solid fa-graduation-cap"></i>
                                                            {{ $program }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="appt-program-cell">
                                                <span class="global-info-pill " title="{{ $programFull }}">
                                                    <i class="fa-solid fa-graduation-cap"></i>
                                                    {{ $program }}
                                                </span>
                                            </div>

                                            <div class="appt-status-cell text-left">

                                                <div class="flex flex-col items-start gap-1">

                                                    @if ($shouldHighlightToday)
                                                        <span class="status-pill status-today">
                                                            <span class="status-dot"></span>
                                                            Today
                                                        </span>
                                                    @else
                                                        <span class="status-pill {{ $statusMeta['class'] }}">
                                                            <span class="status-dot"></span>
                                                            {{ $statusMeta['label'] }}
                                                        </span>

                                                        @if ($isActiveAppointment && $daysUntilAppointment !== null && $daysUntilAppointment > 0)
                                                            <span class="urgency-chip {{ $appointmentUrgencyClass }}">
                                                                {{ $daysUntilAppointment === 1 ? 'Tomorrow' : 'In ' . $daysUntilAppointment . ' days' }}
                                                            </span>
                                                        @endif
                                                    @endif

                                                </div>

                                            </div>

                                            <div class="appt-actions-wrap ui-action-group">

                                                @if (!$isActiveAppointment && $canViewTreatmentRecord)
                                                    <button type="button" class="ui-action-btn ui-action-neutral"
                                                        data-tooltip="View details" onclick="openRecordModal(this)"
                                                        data-appt-id="{{ $appt->id }}"
                                                        data-service="{{ $serviceLabel }}"
                                                        data-date="{{ $dateLabel }}" data-time="{{ $timeLabel }}"
                                                        data-status="{{ $statusMeta['label'] }}"
                                                        data-duration-seconds="{{ $recordDuration }}"
                                                        data-remarks="{{ $recordRemarks }}"
                                                        data-oral="{{ $recordOral }}"
                                                        data-diagnosis="{{ $recordDiagnosis }}"
                                                        data-prescription="{{ $recordPrescription }}"
                                                        data-follow-up='@json($recordFollowUpPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                                                        data-odontogram-data='@json($recordOdontogramData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                                                        <i class="fa-regular fa-eye"></i>
                                                    </button>
                                                @endif

                                                @if ($profileUrl)
                                                    <a href="{{ $profileUrl }}" class="ui-action-btn ui-action-view"
                                                        data-tooltip="View profile" data-tooltip-tone="view">
                                                        <i class="fa-regular fa-user"></i>
                                                    </a>
                                                @else
                                                    <button type="button" class="ui-action-btn ui-action-view" disabled
                                                        data-tooltip="No patient profile">
                                                        <i class="fa-regular fa-user"></i>
                                                    </button>
                                                @endif

                                                @if ($canStartProcedure)
                                                    <button type="button"
                                                        class="ui-action-btn
                                                           ui-action-success
                                                           {{ $canStartThisAppointment ? '' : 'is-start-locked' }}"
                                                        data-tooltip="{{ $startTooltip }}"
                                                        data-tooltip-tone="{{ $canStartThisAppointment ? 'start' : 'locked' }}"
                                                        data-start-locked="{{ $canStartThisAppointment ? '0' : '1' }}"
                                                        aria-disabled="{{ $canStartThisAppointment ? 'false' : 'true' }}"
                                                        {{ $canStartThisAppointment ? '' : 'disabled' }}
                                                        onclick="openStartProcedureModal(this)"
                                                        data-id="{{ $appt->id }}" data-name="{{ $patientName }}"
                                                        data-datetime="{{ $modalDatetime }}"
                                                        data-service="{{ $serviceLabel }}"
                                                        data-start-url="{{ route('dentist.odontogram', ['appointment' => $appt->id]) }}?from=appointments&start_procedure=1">
                                                        <i class="fa-solid fa-play"></i>
                                                    </button>
                                                @endif

                                                @if ($canRescheduleAppointment && !$appt->reserved_booking_period_id)
                                                    <button type="button"
                                                        class="ui-action-btn ui-action-warning {{ $isActiveAppointment ? '' : 'is-start-locked' }}"
                                                        data-tooltip="{{ $isActiveAppointment ? 'Reschedule appointment' : 'Only upcoming or rescheduled appointments can be changed' }}"
                                                        data-tooltip-tone="{{ $isActiveAppointment ? 'reschedule' : 'locked' }}"
                                                        aria-label="Reschedule appointment"
                                                        {{ $isActiveAppointment ? '' : 'disabled aria-disabled=true' }}
                                                        onclick="@if ($isActiveAppointment) openRescheduleModal({
                                                        id: '{{ $appt->id }}',
                                                        name: @js($patientName),
                                                        datetime: @js($modalDatetime),
                                                        serviceType: @js($appt->service_type_name),
                                                        updateUrl: '{{ route('dentist.dentist.appointments.reschedule.update', $appt->id) }}'
                                                    }) @endif">
                                                        <i class="fa-solid fa-rotate-right"></i>
                                                    </button>
                                                @endif

                                                @if ($canCancelAppointment)
                                                    <button type="button"
                                                        class="ui-action-btn ui-action-delete {{ $isActiveAppointment ? '' : 'is-start-locked' }}"
                                                        data-tooltip="{{ $isActiveAppointment ? 'Cancel appointment' : 'Only upcoming or rescheduled appointments can be cancelled' }}"
                                                        data-tooltip-tone="{{ $isActiveAppointment ? 'cancel' : 'locked' }}"
                                                        aria-label="Cancel appointment"
                                                        {{ $isActiveAppointment ? '' : 'disabled aria-disabled=true' }}
                                                        onclick="@if ($isActiveAppointment) cancelAppointmentFromModal(
                                                        '{{ route('dentist.dentist.appointments.cancel', $appt->id) }}',
                                                        @js($patientName),
                                                        @js($dateLabel . ' | ' . $timeLabel)
                                                    ) @endif">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mobile-appointments-list" data-month-grid data-show-more-list>
                            @foreach ($items as $i => $appt)
                                @php
                                    $patientName = optional($appt->patient)->name ?? 'Unknown Patient';

                                    $patient = $appt->patient;

                                    $profilePatientId = optional($appt->patient)->id ?? ($appt->patient_id ?? null);

                                    $profileUrl = $profilePatientId
                                        ? route($patientProfileRouteName, ['patient' => $profilePatientId])
                                        : null;

                                    if ($isDentistView && $profilePatientId) {
                                        $profileUrl =
                                            route($patientProfileRouteName, $profilePatientId) . '?from=appointments';
                                    }

                                    $isWalkInAppointment = (bool) ($appt->is_walk_in ?? false);

                                    $isFollowUpAppointment = (bool) ($appt->is_follow_up ?? false);

                                    $studentNumber = filled($patient?->student_no)
                                        ? $patient->student_no
                                        : (filled($patient?->faculty_code)
                                            ? 'Faculty: ' . $patient->faculty_code
                                            : 'No identity number');

                                    $courseCode = trim((string) ($patient?->course_code ?? ''));

                                    $courseName = trim((string) ($patient?->course_name ?? ''));

                                    $isFacultyPatient = filled($patient?->faculty_code);

                                    if ($isFacultyPatient) {
                                        $program = 'Faculty';
                                        $programFull = 'Faculty';
                                    } else {
                                        $program =
                                            $courseCode !== ''
                                                ? $courseCode
                                                : ($courseName !== ''
                                                    ? $courseName
                                                    : 'No program');

                                        $programFull = collect([
                                            $courseCode,
                                            $courseName !== $courseCode ? $courseName : null,
                                        ])
                                            ->filter()
                                            ->implode(' — ');

                                        if ($programFull === '') {
                                            $programFull = 'No program';
                                        }
                                    }

                                    $dateLabel = \Carbon\Carbon::parse($appt->appointment_date)->format('F j, Y');

                                    $weekday = \Carbon\Carbon::parse($appt->appointment_date)->format('l');

                                    $timeLabel = $appt->appointment_time
                                        ? \Carbon\Carbon::parse($appt->appointment_time)->format('g:i A')
                                        : '—';

                                    $serviceLabel =
                                        ($appt->service_type_name ?? '') === 'Others'
                                            ? ($appt->other_services ?:
                                            'Others')
                                            : $appt->service_type_name ?? '—';

                                    $serviceLower = strtolower($serviceLabel);

                                    $badgeClass = 'service-badge-default';

                                    if (str_contains($serviceLower, 'surgery')) {
                                        $badgeClass = 'service-badge-surgery';
                                    } elseif (str_contains($serviceLower, 'check')) {
                                        $badgeClass = 'service-badge-checkup';
                                    } elseif (str_contains($serviceLower, 'whiten')) {
                                        $badgeClass = 'service-badge-whitening';
                                    } elseif (str_contains($serviceLower, 'extrac')) {
                                        $badgeClass = 'service-badge-extraction';
                                    }

                                    $isToday = ($appt->appointment_date ?? null) === $today;

                                    [$canStartThisAppointment, $startTooltip] = $resolveStartState($appt, $isToday);

                                    $rawStatus = strtolower(trim((string) ($appt->status ?? 'upcoming')));

                                    $normalizedStatus = match ($rawStatus) {
                                        'pending', 'confirmed', 'upcoming' => 'upcoming',

                                        'reschedule', 'rescheduled' => 'rescheduled',

                                        'canceled', 'cancelled' => 'cancelled',

                                        'completed' => 'completed',

                                        default => 'upcoming',
                                    };

                                    $statusMap = [
                                        'upcoming' => [
                                            'label' => 'Upcoming',
                                            'class' => 'status-upcoming',
                                        ],
                                        'rescheduled' => [
                                            'label' => 'Rescheduled',
                                            'class' => 'status-rescheduled',
                                        ],
                                        'completed' => [
                                            'label' => 'Completed',
                                            'class' => 'status-completed',
                                        ],
                                        'cancelled' => [
                                            'label' => 'Cancelled',
                                            'class' => 'status-cancelled',
                                        ],
                                    ];

                                    $statusMeta = $statusMap[$normalizedStatus] ?? $statusMap['upcoming'];

                                    $isActiveAppointment = in_array(
                                        $normalizedStatus,
                                        ['upcoming', 'rescheduled'],
                                        true,
                                    );

                                    $shouldHighlightToday =
                                        $isToday &&
                                        ($isActiveAppointment ||
                                            ($isFollowUpAppointment &&
                                                !in_array($normalizedStatus, ['completed', 'cancelled'], true)));

                                    $daysUntilAppointment =
                                        $isActiveAppointment && !$isToday
                                            ? (int) Carbon::today()->diffInDays(
                                                Carbon::parse($appt->appointment_date)->startOfDay(),
                                                false,
                                            )
                                            : null;

                                    $appointmentUrgencyClass =
                                        $normalizedStatus === 'rescheduled'
                                            ? 'status-rescheduled'
                                            : ($daysUntilAppointment === 1
                                                ? 'urgency-tomorrow'
                                                : 'urgency-upcoming');

                                    $period = in_array($normalizedStatus, ['completed', 'cancelled'], true)
                                        ? 'past'
                                        : 'upcoming';

                                    $modalDatetime =
                                        \Carbon\Carbon::parse($appt->appointment_date)->format('l, F j, Y') .
                                        ' • ' .
                                        $timeLabel;

                                    $recordProcedure = $appt->procedure;

                                    $recordOdontogramData = $recordProcedure?->odontogram_data ?? [];

                                    $recordFollowUp = $appt->followUpAppointments
                                        ?->sortBy('appointment_date')
                                        ?->first();

                                    $recordFollowUpPayload = $recordFollowUp
                                        ? [
                                            'date' => $recordFollowUp->appointment_date
                                                ? \Carbon\Carbon::parse($recordFollowUp->appointment_date)->format(
                                                    'F j, Y',
                                                )
                                                : 'N/A',

                                            'time' => $recordFollowUp->appointment_time
                                                ? \Carbon\Carbon::parse($recordFollowUp->appointment_time)->format(
                                                    'g:i A',
                                                )
                                                : 'N/A',

                                            'service' => $recordFollowUp->service_type_name ?? 'Follow-up',

                                            'status' => $recordFollowUp->status ?? 'upcoming',

                                            'reason' => $recordFollowUp->follow_up_reason,
                                        ]
                                        : null;
                                @endphp

                                <div class="mobile-appt-card {{ $shouldHighlightToday ? 'is-today' : '' }}"
                                    data-appt-id="{{ $appt->id }}" data-period="{{ $period }}"
                                    data-date="{{ $appt->appointment_date }}"
                                    data-time="{{ $appt->appointment_time ?? '' }}"
                                    data-patient="{{ strtolower($patientName) }}"
                                    data-student-no="{{ strtolower($studentNumber) }}"
                                    data-program="{{ strtolower($programFull) }}"
                                    data-service="{{ strtolower($serviceLabel) }}"
                                    data-patient-id="{{ strtolower((string) ($appt->patient_id ?? '')) }}"
                                    data-status="{{ $normalizedStatus }}"
                                    data-follow-up="{{ $isFollowUpAppointment ? '1' : '0' }}" data-show-more-item
                                    style="animation-delay:{{ $i * 0.04 }}s">

                                    <div class="mobile-appt-card-head">

                                        <div class="min-w-0">

                                            <div class="flex items-center gap-2 flex-wrap mb-1">

                                                <p class="mobile-patient-name" data-patient-name>
                                                    {{ $patientName }}
                                                </p>

                                                @if ($isWalkInAppointment)
                                                    <span class="appt-type-icon" data-tooltip="Walk-in appointment"
                                                        data-tooltip-tone="neutral" aria-label="Walk-in appointment"
                                                        tabindex="0">
                                                        <i class="fa-solid fa-person-walking"></i>
                                                    </span>
                                                @endif

                                                @if ($isFollowUpAppointment)
                                                    <span class="appt-type-icon" data-tooltip="Follow-up appointment"
                                                        data-tooltip-tone="neutral" aria-label="Follow-up appointment"
                                                        tabindex="0">
                                                        <i class="fa-solid fa-calendar-plus"></i>
                                                    </span>
                                                @endif

                                                @if ($isDentistView && $appt->reserved_booking_period_id)
                                                    <span class="status-pill status-today"
                                                        data-tooltip="Reserved: {{ $appt->reservedBookingPeriod?->title }}"
                                                        data-tooltip-tone="neutral" aria-label="Reserved appointment"
                                                        tabindex="0">
                                                        <i class="fa-solid fa-calendar-check"></i>
                                                        <span>Reserved</span>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="global-info-group">

                                                <span class="global-info-pill">
                                                    <i class="fa-regular fa-id-card"></i>
                                                    {{ $studentNumber }}
                                                </span>

                                                <span class="global-info-pill " title="{{ $programFull }}">
                                                    <i class="fa-solid fa-graduation-cap"></i>
                                                    {{ $program }}
                                                </span>
                                            </div>

                                        </div>

                                        <div class="flex items-center gap-2 flex-wrap justify-end">

                                            <div class="flex flex-col items-end gap-1">

                                                @if ($shouldHighlightToday)
                                                    <span class="status-pill status-today">
                                                        <span class="status-dot"></span>
                                                        Today
                                                    </span>
                                                @else
                                                    <span class="status-pill {{ $statusMeta['class'] }}">
                                                        <span class="status-dot"></span>
                                                        {{ $statusMeta['label'] }}
                                                    </span>

                                                    @if ($isActiveAppointment && $daysUntilAppointment !== null && $daysUntilAppointment > 0)
                                                        <span class="urgency-chip {{ $appointmentUrgencyClass }}">
                                                            {{ $daysUntilAppointment === 1 ? 'Tomorrow' : 'In ' . $daysUntilAppointment . ' days' }}
                                                        </span>
                                                    @endif
                                                @endif

                                            </div>
                                        </div>
                                    </div>

                                    <div class="appointment-grid-details">

                                        <div class="appointment-grid-detail appointment-grid-detail-wide">
                                            <span class="appointment-grid-detail-label">
                                                Appointment Date
                                            </span>

                                            <span class="global-info-value">
                                                {{ $weekday }}, {{ $dateLabel }}
                                            </span>
                                        </div>

                                        <div class="appointment-grid-detail">
                                            <span class="appointment-grid-detail-label">
                                                Schedule Time
                                            </span>

                                            <span class="time-chip">
                                                <i class="fa-regular fa-clock"></i>
                                                {{ $timeLabel }}
                                            </span>
                                        </div>

                                        <div class="appointment-grid-detail">
                                            <span class="appointment-grid-detail-label">
                                                Service Type
                                            </span>

                                            <span class="service-badge {{ $badgeClass }}">
                                                {{ $serviceLabel }}
                                            </span>
                                        </div>

                                    </div>

                                    <div class="mobile-appt-actions ui-action-group">

                                        @if (!$isActiveAppointment && $canViewTreatmentRecord)
                                            <button type="button" class="ui-action-btn ui-action-neutral"
                                                data-tooltip="View details" onclick="openRecordModal(this)"
                                                data-appt-id="{{ $appt->id }}" data-service="{{ $serviceLabel }}"
                                                data-date="{{ $dateLabel }}" data-time="{{ $timeLabel }}"
                                                data-status="{{ $statusMeta['label'] }}"
                                                data-duration-seconds="{{ $recordProcedure?->procedure_duration_seconds }}"
                                                data-remarks="{{ $recordProcedure?->completion_action ?? '' }}"
                                                data-oral="{{ $recordProcedure?->oral_examination ?? '' }}"
                                                data-diagnosis="{{ $recordProcedure?->diagnosis ?? '' }}"
                                                data-prescription="{{ $recordProcedure?->prescriptions ?? '' }}"
                                                data-follow-up='@json($recordFollowUpPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                                                data-odontogram-data='@json($recordOdontogramData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        @endif

                                        @if ($profileUrl)
                                            <a href="{{ $profileUrl }}" class="ui-action-btn ui-action-view"
                                                data-tooltip="View profile" aria-label="View profile">
                                                <i class="fa-regular fa-user"></i>
                                            </a>
                                        @endif

                                        @if ($canStartProcedure)
                                            <button type="button"
                                                class="ui-action-btn
                                                   ui-action-success
                                                   {{ $canStartThisAppointment ? '' : 'is-start-locked' }}"
                                                data-tooltip="{{ $startTooltip }}"
                                                data-tooltip-tone="{{ $canStartThisAppointment ? 'start' : 'locked' }}"
                                                data-start-locked="{{ $canStartThisAppointment ? '0' : '1' }}"
                                                aria-disabled="{{ $canStartThisAppointment ? 'false' : 'true' }}"
                                                {{ $canStartThisAppointment ? '' : 'disabled' }}
                                                onclick="openStartProcedureModal(this)" data-id="{{ $appt->id }}"
                                                data-name="{{ $patientName }}" data-datetime="{{ $modalDatetime }}"
                                                data-service="{{ $serviceLabel }}"
                                                data-start-url="{{ route('dentist.odontogram', ['appointment' => $appt->id]) }}?from=appointments&start_procedure=1">
                                                <i class="fa-solid fa-play"></i>
                                            </button>
                                        @endif

                                        @if ($canRescheduleAppointment && !$appt->reserved_booking_period_id)
                                            <button type="button"
                                                class="ui-action-btn ui-action-warning {{ $isActiveAppointment ? '' : 'is-start-locked' }}"
                                                data-tooltip="{{ $isActiveAppointment ? 'Reschedule appointment' : 'Only upcoming or rescheduled appointments can be changed' }}"
                                                data-tooltip-tone="{{ $isActiveAppointment ? 'reschedule' : 'locked' }}"
                                                {{ $isActiveAppointment ? '' : 'disabled aria-disabled=true' }}
                                                onclick="@if ($isActiveAppointment) openRescheduleModal({
                                                id: '{{ $appt->id }}',
                                                name: @js($patientName),
                                                datetime: @js($modalDatetime),
                                                serviceType: @js($appt->service_type_name),
                                                updateUrl: '{{ route('dentist.dentist.appointments.reschedule.update', $appt->id) }}'
                                            }) @endif">
                                                <i class="fa-solid fa-rotate-right"></i>
                                            </button>
                                        @endif

                                        @if ($canCancelAppointment)
                                            <button type="button"
                                                class="ui-action-btn ui-action-delete {{ $isActiveAppointment ? '' : 'is-start-locked' }}"
                                                data-tooltip="{{ $isActiveAppointment ? 'Cancel appointment' : 'Only upcoming or rescheduled appointments can be cancelled' }}"
                                                data-tooltip-tone="{{ $isActiveAppointment ? 'cancel' : 'locked' }}"
                                                {{ $isActiveAppointment ? '' : 'disabled aria-disabled=true' }}
                                                onclick="@if ($isActiveAppointment) cancelAppointmentFromModal(
                                                '{{ route('dentist.dentist.appointments.cancel', $appt->id) }}',
                                                @js($patientName),
                                                @js($dateLabel . ' | ' . $timeLabel)
                                            ) @endif">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <x-show-more label="appointments" />

                    </div>
                </details>

            @empty

                <div id="appointmentStaticEmpty" class="empty-state appointment-static-empty">
                    <div class="empty-state-icon appointment-empty-icon">
                        <i class="fa-regular fa-calendar-xmark"></i>
                    </div>

                    <p class="empty-state-title">
                        No appointments yet
                    </p>

                    <p class="empty-state-sub">
                        Appointments will appear here once scheduled.
                    </p>
                </div>
            @endforelse
        </section>

        <div id="appointmentEmptyState" class="empty-state-host"></div>
    </main>

    <x-filter-drawer id="filterModal" title="Filters" close-id="closeFilterModalBtn"
        close-callback="window.closeFilterDrawer?.('filterModal')" clear-id="clearFiltersModal"
        clear-label="Clear Filters" cancel-id="cancelFilterBtn"
        cancel-callback="window.closeFilterDrawer?.('filterModal')" cancel-label="Cancel" apply-id="applyFilters"
        apply-callback="applyAppointmentDrawerFilters()" apply-label="Show 0 results" results-id="showResultsText">

        <div id="activeFiltersSection" class="filter-active-section hidden">

            <div class="filter-active-header">
                <span class="filter-active-title">
                    Active Filters
                </span>

                <button id="clearAllChipsBtn" type="button" class="filter-clear-all ui-btn ui-btn-secondary ui-btn-sm">

                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Clear All</span>
                </button>
            </div>

            <div id="activeChipsContainer" class="active-filters-container">
            </div>
        </div>

        <div>
            <h3 class="filter-section-title">Sort By</h3>

            <div class="filter-chip-row" id="apptSortGroup">
                <button type="button" class="ftag ftag-active" data-sort="nearest">
                    Nearest Appointment
                </button>

                <button type="button" class="ftag" data-sort="oldest">
                    Oldest First
                </button>

                <button type="button" class="ftag" data-sort="az">
                    Patient Name A-Z
                </button>

                <button type="button" class="ftag" data-sort="za">
                    Patient Name Z-A
                </button>
            </div>
        </div>

        <div>
            <h3 class="filter-section-title">
                Filter by Date Range
            </h3>

            <div class="filter-chip-row" id="datePresetGroup">
                <button type="button" class="quick-date-chip" data-range="7">
                    Last 7 Days
                </button>

                <button type="button" class="quick-date-chip" data-range="30">
                    Last 30 Days
                </button>

                <button type="button" class="quick-date-chip" data-range="90">
                    Last 3 Months
                </button>

                <button type="button" class="quick-date-chip" data-range="180">
                    Last 6 Months
                </button>

                <button type="button" class="quick-date-chip" data-range="365">
                    Last 12 Months
                </button>
            </div>
        </div>

        <div>
            <h3 class="filter-section-title">
                Custom Date Range
            </h3>

            <div class="filter-date-grid">
                <div class="filter-date-input-wrap">
                    <input id="fromDate" type="text" class="form-input-custom js-flatpickr-date-range-from" placeholder="Start date"
                        readonly autocomplete="off">

                    <i class="fa-regular fa-calendar"></i>
                </div>

                <div class="filter-date-input-wrap">
                    <input id="toDate" type="text" class="form-input-custom js-flatpickr-date-range-to" placeholder="End date"
                        readonly autocomplete="off">

                    <i class="fa-regular fa-calendar"></i>
                </div>
            </div>
        </div>

    </x-filter-drawer>

    @if ($isDentistView)
        <x-start-procedure-modal id="startProcedureModal" />
    @endif
@endsection

@section('scripts')
    <script>
        const APPOINTMENT_REFRESH_ITEMS = @json($appointmentRefreshItems);
        const APPOINTMENT_REFRESH_URL = window.location.href;
        let appointmentRefreshWatcher = null;

        function initAppointmentRefreshWatcher() {
            if (!window.initGlobalRefreshWatcher) return;

            appointmentRefreshWatcher = window.initGlobalRefreshWatcher({
                key: @json($isDentistView ? 'dentist-appointments' : 'admin-appointments'),
                url: APPOINTMENT_REFRESH_URL,
                initialItems: APPOINTMENT_REFRESH_ITEMS,
                anchorSelector: '#mainContent.shared-appointments-page .appointment-controls-bar',
                itemLabel: 'appointment',

                getItems(payload) {
                    if (Array.isArray(payload)) {
                        return payload;
                    }

                    return Array.isArray(payload?.appointments) ?
                        payload.appointments : [];
                },

                getItemId(appointment) {
                    return appointment?.id;
                },

                title(count) {
                    return `${count} new appointment${count === 1 ? '' : 's'} available`;
                },

                subtitle(count) {
                    return `Refresh to see the latest appointment update${count === 1 ? '' : 's'}.`;
                },

                onRefresh() {
                    window.location.reload();
                },

                toast: false
            });
        }

        function openStartProcedureModal(btn) {
            if (
                !btn ||
                btn.disabled ||
                btn.dataset.startLocked === '1'
            ) {
                return;
            }

            const modal =
                document.getElementById(
                    'startProcedureModal'
                );

            if (modal) {
                modal.dataset.startUrl =
                    btn.dataset.startUrl || '';
            }

            modal
                ?.querySelector(
                    '[data-start-modal-patient]'
                )
                ?.replaceChildren(
                    document.createTextNode(
                        btn.dataset.name || '—'
                    )
                );

            modal
                ?.querySelector(
                    '[data-start-modal-schedule]'
                )
                ?.replaceChildren(
                    document.createTextNode(
                        btn.dataset.datetime || '—'
                    )
                );

            modal
                ?.querySelector(
                    '[data-start-modal-service]'
                )
                ?.replaceChildren(
                    document.createTextNode(
                        btn.dataset.service || '—'
                    )
                );

            window.openModal?.(
                'startProcedureModal'
            );
        }

        let apptSearchInput = null;

        let appointmentPeriodFilter = 'all';
        let appointmentStatusFilter = ['all'];
        let appointmentStatusFilterSource = 'dropdown';
        let appointmentSortFilter = 'nearest';
        let appointmentFromDate = '';
        let appointmentToDate = '';

        function normalizeAppointmentStatusFilter(status = '') {
            const normalized = String(status || '').toLowerCase().trim();

            if (['pending', 'confirmed', 'upcoming'].includes(normalized)) {
                return 'upcoming';
            }

            if (['reschedule', 'rescheduled'].includes(normalized)) {
                return 'rescheduled';
            }

            if (['canceled', 'cancelled'].includes(normalized)) {
                return 'cancelled';
            }

            if (normalized.includes('complete')) {
                return 'completed';
            }

            return normalized || 'upcoming';
        }

        const apptStatusMeta = {
            all: {
                label: 'All statuses',
                icon: 'fa-layer-group',
                tone: 'all'
            },
            upcoming: {
                label: 'Upcoming',
                icon: 'fa-calendar-check',
                tone: 'upcoming'
            },
            rescheduled: {
                label: 'Rescheduled',
                icon: 'fa-rotate-right',
                tone: 'rescheduled'
            },
            completed: {
                label: 'Completed',
                icon: 'fa-circle-check',
                tone: 'completed'
            },
            cancelled: {
                label: 'Cancelled',
                icon: 'fa-circle-xmark',
                tone: 'cancelled'
            }
        };

        function normalizeAppointmentStatusValues(value) {
            const rawValues = Array.isArray(value) ?
                value :
                String(value || 'all').split(',');

            const values = rawValues
                .map(item => String(item || '').trim().toLowerCase())
                .filter(item => item && apptStatusMeta[item]);

            if (!values.length || values.includes('all')) {
                return ['all'];
            }

            return [...new Set(values)];
        }

        function getAppointmentPeriodFromStatuses(values) {
            const statuses = normalizeAppointmentStatusValues(values);

            if (statuses.includes('all')) {
                return 'all';
            }

            const hasUpcoming = statuses.some(status => ['upcoming', 'rescheduled'].includes(status));

            const hasPast = statuses.some(status => ['completed', 'cancelled'].includes(status));

            if (hasUpcoming && hasPast) {
                return 'all';
            }

            return hasPast ? 'past' : 'upcoming';
        }

        const statusEmptyCopy = {
            all: {
                icon: 'fa-filter-circle-xmark',
                title: 'No appointments found',
                sub: 'No appointments match the selected filters.'
            },

            upcoming: {
                icon: 'fa-calendar-check',
                title: 'No upcoming appointments',
                sub: 'No upcoming appointments match the selected filters.'
            },

            rescheduled: {
                icon: 'fa-rotate-right',
                title: 'No rescheduled appointments',
                sub: 'No rescheduled appointments match the selected filters.'
            },

            completed: {
                icon: 'fa-circle-check',
                title: 'No completed appointments',
                sub: 'No completed appointments match the selected filters.'
            },

            cancelled: {
                icon: 'fa-circle-xmark',
                title: 'No cancelled appointments',
                sub: 'No cancelled appointments match the selected filters.'
            }
        };

        function setAppointmentStatusFilter(
            value = 'all',
            shouldApply = true,
            source = 'dropdown'
        ) {
            const nextValues =
                normalizeAppointmentStatusValues(
                    value
                );

            appointmentStatusFilter =
                nextValues;

            appointmentStatusFilterSource =
                source;

            if (source === 'dropdown') {
                appointmentPeriodFilter =
                    getAppointmentPeriodFromStatuses(
                        nextValues
                    );
            }

            if (nextValues.length === 1) {
                window.setGlobalFilterSelectValue?.(
                    'appointmentStatusFilter',
                    nextValues[0], {
                        callback: false,
                        focus: false
                    }
                );
            }

            if (shouldApply) {
                applyAppointmentFilters();
            }
        }

        window.handleAppointmentStatusSelect =
            function(value) {
                const nextValues =
                    normalizeAppointmentStatusValues(
                        value
                    );

                if (apptSearchInput) {
                    apptSearchInput.value = '';

                    window.syncInputClearButton?.(
                        apptSearchInput
                    );
                }

                appointmentStatusFilter =
                    nextValues;

                appointmentPeriodFilter =
                    getAppointmentPeriodFromStatuses(
                        nextValues
                    );

                applyAppointmentFilters();
            };

        window.handleAppointmentSearch =
            function(value) {
                const query =
                    String(value || '')
                    .trim();

                if (
                    query &&
                    !appointmentStatusFilter.includes(
                        'all'
                    )
                ) {
                    setAppointmentStatusFilter(
                        'all',
                        false,
                        'dropdown'
                    );
                }

                appointmentPeriodFilter =
                    'all';

                applyAppointmentFilters();
            };

        function setupAppointmentAccordions() {
            document.querySelectorAll('details.appt-month-group').forEach((group) => {
                const summary = group.querySelector('summary');
                if (!summary || group.dataset.accordionReady === 'true') return;

                group.dataset.accordionReady = 'true';

                summary.addEventListener('click', function(event) {
                    event.preventDefault();

                    if (group.dataset.animating === 'true') return;

                    if (group.open) {
                        group.dataset.animating = 'true';
                        group.classList.add('is-closing');

                        setTimeout(() => {
                            group.open = false;
                            group.classList.remove('is-closing');
                            group.dataset.animating = 'false';
                        }, 220);

                        return;
                    }

                    group.open = true;
                    group.dataset.animating = 'true';
                    group.classList.remove('is-closing');
                    group.classList.add('is-opening');

                    setTimeout(() => {
                        group.classList.remove('is-opening');
                        group.dataset.animating = 'false';
                    }, 280);
                });
            });
        }

        document.addEventListener(
            'DOMContentLoaded',
            () => {
                window.initGlobalVoiceInputs?.();
                window.initGlobalSearchBars?.();

                apptSearchInput =
                    document.getElementById(
                        'apptSearchInput'
                    );

                setupAppointmentFilterPanel();

                const initialStatus =
                    document.getElementById(
                        'appointmentStatusFilterInput'
                    )?.value || 'all';

                appointmentStatusFilter =
                    normalizeAppointmentStatusValues(
                        initialStatus
                    );

                appointmentPeriodFilter =
                    getAppointmentPeriodFromStatuses(
                        appointmentStatusFilter
                    );

                applyAppointmentFilters();
                updateAppointmentFilterButtonState();
                revealAppointmentContainer?.();
                setupAppointmentAccordions();

                window.ShowMore?.init(
                    document
                );

                initAppointmentRefreshWatcher();
            }
        );

        function setAppointmentPeriodFilter(period = 'all') {
            appointmentPeriodFilter = ['upcoming', 'past', 'all'].includes(period) ?
                period :
                'all';
        }

        function normalizeAppointmentDate(value) {
            if (!value) return null;
            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? null : date;
        }

        function getAppointmentTimestamp(card) {
            if (!card) {
                return new Date(0);
            }

            const date =
                card.dataset.date || '';

            const time =
                card.dataset.time || '00:00:00';

            const value =
                new Date(`${date}T${time}`);

            return Number.isNaN(value.getTime()) ?
                new Date(0) :
                value;
        }

        function getAppointmentCards() {
            return Array.from(document.querySelectorAll('.appt-card, .mobile-appt-card'));
        }

        function getUniqueAppointmentCards() {
            const seen = new Set();
            return getAppointmentCards().filter((card) => {
                const key = `${card.dataset.apptId || ''}-${card.dataset.period || ''}`;
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            });
        }

        function matchesAppointmentFilters(
            card,
            filters = null
        ) {
            if (!card) {
                return false;
            }

            const appliedFilters =
                filters || {
                    period: appointmentPeriodFilter,

                    status: appointmentStatusFilter,

                    sort: appointmentSortFilter,

                    fromDate: appointmentFromDate,

                    toDate: appointmentToDate,
                };

            const searchValue =
                String(
                    apptSearchInput?.value || ''
                )
                .trim()
                .toLowerCase();

            const status =
                normalizeAppointmentStatusFilter(
                    card.dataset.status || ''
                );

            const period =
                card.dataset.period || '';

            const date =
                normalizeAppointmentDate(
                    card.dataset.date || ''
                );

            const patient =
                card.dataset.patient || '';

            const patientId =
                card.dataset.patientId || '';

            const studentNumber =
                card.dataset.studentNo || '';

            const program =
                card.dataset.program || '';

            const service =
                card.dataset.service || '';

            const matchesSearch = !searchValue ||
                patient.includes(searchValue) ||
                patientId.includes(searchValue) ||
                studentNumber.includes(searchValue) ||
                program.includes(searchValue) ||
                service.includes(searchValue);

            const matchesPeriod =
                appliedFilters.period === 'all' ||
                period === appliedFilters.period;

            const selectedStatuses =
                normalizeAppointmentStatusValues(
                    appliedFilters.status
                );

            const matchesStatus =
                selectedStatuses.includes('all') ||
                selectedStatuses.includes(status);

            let matchesDate = true;

            const fromDate =
                normalizeAppointmentDate(
                    appliedFilters.fromDate
                );

            const toDate =
                normalizeAppointmentDate(
                    appliedFilters.toDate
                );

            if (
                (fromDate || toDate) &&
                !date
            ) {
                matchesDate = false;
            } else {
                if (
                    fromDate &&
                    date < fromDate
                ) {
                    matchesDate = false;
                }

                if (
                    toDate &&
                    date > toDate
                ) {
                    matchesDate = false;
                }
            }

            return (
                matchesSearch &&
                matchesPeriod &&
                matchesStatus &&
                matchesDate
            );
        }

        function sortAppointmentGroups() {
            const sortValue = appointmentSortFilter;

            document.querySelectorAll('.appt-month-group').forEach((group) => {
                ['.desktop-appointments-table .space-y-2\\.5', '.mobile-appointments-list'].forEach((selector) => {
                    const holder = group.querySelector(selector);
                    if (!holder) return;

                    const cards = Array.from(holder.querySelectorAll(
                        ':scope > .appt-card, :scope > .mobile-appt-card'));
                    cards.sort((a, b) => {
                        const aName =
                            a.dataset.patient || '';

                        const bName =
                            b.dataset.patient || '';

                        const aDate =
                            getAppointmentTimestamp(a);

                        const bDate =
                            getAppointmentTimestamp(b);

                        if (sortValue === 'az') {
                            return aName.localeCompare(bName);
                        }

                        if (sortValue === 'za') {
                            return bName.localeCompare(aName);
                        }

                        if (sortValue === 'oldest') {
                            return aDate - bDate;
                        }

                        const aStatus =
                            normalizeAppointmentStatusFilter(
                                a.dataset.status || ''
                            );

                        const bStatus =
                            normalizeAppointmentStatusFilter(
                                b.dataset.status || ''
                            );

                        const aIsActive = ['upcoming', 'rescheduled']
                            .includes(aStatus);

                        const bIsActive = ['upcoming', 'rescheduled']
                            .includes(bStatus);

                        if (aIsActive !== bIsActive) {
                            return aIsActive ? -1 : 1;
                        }

                        if (aIsActive && bIsActive) {
                            return aDate - bDate;
                        }

                        return bDate - aDate;
                    });

                    cards.forEach((card) => holder.appendChild(card));
                });
            });
        }

        function clearAppointmentSearch() {
            if (apptSearchInput) {
                apptSearchInput.value = '';
                apptSearchInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                apptSearchInput.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                apptSearchInput.focus();
            }
            applyAppointmentFilters();
        }

        function applyAppointmentFilters() {
            const cards =
                getAppointmentCards();

            cards.forEach(function(card) {
                const isMatch =
                    matchesAppointmentFilters(
                        card
                    );

                card.dataset.filterMatch =
                    isMatch ? '1' : '0';

                card.hidden = !isMatch;

                card.classList.toggle(
                    'hidden',
                    !isMatch
                );
            });

            sortAppointmentGroups();

            document
                .querySelectorAll(
                    '.appt-month-group'
                )
                .forEach(function(group) {
                    const referenceHolder =
                        group.querySelector(
                            '[data-month-list]'
                        ) ||
                        group.querySelector(
                            '[data-month-grid]'
                        );

                    const cardsInGroup =
                        Array.from(
                            referenceHolder
                            ?.querySelectorAll(
                                ':scope > [data-show-more-item]'
                            ) || []
                        );

                    const hasMatchingCard =
                        cardsInGroup.some(
                            card =>
                            card.dataset
                            .filterMatch ===
                            '1'
                        );

                    group.hidden = !hasMatchingCard;

                    group.classList.toggle(
                        'hidden',
                        !hasMatchingCard
                    );

                    if (hasMatchingCard) {
                        window.ShowMore?.reset(
                            group
                        );
                    }
                });

            updateFilteredEmptyState();
            updateAppointmentFilterButtonState();
        }

        function updateFilteredEmptyState() {
            const host =
                document.getElementById(
                    'appointmentEmptyState'
                );

            const appointmentsSection =
                document.getElementById(
                    'appointmentsSection'
                );

            const rawSearchValue =
                String(
                    apptSearchInput?.value || ''
                ).trim();

            const hasSearch =
                rawSearchValue !== '';

            const hasStatusFilter = !appointmentStatusFilter.includes(
                'all'
            );

            const hasAdvancedFilters =
                appointmentSortFilter !==
                'nearest' ||
                !!appointmentFromDate ||
                !!appointmentToDate;

            const uniqueCards =
                getUniqueAppointmentCards();

            const matchingCards =
                uniqueCards.filter(
                    card =>
                    matchesAppointmentFilters(
                        card
                    )
                );

            window.EmptyState?.hide?.(
                host
            );

            if (matchingCards.length > 0) {
                appointmentsSection
                    ?.classList
                    .remove('hidden');

                return;
            }

            appointmentsSection
                ?.classList
                .add('hidden');

            if (hasSearch) {
                window.EmptyState?.renderSearch({
                    host: '#appointmentEmptyState',

                    input: '#apptSearchInput',

                    query: rawSearchValue,

                    message: 'Try a different patient name, service, program, or appointment status.'
                });

                return;
            }

            if (hasAdvancedFilters) {
                window.EmptyState?.render({
                    host: '#appointmentEmptyState',

                    icon: 'fa-filter-circle-xmark',

                    title: 'No appointments found',

                    message: 'No appointments match the selected filters.',

                    actionHtml: `
                <button
                    type="button"
                    class="empty-state-btn"
                    data-appointment-clear-filters>
                    <i class="fa-solid fa-rotate-left"></i>
                    Clear filters
                </button>
            `
                });

                document
                    .querySelector(
                        '#appointmentEmptyState ' +
                        '[data-appointment-clear-filters]'
                    )
                    ?.addEventListener(
                        'click',
                        function() {
                            resetAppointmentPanelFilters();
                        }
                    );

                return;
            }

            if (hasStatusFilter) {
                const copy =
                    appointmentStatusFilter.length === 1 ?
                    statusEmptyCopy[
                        appointmentStatusFilter[0]
                    ] || statusEmptyCopy.all :
                    statusEmptyCopy.all;

                window.EmptyState?.render({
                    host: '#appointmentEmptyState',

                    icon: copy.icon,

                    title: copy.title,

                    message: copy.sub
                });

                return;
            }

            window.EmptyState?.render({
                host: '#appointmentEmptyState',

                icon: 'fa-calendar-xmark',

                title: 'No appointments yet',

                message: 'Appointments will appear here once scheduled.'
            });
        }

        function getDraftAppointmentFilters() {
            const activeSort =
                document.querySelector(
                    '#apptSortGroup .ftag.ftag-active'
                );

            return {
                sort: activeSort?.dataset.sort ||
                    'nearest',

                period: appointmentPeriodFilter,

                status: appointmentStatusFilter,

                fromDate: document.getElementById(
                    'fromDate'
                )?.value || '',

                toDate: document.getElementById(
                    'toDate'
                )?.value || ''
            };
        }

        function countDraftAppointmentResults() {
            const draft = getDraftAppointmentFilters();
            return getUniqueAppointmentCards().filter((card) => matchesAppointmentFilters(card, draft)).length;
        }

        function updateAppointmentShowResultsButton() {
            const showResultsText = document.getElementById('showResultsText');
            if (!showResultsText) return;
            const count = countDraftAppointmentResults();
            showResultsText.textContent = `Show ${count} ${count === 1 ? 'result' : 'results'}`;
        }

        function updateAppointmentFilterButtonState() {
            const badge =
                document.getElementById(
                    'appointmentFilterBadge'
                );

            const filterBtn =
                document.getElementById(
                    'appointmentFilterBtn'
                );

            const clearBtn =
                document.getElementById(
                    'appointmentClearFilterBtn'
                );

            let count = 0;

            if (
                appointmentSortFilter !==
                'nearest'
            ) {
                count++;
            }

            if (
                appointmentFromDate ||
                appointmentToDate
            ) {
                count++;
            }

            const has = count > 0;

            if (filterBtn) {
                filterBtn.classList.toggle(
                    'has-filters',
                    has
                );

                filterBtn.setAttribute(
                    'aria-pressed',
                    has ? 'true' : 'false'
                );
            }

            if (badge) {
                badge.classList.toggle(
                    'show',
                    has
                );

                badge.textContent =
                    has ? count : '';
            }

            if (clearBtn) {
                clearBtn.classList.toggle(
                    'hidden',
                    !has
                );

                clearBtn.classList.toggle(
                    'show',
                    has
                );
            }
        }

        function renderAppointmentFilterChips() {
            const container = document.getElementById('activeChipsContainer');
            const section = document.getElementById('activeFiltersSection');
            if (!container || !section) return;

            container.innerHTML = '';
            let hasChips = false;

            function addChip(label, callback) {
                hasChips = true;
                const chip = document.createElement('div');
                chip.className = 'filter-chip';
                chip.innerHTML =
                    `<span>${label}</span><span class="filter-chip-remove"><i class="fa-solid fa-xmark"></i></span>`;
                chip.querySelector('.filter-chip-remove').onclick = function() {
                    callback();
                    renderAppointmentFilterChips();
                    updateAppointmentShowResultsButton();
                };
                container.appendChild(chip);
            }

            const draft = getDraftAppointmentFilters();

            if (draft.sort !== 'nearest') {
                const sortLabel = document.querySelector(`#apptSortGroup .ftag[data-sort="${draft.sort}"]`)?.textContent
                    .trim() || draft.sort;
                addChip(`Sort: ${sortLabel}`, function() {
                    document.querySelectorAll('#apptSortGroup .ftag').forEach(btn => btn.classList.remove(
                        'ftag-active'));
                    document.querySelector('#apptSortGroup .ftag[data-sort="nearest"]')?.classList.add(
                        'ftag-active');
                });
            }

            if (draft.fromDate || draft.toDate) {
                addChip(`Date: ${draft.fromDate || 'Any'} to ${draft.toDate || 'Any'}`, function() {
                    const from = document.getElementById('fromDate');
                    const to = document.getElementById('toDate');
                    if (from) from.value = '';
                    if (to) to.value = '';
                    document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => btn.classList
                        .remove('active'));
                });
            }

            section.classList.toggle('hidden', !hasChips);
        }

        function syncAppointmentFilterInputs() {
            document.querySelectorAll('#apptSortGroup .ftag').forEach(btn => {
                btn.classList.toggle('ftag-active', btn.dataset.sort === appointmentSortFilter);
            });

            const from = document.getElementById('fromDate');
            const to = document.getElementById('toDate');

            if (from) from.value = appointmentFromDate;
            if (to) to.value = appointmentToDate;
        }

        function resetAppointmentPanelFilters(
            shouldApply = true
        ) {
            appointmentSortFilter = 'nearest';
            appointmentFromDate = '';
            appointmentToDate = '';

            syncAppointmentFilterInputs();

            document
                .querySelectorAll(
                    '#datePresetGroup .quick-date-chip'
                )
                .forEach(function(button) {
                    button.classList.remove(
                        'active'
                    );
                });

            renderAppointmentFilterChips();
            updateAppointmentShowResultsButton();
            updateAppointmentFilterButtonState();

            if (shouldApply) {
                applyAppointmentFilters();
            }
        }

        function resetAppointmentFilters() {
            if (apptSearchInput) {
                apptSearchInput.value = '';

                window.syncInputClearButton?.(
                    apptSearchInput
                );
            }

            appointmentStatusFilter = ['all'];

            appointmentPeriodFilter =
                'all';

            window.setGlobalFilterSelectValue?.(
                'appointmentStatusFilter',
                'all', {
                    callback: false,
                    focus: false
                }
            );

            resetAppointmentPanelFilters(
                false
            );

            applyAppointmentFilters();
        }

        window.applyAppointmentDrawerFilters =
            function() {
                const draft =
                    getDraftAppointmentFilters();

                appointmentSortFilter =
                    draft.sort;

                appointmentFromDate =
                    draft.fromDate;

                appointmentToDate =
                    draft.toDate;

                window.closeFilterDrawer?.(
                    'filterModal'
                );

                applyAppointmentFilters();

                updateAppointmentFilterButtonState();
            };

        function setupAppointmentFilterPanel() {
            const filterBtn =
                document.getElementById(
                    'appointmentFilterBtn'
                );
            const filterModal =
                document.getElementById(
                    'filterModal'
                );
            const clearBtn = document.getElementById('clearFiltersModal');
            const clearAllBtn = document.getElementById('clearAllChipsBtn');

            document.querySelectorAll('#apptSortGroup .ftag').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#apptSortGroup .ftag').forEach(item => item.classList.remove(
                        'ftag-active'));
                    btn.classList.add('ftag-active');
                    renderAppointmentFilterChips();
                    updateAppointmentShowResultsButton();
                });
            });

            filterBtn?.addEventListener(
                'click',
                function(event) {
                    event.preventDefault();

                    syncAppointmentFilterInputs();
                    renderAppointmentFilterChips();
                    updateAppointmentShowResultsButton();

                    window.openFilterDrawer?.(
                        'filterModal'
                    );
                }
            );

            filterModal?.querySelectorAll('input[type="radio"]').forEach(input => {
                input.addEventListener('change', function() {
                    renderAppointmentFilterChips();
                    updateAppointmentShowResultsButton();
                });
            });

            filterModal?.querySelectorAll('#fromDate, #toDate').forEach(input => {
                input.addEventListener('change', function() {
                    renderAppointmentFilterChips();
                    updateAppointmentShowResultsButton();
                });
            });

            document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(item => item
                        .classList.remove('active'));
                    btn.classList.add('active');

                    const days = parseInt(btn.dataset.range || '0', 10);
                    const toDate = new Date();
                    const fromDate = new Date();
                    fromDate.setDate(toDate.getDate() - days);

                    const formatDate = (date) => date.toISOString().slice(0, 10);
                    const from = document.getElementById('fromDate');
                    const to = document.getElementById('toDate');
                    if (from) from.value = formatDate(fromDate);
                    if (to) to.value = formatDate(toDate);

                    renderAppointmentFilterChips();
                    updateAppointmentShowResultsButton();
                });
            });

            clearBtn?.addEventListener(
                'click',
                function() {
                    resetAppointmentPanelFilters();
                    updateAppointmentShowResultsButton();
                }
            );

            clearAllBtn?.addEventListener(
                'click',
                function() {
                    resetAppointmentPanelFilters();
                    updateAppointmentShowResultsButton();
                }
            );
        }

        function revealAppointmentContainer() {
            const skeleton = document.getElementById('appointmentContainerSkeleton');
            const content = document.getElementById('appointmentContainerContent');

            setTimeout(() => {
                skeleton?.classList.add('is-hidden');
                content?.classList.remove('is-skeleton-hidden');
                content?.classList.add('is-ready');
            }, 320);
        }
    </script>
@endsection

