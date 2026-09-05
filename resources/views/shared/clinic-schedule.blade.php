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
        ->filter(
            fn($holiday, $date) =>
                \Carbon\Carbon::parse($date)
                    ->isCurrentMonth()
        )
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
    @endphp

    <main id="mainContent"
        class="{{ $pageShellClass }}
        clinic-schedule-page
        {{ $isDentistView ? 'dentist-clinic-schedule-page' : 'admin-clinic-schedule-page' }}
        page-enter
        mode-list">

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
                            setFieldError('ruleStateError', @json($errors->first('is_active')), 'ruleActivationState');
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
                <section class="dentist-hero cs-dentist-hero mb-5">
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

                    <div class="dentist-hero-actions cs-hero-actions">
                        <button type="button" onclick="openRuleModal()" class="ui-btn ui-btn-primary">

                            <i class="fa-solid fa-plus"></i>
                            <span>Add Schedule Rule</span>
                        </button>

                        <button type="button" onclick="openBlockModal()" class="ui-btn ui-btn-danger">

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

                            <button type="button" onclick="openBlockModal()" class="ui-btn ui-btn-danger">

                                <i class="fa-solid fa-ban"></i>
                                <span>Block Date</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="admin-page-body">

                <div id="statCards" class="stat-grid cs-stat-grid">
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

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                    <div class="lg:col-span-2 space-y-6">

                        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">

                            <div
                                class="px-4 py-4 border-b bg-gray-50 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-2">
                                    <i class="fa-solid fa-calendar-week text-[#8B0000] mt-0.5"></i>
                                    <h2 class="font-bold text-gray-800 text-sm leading-5">Weekly Appointment View</h2>
                                </div>
                                <div class="flex items-center justify-between gap-2 sm:justify-end">

                                    <button type="button" id="prevWeek" class="ui-icon-btn neutral"
                                        data-tooltip="Previous week" data-tooltip-tone="neutral" aria-label="Previous week">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <span id="weekRangeLabel"
                                        class="text-xs font-semibold text-gray-600 px-1 min-w-[140px] text-center"></span>
                                    <button type="button" id="nextWeek" class="ui-icon-btn neutral"
                                        data-tooltip="Next week" data-tooltip-tone="neutral" aria-label="Next week">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                    <button type="button" id="todayBtn" class="ui-btn ui-btn-secondary ui-btn-sm">
                                        <i class="fa-solid fa-calendar-day"></i>
                                        <span>Today</span>
                                    </button>
                                </div>
                            </div>

                            <div class="p-4 overflow-x-auto">
                                <div id="weekGrid" class="week-grid" style="min-width:480px;"></div>
                                <div class="flex flex-wrap gap-3 mt-3 justify-end">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500"><span
                                            class="w-3 h-3 rounded bg-blue-200 border-l-2 border-blue-500 inline-block"></span>Check-up
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500"><span
                                            class="w-3 h-3 rounded bg-green-200 border-l-2 border-green-500 inline-block"></span>Cleaning
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500"><span
                                            class="w-3 h-3 rounded bg-yellow-100 border-l-2 border-yellow-400 inline-block"></span>Surgery
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500"><span
                                            class="w-3 h-3 rounded bg-purple-100 border-l-2 border-purple-400 inline-block"></span>Prosthesis
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 flex items-center justify-between cs-rules-card-header">
                                <div class="weekly-toolbar flex items-center gap-2">
                                    <i class="fa-solid fa-list-check text-[#8B0000]"></i>
                                    <h2 class="font-bold text-gray-800 text-sm">Schedule Rules</h2>
                                </div>

                                <div class="cs-rules-header-actions">
                                    <span class="cs-rules-count">{{ $schedules->count() }} rules</span>

                                    <x-view-toggle id="scheduleRulesViewToggle" storage-key="scheduleRulesView"
                                        list-view="#scheduleRulesListView" grid-view="#scheduleRulesGridView" />
                                </div>
                            </div>

                            @if ($schedules->count())
                                <div id="scheduleRulesListView" class="schedule-rules-view">
                                    <div class="overflow-x-auto px-2 pb-2 sm:px-0 sm:pb-0">
                                        <table class="data-table sched-table">
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
                                                    <tr>
                                                        <td data-label="Day(s)" class="font-semibold text-gray-800">
                                                            {{ $rule->days_label }}</td>
                                                        <td data-label="Opens">
                                                            {{ $rule->open_time ? date('g:i A', strtotime($rule->open_time)) : '—' }}
                                                        </td>
                                                        <td data-label="Closes">
                                                            {{ $rule->close_time ? date('g:i A', strtotime($rule->close_time)) : '—' }}
                                                        </td>
                                                        <td data-label="Lunch Break" class="text-xs text-gray-500">
                                                            @if ($rule->break_time && $rule->break_time !== 'none')
                                                                @php [$bs,$be]=explode('-', $rule->break_time); @endphp
                                                                {{ date('g:i A', strtotime(trim($bs) . ':00')) }} –
                                                                {{ date('g:i A', strtotime(trim($be) . ':00')) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td data-label="Max Slots">
                                                            <div class="flex items-center gap-2">
                                                                <span
                                                                    class="font-bold text-[#8B0000]">{{ $rule->max_slots }}</span>
                                                                @if ($rule->status !== 'closed')
                                                                    <div class="cap-bar w-16">
                                                                        <div class="cap-fill"
                                                                            style="width:{{ min(100, ($rule->max_slots / 30) * 100) }}%">
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td data-label="Status">
                                                            @if ($rule->status === 'open')
                                                                <span class="badge-open">Open</span>
                                                            @elseif($rule->status === 'limited')
                                                                <span class="badge-limited">Limited</span>
                                                            @else
                                                                <span class="badge-closed">Closed</span>
                                                            @endif
                                                        </td>
                                                        <td data-label="Rule State">
                                                            @if ($rule->is_active)
                                                                <span class="status-pill status-active">Active</span>
                                                            @else
                                                                <span class="badge-closed">Inactive</span>
                                                            @endif
                                                        </td>
                                                        <td data-label="Actions">
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
        @json(route($clinicScheduleRouteNames['destroy'], $rule)), @json($rule->days_label)
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

                                <div id="scheduleRulesGridView" class="schedule-rules-view" hidden>
                                    <div class="schedule-rules-grid">
                                        @foreach ($schedules as $rule)
                                            <div class="schedule-rule-card">
                                                <div class="schedule-rule-card-top">
                                                    <div>
                                                        <div class="schedule-rule-card-title">{{ $rule->days_label }}
                                                        </div>
                                                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                                                            @if ($rule->status === 'open')
                                                                <span class="badge-open">Open</span>
                                                            @elseif($rule->status === 'limited')
                                                                <span class="badge-limited">Limited</span>
                                                            @else
                                                                <span class="badge-closed">Closed</span>
                                                            @endif

                                                            @if ($rule->is_active)
                                                                <span class="status-pill status-active">Active</span>
                                                            @else
                                                                <span class="badge-closed">Inactive</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="schedule-rule-card-meta">
                                                    <div>
                                                        <div class="schedule-rule-card-label">Opens</div>
                                                        <div class="schedule-rule-card-value">
                                                            {{ $rule->open_time ? date('g:i A', strtotime($rule->open_time)) : '—' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="schedule-rule-card-label">Closes</div>
                                                        <div class="schedule-rule-card-value">
                                                            {{ $rule->close_time ? date('g:i A', strtotime($rule->close_time)) : '—' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="schedule-rule-card-label">Lunch Break</div>
                                                        <div class="schedule-rule-card-value">
                                                            @if ($rule->break_time && $rule->break_time !== 'none')
                                                                @php [$bs,$be]=explode('-', $rule->break_time); @endphp
                                                                {{ date('g:i A', strtotime(trim($bs) . ':00')) }} –
                                                                {{ date('g:i A', strtotime(trim($be) . ':00')) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="schedule-rule-card-label">Max Slots</div>
                                                        <div class="schedule-rule-card-value flex items-center gap-2">
                                                            <span
                                                                class="font-bold text-[#8B0000]">{{ $rule->max_slots }}</span>
                                                            @if ($rule->status !== 'closed')
                                                                <div class="cap-bar w-16">
                                                                    <div class="cap-fill"
                                                                        style="width:{{ min(100, ($rule->max_slots / 30) * 100) }}%">
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="schedule-rule-card-actions ui-action-group">
                                                    <button type="button"
                                                        onclick='openRuleModal(
        "edit",
        {{ $rule->id }},
        {{ json_encode($rule) }}
    )'
                                                        class="ui-action-btn ui-action-edit" data-tooltip="Edit schedule"
                                                        aria-label="Edit schedule">

                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>

                                                    <button type="button" class="ui-action-btn ui-action-delete"
                                                        data-tooltip="Delete schedule" aria-label="Delete schedule"
                                                        onclick='openScheduleDeleteModal(
        @json(route($clinicScheduleRouteNames['destroy'], $rule)), @json($rule->days_label)
                                            )'>

                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div id="scheduleRulesEmptyState"
                                    class="empty-state-host clinic-schedule-empty-state-host"></div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-6">

                        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                                <div class="flex items-center gap-2"><i class="fa-solid fa-clock text-[#8B0000]"></i>
                                    <h2 class="font-bold text-gray-800 text-sm">Clinic Hours</h2>
                                </div>
                                <button type="button" onclick="openRuleModal()" class="ui-btn ui-btn-edit ui-btn-sm">
                                    <i class="fa-solid fa-pen"></i>
                                    <span>Edit</span>
                                </button>
                            </div>
                            <div class="p-4 space-y-0.5">
                                @foreach ($dayNames as $fullName => $abbr)
                                    @php $s = $scheduleByDay[$abbr] ?? null; @endphp
                                    <div
                                        class="flex justify-between items-center py-1.5 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                                        <span class="text-xs font-semibold text-gray-600">{{ $fullName }}</span>
                                        @if ($s && $s->status !== 'closed')
                                            <span class="text-xs font-bold text-[#8B0000]">{{ $s->hours_range }}</span>
                                        @else
                                            <span class="text-xs font-medium text-gray-400">Closed</span>
                                        @endif
                                    </div>
                                @endforeach
                                @if ($breakSchedule)
                                    <div class="pt-2 mt-1 border-t border-gray-100">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-gray-400 italic flex items-center gap-1"><i
                                                    class="fa-solid fa-mug-hot text-yellow-400"></i> Lunch</span>
                                            @php [$bs,$be]=explode('-',$breakSchedule->break_time); @endphp
                                            <span
                                                class="text-xs font-medium text-gray-500">{{ date('g:i A', strtotime(trim($bs) . ':00')) }}
                                                – {{ date('g:i A', strtotime(trim($be) . ':00')) }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-ban text-[#8B0000]"></i>
                                    <h2 class="font-bold text-gray-800 text-sm">Blocked Dates</h2>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openBlockModal()"
                                        class="ui-btn ui-btn-primary ui-btn-sm">
                                        <i class="fa-solid fa-plus"></i>
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>

                            <div class="p-4">
                                @if ($blockedDates->count())
                                    <div id="blockedDatesListView" class="blocked-dates-view">
                                        @foreach ($blockedDates as $blocked)
                                            @php
                                                $bd = \Carbon\Carbon::parse($blocked->date);
                                                $badgeCls = match ($blocked->reason) {
                                                    'Holiday' => 'badge-holiday',
                                                    'Dentist Unavailable' => 'badge-limited',
                                                    default => 'badge-closed',
                                                };
                                            @endphp

                                            <div
                                                class="blocked-list-item flex items-start gap-3 py-2.5 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                                                <div
                                                    class="blocked-date-pill w-9 h-9 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0 text-[#8B0000] text-xs font-bold">
                                                    {{ $bd->day }}
                                                </div>

                                                <div class="flex-1 min-w-0">
                                                    <p class="blocked-title text-xs font-bold text-gray-800 truncate">
                                                        {{ $bd->format('D, M j, Y') }}
                                                    </p>
                                                    <span
                                                        class="{{ $badgeCls }} mt-0.5 inline-block">{{ $blocked->reason }}</span>
                                                    @if ($blocked->note)
                                                        <p
                                                            class="blocked-note text-[10px] text-gray-400 mt-0.5 italic truncate">
                                                            {{ $blocked->note }}
                                                        </p>
                                                    @endif
                                                </div>

                                                <form action="{{ route($clinicScheduleRouteNames['unblock'], $blocked) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ui-action-btn ui-action-delete"
                                                        data-tooltip="Remove blocked date"
                                                        aria-label="Remove blocked date">

                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center py-10 text-center">
                                        <i class="fa-solid fa-check-circle text-4xl text-green-400 mb-3"></i>
                                        <p class="text-sm text-gray-400">No blocked dates</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b bg-gray-50">
                                <div class="flex items-center gap-2"><i
                                        class="fa-solid fa-umbrella-beach text-[#8B0000]"></i>
                                    <h2 class="font-bold text-gray-800 text-sm">Upcoming Holidays</h2>
                                </div>
                            </div>
                            <div class="p-4">
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
                                    $upcoming = collect($philippineHolidays)
                                    ->filter(
                                        fn($holiday, $date) =>
                                            \Carbon\Carbon::parse($date)
                                                ->startOfDay()
                                                ->gte($today)
                                    )
                                    ->sortKeys()
                                    ->take(5);
                                @endphp
                                @forelse($upcoming as $hDate => $holiday)
                                    @php
                                        $hC = \Carbon\Carbon::parse($hDate);

                                        $diff = (int) $today->diffInDays(
                                            $hC,
                                            false
                                        );

                                        $holidayName = is_array($holiday)
                                            ? ($holiday['name'] ?? 'Philippine Holiday')
                                            : (string) $holiday;

                                        $isBlockedHoliday = is_array($holiday)
                                            ? ($holiday['is_blocked_for_booking'] ?? true)
                                            : true;
                                    @endphp

                                    <div
                                        class="holiday-item flex items-center gap-3 py-2 border-b border-gray-50 last:border-b-0">

                                        <div class="w-10 text-center flex-shrink-0">
                                            <div class="month text-[10px] font-bold uppercase text-[#8B0000]">
                                                {{ $MONTHS_SHORT[$hC->month - 1] }}
                                            </div>

                                            <div class="day text-xl font-extrabold text-gray-800 leading-tight">
                                                {{ $hC->day }}
                                            </div>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="holiday-title text-xs font-semibold text-gray-800 truncate">
                                                {{ $holidayName }}
                                            </p>

                                            <p class="holiday-meta text-[10px] text-gray-400">
                                                {{ $diff === 0 ? 'Today' : ($diff === 1 ? 'Tomorrow' : "In $diff days") }}
                                            </p>
                                        </div>

                                        <span
                                            class="{{ $isBlockedHoliday ? 'holiday-badge badge-holiday' : 'badge-open' }} flex-shrink-0">
                                            {{ $isBlockedHoliday ? 'Non-Working' : 'Working Holiday' }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400 text-center py-4">No upcoming holidays.</p>
                                @endforelse
                            </div>
                        </div>

                    </div>
                    <section class="card lg:col-span-3 reserved-periods-wide-card">
                        <div class="card-header cs-rules-card-header">
                            <div class="card-header-left">
                                <span class="card-header-icon" aria-hidden="true">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </span>
                                <h2 class="card-title">Reserved Booking Periods</h2>
                            </div>
                            <div class="card-header-right cs-rules-header-actions reserved-period-header-actions">
                                <span class="cs-rules-count">
                                    {{ $reservedBookingPeriods->count() }}
                                    {{ \Illuminate\Support\Str::plural('period', $reservedBookingPeriods->count()) }}
                                </span>

                                <x-view-toggle id="reservedPeriodsViewToggle" storage-key="reservedPeriodsView"
                                    list-view="#reservedPeriodsListView" grid-view="#reservedPeriodsGridView" />

                                @if ($canCreateReservedPeriods)
                                    <button type="button" onclick="openReservedPeriodModal()"
                                        class="ui-btn ui-btn-primary">
                                        <i class="fa-solid fa-plus"></i>
                                        <span>Add Period</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="card-body reserved-periods-card-body">
                            @if ($reservedBookingPeriods->count())
                                <div id="reservedPeriodsListView" class="reserved-periods-view">
                                    <div class="overflow-x-auto px-2 pb-2 sm:px-0 sm:pb-0">
                                        <table class="data-table sched-table reserved-period-table">
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
                                                    @endphp
                                                    <tr>
                                                        <td data-label="Schedule" class="reserved-period-schedule-cell">
                                                            <strong
                                                                class="text-gray-800">{{ \Carbon\Carbon::parse($period->reserved_date)->format('M d, Y') }}</strong>
                                                            <div class="reserved-period-time">
                                                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                                                {{ date('g:i A', strtotime($period->start_time)) }}–{{ date('g:i A', strtotime($period->end_time)) }}
                                                            </div>
                                                        </td>
                                                        <td data-label="Purpose">
                                                            <div class="font-semibold text-gray-800">{{ $period->title }}
                                                            </div>
                                                            @if ($period->notes)
                                                                <div
                                                                    class="text-xs text-gray-400 mt-0.5 reserved-period-note">
                                                                    {{ $period->notes }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td data-label="Target Group">
                                                            <span
                                                                class="reserved-target-badge">{{ $period->target_label }}</span>
                                                        </td>
                                                        <td data-label="Booking">
                                                            {{ $period->booking_mode === 'timeslot' ? 'Date + timeslot' : 'Date only' }}
                                                            @if ($period->booking_mode === 'timeslot')
                                                                <div class="text-xs text-gray-400 mt-0.5">
                                                                    {{ $period->slots->count() }} selectable
                                                                    {{ \Illuminate\Support\Str::plural('slot', $period->slots->count()) }}
                                                                    · {{ $period->timeslot_duration_minutes }} min each
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td data-label="Capacity">
                                                            <strong
                                                                class="text-[#8B0000]">{{ $period->max_capacity }}</strong>
                                                            <span class="text-xs text-gray-400">patients</span>
                                                        </td>
                                                        <td data-label="Status">
                                                            @if ($isPastPeriod)
                                                                <span class="badge-closed">Past</span>
                                                            @elseif ($period->is_active)
                                                                <span class="status-pill status-active">Active</span>
                                                            @else
                                                                <span class="badge-closed">Inactive</span>
                                                            @endif
                                                        </td>
                                                        @if ($canManageReservedPeriods)
                                                            <td data-label="Actions">
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

                                <div id="reservedPeriodsGridView" class="reserved-periods-view" hidden>
                                    <div class="schedule-rules-grid reserved-periods-grid">
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
                                            @endphp
                                            <article class="schedule-rule-card reserved-period-card">
                                                <div class="schedule-rule-card-top">
                                                    <div class="reserved-period-card-heading">
                                                        <span class="reserved-period-card-date">
                                                            <i class="fa-regular fa-calendar"></i>
                                                            {{ \Carbon\Carbon::parse($period->reserved_date)->format('M d, Y') }}
                                                        </span>
                                                        <h3 class="schedule-rule-card-title">{{ $period->title }}</h3>
                                                    </div>

                                                    @if ($isPastPeriod)
                                                        <span class="badge-closed">Past</span>
                                                    @elseif ($period->is_active)
                                                        <span class="status-pill status-active">Active</span>
                                                    @else
                                                        <span class="badge-closed">Inactive</span>
                                                    @endif
                                                </div>

                                                <span class="reserved-target-badge">{{ $period->target_label }}</span>

                                                <div class="schedule-rule-card-meta reserved-period-card-meta">
                                                    <div>
                                                        <div class="schedule-rule-card-label">Reserved Time</div>
                                                        <div class="schedule-rule-card-value reserved-period-card-value">
                                                            <i class="fa-regular fa-clock"></i>
                                                            {{ date('g:i A', strtotime($period->start_time)) }}–{{ date('g:i A', strtotime($period->end_time)) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="schedule-rule-card-label">Booking</div>
                                                        <div class="schedule-rule-card-value">
                                                            {{ $period->booking_mode === 'timeslot' ? 'Date + timeslot' : 'Date only' }}
                                                            @if ($period->booking_mode === 'timeslot')
                                                                <span class="reserved-period-card-sub">
                                                                    {{ $period->slots->count() }} slots ·
                                                                    {{ $period->timeslot_duration_minutes }} min
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="schedule-rule-card-label">Capacity</div>
                                                        <div class="schedule-rule-card-value">
                                                            <strong
                                                                class="text-[#8B0000]">{{ $period->max_capacity }}</strong>
                                                            patients
                                                        </div>
                                                    </div>
                                                </div>

                                                @if ($period->notes)
                                                    <p class="reserved-period-card-note">{{ $period->notes }}</p>
                                                @endif

                                                @if ($canManageReservedPeriods)
                                                    <div class="schedule-rule-card-actions ui-action-group">
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
                                                            <button type="button" class="ui-action-btn ui-action-delete"
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
                                                @endif
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div id="reservedPeriodsEmptyState"
                                    class="empty-state-host clinic-schedule-empty-state-host"></div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <div id="appointmentDetailModal" class="ui-modal cs-modal">
        <div class="ui-modal-card cs-modal-card cs-detail-modal-card" onclick="event.stopPropagation()">
            <div class="modal-hdr">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold">Appointment Details</h3>
                        <p class="text-sm text-white/70 mt-0.5">Selected booked slot information</p>
                    </div>
                    <button onclick="closeAppointmentDetailModal()"
                        class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white hover:bg-white/30">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>

            <div class="modal-body">
                <div class="space-y-4">
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

                <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-100">
                    <button type="button" onclick="closeAppointmentDetailModal()" class="ui-btn ui-btn-primary">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="ruleModalBackdrop" class="ui-modal cs-modal cs-rule-modal modal-theme-primary">
        <div class="ui-modal-card cs-modal-card cs-rule-modal-card" onclick="event.stopPropagation()">

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

            <div class="modal-bd modal-form-body">
                <form id="ruleForm" method="POST" action="{{ route($clinicScheduleRouteNames['store']) }}"
                    data-global-validation data-discard-form data-form-validation-rule="clinicScheduleRule" novalidate>
                    @csrf
                    <div id="ruleMethodField"></div>
                    <div class="rule-modal-layout">
                        <div class="flex flex-col gap-4">

                            <div class="modal-section m-0">
                                <div class="modal-section-head">
                                    <div class="modal-section-icon">
                                        <i class="fa-solid fa-calendar-days"></i>
                                    </div>

                                    <div>
                                        <div class="modal-section-title">
                                            Applicable Days
                                        </div>

                                        <div class="modal-section-sub">
                                            Select one or more days for this rule.
                                        </div>
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
                                                data-discard-value="false" onclick="toggleDay(this)"
                                                aria-pressed="false">

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

                            <div class="modal-section rule-notes-section m-0 flex-1 flex flex-col" data-global-field>

                                <div class="modal-section-head">
                                    <div class="modal-section-icon">
                                        <i class="fa-solid fa-note-sticky"></i>
                                    </div>

                                    <div>
                                        <div class="modal-section-title">
                                            Additional Notes
                                        </div>

                                        <div class="modal-section-sub">
                                            Optional reminder or exception.
                                        </div>
                                    </div>
                                </div>

                                <div class="global-label-row">
                                    <label class="form-label" for="ruleNotes">
                                        Notes <span class="field-optional">optional</span>
                                    </label>

                                    <span id="ruleNotesCount" class="char-counter">
                                        0 / 150 characters
                                    </span>
                                </div>

                                <div class="voice-search-row rule-notes-field mb-2" data-voice-field>

                                    <div class="global-control-wrap global-form-textarea-wrap rule-notes-textarea-wrap"
                                        data-clearable-field>

                                        <textarea id="ruleNotes" name="notes" class="form-input-custom global-form-textarea rule-notes-textarea"
                                            maxlength="150" data-char-limit="150" data-char-counter="#ruleNotesCount"
                                            placeholder="e.g. Reduced operations due to holiday program." data-clearable-input></textarea>

                                        <button type="button" id="ruleNotesClearBtn"
                                            class="search-clear field-clear-btn field-clear-btn--textarea" data-field-clear
                                            aria-label="Clear notes" title="Clear notes">

                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <x-voice-input target="#ruleNotes" status-id="ruleNotesVoiceStatus"
                                        label="Voice input for schedule notes" title="Voice input" />
                                    <div id="ruleNotesError" class="global-field-error" data-error-for="ruleNotes"
                                        aria-hidden="true">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col h-full">
                            <div class="modal-section m-0 flex-1">
                                <div class="modal-section-head">
                                    <div class="modal-section-icon">
                                        <i class="fa-solid fa-hospital-user"></i>
                                    </div>

                                    <div>
                                        <div class="modal-section-title">
                                            Clinic Availability
                                        </div>

                                        <div class="modal-section-sub">
                                            Define whether the clinic is open, closed, or limited.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-5" data-global-field>
                                    <label class="form-label" for="ruleActivationState">
                                        Schedule State
                                    </label>

                                    <select id="ruleActivationState" class="form-select-custom js-custom-select"
                                        data-placeholder="Select schedule state">
                                        <option value="0" selected>Inactive</option>
                                        <option value="1">Active</option>
                                    </select>

                                    <div class="field-help">
                                        New schedule rules start as Inactive. To activate a replacement rule,
                                        set the current active rule for the same day(s) to Inactive first.
                                    </div>

                                    <div id="ruleStateError" class="global-field-error"
                                        data-error-for="ruleActivationState" aria-hidden="true">
                                    </div>
                                </div>

                                <div class="mb-5" data-global-field>
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

                                <div id="ruleTimeFields" class="rule-availability-grid">

                                    <div class="space-y-5">
                                        <div class="rule-time-select-grid">

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

                                            <div class="rule-closing-time-field" data-global-field>
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
                                                    data-error-for="ruleCloseTime" aria-hidden="true"></div>
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

                                                <input type="number" id="ruleMaxSlots"
                                                    class="global-number-stepper-input" value="5" min="1"
                                                    max="30" step="1" inputmode="numeric" autocomplete="off"
                                                    data-number-stepper-input data-field-label="Max Appointments"
                                                    data-validation-rule="wholeNumber">

                                                <button type="button" class="global-number-stepper-btn"
                                                    data-number-step="1" aria-label="Increase maximum appointments">

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
                                    </div>

                                    <div data-global-field>
                                        <label class="form-label">
                                            Lunch Break
                                        </label>

                                        <div id="ruleBreakGroup" class="break-chip-group break-chip-stack">

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

                    <div class="modal-ft rule-modal-footer">
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
    </div>

    <div id="blockModalBackdrop" class="ui-modal modal-theme-danger cs-modal cs-block-modal" aria-hidden="true">

        <form id="blockDateForm" action="{{ route($clinicScheduleRouteNames['block']) }}" method="POST"
            class="ui-modal-card modal-lg modal-card-form cs-modal-card cs-block-modal-card" role="dialog"
            aria-modal="true" aria-labelledby="blockDateModalTitle" data-global-validation data-discard-form
            data-discard-title="Discard blocked date?" data-discard-subtitle="You have unsaved blocked-date details."
            data-discard-message="Closing this modal will remove the blocked-date details you entered." novalidate>

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

            <div class="modal-bd">
                <div class="modal-section">
                    <div class="modal-section-head">
                        <div class="modal-section-icon">
                            <i class="fa-solid fa-calendar-xmark"></i>
                        </div>

                        <div>
                            <div class="modal-section-title">
                                Date Details
                            </div>

                            <div class="modal-section-sub">
                                Choose the blocked date and specify the reason.
                            </div>
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
                                min="{{ date('Y-m-d') }}" data-field-label="Date"
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
                                Note <span class="field-optional">optional</span>
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

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="blockModalBackdrop">

                    Cancel
                </button>

                <button type="submit" class="ui-btn ui-btn-danger">

                    <i class="fa-solid fa-ban"></i>
                    <span>Block Date</span>
                </button>
            </div>
        </form>
    </div>

    <div id="reservedPeriodModalBackdrop" class="ui-modal cs-modal modal-theme-primary" aria-hidden="true">
        <div class="ui-modal-card cs-modal-card cs-reserved-modal-card" onclick="event.stopPropagation()">
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

                <div class="modal-bd modal-form-body modal-scroll-body">
                    @csrf
                    <div id="reservedPeriodMethodField"></div>
                    <input id="reservedPeriodId" type="hidden" name="reserved_period_id">

                    <div class="reserved-period-form-grid">
                        <div class="modal-section">
                            <div class="modal-section-head">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-calendar-day"></i>
                                </div>
                                <div>
                                    <div class="modal-section-title">Period Details</div>
                                    <div class="modal-section-sub">Set the purpose, date, and reserved hours.</div>
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
                                    <label for="reservedDate" class="form-label">Date <span
                                            class="text-red-500">*</span></label>
                                    <div class="global-control-wrap" data-flatpickr-trigger>
                                        <i class="fa-regular fa-calendar global-control-icon" aria-hidden="true"></i>
                                        <input id="reservedDate" name="reserved_date" type="text" required readonly
                                            data-field-label="Date"
                                            class="form-input-custom global-control-with-icon js-flatpickr-date-min-today @error('reserved_date', 'reservedPeriod') is-invalid @enderror"
                                            data-flatpickr-append-to-body
                                            data-flatpickr-disabled-date-tooltip="This date already has an active reserved booking period"
                                            data-flatpickr-disabled-dates='[]'
                                            placeholder="Select date">
                                    </div>
                                    <div class="global-field-error @error('reserved_date', 'reservedPeriod') show @enderror"
                                        data-error-for="reservedDate"
                                        aria-hidden="{{ $reservedErrors->has('reserved_date') ? 'false' : 'true' }}">
                                        @error('reserved_date', 'reservedPeriod')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>

                                <div class="reserved-time-grid">
                                    <div data-global-field>
                                        <label for="reservedStartTime" class="form-label">Start Time <span
                                                class="text-red-500">*</span></label>
                                        <div class="global-control-wrap reserved-time-control" data-flatpickr-trigger>
                                            <i class="fa-regular fa-clock global-control-icon" aria-hidden="true"></i>
                                            <input id="reservedStartTime" name="start_time" type="text" required
                                                readonly data-field-label="Start Time"
                                                class="form-input-custom global-control-with-icon js-flatpickr-time @error('start_time', 'reservedPeriod') is-invalid @enderror"
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
                                                class="form-input-custom global-control-with-icon js-flatpickr-time @error('end_time', 'reservedPeriod') is-invalid @enderror"
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
                                        Inactive saves the setup only. Active reserves the selected date and time and sends booking notifications to eligible users.
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

                        <div class="modal-section">
                            <div class="modal-section-head">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <div>
                                    <div class="modal-section-title">Target & Capacity</div>
                                    <div class="modal-section-sub">Choose who may book and limit the available places.
                                    </div>
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

                                <div id="reservedStudentFields" class="reserved-student-fields">
                                    <div data-global-field>
                                        <label for="reservedProgramCode" class="form-label">Program <span
                                                class="text-red-500">*</span></label>
                                        <select id="reservedProgramCode" name="program_code" data-field-label="Program"
                                            class="form-select-custom js-custom-select" data-placeholder="Select program"
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

                                    <div class="reserved-student-row">
                                        <div data-global-field>
                                            <label for="reservedYearLevel" class="form-label">Year Level <span
                                                    class="text-red-500">*</span></label>
                                            <select id="reservedYearLevel" name="year_level"
                                                class="form-select-custom js-custom-select"
                                                data-placeholder="Select year level" data-field-label="Year Level"
                                                onchange="updateReservedStudentTargetDropdowns(document.getElementById('reservedProgramCode').value, this.value, '')">
                                                <option value="">Select year</option>
                                                @foreach ($studentTargetOptions->pluck('year_level')->filter()->unique()->sort() as $year)
                                                    <option value="{{ $year }}">Year {{ $year }}</option>
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

                    <div class="modal-section reserved-notes-section mt-5" data-global-field>
                        <div class="modal-section-head">
                            <div class="modal-section-icon">
                                <i class="fa-solid fa-note-sticky"></i>
                            </div>

                            <div>
                                <div class="modal-section-title">
                                    Additional Notes
                                </div>

                                <div class="modal-section-sub">
                                    Add optional instructions for this reserved booking period.
                                </div>
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

                    <div id="reservedTimeslotBuilder" class="modal-section reserved-timeslot-section mt-5">
                        <div class="modal-section-head reserved-timeslot-heading">
                            <div class="modal-section-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <div class="modal-section-title">Selectable Timeslots</div>
                                <div class="modal-section-sub">Create up to
                                    {{ \App\Models\ReservedBookingPeriod::MAX_CAPACITY }} times patients can choose. Each
                                    timeslot is for one patient.</div>
                            </div>
                            <div class="reserved-timeslot-total">
                                <span id="reservedTimeslotTotal">0</span>
                                <small>total patients</small>
                            </div>
                        </div>

                        <div class="reserved-timeslot-add-row">
                            <div data-global-field>
                                <label for="reservedSlotDuration" class="form-label">Duration (minutes)</label>
                                <input id="reservedSlotDuration" name="timeslot_duration_minutes" type="number"
                                    min="5" max="240" step="5" required class="form-input-custom"
                                    data-field-label="Duration" value="30" inputmode="numeric"
                                    onchange="updateReservedSlotDuration()">
                                @error('timeslot_duration_minutes', 'reservedPeriod')
                                    <p class="global-field-error show">{{ $message }}</p>
                                @enderror
                            </div>
                            <div data-global-field>
                                <label for="reservedNewSlotTime" class="form-label">
                                    Timeslot
                                </label>

                                <div class="global-control-wrap reserved-time-control" data-flatpickr-trigger>
                                    <i class="fa-regular fa-clock global-control-icon" aria-hidden="true"></i>

                                    <input id="reservedNewSlotTime" type="text" readonly
                                        class="form-input-custom global-control-with-icon js-flatpickr-time"
                                        value="09:00" placeholder="Select timeslot">
                                </div>
                            </div>
                            <button id="reservedAddTimeslotButton" type="button"
                                class="ui-btn ui-btn-primary" onclick="addReservedTimeslot()">
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
        const TIME_ROWS = [{
                h: 9,
                l: '9:00 AM'
            }, {
                h: 10,
                l: '10:00 AM'
            },
            {
                h: 11,
                l: '11:00 AM'
            }, {
                h: 12,
                l: '12:00 PM'
            },
            {
                h: 13,
                l: '1:00 PM'
            }, {
                h: 14,
                l: '2:00 PM'
            },
            {
                h: 15,
                l: '3:00 PM'
            }, {
                h: 16,
                l: '4:00 PM'
            }
        ];

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

        function getServiceColor(serviceType) {
            const service = (serviceType || '').toLowerCase();

            if (service.includes('oral check')) {
                return 'background:#dbeafe;border-left:3px solid #3b82f6;color:#1e3a8a;';
            }

            if (service.includes('cleaning')) {
                return 'background:#dcfce7;border-left:3px solid #22c55e;color:#166534;';
            }

            if (service.includes('surgery')) {
                return 'background:#fef3c7;border-left:3px solid #f59e0b;color:#92400e;';
            }

            if (
                service.includes('restoration') ||
                service.includes('prosthesis')
            ) {
                return 'background:#f3e8ff;border-left:3px solid #a855f7;color:#6b21a8;';
            }

            return 'background:#f3f4f6;border-left:3px solid #6b7280;color:#374151;';
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

            TIME_ROWS.forEach(({
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
                                const service = appt.service_type === 'Others' ? (appt
                                    .other_services || 'Other Service') : appt.service_type;
                                const serviceStyle = getServiceColor(service);
                                return `<button type="button" onclick='openAppointmentDetailModal(${JSON.stringify(appt)})'
                                    style="${serviceStyle}margin:4px;border-radius:8px;padding:6px 7px;font-size:.62rem;line-height:1.25;font-weight:600;box-shadow:0 1px 3px rgba(0,0,0,.06);width:calc(100% - 8px);text-align:left;cursor:pointer;transition:transform .15s ease,box-shadow .15s ease;"
                                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 10px rgba(0,0,0,.10)'"
                                    onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 1px 3px rgba(0,0,0,.06)'"
                                    title="Click to view details">
                                    <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${appt.patient_name}</div>
                                    <div style="font-size:.58rem;opacity:.9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${service}</div>
                                </button>`;
                            }).join('');
                        }
                    }
                    html += `<div class="cal-slot ${state}">${inner}</div>`;
                });
            });

            document.getElementById('weekGrid').innerHTML = html;
        }

        document.getElementById('prevWeek').addEventListener('click', () => {
            weekOffset--;
            buildWeekGrid();
        });
        document.getElementById('nextWeek').addEventListener('click', () => {
            weekOffset++;
            buildWeekGrid();
        });
        document.getElementById('todayBtn').addEventListener('click', () => {
            weekOffset = 0;
            buildWeekGrid();
        });

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

            if (!backdrop || !form || !methodField || !title || !activationState || !status || !openTime || !closeTime || !maxSlots || !notes ||
                !timeFields) {
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
            setCustomSelectValue(status, 'open');
            setCustomSelectValue(openTime, '09:00');
            setCustomSelectValue(closeTime, '17:00');
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
                    submitBtn.className = 'ui-btn ui-btn-edit';
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

        function sortScheduleDays(days) {
            const order = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            return [...new Set(days)].sort((a, b) => order.indexOf(a) - order.indexOf(b));
        }

        function findConflictingScheduleDays(activeDays) {
            const selected = new Set(activeDays);
            const conflicts = [];

            (scheduleRules || []).forEach(rule => {
                if (!rule || !rule.is_active) return;
                if (editingId !== null && String(rule.id) === String(editingId)) return;

                (rule.days || []).forEach(day => {
                    if (selected.has(day)) conflicts.push(day);
                });
            });

            return sortScheduleDays(conflicts);
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

                    const activationState =
                        document.getElementById('ruleActivationState')?.value || '0';

                    const status =
                        document.getElementById('ruleStatus')?.value || '';

                    const openTimeField =
                        document.getElementById('ruleOpenTime');

                    const closeTimeField =
                        document.getElementById('ruleCloseTime');

                    const maxSlotsField =
                        document.getElementById('ruleMaxSlots');

                    const openTime = openTimeField?.value || '';
                    const closeTime = closeTimeField?.value || '';
                    const maxSlots = Number(maxSlotsField?.value || 0);

                    let valid = true;
                    let firstInvalid = null;

                    window.clearGlobalGroupError?.(
                        daysGroup,
                        'rule-days'
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
                        const conflicts =
                            findConflictingScheduleDays(activeDays);

                        if (conflicts.length) {
                            window.showGlobalGroupError?.(
                                daysGroup,
                                'rule-days',
                                `An active schedule already exists for ${conflicts.join(', ')}. Set the current active schedule to Inactive before activating this rule.`
                            );

                            valid = false;
                            firstInvalid ||= daysGroup;
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

            if (!form) return;

            const validation =
                window.validateGlobalForm?.(form);

            if (!validation || !validation.valid) {
                return;
            }

            const activeDays = Array.from(
                document.querySelectorAll(
                    '#ruleModalBackdrop .day-toggle.active'
                )
            ).map(day => day.dataset.day);

            const activationState =
                document.getElementById('ruleActivationState').value;

            const status =
                document.getElementById('ruleStatus').value;

            const openTime =
                document.getElementById('ruleOpenTime').value;

            const closeTime =
                document.getElementById('ruleCloseTime').value;

            const maxSlots =
                document.getElementById('ruleMaxSlots').value;

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
                document.getElementById('ruleNotes').value
            );
            window.DiscardChanges?.markSubmitting(form);
            form.requestSubmit();
        }

        function getLocalDateString(date = new Date()) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function openBlockModal() {
            const backdrop = document.getElementById('blockModalBackdrop');
            const blockDate = document.getElementById('blockDate');

            if (!backdrop || !blockDate) {
                console.error('Block modal elements not found.');
                return;
            }

            document
                .querySelectorAll(
                    '#blockDateForm .global-field-error'
                )
                .forEach(error => {
                    error.innerHTML = '';
                    error.classList.remove('show');
                });

            document
                .querySelectorAll(
                    '#blockDateForm .is-invalid'
                )
                .forEach(element => {
                    element.classList.remove('is-invalid');
                });

            const today = getLocalDateString();

            blockDate.removeAttribute('max');
            blockDate.setAttribute('min', today);
            blockDate.classList.remove('js-flatpickr-date-max-today');
            blockDate.classList.add('js-flatpickr-date-min-today');

            if (blockDate._flatpickr) {
                blockDate._flatpickr.set('maxDate', null);
                blockDate._flatpickr.set('minDate', today);
                blockDate._flatpickr.clear();
            } else {
                blockDate.value = '';
            }

            setCustomSelectValue(
                document.getElementById('blockReason'),
                'Holiday'
            );

            window.openModal('blockModalBackdrop');
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

        function openScheduleDeleteModal(
            actionUrl,
            scheduleName
        ) {
            window.openDeleteConfirmModal?.({
                modalId: 'scheduleDeleteModal',

                formId: 'scheduleDeleteForm',

                nameId: 'scheduleDeleteName',

                action: actionUrl,

                itemName: scheduleName,
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

            document.getElementById('blockDate')?.addEventListener('input', () => clearFieldError('blockDateError',
                'blockDate'));
            document.getElementById('blockReason')?.addEventListener('change', () => clearFieldError(
                'blockReasonError', 'blockReason'));
            document.getElementById('blockNote')?.addEventListener('input', () => clearFieldError('blockNoteError',
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
                            required readonly class="form-input-custom global-control-with-icon js-flatpickr-time"
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
                submitButton.className = 'ui-btn ui-btn-edit';
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
