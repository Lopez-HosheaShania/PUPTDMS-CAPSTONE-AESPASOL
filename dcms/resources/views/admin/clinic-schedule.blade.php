@extends('layouts.admin')

@section('title', 'Clinic Schedule | PUP Taguig Dental Clinic')

@section('content')
@php
$openRules = $schedules->where('status', '!=', 'closed');
$openDays = $openRules->sum(fn($s) => count($s->days ?? []));
$maxSlots = $openRules->max('max_slots') ?? 0;
$blockedThisMonth = $blockedDates->filter(fn($b) => \Carbon\Carbon::parse($b->date)->isCurrentMonth())->count();
$holidaysThisMonth = collect($philippineHolidays)
->filter(fn($name, $date) => \Carbon\Carbon::parse($date)->isCurrentMonth())
->count();

$scheduleByDay = [];
foreach ($schedules as $s) {
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

<main id="mainContent" class="admin-page-shell clinic-schedule-page page-enter mode-list">
    <div class="full">

        @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const hasRuleErrors =
                    @json(
                        $errors -> has('days') ||
                        $errors -> has('status') ||
                        $errors -> has('open_time') ||
                        $errors -> has('close_time') ||
                        $errors -> has('max_slots') ||
                        $errors -> has('notes'));

                const hasBlockErrors =
                    @json($errors -> has('date') || $errors -> has('reason') || $errors -> has('note'));

                if (hasRuleErrors) {
                    openRuleModal();
                }

                if (hasBlockErrors) {
                    openBlockModal();
                }

                @if ($errors -> has('days'))
                    setFieldError('ruleDaysError', @json($errors -> first('days')), null, 'ruleDaysGroup');
                @endif
                @if ($errors -> has('status'))
                    setFieldError('ruleStatusError', @json($errors -> first('status')), 'ruleStatus');
                @endif
                @if ($errors -> has('open_time'))
                    setFieldError('ruleOpenTimeError', @json($errors -> first('open_time')), 'ruleOpenTime');
                @endif
                @if ($errors -> has('close_time'))
                    setFieldError('ruleCloseTimeError', @json($errors -> first('close_time')), 'ruleCloseTime');
                @endif
                @if ($errors -> has('max_slots'))
                    setFieldError('ruleMaxSlotsError', @json($errors -> first('max_slots')), 'ruleMaxSlots');
                @endif
                @if ($errors -> has('notes'))
                    setFieldError('ruleNotesError', @json($errors -> first('notes')), 'ruleNotes');
                @endif

                @if ($errors -> has('date'))
                    setFieldError('blockDateError', @json($errors -> first('date')), 'blockDate');
                @endif
                @if ($errors -> has('reason'))
                    setFieldError('blockReasonError', @json($errors -> first('reason')), 'blockReason');
                @endif
                @if ($errors -> has('note'))
                    setFieldError('blockNoteError', @json($errors -> first('note')), 'blockNote');
                @endif
            });
        </script>
        @endif

        <div class="page-banner">
            <div class="page-banner-inner">

                <div>
                    <h1 class="page-title">Clinic Schedule</h1>
                </div>

                <div class="flex items-center gap-3 flex-wrap page-actions">
                    <button type="button" onclick="openRuleModal()" class="cs-banner-action-btn">
                        <i class="fa-solid fa-plus"></i>
                        <span>Add Schedule Rule</span>
                    </button>

                    <button type="button" onclick="openBlockModal()"
                        class="cs-banner-action-btn cs-banner-action-danger">
                        <i class="fa-solid fa-ban"></i>
                        <span>Block Date</span>
                    </button>
                </div>
            </div>
        </div>

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

                            <button id="prevWeek"
                                class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:border-[#8B0000] hover:text-[#8B0000] transition-all text-xs"><i
                                    class="fa-solid fa-chevron-left"></i></button>
                            <span id="weekRangeLabel"
                                class="text-xs font-semibold text-gray-600 px-1 min-w-[140px] text-center"></span>
                            <button id="nextWeek"
                                class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:border-[#8B0000] hover:text-[#8B0000] transition-all text-xs"><i
                                    class="fa-solid fa-chevron-right"></i></button>
                            <button id="todayBtn"
                                class="text-[10px] font-bold text-[#8B0000] bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg hover:bg-red-100 transition-colors">Today</button>
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

                            <div class="view-toggle-container" id="scheduleRulesViewToggle" aria-label="View options">
                                <span class="view-slider" aria-hidden="true"></span>

                                <button type="button" class="btn-view-mode active" id="scheduleRulesListViewBtn"
                                    title="List view" aria-label="List view" aria-pressed="true">
                                    <i class="fa-solid fa-table-list"></i>
                                </button>

                                <button type="button" class="btn-view-mode" id="scheduleRulesGridViewBtn"
                                    title="Grid view" aria-label="Grid view" aria-pressed="false">
                                    <i class="fa-solid fa-grip"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    @if ($schedules->count())
                    <div id="scheduleRulesListView" class="schedule-rules-view">
                        <div class="overflow-x-auto px-2 pb-2 sm:px-0 sm:pb-0">
                            <table class="sched-table">
                                <thead>
                                    <tr>
                                        <th>Day(s)</th>
                                        <th>Opens</th>
                                        <th>Closes</th>
                                        <th>Lunch Break</th>
                                        <th>Max Slots</th>
                                        <th>Status</th>
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
                                                <span class="font-bold text-[#8B0000]">{{ $rule->max_slots }}</span>
                                                @if ($rule->status !== 'closed')
                                                <div class="cap-bar w-16">
                                                    <div class="cap-fill"
                                                        style="width:{{ min(100, ($rule->max_slots / 10) * 100) }}%">
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
                                        <td data-label="Actions">
                                            <div class="cs-action-group">
                                                <button type="button"
                                                    onclick='openRuleModal("edit", {{ $rule->id }}, {{ json_encode($rule) }})'
                                                    class="cs-action-btn cs-action-edit" data-tooltip="Edit"
                                                    aria-label="Edit schedule rule">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>

                                                <button type="button" class="cs-action-btn cs-action-delete"
                                                    data-tooltip="Delete" aria-label="Delete schedule rule"
                                                    onclick='openScheduleDeleteModal(@json(route("admin.clinic_schedule.destroy", $rule)), @json($rule->days_label))'>
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
                                        <div class="schedule-rule-card-title">{{ $rule->days_label }}</div>
                                        <div class="mt-2">
                                            @if ($rule->status === 'open')
                                            <span class="badge-open">Open</span>
                                            @elseif($rule->status === 'limited')
                                            <span class="badge-limited">Limited</span>
                                            @else
                                            <span class="badge-closed">Closed</span>
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
                                            <span class="font-bold text-[#8B0000]">{{ $rule->max_slots }}</span>
                                            @if ($rule->status !== 'closed')
                                            <div class="cap-bar w-16">
                                                <div class="cap-fill"
                                                    style="width:{{ min(100, ($rule->max_slots / 10) * 100) }}%">
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="schedule-rule-card-actions cs-action-group">
                                    <button type="button"
                                        onclick='openRuleModal("edit", {{ $rule->id }}, {{ json_encode($rule) }})'
                                        class="cs-action-btn cs-action-edit" data-tooltip="Edit"
                                        aria-label="Edit schedule rule">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button type="button" class="cs-action-btn cs-action-delete" data-tooltip="Delete"
                                        aria-label="Delete schedule rule"
                                        onclick='openScheduleDeleteModal(@json(route("admin.clinic_schedule.destroy", $rule)), @json($rule->days_label))'>
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="text-center py-10">
                        <i class="fa-solid fa-calendar-xmark text-3xl text-gray-300 mb-2 block"></i>
                        <p class="text-gray-400 text-sm">
                            No rules yet.
                            <button onclick="openRuleModal()" class="text-[#8B0000] font-semibold hover:underline">Add
                                one.</button>
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">

                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-2"><i class="fa-solid fa-clock text-[#8B0000]"></i>
                            <h2 class="font-bold text-gray-800 text-sm">Clinic Hours</h2>
                        </div>
                        <button onclick="openRuleModal()"
                            class="text-xs text-[#8B0000] font-semibold hover:underline flex items-center gap-1"><i
                                class="fa-solid fa-pen text-[9px]"></i> Edit</button>
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
                                <span class="text-xs font-medium text-gray-500">{{ date('g:i A', strtotime(trim($bs) .
                                    ':00')) }}
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
                            <button onclick="openBlockModal()"
                                class="text-xs text-[#8B0000] font-semibold hover:underline flex items-center gap-1">
                                <i class="fa-solid fa-plus text-[9px]"></i> Add
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
                                    <span class="{{ $badgeCls }} mt-0.5 inline-block">{{ $blocked->reason }}</span>
                                    @if ($blocked->note)
                                    <p class="blocked-note text-[10px] text-gray-400 mt-0.5 italic truncate">
                                        {{ $blocked->note }}
                                    </p>
                                    @endif
                                </div>

                                <form action="{{ route('admin.clinic_schedule.unblock', $blocked) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="blocked-remove-btn" title="Remove">
                                        <i class="fa-solid fa-xmark text-xs"></i>
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
                        <div class="flex items-center gap-2"><i class="fa-solid fa-umbrella-beach text-[#8B0000]"></i>
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
                        ->filter(fn($n, $d) => \Carbon\Carbon::parse($d)->gte($today))
                        ->take(5);
                        @endphp
                        @forelse($upcoming as $hDate => $hName)
                        @php
                        $hC = \Carbon\Carbon::parse($hDate);
                        $diff = (int) $today->diffInDays($hC, false);
                        @endphp
                        <div class="holiday-item flex items-center gap-3 py-2 border-b border-gray-50 last:border-b-0">
                            <div class="w-10 text-center flex-shrink-0">
                                <div class="month text-[10px] font-bold uppercase text-[#8B0000]">
                                    {{ $MONTHS_SHORT[$hC->month - 1] }}</div>
                                <div class="day text-xl font-extrabold text-gray-800 leading-tight">
                                    {{ $hC->day }}</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="holiday-title text-xs font-semibold text-gray-800 truncate">
                                    {{ $hName }}</p>
                                <p class="holiday-meta text-[10px] text-gray-400">
                                    {{ $diff === 0 ? 'Today' : ($diff === 1 ? 'Tomorrow' : "In $diff days") }}
                                </p>
                            </div>
                            <span class="holiday-badge badge-holiday flex-shrink-0">Holiday</span>
                        </div>
                        @empty
                        <p class="text-xs text-gray-400 text-center py-4">No upcoming holidays.</p>
                        @endforelse
                    </div>
                </div>

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
                    <div id="detailPatientName" class="form-ctrl bg-gray-50">—</div>
                </div>

                <div>
                    <label class="form-label">Service Type</label>
                    <div id="detailServiceType" class="form-ctrl bg-gray-50">—</div>
                </div>

                <div>
                    <label class="form-label">Schedule</label>
                    <div id="detailSchedule" class="form-ctrl bg-gray-50">—</div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-100">
                <button type="button" onclick="closeAppointmentDetailModal()"
                    class="px-5 py-2 rounded-xl bg-[#8B0000] hover:bg-[#760000] text-white text-sm font-bold shadow">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div id="ruleModalBackdrop" class="ui-modal cs-modal cs-rule-modal">
    <div class="ui-modal-card cs-modal-card cs-rule-modal-card" onclick="event.stopPropagation()">
        <div class="modal-hdr modal-form-hdr">

            <div class="modal-title-row">
                <div class="modal-title-block">
                    <h3 class="text-xl font-extrabold" id="ruleModalTitle">Add Schedule Rule</h3>
                    <p class="modal-title-sub">Choose clinic days, set operating hours, and control booking capacity.
                    </p>
                </div>

                <button type="button" onclick="closeRuleModal()" class="modal-close-btn">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="modal-body modal-form-body">
            <form id="ruleForm" method="POST" action="{{ route('admin.clinic_schedule.store') }}">
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
                                    <div class="modal-section-title">Applicable Days</div>
                                    <div class="modal-section-sub">Select one or more days for this rule.</div>
                                </div>
                            </div>

                            <label class="form-label">Select Days <span class="text-red-400">*</span></label>
                            <div id="ruleDaysGroup" class="day-toggle-group mt-1">
                                @foreach (['Mon' => 'M', 'Tue' => 'T', 'Wed' => 'W', 'Thu' => 'Th', 'Fri' => 'F', 'Sat'
                                => 'S', 'Sun' => 'Su'] as $abbr => $lbl)
                                <div class="day-toggle" data-day="{{ $abbr }}" onclick="toggleDay(this)">
                                    {{ $lbl }}
                                </div>
                                @endforeach
                            </div>
                            <div class="form-help">You can apply one schedule to multiple weekdays.</div>
                            <div id="ruleDaysError" class="field-error"></div>
                        </div>

                        <div class="modal-section rule-notes-section m-0 flex-1 flex flex-col">
                            <div class="modal-section-head">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-note-sticky"></i>
                                </div>
                                <div>
                                    <div class="modal-section-title">Additional Notes</div>
                                    <div class="modal-section-sub">Optional reminder or exception.</div>
                                </div>
                            </div>

                            <label class="form-label" for="ruleNotes">Notes (optional)</label>

                            <div class="rule-notes-voice-row mb-2">
                                <div class="rule-notes-textarea-wrap">
                                    <textarea id="ruleNotes" class="form-ctrl resize-none rule-notes-textarea"
                                        maxlength="150"
                                        placeholder="e.g. Reduced operations due to holiday program..."></textarea>
                                    <button type="button" id="ruleNotesClearBtn" class="rule-notes-clear-btn hidden"
                                        aria-label="Clear notes" title="Clear notes">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div class="rule-notes-voice-toggle" id="ruleNotesVoiceToggle"></div>
                            </div>

                            <div class="form-help flex items-center justify-between gap-2 mt-auto">
                                <span>Maximum of 150 characters.</span>
                                <span id="ruleNotesCount" class="notes-counter">0/150</span>
                            </div>
                            <div id="ruleNotesError" class="field-error"></div>
                        </div>
                    </div>

                    <div class="flex flex-col h-full">
                        <div class="modal-section m-0 flex-1">
                            <div class="modal-section-head">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-hospital-user"></i>
                                </div>
                                <div>
                                    <div class="modal-section-title">Clinic Availability</div>
                                    <div class="modal-section-sub">Define whether the clinic is open, closed, or
                                        limited.</div>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label" for="ruleStatus">Clinic Status</label>
                                <select id="ruleStatus" class="form-ctrl form-sel"
                                    onchange="toggleStatusFields(this.value)">
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                    <option value="limited">Limited Hours</option>
                                </select>
                                <div id="ruleStatusError" class="field-error"></div>
                            </div>

                            <div id="ruleTimeFields" class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5">
                                <div class="space-y-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label" for="ruleOpenTime">Opening Time</label>
                                            <select id="ruleOpenTime" class="form-ctrl form-sel">
                                                <option value="07:00">7:00 AM</option>
                                                <option value="08:00">8:00 AM</option>
                                                <option value="09:00" selected>9:00 AM</option>
                                                <option value="10:00">10:00 AM</option>
                                            </select>
                                            <div id="ruleOpenTimeError" class="field-error"></div>
                                        </div>

                                        <div>
                                            <label class="form-label" for="ruleCloseTime">Closing Time</label>
                                            <select id="ruleCloseTime" class="form-ctrl form-sel">
                                                <option value="15:00">3:00 PM</option>
                                                <option value="16:00">4:00 PM</option>
                                                <option value="17:00" selected>5:00 PM</option>
                                                <option value="18:00">6:00 PM</option>
                                            </select>
                                            <div id="ruleCloseTimeError" class="field-error"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label" for="ruleMaxSlots">Max Appointments / Day</label>
                                        <div class="slot-stepper mt-1">
                                            <button type="button" onclick="adjSlots(-1)"
                                                class="slot-stepper-btn">−</button>
                                            <input type="text" id="ruleMaxSlots"
                                                class="form-ctrl slot-stepper-input no-native-spinner" value="5"
                                                inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                                            <button type="button" onclick="adjSlots(1)"
                                                class="slot-stepper-btn">+</button>
                                        </div>
                                        <div class="form-help">Set how many appointments may be accepted.</div>
                                        <div id="ruleMaxSlotsError" class="field-error"></div>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Lunch Break</label>
                                    <div id="ruleBreakGroup"
                                        style="display: flex; flex-direction: column; gap: 0.6rem; margin-top: 0.25rem;">
                                        <div class="break-chip selected" data-val="12:00-13:00"
                                            onclick="selectBreak(this)">
                                            12:00 – 1:00 PM
                                        </div>
                                        <div class="break-chip" data-val="13:00-14:00" onclick="selectBreak(this)">
                                            1:00 – 2:00 PM
                                        </div>
                                        <div class="break-chip" data-val="none" onclick="selectBreak(this)">
                                            <i class="fa-solid fa-ban text-[10px]"></i> No Break
                                        </div>
                                    </div>
                                    <div id="ruleBreakError" class="field-error"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer rule-modal-footer">
                    <button type="button" onclick="closeRuleModal()" class="btn-soft">Cancel</button>
                    <button type="button" onclick="submitRule()" class="rule-save-btn">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Save Rule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="blockModalBackdrop" class="ui-modal cs-modal cs-block-modal">
    <div class="ui-modal-card cs-modal-card cs-block-modal-card" onclick="event.stopPropagation()">
        <div class="modal-hdr modal-form-hdr">

            <div class="modal-title-row">
                <div class="modal-title-block">
                    <h3 class="text-xl font-extrabold">Block Date</h3>
                    <p class="modal-title-sub">Prevent appointments from being booked on a specific date.</p>
                </div>

                <button type="button" onclick="closeBlockModal()" class="modal-close-btn">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="modal-body modal-form-body">
            <form action="{{ route('admin.clinic_schedule.block') }}" method="POST"
                onsubmit="return validateBlockDateBeforeSubmit(event)">
                @csrf

                <div class="modal-section">
                    <div class="modal-section-head">
                        <div class="modal-section-icon">
                            <i class="fa-solid fa-calendar-xmark"></i>
                        </div>
                        <div>
                            <div class="modal-section-title">Date Details</div>
                            <div class="modal-section-sub">Choose the blocked date and specify the reason.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="blockDate">Date <span class="text-red-400">*</span></label>
                        <div class="fp-date-input-wrap">
                            <input type="text" id="blockDate" name="date"
                                class="form-ctrl fp-date-input js-flatpickr-date-min-today" required readonly
                                min="{{ date('Y-m-d') }}" placeholder="Select blocked date">

                            <i class="fa-solid fa-calendar-days fp-date-icon"></i>
                        </div>
                        <div id="blockDateError" class="field-error"></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="blockReason">Reason <span class="text-red-400">*</span></label>
                        <select id="blockReason" name="reason" class="form-ctrl form-sel">
                            <option value="Holiday">Holiday</option>
                            <option value="Dentist Unavailable">Dentist Unavailable</option>
                            <option value="Clinic Maintenance">Clinic Maintenance</option>
                            <option value="Special Event">Special Event</option>
                            <option value="Other">Other</option>
                        </select>
                        <div id="blockReasonError" class="field-error"></div>
                    </div>

                    <div>
                        <label class="form-label" for="blockNote">Note (optional)</label>
                        <input type="text" id="blockNote" name="note" class="form-ctrl"
                            placeholder="e.g. National holiday, maintenance, outreach event...">
                        <div class="form-help">Add extra context for admins viewing blocked dates later.</div>
                        <div id="blockNoteError" class="field-error"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeBlockModal()" class="btn-soft">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-ban mr-1.5"></i> Block Date
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="scheduleDeleteModal" class="ui-modal modal-overlay cs-delete-modal" aria-hidden="true">

    <div class="modal-box-inner cs-delete-modal-card" onclick="event.stopPropagation()" role="dialog" aria-modal="true"
        aria-labelledby="scheduleDeleteTitle">

        <div class="cs-delete-head">
            <div class="cs-delete-head-left">
                <div class="cs-delete-icon">
                    <i class="fa-solid fa-trash"></i>
                </div>

                <div>
                    <h3 id="scheduleDeleteTitle">Delete Schedule Rule</h3>
                    <p>This action requires confirmation</p>
                </div>
            </div>

            <button type="button" class="cs-delete-x" onclick="closeScheduleDeleteModal()"
                aria-label="Close delete modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="cs-delete-body">
            <div class="cs-delete-alert">
                <i class="fa-solid fa-triangle-exclamation"></i>

                <div>
                    <p>
                        Are you sure you want to delete
                        <strong id="scheduleDeleteName"></strong>?
                    </p>
                    <span>This schedule rule will be permanently removed.</span>
                </div>
            </div>

            <div class="cs-delete-actions">
                <button type="button" class="modal-btn-ghost" onclick="closeScheduleDeleteModal()">
                    Cancel
                </button>

                <form id="scheduleDeleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="cs-delete-confirm">
                        <i class="fa-solid fa-trash"></i>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const scheduleRules = @json($schedules);
    const weeklyAppointments = @json($weeklyAppointments ?? []);

    const showClinicScheduleToast = (title, message = '', type = 'info') => {
        if (typeof window.showToast === 'function') {
            window.showToast({
                type,
                title,
                message,
            });
        }
    };

    const dateEl = document.getElementById('currentDate');
    if (dateEl) dateEl.textContent = new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

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

    function clearRuleErrors() {
        clearFieldError('ruleDaysError', null, 'ruleDaysGroup');
        clearFieldError('ruleStatusError', 'ruleStatus');
        clearFieldError('ruleOpenTimeError', 'ruleOpenTime');
        clearFieldError('ruleCloseTimeError', 'ruleCloseTime');
        clearFieldError('ruleBreakError', null, 'ruleBreakGroup');
        clearFieldError('ruleMaxSlotsError', 'ruleMaxSlots');
        clearFieldError('ruleNotesError', 'ruleNotes');
    }

    function clearBlockErrors() {
        clearFieldError('blockDateError', 'blockDate');
        clearFieldError('blockReasonError', 'blockReason');
        clearFieldError('blockNoteError', 'blockNote');
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
    const TIME_ROWS = [
        { h: 9, l: '9:00 AM' }, { h: 10, l: '10:00 AM' },
        { h: 11, l: '11:00 AM' }, { h: 12, l: '12:00 PM' },
        { h: 13, l: '1:00 PM' }, { h: 14, l: '2:00 PM' },
        { h: 15, l: '3:00 PM' }, { h: 16, l: '4:00 PM' }
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

    function getServiceColor(serviceType) {
        const s = (serviceType || '').toLowerCase();
        if (s.includes('oral check')) return { box: 'background:#dbeafe;border-left:3px solid #3b82f6;color:#1e3a8a;', badge: 'Check-up' };
        if (s.includes('cleaning')) return { box: 'background:#dcfce7;border-left:3px solid #22c55e;color:#166534;', badge: 'Cleaning' };
        if (s.includes('surgery')) return { box: 'background:#fef3c7;border-left:3px solid #f59e0b;color:#92400e;', badge: 'Surgery' };
        if (s.includes('restoration') || s.includes('prosthesis')) return { box: 'background:#f3e8ff;border-left:3px solid #a855f7;color:#6b21a8;', badge: 'Prosthesis' };
        return { box: 'background:#f3f4f6;border-left:3px solid #6b7280;color:#374151;', badge: 'Other' };
    }

    function buildWeekGrid() {
        const ws = weekStart(weekOffset);
        const days = Array.from({ length: 7 }, (_, i) => {
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

        TIME_ROWS.forEach(({ h, l }) => {
            html += `<div class="time-lbl">${l}</div>`;
            days.forEach((d, i) => {
                const isoDate = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                const abbr = d.toLocaleDateString('en-US', { weekday: 'short' }).replace('.', '');
                const state = i >= 5 ? 'wk-weekend' :
                    slotState(abbr, h) === 'break' ? 'wk-break' :
                        slotState(abbr, h) === 'closed' ? 'wk-closed' : '';

                let inner = '';
                if (state === 'wk-break') inner = '<span class="slot-label">BREAK</span>';
                else if (state === 'wk-closed' || state === 'wk-weekend') inner = '<span class="slot-label">CLOSED</span>';
                else {
                    const slotAppointments = getAppointmentsForSlot(isoDate, h);
                    if (slotAppointments.length > 0) {
                        inner = slotAppointments.map(appt => {
                            const service = appt.service_type === 'Others' ? (appt.other_services || 'Other Service') : appt.service_type;
                            const style = getServiceColor(service);
                            return `<button type="button" onclick='openAppointmentDetailModal(${JSON.stringify(appt)})'
                                    style="${style.box}margin:4px;border-radius:8px;padding:6px 7px;font-size:.62rem;line-height:1.25;font-weight:600;box-shadow:0 1px 3px rgba(0,0,0,.06);width:calc(100% - 8px);text-align:left;cursor:pointer;transition:transform .15s ease,box-shadow .15s ease;"
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

    document.getElementById('prevWeek').addEventListener('click', () => { weekOffset--; buildWeekGrid(); });
    document.getElementById('nextWeek').addEventListener('click', () => { weekOffset++; buildWeekGrid(); });
    document.getElementById('todayBtn').addEventListener('click', () => { weekOffset = 0; buildWeekGrid(); });

    let selectedBreak = '12:00-13:00';
    let editingId = null;

    function openRuleModal(mode = 'create', ruleId = null, rule = null) {
        editingId = null;

        const backdrop = document.getElementById('ruleModalBackdrop');
        const form = document.getElementById('ruleForm');
        const methodField = document.getElementById('ruleMethodField');
        const title = document.getElementById('ruleModalTitle');
        const status = document.getElementById('ruleStatus');
        const openTime = document.getElementById('ruleOpenTime');
        const closeTime = document.getElementById('ruleCloseTime');
        const maxSlots = document.getElementById('ruleMaxSlots');
        const notes = document.getElementById('ruleNotes');
        const timeFields = document.getElementById('ruleTimeFields');
        const defaultBreak = document.querySelector('.break-chip[data-val="12:00-13:00"]');

        if (!backdrop || !form || !methodField || !title || !status || !openTime || !closeTime || !maxSlots || !notes || !timeFields) {
            console.error('Rule modal elements not found.');
            return;
        }

        clearRuleErrors();

        title.textContent = 'Add Schedule Rule';
        form.action = '{{ route('admin.clinic_schedule.store') }}';
        methodField.innerHTML = '';

        document.querySelectorAll('#ruleModalBackdrop .day-toggle').forEach(d => d.classList.remove('active'));
        document.querySelectorAll('#ruleModalBackdrop .break-chip').forEach(c => c.classList.remove('selected'));

        if (defaultBreak) defaultBreak.classList.add('selected');

        selectedBreak = '12:00-13:00';
        status.value = 'open';
        openTime.value = '09:00';
        closeTime.value = '17:00';
        maxSlots.value = '5';
        notes.value = '';
        timeFields.style.display = '';

        const ruleNotesCount = document.getElementById('ruleNotesCount');
        if (ruleNotesCount) ruleNotesCount.textContent = '0/150';

        if (mode === 'edit' && rule) {
            editingId = ruleId;
            title.textContent = 'Edit Schedule Rule';
            form.action = `/admin/clinic-schedule/rules/${ruleId}`;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

            (rule.days || []).forEach(day => {
                const el = document.querySelector(`#ruleModalBackdrop .day-toggle[data-day="${day}"]`);
                if (el) el.classList.add('active');
            });

            status.value = rule.status || 'open';
            toggleStatusFields(rule.status || 'open');

            if (rule.open_time) openTime.value = String(rule.open_time).substring(0, 5);
            if (rule.close_time) closeTime.value = String(rule.close_time).substring(0, 5);

            maxSlots.value = rule.max_slots || 5;
            notes.value = rule.notes || '';
            if (ruleNotesCount) ruleNotesCount.textContent = `${notes.value.length}/150`;

            selectedBreak = rule.break_time || 'none';
            document.querySelectorAll('#ruleModalBackdrop .break-chip').forEach(c => {
                c.classList.toggle('selected', c.dataset.val === selectedBreak);
            });
        }

        if (typeof window.syncRuleNotesClear === 'function') window.syncRuleNotesClear();

        window.openModal('ruleModalBackdrop');
    }

    function closeRuleModal() {
        window.closeModal('ruleModalBackdrop');
    }

    function toggleDay(el) {
        el.classList.toggle('active');
        clearFieldError('ruleDaysError', null, 'ruleDaysGroup');
    }

    function toggleStatusFields(val) {
        document.getElementById('ruleTimeFields').style.display = val === 'closed' ? 'none' : '';
    }

    function selectBreak(el) {
        document.querySelectorAll('.break-chip').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        selectedBreak = el.dataset.val;
        clearFieldError('ruleBreakError', null, 'ruleBreakGroup');
    }

    function adjSlots(delta) {
        const inp = document.getElementById('ruleMaxSlots');
        inp.value = Math.max(1, Math.min(30, parseInt(inp.value || 5) + delta));
    }

    function submitRule() {
        clearRuleErrors();

        const activeDays = [...document.querySelectorAll('.day-toggle.active')].map(d => d.dataset.day);
        const status = document.getElementById('ruleStatus').value;
        const openTime = document.getElementById('ruleOpenTime').value;
        const closeTime = document.getElementById('ruleCloseTime').value;
        const maxSlots = parseInt(document.getElementById('ruleMaxSlots').value || '0', 10);

        let hasError = false;

        if (!activeDays.length) {
            setFieldError('ruleDaysError', 'Please select at least one day.', null, 'ruleDaysGroup');
            hasError = true;
        }
        if (!status) {
            setFieldError('ruleStatusError', 'Please select a clinic status.', 'ruleStatus');
            hasError = true;
        }
        if (status !== 'closed') {
            if (!openTime) {
                setFieldError('ruleOpenTimeError', 'Please select an opening time.', 'ruleOpenTime');
                hasError = true;
            }
            if (!closeTime) {
                setFieldError('ruleCloseTimeError', 'Please select a closing time.', 'ruleCloseTime');
                hasError = true;
            }
            if (openTime && closeTime && openTime >= closeTime) {
                setFieldError('ruleCloseTimeError', 'Closing time must be later than opening time.', 'ruleCloseTime');
                hasError = true;
            }
            if (!maxSlots || maxSlots < 1) {
                setFieldError('ruleMaxSlotsError', 'Max appointments must be at least 1.', 'ruleMaxSlots');
                hasError = true;
            }
        }
        if (hasError) return;

        const form = document.getElementById('ruleForm');
        form.querySelectorAll('.injected-hidden').forEach(el => el.remove());

        const inject = (name, val) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = val; inp.className = 'injected-hidden';
            form.appendChild(inp);
        };

        activeDays.forEach(d => inject('days[]', d));
        inject('status', status);

        if (status !== 'closed') {
            inject('open_time', openTime);
            inject('close_time', closeTime);
            inject('max_slots', maxSlots);
            inject('break_time', selectedBreak || 'none');
        }
        inject('notes', document.getElementById('ruleNotes').value);

        form.submit();
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

        clearBlockErrors();

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

        window.openModal('blockModalBackdrop');
    }

    function validateBlockDateBeforeSubmit(event) {
        const blockDate = document.getElementById('blockDate');
        if (!blockDate) return true;

        const today = getLocalDateString();
        const selectedDate = blockDate.value;

        clearFieldError('blockDateError', 'blockDate');

        if (!selectedDate) {
            event.preventDefault();
            setFieldError('blockDateError', 'Please select a date.', 'blockDate');
            return false;
        }

        if (selectedDate < today) {
            event.preventDefault();

            blockDate.value = '';
            blockDate._flatpickr?.clear();

            setFieldError('blockDateError', 'Previous dates are not allowed.', 'blockDate');
            return false;
        }

        return true;
    }

    function closeBlockModal() {
        window.closeModal('blockModalBackdrop');
    }

    function openScheduleDeleteModal(actionUrl, ruleName = 'this schedule rule') {
        const form = document.getElementById('scheduleDeleteForm');
        const name = document.getElementById('scheduleDeleteName');

        if (!form || !name) {
            console.error('Delete modal elements not found.');
            return;
        }

        form.action = actionUrl;
        name.textContent = ruleName || 'this schedule rule';

        window.openModal('scheduleDeleteModal');
    }

    function closeScheduleDeleteModal() {
        window.closeModal('scheduleDeleteModal');
    }

    document.addEventListener('DOMContentLoaded', function () {

        const ruleNotes = document.getElementById('ruleNotes');
        const ruleNotesCount = document.getElementById('ruleNotesCount');
        const ruleNotesClearBtn = document.getElementById('ruleNotesClearBtn');

        function syncRuleNotesClear() {
            if (!ruleNotes || !ruleNotesClearBtn) return;
            ruleNotesClearBtn.classList.toggle('hidden', !(ruleNotes.value || '').trim().length);
        }
        window.syncRuleNotesClear = syncRuleNotesClear;

        function updateRuleNotesCount() {
            if (!ruleNotes || !ruleNotesCount) return;
            const len = ruleNotes.value.length;
            ruleNotesCount.textContent = `${len}/150`;
            ruleNotesCount.classList.remove('is-warning', 'is-danger');
            if (len >= 140) ruleNotesCount.classList.add('is-danger');
            else if (len >= 110) ruleNotesCount.classList.add('is-warning');
        }

        ruleNotes?.addEventListener('input', function () {
            if (this.value.length > 150) this.value = this.value.slice(0, 150);
            updateRuleNotesCount();
            syncRuleNotesClear();
            clearFieldError('ruleNotesError', 'ruleNotes');
        });

        ruleNotesClearBtn?.addEventListener('click', function () {
            if (!ruleNotes) return;
            ruleNotes.value = '';
            ruleNotes.dispatchEvent(new Event('input', { bubbles: true }));
            ruleNotes.dispatchEvent(new Event('change', { bubbles: true }));
            syncRuleNotesClear();
            ruleNotes.focus();
        });

        updateRuleNotesCount();
        syncRuleNotesClear();

        (function initRuleNotesVoice() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const toggleWrapper = document.getElementById('ruleNotesVoiceToggle');
            const input = document.getElementById('ruleNotes');
            if (!SpeechRecognition || !toggleWrapper || !input) return;

            const micBtn = document.createElement('button');
            micBtn.type = 'button';
            micBtn.className = 'voice-search-mic external';
            micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
            micBtn.title = 'Toggle voice input';
            toggleWrapper.appendChild(micBtn);

            const status = document.createElement('span');
            status.className = 'voice-status hidden';
            status.setAttribute('aria-hidden', 'true');
            status.setAttribute('aria-live', 'polite');
            toggleWrapper.appendChild(status);

            let recognition = null, listening = false, manualStop = false, capturedText = false;

            const setStatus = (text, state) => {
                status.textContent = text || '';
                status.className = state ? `voice-status is-${state}` : 'voice-status';
                if (!text) status.classList.add('hidden');
                else status.classList.remove('hidden');
            };

            const setMicState = (active) => {
                micBtn.classList.toggle('mic-active', !!active);
                micBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
                micBtn.innerHTML = active
                    ? '<i class="fa-solid fa-stop"></i>'
                    : '<i class="fa-solid fa-microphone"></i>';
            };

            const stopNow = () => {
                manualStop = true; listening = false; setMicState(false);
                if (capturedText) {
                    setStatus('Voice captured.', 'success');
                    setTimeout(() => setStatus('', null), 1200);
                } else {
                    setStatus("Didn't catch that. Try again.", 'error');
                    setTimeout(() => setStatus('', null), 2500);
                }
                if (recognition) { try { recognition.abort(); } catch (e) { try { recognition.stop(); } catch (_) { } } }
            };

            const createRecognition = () => {
                capturedText = false;
                const r = new SpeechRecognition();
                r.lang = 'en-US'; r.continuous = false; r.interimResults = true; r.maxAlternatives = 1;

                let sawSpeech = false, timeoutId = null;
                const clear_ = () => { if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; } };

                r.onstart = () => {
                    timeoutId = setTimeout(() => {
                        if (listening && !sawSpeech) { try { r.stop(); } catch (e) { } }
                    }, 6000);
                };

                r.onspeechend = () => { clear_(); try { r.stop(); } catch (e) { } };

                r.onresult = (event) => {
                    let transcript = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const res = event.results[i];
                        const chunk = (res?.[0]?.transcript ?? '').trim();
                        if (!chunk) continue;
                        sawSpeech = true;
                        if (res.isFinal) transcript = (transcript + ' ' + chunk).trim();
                        else if (!transcript) transcript = chunk;
                    }
                    transcript = transcript.trim();
                    if (transcript) {
                        clear_();
                        capturedText = true;
                        input.value = transcript;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        setStatus('Listening...', 'listening');
                    }
                };

                r.onerror = () => {
                    clear_(); listening = false;
                    if (manualStop) { manualStop = false; return; }
                    setMicState(false);
                    setStatus("Didn't catch that. Try again.", 'error');
                    setTimeout(() => setStatus('', null), 2500);
                };

                r.onend = () => {
                    clear_();
                    if (manualStop) { manualStop = false; listening = false; setMicState(false); return; }
                    const had = sawSpeech || capturedText;
                    listening = false; setMicState(false);
                    if (had) {
                        setStatus('Voice captured.', 'success');
                        setTimeout(() => setStatus('', null), 2200);
                    } else {
                        setStatus("Didn't catch that. Try again.", 'error');
                        setTimeout(() => setStatus('', null), 2500);
                    }
                };

                return r;
            };

            micBtn.addEventListener('click', () => {
                if (listening && recognition) { stopNow(); return; }
                recognition = createRecognition();
                try { recognition.start(); } catch (e) {
                    setStatus('Unable to start voice input.', 'error');
                    setTimeout(() => setStatus('', null), 2500);
                    setMicState(false); listening = false; return;
                }
                listening = true; setMicState(true); setStatus('Listening...', 'listening');
            });

            micBtn.addEventListener('pointerdown', (ev) => {
                if (listening && recognition) {
                    ev.preventDefault(); ev.stopPropagation();
                    manualStop = true;
                    try { recognition.stop(); } catch (e) { }
                }
            }, { passive: false });
        })();

        buildWeekGrid();

        document.getElementById('ruleStatus')?.addEventListener('change', () => clearFieldError('ruleStatusError', 'ruleStatus'));
        document.getElementById('ruleOpenTime')?.addEventListener('change', () => clearFieldError('ruleOpenTimeError', 'ruleOpenTime'));
        document.getElementById('ruleCloseTime')?.addEventListener('change', () => clearFieldError('ruleCloseTimeError', 'ruleCloseTime'));
        document.getElementById('ruleMaxSlots')?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 2);

            if (this.value !== '') {
                const value = Math.max(1, Math.min(30, parseInt(this.value, 10)));
                this.value = String(value);
            }

            clearFieldError('ruleMaxSlotsError', 'ruleMaxSlots');
        });

        document.getElementById('blockDate')?.addEventListener('input', () => clearFieldError('blockDateError', 'blockDate'));
        document.getElementById('blockReason')?.addEventListener('change', () => clearFieldError('blockReasonError', 'blockReason'));
        document.getElementById('blockNote')?.addEventListener('input', () => clearFieldError('blockNoteError', 'blockNote'));

        function getPreferredScheduleRulesView() {
            if (window.innerWidth <= 767) return 'list';
            return localStorage.getItem('scheduleRulesView') || 'list';
        }

        function applyScheduleRulesView(view, save = true) {
            const listView = document.getElementById('scheduleRulesListView');
            const gridView = document.getElementById('scheduleRulesGridView');
            const listBtn = document.getElementById('scheduleRulesListViewBtn');
            const gridBtn = document.getElementById('scheduleRulesGridViewBtn');
            if (!listView || !gridView) return;

            const finalView = window.innerWidth <= 767 ? 'list' : view;

            if (finalView === 'grid') {
                listView.setAttribute('hidden', 'hidden');
                gridView.removeAttribute('hidden');
            } else {
                gridView.setAttribute('hidden', 'hidden');
                listView.removeAttribute('hidden');
            }

            const root = document.getElementById('mainContent');

            root?.classList.toggle('mode-list', finalView === 'list');
            root?.classList.toggle('mode-grid', finalView === 'grid');

            if (listBtn) {
                listBtn.classList.toggle('active', finalView === 'list');
                listBtn.setAttribute('aria-pressed', finalView === 'list' ? 'true' : 'false');
            }

            if (gridBtn) {
                gridBtn.classList.toggle('active', finalView === 'grid');
                gridBtn.setAttribute('aria-pressed', finalView === 'grid' ? 'true' : 'false');
            }

            if (save && window.innerWidth > 767) localStorage.setItem('scheduleRulesView', finalView);
        }

        const listViewBtn = document.getElementById('scheduleRulesListViewBtn');
        const gridViewBtn = document.getElementById('scheduleRulesGridViewBtn');

        listViewBtn?.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); applyScheduleRulesView('list', true); });
        gridViewBtn?.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); applyScheduleRulesView('grid', true); });

        applyScheduleRulesView(getPreferredScheduleRulesView(), false);
        window.addEventListener('resize', () => applyScheduleRulesView(getPreferredScheduleRulesView(), false));
    });
</script>
@endsection