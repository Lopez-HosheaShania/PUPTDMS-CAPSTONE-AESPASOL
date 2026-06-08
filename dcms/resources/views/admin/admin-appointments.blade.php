@extends('layouts.admin')

@section('title', 'Appointments | PUP Taguig Dental Clinic')

@section('usesAppointmentCalendar', true)

@section('content')

@php
use Carbon\Carbon;
use Illuminate\Support\Str;

$upcomingAppointments = collect($upcomingAppointments ?? []);
$pastAppointments = collect($pastAppointments ?? []);
$today = $today ?? \Carbon\Carbon::today()->toDateString();
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
? (($firstTodayAppt->service_type ?? '') === 'Others'
? ($firstTodayAppt->other_services ?:
'Others')
: $firstTodayAppt->service_type ?? 'Appointment')
: null;

$nextAppt = $upcomingAppointments
->sortBy(fn($a) => ($a->appointment_date ?? '') . ' ' . ($a->appointment_time ?? '23:59:59'))
->first();

$nextName = $nextAppt ? optional($nextAppt->patient)->name ?? 'Unknown Patient' : null;

$nextTime =
$nextAppt && $nextAppt->appointment_time
? \Carbon\Carbon::parse($nextAppt->appointment_time)->format('g:i A')
: null;

$nextDate = $nextAppt ? \Carbon\Carbon::parse($nextAppt->appointment_date)->format('M j, Y') : null;

$nextService = $nextAppt
? (($nextAppt->service_type ?? '') === 'Others'
? ($nextAppt->other_services ?:
'Others')
: $nextAppt->service_type ?? 'Appointment')
: null;

$nextIsToday = $nextAppt && ($nextAppt->appointment_date ?? null) === $today;
$upcomingGrouped = $upcomingAppointments->groupBy(
fn($a) => \Carbon\Carbon::parse($a->appointment_date)->format('F'),
);
$pastGrouped = $pastAppointments->groupBy(fn($a) => \Carbon\Carbon::parse($a->appointment_date)->format('F'));
$upcomingTotal = $upcomingAppointments->count();
$pastTotal = $pastAppointments->count();
$allAppointments = $upcomingAppointments->merge($pastAppointments);

$statusCounts = [
'all' => $allAppointments->count(),
'upcoming' => $allAppointments->filter(fn($a) => strtolower($a->status ?? 'upcoming') === 'upcoming')->count(),
'rescheduled' => $allAppointments->filter(fn($a) => strtolower($a->status ?? '') === 'rescheduled')->count(),
'completed' => $allAppointments->filter(fn($a) => strtolower($a->status ?? '') === 'completed')->count(),
'cancelled' => $allAppointments->filter(fn($a) => in_array(strtolower($a->status ?? ''), ['cancelled',
'canceled']))->count(),
];

$statusOptions = [
'all' => ['label' => 'All statuses', 'icon' => 'fa-layer-group', 'tone' => 'all'],
'upcoming' => ['label' => 'Upcoming', 'icon' => 'fa-calendar-check', 'tone' => 'upcoming'],
'rescheduled' => ['label' => 'Rescheduled', 'icon' => 'fa-rotate-right', 'tone' => 'rescheduled'],
'completed' => ['label' => 'Completed', 'icon' => 'fa-circle-check', 'tone' => 'completed'],
'cancelled' => ['label' => 'Cancelled', 'icon' => 'fa-circle-xmark', 'tone' => 'cancelled'],
];
$notifications = collect($notifications ?? []);
$notifCount = $notifications->count();
@endphp

