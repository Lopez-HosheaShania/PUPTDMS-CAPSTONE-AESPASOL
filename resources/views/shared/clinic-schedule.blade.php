@extends('layouts.app')

@section('layout-role', $layoutRole ?? 'admin')

@section('title', $pageTitle ?? 'Clinic Schedule')

@section('styles')
    @vite('resources/css/pages/shared/clinic-schedule.css')
@endsection

@section('content')
    @php
        $layoutRole = $layoutRole ?? 'admin';

        $isDentistView = $isDentistView ?? false;

        $reservedErrors = $reservedErrors ?? $errors->getBag('reservedPeriod');

        $canCreateReservedPeriods = auth()->user()?->hasPermission('create_clinic_schedule') ?? false;
        $canUpdateReservedPeriods = auth()->user()?->hasPermission('update_clinic_schedule') ?? false;
        $canDeleteReservedPeriods = auth()->user()?->hasPermission('delete_clinic_schedule') ?? false;
        $canManageReservedPeriods = $canUpdateReservedPeriods || $canDeleteReservedPeriods;

        $pageShellClass = $pageShellClass ?? ($isDentistView ? 'app-page-shell' : 'app-page-shell');

        $pageTitle = $pageTitle ?? 'Clinic Schedule';

        $clinicScheduleRouteNames = $clinicScheduleRouteNames ?? [
            'store' => 'admin.clinic_schedule.store',
            'update' => 'admin.clinic_schedule.update',
            'destroy' => 'admin.clinic_schedule.destroy',
            'block' => 'admin.clinic_schedule.block',
            'unblock' => 'admin.clinic_schedule.unblock',
        ];

        $activeSchedules = $schedules->where('is_active', true);
        $openRules = $activeSchedules->where('status', '!=', 'closed');
        $openDays = $openRules->sum(fn($s) => count($s->days ?? []));
        $maxSlots = $openRules->max('max_slots') ?? 0;
        $blockedThisMonth = $blockedDates->filter(fn($b) => \Carbon\Carbon::parse($b->date)->isCurrentMonth())->count();
        $holidaysThisMonth = collect($philippineHolidays)
            ->filter(fn($holiday, $date) => \Carbon\Carbon::parse($date)->isCurrentMonth())
            ->count();

        $scheduleByDay = [];
        foreach ($activeSchedules as $s) {
            foreach ($s->days ?? [] as $d) {
                $scheduleByDay[$d] = $s;
            }
        }

        $dayNames = [
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
            'Sunday' => 'Sun',
        ];

        $breakSchedule = $openRules->first(fn($s) => $s->break_time && $s->break_time !== 'none');

        $serviceBadgeClass = function ($service) {
            $service = strtolower(trim((string) $service));

            if (str_contains($service, 'surgery')) {
                return 'service-badge-surgery';
            }

            if (str_contains($service, 'check')) {
                return 'service-badge-checkup';
            }

            if (str_contains($service, 'whiten')) {
                return 'service-badge-whitening';
            }

            if (str_contains($service, 'extrac')) {
                return 'service-badge-extraction';
            }

            return 'service-badge-default';
        };

    @endphp

    <main id="mainContent" class="{{ $pageShellClass }} clinic-schedule-page page-enter mode-list">

        <div class="w-full">

            @if ($errors->any())
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const hasRuleErrors =
                            @json(
                                $errors->has('days') ||
                                    $errors->has('is_active') ||
                                    $errors->has('status') ||
                                    $errors->has('open_time') ||
                                    $errors->has('close_time') ||
                                    $errors->has('max_slots') ||
                                    $errors->has('notes'));

                        const hasBlockErrors =
                            @json($errors->has('date') || $errors->has('reason') || $errors->has('note'));

                        if (hasRuleErrors) {
                            openRuleModal();
                        }

                        if (hasBlockErrors) {
                            openBlockModal();
                        }

                        @if ($errors->has('days'))
                            setFieldError('ruleDaysError', @json($errors->first('days')), null, 'ruleDaysGroup');
                        @endif
                        @if ($errors->has('is_active'))
                            const activeScheduleError =
                                @json($errors->first('is_active'));

                            setFieldError(
                                'ruleStateError',
                                activeScheduleError,
                                'ruleActivationState'
                            );

                            window.setTimeout(() => {
                                window.showToast?.({
                                    type: 'warning',
                                    title: 'Schedule cannot be activated',
                                    message: activeScheduleError,
                                    duration: 5000
                                });
                            }, 100);
                        @endif
                        @if ($errors->has('status'))
                            setFieldError('ruleStatusError', @json($errors->first('status')), 'ruleStatus');
                        @endif
                        @if ($errors->has('open_time'))
                            setFieldError('ruleOpenTimeError', @json($errors->first('open_time')), 'ruleOpenTime');
                        @endif
                        @if ($errors->has('close_time'))
                            setFieldError('ruleCloseTimeError', @json($errors->first('close_time')), 'ruleCloseTime');
                        @endif
                        @if ($errors->has('max_slots'))
                            setFieldError('ruleMaxSlotsError', @json($errors->first('max_slots')), 'ruleMaxSlots');
                        @endif
                        @if ($errors->has('notes'))
                            setFieldError('ruleNotesError', @json($errors->first('notes')), 'ruleNotes');
                        @endif

                        @if ($errors->has('date'))
                            setFieldError('blockDateError', @json($errors->first('date')), 'blockDate');
                        @endif
                        @if ($errors->has('reason'))
                            setFieldError('blockReasonError', @json($errors->first('reason')), 'blockReason');
                        @endif
                        @if ($errors->has('note'))
                            setFieldError('blockNoteError', @json($errors->first('note')), 'blockNote');
                        @endif
                    });
                </script>
            @endif

            @if ($isDentistView)
                <section class="dentist-hero mb-5">
                    <div class="dentist-hero-content">
                        <div class="dentist-hero-icon">
                            <i class="fa-solid fa-calendar-week"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="dentist-hero-eyebrow">
                                <i class="fa-solid fa-tooth"></i>
                                Clinic Availability
                            </div>

                            <h1 class="dentist-hero-title">
                                Clinic Schedule
                            </h1>
                        </div>
                    </div>

                    <div class="dentist-hero-actions">
                        <button type="button" onclick="openRuleModal()" class="ui-btn ui-btn-primary">

                            <i class="fa-solid fa-plus"></i>
                            <span>Add Schedule Rule</span>
                        </button>

                        <button type="button" onclick="openBlockModal()" class="ui-btn ui-btn-secondary">

                            <i class="fa-solid fa-ban"></i>
                            <span>Block Date</span>
                        </button>
                    </div>
                </section>
            @else
                <div class="page-banner">
                    <div class="page-banner-inner">
                        <div>
                            <h1 class="page-title">
                                Clinic Schedule
                            </h1>
                        </div>

                        <div class="flex items-center gap-3 flex-wrap page-actions">
                            <button type="button" onclick="openRuleModal()" class="ui-btn ui-btn-primary">

                                <i class="fa-solid fa-plus"></i>
                                <span>Add Schedule Rule</span>
                            </button>

                            <button type="button" onclick="openBlockModal()" class="ui-btn ui-btn-secondary">

                                <i class="fa-solid fa-ban"></i>
                                <span>Block Date</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="admin-page-body">

                <div id="statCards" class="stat-grid">
                    @php
                        $statCards = [
                            [
                                'icon' => 'fa-calendar-days',
                                'class' => 's-crimson',
                                'val' => $openDays,
                                'label' => 'Open Days/Week',
                                'sub' => 'Active schedule days',
                            ],
                            [
                                'icon' => 'fa-clock',
                                'class' => 's-blue',
                                'val' => $maxSlots,
                                'label' => 'Daily Slot Cap',
                                'sub' => 'Max patients/day',
                            ],
                            [
                                'icon' => 'fa-ban',
                                'class' => 's-green',
                                'val' => $blockedThisMonth,
                                'label' => 'Blocked Dates',
                                'sub' => 'This month',
                            ],
                            [
                                'icon' => 'fa-umbrella-beach',
                                'class' => 's-amber',
                                'val' => $holidaysThisMonth,
                                'label' => 'Holidays',
                                'sub' => 'This month',
                            ],
                        ];
                    @endphp

                    @foreach ($statCards as $card)
                        <div class="stat-card {{ $card['class'] }}">
                            <div class="stat-card-info">
                                <div class="stat-label">{{ $card['label'] }}</div>
                                <div class="stat-num">{{ $card['val'] }}</div>
                                <div class="stat-footer">{{ $card['sub'] }}</div>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid {{ $card['icon'] }}"></i>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-6 mb-6">

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <section id="clinicHoursCard" class="card">
                            <div class="card-header card-header-inline">
                                <div class="card-header-left">
                                    <span class="card-header-icon" aria-hidden="true">
                                        <i class="fa-solid fa-clock"></i>
                                    </span>

                                    <h2 class="card-title">Clinic Hours</h2>
                                </div>

                                <div class="card-header-right">
                                    <button type="button" onclick="openRuleModal()"
                                        class="ui-btn ui-btn-primary ui-btn-sm">

                                        <i class="fa-solid fa-plus"></i>
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>

                            <div class="table-list-view">
                                @foreach ($dayNames as $fullName => $abbr)
                                    @php $s = $scheduleByDay[$abbr] ?? null; @endphp

                                    <div class="table-list-row">
                                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                                            <span class="table-record-title">{{ $fullName }}</span>

                                            @if ($s && $s->status !== 'closed')
                                                <span class="table-record-value">{{ $s->hours_range }}</span>
                                            @else
                                                <span class="status-pill status-inactive">Closed</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @if ($breakSchedule)
                                    <div class="table-list-row">
                                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                                            <span class="table-date">
                                                <i class="fa-solid fa-mug-hot"></i>
                                                Lunch
                                            </span>

                                            @php [$bs,$be]=explode('-',$breakSchedule->break_time); @endphp
                                            <span class="table-record-value">
                                                {{ date('g:i A', strtotime(trim($bs) . ':00')) }} –
                                                {{ date('g:i A', strtotime(trim($be) . ':00')) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <section id="blockedDatesCard" class="card">
                            <div class="card-header card-header-inline">
                                <div class="card-header-left">
                                    <span class="card-header-icon" aria-hidden="true">
                                        <i class="fa-solid fa-ban"></i>
                                    </span>

                                    <h2 class="card-title">Blocked Dates</h2>
                                </div>

                                <div class="card-header-right">
                                    <button type="button" onclick="openBlockModal()"
                                        class="ui-btn ui-btn-primary ui-btn-sm">
                                        <i class="fa-solid fa-plus"></i>
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>

                            @if ($blockedDates->count())
                                <div id="blockedDatesListView" class="table-list-view">
                                    @foreach ($blockedDates as $blocked)
                                        @php
                                            $bd = \Carbon\Carbon::parse($blocked->date);

                                            $blockedDisplayName = $bd->format('D, M j, Y') . ' — ' . $blocked->reason;
                                        @endphp

                                        <div class="table-list-row">

                                            <div class="flex items-start gap-3 px-4 py-3">

                                                <div class="min-w-0 flex-1">

                                                    <div class="table-record-title">
                                                        {{ $bd->format('D, M j, Y') }}
                                                    </div>

                                                    <span class="global-info-subvalue">
                                                        {{ $blocked->reason }}
                                                    </span>

                                                    @if ($blocked->note)
                                                        <span class="global-info-subvalue mt-1">
                                                            {{ $blocked->note }}
                                                        </span>
                                                    @endif

                                                </div>

                                                <span class="status-pill status-pending">
                                                    Blocked
                                                </span>

                                                <button type="button" class="ui-action-btn ui-action-delete"
                                                    data-tooltip="Remove blocked date" aria-label="Remove blocked date"
                                                    onclick='openBlockedDateDeleteModal(
                    @json(route($clinicScheduleRouteNames['unblock'], $blocked)),
                    @json($blockedDisplayName)
                )'>

                                                    <i class="fa-solid fa-trash"></i>

                                                </button>

                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="card-body">
                                    <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                                        <span class="global-info-icon status-completed" aria-hidden="true">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                        <span class="ui-muted-text">No blocked dates</span>
                                    </div>
                                </div>
                            @endif
                        </section>
                    </div>

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <section id="weeklyAppointmentCard" class="card xl:col-span-2">
                            <div class="card-header weekly-appointment-header">

                                <div class="card-header-left">
                                    <span class="card-header-icon" aria-hidden="true">
                                        <i class="fa-solid fa-calendar-week"></i>
                                    </span>

                                    <h2 class="card-title">
                                        Weekly Appointment View
                                    </h2>
                                </div>

                                <div class="card-header-right weekly-appointment-header-actions">

                                    <div class="weekly-week-nav">

                                        <button type="button" id="prevWeek" class="ui-icon-btn neutral"
                                            data-tooltip="Previous week" data-tooltip-tone="neutral"
                                            aria-label="Previous week">

                                            <i class="fa-solid fa-chevron-left"></i>
                                        </button>

                                        <span id="weekRangeLabel" class="ui-muted-text text-center">
                                        </span>

                                        <button type="button" id="nextWeek" class="ui-icon-btn neutral"
                                            data-tooltip="Next week" data-tooltip-tone="neutral" aria-label="Next week">

                                            <i class="fa-solid fa-chevron-right"></i>
                                        </button>

                                    </div>

                                    <button type="button" id="todayBtn" class="ui-btn ui-btn-secondary ui-btn-sm">

                                        <i class="fa-solid fa-calendar-day"></i>
                                        <span>Today</span>

                                    </button>

                                </div>

                            </div>

                            <div class="card-body">
                                <div id="weekGrid" class="week-grid"></div>

                                <div class="global-info-group mt-3 justify-end">
                                    <span class="service-badge service-badge-checkup">
                                        Check-up
                                    </span>

                                    <span class="service-badge service-badge-default">
                                        Cleaning
                                    </span>

                                    <span class="service-badge service-badge-surgery">
                                        Surgery
                                    </span>

                                    <span class="service-badge service-badge-default">
                                        Prosthesis
                                    </span>
                                </div>
                            </div>
                        </section>

                        <section id="holidaysCard" class="card flex flex-col xl:self-start">

                            @php
                                $today = now()->startOfDay();

                                $MONTHS_SHORT = [
                                    'Jan',
                                    'Feb',
                                    'Mar',
                                    'Apr',
                                    'May',
                                    'Jun',
                                    'Jul',
                                    'Aug',
                                    'Sep',
                                    'Oct',
                                    'Nov',
                                    'Dec',
                                ];

                                $allUpcomingHolidays = collect($philippineHolidays)
                                    ->filter(
                                        fn($holiday, $date) => \Carbon\Carbon::parse($date)->startOfDay()->gte($today),
                                    )
                                    ->sortKeys();

                                $upcoming = $allUpcomingHolidays->take(5);
                            @endphp

                            <div class="card-header card-header-inline">

                                <div class="card-header-left">
                                    <span class="card-header-icon" aria-hidden="true">
                                        <i class="fa-solid fa-umbrella-beach"></i>
                                    </span>

                                    <h2 class="card-title">
                                        Upcoming Holidays
                                    </h2>
                                </div>

                                <span class="card-header-badge">
                                    {{ $upcoming->count() }} upcoming
                                </span>

                            </div>

                            @if ($upcoming->count())

                                <div class="table-list-view">

                                    @foreach ($upcoming as $hDate => $holiday)
                                        @php
                                            $hC = \Carbon\Carbon::parse($hDate);

                                            $diff = (int) $today->diffInDays($hC, false);

                                            $holidayName = is_array($holiday)
                                                ? $holiday['name'] ?? 'Philippine Holiday'
                                                : (string) $holiday;

                                            $isBlockedHoliday = is_array($holiday)
                                                ? $holiday['is_blocked_for_booking'] ?? true
                                                : true;
                                        @endphp

                                        <div class="table-list-row {{ $loop->first ? 'status-cancelled' : '' }}"
                                            @if ($loop->first) style="
                            background: var(--status-bg);
                            border-left: 3px solid var(--crimson);
                        " @endif>

                                            <div class="flex items-start gap-3 px-4 py-3">

                                                <div class="w-11 shrink-0 text-center">

                                                    <div class="appt-visit-month">
                                                        {{ $MONTHS_SHORT[$hC->month - 1] }}
                                                    </div>

                                                    <div class="text-xl font-extrabold leading-none"
                                                        style="color: var(--text-1);">

                                                        {{ $hC->day }}

                                                    </div>

                                                </div>

                                                <div class="min-w-0 flex-1">

                                                    <div class="appt-visit-title">
                                                        {{ $holidayName }}
                                                    </div>

                                                    <span class="global-info-subvalue">
                                                        {{ $diff === 0 ? 'Today' : ($diff === 1 ? 'Tomorrow' : "In $diff days") }}
                                                    </span>

                                                    @if ($loop->first)
                                                        @if ($isBlockedHoliday)
                                                            <div class="global-info-subvalue status-cancelled mt-2"
                                                                style="color: var(--status-text);">

                                                                <i class="fa-solid fa-triangle-exclamation"></i>

                                                                Bookings unavailable on this date

                                                            </div>
                                                        @else
                                                            <div class="global-info-subvalue status-active mt-2"
                                                                style="color: var(--status-text);">

                                                                <i class="fa-solid fa-circle-check"></i>

                                                                Clinic remains open on this date

                                                            </div>
                                                        @endif
                                                    @endif

                                                </div>

                                                <div class="shrink-0 pt-1">

                                                    @if ($isBlockedHoliday)
                                                        <div class="global-info-group status-pending flex-nowrap"
                                                            style="color: var(--status-text);">

                                                            <i class="fa-solid fa-star text-[9px]"></i>

                                                            <span class="text-[.66rem] font-bold whitespace-nowrap">
                                                                Non-Working
                                                            </span>

                                                        </div>
                                                    @else
                                                        <div class="global-info-group status-all flex-nowrap"
                                                            style="color: var(--status-text);">

                                                            <i class="fa-solid fa-briefcase text-[9px]"></i>

                                                            <span class="text-[.66rem] font-bold whitespace-nowrap">
                                                                Working
                                                            </span>

                                                        </div>
                                                    @endif

                                                </div>

                                            </div>

                                        </div>
                                    @endforeach

                                </div>

                                <div class="border-t px-4 py-4" style="border-color: var(--border);">

                                    <div class="table-record-label mb-3">
                                        Good to Know
                                    </div>

                                    <div class="space-y-3">

                                        <div class="flex items-start gap-2">
                                            <span class="global-info-icon status-active"
                                                style="width:20px;height:20px;min-width:20px;" aria-hidden="true">

                                                <i class="fa-solid fa-check text-[9px]"></i>
                                            </span>

                                            <span class="global-info-subvalue">
                                                Non-working holidays auto-block regular bookings for that date.
                                            </span>
                                        </div>

                                        <div class="flex items-start gap-2">
                                            <span class="global-info-icon status-active"
                                                style="width:20px;height:20px;min-width:20px;" aria-hidden="true">

                                                <i class="fa-solid fa-check text-[9px]"></i>
                                            </span>

                                            <span class="global-info-subvalue">
                                                Working holidays keep the clinic open on its normal hours.
                                            </span>
                                        </div>

                                        <div class="flex items-start gap-2">
                                            <span class="global-info-icon status-active"
                                                style="width:20px;height:20px;min-width:20px;" aria-hidden="true">

                                                <i class="fa-solid fa-check text-[9px]"></i>
                                            </span>

                                            <span class="global-info-subvalue">
                                                Reserved periods aren't cancelled automatically — check for conflicts.
                                            </span>
                                        </div>

                                    </div>

                                </div>

                                <div class="border-t px-4 py-3" style="border-color: var(--border);">

                                    <button type="button" class="ui-btn ui-btn-secondary w-full"
                                        onclick="window.openModal('holidayListModal')">

                                        <i class="fa-regular fa-calendar-days"></i>

                                        <span>
                                            View Full List
                                        </span>
                                    </button>

                                </div>
                            @else
                                <div class="card-body">

                                    <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">

                                        <span class="global-info-icon status-default" aria-hidden="true">

                                            <i class="fa-solid fa-calendar"></i>

                                        </span>

                                        <span class="ui-muted-text">
                                            No upcoming holidays.
                                        </span>

                                    </div>

                                </div>

                            @endif

                        </section>
                    </div>

                    <section id="scheduleRulesCard" class="card">
                        <div class="card-header schedule-rules-header">
                            <div class="card-header-left">
                                <span class="card-header-icon" aria-hidden="true">
                                    <i class="fa-solid fa-list-check"></i>
                                </span>

                                <h2 class="card-title">Schedule Rules</h2>
                            </div>

                            <div class="card-header-right schedule-rules-header-actions">

                                <x-view-toggle id="scheduleRulesViewToggle" storage-key="scheduleRulesView"
                                    list-view="#scheduleRulesListView" grid-view="#scheduleRulesGridView" />
                            </div>
                        </div>

                        @if ($schedules->count())
                            <div id="scheduleRulesListView" class="table-list-view">
                                <div class="hidden md:block">
                                    <div class="table-scroll">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Day(s)</th>
                                                    <th>Opens</th>
                                                    <th>Closes</th>
                                                    <th>Lunch Break</th>
                                                    <th>Max Slots</th>
                                                    <th>Status</th>
                                                    <th>Rule State</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($schedules as $rule)
                                                    @php
                                                        $ruleStatusClass = match ($rule->status) {
                                                            'open' => 'status-active',
                                                            'limited' => 'status-pending',
                                                            default => 'status-inactive',
                                                        };
                                                        $ruleStatusLabel = match ($rule->status) {
                                                            'open' => 'Open',
                                                            'limited' => 'Limited',
                                                            default => 'Closed',
                                                        };
                                                        $ruleStateClass = $rule->is_active
                                                            ? 'status-active'
                                                            : 'status-pending';
                                                    @endphp
                                                    <tr>
                                                        <td data-label="Day(s)">
                                                            <strong>{{ $rule->days_label }}</strong>
                                                        </td>
                                                        <td data-label="Opens">
                                                            {{ $rule->open_time ? date('g:i A', strtotime($rule->open_time)) : '—' }}
                                                        </td>
                                                        <td data-label="Closes">
                                                            {{ $rule->close_time ? date('g:i A', strtotime($rule->close_time)) : '—' }}
                                                        </td>
                                                        <td data-label="Lunch Break">
                                                            @if ($rule->break_time && $rule->break_time !== 'none')
                                                                @php [$bs,$be]=explode('-', $rule->break_time); @endphp
                                                                {{ date('g:i A', strtotime(trim($bs) . ':00')) }} –
                                                                {{ date('g:i A', strtotime(trim($be) . ':00')) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td data-label="Max Slots">
                                                            <div class="flex items-center gap-3">
                                                                <strong>{{ $rule->max_slots }}</strong>

                                                                @if ($rule->status !== 'closed')
                                                                    <div class="cap-bar" aria-hidden="true">
                                                                        <div class="cap-fill"
                                                                            style="width:{{ min(100, ($rule->max_slots / 30) * 100) }}%">
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td data-label="Status">
                                                            <span class="status-pill {{ $ruleStatusClass }}">
                                                                {{ $ruleStatusLabel }}
                                                            </span>
                                                        </td>
                                                        <td data-label="Rule State">
                                                            <span class="status-pill {{ $ruleStateClass }}">
                                                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        </td>
                                                        <td data-label="Actions" class="table-action-cell">
                                                            <div class="ui-action-group">
                                                                <button type="button"
                                                                    onclick='openRuleModal(
                                                                                "edit",
                                                                                {{ $rule->id }},
                                                                                {{ json_encode($rule) }}
                                                                            )'
                                                                    class="ui-action-btn ui-action-edit"
                                                                    data-tooltip="Edit schedule"
                                                                    aria-label="Edit schedule">
                                                                    <i class="fa-solid fa-pen"></i>
                                                                </button>

                                                                <button type="button"
                                                                    class="ui-action-btn ui-action-delete"
                                                                    data-tooltip="Delete schedule"
                                                                    aria-label="Delete schedule"
                                                                    onclick='openScheduleDeleteModal(
                                                                                @json(route($clinicScheduleRouteNames['destroy'], $rule)),
                                                                                @json($rule->days_label)
                                                                            )'>
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div id="scheduleRulesMobileList" class="md:hidden">
                                    @foreach ($schedules as $rule)
                                        @php
                                            $ruleStatusClass = match ($rule->status) {
                                                'open' => 'status-active',
                                                'limited' => 'status-pending',
                                                default => 'status-inactive',
                                            };
                                            $ruleStatusLabel = match ($rule->status) {
                                                'open' => 'Open',
                                                'limited' => 'Limited',
                                                default => 'Closed',
                                            };
                                            $ruleStateClass = $rule->is_active ? 'status-active' : 'status-pending';
                                        @endphp

                                        <div class="table-list-row">
                                            <div class="table-record-card-layout">

                                                <div class="table-record-content">

                                                    <div class="table-record-header">

                                                        <div class="table-primary">
                                                            <strong>{{ $rule->days_label }}</strong>
                                                        </div>

                                                        <div class="global-info-group">
                                                            <span class="status-pill {{ $ruleStatusClass }}">
                                                                {{ $ruleStatusLabel }}
                                                            </span>

                                                            <span class="status-pill {{ $ruleStateClass }}">
                                                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        </div>

                                                    </div>

                                                    <div class="table-record-meta">

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">
                                                                Opens
                                                            </span>

                                                            <span class="table-record-value">
                                                                {{ $rule->open_time ? date('g:i A', strtotime($rule->open_time)) : '—' }}
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">
                                                                Closes
                                                            </span>

                                                            <span class="table-record-value">
                                                                {{ $rule->close_time ? date('g:i A', strtotime($rule->close_time)) : '—' }}
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">
                                                                Lunch Break
                                                            </span>

                                                            <span class="table-record-value">
                                                                @if ($rule->break_time && $rule->break_time !== 'none')
                                                                    @php [$bs,$be] = explode('-', $rule->break_time); @endphp

                                                                    {{ date('g:i A', strtotime(trim($bs) . ':00')) }}
                                                                    –
                                                                    {{ date('g:i A', strtotime(trim($be) . ':00')) }}
                                                                @else
                                                                    —
                                                                @endif
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">
                                                                Max Slots
                                                            </span>

                                                            <span class="table-record-value">
                                                                <strong>{{ $rule->max_slots }}</strong>

                                                                @if ($rule->status !== 'closed')
                                                                    <span class="cap-bar" aria-hidden="true">
                                                                        <span class="cap-fill"
                                                                            style="display:block;width:{{ min(100, ($rule->max_slots / 30) * 100) }}%">
                                                                        </span>
                                                                    </span>
                                                                @endif
                                                            </span>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div class="table-record-actions">
                                                    <div class="ui-action-group">

                                                        <button type="button"
                                                            onclick='openRuleModal(
                            "edit",
                            {{ $rule->id }},
                            {{ json_encode($rule) }}
                        )'
                                                            class="ui-action-btn ui-action-edit"
                                                            data-tooltip="Edit schedule" aria-label="Edit schedule">

                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>

                                                        <button type="button" class="ui-action-btn ui-action-delete"
                                                            data-tooltip="Delete schedule" aria-label="Delete schedule"
                                                            onclick='openScheduleDeleteModal(
                            @json(route($clinicScheduleRouteNames['destroy'], $rule)),
                            @json($rule->days_label)
                        )'>

                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>

                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div id="scheduleRulesGridView" class="table-grid-view" hidden>
                                <div class="table-record-grid">
                                    @foreach ($schedules as $rule)
                                        @php
                                            $ruleStatusClass = match ($rule->status) {
                                                'open' => 'status-active',
                                                'limited' => 'status-pending',
                                                default => 'status-inactive',
                                            };
                                            $ruleStatusLabel = match ($rule->status) {
                                                'open' => 'Open',
                                                'limited' => 'Limited',
                                                default => 'Closed',
                                            };
                                            $ruleStateClass = $rule->is_active ? 'status-active' : 'status-pending';
                                        @endphp

                                        <article class="table-record-card">
                                            <div class="table-record-card-layout">
                                                <div class="table-record-content">
                                                    <div class="table-record-header">
                                                        <div class="table-primary">
                                                            <h3 class="table-record-title">{{ $rule->days_label }}
                                                            </h3>
                                                        </div>

                                                        <div class="global-info-group">
                                                            <span class="status-pill {{ $ruleStatusClass }}">
                                                                {{ $ruleStatusLabel }}
                                                            </span>
                                                            <span class="status-pill {{ $ruleStateClass }}">
                                                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="table-record-meta">
                                                        <div class="table-record-row">
                                                            <span class="table-record-label">Opens</span>
                                                            <span class="table-record-value">
                                                                {{ $rule->open_time ? date('g:i A', strtotime($rule->open_time)) : '—' }}
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">Closes</span>
                                                            <span class="table-record-value">
                                                                {{ $rule->close_time ? date('g:i A', strtotime($rule->close_time)) : '—' }}
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">Lunch Break</span>
                                                            <span class="table-record-value">
                                                                @if ($rule->break_time && $rule->break_time !== 'none')
                                                                    @php [$bs,$be]=explode('-', $rule->break_time); @endphp
                                                                    {{ date('g:i A', strtotime(trim($bs) . ':00')) }} –
                                                                    {{ date('g:i A', strtotime(trim($be) . ':00')) }}
                                                                @else
                                                                    —
                                                                @endif
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">
                                                                Max Slots
                                                            </span>

                                                            <span class="table-record-value">
                                                                <strong>{{ $rule->max_slots }}</strong>

                                                                @if ($rule->status !== 'closed')
                                                                    <span class="cap-bar" aria-hidden="true">
                                                                        <span class="cap-fill"
                                                                            style="display:block;width:{{ min(100, ($rule->max_slots / 30) * 100) }}%">
                                                                        </span>
                                                                    </span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="table-record-actions">
                                                    <div class="ui-action-group">
                                                        <button type="button"
                                                            onclick='openRuleModal(
                                                                        "edit",
                                                                        {{ $rule->id }},
                                                                        {{ json_encode($rule) }}
                                                                    )'
                                                            class="ui-action-btn ui-action-edit"
                                                            data-tooltip="Edit schedule" aria-label="Edit schedule">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>

                                                        <button type="button" class="ui-action-btn ui-action-delete"
                                                            data-tooltip="Delete schedule" aria-label="Delete schedule"
                                                            onclick='openScheduleDeleteModal(
                                                                        @json(route($clinicScheduleRouteNames['destroy'], $rule)),
                                                                        @json($rule->days_label)
                                                                    )'>
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div id="scheduleRulesEmptyState" class="empty-state-host"></div>
                        @endif
                    </section>

                    <section id="reservedPeriodsCard" class="card">
                        <div class="card-header reserved-periods-header">

                            <div class="card-header-left">
                                <span class="card-header-icon" aria-hidden="true">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </span>

                                <h2 class="card-title">
                                    Reserved Booking Periods
                                </h2>
                            </div>

                            <div class="card-header-right reserved-periods-header-actions">

                                <x-view-toggle id="reservedPeriodsViewToggle" storage-key="reservedPeriodsView"
                                    list-view="#reservedPeriodsListView" grid-view="#reservedPeriodsGridView" />

                                @if ($canCreateReservedPeriods)
                                    <button type="button" onclick="openReservedPeriodModal()"
                                        class="ui-btn ui-btn-primary ui-btn-sm" data-tooltip="Add reserved booking period"
                                        aria-label="Add reserved booking period">

                                        <i class="fa-solid fa-plus"></i>

                                        <span class="reserved-period-add-label">
                                            Add Period
                                        </span>

                                    </button>
                                @endif

                            </div>

                        </div>

                        @if ($reservedBookingPeriods->count())
                            <div id="reservedPeriodsListView" class="table-list-view reserved-periods-list-view">
                                <div class="reserved-periods-desktop-list">
                                    <div class="table-scroll">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Schedule</th>
                                                    <th>Purpose</th>
                                                    <th>Target Group</th>
                                                    <th>Booking</th>
                                                    <th>Capacity</th>
                                                    <th>Status</th>
                                                    @if ($canManageReservedPeriods)
                                                        <th>Actions</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($reservedBookingPeriods as $period)
                                                    @php
                                                        $isPastPeriod = \Carbon\Carbon::parse($period->reserved_date)
                                                            ->startOfDay()
                                                            ->lt(\Carbon\Carbon::today());
                                                        $periodPayload = [
                                                            'id' => $period->id,
                                                            'title' => $period->title,
                                                            'is_active' => (bool) $period->is_active,
                                                            'reserved_date' => optional($period->reserved_date)->format(
                                                                'Y-m-d',
                                                            ),
                                                            'start_time' => $period->start_time,
                                                            'end_time' => $period->end_time,
                                                            'target_patient_type' => $period->target_patient_type,
                                                            'allowed_services' => $period->allowed_services,
                                                            'program_code' => $period->program_code,
                                                            'year_level' => $period->year_level,
                                                            'section' => $period->section,
                                                            'max_capacity' => $period->max_capacity,
                                                            'timeslot_duration_minutes' =>
                                                                $period->timeslot_duration_minutes,
                                                            'notes' => $period->notes,
                                                            'booking_mode' => $period->booking_mode,
                                                            'timeslots' => $period->slots
                                                                ->map(fn($slot) => ['time' => $slot->slot_time])
                                                                ->values()
                                                                ->all(),
                                                        ];
                                                        $periodStatusClass = $isPastPeriod
                                                            ? 'status-inactive'
                                                            : ($period->is_active
                                                                ? 'status-active'
                                                                : 'status-pending');
                                                        $periodStatusLabel = $isPastPeriod
                                                            ? 'Past'
                                                            : ($period->is_active
                                                                ? 'Active'
                                                                : 'Inactive');
                                                    @endphp

                                                    <tr>
                                                        <td data-label="Schedule">
                                                            <div class="flex flex-col items-start gap-1">

                                                                <div class="table-primary">
                                                                    <strong>
                                                                        {{ \Carbon\Carbon::parse($period->reserved_date)->format('M d, Y') }}
                                                                    </strong>
                                                                </div>

                                                                <div class="table-date">
                                                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>

                                                                    <span>
                                                                        {{ date('g:i A', strtotime($period->start_time)) }}
                                                                        –
                                                                        {{ date('g:i A', strtotime($period->end_time)) }}
                                                                    </span>
                                                                </div>

                                                            </div>
                                                        </td>
                                                        <td data-label="Purpose" class="table-cell-main">
                                                            <div class="table-primary">
                                                                <strong>{{ $period->title }}</strong>
                                                            </div>
                                                            <div class="global-info-group mt-1">
                                                                @if ($period->allowed_services === null)
                                                                    <span class="service-badge service-badge-default">
                                                                        All dental services
                                                                    </span>
                                                                @else
                                                                    @foreach ($period->allowed_services as $service)
                                                                        <span
                                                                            class="service-badge {{ $serviceBadgeClass($service) }}">
                                                                            {{ $service }}
                                                                        </span>
                                                                    @endforeach
                                                                @endif
                                                            </div>
                                                            @if ($period->notes)
                                                                <span class="global-info-subvalue">
                                                                    {{ $period->notes }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td data-label="Target Group">
                                                            <span class="status-pill status-all">
                                                                {{ $period->target_label }}
                                                            </span>
                                                        </td>
                                                        <td data-label="Booking">
                                                            @if ($period->booking_mode === 'timeslot')
                                                                <span class="global-info-subvalue">
                                                                    {{ $period->slots->count() }} selectable
                                                                    {{ \Illuminate\Support\Str::plural('slot', $period->slots->count()) }}
                                                                    · {{ $period->timeslot_duration_minutes }} min each
                                                                </span>
                                                            @else
                                                                Date only
                                                            @endif
                                                        </td>

                                                        <td data-label="Capacity">
                                                            <strong>{{ $period->max_capacity }}</strong>

                                                            <span class="global-info-subvalue">
                                                                {{ \Illuminate\Support\Str::plural('patient', $period->max_capacity) }}
                                                            </span>
                                                        </td>
                                                        <td data-label="Status">
                                                            <span class="status-pill {{ $periodStatusClass }}">
                                                                {{ $periodStatusLabel }}
                                                            </span>
                                                        </td>
                                                        @if ($canManageReservedPeriods)
                                                            <td data-label="Actions" class="table-action-cell">
                                                                <div class="ui-action-group">
                                                                    @if ($canUpdateReservedPeriods && !$isPastPeriod)
                                                                        <button type="button"
                                                                            onclick='openReservedPeriodModal("edit", {{ $period->id }}, @json($periodPayload))'
                                                                            class="ui-action-btn ui-action-edit"
                                                                            data-tooltip="Edit reserved period"
                                                                            aria-label="Edit reserved period">
                                                                            <i class="fa-solid fa-pen"></i>
                                                                        </button>
                                                                    @endif

                                                                    @if ($canDeleteReservedPeriods)
                                                                        <button type="button"
                                                                            class="ui-action-btn ui-action-delete"
                                                                            data-tooltip="Remove reserved period"
                                                                            aria-label="Remove reserved period"
                                                                            onclick='openReservedPeriodDeleteModal(
                                                                                @json(route($clinicScheduleRouteNames['reserved_destroy'], $period)),
                                                                                @json($period->title)
                                                                            )'>
                                                                            <i class="fa-solid fa-trash"></i>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="reserved-periods-compact-list">
                                    @foreach ($reservedBookingPeriods as $period)
                                        @php
                                            $isPastPeriod = \Carbon\Carbon::parse($period->reserved_date)
                                                ->startOfDay()
                                                ->lt(\Carbon\Carbon::today());
                                            $periodPayload = [
                                                'id' => $period->id,
                                                'title' => $period->title,
                                                'is_active' => (bool) $period->is_active,
                                                'reserved_date' => optional($period->reserved_date)->format('Y-m-d'),
                                                'start_time' => $period->start_time,
                                                'end_time' => $period->end_time,
                                                'target_patient_type' => $period->target_patient_type,
                                                'allowed_services' => $period->allowed_services,
                                                'program_code' => $period->program_code,
                                                'year_level' => $period->year_level,
                                                'section' => $period->section,
                                                'max_capacity' => $period->max_capacity,
                                                'timeslot_duration_minutes' => $period->timeslot_duration_minutes,
                                                'notes' => $period->notes,
                                                'booking_mode' => $period->booking_mode,
                                                'timeslots' => $period->slots
                                                    ->map(fn($slot) => ['time' => $slot->slot_time])
                                                    ->values()
                                                    ->all(),
                                            ];
                                            $periodStatusClass = $isPastPeriod
                                                ? 'status-inactive'
                                                : ($period->is_active
                                                    ? 'status-active'
                                                    : 'status-pending');
                                            $periodStatusLabel = $isPastPeriod
                                                ? 'Past'
                                                : ($period->is_active
                                                    ? 'Active'
                                                    : 'Inactive');
                                        @endphp

                                        <div class="table-list-row reserved-periods-list-row">
                                            <div class="reserved-periods-list-layout">
                                                <div class="reserved-periods-list-heading">
                                                    <div class="table-primary">
                                                        <strong>{{ $period->title }}</strong>
                                                    </div>

                                                    <div class="global-info-group reserved-periods-service-badges">
                                                        @if ($period->allowed_services === null)
                                                            <span class="service-badge service-badge-default">
                                                                All dental services
                                                            </span>
                                                        @else
                                                            @foreach ($period->allowed_services as $service)
                                                                <span
                                                                    class="service-badge {{ $serviceBadgeClass($service) }}">
                                                                    {{ $service }}
                                                                </span>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="reserved-periods-list-status">
                                                    <span class="status-pill {{ $periodStatusClass }}">
                                                        {{ $periodStatusLabel }}
                                                    </span>
                                                </div>

                                                <div class="reserved-periods-list-details">
                                                    <div class="reserved-periods-list-detail">
                                                        <span class="reserved-periods-list-key">Schedule</span>
                                                        <span class="reserved-periods-list-value">
                                                            {{ \Carbon\Carbon::parse($period->reserved_date)->format('M d, Y') }}
                                                            <span class="reserved-periods-list-subvalue">
                                                                {{ date('g:i A', strtotime($period->start_time)) }}–{{ date('g:i A', strtotime($period->end_time)) }}
                                                            </span>
                                                        </span>
                                                    </div>

                                                    <div class="reserved-periods-list-detail">
                                                        <span class="reserved-periods-list-key">Target</span>
                                                        <span class="reserved-periods-list-value">
                                                            {{ $period->target_label }}
                                                        </span>
                                                    </div>

                                                    <div class="reserved-periods-list-detail">
                                                        <span class="reserved-periods-list-key">Booking</span>

                                                        <span class="reserved-periods-list-value">
                                                            @if ($period->booking_mode === 'timeslot')
                                                                {{ $period->slots->count() }} selectable
                                                                {{ \Illuminate\Support\Str::plural('slot', $period->slots->count()) }}
                                                                · {{ $period->timeslot_duration_minutes }} min each
                                                            @else
                                                                Date only
                                                            @endif
                                                        </span>
                                                    </div>

                                                    <div class="reserved-periods-list-detail">
                                                        <span class="reserved-periods-list-key">Capacity</span>

                                                        <span class="reserved-periods-list-value">
                                                            <strong>{{ $period->max_capacity }}</strong>
                                                            {{ \Illuminate\Support\Str::plural('patient', $period->max_capacity) }}
                                                        </span>
                                                    </div>
                                                </div>

                                                @if ($period->notes)
                                                    <div class="reserved-periods-list-note">
                                                        {{ $period->notes }}
                                                    </div>
                                                @endif

                                                @if ($canManageReservedPeriods)
                                                    <div class="reserved-periods-list-actions table-action-cell">
                                                        <div class="ui-action-group">
                                                            @if ($canUpdateReservedPeriods && !$isPastPeriod)
                                                                <button type="button"
                                                                    onclick='openReservedPeriodModal("edit", {{ $period->id }}, @json($periodPayload))'
                                                                    class="ui-action-btn ui-action-edit"
                                                                    data-tooltip="Edit reserved period"
                                                                    aria-label="Edit reserved period">
                                                                    <i class="fa-solid fa-pen"></i>
                                                                </button>
                                                            @endif

                                                            @if ($canDeleteReservedPeriods)
                                                                <button type="button"
                                                                    class="ui-action-btn ui-action-delete"
                                                                    data-tooltip="Remove reserved period"
                                                                    aria-label="Remove reserved period"
                                                                    onclick='openReservedPeriodDeleteModal(
                                                                        @json(route($clinicScheduleRouteNames['reserved_destroy'], $period)),
                                                                        @json($period->title)
                                                                    )'>
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div id="reservedPeriodsGridView" class="table-grid-view" hidden>
                                <div class="table-record-grid">
                                    @foreach ($reservedBookingPeriods as $period)
                                        @php
                                            $isPastPeriod = \Carbon\Carbon::parse($period->reserved_date)
                                                ->startOfDay()
                                                ->lt(\Carbon\Carbon::today());
                                            $periodPayload = [
                                                'id' => $period->id,
                                                'title' => $period->title,
                                                'is_active' => (bool) $period->is_active,
                                                'reserved_date' => optional($period->reserved_date)->format('Y-m-d'),
                                                'start_time' => $period->start_time,
                                                'end_time' => $period->end_time,
                                                'target_patient_type' => $period->target_patient_type,
                                                'allowed_services' => $period->allowed_services,
                                                'program_code' => $period->program_code,
                                                'year_level' => $period->year_level,
                                                'section' => $period->section,
                                                'max_capacity' => $period->max_capacity,
                                                'timeslot_duration_minutes' => $period->timeslot_duration_minutes,
                                                'notes' => $period->notes,
                                                'booking_mode' => $period->booking_mode,
                                                'timeslots' => $period->slots
                                                    ->map(fn($slot) => ['time' => $slot->slot_time])
                                                    ->values()
                                                    ->all(),
                                            ];
                                            $periodStatusClass = $isPastPeriod
                                                ? 'status-inactive'
                                                : ($period->is_active
                                                    ? 'status-active'
                                                    : 'status-pending');
                                            $periodStatusLabel = $isPastPeriod
                                                ? 'Past'
                                                : ($period->is_active
                                                    ? 'Active'
                                                    : 'Inactive');
                                        @endphp

                                        <article class="table-record-card">
                                            <div class="table-record-card-layout">
                                                <div class="table-record-content">
                                                    <div class="table-record-header">
                                                        <div class="table-primary">
                                                            <h3 class="table-record-title">{{ $period->title }}</h3>
                                                        </div>

                                                        <span class="status-pill {{ $periodStatusClass }}">
                                                            {{ $periodStatusLabel }}
                                                        </span>
                                                    </div>

                                                    <div class="global-info-group">
                                                        @if ($period->allowed_services === null)
                                                            <span class="service-badge service-badge-default">
                                                                All dental services
                                                            </span>
                                                        @else
                                                            @foreach ($period->allowed_services as $service)
                                                                <span
                                                                    class="service-badge {{ $serviceBadgeClass($service) }}">
                                                                    {{ $service }}
                                                                </span>
                                                            @endforeach
                                                        @endif
                                                    </div>

                                                    <div class="table-record-meta reserved-period-grid-details">
                                                        <div class="table-record-row">
                                                            <span class="table-record-label">Schedule</span>
                                                            <span class="table-record-value">
                                                                {{ \Carbon\Carbon::parse($period->reserved_date)->format('M d, Y') }},
                                                                {{ date('g:i A', strtotime($period->start_time)) }}–{{ date('g:i A', strtotime($period->end_time)) }}
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">Target</span>
                                                            <span class="table-record-value">
                                                                {{ $period->target_label }}
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">Booking</span>

                                                            <span class="table-record-value">
                                                                @if ($period->booking_mode === 'timeslot')
                                                                    {{ $period->slots->count() }} selectable
                                                                    {{ \Illuminate\Support\Str::plural('slot', $period->slots->count()) }}
                                                                    · {{ $period->timeslot_duration_minutes }} min each
                                                                @else
                                                                    Date only
                                                                @endif
                                                            </span>
                                                        </div>

                                                        <div class="table-record-row">
                                                            <span class="table-record-label">Capacity</span>

                                                            <span class="table-record-value">
                                                                {{ $period->max_capacity }}
                                                                {{ \Illuminate\Support\Str::plural('patient', $period->max_capacity) }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    @if ($period->notes)
                                                        <span class="global-info-subvalue">{{ $period->notes }}</span>
                                                    @endif
                                                </div>

                                                @if ($canManageReservedPeriods)
                                                    <div class="table-record-actions">
                                                        <div class="ui-action-group">
                                                            @if ($canUpdateReservedPeriods && !$isPastPeriod)
                                                                <button type="button"
                                                                    onclick='openReservedPeriodModal("edit", {{ $period->id }}, @json($periodPayload))'
                                                                    class="ui-action-btn ui-action-edit"
                                                                    data-tooltip="Edit reserved period"
                                                                    aria-label="Edit reserved period">
                                                                    <i class="fa-solid fa-pen"></i>
                                                                </button>
                                                            @endif

                                                            @if ($canDeleteReservedPeriods)
                                                                <button type="button"
                                                                    class="ui-action-btn ui-action-delete"
                                                                    data-tooltip="Remove reserved period"
                                                                    aria-label="Remove reserved period"
                                                                    onclick='openReservedPeriodDeleteModal(
                                                                            @json(route($clinicScheduleRouteNames['reserved_destroy'], $period)),
                                                                            @json($period->title)
                                                                        )'>
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="card-body">
                                <div id="reservedPeriodsEmptyState" class="empty-state-host"></div>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </main>

    <div id="holidayListModal" class="ui-modal" aria-hidden="true">
        <div class="ui-modal-card modal-md" role="dialog" aria-modal="true" aria-labelledby="holidayListModalTitle"
            onclick="event.stopPropagation()">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-umbrella-beach"></i>
                    </div>
                    <div class="modal-copy">
                        <h3 id="holidayListModalTitle" class="modal-title">
                            Upcoming Holidays
                        </h3>
                        <p class="modal-subtitle">
                            Working and non-working holidays
                        </p>
                    </div>
                </div>
                <button type="button" class="modal-x" onclick="window.closeModal('holidayListModal')"
                    aria-label="Close holiday list">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd modal-scroll-body">
                <div class="table-list-view">
                    @foreach ($allUpcomingHolidays as $hDate => $holiday)
                        @php
                            $hC = \Carbon\Carbon::parse($hDate);

                            $diff = (int) $today->diffInDays($hC, false);

                            $holidayName = is_array($holiday)
                                ? $holiday['name'] ?? 'Philippine Holiday'
                                : (string) $holiday;

                            $isBlockedHoliday = is_array($holiday) ? $holiday['is_blocked_for_booking'] ?? true : true;
                        @endphp

                        <div class="table-list-row">
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div class="w-11 shrink-0 text-center">
                                    <div class="appt-visit-month">
                                        {{ $MONTHS_SHORT[$hC->month - 1] }}
                                    </div>

                                    <div class="text-xl font-extrabold leading-none" style="color: var(--text-1);">
                                        {{ $hC->day }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="appt-visit-title">
                                        {{ $holidayName }}
                                    </div>
                                    <span class="global-info-subvalue">
                                        {{ $diff === 0 ? 'Today' : ($diff === 1 ? 'Tomorrow' : "In $diff days") }}
                                    </span>
                                </div>

                                <div class="shrink-0">
                                    @if ($isBlockedHoliday)
                                        <span class="cal-pill cal-pill-yellow">
                                            <i class="fa-solid fa-star text-[10px]"></i>
                                            Non-Working
                                        </span>
                                    @else
                                        <span class="cal-pill cal-pill-working-holiday">
                                            <i class="fa-solid fa-briefcase text-[10px]"></i>
                                            Working Holiday
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" onclick="window.closeModal('holidayListModal')">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="appointmentDetailModal" class="ui-modal">
        <div class="ui-modal-card modal-md" onclick="event.stopPropagation()">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">Appointment Details</h3>
                        <p class="modal-subtitle">Selected booked slot information</p>
                    </div>
                </div>

                <button type="button" class="modal-x" onclick="closeAppointmentDetailModal()"
                    aria-label="Close appointment details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd modal-scroll-body">
                <div class="modal-form-grid">
                    <div>
                        <label class="form-label">Patient Name</label>
                        <div id="detailPatientName" class="global-readonly-field">—</div>
                    </div>

                    <div>
                        <label class="form-label">Service Type</label>
                        <div id="detailServiceType" class="global-readonly-field">—</div>
                    </div>

                    <div>
                        <label class="form-label">Schedule</label>
                        <div id="detailSchedule" class="global-readonly-field">—</div>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" onclick="closeAppointmentDetailModal()" class="ui-btn ui-btn-primary">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="ruleModalBackdrop" class="ui-modal modal-theme-primary">
        <div class="ui-modal-card modal-xl" onclick="event.stopPropagation()">

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-calendar-plus" id="ruleModalIcon"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title" id="ruleModalTitle">
                            Add Schedule Rule
                        </h3>

                        <p class="modal-subtitle" id="ruleModalSubtitle">
                            Choose clinic days, set operating hours,
                            and control booking capacity.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="ruleModalBackdrop"
                    aria-label="Close rule modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="ruleForm" method="POST" action="{{ route($clinicScheduleRouteNames['store']) }}"
                class="modal-card-form" data-global-validation data-discard-form
                data-form-validation-rule="clinicScheduleRule" novalidate>
                @csrf
                <div id="ruleMethodField"></div>
                <div class="modal-bd modal-scroll-body modal-form-grid-2">
                    <div class="flex flex-col gap-4">

                        <div class="info-card modal-form-section m-0">
                            <div class="modal-section-heading">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>

                                <div>
                                    <h4>
                                        Applicable Days
                                    </h4>

                                    <p>
                                        Select one or more days for this rule.
                                    </p>
                                </div>
                            </div>

                            <div data-global-field>
                                <label class="form-label">
                                    Select Days
                                    <span class="text-red-400">*</span>
                                </label>

                                <div id="ruleDaysGroup" class="day-toggle-group mt-1" tabindex="-1">
                                    @foreach ([
            'Mon' => 'M',
            'Tue' => 'T',
            'Wed' => 'W',
            'Thu' => 'Th',
            'Fri' => 'F',
            'Sat' => 'S',
            'Sun' => 'Su',
        ] as $abbr => $lbl)
                                        <button type="button" class="day-toggle" data-day="{{ $abbr }}"
                                            data-discard-track data-discard-key="schedule-day-{{ $abbr }}"
                                            data-discard-value="false" onclick="toggleDay(this)" aria-pressed="false">

                                            <span class="day-toggle-label">
                                                {{ $lbl }}
                                            </span>

                                            <span class="day-toggle-check" aria-hidden="true">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="field-help">
                                    You can apply one schedule to multiple weekdays.
                                </div>
                                <div id="ruleDaysError" class="global-field-error" data-error-for="ruleDaysGroup"
                                    aria-hidden="true">
                                </div>
                            </div>
                        </div>

                        <div class="info-card modal-form-section m-0 flex-1" data-global-field>

                            <div class="modal-section-heading">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-note-sticky"></i>
                                </div>

                                <div>
                                    <h4>
                                        Additional Notes
                                    </h4>

                                    <p>
                                        Optional reminder or exception.
                                    </p>
                                </div>
                            </div>

                            <div class="global-label-row">
                                <label class="form-label" for="ruleNotes">
                                    Notes
                                    <span class="field-optional">optional</span>
                                </label>
                            </div>

                            <div class="global-voice-row is-textarea rule-notes-field mb-2" data-voice-field>

                                <div class="global-voice-control" data-clearable-field>

                                    <div class="global-form-textarea-wrap rule-notes-textarea-wrap">

                                        <textarea id="ruleNotes" name="notes" class="form-input-custom global-form-textarea rule-notes-textarea"
                                            maxlength="150" data-char-limit="150" data-char-counter="#ruleNotesCount"
                                            placeholder="e.g. Reduced operations due to holiday program." data-clearable-input></textarea>

                                        <button type="button" id="ruleNotesClearBtn"
                                            class="search-clear field-clear-btn field-clear-btn--textarea" data-field-clear
                                            aria-label="Clear notes" title="Clear notes">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>

                                        <span id="ruleNotesCount" class="char-counter">
                                            0 / 150 characters
                                        </span>

                                    </div>
                                </div>

                                <x-voice-input target="#ruleNotes" status-id="ruleNotesVoiceStatus"
                                    label="Voice input for schedule notes" title="Voice input" />

                            </div>

                            <div id="ruleNotesError" class="global-field-error" data-error-for="ruleNotes"
                                aria-hidden="true">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col h-full">
                        <div class="info-card modal-form-section m-0 flex-1">
                            <div class="modal-section-heading">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-hospital-user"></i>
                                </div>

                                <div>
                                    <h4>
                                        Clinic Availability
                                    </h4>

                                    <p>
                                        Define whether the clinic is open, closed, or limited.
                                    </p>
                                </div>
                            </div>

                            <div class="modal-form-grid">
                                <div data-global-field>
                                    <label class="form-label" for="ruleActivationState">
                                        Schedule State
                                    </label>

                                    <select id="ruleActivationState" class="form-select-custom js-custom-select"
                                        data-placeholder="Select schedule state">
                                        <option value="0" selected>Inactive</option>
                                        <option value="1">Active</option>
                                    </select>

                                    <div class="field-help">
                                        New schedule rules start as Inactive. Only one clinic schedule can be Active
                                        at a time. Set the current Active schedule to Inactive before activating
                                        another schedule.
                                    </div>

                                    <div id="ruleStateError" class="global-field-error"
                                        data-error-for="ruleActivationState" aria-hidden="true">
                                    </div>
                                </div>

                                <div data-global-field>
                                    <label class="form-label" for="ruleStatus">
                                        Clinic Status
                                    </label>

                                    <select id="ruleStatus" class="form-select-custom js-custom-select"
                                        data-placeholder="Select clinic status" onchange="toggleStatusFields(this.value)">
                                        <option value="open">Open</option>
                                        <option value="closed">Closed</option>
                                        <option value="limited">Limited Hours</option>
                                    </select>
                                    <div id="ruleStatusError" class="global-field-error" data-error-for="ruleStatus"
                                        aria-hidden="true">
                                    </div>
                                </div>

                                <div id="ruleTimeFields" class="modal-form-grid">

                                    <div class="modal-form-grid-2">

                                        <div data-global-field>
                                            <label class="form-label" for="ruleOpenTime">
                                                Opening Time
                                            </label>

                                            <select id="ruleOpenTime" class="form-select-custom js-custom-select"
                                                data-placeholder="Select opening time">

                                                <option value="07:00">7:00 AM</option>
                                                <option value="08:00">8:00 AM</option>
                                                <option value="09:00" selected>9:00 AM</option>
                                                <option value="10:00">10:00 AM</option>

                                            </select>

                                            <div id="ruleOpenTimeError" class="global-field-error"
                                                data-error-for="ruleOpenTime" aria-hidden="true">
                                            </div>
                                        </div>


                                        <div data-global-field>
                                            <label class="form-label" for="ruleCloseTime">
                                                Closing Time
                                            </label>

                                            <select id="ruleCloseTime" class="form-select-custom js-custom-select"
                                                data-placeholder="Select closing time">

                                                <option value="15:00">3:00 PM</option>
                                                <option value="16:00">4:00 PM</option>
                                                <option value="17:00" selected>5:00 PM</option>
                                                <option value="18:00">6:00 PM</option>

                                            </select>

                                            <div id="ruleCloseTimeError" class="global-field-error"
                                                data-error-for="ruleCloseTime" aria-hidden="true">
                                            </div>
                                        </div>

                                    </div>


                                    <div data-global-field>
                                        <label class="form-label" for="ruleMaxSlots">
                                            Max Appointments / Day
                                        </label>

                                        <div class="global-number-stepper mt-1" data-global-number-stepper>

                                            <button type="button" class="global-number-stepper-btn"
                                                data-number-step="-1" aria-label="Decrease maximum appointments">

                                                <i class="fa-solid fa-minus"></i>
                                            </button>

                                            <input type="number" id="ruleMaxSlots" class="global-number-stepper-input"
                                                value="5" min="1" max="30" step="1"
                                                inputmode="numeric" autocomplete="off" data-number-stepper-input
                                                data-field-label="Max Appointments" data-validation-rule="wholeNumber">

                                            <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                                aria-label="Increase maximum appointments">

                                                <i class="fa-solid fa-plus"></i>
                                            </button>

                                        </div>

                                        <div id="ruleMaxSlotsError" class="global-field-error"
                                            data-error-for="ruleMaxSlots" aria-hidden="true">
                                        </div>

                                        <div class="field-help">
                                            Set how many appointments may be accepted per day, from 1 to 30.
                                        </div>
                                    </div>


                                    <div data-global-field>
                                        <label class="form-label">
                                            Lunch Break
                                        </label>

                                        <div id="ruleBreakGroup" class="break-chip-group">

                                            <button type="button" class="break-chip selected" data-val="12:00-13:00"
                                                data-discard-track data-discard-key="schedule-break-12-13"
                                                data-discard-value="true" onclick="selectBreak(this)">
                                                12:00 – 1:00 PM
                                            </button>

                                            <button type="button" class="break-chip" data-val="13:00-14:00"
                                                data-discard-track data-discard-key="schedule-break-13-14"
                                                data-discard-value="false" onclick="selectBreak(this)">
                                                1:00 – 2:00 PM
                                            </button>

                                            <button type="button" class="break-chip" data-val="none" data-discard-track
                                                data-discard-key="schedule-break-none" data-discard-value="false"
                                                onclick="selectBreak(this)">

                                                <i class="fa-solid fa-ban text-[10px]"></i>
                                                No Break
                                            </button>

                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-ft modal-sticky-footer">
                    <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="ruleModalBackdrop">
                        Cancel
                    </button>

                    <button type="button" onclick="submitRule()" id="ruleSubmitBtn" class="ui-btn ui-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span id="ruleSubmitText">Save Rule</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="blockModalBackdrop" class="ui-modal modal-theme-danger" aria-hidden="true">

        <form id="blockDateForm" action="{{ route($clinicScheduleRouteNames['block']) }}" method="POST"
            class="ui-modal-card modal-md modal-card-form" role="dialog" aria-modal="true"
            aria-labelledby="blockDateModalTitle" data-global-validation data-discard-form
            data-discard-title="Discard blocked date?" data-discard-subtitle="You have unsaved blocked-date details."
            data-discard-message="Closing this modal will remove the blocked-date details you entered."
            onclick="event.stopPropagation()" novalidate>
            @csrf

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title" id="blockDateModalTitle">
                            Block Date
                        </h3>

                        <p class="modal-subtitle">
                            Prevent appointments from being booked on a specific date.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="blockModalBackdrop"
                    aria-label="Close block date modal">

                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd modal-scroll-body">
                <div class="info-card modal-form-section">
                    <div class="modal-section-heading">
                        <div class="modal-section-icon">
                            <i class="fa-solid fa-calendar-xmark"></i>
                        </div>

                        <div>
                            <h4>
                                Date Details
                            </h4>

                            <p>
                                Choose the blocked date and specify the reason.
                            </p>
                        </div>
                    </div>

                    <div class="mb-4" data-global-field>

                        <label class="form-label" for="blockDate">

                            Date
                            <span class="required-mark">*</span>
                        </label>

                        <div class="fp-date-input-wrap">
                            <input type="text" id="blockDate" name="date"
                                class="form-input-custom fp-date-input js-flatpickr-date-min-today"
                                data-flatpickr-append-to-body min="{{ date('Y-m-d') }}" data-field-label="Date"
                                data-required-message="Please select a date." data-validation-rule="clinicFutureOrToday"
                                placeholder="Select blocked date" required readonly>
                            <i class="fa-solid fa-calendar-days fp-date-icon"></i>
                        </div>
                        <div id="blockDateError" class="global-field-error" data-error-for="blockDate"
                            aria-hidden="true">
                        </div>
                    </div>

                    <div class="mb-4" data-global-field>

                        <label class="form-label" for="blockReason">
                            Reason
                            <span class="required-mark">*</span>
                        </label>

                        <select id="blockReason" name="reason" class="form-select-custom js-custom-select"
                            data-placeholder="Select reason" data-field-label="Reason" required>

                            <option value="Holiday">Holiday</option>
                            <option value="Dentist Unavailable">
                                Dentist Unavailable
                            </option>
                            <option value="Clinic Maintenance">
                                Clinic Maintenance
                            </option>
                            <option value="Special Event">
                                Special Event
                            </option>
                            <option value="Other">Other</option>
                        </select>
                        <div id="blockReasonError" class="global-field-error" data-error-for="blockReason"
                            aria-hidden="true">
                        </div>
                    </div>

                    <div data-global-field>

                        <div class="global-label-row">
                            <label class="form-label" for="blockNote">
                                Notes
                                <span class="field-optional">optional</span>
                            </label>

                            <span id="blockNoteCount" class="char-counter">
                                0 / 150 characters
                            </span>
                        </div>

                        <div class="global-voice-row">
                            <div class="global-voice-control">

                                <input type="text" id="blockNote" name="note" class="form-input-custom"
                                    maxlength="150" data-char-limit="150" data-char-counter="#blockNoteCount"
                                    placeholder="e.g. National holiday, maintenance, outreach event...">

                            </div>

                            <x-voice-input target="#blockNote" status-id="blockNoteVoiceStatus"
                                label="Voice input for blocked date note" title="Voice input" />
                        </div>

                        <div id="blockNoteError" class="global-field-error" data-error-for="blockNote"
                            aria-hidden="true">
                        </div>

                        <div class="field-help">
                            Add extra context for admins viewing blocked dates later.
                        </div>

                    </div>
                </div>
            </div>

            <div class="modal-ft modal-sticky-footer">
                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="blockModalBackdrop">
                    Cancel
                </button>

                <button type="submit" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-ban"></i>
                    <span>Block Date</span>
                </button>
            </div>
        </form>
    </div>

    <div id="reservedPeriodModalBackdrop" class="ui-modal modal-theme-primary" aria-hidden="true">
        <div class="ui-modal-card modal-xl" onclick="event.stopPropagation()">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i id="reservedPeriodModalIcon" class="fa-solid fa-calendar-check"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 id="reservedPeriodModalTitle" class="modal-title">Create Reserved Booking Period</h3>
                        <p id="reservedPeriodModalSubtitle" class="modal-subtitle">
                            Reserve part of a clinic day for a selected patient group.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="reservedPeriodModalBackdrop"
                    aria-label="Close reserved period modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="reservedPeriodForm" method="POST"
                action="{{ route($clinicScheduleRouteNames['reserved_store']) }}" class="modal-card-form"
                data-global-validation data-form-validation-rule="reservedBookingPeriod" data-discard-form novalidate>

                <div class="modal-bd modal-scroll-body">
                    @csrf
                    <div id="reservedPeriodMethodField"></div>
                    <input id="reservedPeriodId" type="hidden" name="reserved_period_id">
                    <input type="hidden" name="allowed_services_present" value="1">

                    <div class="modal-form-grid-2">
                        <div class="info-card modal-form-section">
                            <div class="modal-section-heading">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-calendar-day"></i>
                                </div>
                                <div>
                                    <h4>Period Details</h4>
                                    <p>Set the purpose, date, and reserved hours.</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div data-global-field>
                                    <label for="reservedTitle" class="form-label">Purpose <span
                                            class="text-red-500">*</span></label>
                                    <div class="global-voice-row" data-voice-field>
                                        <div class="global-voice-control">
                                            <input id="reservedTitle" name="title" type="text" maxlength="120"
                                                required
                                                class="form-input-custom @error('title', 'reservedPeriod') is-invalid @enderror"
                                                data-field-label="Purpose" placeholder="e.g. Mandatory Oral Check-up">
                                        </div>

                                        <x-voice-input target="#reservedTitle" status-id="reservedTitleVoiceStatus"
                                            label="Voice input for reserved period purpose" title="Voice input" />
                                    </div>
                                    <div class="global-field-error @error('title', 'reservedPeriod') show @enderror"
                                        data-error-for="reservedTitle"
                                        aria-hidden="{{ $reservedErrors->has('title') ? 'false' : 'true' }}">
                                        @error('title', 'reservedPeriod')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>

                                <div data-global-field>
                                    <label for="reservedDate" class="form-label">
                                        Date
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <div class="fp-date-input-wrap" data-flatpickr-trigger>
                                        <input id="reservedDate" name="reserved_date" type="text" required readonly
                                            data-field-label="Date"
                                            class="form-input-custom fp-date-input js-flatpickr-date-min-today @error('reserved_date', 'reservedPeriod') is-invalid @enderror"
                                            data-flatpickr-append-to-body
                                            data-flatpickr-disabled-date-tooltip="This date already has an active reserved booking period"
                                            data-flatpickr-disabled-dates='[]' placeholder="Select date">
                                    </div>

                                    <div class="global-field-error @error('reserved_date', 'reservedPeriod') show @enderror"
                                        data-error-for="reservedDate"
                                        aria-hidden="{{ $reservedErrors->has('reserved_date') ? 'false' : 'true' }}">
                                        @error('reserved_date', 'reservedPeriod')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>

                                <div class="modal-form-grid-2">
                                    <div data-global-field>
                                        <label for="reservedStartTime" class="form-label">Start Time <span
                                                class="text-red-500">*</span></label>
                                        <div class="global-control-wrap reserved-time-control" data-flatpickr-trigger>
                                            <i class="fa-regular fa-clock global-control-icon" aria-hidden="true"></i>
                                            <input id="reservedStartTime" name="start_time" type="text" required
                                                readonly data-field-label="Start Time"
                                                class="form-input-custom global-form-icon js-flatpickr-time @error('start_time', 'reservedPeriod') is-invalid @enderror"
                                                placeholder="Select start time">
                                        </div>
                                        <div class="global-field-error @error('start_time', 'reservedPeriod') show @enderror"
                                            data-error-for="reservedStartTime"
                                            aria-hidden="{{ $reservedErrors->has('start_time') ? 'false' : 'true' }}">
                                            @error('start_time', 'reservedPeriod')
                                                {{ $message }}
                                            @enderror
                                        </div>
                                    </div>

                                    <div data-global-field>
                                        <label for="reservedEndTime" class="form-label">End Time <span
                                                class="text-red-500">*</span></label>
                                        <div class="global-control-wrap reserved-time-control" data-flatpickr-trigger>
                                            <i class="fa-regular fa-clock global-control-icon" aria-hidden="true"></i>
                                            <input id="reservedEndTime" name="end_time" type="text" required readonly
                                                data-field-label="End Time"
                                                class="form-input-custom global-form-icon js-flatpickr-time @error('end_time', 'reservedPeriod') is-invalid @enderror"
                                                placeholder="Select end time">
                                        </div>
                                        <div class="global-field-error @error('end_time', 'reservedPeriod') show @enderror"
                                            data-error-for="reservedEndTime"
                                            aria-hidden="{{ $reservedErrors->has('end_time') ? 'false' : 'true' }}">
                                            @error('end_time', 'reservedPeriod')
                                                {{ $message }}
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div data-global-field>
                                    <span class="form-label">Patient chooses <span class="text-red-500">*</span></span>
                                    <div id="reservedBookingModeGroup" class="reserved-booking-mode-grid">
                                        <label class="reserved-booking-mode-card">
                                            <input type="radio" name="booking_mode" value="timeslot" required
                                                onchange="setReservedBookingMode(this.value)">
                                            <span class="reserved-booking-mode-icon"><i
                                                    class="fa-regular fa-clock"></i></span>
                                            <span>
                                                <strong>Date & timeslot</strong>
                                                <small>Patient selects an available time.</small>
                                            </span>
                                        </label>
                                        <label class="reserved-booking-mode-card">
                                            <input type="radio" name="booking_mode" value="date_only" required
                                                onchange="setReservedBookingMode(this.value)">
                                            <span class="reserved-booking-mode-icon"><i
                                                    class="fa-solid fa-list-ol"></i></span>
                                            <span>
                                                <strong>Date only</strong>
                                                <small>Queue-based within the period.</small>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="global-field-error @error('booking_mode', 'reservedPeriod') show @enderror"
                                        data-error-for="reserved-booking-mode"
                                        aria-hidden="{{ $reservedErrors->has('booking_mode') ? 'false' : 'true' }}">
                                        @error('booking_mode', 'reservedPeriod')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>

                                <div data-global-field>
                                    <label for="reservedActivationState" class="form-label">Period State <span
                                            class="text-red-500">*</span></label>
                                    <select id="reservedActivationState" name="is_active"
                                        class="form-select-custom js-custom-select @error('is_active', 'reservedPeriod') is-invalid @enderror"
                                        data-placeholder="Select period state" data-field-label="Period State"
                                        onchange="handleReservedPeriodStateChange()" required>
                                        <option value="0" selected>Inactive</option>
                                        <option value="1">Active</option>
                                    </select>
                                    <p class="field-help">
                                        Inactive saves the setup only. Active reserves the selected date and time and sends
                                        booking notifications to eligible users.
                                    </p>
                                    <div class="global-field-error @error('is_active', 'reservedPeriod') show @enderror"
                                        data-error-for="reservedActivationState"
                                        aria-hidden="{{ $reservedErrors->has('is_active') ? 'false' : 'true' }}">
                                        @error('is_active', 'reservedPeriod')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card modal-form-section">
                            <div class="modal-section-heading">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <div>
                                    <h4>Target & Capacity</h4>
                                    <p>Choose who may book and limit the available places.
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div data-global-field>
                                    <label for="reservedPatientType" class="form-label">Patient Type <span
                                            class="text-red-500">*</span></label>
                                    <select id="reservedPatientType" name="target_patient_type" required
                                        data-field-label="Patient Type" class="form-select-custom js-custom-select"
                                        data-placeholder="Select patient type"
                                        onchange="toggleReservedStudentFields(this.value)">
                                        <option value="student">Student</option>
                                        <option value="faculty">Faculty</option>
                                        <option value="administrative">Administrative</option>
                                        <option value="guest">Guest</option>
                                    </select>
                                    @error('target_patient_type', 'reservedPeriod')
                                        <p class="global-field-error show">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div id="reservedStudentFields" class="info-card modal-form-grid">
                                    <div data-global-field>
                                        <label for="reservedProgramCode" class="form-label">Program <span
                                                class="text-red-500">*</span></label>
                                        <select id="reservedProgramCode" name="program_code"
                                            data-field-label="Program" class="form-select-custom js-custom-select"
                                            data-placeholder="Select program"
                                            onchange="updateReservedStudentTargetDropdowns(this.value, '', '')">
                                            <option value="">Select program</option>
                                            @foreach ($studentTargetOptions->unique('course_code') as $studentOption)
                                                <option value="{{ $studentOption['course_code'] }}">
                                                    {{ $studentOption['course_code'] }}{{ $studentOption['course_name'] && $studentOption['course_name'] !== $studentOption['course_code'] ? ' — ' . $studentOption['course_name'] : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="field-help">Programs are loaded from the student information system.</p>
                                        @error('program_code', 'reservedPeriod')
                                            <p class="global-field-error show">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="modal-form-grid-2">
                                        <div data-global-field>
                                            <label for="reservedYearLevel" class="form-label">Year Level <span
                                                    class="text-red-500">*</span></label>
                                            <select id="reservedYearLevel" name="year_level"
                                                class="form-select-custom js-custom-select"
                                                data-placeholder="Select year level" data-field-label="Year Level"
                                                onchange="updateReservedStudentTargetDropdowns(document.getElementById('reservedProgramCode').value, this.value, '')">
                                                <option value="">Select year</option>
                                                @foreach ($studentTargetOptions->pluck('year_level')->filter()->unique()->sort() as $year)
                                                    <option value="{{ $year }}">Year {{ $year }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('year_level', 'reservedPeriod')
                                                <p class="global-field-error show">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div data-global-field>
                                            <label for="reservedSection" class="form-label">Section <span
                                                    class="text-red-500">*</span></label>
                                            <select id="reservedSection" name="section" data-field-label="Section"
                                                class="form-select-custom js-custom-select"
                                                data-placeholder="Select section">
                                                <option value="">Select section</option>
                                                @foreach ($studentTargetOptions->pluck('section')->filter()->unique() as $sectionOption)
                                                    <option value="{{ $sectionOption }}">{{ $sectionOption }}</option>
                                                @endforeach
                                            </select>
                                            @error('section', 'reservedPeriod')
                                                <p class="global-field-error show">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div id="reservedOverallCapacityField" data-global-field hidden>
                                    <label for="reservedMaxCapacity" class="form-label">Maximum Capacity <span
                                            class="text-red-500">*</span></label>
                                    <div class="global-number-stepper reserved-capacity-stepper"
                                        data-global-number-stepper>
                                        <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                                            aria-label="Decrease maximum capacity">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                        <input id="reservedMaxCapacity" name="max_capacity"
                                            data-field-label="Maximum Capacity" type="number" min="1"
                                            max="{{ \App\Models\ReservedBookingPeriod::MAX_CAPACITY }}" step="1"
                                            required class="global-number-stepper-input" value="10"
                                            inputmode="numeric" data-number-stepper-input>
                                        <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                            aria-label="Increase maximum capacity">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                    <p class="field-help">Maximum eligible patients who can book this period, up to
                                        {{ \App\Models\ReservedBookingPeriod::MAX_CAPACITY }}.</p>
                                    @error('max_capacity', 'reservedPeriod')
                                        <p class="global-field-error show">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card modal-form-section mt-5" data-global-field>
                        <div class="modal-section-heading">
                            <div class="modal-section-icon">
                                <i class="fa-solid fa-tooth"></i>
                            </div>
                            <div>
                                <div class="modal-section-title">Allowed Dental Services</div>
                                <div class="modal-section-sub">
                                    Select every service patients may choose during this reserved period.
                                </div>
                            </div>
                        </div>

                        <div id="reservedAllowedServicesGroup" class="global-choice-group">
                            @foreach ($serviceTypes as $serviceType)
                                <label class="global-choice-card">
                                    <input type="checkbox" name="allowed_services[]"
                                        value="{{ $serviceType->name }}" class="global-choice-input"
                                        data-reserved-service>
                                    <span class="global-choice-indicator">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                    <span class="global-choice-copy">
                                        <span class="global-choice-title">{{ $serviceType->name }}</span>
                                        <span class="global-choice-description">
                                            {{ $serviceType->description ?: 'Available dental service.' }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="field-help">Only these services will appear when an eligible patient books this period.
                        </p>
                        <div class="global-field-error @error('allowed_services', 'reservedPeriod') show @enderror"
                            data-error-for="reserved-allowed-services"
                            aria-hidden="{{ $reservedErrors->has('allowed_services') || $reservedErrors->has('allowed_services.*') ? 'false' : 'true' }}">
                            @if ($reservedErrors->has('allowed_services') || $reservedErrors->has('allowed_services.*'))
                                {{ $reservedErrors->first('allowed_services') ?: $reservedErrors->first('allowed_services.*') }}
                            @endif
                        </div>
                    </div>

                    <div class="info-card modal-form-section mt-5" data-global-field>
                        <div class="modal-section-heading">
                            <div class="modal-section-icon">
                                <i class="fa-solid fa-note-sticky"></i>
                            </div>

                            <div>
                                <h4>
                                    Additional Notes
                                </h4>

                                <p>
                                    Add optional instructions for this reserved booking period.
                                </p>
                            </div>
                        </div>

                        <div class="global-label-row">
                            <label for="reservedNotes" class="form-label">
                                Notes
                                <span class="field-optional">optional</span>
                            </label>

                            <span id="reservedNotesCount" class="char-counter">
                                0 / 500 characters
                            </span>
                        </div>

                        <div class="global-voice-row" data-voice-field>
                            <div class="global-voice-control">
                                <div class="global-form-textarea-wrap">
                                    <textarea id="reservedNotes" name="notes" maxlength="500" rows="3"
                                        class="form-input-custom global-form-textarea" data-char-limit="500" data-char-counter="#reservedNotesCount"
                                        data-field-label="Notes" placeholder="Internal instructions for this reserved period.">{{ old('notes') }}</textarea>
                                </div>
                            </div>

                            <x-voice-input target="#reservedNotes" status-id="reservedNotesVoiceStatus"
                                label="Voice input for reserved period notes" title="Voice input" />
                        </div>

                        <div class="global-field-error @error('notes', 'reservedPeriod') show @enderror"
                            data-error-for="reservedNotes"
                            aria-hidden="{{ $reservedErrors->has('notes') ? 'false' : 'true' }}">
                            @error('notes', 'reservedPeriod')
                                {{ $message }}
                            @enderror
                        </div>
                    </div>

                    <div id="reservedTimeslotBuilder" class="info-card modal-form-section mt-5">
                        <div class="modal-section-heading reserved-timeslot-heading">
                            <div class="modal-section-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <h4>Selectable Timeslots</h4>
                                <p>Create up to
                                    {{ \App\Models\ReservedBookingPeriod::MAX_CAPACITY }} times patients can choose. Each
                                    timeslot is for one patient.</p>
                            </div>
                            <div class="reserved-timeslot-total">
                                <span id="reservedTimeslotTotal">0</span>
                                <small>total patients</small>
                            </div>
                        </div>

                        <div class="reserved-timeslot-add-row">
                            <div data-global-field>
                                <label for="reservedSlotDuration" class="form-label">
                                    Duration (minutes)
                                </label>

                                <div class="global-number-stepper" data-global-number-stepper>
                                    <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                                        aria-label="Decrease duration">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>

                                    <input id="reservedSlotDuration" name="timeslot_duration_minutes" type="number"
                                        min="5" max="240" step="5" required
                                        class="global-number-stepper-input" data-field-label="Duration"
                                        data-validation-rule="wholeNumber" value="30" inputmode="numeric"
                                        autocomplete="off" data-number-stepper-input
                                        onchange="updateReservedSlotDuration()">

                                    <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                        aria-label="Increase duration">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div data-global-field>
                                <label for="reservedNewSlotTime" class="form-label">
                                    Timeslot
                                </label>

                                <div class="global-control-wrap reserved-time-control" data-flatpickr-trigger>
                                    <i class="fa-regular fa-clock global-control-icon" aria-hidden="true"></i>

                                    <input id="reservedNewSlotTime" type="text" readonly
                                        class="form-input-custom global-form-icon js-flatpickr-time" value="09:00"
                                        placeholder="Select timeslot">
                                </div>
                            </div>
                            <button id="reservedAddTimeslotButton" type="button" class="ui-btn ui-btn-primary"
                                onclick="addReservedTimeslot()">
                                <i class="fa-solid fa-plus"></i>
                                <span>Add Timeslot</span>
                            </button>
                        </div>

                        <div class="global-field-error" data-error-for="reservedNewSlotTime" aria-hidden="true">
                        </div>

                        <div id="reservedTimeslotList" class="reserved-timeslot-list"></div>
                        <p id="reservedTimeslotEmpty" class="reserved-timeslot-empty">
                            <i class="fa-regular fa-clock"></i>
                            No timeslots yet. Add the first selectable time above.
                        </p>
                        @if ($reservedErrors->has('timeslots') || $reservedErrors->has('timeslots.*.time'))
                            <p class="global-field-error show">
                                {{ $reservedErrors->first('timeslots') ?: $reservedErrors->first('timeslots.*.time') }}
                            </p>
                        @endif
                    </div>

                </div>

                <div class="modal-ft modal-sticky-footer">
                    <button type="button" class="ui-btn ui-btn-secondary"
                        data-discard-close="reservedPeriodModalBackdrop">
                        Cancel
                    </button>

                    <button id="reservedPeriodSubmitButton" type="submit" class="ui-btn ui-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span id="reservedPeriodSubmitText">
                            Save Reserved Period
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <x-delete-confirm-modal id="scheduleDeleteModal" form-id="scheduleDeleteForm" name-id="scheduleDeleteName"
        title="Delete Schedule Rule" helper="This schedule rule will be permanently removed." />

    <x-delete-confirm-modal id="blockedDateDeleteModal" form-id="blockedDateDeleteForm"
        name-id="blockedDateDeleteName" title="Remove Blocked Date" subtitle="This action requires confirmation"
        message="Are you sure you want to remove"
        helper="Removing this record will allow the date to follow the applicable clinic schedule again." />

    <x-delete-confirm-modal id="reservedPeriodDeleteModal" form-id="reservedPeriodDeleteForm"
        name-id="reservedPeriodDeleteName" title="Remove Reserved Booking Period"
        helper="The period remains in database history but will no longer be available for booking." />
@endsection

@section('scripts')

    @php
        $clinicScheduleStoreUrl = route($clinicScheduleRouteNames['store']);

        $clinicScheduleUpdateUrlTemplate = route($clinicScheduleRouteNames['update'], [
            'clinicSchedule' => '__RULE_ID__',
        ]);

        $reservedPeriodStoreUrl = route($clinicScheduleRouteNames['reserved_store']);

        $reservedPeriodUpdateUrlTemplate = route($clinicScheduleRouteNames['reserved_update'], [
            'reservedBookingPeriod' => '__PERIOD_ID__',
        ]);
    @endphp

    <script>
        const clinicScheduleRoutes = {
            store: @json($clinicScheduleStoreUrl),
            update: @json($clinicScheduleUpdateUrlTemplate),
        };

        const reservedPeriodRoutes = {
            store: @json($reservedPeriodStoreUrl),
            update: @json($reservedPeriodUpdateUrlTemplate),
        };
        const reservedPeriodValidationFailed = @json($reservedErrors->any());
        const reservedPeriodOldInput = @json(old());
        const reservedPeriodMaxCapacity = @json(\App\Models\ReservedBookingPeriod::MAX_CAPACITY);
        const reservedStudentTargetOptions = @json($studentTargetOptions->values());

        const scheduleRules = @json($schedules);
        const weeklyAppointments = @json($weeklyAppointments ?? []);
        const reservedBookingPeriods = @json($reservedBookingPeriods);

        function reservedPeriodStateIsActive() {
            const value = document.getElementById('reservedActivationState')?.value ?? '0';

            return String(value) === '1';
        }

        function syncReservedDateAvailability(ignorePeriodId = null) {
            const dateInput = document.getElementById('reservedDate');

            if (!dateInput) return;

            const disabledDates = reservedPeriodStateIsActive() ? reservedBookingPeriods
                .filter(period => {
                    const isActive = period?.is_active === true ||
                        period?.is_active === 1 ||
                        period?.is_active === '1';

                    return isActive && String(period.id) !== String(ignorePeriodId ?? '');
                })
                .map(period => String(period.reserved_date || '').slice(0, 10))
                .filter(Boolean) : [];

            dateInput.dataset.flatpickrDisabledDates = JSON.stringify(disabledDates);

            if (dateInput._flatpickr) {
                dateInput._flatpickr.set('disable', disabledDates);
                dateInput._flatpickr.redraw();
            }

            const selectedDate = String(dateInput.value || '').slice(0, 10);

            if (reservedPeriodStateIsActive() && selectedDate && disabledDates.includes(selectedDate)) {
                if (dateInput._flatpickr) {
                    dateInput._flatpickr.clear(false);
                } else {
                    dateInput.value = '';
                }

                window.showFormInputValidationMessage?.(
                    dateInput,
                    'This date already has an active reserved booking period. Set that period to Inactive first or choose another date.'
                );
            } else {
                window.showFormInputValidationMessage?.(dateInput, '');
            }
        }

        function handleReservedPeriodStateChange() {
            const periodId = document.getElementById('reservedPeriodId')?.value || null;
            syncReservedDateAvailability(periodId);
        }

        function clearFieldError(errorId, inputId = null, groupId = null) {
            const errorEl = document.getElementById(errorId);
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.remove('show');
            }
            if (inputId) document.getElementById(inputId)?.classList.remove('is-invalid');
            if (groupId) document.getElementById(groupId)?.classList.remove('is-invalid');
        }

        function setFieldError(errorId, message, inputId = null, groupId = null) {
            const errorEl = document.getElementById(errorId);
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.add('show');
            }
            if (inputId) document.getElementById(inputId)?.classList.add('is-invalid');
            if (groupId) document.getElementById(groupId)?.classList.add('is-invalid');
        }

        function openAppointmentDetailModal(appt) {
            const service = appt.service_type === 'Others' ?
                (appt.other_services || 'Other Service') :
                (appt.service_type || '—');

            document.getElementById('detailPatientName').textContent = appt.patient_name || 'Unknown Patient';
            document.getElementById('detailServiceType').textContent = service;
            document.getElementById('detailSchedule').textContent =
                `${appt.appointment_date} ${appt.display_time || appt.appointment_time || ''}`;

            window.openModal('appointmentDetailModal');
        }

        function closeAppointmentDetailModal() {
            window.closeModal('appointmentDetailModal');
        }

        const SHORT_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const DAY_ABBRS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const DEFAULT_CALENDAR_OPEN_HOUR = 9;
        const DEFAULT_CALENDAR_CLOSE_HOUR = 17;

        function formatCalendarHour(hour) {
            const normalizedHour =
                ((hour % 24) + 24) % 24;

            const suffix =
                normalizedHour >= 12 ? 'PM' : 'AM';

            const displayHour =
                normalizedHour % 12 || 12;

            return `${displayHour}:00 ${suffix}`;
        }

        function getActiveCalendarSchedule() {
            return (scheduleRules || []).find(rule => {
                return (
                    rule &&
                    rule.is_active &&
                    rule.status !== 'closed' &&
                    rule.open_time &&
                    rule.close_time
                );
            }) || null;
        }

        function getCalendarTimeRows() {
            const activeSchedule =
                getActiveCalendarSchedule();

            let openHour =
                DEFAULT_CALENDAR_OPEN_HOUR;

            let closeHour =
                DEFAULT_CALENDAR_CLOSE_HOUR;

            if (activeSchedule) {
                openHour = parseInt(
                    String(activeSchedule.open_time).substring(0, 2),
                    10
                );

                closeHour = parseInt(
                    String(activeSchedule.close_time).substring(0, 2),
                    10
                );
            }

            if (
                !Number.isFinite(openHour) ||
                !Number.isFinite(closeHour) ||
                closeHour <= openHour
            ) {
                openHour =
                    DEFAULT_CALENDAR_OPEN_HOUR;

                closeHour =
                    DEFAULT_CALENDAR_CLOSE_HOUR;
            }

            return Array.from({
                    length: closeHour - openHour
                },
                (_, index) => {
                    const hour =
                        openHour + index;

                    return {
                        h: hour,
                        l: formatCalendarHour(hour)
                    };
                }
            );
        }
        let weekOffset = 0;

        function weekStart(offset) {
            const t = new Date();
            t.setHours(0, 0, 0, 0);
            const dow = t.getDay();
            const mon = new Date(t);
            mon.setDate(t.getDate() - (dow === 0 ? 6 : dow - 1) + offset * 7);
            return mon;
        }

        function slotState(dayAbbr, hour) {
            const rule = scheduleRules.find(r => r.is_active && (r.days || []).includes(dayAbbr));
            if (!rule || rule.status === 'closed') return 'closed';
            const oh = rule.open_time ? parseInt(rule.open_time) : 9;
            const ch = rule.close_time ? parseInt(rule.close_time) : 17;
            if (hour < oh || hour >= ch) return 'closed';
            if (rule.break_time && rule.break_time !== 'none') {
                const [bs, be] = rule.break_time.split('-');
                if (hour >= parseInt(bs) && hour < parseInt(be)) return 'break';
            }
            return 'open';
        }

        function to24Hour(hour) {
            return String(hour).padStart(2, '0') + ':00';
        }

        function normalizeToHourMinute(timeValue) {
            if (!timeValue) return '';
            if (/^\d{2}:\d{2}$/.test(timeValue)) return timeValue;
            if (/^\d{2}:\d{2}:\d{2}$/.test(timeValue)) return timeValue.slice(0, 5);
            const temp = new Date(`1970-01-01 ${timeValue}`);
            if (!isNaN(temp.getTime())) {
                return `${String(temp.getHours()).padStart(2, '0')}:${String(temp.getMinutes()).padStart(2, '0')}`;
            }
            return String(timeValue).trim();
        }

        function getAppointmentsForSlot(isoDate, hour) {
            const slotTime = to24Hour(hour);
            return weeklyAppointments.filter(appt =>
                appt.appointment_date === isoDate &&
                normalizeToHourMinute(appt.appointment_time) === slotTime
            );
        }

        function getReservedPeriodsForSlot(isoDate, hour) {
            return reservedBookingPeriods.filter(period => {
                if (!period?.is_active || period.reserved_date !== isoDate) {
                    return false;
                }

                const [startHour, startMinute] = normalizeToHourMinute(period.start_time)
                    .split(':')
                    .map(Number);
                const [endHour, endMinute] = normalizeToHourMinute(period.end_time)
                    .split(':')
                    .map(Number);
                const start = startHour * 60 + startMinute;
                const end = endHour * 60 + endMinute;
                const rowStart = hour * 60;
                const rowEnd = rowStart + 60;

                return start < rowEnd && end > rowStart;
            });
        }

        function escapeCalendarText(value) {
            const element = document.createElement('div');
            element.textContent = String(value ?? '');
            return element.innerHTML;
        }

        function getAppointmentStatusMeta(status) {
            const normalized = String(status || '')
                .trim()
                .toLowerCase();

            if (['pending', 'confirmed', 'upcoming'].includes(normalized)) {
                return {
                    className: 'status-upcoming',
                    label: 'Upcoming',
                };
            }

            if (['reschedule', 'rescheduled'].includes(normalized)) {
                return {
                    className: 'status-rescheduled',
                    label: 'Rescheduled',
                };
            }

            if (normalized === 'completed') {
                return {
                    className: 'status-completed',
                    label: 'Completed',
                };
            }

            if (['canceled', 'cancelled'].includes(normalized)) {
                return {
                    className: 'status-cancelled',
                    label: 'Cancelled',
                };
            }

            return {
                className: 'status-upcoming',
                label: 'Upcoming',
            };
        }

        function getServiceBadgeClass(serviceType) {
            const service = String(serviceType || '').toLowerCase();

            if (service.includes('surgery')) {
                return 'service-badge-surgery';
            }

            if (service.includes('check')) {
                return 'service-badge-checkup';
            }

            if (service.includes('whiten')) {
                return 'service-badge-whitening';
            }

            if (service.includes('extrac')) {
                return 'service-badge-extraction';
            }

            return 'service-badge-default';
        }

        function buildWeekGrid() {
            const ws = weekStart(weekOffset);
            const days = Array.from({
                length: 7
            }, (_, i) => {
                const d = new Date(ws);
                d.setDate(d.getDate() + i);
                return d;
            });
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            document.getElementById('weekRangeLabel').textContent =
                `${SHORT_MONTHS[days[0].getMonth()]} ${days[0].getDate()} – ${SHORT_MONTHS[days[6].getMonth()]} ${days[6].getDate()}, ${days[6].getFullYear()}`;

            let html = `<div class="wk-hdr empty"></div>`;
            days.forEach((d, i) => {
                const isTod = d.getTime() === today.getTime();
                const cls = isTod ? 'today-hdr' : i >= 5 ? 'weekend-hdr' : '';
                html += `<div class="wk-hdr ${cls}">
                    <div style="font-size:.65rem;opacity:.75">${DAY_ABBRS[d.getDay()]}</div>
                    <div style="font-size:1rem;font-weight:800;line-height:1.2">${d.getDate()}</div>
                    ${isTod ? '<div style="font-size:.55rem;background:rgba(255,255,255,.25);border-radius:999px;padding:1px 6px;margin-top:2px">Today</div>' : ''}
                </div>`;
            });

            getCalendarTimeRows().forEach(({
                h,
                l
            }) => {
                html += `<div class="time-lbl">${l}</div>`;
                days.forEach((d, i) => {
                    const isoDate =
                        `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    const abbr = d.toLocaleDateString('en-US', {
                        weekday: 'short'
                    }).replace('.', '');
                    const slotReservedPeriods = getReservedPeriodsForSlot(isoDate, h);
                    const state = i >= 5 ? 'wk-weekend' :
                        slotReservedPeriods.length ? 'wk-reserved' :
                        slotState(abbr, h) === 'break' ? 'wk-break' :
                        slotState(abbr, h) === 'closed' ? 'wk-closed' : '';

                    let inner = '';
                    if (state === 'wk-reserved') {
                        inner = slotReservedPeriods.map(period => `
                        <div class="reserved-calendar-block" title="Reserved booking period">
                            <strong>${escapeCalendarText(period.title)}</strong>
                            <span>${escapeCalendarText(period.target_label || period.target_patient_type)}</span>
                        </div>
                    `).join('');
                    } else if (state === 'wk-break') inner = '<span class="slot-label">BREAK</span>';
                    else if (state === 'wk-closed' || state === 'wk-weekend') inner =
                        '<span class="slot-label">CLOSED</span>';
                    else {
                        const slotAppointments = getAppointmentsForSlot(isoDate, h);
                        if (slotAppointments.length > 0) {
                            inner = slotAppointments.map(appt => {
                                const service =
                                    appt.service_type === 'Others' ?
                                    (appt.other_services || 'Other Service') :
                                    (appt.service_type || 'Other Service');

                                const statusMeta =
                                    getAppointmentStatusMeta(appt.status);

                                const serviceBadgeClass =
                                    getServiceBadgeClass(service);

                                return `
        <button
            type="button"
            class="week-appointment ${statusMeta.className}"
            onclick='openAppointmentDetailModal(${JSON.stringify(appt)})'
            title="Click to view details"
        >
            <div class="week-appointment-top">
                <strong class="week-appointment-patient">
                    ${escapeCalendarText(appt.patient_name || 'Unknown Patient')}
                </strong>

                <span class="week-appointment-status">
                    ${statusMeta.label}
                </span>
            </div>

            <span class="service-badge ${serviceBadgeClass}">
                ${escapeCalendarText(service)}
            </span>
        </button>
    `;
                            }).join('');
                        }
                    }
                    html += `<div class="cal-slot ${state}">${inner}</div>`;
                });
            });

            document.getElementById('weekGrid').innerHTML = html;
        }

        let weekCarouselAnimating = false;

        const WEEK_CAROUSEL_OUT_MS = 180;
        const WEEK_CAROUSEL_IN_MS = 280;

        function clearWeekCarouselClasses(element) {
            if (!element) {
                return;
            }

            element.classList.remove(
                'global-carousel-out-left',
                'global-carousel-out-right',
                'global-carousel-in-left',
                'global-carousel-in-right'
            );
        }

        function runWeekCarousel(direction, updateContent) {
            const weekGrid =
                document.getElementById('weekGrid');

            if (!weekGrid) {
                updateContent();
                return;
            }

            const reducedMotion =
                window.matchMedia(
                    '(prefers-reduced-motion: reduce)'
                ).matches;

            if (!direction || reducedMotion) {
                updateContent();
                return;
            }

            if (weekCarouselAnimating) {
                return;
            }

            weekCarouselAnimating = true;

            const outClass =
                direction > 0 ?
                'global-carousel-out-left' :
                'global-carousel-out-right';

            const inClass =
                direction > 0 ?
                'global-carousel-in-right' :
                'global-carousel-in-left';

            clearWeekCarouselClasses(weekGrid);

            weekGrid.classList.add(outClass);

            window.setTimeout(() => {
                weekGrid.classList.remove(outClass);

                updateContent();

                requestAnimationFrame(() => {
                    clearWeekCarouselClasses(weekGrid);

                    weekGrid.classList.add(inClass);

                    window.setTimeout(() => {
                        weekGrid.classList.remove(inClass);

                        weekCarouselAnimating = false;
                    }, WEEK_CAROUSEL_IN_MS);
                });
            }, WEEK_CAROUSEL_OUT_MS);
        }

        function changeWeek(direction) {
            if (weekCarouselAnimating) {
                return;
            }

            runWeekCarousel(
                direction,
                () => {
                    weekOffset += direction;
                    buildWeekGrid();
                }
            );
        }

        document
            .getElementById('prevWeek')
            ?.addEventListener(
                'click',
                () => changeWeek(-1)
            );

        document
            .getElementById('nextWeek')
            ?.addEventListener(
                'click',
                () => changeWeek(1)
            );

        document
            .getElementById('todayBtn')
            ?.addEventListener(
                'click',
                () => {
                    if (
                        weekCarouselAnimating ||
                        weekOffset === 0
                    ) {
                        return;
                    }

                    const direction =
                        weekOffset < 0 ? 1 : -1;

                    runWeekCarousel(
                        direction,
                        () => {
                            weekOffset = 0;
                            buildWeekGrid();
                        }
                    );
                }
            );

        function initWeeklyAppointmentView() {
            const weekGrid =
                document.getElementById('weekGrid');

            if (!weekGrid) return;

            buildWeekGrid();
        }

        initWeeklyAppointmentView();

        let selectedBreak = '12:00-13:00';
        let editingId = null;

        function setCustomSelectValue(
            select,
            value,
            options = {}
        ) {
            if (!select) return;

            const {
                dispatch = false
            } = options;

            select.value = value;

            const wrapper =
                select.closest('.custom-select');

            if (
                wrapper &&
                typeof window.syncCustomSelect === 'function'
            ) {
                window.syncCustomSelect(wrapper);
            }

            if (dispatch) {
                select.dispatchEvent(
                    new Event('change', {
                        bubbles: true
                    })
                );
            }
        }

        function openRuleModal(mode = 'create', ruleId = null, rule = null) {
            editingId = null;

            const submitBtn = document.getElementById('ruleSubmitBtn');
            const submitText = document.getElementById('ruleSubmitText');
            const subtitle = document.getElementById('ruleModalSubtitle');
            const icon = document.getElementById('ruleModalIcon');

            const backdrop = document.getElementById('ruleModalBackdrop');
            const form = document.getElementById('ruleForm');
            const methodField = document.getElementById('ruleMethodField');
            const title = document.getElementById('ruleModalTitle');
            const activationState = document.getElementById('ruleActivationState');
            const status = document.getElementById('ruleStatus');
            const openTime = document.getElementById('ruleOpenTime');
            const closeTime = document.getElementById('ruleCloseTime');
            const maxSlots = document.getElementById('ruleMaxSlots');
            const notes = document.getElementById('ruleNotes');
            const timeFields = document.getElementById('ruleTimeFields');
            const defaultBreak = document.querySelector('.break-chip[data-val="12:00-13:00"]');

            if (
                !backdrop ||
                !form ||
                !methodField ||
                !title ||
                !activationState ||
                !status ||
                !openTime ||
                !closeTime ||
                !maxSlots ||
                !notes ||
                !timeFields
            ) {
                console.error('Rule modal elements not found.');
                return;
            }

            document
                .querySelectorAll(
                    '#ruleForm .global-field-error'
                )
                .forEach(error => {
                    error.innerHTML = '';
                    error.classList.remove('show');
                });

            document
                .querySelectorAll(
                    '#ruleForm .is-invalid'
                )
                .forEach(element => {
                    element.classList.remove('is-invalid');
                });

            backdrop.classList.remove(
                'modal-theme-primary',
                'modal-theme-edit'
            );

            backdrop.classList.add('modal-theme-primary');

            title.textContent = 'Add Schedule Rule';

            if (subtitle) {
                subtitle.textContent =
                    'Choose clinic days, set operating hours, and control booking capacity.';
            }

            if (icon) {
                icon.className = 'fa-solid fa-calendar-plus';
            }

            if (submitBtn) {
                submitBtn.className = 'ui-btn ui-btn-primary';
            }

            if (submitText) {
                submitText.textContent = 'Save Rule';
            }
            form.action = clinicScheduleRoutes.store;
            methodField.innerHTML = '';

            document.querySelectorAll(
                '#ruleModalBackdrop .day-toggle'
            ).forEach(button => {
                button.classList.remove('active');
                button.setAttribute(
                    'aria-pressed',
                    'false'
                );
                button.dataset.discardValue = 'false';
            });

            document.querySelectorAll(
                '#ruleModalBackdrop .break-chip'
            ).forEach(button => {
                const selected =
                    button === defaultBreak;

                button.classList.toggle(
                    'selected',
                    selected
                );

                button.dataset.discardValue =
                    selected ? 'true' : 'false';
            });

            selectedBreak = '12:00-13:00';
            setCustomSelectValue(activationState, '0');
            setCustomSelectValue(status,
                'open');
            setCustomSelectValue(openTime, '09:00');
            setCustomSelectValue(closeTime,
                '17:00');
            toggleStatusFields('open');
            maxSlots.value = '5';
            notes.value = '';

            window.initCharLimitFields?.(
                backdrop
            );

            notes.dispatchEvent(
                new Event('input', {
                    bubbles: true
                })
            );

            timeFields.style.display = '';

            if (mode === 'edit' && rule) {
                backdrop.classList.remove('modal-theme-primary');
                backdrop.classList.add('modal-theme-edit');

                title.textContent = 'Edit Schedule Rule';

                if (subtitle) {
                    subtitle.textContent =
                        'Update clinic days, operating hours, and booking capacity.';
                }

                if (icon) {
                    icon.className = 'fa-solid fa-pen-to-square';
                }

                if (submitBtn) {
                    submitBtn.className = 'ui-btn ui-btn-primary';
                }

                if (submitText) {
                    submitText.textContent = 'Update Rule';
                }

                editingId = ruleId;
                form.action = clinicScheduleRoutes.update.replace('__RULE_ID__', ruleId);
                methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

                (rule.days || []).forEach(day => {
                    const el = document.querySelector(`#ruleModalBackdrop .day-toggle[data-day="${day}"]`);
                    if (el) {
                        el.classList.add('active');
                        el.setAttribute(
                            'aria-pressed',
                            'true'
                        );
                        el.dataset.discardValue = 'true';
                    }
                });

                const selectedStatus = rule.status || 'open';
                const selectedActivationState = rule.is_active ? '1' : '0';

                setCustomSelectValue(activationState, selectedActivationState);
                setCustomSelectValue(status, selectedStatus);
                toggleStatusFields(selectedStatus);

                if (rule.open_time) {
                    setCustomSelectValue(
                        openTime,
                        String(rule.open_time).substring(0, 5)
                    );
                }

                if (rule.close_time) {
                    setCustomSelectValue(
                        closeTime,
                        String(rule.close_time).substring(0, 5)
                    );
                }

                maxSlots.value = rule.max_slots || 5;
                notes.value =
                    rule.notes || '';

                notes.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );

                selectedBreak = rule.break_time || 'none';
                document.querySelectorAll(
                    '#ruleModalBackdrop .break-chip'
                ).forEach(button => {
                    const selected =
                        button.dataset.val === selectedBreak;

                    button.classList.toggle(
                        'selected',
                        selected
                    );

                    button.dataset.discardValue =
                        selected ? 'true' : 'false';
                });
            }

            notes.dispatchEvent(
                new Event('input', {
                    bubbles: true
                })
            );
            window.openModal('ruleModalBackdrop');
        }

        function toggleDay(button) {
            const isActive =
                button.classList.toggle('active');

            const value =
                isActive ? 'true' : 'false';

            button.setAttribute(
                'aria-pressed',
                value
            );

            button.dataset.discardValue = value;

            clearFieldError(
                'ruleDaysError',
                null,
                'ruleDaysGroup'
            );
        }

        function toggleStatusFields(val) {
            document.getElementById('ruleTimeFields').style.display = val === 'closed' ? 'none' : '';
        }

        function selectBreak(selectedButton) {
            document
                .querySelectorAll(
                    '#ruleBreakGroup .break-chip'
                )
                .forEach(button => {
                    const selected =
                        button === selectedButton;

                    button.classList.toggle(
                        'selected',
                        selected
                    );

                    button.dataset.discardValue =
                        selected ? 'true' : 'false';
                });

            selectedBreak =
                selectedButton.dataset.val;

            clearFieldError(
                'ruleBreakError',
                null,
                'ruleBreakGroup'
            );
        }

        function findOtherActiveSchedule() {
            return (scheduleRules || []).find(rule => {
                if (!rule || !rule.is_active) {
                    return false;
                }

                if (
                    editingId !== null &&
                    String(rule.id) === String(editingId)
                ) {
                    return false;
                }

                return true;
            }) || null;
        }

        function registerClinicScheduleValidation() {
            if (
                typeof window.registerGlobalFormValidationRule !==
                'function'
            ) {
                return false;
            }

            window.registerGlobalFormValidationRule(
                'clinicScheduleRule',
                form => {
                    const daysGroup =
                        document.getElementById('ruleDaysGroup');

                    const activeDays = Array.from(
                        form.querySelectorAll('.day-toggle.active')
                    ).map(day => day.dataset.day);

                    const activationStateField =
                        document.getElementById('ruleActivationState');

                    const activationState =
                        activationStateField?.value || '0';

                    const status =
                        document.getElementById('ruleStatus')?.value || '';

                    const openTimeField =
                        document.getElementById('ruleOpenTime');

                    const closeTimeField =
                        document.getElementById('ruleCloseTime');

                    const maxSlotsField =
                        document.getElementById('ruleMaxSlots');

                    const openTime =
                        openTimeField?.value || '';

                    const closeTime =
                        closeTimeField?.value || '';

                    const maxSlots =
                        Number(maxSlotsField?.value || 0);

                    let valid = true;
                    let firstInvalid = null;

                    window.clearGlobalGroupError?.(
                        daysGroup,
                        'rule-days'
                    );

                    clearFieldError(
                        'ruleStateError',
                        'ruleActivationState'
                    );

                    window.showFormInputValidationMessage?.(
                        closeTimeField,
                        ''
                    );

                    window.showFormInputValidationMessage?.(
                        maxSlotsField,
                        ''
                    );

                    if (!activeDays.length) {
                        window.showGlobalGroupError?.(
                            daysGroup,
                            'rule-days',
                            'Please select at least one day.'
                        );

                        valid = false;
                        firstInvalid = daysGroup;
                    }

                    if (activationState === '1') {
                        const otherActiveSchedule =
                            findOtherActiveSchedule();

                        if (otherActiveSchedule) {
                            const message =
                                'Set the current active schedule to Inactive before activating this schedule.';

                            setFieldError(
                                'ruleStateError',
                                message,
                                'ruleActivationState'
                            );

                            window.showToast?.({
                                type: 'warning',
                                title: 'Schedule cannot be activated',
                                message: message,
                                duration: 5000
                            });

                            valid = false;
                            firstInvalid ||= activationStateField;
                        }
                    }

                    if (status !== 'closed') {
                        if (
                            !openTime ||
                            !closeTime
                        ) {
                            if (!openTime) {
                                window.showFormInputValidationMessage?.(
                                    openTimeField,
                                    'Please select an opening time.'
                                );

                                firstInvalid ||= openTimeField;
                            }

                            if (!closeTime) {
                                window.showFormInputValidationMessage?.(
                                    closeTimeField,
                                    'Please select a closing time.'
                                );

                                firstInvalid ||= closeTimeField;
                            }

                            valid = false;
                        } else if (openTime >= closeTime) {
                            window.showFormInputValidationMessage?.(
                                closeTimeField,
                                'Closing time must be later than opening time.'
                            );

                            valid = false;
                            firstInvalid ||= closeTimeField;
                        }

                        if (
                            !Number.isFinite(maxSlots) ||
                            maxSlots < 1 ||
                            maxSlots > 30
                        ) {
                            window.showFormInputValidationMessage?.(
                                maxSlotsField,
                                'Max appointments must be between 1 and 30.'
                            );

                            valid = false;
                            firstInvalid ||= maxSlotsField;
                        }
                    }

                    return {
                        valid,
                        firstInvalid
                    };
                }
            );

            return true;
        }

        window.addEventListener(
            'global-validation-ready',
            () => {
                registerClinicScheduleValidation();
                registerClinicDateValidation();
                registerReservedBookingPeriodValidation();
            }
        );

        document.addEventListener(
            'DOMContentLoaded',
            () => {
                registerClinicScheduleValidation();
                registerClinicDateValidation();
                registerReservedBookingPeriodValidation();
            }
        );

        function submitRule() {
            const form = document.getElementById('ruleForm');

            if (!form) {
                return;
            }

            const validation =
                window.validateGlobalForm?.(form);

            if (validation && !validation.valid) {
                return;
            }

            const activeDays = Array.from(
                document.querySelectorAll(
                    '#ruleModalBackdrop .day-toggle.active'
                )
            ).map(day => day.dataset.day);

            const activationState =
                document.getElementById('ruleActivationState')?.value || '0';

            const status =
                document.getElementById('ruleStatus')?.value || 'open';

            const openTime =
                document.getElementById('ruleOpenTime')?.value || '';

            const closeTime =
                document.getElementById('ruleCloseTime')?.value || '';

            const maxSlots =
                document.getElementById('ruleMaxSlots')?.value || '';

            form
                .querySelectorAll('.injected-hidden')
                .forEach(element => element.remove());

            const inject = (name, value) => {
                const input = document.createElement('input');

                input.type = 'hidden';
                input.name = name;
                input.value = value;
                input.className = 'injected-hidden';

                form.appendChild(input);
            };

            activeDays.forEach(day => {
                inject('days[]', day);
            });

            inject('is_active', activationState);
            inject('status', status);

            if (status !== 'closed') {
                inject('open_time', openTime);
                inject('close_time', closeTime);
                inject('max_slots', maxSlots);
                inject(
                    'break_time',
                    selectedBreak || 'none'
                );
            }

            inject(
                'notes',
                document.getElementById('ruleNotes')?.value || ''
            );

            window.DiscardChanges?.markSubmitting?.(form);

            form.requestSubmit();
        }

        function getLocalDateString(date = new Date()) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        async function openBlockModal() {
            const backdrop = document.getElementById('blockModalBackdrop');
            const blockDate = document.getElementById('blockDate');

            if (!backdrop || !blockDate) {
                console.error('Block modal elements not found.');
                return;
            }

            document
                .querySelectorAll('#blockDateForm .global-field-error')
                .forEach(error => {
                    error.innerHTML = '';
                    error.classList.remove('show');
                });

            document
                .querySelectorAll('#blockDateForm .is-invalid')
                .forEach(element => {
                    element.classList.remove('is-invalid');
                });

            const today = getLocalDateString();

            blockDate.removeAttribute('max');
            blockDate.setAttribute('min', today);
            blockDate.classList.remove('js-flatpickr-date-max-today');
            blockDate.classList.add('js-flatpickr-date-min-today');

            setCustomSelectValue(
                document.getElementById('blockReason'),
                'Holiday'
            );

            window.openModal('blockModalBackdrop');

            try {
                await window.initGlobalDatePickers?.(backdrop);
            } catch (error) {
                console.error('Unable to initialize Block Date picker.', error);
            }

            const picker = blockDate._flatpickr;

            if (picker) {
                picker.set('maxDate', null);
                picker.set('minDate', today);
                picker.clear(false);
            } else {
                blockDate.value = '';
            }
        }

        function registerClinicDateValidation() {
            if (
                typeof window.registerGlobalValidationRule !==
                'function'
            ) {
                return false;
            }

            window.registerGlobalValidationRule(
                'clinicFutureOrToday',
                field => {
                    const value =
                        String(field.value || '').trim();

                    if (!value) {
                        return 'Please select a date.';
                    }

                    const today = getLocalDateString();

                    if (value < today) {
                        return 'Previous dates are not allowed.';
                    }

                    return '';
                }
            );

            return true;
        }

        function openScheduleDeleteModal(actionUrl, scheduleName) {
            window.openDeleteConfirmModal?.({
                modalId: 'scheduleDeleteModal',

                formId: 'scheduleDeleteForm',

                nameId: 'scheduleDeleteName',

                action: actionUrl,

                itemName: scheduleName,
            });
        }

        function openBlockedDateDeleteModal(actionUrl, blockedDateName) {
            window.openDeleteConfirmModal?.({
                modalId: 'blockedDateDeleteModal',

                formId: 'blockedDateDeleteForm',

                nameId: 'blockedDateDeleteName',

                action: actionUrl,

                itemName: blockedDateName,
            });
        }

        document.addEventListener('DOMContentLoaded', function() {

            const ruleNotes = document.getElementById('ruleNotes');

            ruleNotes?.addEventListener(
                'input',
                function() {
                    clearFieldError(
                        'ruleNotesError',
                        'ruleNotes'
                    );
                }
            );

            window.syncInputClearButton?.(ruleNotes);

            document.getElementById('ruleStatus')?.addEventListener('change', () => clearFieldError(
                'ruleStatusError', 'ruleStatus'));
            document.getElementById('ruleOpenTime')?.addEventListener('change', () => clearFieldError(
                'ruleOpenTimeError', 'ruleOpenTime'));
            document.getElementById('ruleCloseTime')?.addEventListener('change', () => clearFieldError(
                'ruleCloseTimeError', 'ruleCloseTime'));
            document.getElementById('ruleMaxSlots')?.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 2);

                if (this.value !== '') {
                    const value = Math.max(1, Math.min(30, parseInt(this.value, 10)));
                    this.value = String(value);
                }

                clearFieldError('ruleMaxSlotsError', 'ruleMaxSlots');
            });

            document.getElementById('blockDate')?.addEventListener('input', () => clearFieldError(
                'blockDateError',
                'blockDate'));

            document
                .querySelector('#blockModalBackdrop .fp-date-input-wrap')
                ?.addEventListener('click', async function() {
                    const input = document.getElementById('blockDate');

                    if (!input) return;

                    if (!input._flatpickr) {
                        try {
                            await window.initGlobalDatePickers?.(
                                document.getElementById('blockModalBackdrop')
                            );
                        } catch (error) {
                            console.error('Unable to initialize Block Date picker.', error);
                        }
                    }

                    input._flatpickr?.open();
                });
            document.getElementById('blockReason')?.addEventListener('change', () => clearFieldError(
                'blockReasonError', 'blockReason'));
            document.getElementById('blockNote')?.addEventListener('input', () => clearFieldError(
                'blockNoteError',
                'blockNote'));
        });

        function replaceReservedTargetSelectOptions(select, options, placeholder, selectedValue = '') {
            if (!select) return;

            const wrapper = select.closest('.custom-select');
            if (wrapper) {
                wrapper.parentNode.insertBefore(select, wrapper);
                wrapper.remove();
            }

            delete select.dataset.customSelectReady;
            select.classList.remove('custom-select-native');
            select.innerHTML = '';
            const placeholderOption = new Option(placeholder, '');
            placeholderOption.disabled = true;
            placeholderOption.hidden = true;
            select.appendChild(placeholderOption);

            options.forEach(option => {
                select.appendChild(new Option(option.label, option.value));
            });

            const normalizedSelected = String(selectedValue ?? '');
            select.value = Array.from(select.options).some(option => option.value === normalizedSelected) ?
                normalizedSelected :
                '';

            window.initCustomSelects?.(select.parentElement);
        }

        function updateReservedStudentTargetDropdowns(program = '', year = '', section = '') {
            const programSelect = document.getElementById('reservedProgramCode');
            const yearSelect = document.getElementById('reservedYearLevel');
            const sectionSelect = document.getElementById('reservedSection');
            const selectedProgram = String(program || '').toUpperCase();
            const selectedYear = String(year || '');
            const selectedSection = String(section || '').toUpperCase();

            const uniqueOptions = (items, valueKey, labelBuilder) => {
                const seen = new Set();

                return items.reduce((options, item) => {
                    const value = String(item[valueKey] ?? '').trim();
                    const key = value.toLowerCase();
                    if (!value || seen.has(key)) return options;

                    seen.add(key);
                    options.push({
                        value,
                        label: labelBuilder(item, value)
                    });
                    return options;
                }, []);
            };

            const programs = uniqueOptions(
                reservedStudentTargetOptions,
                'course_code',
                (item, value) => item.course_name && item.course_name !== value ?
                `${value} — ${item.course_name}` :
                value
            );
            const matchingProgram = reservedStudentTargetOptions.filter(
                item => String(item.course_code || '').toUpperCase() === selectedProgram
            );
            const years = uniqueOptions(
                matchingProgram,
                'year_level',
                (_item, value) => `Year ${value}`
            ).sort((a, b) => Number(a.value) - Number(b.value));
            const matchingYear = matchingProgram.filter(
                item => String(item.year_level || '') === selectedYear
            );
            const sections = uniqueOptions(
                matchingYear,
                'section',
                (_item, value) => value
            ).sort((a, b) => a.label.localeCompare(b.label, undefined, {
                numeric: true
            }));

            replaceReservedTargetSelectOptions(programSelect, programs, 'Select program', selectedProgram);
            replaceReservedTargetSelectOptions(yearSelect, years, 'Select year', selectedYear);
            replaceReservedTargetSelectOptions(sectionSelect, sections, 'Select section', selectedSection);

            yearSelect.disabled = !selectedProgram;
            sectionSelect.disabled = !selectedProgram || !selectedYear;

            const yearCustomSelect = yearSelect.closest('.custom-select');
            const sectionCustomSelect = sectionSelect.closest('.custom-select');

            if (yearCustomSelect) {
                window.syncCustomSelect?.(yearCustomSelect);
            }

            if (sectionCustomSelect) {
                window.syncCustomSelect?.(sectionCustomSelect);
            }
        }

        function toggleReservedStudentFields(patientType) {
            const studentFields = document.getElementById('reservedStudentFields');
            const isStudent = patientType === 'student';

            if (!studentFields) return;

            studentFields.hidden = !isStudent;

            ['reservedProgramCode', 'reservedYearLevel', 'reservedSection'].forEach(id => {
                const field = document.getElementById(id);
                if (!field) return;

                const selectedProgram = document.getElementById('reservedProgramCode')?.value || '';
                const selectedYear = document.getElementById('reservedYearLevel')?.value || '';
                field.disabled = !isStudent ||
                    (id === 'reservedYearLevel' && !selectedProgram) ||
                    (id === 'reservedSection' && (!selectedProgram || !selectedYear));
                field.required = isStudent;

                if (!isStudent) {
                    field.value = '';
                }

                const customSelect = field.closest('.custom-select');
                if (customSelect) {
                    customSelect.classList.toggle('is-disabled', !isStudent);
                    window.syncCustomSelect?.(customSelect);
                }
            });
        }

        let reservedTimeslots = [];

        function setReservedBookingMode(mode) {
            const normalizedMode = mode === 'date_only' ? 'date_only' : 'timeslot';
            const timeslotBuilder = document.getElementById('reservedTimeslotBuilder');
            const capacityField = document.getElementById('reservedOverallCapacityField');
            const capacityInput = document.getElementById('reservedMaxCapacity');
            const durationInput = document.getElementById('reservedSlotDuration');

            document.querySelectorAll('input[name="booking_mode"]').forEach(input => {
                input.checked = input.value === normalizedMode;
            });

            if (timeslotBuilder) {
                timeslotBuilder.hidden = normalizedMode !== 'timeslot';
                timeslotBuilder.querySelectorAll('input[name^="timeslots["]').forEach(input => {
                    input.disabled = normalizedMode !== 'timeslot';
                });
            }

            if (capacityField) capacityField.hidden = normalizedMode === 'timeslot';
            if (capacityInput) {
                capacityInput.disabled = normalizedMode === 'timeslot';
                capacityInput.required = normalizedMode === 'date_only';
            }
            if (durationInput) {
                durationInput.disabled = normalizedMode !== 'timeslot';
                durationInput.required = normalizedMode === 'timeslot';
            }
        }

        function getReservedSlotDuration() {
            return Math.min(
                240,
                Math.max(5, Number(document.getElementById('reservedSlotDuration')?.value) || 30)
            );
        }

        function updateReservedSlotDuration() {
            const durationInput = document.getElementById('reservedSlotDuration');
            const duration = getReservedSlotDuration();

            if (durationInput) durationInput.value = duration;
            renderReservedTimeslots();
        }

        function renderReservedTimeslots() {
            const list = document.getElementById('reservedTimeslotList');
            const empty = document.getElementById('reservedTimeslotEmpty');
            const total = document.getElementById('reservedTimeslotTotal');
            const addButton = document.getElementById('reservedAddTimeslotButton');

            if (!list || !empty || !total) return;

            list.querySelectorAll('.js-flatpickr-time').forEach(input => {
                input._flatpickr?.destroy();
            });

            list.innerHTML = reservedTimeslots.map((slot, index) => `
            <div class="reserved-timeslot-item">
                <span class="reserved-timeslot-item-icon"><i class="fa-regular fa-clock"></i></span>
                <div data-global-field>
                    <label class="form-label" for="reservedSlotTime${index}">Time</label>
                    <div class="global-control-wrap reserved-time-control" data-flatpickr-trigger>
                        <i class="fa-regular fa-clock global-control-icon" aria-hidden="true"></i>
                        <input id="reservedSlotTime${index}" name="timeslots[${index}][time]" type="text"
                            required readonly class="form-input-custom global-form-icon js-flatpickr-time"
                            value="${slot.time}" placeholder="Select time"
                            onchange="updateReservedTimeslot(${index}, 'time', this.value)">
                    </div>
                </div>
                <span class="reserved-timeslot-one-patient">
                    <i class="fa-solid fa-user"></i> 1 patient · ${getReservedSlotDuration()} min
                </span>
                <button type="button" class="reserved-remove-timeslot" onclick="removeReservedTimeslot(${index})"
                    aria-label="Remove timeslot" title="Remove timeslot">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `).join('');

            empty.hidden = reservedTimeslots.length > 0;
            total.textContent = reservedTimeslots.length;

            if (addButton) {
                const atCapacity = reservedTimeslots.length >= reservedPeriodMaxCapacity;

                addButton.disabled = atCapacity;

                if (atCapacity) {
                    addButton.dataset.tooltip = `Maximum of ${reservedPeriodMaxCapacity} timeslots reached`;
                    addButton.dataset.tooltipTone = 'locked';
                } else {
                    addButton.removeAttribute('data-tooltip');
                    addButton.removeAttribute('data-tooltip-tone');
                }
            }

            setReservedBookingMode(
                document.querySelector('input[name="booking_mode"]:checked')?.value || 'timeslot'
            );
        }

        function updateReservedTimeslot(index, field, value) {
            if (!reservedTimeslots[index]) return;

            reservedTimeslots[index][field] = String(value).slice(0, 5);
        }

        function removeReservedTimeslot(index) {
            reservedTimeslots.splice(index, 1);
            renderReservedTimeslots();
        }

        function addReservedTimeslot() {
            const timeInput = document.getElementById('reservedNewSlotTime');
            const time = timeInput?.value || '';

            timeInput?.setCustomValidity('');

            if (!time) {
                window.showFormInputValidationMessage?.(
                    timeInput,
                    'Choose a time before adding the timeslot.'
                );

                window.focusGlobalInvalidField?.(
                    timeInput
                );

                return;
            }

            if (
                reservedTimeslots.some(
                    slot => slot.time === time
                )
            ) {
                window.showFormInputValidationMessage?.(
                    timeInput,
                    'This timeslot has already been added.'
                );

                return;
            }

            if (
                reservedTimeslots.length >=
                reservedPeriodMaxCapacity
            ) {
                window.showFormInputValidationMessage?.(
                    timeInput,
                    `A reserved period cannot have more than ${reservedPeriodMaxCapacity} timeslots.`
                );

                return;
            }

            window.showFormInputValidationMessage?.(timeInput, '');
            reservedTimeslots.push({
                time
            });

            reservedTimeslots.sort((a, b) => a.time.localeCompare(b.time));
            renderReservedTimeslots();

            if (timeInput) {
                const [hours, minutes] = time.split(':').map(Number);
                const nextMinutes = (hours * 60) + minutes + getReservedSlotDuration();
                const end = document.getElementById('reservedEndTime')?.value || '';
                const nextTime =
                    `${String(Math.floor(nextMinutes / 60)).padStart(2, '0')}:${String(nextMinutes % 60).padStart(2, '0')}`;
                if (nextMinutes < 24 * 60 && (!end || nextTime < end)) {
                    if (timeInput._flatpickr) {
                        timeInput._flatpickr.setDate(nextTime, false, 'H:i');
                    } else {
                        timeInput.value = nextTime;
                    }
                }
            }
        }

        function openReservedPeriodModal(mode = 'create', periodId = null, period = null) {
            const modal = document.getElementById('reservedPeriodModalBackdrop');
            const form = document.getElementById('reservedPeriodForm');
            const methodField = document.getElementById('reservedPeriodMethodField');
            const idField = document.getElementById('reservedPeriodId');
            const modalTitle = document.getElementById('reservedPeriodModalTitle');
            const modalSubtitle = document.getElementById('reservedPeriodModalSubtitle');
            const modalIcon = document.getElementById('reservedPeriodModalIcon');
            const submitButton = document.getElementById('reservedPeriodSubmitButton');
            const submitText = document.getElementById('reservedPeriodSubmitText');
            const activationState = document.getElementById('reservedActivationState');
            const serviceCheckboxes = Array.from(document.querySelectorAll('[data-reserved-service]'));

            if (!modal || !form || !methodField || !idField || !activationState) {
                return;
            }

            form.reset();
            form.action = reservedPeriodRoutes.store;
            methodField.innerHTML = '';
            idField.value = '';

            modal.classList.remove('modal-theme-primary', 'modal-theme-edit');
            modal.classList.add('modal-theme-primary');

            modalTitle.textContent = 'Create Reserved Booking Period';
            modalSubtitle.textContent = 'Reserve part of a clinic day for a selected patient group.';
            modalIcon.className = 'fa-solid fa-calendar-check';
            submitButton.className = 'ui-btn ui-btn-primary';
            submitText.textContent = 'Save Reserved Period';

            document.getElementById('reservedDate').value = '';
            activationState.value = '0';
            const activationCustomSelect = activationState.closest('.custom-select');
            if (activationCustomSelect) {
                window.syncCustomSelect?.(activationCustomSelect);
            }
            document.getElementById('reservedStartTime').value = '09:00';
            document.getElementById('reservedEndTime').value = '13:00';
            document.getElementById('reservedPatientType').value = 'student';
            document.getElementById('reservedMaxCapacity').value = '10';
            document.getElementById('reservedSlotDuration').value = '30';
            document.getElementById('reservedNewSlotTime').value = '09:00';
            reservedTimeslots = [];
            serviceCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });

            const values = period || {};

            if (mode === 'edit' && periodId) {
                modal.classList.remove('modal-theme-primary');
                modal.classList.add('modal-theme-edit');
                form.action = reservedPeriodRoutes.update.replace('__PERIOD_ID__', periodId);
                methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                idField.value = periodId;

                modalTitle.textContent = 'Edit Reserved Booking Period';
                modalSubtitle.textContent = 'Update the window, target group, booking mode, or capacity.';
                modalIcon.className = 'fa-solid fa-pen-to-square';
                submitButton.className = 'ui-btn ui-btn-primary';
                submitText.textContent = 'Update Reserved Period';
            }

            const setValue = (id, value, fallback = '') => {
                const field = document.getElementById(id);
                if (!field) return;

                field.value = value ?? fallback;

                if (field._flatpickr) {
                    if (field.value) {
                        const valueFormat = field.classList.contains('js-flatpickr-time') ?
                            'H:i' :
                            'Y-m-d';
                        field._flatpickr.setDate(field.value, false, valueFormat);
                    } else {
                        field._flatpickr.clear(false);
                    }
                }

                const customSelect = field.closest('.custom-select');
                if (customSelect) {
                    window.syncCustomSelect?.(customSelect);
                }
            };

            const valuesAreActive = values.is_active === true ||
                values.is_active === 1 ||
                values.is_active === '1';

            setValue('reservedActivationState', valuesAreActive ? '1' : '0', '0');
            setValue('reservedTitle', values.title);
            setValue(
                'reservedDate',
                values.reserved_date ? String(values.reserved_date).slice(0, 10) : ''
            );
            syncReservedDateAvailability(mode === 'edit' ? periodId : null);
            setValue('reservedStartTime', values.start_time ? String(values.start_time).slice(0, 5) : '09:00');
            setValue('reservedEndTime', values.end_time ? String(values.end_time).slice(0, 5) : '13:00');
            setValue('reservedPatientType', values.target_patient_type, 'student');
            updateReservedStudentTargetDropdowns(
                values.program_code,
                values.year_level,
                values.section
            );
            setValue('reservedMaxCapacity', values.max_capacity, '10');
            setValue('reservedSlotDuration', values.timeslot_duration_minutes, '30');
            setValue('reservedNewSlotTime', '09:00');
            setValue('reservedNotes', values.notes);

            const allowedServices = Array.isArray(values.allowed_services) ?
                values.allowed_services.map(service => String(service).toLowerCase()) :
                (mode === 'edit' ? serviceCheckboxes.map(checkbox => checkbox.value.toLowerCase()) : []);

            serviceCheckboxes.forEach(checkbox => {
                checkbox.checked = allowedServices.includes(checkbox.value.toLowerCase());
            });

            window.initCharLimitFields?.(
                modal
            );

            document
                .getElementById('reservedNotes')
                ?.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );

            reservedTimeslots = Array.from(values.timeslots || values.slots || []).map(slot => ({
                time: String(slot.time || slot.slot_time || '').slice(0, 5),
            })).filter(slot => slot.time);
            renderReservedTimeslots();
            updateReservedSlotDuration();
            setReservedBookingMode(values.booking_mode || 'timeslot');

            toggleReservedStudentFields(
                document.getElementById('reservedPatientType').value
            );

            window.openModal('reservedPeriodModalBackdrop');
        }

        function openReservedPeriodDeleteModal(action, title) {
            const form = document.getElementById('reservedPeriodDeleteForm');
            const name = document.getElementById('reservedPeriodDeleteName');

            if (!form || !name) return;

            form.action = action;
            name.textContent = title || 'Reserved booking period';
            window.openModal('reservedPeriodDeleteModal');
        }

        function registerReservedBookingPeriodValidation() {
            if (
                typeof window.registerGlobalFormValidationRule !==
                'function'
            ) {
                return false;
            }

            window.registerGlobalFormValidationRule(
                'reservedBookingPeriod',
                form => {
                    const startField =
                        document.getElementById('reservedStartTime');

                    const endField =
                        document.getElementById('reservedEndTime');

                    const bookingModeGroup =
                        document.getElementById(
                            'reservedBookingModeGroup'
                        );

                    const allowedServicesGroup =
                        document.getElementById('reservedAllowedServicesGroup');

                    const slotPrompt =
                        document.getElementById('reservedNewSlotTime');

                    const start =
                        startField?.value || '';

                    const end =
                        endField?.value || '';

                    const bookingMode =
                        form.querySelector(
                            'input[name="booking_mode"]:checked'
                        )?.value || '';

                    let valid = true;
                    let firstInvalid = null;

                    window.showFormInputValidationMessage?.(
                        endField,
                        ''
                    );

                    window.clearGlobalGroupError?.(
                        bookingModeGroup,
                        'reserved-booking-mode'
                    );

                    window.clearGlobalGroupError?.(
                        slotPrompt,
                        'reserved-timeslots'
                    );

                    window.clearGlobalGroupError?.(
                        allowedServicesGroup,
                        'reserved-allowed-services'
                    );

                    if (!allowedServicesGroup?.querySelector('input[name="allowed_services[]"]:checked')) {
                        window.showGlobalGroupError?.(
                            allowedServicesGroup,
                            'reserved-allowed-services',
                            'Select at least one dental service.'
                        );

                        valid = false;
                        firstInvalid ||= allowedServicesGroup;
                    }

                    if (!bookingMode) {
                        window.showGlobalGroupError?.(
                            bookingModeGroup,
                            'reserved-booking-mode',
                            'Please select how the patient will book.'
                        );

                        valid = false;
                        firstInvalid ||= bookingModeGroup;
                    }

                    if (
                        start &&
                        end &&
                        end <= start
                    ) {
                        window.showFormInputValidationMessage?.(
                            endField,
                            'End time must be later than start time.'
                        );

                        valid = false;
                        firstInvalid ||= endField;
                    }

                    if (bookingMode === 'timeslot') {
                        const duration =
                            getReservedSlotDuration();

                        const toMinutes = time => {
                            const [
                                hours,
                                minutes
                            ] = String(time)
                                .split(':')
                                .map(Number);

                            return (
                                hours * 60 +
                                minutes
                            );
                        };

                        const endMinutes =
                            toMinutes(end);

                        const sortedMinutes =
                            reservedTimeslots
                            .map(slot =>
                                toMinutes(slot.time)
                            )
                            .sort(
                                (a, b) => a - b
                            );

                        const hasInvalidSlot =
                            reservedTimeslots.some(
                                slot =>
                                !slot.time ||
                                slot.time < start ||
                                (
                                    toMinutes(slot.time) +
                                    duration
                                ) > endMinutes
                            );

                        const slotTimes =
                            reservedTimeslots.map(
                                slot => slot.time
                            );

                        const hasDuplicateSlot =
                            new Set(slotTimes).size !==
                            slotTimes.length;

                        const hasOverlappingSlot =
                            sortedMinutes.some(
                                (minutes, index) =>
                                index > 0 &&
                                minutes <
                                (
                                    sortedMinutes[index - 1] +
                                    duration
                                )
                            );

                        const exceedsCapacity =
                            reservedTimeslots.length >
                            reservedPeriodMaxCapacity;

                        let timeslotMessage = '';

                        if (!reservedTimeslots.length) {
                            timeslotMessage =
                                'Add at least one timeslot for patients to select.';
                        } else if (exceedsCapacity) {
                            timeslotMessage =
                                `A reserved period cannot have more than ${reservedPeriodMaxCapacity} timeslots.`;
                        } else if (hasDuplicateSlot) {
                            timeslotMessage =
                                'Each timeslot must have a unique time.';
                        } else if (hasOverlappingSlot) {
                            timeslotMessage =
                                'Timeslots cannot overlap based on the selected duration.';
                        } else if (hasInvalidSlot) {
                            timeslotMessage =
                                'Every timeslot, including its duration, must fit within the reserved period.';
                        }

                        if (timeslotMessage) {
                            window.showGlobalGroupError?.(
                                slotPrompt,
                                'reserved-timeslots',
                                timeslotMessage
                            );

                            valid = false;
                            firstInvalid ||= slotPrompt;
                        }
                    }

                    return {
                        valid,
                        firstInvalid
                    };
                }
            );

            return true;
        }

        function renderClinicScheduleEmptyStates() {
            if (!window.EmptyState) return;

            if (document.getElementById('scheduleRulesEmptyState')) {
                window.EmptyState.render({
                    host: '#scheduleRulesEmptyState',
                    icon: 'fa-calendar-xmark',
                    title: 'No schedule rules yet',
                    message: 'Add clinic hours and availability rules to begin scheduling appointments.',
                    className: 'empty-state-compact clinic-schedule-empty-state',
                    actionHtml: `
                    <button type="button" onclick="openRuleModal()" class="empty-state-btn">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>Add schedule rule</span>
                    </button>
                `,
                });
            }

            if (document.getElementById('reservedPeriodsEmptyState')) {
                window.EmptyState.render({
                    host: '#reservedPeriodsEmptyState',
                    icon: 'fa-calendar-plus',
                    title: 'No reserved booking periods yet',
                    message: 'Create a dedicated booking period for a selected patient group.',
                    className: 'empty-state-compact clinic-schedule-empty-state',
                    @if ($canCreateReservedPeriods)
                        actionHtml: `
                    <button type="button" onclick="openReservedPeriodModal()" class="empty-state-btn">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>Create reserved period</span>
                    </button>
                `,
                    @endif
                });
            }
        }

        function initializeClinicScheduleDynamicUi() {
            renderClinicScheduleEmptyStates();

            if (!reservedPeriodValidationFailed) return;

            const periodId = reservedPeriodOldInput.reserved_period_id || null;

            openReservedPeriodModal(
                periodId ? 'edit' : 'create',
                periodId,
                reservedPeriodOldInput
            );
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeClinicScheduleDynamicUi, {
                once: true
            });
        } else {
            initializeClinicScheduleDynamicUi();
        }
    </script>
@endsection
