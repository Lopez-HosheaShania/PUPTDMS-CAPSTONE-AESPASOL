@extends('layouts.dentist')

@section('title', 'Appointments | PUP Taguig Dental Clinic')

@section('usesAppointmentCalendar', true)

@section('content')

@php
$upcomingAppointments = $upcomingAppointments ?? collect();
$pastAppointments = $pastAppointments ?? collect();
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
'cancelled' => $allAppointments->filter(fn($a) => in_array(strtolower($a->status ?? ''), ['cancelled', 'canceled']))->count(),
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

<main id="mainContent" class="dentist-page-shell dentist-appointments-page page-enter mode-list">
  <div class="w-full">

    <div class="appointment-header-wrap mb-8">
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
          <div class="view-toggle-container hidden md:flex">
            <div class="view-slider"></div>

            <button id="btnListView" onclick="switchView('list')" class="btn-view-mode active" title="List View">
              <i class="fa-solid fa-list text-sm"></i>
            </button>

            <button id="btnGridView" onclick="switchView('grid')" class="btn-view-mode" title="Grid View">
              <i class="fa-solid fa-grip"></i>
            </button>
          </div>

          <div class="tab-toggle-wrap">
            <button id="btnUpcoming" type="button" class="tab-btn-toggle active">
              <i class="fa-solid fa-calendar-clock text-xs"></i>
              Upcoming
              <span class="tab-count-badge">{{ $upcomingTotal }}</span>
            </button>

            <button id="btnPast" type="button" class="tab-btn-toggle">
              <i class="fa-solid fa-clock-rotate-left text-xs"></i>
              Past
              <span class="tab-count-badge">{{ $pastTotal }}</span>
            </button>
          </div>
        </div>
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
            $program = optional($appt->patient)->program ?? '—';
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
                  <a href="{{ route('dentist.dentist.patient.profile', $appt->patient_id) }}?from=appointments"
                    class="action-btn action-btn-view" data-tooltip="View profile">
                    <i class="fa-regular fa-user"></i>
                  </a>

                  <button type="button" class="action-btn action-btn-start" data-tooltip="Start procedure"
                    onclick="openStartProcedureModal(this)" data-id="{{ $appt->id }}" data-name="{{ $patientName }}"
                    data-datetime="{{ $modalDatetime }}" data-start-url="{{ route('dentist.odontogram', $appt->patient_id) }}?from=appointments&appointment_id={{ $appt->id }}&start_procedure=1" {{ $isToday ? '' : 'disabled' }}>
                    <i class="fa-solid fa-play"></i>
                  </button>

                  <button type="button" class="action-btn action-btn-reschedule" data-tooltip="Reschedule" onclick="openRescheduleModal({
      id: '{{ $appt->id }}',
      name: @js($patientName),
      datetime: @js($modalDatetime),
      serviceType: @js($appt->service_type),
      updateUrl: '{{ route('dentist.dentist.appointments.reschedule.update', $appt->id) }}'
    })">
                    <i class="fa-solid fa-rotate-right"></i>
                  </button>

                  <button type="button" class="action-btn action-btn-cancel" data-tooltip="Cancel appointment"
                    onclick="cancelAppointmentFromModal('{{ route('dentist.dentist.appointments.cancel', $appt->id) }}', @js($patientName), @js($dateLabel . ' | ' . $timeLabel))">
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
          $program = optional($appt->patient)->program ?? '—';
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

            <div class="grid grid-cols-2 gap-3">
              <button type="button" class="action-btn action-btn-start" onclick="openStartProcedureModal(this)"
                data-id="{{ $appt->id }}" data-name="{{ $patientName }}" data-datetime="{{ $modalDatetime }}"
                data-start-url="{{ route('dentist.odontogram', $appt->patient_id) }}?from=appointments&appointment_id={{ $appt->id }}&start_procedure=1"
                {{ $isToday ? '' : 'disabled' }}>
                <i class="fa-solid fa-play text-[10px]"></i> Start
              </button>

              <a href="{{ route('dentist.dentist.patient.profile', $appt->patient_id) }}?from=appointments"
                class="action-btn action-btn-view">
                <i class="fa-regular fa-user text-[10px]"></i> View
              </a>

              <button type="button" class="action-btn action-btn-reschedule" onclick="openRescheduleModal({
                    id: '{{ $appt->id }}',
                    name: @js($patientName),
                    datetime: @js($modalDatetime),
                    serviceType: @js($appt->service_type),
                    updateUrl: '{{ route('dentist.dentist.appointments.reschedule.update', $appt->id) }}'
                })" data-id="{{ $appt->id }}" data-name="{{ $patientName }}" data-datetime="{{ $modalDatetime }}">
                <i class="fa-solid fa-rotate-right text-[10px]"></i> Reschedule
              </button>

              <button type="button" class="action-btn action-btn-cancel"
                onclick="cancelAppointmentFromModal('{{ route('dentist.dentist.appointments.cancel', $appt->id) }}', @js($patientName), @js($dateLabel . ' | ' . $timeLabel))">
                <i class="fa-solid fa-xmark text-[10px]"></i> Cancel
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

      <div id="appointmentFilterEmptyUpcoming" class="empty-state empty-state-controlled">
        <div class="empty-state-icon appointment-empty-icon">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <p id="appointmentFilterEmptyUpcomingTitle" class="empty-state-title">
          No results found
        </p>

        <p class="empty-state-sub">
          Try a different name, ID, or service type.
        </p>

        <button type="button" onclick="clearAppointmentSearch()" class="empty-state-btn">
          <i class="fa-solid fa-xmark"></i>
          Clear search
        </button>
      </div>

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
            $program = optional($appt->patient)->program ?? '—';
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

            <div class="appt-card opacity-70" data-patient="{{ strtolower($patientName) }}"
              data-service="{{ strtolower($serviceLabel) }}"
              data-patient-id="{{ strtolower((string) ($appt->patient_id ?? '')) }}"
              data-status="{{ $isCancelledPast ? 'cancelled' : 'completed' }}" style="animation-delay:{{ $i * 0.04 }}s">

              <div class="grid gap-4 items-center px-5 py-3.5"
                style="grid-template-columns: 130px 100px 160px minmax(160px,1fr) 100px 150px 100px;">

                <div>
                  <p class="text-[13px] font-semibold text-gray-500">{{ $dateLabel }}</p>
                  <p class="text-[11px] text-gray-400 mt-0.5">{{ $weekday }}</p>
                </div>

                <div><span class="time-chip text-gray-400"><i class="fa-regular fa-clock text-xs"></i>{{ $timeLabel
                    }}</span>
                </div>

                <div class="flex items-center justify-start"><span class="service-badge {{ $badgeClass }} opacity-70">{{
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

                  <a href="{{ route('dentist.dentist.patient.profile', $appt->patient_id) }}?from=appointments"
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
          $program = optional($appt->patient)->program ?? '—';
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

          <div class="mobile-appt-card opacity-75 border-gray-200" data-patient="{{ strtolower($patientName) }}"
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

              <div class="grid grid-cols-2 gap-3">
                <button type="button" class="action-btn action-btn-record" onclick="openRecordModal(this)"
                  data-appt-id="{{ $appt->id }}" data-service="{{ $serviceLabel }}" data-date="{{ $dateLabel }}"
                  data-time="{{ $timeLabel }}" data-status="{{ $pastStatusLabel }}"
                  data-duration="{{ $recordDuration }}" data-remarks="{{ $recordRemarks }}"
                  data-oral="{{ $recordOral }}" data-diagnosis="{{ $recordDiagnosis }}"
                  data-prescription="{{ $recordPrescription }}">
                  <i class="fa-regular fa-eye text-[10px]"></i> Details
                </button>

                <a href="{{ route('dentist.dentist.patient.profile', $appt->patient_id) }}?from=appointments"
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
      <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
        <i class="fa-regular fa-calendar-xmark text-4xl sm:text-5xl mb-4 text-gray-300"></i>
        <p class="text-base font-semibold text-gray-500">No past appointments</p>
        <p class="text-sm mt-1">Completed appointments will appear here.</p>
      </div>
      @endforelse

      <div id="appointmentFilterEmptyPast" class="empty-state empty-state-controlled">
        <div class="empty-state-icon appointment-empty-icon">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <p id="appointmentFilterEmptyPastTitle" class="empty-state-title">
          No results found
        </p>

        <p class="empty-state-sub">
          Try a different name, ID, or service type.
        </p>

        <button type="button" onclick="clearAppointmentSearch()" class="empty-state-btn">
          <i class="fa-solid fa-xmark"></i>
          Clear search
        </button>
      </div>

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
      </div>
    </section>

    <div class="pb-16"></div>
  </div>
</main>

<div id="actionTooltip" class="action-tooltip">
  <div class="action-tooltip-bubble" id="actionTooltipText"></div>
</div>

<div id="startProcedureModal"
  class="start-procedure-overlay fixed inset-0 hidden z-[9999] items-end sm:items-center justify-center p-0 sm:p-4">
  <div class="start-procedure-shell modal-box">
    <div class="start-procedure-header">
      <div class="start-procedure-header-left">
        <div class="start-procedure-icon">
          <i class="fa-solid fa-tooth"></i>
        </div>

        <div class="min-w-0">
          <h2>Start Procedure</h2>
          <p>Open the odontogram to begin this appointment.</p>
        </div>
      </div>

      <button type="button" class="start-procedure-close" onclick="closeStartProcedureModal()"
        aria-label="Close start procedure modal">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="start-procedure-body">
      <div class="start-procedure-alert">
        <div class="start-procedure-alert-icon">
          <i class="fa-solid fa-play"></i>
        </div>

        <div>
          <p class="start-procedure-alert-title">Ready to start this appointment?</p>
          <p class="start-procedure-alert-sub">You will be redirected to the odontogram page for the selected patient.</p>
        </div>
      </div>

      <div class="start-procedure-card">
        <div class="start-procedure-card-row">
          <span>Patient</span>
          <strong id="startPatientName">—</strong>
        </div>

        <div class="start-procedure-card-row">
          <span>Schedule</span>
          <strong id="startAppointmentDate">—</strong>
        </div>
      </div>
    </div>

    <div class="start-procedure-footer">
      <button type="button" onclick="closeStartProcedureModal()" class="start-procedure-btn start-procedure-btn-cancel">
        Cancel
      </button>

      <button type="button" onclick="confirmStartProcedure()" class="start-procedure-btn start-procedure-btn-primary">
        <i class="fa-solid fa-tooth"></i>
        Start Procedure
      </button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  document.getElementById('btnUpcoming')?.addEventListener('click', () => setActiveTab('upcoming'));
  document.getElementById('btnPast')?.addEventListener('click', () => setActiveTab('past'));

  function setActiveTab(tab) {
    const isUpcoming = tab === 'upcoming';
    document.getElementById('upcomingSection')?.classList.toggle('hidden', !isUpcoming);
    document.getElementById('pastSection')?.classList.toggle('hidden', isUpcoming);
    document.getElementById('btnUpcoming')?.classList.toggle('active', isUpcoming);
    document.getElementById('btnPast')?.classList.toggle('active', !isUpcoming);
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
      const reason = normalizeCancelReasonLabel(pill.dataset.cancelReason || getStoredCancelReason(
        apptId));
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

  document.addEventListener('DOMContentLoaded', hydratePastCancellationReasons);

  function switchView(mode) {
    const mainContent = document.getElementById('mainContent');
    const btnList = document.getElementById('btnListView');
    const btnGrid = document.getElementById('btnGridView');

    if (mode === 'grid') {
      mainContent.classList.remove('mode-list');
      mainContent.classList.add('mode-grid');
      btnList.classList.remove('active');
      btnGrid.classList.add('active');
      localStorage.setItem('apptViewMode', 'grid');
    } else {
      mainContent.classList.remove('mode-grid');
      mainContent.classList.add('mode-list');
      btnGrid.classList.remove('active');
      btnList.classList.add('active');
      localStorage.setItem('apptViewMode', 'list');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.innerWidth <= 767) {
      switchView('grid');
    } else {
      const savedMode = localStorage.getItem('apptViewMode') || 'list';
      switchView(savedMode);
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth <= 767) {
      switchView('grid');
    }
  });

  function initActionTooltips() {
    const tooltip = document.getElementById('actionTooltip');
    const tooltipText = document.getElementById('actionTooltipText');

    if (!tooltip || !tooltipText) return;

    const targets = document.querySelectorAll('.appt-actions-wrap [data-tooltip]');

    const showTooltip = (el) => {
      if (el.disabled) return;

      tooltipText.textContent = el.dataset.tooltip || '';
      tooltip.classList.add('show');

      requestAnimationFrame(() => {
        const rect = el.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        const top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
        const left = rect.left - tooltipRect.width - 12;

        tooltip.style.top = `${Math.max(8, top)}px`;
        tooltip.style.left = `${Math.max(8, left)}px`;
      });
    };

    const hideTooltip = () => {
      tooltip.classList.remove('show');
    };

    targets.forEach((el) => {
      el.addEventListener('mouseenter', () => showTooltip(el));
      el.addEventListener('mouseleave', hideTooltip);
      el.addEventListener('focus', () => showTooltip(el));
      el.addEventListener('blur', hideTooltip);
    });

    window.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);
  }

  document.addEventListener('DOMContentLoaded', () => {
    initActionTooltips();
  });

  var selectedApptId = null;
  var selectedStartUrl = null;

  function openStartProcedureModal(btn) {
    if (!btn || btn.disabled) return;

    selectedApptId = btn.dataset.id || null;
    selectedStartUrl = btn.dataset.startUrl || null;

    document.getElementById('startPatientName').textContent = btn.dataset.name || '—';
    document.getElementById('startAppointmentDate').textContent = btn.dataset.datetime || '—';

    const modal = document.getElementById('startProcedureModal');
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
  }

  function closeStartProcedureModal() {
    const modal = document.getElementById('startProcedureModal');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');

    selectedApptId = null;
    selectedStartUrl = null;
  }

  function confirmStartProcedure() {
    if (!selectedStartUrl) return;

    window.location.href = selectedStartUrl;
  }

  const apptSearchInput = document.getElementById('apptSearchInput');
  const apptStatusFilter = document.getElementById('apptStatusFilter');

  const apptStatusMeta = {
    all: { label: 'All statuses', icon: 'fa-layer-group', tone: 'all' },
    upcoming: { label: 'Upcoming', icon: 'fa-calendar-check', tone: 'upcoming' },
    rescheduled: { label: 'Rescheduled', icon: 'fa-rotate-right', tone: 'rescheduled' },
    completed: { label: 'Completed', icon: 'fa-circle-check', tone: 'completed' },
    cancelled: { label: 'Cancelled', icon: 'fa-circle-xmark', tone: 'cancelled' }
  };

  function setAppointmentStatusFilter(value = 'all') {
    const nextValue = apptStatusMeta[value] ? value : 'all';
    const meta = apptStatusMeta[nextValue];

    if (apptStatusFilter) {
      apptStatusFilter.value = nextValue;
      apptStatusFilter.dispatchEvent(new Event('change', { bubbles: true }));
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
    const searchValue = (apptSearchInput?.value || '').toLowerCase().trim();
    const statusValue = apptStatusFilter?.value || 'all';

    document.querySelectorAll('.appt-card, .mobile-appt-card').forEach((card) => {
      const patient = card.dataset.patient || '';
      const patientId = card.dataset.patientId || '';
      const service = card.dataset.service || '';
      const status = card.dataset.status || '';

      const matchesSearch = !searchValue ||
        patient.includes(searchValue) ||
        patientId.includes(searchValue) ||
        service.includes(searchValue);

      const matchesStatus =
        statusValue === 'all' ||
        status === statusValue ||
        (statusValue === 'cancelled' && status === 'canceled');

      card.classList.toggle('hidden', !(matchesSearch && matchesStatus));
    });

    document.querySelectorAll('.appt-month-group').forEach((group) => {
      const cards = Array.from(group.querySelectorAll('.appt-card, .mobile-appt-card'));
      const hasVisibleCard = cards.some((card) => !card.classList.contains('hidden'));

      group.classList.toggle('hidden', !hasVisibleCard);
    });

    updateFilteredEmptyState();
  }

  function updateFilteredEmptyState() {
    const searchValue = (apptSearchInput?.value || '').trim();
    const statusValue = apptStatusFilter?.value || 'all';

    const hasSearch = searchValue.length > 0;
    const hasStatusFilter = statusValue !== 'all';

    const upcomingVisible = Array.from(
      document.querySelectorAll('#upcomingSection .appt-card, #upcomingSection .mobile-appt-card')
    ).some((card) => !card.classList.contains('hidden'));

    const pastVisible = Array.from(
      document.querySelectorAll('#pastSection .appt-card, #pastSection .mobile-appt-card')
    ).some((card) => !card.classList.contains('hidden'));

    const searchTitle = hasSearch ? `No results for "${searchValue}"` : 'No results found';

    const upcomingSearchEmpty = document.getElementById('appointmentFilterEmptyUpcoming');
    const pastSearchEmpty = document.getElementById('appointmentFilterEmptyPast');
    const upcomingStatusEmpty = document.getElementById('appointmentStatusEmptyUpcoming');
    const pastStatusEmpty = document.getElementById('appointmentStatusEmptyPast');

    const upcomingSearchTitle = document.getElementById('appointmentFilterEmptyUpcomingTitle');
    const pastSearchTitle = document.getElementById('appointmentFilterEmptyPastTitle');

    if (upcomingSearchTitle) upcomingSearchTitle.textContent = searchTitle;
    if (pastSearchTitle) pastSearchTitle.textContent = searchTitle;

    const statusEmptyCopy = {
      upcoming: {
        icon: 'fa-regular fa-calendar-xmark',
        title: 'No upcoming appointments',
        sub: 'New appointments will appear here once scheduled.'
      },
      rescheduled: {
        icon: 'fa-solid fa-rotate-right',
        title: 'No rescheduled appointments',
        sub: 'Rescheduled appointments will appear here once available.'
      },
      completed: {
        icon: 'fa-solid fa-circle-check',
        title: 'No completed appointments',
        sub: 'Completed appointments will appear here.'
      },
      cancelled: {
        icon: 'fa-regular fa-calendar-xmark',
        title: 'No cancelled appointments',
        sub: 'Cancelled appointments will appear here.'
      },
      all: {
        icon: 'fa-regular fa-calendar-xmark',
        title: 'No appointments found',
        sub: 'Appointments will appear here once available.'
      }
    };

    const meta = statusEmptyCopy[statusValue] || statusEmptyCopy.all;

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

    const showUpcomingSearchEmpty = hasSearch && !upcomingVisible;
    const showPastSearchEmpty = hasSearch && !pastVisible;
    const showUpcomingStatusEmpty = !hasSearch && hasStatusFilter && !upcomingVisible;
    const showPastStatusEmpty = !hasSearch && hasStatusFilter && !pastVisible;

    upcomingSearchEmpty?.classList.toggle('show', showUpcomingSearchEmpty);
    upcomingSearchEmpty?.classList.toggle('is-visible', showUpcomingSearchEmpty);

    pastSearchEmpty?.classList.toggle('show', showPastSearchEmpty);
    pastSearchEmpty?.classList.toggle('is-visible', showPastSearchEmpty);

    upcomingStatusEmpty?.classList.toggle('show', showUpcomingStatusEmpty);
    upcomingStatusEmpty?.classList.toggle('is-visible', showUpcomingStatusEmpty);

    pastStatusEmpty?.classList.toggle('show', showPastStatusEmpty);
    pastStatusEmpty?.classList.toggle('is-visible', showPastStatusEmpty);
  }

  apptSearchInput?.addEventListener('input', applyAppointmentFilters);
  apptStatusFilter?.addEventListener('change', applyAppointmentFilters);

  document.addEventListener('DOMContentLoaded', () => {
    window.initGlobalVoiceInputs?.();

    const dropdown = document.getElementById('apptStatusDropdown');
    const toggle = document.getElementById('apptStatusToggle');
    const panel = document.getElementById('apptStatusPanel');

    function closeAppointmentStatusDropdown() {
      dropdown?.classList.remove('open');
      toggle?.setAttribute('aria-expanded', 'false');
    }

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
      applyAppointmentFilters();
    });

    document.addEventListener('click', closeAppointmentStatusDropdown);
  });
</script>
@endsection