<main id="mainContent" class="admin-page-shell admin-appointments-page page-enter mode-list">
  <div class="w-full">

    <div class="appointment-header-wrap mb-8">
      <div class="page-banner">
        <div class="page-banner-inner">
          <div class="appointment-banner-title-wrap">
            <h1 class="page-title">Appointment Management</h1>
          </div>
        </div>
      </div>

      <div class="today-snapshot-card compact-snapshot-card">
        <div class="today-snapshot-header">
          <div>
            <span class="today-snapshot-kicker">Today’s Snapshot</span>
          </div>
        </div>

        <div class="snapshot-focus-grid">
          <div class="snapshot-focus-item {{ $firstTodayAppt ? 'has-appointment' : 'is-clear' }}">
            <div class="snapshot-focus-icon">
              <i class="fa-solid {{ $firstTodayAppt ? 'fa-calendar-check' : 'fa-mug-hot' }}"></i>
            </div>

            <div class="snapshot-focus-content">
              <span class="snapshot-focus-label">Today</span>

              @if ($firstTodayAppt)
              <h4>{{ $todayCount }} appointment{{ $todayCount > 1 ? 's' : '' }} today</h4>
              <p>
                First visit: <strong>{{ $firstTodayName }}</strong>
                @if ($firstTodayTime)
                at <strong>{{ $firstTodayTime }}</strong>
                @endif
              </p>
              <span class="snapshot-mini-chip">
                <i class="fa-solid fa-tooth"></i>
                {{ $firstTodayService }}
              </span>
              @else
              <h4>No appointments today</h4>
              @endif
            </div>
          </div>

          <div class="snapshot-focus-item next-appointment">
            <div class="snapshot-focus-icon">
              <i class="fa-solid fa-clock"></i>
            </div>

            <div class="snapshot-focus-content">
              <span class="snapshot-focus-label">Next Appointment</span>

              @if ($nextAppt)
              <h4>{{ $nextName }}</h4>
              <p>
                {{ $nextDate }}
                @if ($nextTime)
                at <strong>{{ $nextTime }}</strong>
                @endif
              </p>
              <div class="snapshot-chip-row">
                <span class="snapshot-mini-chip">
                  <i class="fa-solid fa-tooth"></i>
                  {{ $nextService }}
                </span>

                @if ($nextIsToday)
                <span class="snapshot-mini-chip today-chip">
                  Today
                </span>
                @endif
              </div>
              @else
              <h4>No upcoming appointments</h4>
              @endif
            </div>
          </div>
        </div>
      </div>

      <div class="appointment-controls-bar">
        <div class="appointment-control-copy">
          <span class="appointment-control-kicker">Manage view</span>
          <span class="appointment-control-text">Switch layout or review appointment history.</span>
        </div>

        <div class="appointment-filter-wrap">
          <div class="appointment-search-row voice-search-row">
            <div class="search-wrap global-search" data-search-wrapper>
              <i class="fa-solid fa-magnifying-glass search-icon"></i>

              <input id="apptSearchInput" type="text" placeholder="Search patient" class="search-input"
                data-search-input autocomplete="off">

              <button type="button" class="search-clear" data-search-clear aria-label="Clear search">
                <i class="fa-solid fa-xmark text-xs"></i>
              </button>
            </div>

            <div class="voice-input-toggle">
              <button type="button" class="voice-search-mic external" data-voice-trigger
                data-voice-target="#apptSearchInput" data-voice-status="#apptVoiceStatus"
                aria-label="Voice search appointments">
                <i class="fa-solid fa-microphone"></i>
              </button>

              <span id="apptVoiceStatus" class="voice-status hidden" data-voice-status aria-live="polite"></span>
            </div>
          </div>

          <input type="hidden" id="apptStatusFilter" value="all">

          <div class="appointment-status-dropdown" id="apptStatusDropdown">
            <button type="button" class="appointment-status-trigger" id="apptStatusToggle" aria-expanded="false">
              <span class="appointment-status-trigger-left">
                <span class="appointment-status-trigger-icon tone-all" id="apptStatusIcon">
                  <i class="fa-solid fa-layer-group"></i>
                </span>

                <span class="appointment-status-trigger-text">
                  <span class="appointment-status-trigger-label">Status</span>
                  <strong id="apptStatusSelectedLabel">All statuses</strong>
                </span>
              </span>

              <span class="appointment-status-trigger-right">
                <span class="appointment-status-count-badge" id="apptStatusSelectedCount">
                  {{ $statusCounts['all'] ?? 0 }}
                </span>
                <i class="fa-solid fa-chevron-down appointment-status-chevron"></i>
              </span>
            </button>

            <div class="appointment-status-panel" id="apptStatusPanel">
              <div class="appointment-status-grid">
                @foreach ($statusOptions as $value => $meta)
                <button type="button"
                  class="appointment-status-option {{ $value === 'all' ? 'is-active' : '' }} tone-{{ $meta['tone'] }}"
                  data-status-value="{{ $value }}" data-status-label="{{ $meta['label'] }}"
                  data-status-icon="{{ $meta['icon'] }}" data-status-tone="{{ $meta['tone'] }}"
                  data-status-count="{{ $statusCounts[$value] ?? 0 }}">
                  <span class="appointment-status-option-icon">
                    <i class="fa-solid {{ $meta['icon'] }}"></i>
                  </span>

                  <span class="appointment-status-option-label">{{ $meta['label'] }}</span>
                  <span class="appointment-status-option-count">{{ $statusCounts[$value] ?? 0 }}</span>
                </button>
                @endforeach
              </div>
            </div>
          </div>
        </div>

        <div class="appointment-controls-actions">
          <div class="appointment-filter-actions">
            <button id="appointmentFilterBtn" type="button" onclick="openAppointmentFilterPanel()"
              class="global-filter-btn">
              <i class="fa-solid fa-sliders"></i>
              <span>Filter</span>
              <span id="appointmentFilterBadge" class="filter-badge" style="display:none;"></span>
            </button>
          </div>

          <div class="view-toggle-container hidden md:flex">
            <div class="view-slider"></div>

            <button id="btnListView" onclick="switchView('list')" class="btn-view-mode active" title="List View">
              <i class="fa-solid fa-list text-sm"></i>
            </button>

            <button id="btnGridView" onclick="switchView('grid')" class="btn-view-mode" title="Grid View">
              <i class="fa-solid fa-grip"></i>
            </button>
          </div>

          <button id="appointmentClearFilterBtn" type="button" onclick="resetAppointmentFilters()"
            class="global-filter-reset-btn hidden" title="Reset filters">
            <i class="fa-solid fa-rotate-left"></i>
          </button>
        </div>
      </div>

      <section id="upcomingSection" class="pb-16">
        @forelse($upcomingGrouped as $month => $items)
        <div class="appt-month-group mb-10 sm:mb-14">
          <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-5">
            <div class="timeline-dot"></div>
            <h2 class="text-lg sm:text-xl font-bold text-[#8b0000]">{{ $month }}</h2>
            <span class="month-count-pill">
              {{ $items->count() }} {{ Str::plural('appointment', $items->count()) }}
            </span>
          </div>

          <div class="desktop-appointments-table relative pl-10">
            <div
              class="absolute left-[8px] top-0 bottom-0 w-[2px] bg-gradient-to-b from-[#8b0000]/30 to-[#8b0000]/05 rounded-full">
            </div>

            <div
              class="appt-table-head grid gap-4 text-[10px] font-bold uppercase tracking-[0.14em] text-gray-500 py-3 px-5 bg-[#FAFAFA] border border-gray-200 rounded-t-2xl mb-3"
              style="grid-template-columns: 140px 110px 170px minmax(180px,1.15fr) 90px 115px 170px;">
              <div class="flex items-center gap-1.5"><i class="fa-regular fa-calendar text-[10px]"></i>Date</div>
              <div class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-[10px]"></i>Time
              </div>
              <div class="appt-program-cell text-left">Service</div>
              <div class="appt-program-cell text-left">Patient</div>
              <div class="appt-program-cell text-left">Program</div>
              <div class="appt-program-cell text-left">Status</div>
              <div class="text-right">Actions</div>
            </div>

            <div class="space-y-2.5">
              @foreach ($items as $i => $appt)
              @php
              $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
              $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
              $dateLabel = \Carbon\Carbon::parse($appt->appointment_date)->format('F j, Y');
              $weekday = \Carbon\Carbon::parse($appt->appointment_date)->format('l');
              $timeLabel = $appt->appointment_time
              ? \Carbon\Carbon::parse($appt->appointment_time)->format('g:i A')
              : '—';
              $serviceLabel =
              ($appt->service_type ?? '') === 'Others'
              ? ($appt->other_services ?? '' ?:
              'Others')
              : $appt->service_type ?? '—';
              $isToday = ($appt->appointment_date ?? null) === $today;
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
              $modalDatetime =
              \Carbon\Carbon::parse($appt->appointment_date)->format('l, F j, Y') .
              ' • ' .
              $timeLabel;
              $statusRaw = strtolower((string) ($appt->status ?? 'completed'));
              $isCancelledPast = in_array($statusRaw, ['cancelled', 'canceled']);
              $cancelReason =
              $appt->cancellation_reason ??
              ($appt->cancel_reason ??
              ($appt->cancelled_reason ?? ($appt->reason ?? '')));
              $cancelReasonLabel = trim(
              str_ireplace('Patient no-show', 'No-show', (string) $cancelReason),
              );
              $pastStatusBase = $isCancelledPast ? 'Cancelled' : 'Completed';
              $pastStatusLabel = $isCancelledPast
              ? 'Cancelled' . ($cancelReasonLabel ? ' - ' . $cancelReasonLabel : '')
              : 'Completed';
              $pastStatusClass = $isCancelledPast ? 'status-cancelled' : 'status-completed';
              $recordDuration =
              $appt->duration ??
              ($appt->procedure_duration ?? ($appt->treatment_duration ?? ''));
              $recordRemarks =
              $appt->remarks ?? ($appt->treatment_notes ?? ($appt->notes ?? ''));
              $recordOral = $appt->oral_examination ?? ($appt->oral ?? '');
              $recordDiagnosis = $appt->diagnosis ?? '';
              $recordPrescription = $appt->prescription ?? '';
              @endphp

              <div class="appt-card {{ $isToday ? 'is-today' : '' }}" data-appt-id="{{ $appt->id }}"
                data-period="upcoming" data-date="{{ $appt->appointment_date }}"
                data-patient="{{ strtolower($patientName) }}" data-service="{{ strtolower($serviceLabel) }}"
                data-patient-id="{{ strtolower((string) ($appt->patient_id ?? '')) }}"
                data-status="{{ strtolower($appt->status ?? 'upcoming') }}" style="animation-delay:{{ $i * 0.04 }}s">

                <div class="rounded-[14px] grid gap-4 items-center px-5 py-3.5"
                  style="grid-template-columns: 140px 110px 170px minmax(180px,1.15fr) 90px 115px 170px;">

                  <div class="appt-row-date">
                    <p class="date-main">{{ $dateLabel }}</p>
                    <p class="date-sub">{{ $weekday }}</p>
                    @if ($isToday)
                    <span
                      class="inline-flex mt-1.5 text-[9px] font-bold uppercase tracking-wide bg-green-500 text-white px-2 py-0.5 rounded-md">
                      Today
                    </span>
                    @endif
                  </div>

                  <div><span class="time-chip"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel }}</span>
                  </div>

                  <div class="appt-service-cell flex items-center justify-start"><span
                      class="service-badge {{ $badgeClass }}">{{ $serviceLabel }}</span>
                  </div>

                  <div class="appt-patient-cell flex items-center justify-start gap-3">
                    <img
                      src="{{ optional($appt->patient)->profile_image ? asset('storage/' . $appt->patient->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($patientName) . '&background=8B0000&color=ffffff&bold=true' }}"
                      alt="{{ $patientName }}"
                      class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0">
                    <div class="text-left min-w-0">
                      <p class="appt-patient-name text-[13px] font-bold text-gray-800 leading-tight">
                        {{ $patientName }}</p>
                      <p class="text-[10px] text-gray-400 font-medium mt-0.5">ID
                        #{{ $appt->patient_id ?? 'N/A' }}</p>
                    </div>
                  </div>

                  <div class="appt-program-cell text-left">
                    @if ($program === '—')
                    <span class="text-[12px] text-gray-400">—</span>
                    @else
                    <span
                      class="appt-program-pill inline-block bg-gray-100 text-gray-500 text-[11px] font-medium px-2 py-1 rounded-full border border-gray-200 truncate">
                      {{ $program }}
                    </span>
                    @endif
                  </div>

                  @php
                  $appointmentStatus = strtolower($appt->status ?? 'upcoming');

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

                  $statusMeta = $statusMap[$appointmentStatus] ?? $statusMap['upcoming'];
                  @endphp

                  <div class="appt-status-cell text-left">
                    <span class="status-pill {{ $statusMeta['class'] }}">
                      <span class="status-dot"></span>{{ $statusMeta['label'] }}
                    </span>
                  </div>

                  <div class="appt-actions-wrap">
                    <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}?from=appointments"
                      class="action-btn action-btn-view" data-tooltip="View profile">
                      <i class="fa-regular fa-user"></i>
                    </a>

                    <button type="button" class="action-btn action-btn-start" data-tooltip="Start procedure"
                      onclick="openStartProcedureModal(this)" data-id="{{ $appt->id }}" data-name="{{ $patientName }}"
                      data-datetime="{{ $modalDatetime }}" {{ $isToday ? '' : 'disabled' }}>
                      <i class="fa-solid fa-play"></i>
                    </button>

                    <button type="button" class="action-btn action-btn-reschedule" data-tooltip="Reschedule" onclick="openRescheduleModal({
      id: '{{ $appt->id }}',
      name: @js($patientName),
      datetime: @js($modalDatetime),
      serviceType: @js($appt->service_type),
      updateUrl: '{{ route('admin.admin.appointments.reschedule', ['id' => $appt->id]) }}'
    })">
                      <i class="fa-solid fa-rotate-right"></i>
                    </button>

                    <button type="button" class="action-btn action-btn-cancel" data-tooltip="Cancel appointment"
                      onclick="cancelAppointmentFromModal('{{ url('/admin/appointments/' . $appt->id . '/cancel') }}', @js($patientName), @js($dateLabel . ' | ' . $timeLabel))">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </div>
                </div>
              </div>
              @endforeach
            </div>
          </div>

          <div class="mobile-appointments-list">
            @foreach ($items as $i => $appt)
            @php
            $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
            $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
            $dateLabel = \Carbon\Carbon::parse($appt->appointment_date)->format('M j, Y');
            $weekday = \Carbon\Carbon::parse($appt->appointment_date)->format('l');
            $timeLabel = $appt->appointment_time
            ? \Carbon\Carbon::parse($appt->appointment_time)->format('g:i A')
            : '—';
            $serviceLabel =
            ($appt->service_type ?? '') === 'Others'
            ? ($appt->other_services ?? '' ?:
            'Others')
            : $appt->service_type ?? '—';
            $isToday = ($appt->appointment_date ?? null) === $today;
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
            $modalDatetime =
            \Carbon\Carbon::parse($appt->appointment_date)->format('l, F j, Y') .
            ' • ' .
            $timeLabel;
            $statusRaw = strtolower((string) ($appt->status ?? 'completed'));
            $isCancelledPast = in_array($statusRaw, ['cancelled', 'canceled']);
            $cancelReason =
            $appt->cancellation_reason ??
            ($appt->cancel_reason ?? ($appt->cancelled_reason ?? ($appt->reason ?? '')));
            $cancelReasonLabel = trim(
            str_ireplace('Patient no-show', 'No-show', (string) $cancelReason),
            );
            $pastStatusBase = $isCancelledPast ? 'Cancelled' : 'Completed';
            $pastStatusLabel = $isCancelledPast
            ? 'Cancelled' . ($cancelReasonLabel ? ' - ' . $cancelReasonLabel : '')
            : 'Completed';
            $pastStatusClass = $isCancelledPast ? 'status-cancelled' : 'status-completed';
            $recordDuration =
            $appt->duration ??
            ($appt->procedure_duration ?? ($appt->treatment_duration ?? ''));
            $recordRemarks = $appt->remarks ?? ($appt->treatment_notes ?? ($appt->notes ?? ''));
            $recordOral = $appt->oral_examination ?? ($appt->oral ?? '');
            $recordDiagnosis = $appt->diagnosis ?? '';
            $recordPrescription = $appt->prescription ?? '';
            @endphp

            <div class="mobile-appt-card {{ $isToday ? 'is-today' : '' }}" data-appt-id="{{ $appt->id }}"
              data-period="upcoming" data-date="{{ $appt->appointment_date }}"
              data-patient="{{ strtolower($patientName) }}" data-service="{{ strtolower($serviceLabel) }}"
              data-patient-id="{{ strtolower((string) ($appt->patient_id ?? '')) }}"
              data-status="{{ strtolower($appt->status ?? 'upcoming') }}" style="animation-delay:{{ $i * 0.04 }}s">

              <div class="flex items-start justify-between gap-2 mb-4 pl-1">
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap mb-1">
                    <p class="mobile-patient-name text-[15px] font-extrabold text-gray-800 leading-snug">
                      {{ $patientName }}</p>
                    @if ($isToday)
                    <span
                      class="text-[9px] font-bold uppercase tracking-wide bg-blue-600 text-white px-2 py-0.5 rounded-md">Today</span>
                    @endif
                  </div>
                  <p class="text-[11px] font-medium text-gray-500">{{ $weekday }},
                    {{ $dateLabel }}</p>
                </div>
                @php
                $appointmentStatus = strtolower($appt->status ?? 'upcoming');

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

                $statusMeta = $statusMap[$appointmentStatus] ?? $statusMap['upcoming'];
                @endphp

                <span class="status-pill {{ $statusMeta['class'] }} flex-shrink-0">
                  <span class="status-dot"></span>{{ $statusMeta['label'] }}
                </span>
              </div>

              <div class="bg-gray-50 rounded-xl p-3 mb-4 grid grid-cols-2 gap-3 border border-gray-100 ml-1">
                <div>
                  <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">
                    Schedule Time</p>
                  <span class="time-chip text-[11px] bg-white w-full justify-center shadow-sm py-1.5 border-gray-200">
                    <i class="fa-regular fa-clock text-[#8B0000]"></i> {{ $timeLabel }}
                  </span>
                </div>
                <div>
                  <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">
                    Service Type</p>
                  <span
                    class="service-badge {{ $badgeClass }} text-[11px] w-full justify-center py-1.5 truncate shadow-sm border border-gray-100/50">
                    {{ $serviceLabel }}
                  </span>
                </div>
              </div>

              <div class="mobile-appt-actions grid grid-cols-2 gap-3">
                <button type="button" class="action-btn action-btn-start" onclick="openStartProcedureModal(this)"
                  data-id="{{ $appt->id }}" data-name="{{ $patientName }}" data-datetime="{{ $modalDatetime }}" {{
                  $isToday ? '' : 'disabled' }}>
                  <i class="fa-solid fa-play text-[10px]"></i> Start
                </button>

                <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}?from=appointments"
                  class="action-btn action-btn-view">
                  <i class="fa-regular fa-user text-[10px]"></i> View
                </a>

                <button type="button" class="action-btn action-btn-reschedule" onclick="openRescheduleModal({
                    id: '{{ $appt->id }}',
                    name: @js($patientName),
                    datetime: @js($modalDatetime),
                    serviceType: @js($appt->service_type),
                    updateUrl: '{{ route('admin.admin.appointments.reschedule', ['id' => $appt->id]) }}'
                })" data-id="{{ $appt->id }}" data-name="{{ $patientName }}" data-datetime="{{ $modalDatetime }}">
                  <i class="fa-solid fa-rotate-right text-[10px]"></i> Reschedule
                </button>

                <button type="button" class="action-btn action-btn-cancel"
                  onclick="cancelAppointmentFromModal('{{ url('/admin/appointments/' . $appt->id . '/cancel') }}', @js($patientName), @js($dateLabel . ' | ' . $timeLabel))">
                  <i class="fa-solid fa-xmark text-[10px]"></i> Cancel
                </button>
              </div>

            </div>
            @endforeach
          </div>

        </div>
        @empty
        <div id="appointmentStaticEmptyUpcoming" class="empty-state">
          <div class="empty-state-icon appointment-empty-icon">
            <i class="fa-regular fa-calendar-xmark"></i>
          </div>

          <p class="empty-state-title">No upcoming appointments</p>
          <p class="empty-state-sub">New appointments will appear here once scheduled.</p>
        </div>
        @endforelse

        <div id="appointmentStatusEmptyUpcoming" class="empty-state empty-state-controlled">
          <div class="empty-state-icon appointment-empty-icon">
            <i id="appointmentStatusEmptyUpcomingIcon" class="fa-regular fa-calendar-xmark"></i>
          </div>

          <p id="appointmentStatusEmptyUpcomingTitle" class="empty-state-title">
            No upcoming appointments
          </p>

          <p id="appointmentStatusEmptyUpcomingSub" class="empty-state-sub">
            New appointments will appear here once scheduled.
          </p>

          <button type="button" onclick="resetAppointmentFilters()"
            class="empty-state-btn appointment-panel-empty-clear hidden">
            <i class="fa-solid fa-xmark"></i>
            Clear filter
          </button>
        </div>

        <div id="appointmentFilterEmptyUpcoming" class="empty-state empty-state-controlled">
          <div class="empty-state-icon appointment-empty-icon">
            <i class="fa-solid fa-magnifying-glass"></i>
          </div>

          <p id="appointmentFilterEmptyUpcomingTitle" class="empty-state-title">
            No results found
          </p>

          <p class="empty-state-sub">
            Try a different patient name, ID, or status.
          </p>

          <button type="button" onclick="clearAppointmentSearch()" class="empty-state-btn">
            <i class="fa-solid fa-xmark"></i>
            Clear search
          </button>
        </div>
      </section>

      <section id="pastSection" class="pb-16 hidden">
        @forelse($pastGrouped as $month => $items)
        <div class="appt-month-group mb-10 sm:mb-14">
          <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-5 pl-2">
            <div class="timeline-dot-past"></div>
            <h2 class="text-lg sm:text-xl font-bold text-gray-400">{{ $month }}</h2>
            <span class="bg-gray-100 text-gray-400 text-xs font-semibold px-3 py-1 rounded-full">
              {{ $items->count() }} {{ Str::plural('appointment', $items->count()) }}
            </span>
          </div>

          <div class="desktop-appointments-table relative pl-10">
            <div class="absolute left-[8px] top-0 bottom-0 w-[2px] bg-gray-200 rounded-full"></div>

            <div
              class="appt-table-head grid gap-4 text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400 py-3 px-5 bg-[#FAFAFA] border border-gray-200 rounded-t-2xl mb-3"
              style="grid-template-columns: 130px 100px 160px minmax(160px,1fr) 100px 150px 100px;">
              <div class="flex items-center gap-1.5"><i class="fa-regular fa-calendar text-[10px]"></i>Date</div>
              <div class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-[10px]"></i>Time
              </div>
              <div class="appt-program-cell text-left">Service</div>
              <div class="appt-program-cell text-left">Patient</div>
              <div class="appt-program-cell text-left">Program</div>
              <div class="appt-program-cell text-left">Status</div>
              <div class="text-right">Actions</div>
            </div>

            <div class="space-y-2.5">
              @foreach ($items as $i => $appt)
              @php
              $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
              $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
              $dateLabel = \Carbon\Carbon::parse($appt->appointment_date)->format('F j, Y');
              $weekday = \Carbon\Carbon::parse($appt->appointment_date)->format('l');
              $timeLabel = $appt->appointment_time
              ? \Carbon\Carbon::parse($appt->appointment_time)->format('g:i A')
              : '—';
              $serviceLabel =
              ($appt->service_type ?? '') === 'Others'
              ? ($appt->other_services ?? '' ?:
              'Others')
              : $appt->service_type ?? '—';
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
              $modalDatetime =
              \Carbon\Carbon::parse($appt->appointment_date)->format('l, F j, Y') .
              ' • ' .
              $timeLabel;
              $statusRaw = strtolower((string) ($appt->status ?? 'completed'));
              $isCancelledPast = in_array($statusRaw, ['cancelled', 'canceled']);
              $cancelReason =
              $appt->cancellation_reason ??
              ($appt->cancel_reason ??
              ($appt->cancelled_reason ?? ($appt->reason ?? '')));
              $cancelReasonLabel = trim(
              str_ireplace('Patient no-show', 'No-show', (string) $cancelReason),
              );
              $pastStatusBase = $isCancelledPast ? 'Cancelled' : 'Completed';
              $pastStatusLabel = $isCancelledPast
              ? 'Cancelled' . ($cancelReasonLabel ? ' - ' . $cancelReasonLabel : '')
              : 'Completed';
              $pastStatusClass = $isCancelledPast ? 'status-cancelled' : 'status-completed';
              $recordDuration =
              $appt->duration ??
              ($appt->procedure_duration ?? ($appt->treatment_duration ?? ''));
              $recordRemarks =
              $appt->remarks ?? ($appt->treatment_notes ?? ($appt->notes ?? ''));
              $recordOral = $appt->oral_examination ?? ($appt->oral ?? '');
              $recordDiagnosis = $appt->diagnosis ?? '';
              $recordPrescription = $appt->prescription ?? '';
              @endphp

              <div class="appt-card opacity-70" data-appt-id="{{ $appt->id }}" data-period="past"
                data-date="{{ $appt->appointment_date }}" data-patient="{{ strtolower($patientName) }}"
                data-service="{{ strtolower($serviceLabel) }}"
                data-patient-id="{{ strtolower((string) ($appt->patient_id ?? '')) }}"
                data-status="{{ $isCancelledPast ? 'cancelled' : 'completed' }}"
                style="animation-delay:{{ $i * 0.04 }}s">

                <div class="grid gap-4 items-center px-5 py-3.5"
                  style="grid-template-columns: 130px 100px 160px minmax(160px,1fr) 100px 150px 100px;">

                  <div>
                    <p class="text-[13px] font-semibold text-gray-500">{{ $dateLabel }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $weekday }}</p>
                  </div>

                  <div><span class="time-chip text-gray-400"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel
                      }}</span>
                  </div>

                  <div class="flex items-center justify-start"><span
                      class="service-badge {{ $badgeClass }} opacity-70">{{
                      $serviceLabel }}</span>
                  </div>

                  <div class="appt-patient-cell flex items-center justify-start gap-3">
                    <img
                      src="{{ optional($appt->patient)->profile_image ? asset('storage/' . $appt->patient->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($patientName) . '&background=9ca3af&color=ffffff&bold=true' }}"
                      alt="{{ $patientName }}"
                      class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0 opacity-80">
                    <div class="text-left min-w-0">
                      <p class="past-patient-name text-[13px] font-bold text-gray-500 leading-tight"
                        title="{{ $patientName }}">
                        {{ $patientName }}</p>
                      <p class="text-[10px] text-gray-400 font-medium mt-0.5">ID
                        #{{ $appt->patient_id ?? 'N/A' }}</p>
                    </div>
                  </div>

                  <div class="appt-program-cell text-left">
                    @if ($program === '—')
                    <span class="text-[12px] text-gray-400">—</span>
                    @else
                    <span
                      class="inline-block bg-gray-100 text-gray-400 text-[11px] font-medium px-2.5 py-1 rounded-full border border-gray-200">{{
                      $program }}</span>
                    @endif
                  </div>

                  <div class="appt-status-cell text-left">
                    <span class="status-pill {{ $pastStatusClass }} past-status-pill" data-appt-id="{{ $appt->id }}"
                      data-status-base="{{ $pastStatusBase }}" data-cancel-reason="{{ $cancelReasonLabel }}"><span
                        class="status-dot"></span><span class="past-status-text">{{ $pastStatusLabel }}</span></span>
                  </div>

                  <div class="appt-actions-wrap">
                    <button type="button" class="action-btn action-btn-record" data-tooltip="View details"
                      onclick="openRecordModal(this)" data-appt-id="{{ $appt->id }}" data-service="{{ $serviceLabel }}"
                      data-date="{{ $dateLabel }}" data-time="{{ $timeLabel }}" data-status="{{ $pastStatusLabel }}"
                      data-duration="{{ $recordDuration }}" data-remarks="{{ $recordRemarks }}"
                      data-oral="{{ $recordOral }}" data-diagnosis="{{ $recordDiagnosis }}"
                      data-prescription="{{ $recordPrescription }}">
                      <i class="fa-regular fa-eye"></i>
                    </button>

                    <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}?from=appointments"
                      class="action-btn action-btn-view" data-tooltip="View profile">
                      <i class="fa-regular fa-user"></i>
                    </a>
                  </div>

                </div>
              </div>
              @endforeach
            </div>
          </div>

          <div class="mobile-appointments-list">
            @foreach ($items as $i => $appt)
            @php
            $patientName = optional($appt->patient)->name ?? 'Unknown Patient';
            $program = optional($appt->patient)->program ?? optional($appt->patient)->course ?? '—';
            $dateLabel = \Carbon\Carbon::parse($appt->appointment_date)->format('M j, Y');
            $weekday = \Carbon\Carbon::parse($appt->appointment_date)->format('l');
            $timeLabel = $appt->appointment_time
            ? \Carbon\Carbon::parse($appt->appointment_time)->format('g:i A')
            : '—';
            $serviceLabel =
            ($appt->service_type ?? '') === 'Others'
            ? ($appt->other_services ?? '' ?:
            'Others')
            : $appt->service_type ?? '—';
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
            $modalDatetime =
            \Carbon\Carbon::parse($appt->appointment_date)->format('l, F j, Y') .
            ' • ' .
            $timeLabel;
            $statusRaw = strtolower((string) ($appt->status ?? 'completed'));
            $isCancelledPast = in_array($statusRaw, ['cancelled', 'canceled']);
            $cancelReason =
            $appt->cancellation_reason ??
            ($appt->cancel_reason ?? ($appt->cancelled_reason ?? ($appt->reason ?? '')));
            $cancelReasonLabel = trim(
            str_ireplace('Patient no-show', 'No-show', (string) $cancelReason),
            );
            $pastStatusBase = $isCancelledPast ? 'Cancelled' : 'Completed';
            $pastStatusLabel = $isCancelledPast
            ? 'Cancelled' . ($cancelReasonLabel ? ' - ' . $cancelReasonLabel : '')
            : 'Completed';
            $pastStatusClass = $isCancelledPast ? 'status-cancelled' : 'status-completed';
            $recordDuration =
            $appt->duration ??
            ($appt->procedure_duration ?? ($appt->treatment_duration ?? ''));
            $recordRemarks = $appt->remarks ?? ($appt->treatment_notes ?? ($appt->notes ?? ''));
            $recordOral = $appt->oral_examination ?? ($appt->oral ?? '');
            $recordDiagnosis = $appt->diagnosis ?? '';
            $recordPrescription = $appt->prescription ?? '';
            @endphp

            <div class="mobile-appt-card opacity-75 border-gray-200" data-appt-id="{{ $appt->id }}" data-period="past"
              data-date="{{ $appt->appointment_date }}" data-patient="{{ strtolower($patientName) }}"
              data-service="{{ strtolower($serviceLabel) }}"
              data-patient-id="{{ strtolower((string) ($appt->patient_id ?? '')) }}"
              data-status="{{ $isCancelledPast ? 'cancelled' : 'completed' }}" style="animation-delay:{{ $i * 0.04 }}s">
              <div class="pl-1">
                <div class="flex items-start justify-between gap-2 mb-3">
                  <div class="min-w-0">
                    <p class="past-grid-name text-[14px] font-extrabold text-gray-500" title="{{ $patientName }}">{{
                      $patientName }}</p>
                    <p class="text-[11px] font-medium text-gray-400 mt-0.5">
                      {{ $weekday }},
                      {{ $dateLabel }}</p>
                  </div>
                  <span class="status-pill {{ $pastStatusClass }} past-status-pill flex-shrink-0"
                    data-appt-id="{{ $appt->id }}" data-status-base="{{ $pastStatusBase }}"
                    data-cancel-reason="{{ $cancelReasonLabel }}"><span class="status-dot"></span><span
                      class="past-status-text">{{ $pastStatusLabel }}</span></span>
                </div>

                <div class="bg-gray-50 rounded-xl p-2.5 grid grid-cols-2 gap-2 border border-gray-100 mb-3">
                  <span
                    class="time-chip text-[11px] text-gray-400 bg-white w-full justify-center shadow-sm py-1.5 border-gray-100">
                    <i class="fa-regular fa-clock"></i> {{ $timeLabel }}
                  </span>
                  <span
                    class="service-badge {{ $badgeClass }} opacity-70 text-[11px] w-full justify-center py-1.5 truncate border border-gray-100/50">
                    {{ $serviceLabel }}
                  </span>
                </div>

                <div class="mobile-appt-actions grid grid-cols-2 gap-3">
                  <button type="button" class="action-btn action-btn-record" onclick="openRecordModal(this)"
                    data-appt-id="{{ $appt->id }}" data-service="{{ $serviceLabel }}" data-date="{{ $dateLabel }}"
                    data-time="{{ $timeLabel }}" data-status="{{ $pastStatusLabel }}"
                    data-duration="{{ $recordDuration }}" data-remarks="{{ $recordRemarks }}"
                    data-oral="{{ $recordOral }}" data-diagnosis="{{ $recordDiagnosis }}"
                    data-prescription="{{ $recordPrescription }}">
                    <i class="fa-regular fa-eye text-[10px]"></i> Details
                  </button>

                  <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}?from=appointments"
                    class="action-btn action-btn-view">
                    <i class="fa-regular fa-user text-[10px]"></i> Profile
                  </a>
                </div>
              </div>
            </div>
            @endforeach
          </div>

        </div>
        @empty
        <div id="appointmentStaticEmptyPast" class="empty-state">
          <div class="empty-state-icon appointment-empty-icon">
            <i class="fa-regular fa-calendar-xmark"></i>
          </div>

          <p class="empty-state-title">No past appointments</p>
          <p class="empty-state-sub">Completed appointments will appear here.</p>
        </div>
        @endforelse

        <div id="appointmentStatusEmptyPast" class="empty-state empty-state-controlled">
          <div class="empty-state-icon appointment-empty-icon">
            <i id="appointmentStatusEmptyPastIcon" class="fa-regular fa-calendar-xmark"></i>
          </div>

          <p id="appointmentStatusEmptyPastTitle" class="empty-state-title">
            No past appointments
          </p>

          <p id="appointmentStatusEmptyPastSub" class="empty-state-sub">
            Completed appointments will appear here.
          </p>

          <button type="button" onclick="resetAppointmentFilters()"
            class="empty-state-btn appointment-panel-empty-clear hidden">
            <i class="fa-solid fa-xmark"></i>
            Clear filter
          </button>
        </div>

        <div id="appointmentFilterEmptyPast" class="empty-state empty-state-controlled">
          <div class="empty-state-icon appointment-empty-icon">
            <i class="fa-solid fa-magnifying-glass"></i>
          </div>

          <p id="appointmentFilterEmptyPastTitle" class="empty-state-title">
            No results found
          </p>

          <p class="empty-state-sub">
            Try a different patient name, ID, or status.
          </p>

          <button type="button" onclick="clearAppointmentSearch()" class="empty-state-btn">
            <i class="fa-solid fa-xmark"></i>
            Clear search
          </button>
        </div>
      </section>

      <div class="pb-16"></div>
    </div>
