@extends('layouts.app')

@section('layout-role', $layoutRole)

@section('title', $pageTitle)

@section('styles')
    @vite('resources/css/pages/shared/patient-list.css')
@endsection

@section('content')

    @php
        $notifications = collect($notifications ?? []);
        $notifCount = $notifications->count();
        $isBookingMode = $bookingMode ?? request()->routeIs('admin.book_appointments.*');
        $pageHeading = $isDentistView
            ? 'Patient Directory'
            : $pageTitle ?? ($isBookingMode ? 'Select Patient for Booking' : 'Patient List');
    @endphp

    <main id="mainContent"
        class="{{ $pageShellClass }}
           shared-patient-page
           {{ $isDentistView ? 'dentist-patient-view' : 'admin-patient-view' }}
           page-enter
           mode-list">
        <div class="w-full">

            @php
                use Carbon\Carbon;
                $today = Carbon::today()->toDateString();
                $todayCount = $todayCount ?? 0;
                $upcomingCount = $upcomingCount ?? 0;
                $rescheduledCount = $rescheduledCount ?? 0;
                $cancelledCount = $cancelledCount ?? 0;
                $completedCount = $completedCount ?? 0;
                $allCount = $allCount ?? 0;
            @endphp

            @if ($isDentistView)
                <div class="dentist-hero page-title-row mb-6">
                    <div class="dentist-hero-content">
                        <div class="dentist-hero-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="dentist-hero-eyebrow">
                                <i class="fa-solid fa-tooth"></i>
                                Patient Management
                            </div>

                            <h2 class="dentist-hero-title">
                                {{ $pageHeading }}
                            </h2>

                            <div class="page-summary patient-hero-summary">
                                <span class="summary-tag">
                                    <span class="summary-tag-dot bg-gray-400"></span>
                                    {{ $allCount }} total
                                </span>

                                @if ($todayCount > 0)
                                    <span class="summary-tag">
                                        <span class="summary-tag-dot bg-blue-500"></span>
                                        {{ $todayCount }} today
                                    </span>
                                @endif

                                @if ($upcomingCount > 0)
                                    <span class="summary-tag">
                                        <span class="summary-tag-dot bg-orange-500"></span>
                                        {{ $upcomingCount }} upcoming
                                    </span>
                                @endif

                                @if ($completedCount > 0)
                                    <span class="summary-tag">
                                        <span class="summary-tag-dot bg-green-500"></span>
                                        {{ $completedCount }} completed
                                    </span>
                                @endif

                                @if ($cancelledCount > 0)
                                    <span class="summary-tag">
                                        <span class="summary-tag-dot bg-red-500"></span>
                                        {{ $cancelledCount }} cancelled
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="page-banner mt-2 mb-6">
                    <div class="page-banner-inner">
                        <div>
                            <h1 class="page-title">{{ $pageHeading }}</h1>
                        </div>
                    </div>
                </div>
            @endif

            <div class="w-full">
                <div class="relative">

                    <div
                        class="table-card patient-table-card rounded-2xl border border-gray-200 shadow-sm overflow-visible">

                        @php
                            $patientStatusOptions = [
                                [
                                    'value' => 'all',
                                    'label' => 'All Patients',
                                    'icon' => 'fa-users',
                                    'tone' => 'status-all',
                                    'count' => $allCount ?? 0,
                                ],
                                [
                                    'value' => 'today',
                                    'label' => 'Today',
                                    'icon' => 'fa-clock',
                                    'tone' => 'status-today',
                                    'count' => $todayCount ?? 0,
                                ],
                                [
                                    'value' => 'upcoming',
                                    'label' => 'Upcoming',
                                    'icon' => 'fa-calendar-check',
                                    'tone' => 'status-upcoming',
                                    'count' => $upcomingCount ?? 0,
                                ],
                                [
                                    'value' => 'rescheduled',
                                    'label' => 'Rescheduled',
                                    'icon' => 'fa-calendar-plus',
                                    'tone' => 'status-rescheduled',
                                    'count' => $rescheduledCount ?? 0,
                                ],
                                [
                                    'value' => 'completed',
                                    'label' => 'Completed',
                                    'icon' => 'fa-check-double',
                                    'tone' => 'status-completed',
                                    'count' => $completedCount ?? 0,
                                ],
                                [
                                    'value' => 'cancelled',
                                    'label' => 'Cancelled',
                                    'icon' => 'fa-calendar-xmark',
                                    'tone' => 'status-cancelled',
                                    'count' => $cancelledCount ?? 0,
                                ],
                            ];
                        @endphp

                        <div class="patient-table-toolbar px-4 md:px-6 py-3.5 border-b border-gray-100">
                            <div class="patient-toolbar-main">
                                <div class="patient-search-wrap">
                                    <div class="patient-search-row voice-search-row">
                                        <x-search-bar id="searchInput" :placeholder="$isBookingMode ? 'Search patient to book' : 'Search patient'"
                                            callback="handlePatientDirectorySearch" :debounce="250" class="flex-1" />

                                        <x-voice-input target="#searchInput" status-id="patientSearchVoiceStatus"
                                            label="Use voice search" title="Voice search" />
                                    </div>
                                </div>

                                <div class="patient-toolbar-actions">
                                    <div class="patient-sort-row">
                                        <x-filter-select id="patientStatusFilter" name="patient_status" label="Status"
                                            value="all" :options="$patientStatusOptions" callback="handlePatientStatusSelect" />
                                    </div>

                                    <div class="patient-filter-actions">
                                        <button id="filterBtn" type="button" class="global-filter-btn"
                                            aria-label="Filter patients" data-tooltip="Filter" data-tooltip-tone="neutral">
                                            <i class="fa-solid fa-sliders"></i>
                                            <span>Filter</span>
                                            <span id="filterBadge" class="filter-badge" style="display:none;"></span>
                                        </button>
                                    </div>

                                    <x-view-toggle id="patientListViewToggle" root="#mainContent"
                                        storage-key="patientListViewMode" list-label="List" grid-label="Grid" />

                                    <button id="externalClearFilterBtn" type="button"
                                        class="global-filter-reset-btn hidden" aria-label="Reset filters"
                                        data-tooltip="Reset filters" data-tooltip-tone="neutral">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <x-pagination-bar id="patientPaginationTopBar" info-id="patientPageInfoTop"
                            pagination-id="patientPaginationTop" position="top" :show-entries="true"
                            page-size-id="patientPerPage" page-size-callback="changePatientPageSize" :page-size-value="10"
                            page-size-label="per page" label="patients" :total="$allCount" :from="$allCount > 0 ? 1 : 0"
                            :to="min(10, $allCount)" class="patient-pagebar" />

                        <div class="table-scroll-wrapper">
                            <div class="table-scroll-inner">

                                <div class="card-col-header">
                                    <span>Patient</span>
                                    <span>Date &amp; Time</span>
                                    <span>Service &amp; Status</span>
                                    <span></span>
                                </div>

                                <div id="patientSkeleton" class="hidden px-3 md:px-6 pb-6 pt-4">

                                    <div class="skeleton-list-layout space-y-3">
                                        @for ($i = 0; $i < 3; $i++)
                                            <div class="skeleton-shell p-4">
                                                <div class="flex items-center gap-5">
                                                    <div class="skeleton-circle w-14 h-14 flex-shrink-0"></div>

                                                    <div class="w-44 flex-shrink-0 space-y-2">
                                                        <div class="skeleton-line h-4 w-36"></div>
                                                        <div class="skeleton-pill h-5 w-20"></div>
                                                    </div>

                                                    <div class="skeleton-block h-10 w-px hidden lg:block"></div>

                                                    <div class="flex items-center gap-3 w-44 flex-shrink-0">
                                                        <div class="skeleton-block w-10 h-10"></div>
                                                        <div class="space-y-2">
                                                            <div class="skeleton-line h-3 w-20"></div>
                                                            <div class="skeleton-line h-4 w-28"></div>
                                                            <div class="skeleton-line h-3 w-16"></div>
                                                        </div>
                                                    </div>

                                                    <div class="skeleton-block h-10 w-px hidden lg:block"></div>

                                                    <div class="flex items-center gap-3 flex-1">
                                                        <div class="skeleton-block w-10 h-10"></div>
                                                        <div class="space-y-2 flex-1">
                                                            <div class="skeleton-line h-3 w-16"></div>
                                                            <div class="skeleton-line h-4 w-32"></div>
                                                            <div class="skeleton-pill h-6 w-36"></div>
                                                        </div>
                                                    </div>

                                                    <div class="skeleton-circle w-9 h-9 flex-shrink-0"></div>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>

                                    <div class="skeleton-grid-layout">
                                        @for ($i = 0; $i < 6; $i++)
                                            <div class="skeleton-shell patient-grid-skeleton-card">
                                                <div class="flex items-start gap-3">
                                                    <div class="skeleton-circle w-[54px] h-[54px] flex-shrink-0"></div>

                                                    <div class="flex-1 min-w-0 space-y-2">
                                                        <div class="skeleton-line h-4 w-4/5"></div>
                                                        <div class="skeleton-line h-4 w-3/5"></div>

                                                        <div class="flex gap-2 pt-1">
                                                            <div class="skeleton-pill h-6 w-28"></div>
                                                            <div class="skeleton-pill h-6 w-24"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-y-2 mt-4">
                                                    <div class="skeleton-block h-14 w-full"></div>
                                                    <div class="skeleton-block h-14 w-full"></div>
                                                </div>

                                                <div class="flex items-center justify-between gap-2 mt-4">
                                                    <div class="skeleton-pill h-7 w-28"></div>
                                                    <div class="skeleton-pill h-7 w-20"></div>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>

                                <div id="patientContainer">

                       @php
                                    $appointments = collect($appointments)
                                        ->sort(function ($a, $b) {
                                            $aStatus = strtolower(
                                                trim((string) ($a->status ?? 'upcoming'))
                                            );

                                            $bStatus = strtolower(
                                                trim((string) ($b->status ?? 'upcoming'))
                                            );

                                            $activeStatuses = [
                                                'upcoming',
                                                'rescheduled',
                                                'pending',
                                                'confirmed',
                                            ];

                                            $aIsActive = in_array(
                                                $aStatus,
                                                $activeStatuses,
                                                true
                                            );

                                            $bIsActive = in_array(
                                                $bStatus,
                                                $activeStatuses,
                                                true
                                            );

                                            if ($aIsActive !== $bIsActive) {
                                                return $aIsActive ? -1 : 1;
                                            }

                                            $aDateTime = Carbon::parse(
                                                ($a->appointment_date ?? '1970-01-01') .
                                                    ' ' .
                                                    ($a->appointment_time ?? '00:00:00')
                                            );

                                            $bDateTime = Carbon::parse(
                                                ($b->appointment_date ?? '1970-01-01') .
                                                    ' ' .
                                                    ($b->appointment_time ?? '00:00:00')
                                            );

                                            if ($aIsActive && $bIsActive) {
                                                return $aDateTime <=> $bDateTime;
                                            }

                                            return $bDateTime <=> $aDateTime;
                                        })
                                        ->values();
                                @endphp

                                    @foreach ($appointments as $appt)
                                        @php
                                            $status = strtolower($appt->status ?? '');
                                            $isCancelled = $status === 'cancelled';
                                            $isCompleted = $status === 'completed';
                                            $isRescheduled = $status === 'rescheduled';
                                            $isToday =
                                                $appt->appointment_date === $today && !$isCancelled && !$isCompleted;
                                            $isUpcoming =
                                                $appt->appointment_date > $today &&
                                                in_array(
                                                    $status,
                                                    ['upcoming', 'rescheduled', 'pending', 'confirmed'],
                                                    true,
                                                );

                                            $tabClass = $isCancelled
                                                ? 'cancelled'
                                                : ($isCompleted
                                                    ? 'completed'
                                                    : ($isRescheduled
                                                        ? 'rescheduled'
                                                        : ($isToday
                                                            ? 'today'
                                                            : ($isUpcoming
                                                                ? 'upcoming'
                                                                : 'all'))));

                                            $patient = $appt->patient;
                                            $isWalkInAppointment = (bool) ($appt->is_walk_in ?? false);
                                            $isFollowUpAppointment = (bool) ($appt->is_follow_up ?? false);
                                            $patientId = $patient?->id ?? $appt->patient_id;
                                            $patientName = $patient?->name ?? 'Unknown Patient';
                                            $patientName =
                                                preg_replace_callback(
                                                    '/\b(ii|iii|iv|v|vi|vii|viii|ix|x)\.?$/i',
                                                    fn($matches) => strtoupper($matches[0]),
                                                    $patientName,
                                                ) ?? $patientName;
                                            $patientStudentNo = filled($patient?->student_no)
                                                ? $patient->student_no
                                                : (filled($patient?->faculty_code)
                                                    ? 'Faculty: ' . $patient->faculty_code
                                                    : 'No identity number');

                                            $patientCourseCode = trim((string) ($patient?->course_code ?? ''));
                                            $patientCourseName = trim((string) ($patient?->course_name ?? ''));
                                           

                                            $patientCourse =
                                                $patientCourseCode !== ''
                                                    ? $patientCourseCode
                                                    : ($patientCourseName !== ''
                                                        ? $patientCourseName
                                                        : 'No program');

                                            $patientCourseFull = collect([
                                                $patientCourseCode,
                                                $patientCourseName !== $patientCourseCode ? $patientCourseName : null,
                                            ])
                                                ->filter()
                                                ->implode(' — ');

                                            if ($patientCourseFull === '') {
                                                $patientCourseFull = 'No program';
                                            }
                                           

                                            $patientYearLevel = $patient?->year_level ?? '';
                                            $patientSection = $patient?->section ?? '';

                                            $patientImage = $patient?->profile_image
                                                ? asset('storage/' . $patient->profile_image)
                                                : null;
                                          

                                            $dateLabel = Carbon::parse($appt->appointment_date)->format('l, F j, Y');

                                            $gridDayLabel = Carbon::parse($appt->appointment_date)->format('l');
                                            $gridDateLabel = Carbon::parse($appt->appointment_date)->format('F j, Y');
                                      

                                            $timeLabel = Carbon::parse($appt->appointment_time)->format('g:i A');
                                            $serviceLabel =
                                                $appt->service_type_name === 'Others'
                                                    ? ($appt->other_services ?:
                                                    'Others')
                                                    : $appt->service_type_name;

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

                                            $statusToneClass = $isCancelled
                                                ? 'status-cancelled'
                                                : ($isCompleted
                                                    ? 'status-completed'
                                                    : ($isRescheduled
                                                        ? 'status-rescheduled'
                                                        : ($isToday
                                                            ? 'status-today'
                                                            : ($isUpcoming
                                                                ? 'status-upcoming'
                                                                : 'status-default'))));
                                            $pillClass = $isCancelled
                                                ? 'pill-cancelled'
                                                : ($isCompleted
                                                    ? 'pill-completed'
                                                    : ($isRescheduled
                                                        ? 'pill-rescheduled'
                                                        : ($isToday
                                                            ? 'pill-today'
                                                            : ($isUpcoming
                                                                ? 'pill-upcoming'
                                                                : 'pill-default'))));
                                            $pillText = $isCancelled
                                                ? 'Cancelled'
                                                : ($isCompleted
                                                    ? 'Completed'
                                                    : ($isRescheduled
                                                        ? 'Rescheduled'
                                                        : ($isToday
                                                            ? 'Appointment Today'
                                                            : ($isUpcoming
                                                                ? ($status === 'upcoming'
                                                                    ? 'Upcoming'
                                                                    : 'Upcoming ·
                                ' . ucfirst($status))
                                                                : ucfirst($status ?: 'Pending')))));

                                            $appointmentDate = Carbon::parse($appt->appointment_date)->startOfDay();
                                            $todayDate = Carbon::today();
                                            $daysDiff = (int) $todayDate->diffInDays($appointmentDate, false);

                                            $showDateUrgency = !$isCancelled && !$isCompleted && $daysDiff >= 0;

                                            $urgencyLabel = $showDateUrgency
                                                ? ($daysDiff === 0
                                                    ? 'Today'
                                                    : ($daysDiff === 1
                                                        ? 'Tomorrow'
                                                        : 'In ' . $daysDiff . ' days'))
                                                : '';

                                            $urgencyClass = $showDateUrgency
                                                ? ($isRescheduled
                                                    ? 'status-rescheduled'
                                                    : ($daysDiff === 0
                                                        ? 'urgency-today'
                                                        : ($daysDiff === 1
                                                            ? 'urgency-tomorrow'
                                                            : 'urgency-upcoming')))
                                                : '';
                                        @endphp

                                        @php
                                            $patientProfileUrl = $patientId
                                                ? route($patientProfileRouteName, $patientId)
                                                : null;
                                        @endphp
                                        <div class="patient-card patient-item all {{ $tabClass }} block"
                                            data-patient-id="{{ $patientId }}" data-status="{{ $status }}"
                                            data-date="{{ $appt->appointment_date }}"
                                            data-time="{{ $appt->appointment_time ?? '00:00:00' }}">
                                            <div class="accent-bar {{ $statusToneClass }}"></div>

                                            <div class="patient-list-card-body">
                                                <div class="patient-list-main">
                                                    <span class="patient-avatar patient-avatar-md" data-patient-avatar
                                                        data-patient-name="{{ $patientName }}"
                                                        data-patient-url="{{ $patientImage }}"></span>
                                                    <div class="patient-list-person">
                                                        <div class="patient-list-name-row">

                                                            <h3 class="patient-list-name" data-patient-name>
                                                                {{ $patientName }}
                                                            </h3>

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

                                                        </div>

                                                        <div class="patient-list-meta">
                                                            <span class="global-info-pill">
                                                                <i class="fa-regular fa-id-card"></i>
                                                                {{ $patientStudentNo }}
                                                            </span>

                                                            <span class="global-info-pill"
                                                                title="{{ $patientCourseFull }}">
                                                                <i class="fa-solid fa-graduation-cap"></i>
                                                                {{ $patientCourse }}
                                                            </span>
                                                        </div>

                                                        <span class="patient-info hidden">
                                                            {{ $patientCourseCode }}|
                                                            {{ $patientYearLevel }}|
                                                            {{ $patientSection }}|
                                                            {{ $appt->appointment_date }}|
                                                            {{ $patient?->department ?? '' }}|
                                                            {{ optional($appt->created_at)->toDateTimeString() }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="patient-list-detail patient-list-date-block">
                                                    <span
                                                        class="patient-list-detail-icon global-info-icon {{ $statusToneClass }}">
                                                        <i class="fa-regular fa-calendar"></i>
                                                    </span>

                                                    <div class="patient-list-detail-copy">
                                                        <span class="patient-list-detail-label">
                                                            Appointment
                                                        </span>

                                                        <strong class="patient-list-detail-value">
                                                            {{ $dateLabel }}
                                                        </strong>

                                                        <small class="patient-list-detail-subvalue">
                                                            {{ $timeLabel }}
                                                        </small>
                                                    </div>
                                                </div>

                                                <div
                                                    class="patient-list-detail patient-list-service-block patient-service-block">
                                                    <span
                                                        class="patient-list-detail-icon global-info-icon {{ $statusToneClass }}">
                                                        <i class="fa-solid fa-tooth"></i>
                                                    </span>

                                                    <div class="patient-list-detail-copy">
                                                        <span class="patient-list-detail-label global-info-label">
                                                            Service
                                                        </span>

                                                        <strong class="patient-list-detail-value font-semibold">
                                                            {{ $serviceLabel }}
                                                        </strong>

                                                        <span class="status-pill {{ $pillClass }} patient-list-status">
                                                            <span class="pill-dot"></span>
                                                            {{ $pillText }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="appt-actions-wrap ui-action-group ml-auto">
                                                    @if ($patientProfileUrl)
                                                        <a href="{{ $patientProfileUrl }}"
                                                            class="ui-action-btn ui-action-view"
                                                            data-tooltip="View profile" aria-label="View profile">
                                                            <i class="fa-regular fa-user"></i>
                                                        </a>
                                                    @else
                                                        <button type="button" class="ui-action-btn ui-action-view"
                                                            disabled data-tooltip="No patient profile">
                                                            <i class="fa-regular fa-user"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="patient-grid-card-body">
                                                <div class="mobile-appt-card-head">
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                                            <p class="mobile-patient-name" data-patient-name>
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
                                                        </div>

                                                        <div class="global-info-group">
                                                            <span class="global-info-pill">
                                                                <i class="fa-regular fa-id-card"></i>
                                                                {{ $patientStudentNo }}
                                                            </span>

                                                            <span class="global-info-pill"
                                                                title="{{ $patientCourseFull }}">
                                                                <i class="fa-solid fa-graduation-cap"></i>
                                                                {{ $patientCourse }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-2 flex-wrap justify-end">
                                                        <div class="flex flex-col items-end gap-1">
                                                            <span
                                                                class="status-pill {{ $pillClass }} patient-grid-card-status">
                                                                <span class="pill-dot"></span>
                                                                {{ $pillText }}
                                                            </span>

                                                            @if ($showDateUrgency)
                                                                <span class="urgency-chip {{ $urgencyClass }}">
                                                                    {{ $urgencyLabel }}
                                                                </span>
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
                                                            {{ $gridDayLabel }}, {{ $gridDateLabel }}
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
                                                    @if ($patientProfileUrl)
                                                        <a href="{{ $patientProfileUrl }}"
                                                            class="ui-action-btn ui-action-view"
                                                            data-tooltip="View profile" aria-label="View profile">
                                                            <i class="fa-regular fa-user"></i>
                                                        </a>
                                                    @else
                                                        <button type="button" class="ui-action-btn ui-action-view"
                                                            disabled data-tooltip="No patient profile">
                                                            <i class="fa-regular fa-user"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <div id="patientBaseEmptyState" class="empty-state-host"></div>

                                    <div id="patientSearchEmptyState" class="empty-state-host"></div>

                                    <div id="patientStatusEmptyState" class="empty-state-host"></div>
                                </div>
                            </div>
                        </div>

                        <x-pagination-bar id="patientPaginationBottomBar" info-id="patientPageInfoBottom"
                            pagination-id="patientPaginationBottom" position="bottom" label="patients" :total="$allCount"
                            :from="$allCount > 0 ? 1 : 0" :to="min(10, $allCount)" class="patient-pagebar" />

                    </div>
                </div>
            </div>
    </main>

    <x-filter-drawer id="filterModal" title="Filters" close-id="closeFilterModalBtn" clear-id="clearFiltersModal"
        clear-label="Clear Filters" cancel-id="cancelFilterBtn" cancel-label="Cancel" apply-id="applyFilters"
        apply-label="Show 0 results" results-id="showResultsText">

        <div id="activeFiltersSection" class="filter-active-section hidden">

            <div class="filter-active-header">

                <span class="filter-active-title">
                    Active Filters
                </span>

                <button id="clearAllChipsBtn" type="button"
                    class="
                    filter-clear-all
                    ui-btn
                    ui-btn-secondary
                    ui-btn-sm
                ">
                    <i class="fa-solid fa-rotate-left"></i>

                    <span>
                        Clear All
                    </span>
                </button>

            </div>

            <div id="activeChipsContainer" class="active-filters-container"></div>

        </div>

        <x-filter-group title="Sort By">

            <div id="fSortGroup" class="filter-chip-row">

                <button type="button" class="ftag ftag-active" data-val="nearest">
                    Nearest Appointment
                </button>

                <button type="button" class="ftag" data-val="farthest">
                    Farthest Appointment
                </button>

                <button type="button" class="ftag" data-val="az">
                    Patient Name A-Z
                </button>

                <button type="button" class="ftag" data-val="za">
                    Patient Name Z-A
                </button>

            </div>

        </x-filter-group>

        <x-filter-group title="Filter by Date Range">

            <div id="datePresetGroup" class="filter-chip-row">

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

        </x-filter-group>

        <x-filter-group title="Custom Date Range">

            <div class="filter-date-grid">

                <div class="filter-date-input-wrap">

                    <input id="fromDate" type="text" class="js-flatpickr-date-range-from" placeholder="Start date"
                        readonly autocomplete="off">

                    <i class="fa-regular fa-calendar"></i>

                </div>

                <div class="filter-date-input-wrap">

                    <input id="toDate" type="text" class="js-flatpickr-date-range-to" placeholder="End date"
                        readonly autocomplete="off">

                    <i class="fa-regular fa-calendar"></i>

                </div>

            </div>

        </x-filter-group>

        <x-filter-group title="Course">

            <div class="filter-chip-grid">

                @foreach ([
            'BSIT',
            'BSECE',
            'BSBA - HRM',
            'BSED - ENG',
            'BSOA',
            'BSPSYCH',
            'DIT',
            'BSME',
            'BSBA - MM',
            'BSED
                - MATH',
            'DOMT',
        ] as $course)
                    <label class="choice-chip">

                        <input type="radio" name="course" value="{{ $course }}"
                            class="
                            filter-input
                            radio-red
                            chip-radio
                        ">

                        <span>
                            {{ $course }}
                        </span>

                    </label>
                @endforeach

            </div>

        </x-filter-group>

        <div class="filter-two-column-grid">

            <x-filter-group title="Year Level">

                <div class="filter-chip-row">

                    @foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $year)
                        <label class="choice-chip">

                            <input type="radio" name="year" value="{{ $year }}"
                                class="
                                filter-input
                                radio-red
                                chip-radio
                            ">

                            <span>
                                {{ $year }}
                            </span>

                        </label>
                    @endforeach

                </div>

            </x-filter-group>

            <x-filter-group title="Section">

                <div class="filter-chip-row">

                    @foreach (['1', '2'] as $section)
                        <label class="choice-chip">

                            <input type="radio" name="section" value="{{ $section }}"
                                class="
                                filter-input
                                radio-red
                                chip-radio
                            ">

                            <span>
                                {{ $section }}
                            </span>

                        </label>
                    @endforeach

                </div>

            </x-filter-group>

        </div>

        <x-filter-group title="Department" class="filter-group-last">

            <div class="filter-chip-row">

                @foreach (['Administrative', 'Faculty', 'Dependent'] as $department)
                    <label class="choice-chip">

                        <input type="radio" name="department" value="{{ $department }}"
                            class="
                            filter-input
                            radio-red
                            chip-radio
                        ">

                        <span>
                            {{ $department }}
                        </span>

                    </label>
                @endforeach

            </div>

        </x-filter-group>

    </x-filter-drawer>
@endsection

@section('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let patientFilterModal = null;
            let patientSearchInput = null;
            let patientFilterBtn = null;
            let patientFilterBadge = null;
            let patientExternalResetBtn = null;

            function openFilterModal() {
                window.openFilterDrawer?.('filterModal');
            }

            function closeFilterModal() {
                window.closeFilterDrawer?.('filterModal');
            }

            function onSearch(input) {
                if (!input) return;
                input.dispatchEvent(new Event('input'));
            }

            function resetPatientPanelFilters(
                shouldApply = true
            ) {
                clearFormState();

                selectedDepartment = null;
                selectedProgram = null;
                selectedYearLevel = null;
                selectedSection = null;

                activeFromDate = "";
                activeToDate = "";
                activeDatePreset = "";

                dateSort = "nearest";
                nameSort = null;

                renderFilterChips();
                syncMutualExclusion();
                updateFilterButtonState();

                if (shouldApply) {
                    applyFilters();
                }
            }

            function resetAllFilters() {
                resetPatientPanelFilters(
                    false
                );

                searchKeyword = '';

                if (patientSearchInput) {
                    patientSearchInput.value = '';

                    window.syncInputClearButton?.(
                        patientSearchInput
                    );
                }

                selectPatientStatus(
                    'all'
                );

                currentPage = 1;

                applyFilters();
            }

            try {
                var patientContainer = document.getElementById("patientContainer");
                if (!patientContainer) return;

                var patientSkeleton = document.getElementById("patientSkeleton");

                function showPatientSkeleton() {
                    if (!patientSkeleton || !patientContainer) return;
                    patientSkeleton.classList.remove("hidden");
                    patientContainer.classList.add("hidden");
                }

                function hidePatientSkeleton() {
                    if (!patientSkeleton || !patientContainer) return;
                    patientSkeleton.classList.add("hidden");
                    patientContainer.classList.remove("hidden");
                }

                var allPatients = Array.from(patientContainer.querySelectorAll(".patient-item"));
                var filterModal = document.getElementById("filterModal");
                var filterBtn = document.getElementById("filterBtn");
                var filterBadge = document.getElementById("filterBadge");
                var clearFiltersModalBtn = document.getElementById("clearFiltersModal");
                var applyFiltersBtn = document.getElementById("applyFilters");
                var searchInput = document.getElementById("searchInput");
                var externalClearFilterBtn = document.getElementById("externalClearFilterBtn");
                var colHeader = document.querySelector(".card-col-header");
                var patientSearchEmptyState =
                    document.getElementById("patientSearchEmptyState");

                var patientStatusEmptyState =
                    document.getElementById("patientStatusEmptyState");

                var patientBaseEmptyState =
                    document.getElementById(
                        "patientBaseEmptyState"
                    );

                patientFilterModal = filterModal;
                patientSearchInput = searchInput;
                patientFilterBtn = filterBtn;
                patientFilterBadge = filterBadge;
                patientExternalResetBtn = externalClearFilterBtn;

                var activeTab = "all";
                var searchKeyword = "";

                window.handlePatientStatusSelect =
                    function(value) {
                        activeTab =
                            String(value || 'all')
                            .trim()
                            .toLowerCase();
                        searchKeyword = '';

                        if (searchInput) {
                            searchInput.value = '';

                            window.syncInputClearButton?.(
                                searchInput
                            );
                        }

                        currentPage = 1;

                        applyFilters();
                    };

                window.handlePatientDirectorySearch =
                    function(value) {
                        searchKeyword =
                            String(value || '')
                            .trim()
                            .toLowerCase();
                        if (
                            searchKeyword &&
                            activeTab !== 'all'
                        ) {
                            activeTab = 'all';

                            window.setGlobalFilterSelectValue?.(
                                'patientStatusFilter',
                                'all', {
                                    callback: false,
                                    focus: false
                                }
                            );
                        }

                        currentPage = 1;

                        applyFilters();
                    };

                function selectPatientStatus(
                    status,
                    options = {}
                ) {
                    var nextStatus =
                        String(
                            status || 'all'
                        )
                        .trim()
                        .toLowerCase();

                    activeTab =
                        nextStatus;

                    window.setGlobalFilterSelectValue?.(
                        'patientStatusFilter',
                        nextStatus, {
                            callback: options.callback === true,

                            focus: false
                        }
                    );
                }

                var selectedProgram = null,
                    selectedYearLevel = null,
                    selectedSection = null,
                    selectedDepartment = null;
                var nameSort = null,
                    dateSort = "nearest";

                var activeFromDate = "",
                    activeToDate = "",
                    activeDatePreset = "";

                var deptRadios = Array.from(document.querySelectorAll('input[name="department"]'));
                var courseRadios = Array.from(document.querySelectorAll('input[name="course"]'));
                var yearRadios = Array.from(document.querySelectorAll('input[name="year"]'));
                var sectionRadios = Array.from(document.querySelectorAll('input[name="section"]'));
                var otherRadios = courseRadios.concat(yearRadios, sectionRadios);

                if (filterBtn) {
                    filterBtn.onclick = function(e) {
                        e.preventDefault();
                        renderFilterChips();
                        syncMutualExclusion();
                        updateShowResultsButton();
                        openFilterModal();
                    };
                }

                var cancelFilterBtn = document.getElementById("cancelFilterBtn");
                var closeFilterModalBtn =
                    document.getElementById("closeFilterModalBtn");

                if (closeFilterModalBtn) {
                    closeFilterModalBtn.onclick = function() {
                        closeFilterModal();
                        updateFilterButtonState();
                    };
                }
                if (cancelFilterBtn) {
                    cancelFilterBtn.onclick = function() {
                        if (filterModal) closeFilterModal();
                        updateFilterButtonState();
                    };
                }

                document.addEventListener("keydown", function(e) {
                    if (e.key === "Escape" && filterModal && filterModal.classList.contains("open")) {
                        closeFilterModal();
                        updateFilterButtonState();
                    }
                });

                function clearFormState() {
                    if (filterModal) {
                        filterModal.querySelectorAll("input[type='radio']").forEach(function(i) {
                            i.checked = false;
                            i.disabled = false;

                            var lbl = i.closest("label");
                            if (lbl) {
                                lbl.classList.remove("opacity-50", "cursor-not-allowed");
                            }
                        });
                    }

                    var f = document.getElementById("fromDate");
                    var t = document.getElementById("toDate");

                    if (f) f.value = "";
                    if (t) t.value = "";

                    document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(function(b) {
                        b.classList.remove('active');
                    });

                    selectedDepartment = null;
                    selectedProgram = null;
                    selectedYearLevel = null;
                    selectedSection = null;
                    activeFromDate = "";
                    activeToDate = "";
                    activeDatePreset = "";

                    window.syncFilterTagGroup(
                        "fSortGroup",
                        "nearest"
                    );

                    syncMutualExclusion();
                    updateFilterButtonState();

                    dateSort = 'nearest';
                    nameSort = null;

                    updateShowResultsButton();
                }

                function getDraftFilterState() {
                    var deptEl = filterModal ? filterModal.querySelector('input[name="department"]:checked') : null;
                    var crsEl = filterModal ? filterModal.querySelector('input[name="course"]:checked') : null;
                    var yrEl = filterModal ? filterModal.querySelector('input[name="year"]:checked') : null;
                    var secEl = filterModal ? filterModal.querySelector('input[name="section"]:checked') : null;

                    var fromDateEl = document.getElementById("fromDate");
                    var toDateEl = document.getElementById("toDate");

                    return {
                        department: deptEl ? deptEl.value : null,
                        program: crsEl ? crsEl.value : null,
                        year: yrEl ? yrEl.value : null,
                        section: secEl ? secEl.value : null,
                        fromDate: fromDateEl ? fromDateEl.value : "",
                        toDate: toDateEl ? toDateEl.value : ""
                    };
                }

                function hasDraftFilterChips() {
                    var draft = getDraftFilterState();

                    var sortActive = document.querySelector('#fSortGroup .ftag.ftag-active');
                    var sortVal = sortActive ?
                        sortActive.getAttribute("data-val") :
                        "nearest";

                    return !!(
                        draft.department ||
                        draft.program ||
                        draft.year ||
                        draft.section ||
                        draft.fromDate ||
                        draft.toDate ||
                        activeDatePreset ||
                        sortVal !== 'nearest'
                    );
                }

                function countDraftResults() {
                    var draft = getDraftFilterState();
                    var data = allPatients.slice();

                    if (searchKeyword) {
                        data = data.filter(function(patient) {
                            return matchesSearch(
                                patient,
                                searchKeyword
                            );
                        });
                    }

                    if (draft.program) {
                        data = data.filter(function(patient) {
                            return ilike(
                                getInfo(patient).program,
                                draft.program
                            );
                        });
                    }

                    if (draft.year || draft.section) {
                        data = data.filter(function(patient) {
                            var info = getInfo(patient);

                            if (
                                draft.year &&
                                !ilike(info.year, draft.year)
                            ) {
                                return false;
                            }

                            if (
                                draft.section &&
                                String(info.section).trim() !==
                                String(draft.section).trim()
                            ) {
                                return false;
                            }

                            return true;
                        });
                    }

                    if (draft.department) {
                        data = data.filter(function(patient) {
                            return ilike(
                                getInfo(patient).department,
                                draft.department
                            );
                        });
                    }

                    if (draft.fromDate || draft.toDate) {
                        data = data.filter(function(patient) {
                            var date = new Date(
                                getInfo(patient).dateStr
                            );

                            if (isNaN(date.getTime())) {
                                return false;
                            }

                            if (
                                draft.fromDate &&
                                date < new Date(draft.fromDate)
                            ) {
                                return false;
                            }

                            if (
                                draft.toDate &&
                                date > new Date(draft.toDate)
                            ) {
                                return false;
                            }

                            return true;
                        });
                    }

                    return data.length;
                }

                function updateShowResultsButton() {
                    if (!hasDraftFilterChips()) {
                        window.updateShowResultsText(0);
                        return;
                    }

                    var count = countDraftResults();
                    window.updateShowResultsText(count);
                }

                function renderFilterChips() {
                    var container = document.getElementById("activeChipsContainer");
                    var section = document.getElementById("activeFiltersSection");
                    if (!container || !section) return;

                    container.innerHTML = "";
                    var hasChips = false;

                    function addChip(label, callback) {
                        hasChips = true;
                        var chip = document.createElement("div");
                        chip.className = "filter-chip";
                        chip.innerHTML = "<span>" + label +
                            "</span><span class='filter-chip-remove'><i class='fa-solid fa-xmark'></i></span>";
                        chip.querySelector(".filter-chip-remove").onclick = function() {
                            callback();
                            renderFilterChips();
                            syncMutualExclusion();
                            updateShowResultsButton();
                        };
                        container.appendChild(chip);
                    }

                    var sortActive = document.querySelector('#fSortGroup .ftag.ftag-active');
                    if (
                        sortActive &&
                        sortActive.getAttribute("data-val") !== "nearest"
                    ) {
                        addChip("Sort: " + sortActive.textContent.trim().replace(/\n/g, ' '), function() {
                            document.querySelectorAll('#fSortGroup .ftag').forEach(function(b) {
                                b.classList.remove('ftag-active');
                            });
                            var defSort = document.querySelector(
                                '#fSortGroup .ftag[data-val="nearest"]'
                            );
                            if (defSort) defSort.classList.add('ftag-active');
                        });
                    }

                    var fDate = document.getElementById("fromDate");
                    var tDate = document.getElementById("toDate");
                    var activePresetBtn = document.querySelector('#datePresetGroup .quick-date-chip.active');

                    if (activePresetBtn) {
                        addChip(activePresetBtn.textContent.trim(), function() {
                            activePresetBtn.classList.remove("active");
                            if (fDate) fDate.value = "";
                            if (tDate) tDate.value = "";
                            activeDatePreset = "";
                        });
                    } else if (fDate && tDate && (fDate.value || tDate.value)) {
                        var lbl = fDate.value && tDate.value ? (fDate.value + " to " + tDate.value) : (fDate.value ?
                            "From " + fDate.value : "Until " + tDate.value);

                        addChip(lbl, function() {
                            fDate.value = "";
                            tDate.value = "";
                            activeDatePreset = "";
                        });
                    }

                    var course = document.querySelector('input[name="course"]:checked');
                    if (course) addChip(course.value, function() {
                        course.checked = false;
                    });

                    var year = document.querySelector('input[name="year"]:checked');
                    if (year) addChip(year.value, function() {
                        year.checked = false;
                    });

                    var sectionElem = document.querySelector('input[name="section"]:checked');
                    if (sectionElem) addChip("Section " + sectionElem.value, function() {
                        sectionElem.checked = false;
                    });

                    var dept = document.querySelector('input[name="department"]:checked');
                    if (dept) addChip(dept.value, function() {
                        dept.checked = false;
                    });

                    if (hasChips) {
                        section.classList.remove("hidden");
                        var clearAllBtn = document.getElementById("clearAllChipsBtn");
                        if (clearAllBtn) {
                            clearAllBtn.onclick = function() {
                                clearFormState();
                                renderFilterChips();

                                selectedDepartment = null;
                                selectedProgram = null;
                                selectedYearLevel = null;
                                selectedSection = null;
                                activeFromDate = "";
                                activeToDate = "";
                                dateSort = 'nearest';
                                nameSort = null;

                                applyFilters();
                            };
                        }
                    } else {
                        section.classList.add("hidden");
                    }

                    updateShowResultsButton();
                }

                if (filterModal) {
                    var radioInputs = filterModal.querySelectorAll('input[type="radio"]');

                    radioInputs.forEach(function(input) {
                        input.addEventListener("change", function() {
                            renderFilterChips();
                            syncMutualExclusion();
                            updateShowResultsButton();
                        });
                    });
                }

                window.bindQuickDatePresets({
                    groupId: "datePresetGroup",
                    fromId: "fromDate",
                    toId: "toDate",
                    onChange: function() {
                        var activePresetBtn = document.querySelector(
                            "#datePresetGroup .quick-date-chip.active");
                        activeDatePreset = activePresetBtn ? activePresetBtn.getAttribute(
                            "data-range") : "";

                        renderFilterChips();
                        updateShowResultsButton();
                    }
                });

                function anyChecked(list) {
                    return list.some(function(i) {
                        return i.checked;
                    });
                }

                function setDisabled(list, d) {
                    list.forEach(function(i) {
                        i.disabled = d;
                        var label = i.closest("label");
                        if (label) {
                            label.classList.toggle("opacity-50", d);
                            label.classList.toggle("cursor-not-allowed", d);
                        }
                    });
                }

                function clearChecked(list) {
                    list.forEach(function(i) {
                        i.checked = false;
                    });
                }

                function ilike(val, t) {
                    return (val || "").toLowerCase().includes((t || "").toLowerCase());
                }

                function syncMutualExclusion() {
                    if (anyChecked(deptRadios)) {
                        clearChecked(otherRadios);
                        setDisabled(otherRadios, true);
                        setDisabled(deptRadios, false);
                        return;
                    }
                    if (anyChecked(otherRadios)) {
                        clearChecked(deptRadios);
                        setDisabled(deptRadios, true);
                        setDisabled(otherRadios, false);
                        return;
                    }
                    setDisabled(deptRadios, false);
                    setDisabled(otherRadios, false);
                }
                deptRadios.concat(otherRadios).forEach(function(r) {
                    r.addEventListener("change", syncMutualExclusion);
                });

                function getInfo(p) {
                    var infoEl = p.querySelector(".patient-info");
                    var parts = ((infoEl ? infoEl.textContent.trim() : "") || "").split("|").map(function(s) {
                        return s.trim();
                    });
                    return {
                        program: parts[0] || "",
                        year: parts[1] || "",
                        section: parts[2] || "",
                        dateStr: parts[3] || "",
                        department: parts[4] || p.getAttribute("data-department") || "",
                        createdAt: parts[5] || ""
                    };
                }

                function compareNearestAppointments(firstPatient, secondPatient) {
                    var aStatus = firstPatient.getAttribute('data-status') || '';
                    var bStatus = secondPatient.getAttribute('data-status') || '';

                    var activeStatuses = ['upcoming', 'rescheduled', 'pending', 'confirmed'];
                    var aActive = activeStatuses.includes(aStatus);
                    var bActive = activeStatuses.includes(bStatus);

                    if (aActive !== bActive) {
                        return aActive ? -1 : 1;
                    }

                    var aDate = firstPatient.getAttribute('data-date') || '1970-01-01';
                    var aTime = firstPatient.getAttribute('data-time') || '00:00:00';
                    var aTimestamp = new Date(aDate + 'T' + aTime).getTime();
                    if (isNaN(aTimestamp)) aTimestamp = 0;

                    var bDate = secondPatient.getAttribute('data-date') || '1970-01-01';
                    var bTime = secondPatient.getAttribute('data-time') || '00:00:00';
                    var bTimestamp = new Date(bDate + 'T' + bTime).getTime();
                    if (isNaN(bTimestamp)) bTimestamp = 0;

                    if (aActive && bActive) {
                        return aTimestamp - bTimestamp;
                    }
                    return bTimestamp - aTimestamp;
                }

                function getName(patient) {
                    var element = patient.querySelector(
                        ".patient-list-name, .patient-grid-name"
                    );

                    return element ?
                        element.textContent.trim() :
                        "";
                }

                function getService(patient) {
                    var serviceBlock = patient.querySelector(
                        ".patient-service-block"
                    );

                    if (!serviceBlock) {
                        var gridItems = patient.querySelectorAll(
                            ".global-info-item"
                        );

                        for (var index = 0; index < gridItems.length; index++) {
                            var label = gridItems[index].querySelector(
                                ".global-info-label"
                            );

                            if (
                                label &&
                                label.textContent.trim().toLowerCase() ===
                                "service"
                            ) {
                                return gridItems[index]
                                    .querySelector("strong")
                                    ?.textContent
                                    ?.trim() || "";
                            }
                        }

                        return "";
                    }

                    var serviceName =
                        serviceBlock.querySelector(".font-semibold");

                    return serviceName ?
                        serviceName.textContent.trim() :
                        "";
                }

                function getIdText(patient) {
                    var element = patient.querySelector(
                        ".global-info-pill"
                    );

                    return element ?
                        element.textContent.trim() :
                        "";
                }

                function matchesSearch(patient, keyword) {
                    if (!keyword) return true;

                    var info = getInfo(patient);

                    var searchableText = [
                            getName(patient),
                            getService(patient),
                            getIdText(patient),
                            info.program,
                            info.year,
                            info.section,
                            info.department,
                            info.dateStr
                        ]
                        .join(" ")
                        .toLowerCase();

                    return searchableText.includes(
                        keyword.toLowerCase()
                    );
                }

                function updateFilterButtonState() {
                    var count = 0;

                    if (document.querySelector('input[name="course"]:checked')) count++;
                    if (document.querySelector('input[name="year"]:checked')) count++;
                    if (document.querySelector('input[name="section"]:checked')) count++;
                    if (document.querySelector('input[name="department"]:checked')) count++;

                    if (activeFromDate || activeToDate || activeDatePreset) count++;

                    var sortActive = document.querySelector('#fSortGroup .ftag.ftag-active');
                    if (sortActive && sortActive.getAttribute("data-val") !== "nearest") count++;

                    var has = count > 0;

                    if (filterBtn) {
                        filterBtn.classList.toggle("has-filters", has);
                    }

                    if (filterBadge) {
                        if (has) {
                            filterBadge.classList.remove("hidden");
                            filterBadge.style.display = "inline-flex";
                            filterBadge.textContent = count;
                        } else {
                            filterBadge.classList.add("hidden");
                            filterBadge.style.display = "none";
                            filterBadge.textContent = "";
                        }
                    }

                    if (externalClearFilterBtn) {
                        if (has) {
                            externalClearFilterBtn.classList.remove('hidden');
                        } else {
                            externalClearFilterBtn.classList.add('hidden');
                        }
                    }
                }

                if (externalClearFilterBtn) {
                    externalClearFilterBtn.onclick = function() {
                        resetAllFilters();
                    };
                }

                window.bindFilterTagGroup({
                    groupId: "fSortGroup",
                    onChange: function() {
                        renderFilterChips();
                        updateShowResultsButton();
                    }
                });

                if (applyFiltersBtn) {
                    applyFiltersBtn.onclick = function() {
                        var draft = getDraftFilterState();

                        selectedDepartment = draft.department;
                        selectedProgram = draft.program;
                        selectedYearLevel = draft.year;
                        selectedSection = draft.section;
                        activeFromDate = draft.fromDate;
                        activeToDate = draft.toDate;

                        var activePresetBtn = document.querySelector(
                            '#datePresetGroup .quick-date-chip.active');
                        activeDatePreset = activePresetBtn ? activePresetBtn.getAttribute("data-range") : "";

                        var sortActive = document.querySelector('#fSortGroup .ftag.ftag-active');
                        var sortVal = sortActive ?
                            sortActive.getAttribute("data-val") :
                            "nearest";

                        if (sortVal === "az") {
                            nameSort = "az";
                            dateSort = null;
                        } else if (sortVal === "za") {
                            nameSort = "za";
                            dateSort = null;
                        } else if (sortVal === "nearest") {
                            dateSort = "nearest";
                            nameSort = null;
                        } else if (sortVal === "farthest") {
                            dateSort = "farthest";
                            nameSort = null;
                        }

                        selectPatientStatus("all");
                        if (filterModal) closeFilterModal();

                        syncMutualExclusion();
                        applyFilters();
                        updateFilterButtonState();
                    };
                }

                if (clearFiltersModalBtn) {
                    clearFiltersModalBtn.onclick = function() {
                        resetPatientPanelFilters();
                        renderFilterChips();
                        updateShowResultsButton();
                    };
                }

                var pageInfoTop =
                    document.getElementById(
                        "patientPageInfoTop"
                    );

                var pageInfoBottom =
                    document.getElementById(
                        "patientPageInfoBottom"
                    );

                var paginationTop =
                    document.getElementById(
                        "patientPaginationTop"
                    );

                var paginationBottom =
                    document.getElementById(
                        "patientPaginationBottom"
                    );

                var perPageInput =
                    document.getElementById(
                        "patientPerPage"
                    );

                var PER_PAGE =
                    Number(
                        perPageInput?.value || 10
                    );

                var currentPage = 1;

                var currentItems = allPatients.slice();

                function renderPatientPagebars() {
                    const totalItems =
                        currentItems.length;

                    const lastPage =
                        Math.max(
                            1,
                            Math.ceil(
                                totalItems /
                                PER_PAGE
                            )
                        );

                    currentPage =
                        Math.min(
                            Math.max(
                                1,
                                currentPage
                            ),
                            lastPage
                        );

                    const from =
                        totalItems > 0 ?
                        (
                            (
                                currentPage - 1
                            ) * PER_PAGE
                        ) + 1 :
                        null;

                    const to =
                        totalItems > 0 ?
                        Math.min(
                            currentPage *
                            PER_PAGE,
                            totalItems
                        ) :
                        null;

                    window.renderGlobalPagination?.({
                        currentPage,

                        lastPage,

                        total: totalItems,

                        from,

                        to,

                        containers: [
                            document.getElementById(
                                'patientPaginationTop'
                            ),
                            document.getElementById(
                                'patientPaginationBottom'
                            ),
                        ],

                        bars: [
                            document.getElementById(
                                'patientPaginationTopBar'
                            ),
                            document.getElementById(
                                'patientPaginationBottomBar'
                            ),
                        ],

                        infoElements: [
                            document.getElementById(
                                'patientPageInfoTop'
                            ),
                            document.getElementById(
                                'patientPageInfoBottom'
                            ),
                        ],

                        itemLabel: totalItems === 1 ?
                            'patient' : 'patients',

                        onPageChange(page) {
                            currentPage = page;

                            updatePage();

                            document
                                .querySelector(
                                    '.patient-table-card'
                                )
                                ?.scrollIntoView({
                                    behavior: 'smooth',

                                    block: 'start',
                                });
                        },
                    });

                    const perPageInput =
                        document.getElementById(
                            'patientPerPage'
                        );

                    if (perPageInput) {
                        perPageInput.value =
                            String(PER_PAGE);

                        window
                            .syncGlobalPageSizeSelect?.(
                                perPageInput,
                                PER_PAGE
                            );
                    }
                }

                function getCurrentPatientStatus() {
                    return activeTab || 'all';
                }

                function getPatientStatusEmptyMeta(status) {
                    var map = {
                        today: {
                            icon: "fa-clock",

                            title: "No patients today",

                            text: "There are currently no patient appointments scheduled for today."
                        },

                        upcoming: {
                            icon: "fa-calendar-check",

                            title: "No upcoming patients",

                            text: "There are currently no upcoming patient appointments."
                        },

                        rescheduled: {
                            icon: "fa-rotate-right",

                            title: "No rescheduled patients",

                            text: "There are currently no rescheduled patient appointments."
                        },

                        completed: {
                            icon: "fa-circle-check",

                            title: "No completed patients",

                            text: "Completed patient appointments will appear here."
                        },

                        cancelled: {
                            icon: "fa-calendar-xmark",

                            title: "No cancelled patients",

                            text: "Cancelled patient appointments will appear here."
                        },

                        all: {
                            icon: "fa-sliders",

                            title: "No patients match your filters",

                            text: "Try removing or changing the selected filter criteria."
                        }
                    };

                    return map[status] || map.all;
                }

                function updateFilteredEmptyState() {
                    var hasResults =
                        currentItems.length > 0;

                    var hasSearch =
                        searchKeyword
                        .trim()
                        .length > 0;

                    var hasAdvancedFilters =
                        Boolean(
                            selectedProgram ||
                            selectedYearLevel ||
                            selectedSection ||
                            selectedDepartment ||
                            activeFromDate ||
                            activeToDate ||
                            activeDatePreset ||
                            nameSort ||
                            dateSort !== 'nearest'
                        );

                    var currentStatus =
                        getCurrentPatientStatus();

                    window.EmptyState?.hide(
                        patientBaseEmptyState
                    );

                    window.EmptyState?.hide(
                        patientSearchEmptyState
                    );

                    window.EmptyState?.hide(
                        patientStatusEmptyState
                    );

                    if (hasResults) {
                        return;
                    }

                    if (allPatients.length === 0) {
                        window.EmptyState?.render({
                            host: patientBaseEmptyState,

                            icon: 'fa-tooth',

                            title: 'No patients found',

                            message: 'There are no patient appointments in the system yet.',
                        });

                        return;
                    }

                    if (hasSearch) {
                        window.EmptyState?.renderSearch({
                            host: patientSearchEmptyState,

                            input: searchInput,

                            query: searchKeyword,

                            message: 'Try a different patient name, student number, program, or service.',
                        });

                        return;
                    }

                    if (
                        currentStatus === 'all' &&
                        !hasAdvancedFilters
                    ) {
                        return;
                    }

                    var meta =
                        getPatientStatusEmptyMeta(
                            hasAdvancedFilters ?
                            'all' :
                            currentStatus
                        );

                    window.EmptyState?.render({
                        host: patientStatusEmptyState,

                        icon: meta.icon,

                        title: meta.title,

                        message: meta.text,

                        actionHtml: hasAdvancedFilters ?
                            `
                    <button
                        type="button"
                        class="empty-state-btn"
                        data-patient-clear-filters
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear filters
                    </button>
                ` : '',
                    });

                    document
                        .querySelector(
                            '#patientStatusEmptyState [data-patient-clear-filters]'
                        )
                        ?.addEventListener(
                            'click',
                            function() {
                                resetPatientPanelFilters();
                            }
                        );
                }

                function updatePage() {
                    var totalItems = currentItems.length;
                    var startIndex = (currentPage - 1) * PER_PAGE;
                    var endIndex = startIndex + PER_PAGE;

                    allPatients.forEach(function(patient) {
                        patient.classList.add("hidden");
                    });

                    currentItems
                        .slice(startIndex, endIndex)
                        .forEach(function(patient) {
                            patient.classList.remove("hidden");
                        });

                    var hasVisiblePatients = currentItems.length > 0;

                    if (colHeader) {
                        colHeader.classList.toggle(
                            "hidden",
                            !hasVisiblePatients
                        );
                    }

                    updateFilteredEmptyState();
                    renderPatientPagebars();
                }

                window.changePatientPageSize = function(value) {
                    var nextSize = Number(value);

                    if (!Number.isFinite(nextSize) || nextSize <= 0) {
                        nextSize = 10;
                    }

                    PER_PAGE = nextSize;
                    currentPage = 1;

                    updatePage();
                };

                function applyFilters(
                    options = {}
                ) {
                    var showLoading =
                        options.showLoading !== false;

                    var delay =
                        Number.isFinite(options.delay) ?
                        options.delay :
                        300;

                    if (showLoading) {
                        showPatientSkeleton();
                    }

                    window.clearTimeout(
                        window.patientDirectoryFilterTimer
                    );

                    function runFiltering() {
                        try {
                            var data =
                                allPatients.slice();

                            if (
                                activeTab !== "all"
                            ) {
                                data =
                                    data.filter(
                                        function(patient) {
                                            return patient
                                                .classList
                                                .contains(
                                                    activeTab
                                                );
                                        }
                                    );
                            }

                            if (searchKeyword) {
                                data =
                                    data.filter(
                                        function(patient) {
                                            return matchesSearch(
                                                patient,
                                                searchKeyword
                                            );
                                        }
                                    );
                            }

                            if (selectedProgram) {
                                data =
                                    data.filter(
                                        function(patient) {
                                            return ilike(
                                                getInfo(
                                                    patient
                                                ).program,
                                                selectedProgram
                                            );
                                        }
                                    );
                            }

                            if (
                                selectedYearLevel ||
                                selectedSection
                            ) {
                                data =
                                    data.filter(
                                        function(patient) {
                                            var info =
                                                getInfo(
                                                    patient
                                                );

                                            if (
                                                selectedYearLevel &&
                                                !ilike(
                                                    info.year,
                                                    selectedYearLevel
                                                )
                                            ) {
                                                return false;
                                            }

                                            if (
                                                selectedSection &&
                                                String(
                                                    info.section
                                                ).trim() !==
                                                String(
                                                    selectedSection
                                                ).trim()
                                            ) {
                                                return false;
                                            }

                                            return true;
                                        }
                                    );
                            }

                            if (selectedDepartment) {
                                data =
                                    data.filter(
                                        function(patient) {
                                            return ilike(
                                                getInfo(
                                                    patient
                                                ).department,
                                                selectedDepartment
                                            );
                                        }
                                    );
                            }

                            if (
                                activeFromDate ||
                                activeToDate
                            ) {
                                data =
                                    data.filter(
                                        function(patient) {
                                            var date =
                                                new Date(
                                                    getInfo(
                                                        patient
                                                    ).dateStr
                                                );

                                            if (
                                                isNaN(
                                                    date.getTime()
                                                )
                                            ) {
                                                return false;
                                            }

                                            if (
                                                activeFromDate &&
                                                date <
                                                new Date(
                                                    activeFromDate
                                                )
                                            ) {
                                                return false;
                                            }

                                            if (
                                                activeToDate &&
                                                date >
                                                new Date(
                                                    activeToDate
                                                )
                                            ) {
                                                return false;
                                            }

                                            return true;
                                        }
                                    );
                            }

                            if (
                                nameSort === "az"
                            ) {
                                data.sort(
                                    function(a, b) {
                                        return getName(a)
                                            .localeCompare(
                                                getName(b)
                                            );
                                    }
                                );
                            }

                            if (
                                nameSort === "za"
                            ) {
                                data.sort(
                                    function(a, b) {
                                        return getName(b)
                                            .localeCompare(
                                                getName(a)
                                            );
                                    }
                                );
                            }

                            if (
                                dateSort ===
                                "nearest"
                            ) {
                                data.sort(
                                    compareNearestAppointments
                                );
                            }

                            if (
                                dateSort ===
                                "farthest"
                            ) {
                                data.sort(
                                    function(
                                        firstPatient,
                                        secondPatient
                                    ) {
                                        return compareNearestAppointments(
                                            secondPatient,
                                            firstPatient
                                        );
                                    }
                                );
                            }

                            var rowCountEl =
                                document.getElementById(
                                    "rowCount"
                                );

                            if (rowCountEl) {
                                rowCountEl.textContent =
                                    data.length +
                                    " " +
                                    (
                                        data.length === 1 ?
                                        "patient" :
                                        "patients"
                                    );
                            }

                            currentItems =
                                data;

                            currentPage = 1;

                            updatePage();
                            updateFilterButtonState();

                        } catch (error) {
                            console.error(
                                "Patient list filtering error:",
                                error
                            );
                        } finally {
                            if (showLoading) {
                                hidePatientSkeleton();
                            }
                        }
                    }

                    if (delay > 0) {
                        window.patientDirectoryFilterTimer =
                            window.setTimeout(
                                runFiltering,
                                delay
                            );

                        return;
                    }

                    runFiltering();
                }

                syncMutualExclusion();

                activeTab = 'all';

                window.setGlobalFilterSelectValue?.(
                    'patientStatusFilter',
                    'all', {
                        callback: false,
                        focus: false
                    }
                );

                window.initGlobalPageSizeSelects?.(
                    document
                );

                applyFilters({
                    showLoading: false,
                    delay: 0
                });

            } catch (err) {
                console.error("Initialization Error:", err);
            }
        });
    </script>
@endsection