</main>

<div id="filterModal" class="filter-drawer-wrapper" aria-hidden="true">
  <div class="filter-drawer-overlay" onclick="document.getElementById('closeFilterModalBtn').click()"></div>

  <div class="filter-drawer-panel flex flex-col bg-white">
    <div class="px-6 py-5 flex items-center justify-between flex-shrink-0 bg-white border-b border-gray-100">
      <div class="filter-drawer-title flex items-center gap-2">
        <i class="fa-solid fa-sliders text-xl"></i>
        <h2 class="text-xl font-extrabold">Filters</h2>
      </div>

      <button id="closeFilterModalBtn" type="button" class="text-gray-400 hover:text-gray-700 transition-colors">
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>
    </div>

    <div class="px-6 py-5 flex flex-col gap-6 flex-1 overflow-y-auto bg-white">
      <div id="activeFiltersSection" class="hidden">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[13px] font-bold text-gray-800">Active Filters</span>
          <button id="clearAllChipsBtn" type="button" class="text-xs font-bold text-[#8B0000] hover:underline">
            Clear All
          </button>
        </div>
        <div id="activeChipsContainer" class="flex flex-wrap gap-2 pb-4 border-b border-gray-100"></div>
      </div>

      <div>
        <h3 class="filter-section-title">Sort By</h3>
        <div class="filter-chip-row" id="apptSortGroup">
          <button type="button" class="ftag ftag-active" data-sort="newest">Newest First</button>
          <button type="button" class="ftag" data-sort="oldest">Oldest First</button>
          <button type="button" class="ftag" data-sort="az">Patient Name A-Z</button>
          <button type="button" class="ftag" data-sort="za">Patient Name Z-A</button>
        </div>
      </div>

      <div>
        <h3 class="filter-section-title">Appointment View</h3>
        <div class="filter-chip-row" id="apptPeriodGroup">
          <label class="choice-chip">
            <input type="radio" name="appointment_period" value="upcoming" class="filter-input radio-red chip-radio"
              checked />
            <span>Upcoming</span>
          </label>
          <label class="choice-chip">
            <input type="radio" name="appointment_period" value="past" class="filter-input radio-red chip-radio" />
            <span>Past</span>
          </label>
          <label class="choice-chip">
            <input type="radio" name="appointment_period" value="all" class="filter-input radio-red chip-radio" />
            <span>All appointments</span>
          </label>
        </div>
      </div>

      <div>
        <h3 class="filter-section-title">Status</h3>
        <div class="filter-chip-row" id="apptStatusChipGroup">
          @foreach ($statusOptions as $value => $meta)
          <label class="choice-chip">
            <input type="radio" name="appointment_status" value="{{ $value }}" class="filter-input radio-red chip-radio"
              {{ $value==='all' ? 'checked' : '' }} />
            <span>{{ $meta['label'] }}</span>
          </label>
          @endforeach
        </div>
      </div>

      <div>
        <h3 class="filter-section-title">Filter by Date Range</h3>
        <div class="filter-chip-row" id="datePresetGroup">
          <button type="button" class="quick-date-chip" data-range="7">Last 7 Days</button>
          <button type="button" class="quick-date-chip" data-range="30">Last 30 Days</button>
          <button type="button" class="quick-date-chip" data-range="90">Last 3 Months</button>
          <button type="button" class="quick-date-chip" data-range="180">Last 6 Months</button>
          <button type="button" class="quick-date-chip" data-range="365">Last 12 Months</button>
        </div>
      </div>

      <div class="pb-6">
        <h3 class="filter-section-title">Custom Date Range</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="filter-date-input-wrap">
            <input id="fromDate" type="text" class="js-flatpickr-date-range-from" placeholder="Start date" readonly
              autocomplete="off" />
            <i class="fa-regular fa-calendar"></i>
          </div>

          <div class="filter-date-input-wrap">
            <input id="toDate" type="text" class="js-flatpickr-date-range-to" placeholder="End date" readonly
              autocomplete="off" />
            <i class="fa-regular fa-calendar"></i>
          </div>
        </div>
      </div>
    </div>

    <div
      class="px-6 py-5 bg-white flex flex-col sm:flex-row items-center justify-between flex-shrink-0 border-t border-gray-100 gap-4 sm:gap-0 relative z-20">
      <button id="clearFiltersModal" type="button"
        class="filter-clear-btn flex items-center gap-2 transition-colors w-full sm:w-auto justify-center sm:justify-start">
        <i class="fa-regular fa-trash-can text-lg"></i>
        <span class="text-[13px] font-bold leading-none whitespace-nowrap">Clear Filters</span>
      </button>

      <div class="flex items-center gap-3 w-full sm:w-auto">
        <button id="cancelFilterBtn" type="button"
          class="filter-cancel-btn flex-1 sm:flex-none px-5 py-2.5 text-sm font-bold rounded-lg transition-colors">
          Cancel
        </button>

        <button id="applyFilters" type="button"
          class="filter-show-results-btn filter-apply-btn flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold rounded-lg transition-colors shadow-sm">
          <i class="fa-solid fa-check"></i>
          <span id="showResultsText">Show 0 results</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div id="startProcedureModal"
  class="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center backdrop-blur-sm hidden z-[9999] p-0 sm:p-4">
  <div class="bg-white w-full sm:w-[560px] rounded-t-2xl sm:rounded-2xl overflow-hidden shadow-2xl">
    <div class="bg-green-700 px-6 sm:px-8 py-5 text-center">
      <h2 class="text-lg sm:text-xl font-bold text-white">Confirm Procedure Start</h2>
    </div>
    <div class="px-6 sm:px-10 py-6 sm:py-7 bg-gray-50">
      <p class="text-sm sm:text-base font-bold text-gray-900 mb-1 text-center">You are about to begin this
        appointment's procedure.</p>
      <p class="text-xs sm:text-sm text-gray-500 mb-5 text-center">This will mark the appointment as in progress.
      </p>
      <div class="bg-white border border-gray-200 rounded-2xl px-6 sm:px-8 py-4 sm:py-5 text-center mb-4 shadow-sm">
        <p class="text-sm text-gray-800">Patient: <span class="font-bold" id="startPatientName">—</span></p>
        <p class="text-sm text-gray-600 mt-1" id="startAppointmentDate">—</p>
      </div>
      <div class="flex justify-end gap-3">
        <button onclick="closeStartProcedureModal()"
          class="px-5 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-100 transition text-sm">Cancel</button>
        <button onclick="confirmStartProcedure()"
          class="px-5 py-2 rounded-lg bg-green-700 text-white font-semibold hover:bg-green-800 transition text-sm">Start
          Procedure</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  let selectedApptId = null;
  let apptSearchInput = null;
  let apptStatusFilter = null;

  let appointmentPeriodFilter = 'upcoming';
  let appointmentStatusFilter = 'all';
  let appointmentStatusFilterSource = 'dropdown';
  let appointmentSortFilter = 'newest';
  let appointmentFromDate = '';
  let appointmentToDate = '';

  const apptStatusMeta = {
    all: { label: 'All statuses', icon: 'fa-layer-group', tone: 'all' },
    upcoming: { label: 'Upcoming', icon: 'fa-calendar-check', tone: 'upcoming' },
    rescheduled: { label: 'Rescheduled', icon: 'fa-rotate-right', tone: 'rescheduled' },
    completed: { label: 'Completed', icon: 'fa-circle-check', tone: 'completed' },
    cancelled: { label: 'Cancelled', icon: 'fa-circle-xmark', tone: 'cancelled' }
  };

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

  function confirmStartProcedure() {
    if (!selectedApptId) return;
    window.location.href = `/admin/appointments/${selectedApptId}/start`;
  }

  function normalizeCancelReasonLabel(reason) {
    reason = String(reason || '').trim();
    if (!reason) return '';
    if (reason.toLowerCase() === 'patient no-show') return 'No-show';
    return reason;
  }

  function getStoredCancelReason(apptId) {
    if (!apptId) return '';
    return sessionStorage.getItem(`appointmentCancelReason:${apptId}`) || '';
  }

  function hydratePastCancellationReasons() {
    document.querySelectorAll('.past-status-pill[data-status-base="Cancelled"]').forEach((pill) => {
      const apptId = pill.dataset.apptId || '';
      const reason = normalizeCancelReasonLabel(pill.dataset.cancelReason || getStoredCancelReason(apptId));
      const label = reason ? `Cancelled - ${reason}` : 'Cancelled';
      const text = pill.querySelector('.past-status-text');

      if (text) text.textContent = label;
      pill.dataset.statusFull = label;

      document.querySelectorAll(`.action-btn-record[data-appt-id="${apptId}"]`).forEach((btn) => {
        btn.dataset.status = label;
      });
    });

    document.querySelectorAll('.past-status-pill[data-status-base="Completed"]').forEach((pill) => {
      const text = pill.querySelector('.past-status-text');
      if (text) text.textContent = 'Completed';
      pill.dataset.statusFull = 'Completed';
    });
  }

  function switchView(mode) {
    const mainContent = document.getElementById('mainContent');
    const btnList = document.getElementById('btnListView');
    const btnGrid = document.getElementById('btnGridView');
    const isMobile = window.matchMedia('(max-width: 767px)').matches;

    if (!mainContent) return;
    if (isMobile) mode = 'grid';

    if (mode === 'grid') {
      mainContent.classList.remove('mode-list');
      mainContent.classList.add('mode-grid');
      btnList?.classList.remove('active');
      btnGrid?.classList.add('active');
      if (!isMobile) localStorage.setItem('apptViewMode', 'grid');
    } else {
      mainContent.classList.remove('mode-grid');
      mainContent.classList.add('mode-list');
      btnGrid?.classList.remove('active');
      btnList?.classList.add('active');
      localStorage.setItem('apptViewMode', 'list');
    }
  }

  function syncResponsiveAppointmentView() {
    if (window.matchMedia('(max-width: 767px)').matches) {
      switchView('grid');
      return;
    }

    switchView(localStorage.getItem('apptViewMode') || 'list');
  }

  function setAppointmentStatusFilter(value = 'all', shouldApply = true, source = 'dropdown') {
    const nextValue = apptStatusMeta[value] ? value : 'all';
    const meta = apptStatusMeta[nextValue];

    appointmentStatusFilter = nextValue;
    appointmentStatusFilterSource = source === 'panel' ? 'panel' : 'dropdown';

    if (apptStatusFilter) {
      apptStatusFilter.value = nextValue;
    }

    const label = document.getElementById('apptStatusSelectedLabel');
    const count = document.getElementById('apptStatusSelectedCount');
    const icon = document.getElementById('apptStatusIcon');
    const activeOption = document.querySelector(`.appointment-status-option[data-status-value="${nextValue}"]`);

    if (label) label.textContent = meta.label;
    if (count) count.textContent = activeOption?.dataset.statusCount || '0';

    if (icon) {
      icon.className = `appointment-status-trigger-icon tone-${meta.tone}`;
      icon.innerHTML = `<i class="fa-solid ${meta.icon}"></i>`;
    }

    document.querySelectorAll('.appointment-status-option').forEach(option => {
      option.classList.toggle('is-active', option.dataset.statusValue === nextValue);
    });

    if (shouldApply) applyAppointmentFilters();
  }

  function closeAppointmentStatusDropdown() {
    const dropdown = document.getElementById('apptStatusDropdown');
    const toggle = document.getElementById('apptStatusToggle');
    dropdown?.classList.remove('open');
    toggle?.setAttribute('aria-expanded', 'false');
  }

  function setupAppointmentStatusDropdown() {
    const dropdown = document.getElementById('apptStatusDropdown');
    const toggle = document.getElementById('apptStatusToggle');
    const panel = document.getElementById('apptStatusPanel');

    toggle?.addEventListener('click', function (event) {
      event.stopPropagation();
      const isOpen = dropdown?.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    panel?.addEventListener('click', function (event) {
      event.stopPropagation();
      const option = event.target.closest('.appointment-status-option');
      if (!option) return;
      setAppointmentStatusFilter(option.dataset.statusValue || 'all');
      closeAppointmentStatusDropdown();
    });

    document.addEventListener('click', closeAppointmentStatusDropdown);
  }

  function getAppointmentFilterModal() {
    return document.getElementById('filterModal');
  }

  function openAppointmentFilterPanel() {
    const modal = getAppointmentFilterModal();
    if (!modal) return;

    syncAppointmentFilterInputs();
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('filter-lock');
    document.body.classList.add('filter-lock');
    renderAppointmentFilterChips();
    updateAppointmentShowResultsButton();
  }

  function closeAppointmentFilterPanel() {
    const modal = getAppointmentFilterModal();
    if (!modal) return;

    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('filter-lock');
    document.body.classList.remove('filter-lock');
  }

  function setAppointmentPeriodFilter(period = 'upcoming') {
    appointmentPeriodFilter = ['upcoming', 'past', 'all'].includes(period) ? period : 'upcoming';
    document.getElementById('upcomingSection')?.classList.toggle('hidden', appointmentPeriodFilter === 'past');
    document.getElementById('pastSection')?.classList.toggle('hidden', appointmentPeriodFilter === 'upcoming');
  }

  function normalizeAppointmentDate(value) {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
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

  function matchesAppointmentFilters(card, draft = null) {
    const filters = draft || {
      period: appointmentPeriodFilter,
      status: appointmentStatusFilter,
      sort: appointmentSortFilter,
      fromDate: appointmentFromDate,
      toDate: appointmentToDate,
    };

    const searchValue = (apptSearchInput?.value || '').toLowerCase().trim();
    const patient = card.dataset.patient || '';
    const patientId = card.dataset.patientId || '';
    const service = card.dataset.service || '';
    const status = card.dataset.status || '';
    const period = card.dataset.period || '';
    const date = normalizeAppointmentDate(card.dataset.date || '');

    const matchesSearch = !searchValue ||
      patient.includes(searchValue) ||
      patientId.includes(searchValue) ||
      service.includes(searchValue);

    const matchesPeriod = filters.period === 'all' || period === filters.period;
    const matchesStatus = filters.status === 'all' || status === filters.status ||
      (filters.status === 'cancelled' && status === 'canceled');

    let matchesDate = true;
    const fromDate = normalizeAppointmentDate(filters.fromDate);
    const toDate = normalizeAppointmentDate(filters.toDate);

    if ((fromDate || toDate) && !date) {
      matchesDate = false;
    } else {
      if (fromDate && date < fromDate) matchesDate = false;
      if (toDate && date > toDate) matchesDate = false;
    }

    return matchesSearch && matchesPeriod && matchesStatus && matchesDate;
  }

  function sortAppointmentGroups() {
    const sortValue = appointmentSortFilter;

    document.querySelectorAll('.appt-month-group').forEach((group) => {
      ['.desktop-appointments-table .space-y-2\\.5', '.mobile-appointments-list'].forEach((selector) => {
        const holder = group.querySelector(selector);
        if (!holder) return;

        const cards = Array.from(holder.querySelectorAll(':scope > .appt-card, :scope > .mobile-appt-card'));
        cards.sort((a, b) => {
          const aName = a.dataset.patient || '';
          const bName = b.dataset.patient || '';
          const aDate = normalizeAppointmentDate(a.dataset.date || '') || new Date(0);
          const bDate = normalizeAppointmentDate(b.dataset.date || '') || new Date(0);

          if (sortValue === 'az') return aName.localeCompare(bName);
          if (sortValue === 'za') return bName.localeCompare(aName);
          if (sortValue === 'oldest') return aDate - bDate;
          return bDate - aDate;
        });

        cards.forEach((card) => holder.appendChild(card));
      });
    });
  }

  function clearAppointmentSearch() {
    if (apptSearchInput) {
      apptSearchInput.value = '';
      apptSearchInput.dispatchEvent(new Event('input', { bubbles: true }));
      apptSearchInput.dispatchEvent(new Event('change', { bubbles: true }));
      apptSearchInput.focus();
    }
    applyAppointmentFilters();
  }

  function applyAppointmentFilters() {
    setAppointmentPeriodFilter(appointmentPeriodFilter);

    getAppointmentCards().forEach((card) => {
      card.classList.toggle('hidden', !matchesAppointmentFilters(card));
    });

    sortAppointmentGroups();

    document.querySelectorAll('.appt-month-group').forEach((group) => {
      const cards = Array.from(group.querySelectorAll('.appt-card, .mobile-appt-card'));
      const hasVisibleCard = cards.some((card) => !card.classList.contains('hidden'));
      group.classList.toggle('hidden', !hasVisibleCard);
    });

    updateFilteredEmptyState();
    updateAppointmentFilterButtonState();
  }

  function updateFilteredEmptyState() {
    const searchValue = (apptSearchInput?.value || '').trim();
    const hasSearch = searchValue.length > 0;

    const hasPanelStatusFilter = appointmentStatusFilterSource === 'panel' && appointmentStatusFilter !== 'all';
    const hasDropdownStatusFilter = appointmentStatusFilterSource !== 'panel' && appointmentStatusFilter !== 'all';

    const hasPanelFilters =
      hasPanelStatusFilter ||
      appointmentPeriodFilter !== 'upcoming' ||
      appointmentSortFilter !== 'newest' ||
      !!appointmentFromDate ||
      !!appointmentToDate;

    const upcomingCards = Array.from(document.querySelectorAll('#upcomingSection .appt-card, #upcomingSection .mobile-appt-card'));
    const pastCards = Array.from(document.querySelectorAll('#pastSection .appt-card, #pastSection .mobile-appt-card'));

    const upcomingVisible = upcomingCards.some(card => !card.classList.contains('hidden'));
    const pastVisible = pastCards.some(card => !card.classList.contains('hidden'));
    const searchTitle = hasSearch ? `No results for "${searchValue}"` : 'No results found';

    const upcomingSearchEmpty = document.getElementById('appointmentFilterEmptyUpcoming');
    const pastSearchEmpty = document.getElementById('appointmentFilterEmptyPast');
    const upcomingStatusEmpty = document.getElementById('appointmentStatusEmptyUpcoming');
    const pastStatusEmpty = document.getElementById('appointmentStatusEmptyPast');
    const upcomingStaticEmpty = document.getElementById('appointmentStaticEmptyUpcoming');
    const pastStaticEmpty = document.getElementById('appointmentStaticEmptyPast');

    const upcomingSearchTitle = document.getElementById('appointmentFilterEmptyUpcomingTitle');
    const pastSearchTitle = document.getElementById('appointmentFilterEmptyPastTitle');

    if (upcomingSearchTitle) upcomingSearchTitle.textContent = searchTitle;
    if (pastSearchTitle) pastSearchTitle.textContent = searchTitle;

    const statusEmptyCopy = {
      upcoming: { icon: 'fa-regular fa-calendar-xmark', title: 'No upcoming appointments', sub: 'New appointments will appear here once scheduled.' },
      rescheduled: { icon: 'fa-solid fa-rotate-right', title: 'No rescheduled appointments', sub: 'Rescheduled appointments will appear here once available.' },
      completed: { icon: 'fa-solid fa-circle-check', title: 'No completed appointments', sub: 'Completed appointments will appear here.' },
      cancelled: { icon: 'fa-regular fa-calendar-xmark', title: 'No cancelled appointments', sub: 'Cancelled appointments will appear here.' },
      all: {
        icon: 'fa-solid fa-sliders',
        title: hasPanelFilters ? 'No matches for your filters' : 'No appointments found',
        sub: hasPanelFilters ? 'Try removing or adjusting your filter criteria.' : 'Appointments will appear here once available.'
      }
    };

    const meta = hasPanelFilters
      ? statusEmptyCopy.all
      : (statusEmptyCopy[appointmentStatusFilter] || statusEmptyCopy.all);

    function setStatusEmptyContent(prefix) {
      const icon = document.getElementById(`appointmentStatusEmpty${prefix}Icon`);
      const title = document.getElementById(`appointmentStatusEmpty${prefix}Title`);
      const sub = document.getElementById(`appointmentStatusEmpty${prefix}Sub`);

      if (icon) icon.className = meta.icon;
      if (title) title.textContent = meta.title;
      if (sub) sub.textContent = meta.sub;
    }

    setStatusEmptyContent('Upcoming');
    setStatusEmptyContent('Past');

    const upcomingAllowed = appointmentPeriodFilter !== 'past';
    const pastAllowed = appointmentPeriodFilter !== 'upcoming';

    const showUpcomingSearchEmpty = upcomingAllowed && hasSearch && !upcomingVisible;
    const showPastSearchEmpty = pastAllowed && hasSearch && !pastVisible;

    const showUpcomingStatusEmpty =
      upcomingAllowed &&
      !hasSearch &&
      (hasPanelFilters || hasDropdownStatusFilter) &&
      !upcomingVisible;

    const showPastStatusEmpty =
      pastAllowed &&
      !hasSearch &&
      (hasPanelFilters || hasDropdownStatusFilter) &&
      !pastVisible;

    upcomingSearchEmpty?.classList.toggle('show', showUpcomingSearchEmpty);
    upcomingSearchEmpty?.classList.toggle('is-visible', showUpcomingSearchEmpty);

    pastSearchEmpty?.classList.toggle('show', showPastSearchEmpty);
    pastSearchEmpty?.classList.toggle('is-visible', showPastSearchEmpty);

    upcomingStatusEmpty?.classList.toggle('show', showUpcomingStatusEmpty);
    upcomingStatusEmpty?.classList.toggle('is-visible', showUpcomingStatusEmpty);

    pastStatusEmpty?.classList.toggle('show', showPastStatusEmpty);
    pastStatusEmpty?.classList.toggle('is-visible', showPastStatusEmpty);

    document.querySelectorAll('.appointment-panel-empty-clear').forEach(btn => {
      btn.classList.toggle('hidden', !hasPanelFilters);
    });

    upcomingStaticEmpty?.classList.toggle('hidden', !upcomingAllowed || showUpcomingSearchEmpty || showUpcomingStatusEmpty);
    pastStaticEmpty?.classList.toggle('hidden', !pastAllowed || showPastSearchEmpty || showPastStatusEmpty);
  }

  function getDraftAppointmentFilters() {
    const activeSort = document.querySelector('#apptSortGroup .ftag.ftag-active');
    const selectedPeriod = document.querySelector('input[name="appointment_period"]:checked');
    const selectedStatus = document.querySelector('input[name="appointment_status"]:checked');

    return {
      sort: activeSort?.dataset.sort || 'newest',
      period: selectedPeriod?.value || 'upcoming',
      status: selectedStatus?.value || 'all',
      fromDate: document.getElementById('fromDate')?.value || '',
      toDate: document.getElementById('toDate')?.value || '',
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
    const badge = document.getElementById('appointmentFilterBadge');
    const filterBtn = document.getElementById('appointmentFilterBtn');
    const clearBtn = document.getElementById('appointmentClearFilterBtn');
    const activeCount = [
      appointmentPeriodFilter !== 'upcoming',
      appointmentStatusFilterSource === 'panel' && appointmentStatusFilter !== 'all',
      !!appointmentFromDate || !!appointmentToDate,
      appointmentSortFilter !== 'newest',
    ].filter(Boolean).length;

    if (badge) {
      badge.textContent = activeCount;
      badge.style.display = activeCount ? 'inline-flex' : 'none';
    }

    filterBtn?.classList.toggle('has-filters', activeCount > 0);
    filterBtn?.setAttribute('aria-pressed', activeCount > 0 ? 'true' : 'false');
    clearBtn?.classList.toggle('hidden', activeCount === 0);
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
      chip.innerHTML = `<span>${label}</span><span class="filter-chip-remove"><i class="fa-solid fa-xmark"></i></span>`;
      chip.querySelector('.filter-chip-remove').onclick = function () {
        callback();
        renderAppointmentFilterChips();
        updateAppointmentShowResultsButton();
      };
      container.appendChild(chip);
    }

    const draft = getDraftAppointmentFilters();

    if (draft.sort !== 'newest') {
      const sortLabel = document.querySelector(`#apptSortGroup .ftag[data-sort="${draft.sort}"]`)?.textContent.trim() || draft.sort;
      addChip(`Sort: ${sortLabel}`, function () {
        document.querySelectorAll('#apptSortGroup .ftag').forEach(btn => btn.classList.remove('ftag-active'));
        document.querySelector('#apptSortGroup .ftag[data-sort="newest"]')?.classList.add('ftag-active');
      });
    }

    if (draft.period !== 'upcoming') {
      const periodLabel = document.querySelector(`input[name="appointment_period"][value="${draft.period}"]`)?.closest('label')?.textContent.trim() || draft.period;
      addChip(`View: ${periodLabel}`, function () {
        const input = document.querySelector('input[name="appointment_period"][value="upcoming"]');
        if (input) input.checked = true;
      });
    }

    if (draft.status !== 'all') {
      const statusLabel = document.querySelector(`input[name="appointment_status"][value="${draft.status}"]`)?.closest('label')?.textContent.trim() || draft.status;

      addChip(`Status: ${statusLabel}`, function () {
        const input = document.querySelector('input[name="appointment_status"][value="all"]');
        if (input) input.checked = true;
      });
    }

    if (draft.fromDate || draft.toDate) {
      addChip(`Date: ${draft.fromDate || 'Any'} to ${draft.toDate || 'Any'}`, function () {
        const from = document.getElementById('fromDate');
        const to = document.getElementById('toDate');
        if (from) from.value = '';
        if (to) to.value = '';
        document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => btn.classList.remove('active'));
      });
    }

    section.classList.toggle('hidden', !hasChips);
  }

  function syncAppointmentFilterInputs() {
    document.querySelectorAll('#apptSortGroup .ftag').forEach(btn => {
      btn.classList.toggle('ftag-active', btn.dataset.sort === appointmentSortFilter);
    });

    const periodInput = document.querySelector(`input[name="appointment_period"][value="${appointmentPeriodFilter}"]`);
    if (periodInput) periodInput.checked = true;

    const panelStatusValue = appointmentStatusFilterSource === 'panel'
      ? appointmentStatusFilter
      : 'all';

    const statusInput = document.querySelector(`input[name="appointment_status"][value="${panelStatusValue}"]`);
    if (statusInput) statusInput.checked = true;

    const from = document.getElementById('fromDate');
    const to = document.getElementById('toDate');

    if (from) from.value = appointmentFromDate;
    if (to) to.value = appointmentToDate;
  }

  function resetAppointmentFilters() {
    appointmentPeriodFilter = 'upcoming';
    appointmentStatusFilter = 'all';
    appointmentStatusFilterSource = 'dropdown';
    appointmentSortFilter = 'newest';
    appointmentFromDate = '';
    appointmentToDate = '';

    setAppointmentStatusFilter('all', false, 'dropdown');
    syncAppointmentFilterInputs();

    document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => btn.classList.remove('active'));

    renderAppointmentFilterChips();
    applyAppointmentFilters();
  }

  function setupAppointmentFilterPanel() {
    const filterModal = getAppointmentFilterModal();
    const closeBtn = document.getElementById('closeFilterModalBtn');
    const cancelBtn = document.getElementById('cancelFilterBtn');
    const applyBtn = document.getElementById('applyFilters');
    const clearBtn = document.getElementById('clearFiltersModal');
    const clearAllBtn = document.getElementById('clearAllChipsBtn');

    closeBtn?.addEventListener('click', closeAppointmentFilterPanel);
    cancelBtn?.addEventListener('click', closeAppointmentFilterPanel);

    document.querySelectorAll('#apptSortGroup .ftag').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('#apptSortGroup .ftag').forEach(item => item.classList.remove('ftag-active'));
        btn.classList.add('ftag-active');
        renderAppointmentFilterChips();
        updateAppointmentShowResultsButton();
      });
    });

    filterModal?.querySelectorAll('input[type="radio"]').forEach(input => {
      input.addEventListener('change', function () {
        renderAppointmentFilterChips();
        updateAppointmentShowResultsButton();
      });
    });

    filterModal?.querySelectorAll('#fromDate, #toDate').forEach(input => {
      input.addEventListener('change', function () {
        renderAppointmentFilterChips();
        updateAppointmentShowResultsButton();
      });
    });

    document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(item => item.classList.remove('active'));
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

    applyBtn?.addEventListener('click', function () {
      const draft = getDraftAppointmentFilters();

      appointmentSortFilter = draft.sort;
      appointmentPeriodFilter = draft.period;
      appointmentFromDate = draft.fromDate;
      appointmentToDate = draft.toDate;

      if (draft.status !== 'all') {
        setAppointmentStatusFilter(draft.status, false, 'panel');
      } else if (appointmentStatusFilterSource === 'panel') {
        setAppointmentStatusFilter('all', false, 'dropdown');
      }

      applyAppointmentFilters();
      closeAppointmentFilterPanel();
    });

    clearBtn?.addEventListener('click', function () {
      resetAppointmentFilters();
      openAppointmentFilterPanel();
    });

    clearAllBtn?.addEventListener('click', function () {
      resetAppointmentFilters();
      openAppointmentFilterPanel();
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    window.initGlobalVoiceInputs?.();

    apptSearchInput = document.getElementById('apptSearchInput');
    apptStatusFilter = document.getElementById('apptStatusFilter');

    apptSearchInput?.addEventListener('input', applyAppointmentFilters);
    apptStatusFilter?.addEventListener('change', function () {
      appointmentStatusFilter = apptStatusFilter.value || 'all';
      applyAppointmentFilters();
    });

    hydratePastCancellationReasons();
    setupAppointmentStatusDropdown();
    setupAppointmentFilterPanel();
    syncResponsiveAppointmentView();
    setAppointmentStatusFilter('all', false);
    applyAppointmentFilters();
    updateAppointmentFilterButtonState();
  });

  window.addEventListener('resize', syncResponsiveAppointmentView);

  /* Admin fallback: keeps the dentist-style buttons working even if the shared dentist modal helpers are not loaded on the admin layout. */
  if (typeof window.openRescheduleModal !== 'function') {
    window.openRescheduleModal = function (payload) {
      const targetUrl = payload?.updateUrl || (payload?.id ? `/admin/appointments/${payload.id}/reschedule` : null);
      if (targetUrl) window.location.href = targetUrl;
    };
  }

  if (typeof window.cancelAppointmentFromModal !== 'function') {
    window.cancelAppointmentFromModal = function (cancelUrl, patientName, datetimeLabel) {
      const confirmed = window.confirm(`Cancel ${patientName || 'this patient'}'s appointment${datetimeLabel ? ` (${datetimeLabel})` : ''}?`);
      if (!confirmed || !cancelUrl) return;

      fetch(cancelUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ reason: 'Cancelled by admin' })
      })
        .then((res) => res.json())
        .then((data) => {
          if (data?.success) {
            window.location.reload();
            return;
          }
          alert(data?.message || 'Unable to cancel appointment.');
        })
        .catch(() => alert('Could not reach the server.'));
    };
  }
</script>

@endsection