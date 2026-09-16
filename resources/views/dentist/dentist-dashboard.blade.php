@extends('layouts.app')

@section('layout-role', 'dentist')

@section('title', 'Dashboard')

@section('usesAppointmentCalendar', true)

@section('styles')
    @vite('resources/css/pages/dentist/dentist-dashboard.css')
@endsection

@section('content')
    @php
        $dentalCasesThisMonth = $dentalCasesThisMonth ?? 0;
        $totalApptsThisMonth = $totalApptsThisMonth ?? 0;

        $dentalCasesDelta = $dentalCasesDelta ?? null;
        $totalApptsDelta = $totalApptsDelta ?? null;

        $appointmentCountsPerDay = $appointmentCountsPerDay ?? [];
        $blockedDates = $blockedDates ?? [];
        $philippineHolidays = $philippineHolidays ?? [];
        $todayAppointments = $todayAppointments ?? collect();

        $medicalSupplies = $medicalSupplies ?? collect();
        $medicineSupplies = $medicineSupplies ?? collect();

        $normalizeCalendarAppointmentStatus = static function ($status): string {
            $value = strtolower(trim((string) ($status ?? '')));

            if (in_array($value, ['pending', 'confirmed', 'upcoming'], true)) {
                return 'upcoming';
            }

            if ($value === 'reschedule' || str_contains($value, 'resched')) {
                return 'rescheduled';
            }

            return $value;
        };

        $calendarAppointmentDetails = collect($calendarAppointmentDetails ?? [])
            ->map(function ($appointments) use ($normalizeCalendarAppointmentStatus) {
                return collect($appointments ?? [])
                    ->filter(function ($appointment) use ($normalizeCalendarAppointmentStatus) {
                        $status = $normalizeCalendarAppointmentStatus(data_get($appointment, 'status'));

                        return in_array($status, ['upcoming', 'rescheduled'], true);
                    })
                    ->values()
                    ->all();
            })
            ->filter(fn($appointments) => count($appointments) > 0)
            ->all();

        $calendarAppointmentCounts = collect($calendarAppointmentDetails)
            ->map(fn($appointments) => count($appointments))
            ->all();
    @endphp

    <main id="mainContent" class="dentist-dashboard-page app-page-shell page-enter mode-list">
        <div class="w-full">

            <x-dashboard-loading-status />

            <div class="greeting-row">
                <div class="greeting-banner">
                    <div class="greeting-banner-inner">
                        <div class="greeting-banner-copy min-w-0">
                            <h1 class="greeting-heading">
                                <span class="greeting-line">
                                    <span id="greetingText">
                                        @php
                                            $hour = now()->hour;
                                        @endphp

                                        @if ($hour < 12)
                                            Good Morning,
                                        @elseif ($hour < 18)
                                            Good Afternoon,
                                        @else
                                            Good Evening,
                                        @endif
                                    </span>
                                </span>
                                <span class="greeting-line greeting-name-line">
                                    @if (auth()->user()?->hasRole('dentist'))
                                        <span class="greeting-name-prefix">Dr.</span>
                                    @endif
                                    <span id="dentistName">
                                        {{ auth()->user()->name ?? 'Doctor' }}
                                    </span>
                                    <i class="fa-solid fa-heart-pulse text-rose-300"></i>
                                </span>
                            </h1>
                            <p id="rotatingSubtitle" class="greeting-subtitle"></p>
                        </div>

                        <div class="greeting-banner-actions">
                            <div class="greeting-clinic-status-group">

                                <div class="greeting-status-meta">
                                    <div class="greeting-status-eyebrow">
                                        <i class="fa-solid fa-circle-plus"></i>
                                        Clinic Status
                                    </div>

                                    <div class="greeting-status-text">
                                        The Dentist is currently
                                    </div>
                                </div>

                                <div class="status-btn-wrap">
                                    <div class="status-icon-badge">
                                        <i class="fa-solid fa-stethoscope"></i>
                                    </div>

                                    <button id="statusBtn" type="button" onclick="openStatusModal()"
                                        class="ui-btn {{ ($clinicStatus ?? 'in') === 'in' ? 'ui-btn-success' : 'ui-btn-danger' }} ui-btn-sm"
                                        data-tooltip="{{ ($clinicStatus ?? 'in') === 'in' ? 'Clinic is open' : 'Clinic is closed' }}"
                                        data-tooltip-tone="{{ ($clinicStatus ?? 'in') === 'in' ? 'start' : 'cancel' }}">
                                        <span id="statusDot"
                                            class="status-dot {{ ($clinicStatus ?? 'in') === 'in' ? 'status-active' : 'status-cancelled' }}"
                                            aria-hidden="true"></span>

                                        <span id="statusLabel">
                                            {{ strtoupper($clinicStatus ?? 'in') }}
                                        </span>
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="statCards" class="stat-grid dashboard-kpi-grid skeleton-section skeleton-fade-swap">
                <div class="skeleton-shell space-y-4">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="rounded-2xl h-32 skeleton-block">
                        </div>
                    @endfor
                </div>
            </div>

            <div class="row2-grid">
                <div class="min-w-0">
                    <div id="dentistCalendarContainer"
                        class="w-full h-full min-h-[420px] skeleton-section skeleton-fade-swap">
                        <div class="cal-shell skeleton-shell p-5 sm:p-6 h-full border-none">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="w-8 h-8 rounded-full skeleton-block"></div>
                                    <div class="text-center space-y-2">
                                        <div class="h-5 w-28 skeleton-block rounded mx-auto"></div>
                                        <div class="h-3 w-16 skeleton-line rounded mx-auto"></div>
                                    </div>
                                    <div class="w-8 h-8 rounded-full skeleton-block"></div>
                                </div>

                                <div class="border-t border-gray-100 mb-3"></div>

                                <div class="grid grid-cols-7 gap-0.5 mb-2">
                                    @for ($i = 0; $i < 7; $i++)
                                        <div class="h-4 skeleton-line rounded mx-2">
                                        </div>
                                    @endfor
                                </div>

                                <div class="grid grid-cols-7 gap-1">
                                    @for ($i = 0; $i < 35; $i++)
                                        <div class="flex items-center justify-center py-1.5">
                                            <div class="w-9 h-9 rounded-xl skeleton-line"></div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-side-stack min-w-0">
                    <div id="upcomingAppointmentsContainer" class="skeleton-section skeleton-fade-swap h-full">
                        <div class="skeleton-shell rounded-xl p-4 h-full flex flex-col">
                            <div class="space-y-3 flex flex-col h-full">

                                <div class="h-5 w-40 skeleton-block rounded"></div>

                                <div class="flex gap-2 justify-center">
                                    @for ($i = 0; $i < 5; $i++)
                                        <div class="h-14 w-14 skeleton-line rounded-xl">
                                        </div>
                                    @endfor
                                </div>

                                <div class="flex-1 space-y-2 mt-2">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="h-14 skeleton-block rounded-xl">
                                        </div>
                                    @endfor
                                </div>

                                <div class="h-6 w-40 mx-auto skeleton-line rounded"></div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row3-grid">

            <div class="dashboard-lower-stack">

                <div id="gadAnalyticsContainer" class="skeleton-section skeleton-fade-swap">
                    <div class="skeleton-shell p-5 rounded-3xl shadow-sm flex flex-col">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between mb-4">
                                <div class="space-y-2 flex-1">
                                    <div class="h-5 w-32 skeleton-block rounded"></div>
                                    <div class="h-4 w-24 skeleton-line rounded"></div>
                                    <div class="h-3 w-40 skeleton-line rounded"></div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="h-6 w-24 skeleton-block rounded"></div>
                                    <div class="h-6 w-24 skeleton-block rounded"></div>
                                </div>
                            </div>
                            <div class="h-48 skeleton-block rounded"></div>
                        </div>
                    </div>
                </div>

                <div id="todayTreatmentProgressContainer" class="skeleton-section skeleton-fade-swap">
                    <div class="skeleton-shell p-5 rounded-3xl">
                        <div class="space-y-4">

                            <div class="h-5 w-44 skeleton-block rounded"></div>
                            <div class="h-3 w-56 skeleton-line rounded"></div>

                            <div class="grid grid-cols-2 gap-3">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="h-16 skeleton-block rounded-xl"></div>
                                @endfor
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="inventory-grid">
                <div id="medicalSuppliesContainer" class="skeleton-section skeleton-fade-swap">
                    <div class="skeleton-shell p-5 rounded-3xl shadow-sm flex flex-col">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="space-y-2 flex-1">
                                    <div class="h-5 w-32 skeleton-block rounded"></div>
                                    <div class="h-3 w-24 skeleton-line rounded"></div>
                                </div>
                                <div class="h-6 w-20 skeleton-block rounded"></div>
                            </div>
                            @for ($i = 0; $i < 3; $i++)
                                <div class="h-10 skeleton-block rounded">
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <div id="medicineSuppliesContainer" class="skeleton-section skeleton-fade-swap">
                    <div class="skeleton-shell p-5 rounded-3xl shadow-sm flex flex-col">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="space-y-2 flex-1">
                                    <div class="h-5 w-32 skeleton-block rounded"></div>
                                    <div class="h-3 w-24 skeleton-line rounded"></div>
                                </div>
                                <div class="h-6 w-20 skeleton-block rounded"></div>
                            </div>
                            @for ($i = 0; $i < 3; $i++)
                                <div class="h-10 skeleton-block rounded">
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <div id="statusModal" class="ui-modal modal-theme-danger" aria-hidden="true">
        <div class="ui-modal-card modal-sm" role="dialog" aria-modal="true" aria-labelledby="statusModalTitle"
            onclick="event.stopPropagation()">

            <div class="modal-hd">

                <div class="modal-heading">

                    <span id="statusModalIcon" class="modal-icon">
                        <i class="fa-solid fa-door-closed"></i>
                    </span>

                    <div class="modal-copy">

                        <h2 id="statusModalTitle" class="modal-title">
                            Close the Clinic
                        </h2>

                        <p id="statusModalSubtitle" class="modal-subtitle">
                            Change your current clinic availability.
                        </p>

                    </div>

                </div>

            </div>

            <div class="modal-bd">

                <div id="statusModalAlert" class="global-confirm-alert">
                    <i id="statusModalAlertIcon" class="fa-solid fa-circle-exclamation"></i>

                    <div>
                        <strong id="statusModalAlertTitle">
                            Mark clinic as OUT?
                        </strong>

                        <span id="statusModalBody">
                            Patients will not be able to book new appointments while the clinic is closed.
                            Existing appointments will remain active until they are completed or manually cancelled.
                            You can reopen the clinic at any time.
                        </span>
                    </div>
                </div>

            </div>

            <div class="modal-ft">

                <button type="button" class="ui-btn ui-btn-secondary" onclick="closeStatusModal()">
                    Cancel
                </button>

                <button id="confirmStatusBtn" type="button" class="ui-btn ui-btn-warning" onclick="confirmStatus()">
                    <i class="fa-solid fa-check"></i>
                    Confirm
                </button>

            </div>

        </div>
    </div>

    <div id="dayAppointmentsModal" class="ui-modal modal-theme-primary" aria-hidden="true">
        <div class="ui-modal-card modal-md" role="dialog" aria-modal="true" aria-labelledby="dayAppointmentsModalTitle"
            onclick="event.stopPropagation()">

            <div class="modal-hd">

                <div class="modal-heading">

                    <span class="modal-icon">
                        <i class="fa-solid fa-users"></i>
                    </span>

                    <div class="modal-copy">
                        <h2 id="dayAppointmentsModalTitle" class="modal-title">
                            Scheduled Patients
                        </h2>

                        <p id="dayAppointmentsModalDate" class="modal-subtitle"></p>
                    </div>

                </div>

                <button type="button" class="modal-x" onclick="closeDayAppointmentsModal()"
                    aria-label="Close scheduled patients" data-tooltip="Close" data-tooltip-tone="neutral">
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

            <div class="modal-bd">

                <div id="dayAppointmentsModalList" class="scheduled-modal-list"></div>

            </div>

            <div class="modal-ft">

                <button type="button" class="ui-btn ui-btn-secondary ui-btn-sm" onclick="closeDayAppointmentsModal()">
                    Close
                </button>

            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function renderDashboardContent(
            targetId,
            html
        ) {
            const container =
                document.getElementById(
                    targetId
                );

            if (!container) {
                return;
            }

            container.innerHTML =
                html;

            container.classList.remove(
                'skeleton-section',
                'skeleton-fade-swap',
                'skeleton-fade-leave',
                'content-reveal'
            );
        }

        function normalizeDashboardAppointmentStatus(
            status = ''
        ) {
            const value =
                String(status || '')
                .toLowerCase()
                .trim();

            if (
                [
                    'pending',
                    'confirmed',
                    'upcoming'
                ].includes(value)
            ) {
                return 'upcoming';
            }

            if (
                value === 'reschedule' ||
                value.includes('resched')
            ) {
                return 'rescheduled';
            }

            if (
                value === 'completed' ||
                value.includes('complete')
            ) {
                return 'completed';
            }

            if (
                value === 'canceled' ||
                value.includes('cancel')
            ) {
                return 'cancelled';
            }

            return value || 'upcoming';
        }

        function buildAppointmentStatus(appt) {
            const status =
                normalizeDashboardAppointmentStatus(
                    appt?.status
                );

            const statuses = {
                upcoming: {
                    label: 'Upcoming',
                    className: 'status-upcoming'
                },

                rescheduled: {
                    label: 'Rescheduled',
                    className: 'status-rescheduled'
                },

                completed: {
                    label: 'Completed',
                    className: 'status-completed'
                },

                cancelled: {
                    label: 'Cancelled',
                    className: 'status-cancelled'
                }
            };

            const meta =
                statuses[status] ||
                statuses.upcoming;

            return `
        <span
            class="status-pill ${meta.className}"
        >
            <span class="status-dot"></span>
            ${meta.label}
        </span>
    `;
        }

        function buildAppointmentTypeIcons(
            appt
        ) {
            let html = '';

            if (appt?.is_walk_in) {
                html += `
            <span
                class="appt-type-icon"
                data-tooltip="Walk-in appointment"
                data-tooltip-tone="neutral"
                aria-label="Walk-in appointment"
                tabindex="0"
            >
                <i class="fa-solid fa-person-walking"></i>
            </span>
        `;
            }

            if (appt?.is_follow_up) {
                html += `
            <span
                class="appt-type-icon"
                data-tooltip="Follow-up appointment"
                data-tooltip-tone="neutral"
                aria-label="Follow-up appointment"
                tabindex="0"
            >
                <i class="fa-solid fa-calendar-plus"></i>
            </span>
        `;
            }

            return html;
        }

        function buildUpcomingAppointments() {

            const rawApptDetails =
                dashboardData.dashboardAppointmentDetails || {};

            const apptDetails =
                Object.fromEntries(
                    Object.entries(
                        rawApptDetails
                    ).map(
                        ([date, appointments]) => [
                            date,

                            (
                                Array.isArray(
                                    appointments
                                ) ?
                                appointments : []
                            ).filter(
                                appointment => {
                                    const status =
                                        normalizeDashboardAppointmentStatus(
                                            appointment?.status
                                        );

                                    return [
                                        'upcoming',
                                        'rescheduled'
                                    ].includes(
                                        status
                                    );
                                }
                            ),
                        ]
                    )
                );

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const pad = n =>
                String(n).padStart(2, '0');

            const dateKey = date =>
                `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

            let windowStart = new Date(today);
            let selectedKey = dateKey(today);

            let upcomingWindowAnimating = false;

            const UPCOMING_CAROUSEL_OUT_MS = 180;
            const UPCOMING_CAROUSEL_IN_MS = 280;

            function getVisibleDays() {
                return Array.from({
                        length: 7
                    },
                    (_, index) => {
                        const date =
                            new Date(windowStart);

                        date.setDate(
                            windowStart.getDate() +
                            index
                        );

                        return date;
                    }
                );
            }

            function buildAvatar(appt, name) {
                const photo =
                    appt.patientPhotoUrl ||
                    appt.patient_photo_url ||
                    appt.profile_photo_url ||
                    appt.avatar ||
                    '';

                return `
<span
class="
patient-avatar
patient-avatar-sm
"
data-patient-avatar
data-patient-name="${escHtml(name)}"
data-patient-url="${escHtml(photo)}"
></span>
`;
            }

            function initUpcomingAvatars(root) {
                window.PatientUI
                    ?.initAvatars
                    ?.(root);
            }

            function clearUpcomingCarouselClasses(element) {
                if (!element) return;

                element.classList.remove(
                    'upcoming-carousel-out-left',
                    'upcoming-carousel-out-right',
                    'upcoming-carousel-in-left',
                    'upcoming-carousel-in-right'
                );
            }

            function runUpcomingCarousel(
                direction,
                elements,
                updateContent
            ) {
                const targets =
                    elements.filter(Boolean);

                const reducedMotion =
                    window.matchMedia(
                        '(prefers-reduced-motion: reduce)'
                    ).matches;

                if (
                    !direction ||
                    !targets.length ||
                    reducedMotion
                ) {
                    updateContent();
                    return;
                }

                upcomingWindowAnimating = true;

                const outClass =
                    direction > 0 ?
                    'upcoming-carousel-out-left' :
                    'upcoming-carousel-out-right';

                const inClass =
                    direction > 0 ?
                    'upcoming-carousel-in-right' :
                    'upcoming-carousel-in-left';

                targets.forEach(element => {
                    clearUpcomingCarouselClasses(
                        element
                    );

                    element.classList.add(
                        outClass
                    );
                });

                window.setTimeout(() => {
                    targets.forEach(element => {
                        element.classList.remove(
                            outClass
                        );
                    });

                    updateContent();

                    requestAnimationFrame(() => {
                        targets.forEach(element => {
                            clearUpcomingCarouselClasses(
                                element
                            );

                            element.classList.add(
                                inClass
                            );
                        });

                        window.setTimeout(() => {
                            targets.forEach(element => {
                                element.classList.remove(
                                    inClass
                                );
                            });

                            upcomingWindowAnimating =
                                false;
                        }, UPCOMING_CAROUSEL_IN_MS);
                    });
                }, UPCOMING_CAROUSEL_OUT_MS);
            }

            function render(
                selectedDateKey = selectedKey,
                carouselDirection = 0
            ) {
                const days = getVisibleDays();

                const visibleKeys =
                    days.map(dateKey);

                if (
                    !visibleKeys.includes(
                        selectedDateKey
                    )
                ) {
                    selectedDateKey =
                        visibleKeys[0];
                }

                selectedKey =
                    selectedDateKey;

                const appointments =
                    apptDetails[selectedKey] || [];

                const dateButtons =
                    days
                    .map(d => {
                        const key =
                            dateKey(d);

                        const active =
                            key === selectedKey;

                        const count =
                            (
                                apptDetails[key] || []
                            ).length;

                        const hasAppointments =
                            count > 0;

                        const isToday =
                            key === dateKey(today);

                        return `
<button
type="button"
onclick="renderUpcomingAppointmentsDay('${key}')"
class="
upcoming-date-btn
${active ? 'active status-today' : ''}
${hasAppointments ? 'has-appointments' : ''}
${isToday ? 'is-today' : ''}
"
>
${
hasAppointments
? `
                                                                                                                                                                                                                                                                                <span class="upcoming-date-badge">
                                                                                                                                                                                                                                                                                ${count}
                                                                                                                                                                                                                                                                                </span>
                                                                                                                                                                                                                                                                                `
: ''
}

<div class="text-sm font-extrabold">
${d.getDate()}
</div>

<div class="text-[10px] font-semibold opacity-70">
${d.toLocaleDateString(
'en-US',
{
weekday: 'short'
}
)}
</div>
</button>
`;
                    })
                    .join('');

                const items =
                    appointments.length ?
                    appointments
                    .slice(0, 3)
                    .map(appt => {
                        const name =
                            appt.name ||
                            'Unknown Patient';

                        return `
<a
href="${
    appt.patientProfileUrl ||
    '{{ route('dentist.dentist.appointments') }}'
}"
class="upcoming-item"
>

${buildAvatar(
    appt,
    name
)}

<div class="flex-1 min-w-0">

    <div class="flex items-center justify-between gap-2">

        <div class="flex flex-nowrap items-center gap-2 min-w-0">

            <p class="upcoming-item-name min-w-0">
                ${escHtml(name)}
            </p>

            ${buildAppointmentTypeIcons(appt)}

            ${appt.is_reserved ? `
                    <span class="status-pill status-today shrink-0"
                        data-tooltip="${escHtml(
                            appt.reserved_title
                                ? `Reserved: ${appt.reserved_title}`
                                : 'Reserved appointment'
                        )}"
                        data-tooltip-tone="neutral"
                        aria-label="Reserved appointment"
                        tabindex="0">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Reserved</span>
                    </span>
                ` : ''}

        </div>

        <span class="upcoming-item-time">
            ${escHtml(
                appt.time ||
                '—'
            )}
        </span>

    </div>

    <div class="flex items-center justify-between gap-2 mt-1">

        <p class="upcoming-item-service">
            ${escHtml(
                appt.service ||
                'General Service'
            )}
        </p>

        ${buildAppointmentStatus(appt)}

    </div>

</div>

</a>
`;
                    })
                    .join('') :
                    (
                        window.EmptyState
                        ?.buildHtml ?
                        window.EmptyState
                        .buildHtml({
                            title: 'No appointments for this day',

                            message: 'Scheduled appointments for this date will appear here.',

                            icon: 'fa-calendar-xmark',

                            className: 'empty-state-compact upcoming-empty-state'
                        }) :
                        ''
                    );

                const firstVisibleDate =
                    days[0];

                const lastVisibleDate =
                    days[
                        days.length - 1
                    ];

                const rangeLabel =
                    `${firstVisibleDate.toLocaleDateString(
'en-US',
{
month: 'short',
day: 'numeric'
}
)} – ${lastVisibleDate.toLocaleDateString(
'en-US',
{
month: 'short',
day: 'numeric'
}
)}`;

                const container =
                    document.getElementById(
                        'upcomingAppointmentsContainer'
                    );

                if (!container) {
                    return;
                }

                if (
                    !container.dataset.loaded
                ) {
                    const html = `
<article class="card upcoming-card">

<header class="card-header card-header-inline">

<div class="card-header-left">
<div class="card-header-icon">
<i class="fa-regular fa-calendar-days"></i>
</div>

<div>
<h3 class="card-title">
Upcoming Appointments
</h3>

<p
class="upcoming-range-label"
data-upcoming-range
>
${rangeLabel}
</p>
</div>
</div>

<button
type="button"
class="ui-btn ui-btn-secondary ui-btn-sm"
onclick="goToUpcomingToday()"
data-tooltip="Return to today"
>
<i class="fa-solid fa-calendar-day"></i>
Today
</button>

</header>

<div class="card-body upcoming-card-body">

<div class="upcoming-date-navigation">

<button
type="button"
class="ui-action-btn ui-action-view"
onclick="changeUpcomingAppointmentWindow(-7)"
aria-label="Previous 7 days"
data-tooltip="Previous 7 days"
>
<i class="fa-solid fa-chevron-left"></i>
</button>

<div
class="upcoming-date-strip"
data-upcoming-date-strip
>
${dateButtons}
</div>

<button
type="button"
class="ui-action-btn ui-action-view"
onclick="changeUpcomingAppointmentWindow(7)"
aria-label="Next 7 days"
data-tooltip="Next 7 days"
>
<i class="fa-solid fa-chevron-right"></i>
</button>

</div>

<div
class="
upcoming-list-area
divide-y
divide-gray-100
"
data-upcoming-list
>
${items}
</div>

<a
href="{{ route('dentist.dentist.appointments') }}"
class="card-link upcoming-view-all"
>
View all appointments
<i class="fa-solid fa-arrow-right"></i>
</a>

</div>

</article>
`;

                    renderDashboardContent(
                        'upcomingAppointmentsContainer',
                        html
                    );

                    container.dataset.loaded =
                        'true';

                    setTimeout(
                        () => {
                            initUpcomingAvatars(
                                container
                            );
                        },
                        180
                    );

                    return;
                }

                const rangeElement =
                    container.querySelector(
                        '[data-upcoming-range]'
                    );

                const stripElement =
                    container.querySelector(
                        '[data-upcoming-date-strip]'
                    );

                const listElement =
                    container.querySelector(
                        '[data-upcoming-list]'
                    );

                const updateUpcomingContent = () => {
                    if (rangeElement) {
                        rangeElement.textContent =
                            rangeLabel;
                    }

                    if (stripElement) {
                        stripElement.innerHTML =
                            dateButtons;
                    }

                    if (listElement) {
                        listElement.innerHTML =
                            items;

                        initUpcomingAvatars(
                            listElement
                        );
                    }
                };

                if (carouselDirection !== 0) {
                    runUpcomingCarousel(
                        carouselDirection,
                        [
                            stripElement
                        ],
                        updateUpcomingContent
                    );

                    return;
                }

                updateUpcomingContent();
            }

            window.renderUpcomingAppointmentsDay =
                function(key) {
                    if (upcomingWindowAnimating) {
                        return;
                    }

                    render(
                        key,
                        0
                    );
                };

            window.changeUpcomingAppointmentWindow =
                function(daysToMove) {
                    if (upcomingWindowAnimating) {
                        return;
                    }

                    const direction =
                        daysToMove > 0 ?
                        1 :
                        -1;

                    windowStart.setDate(
                        windowStart.getDate() +
                        daysToMove
                    );

                    selectedKey =
                        dateKey(windowStart);

                    render(
                        selectedKey,
                        direction
                    );
                };

            window.goToUpcomingToday =
                function() {
                    if (upcomingWindowAnimating) {
                        return;
                    }

                    const todayKey =
                        dateKey(today);

                    const currentStart =
                        new Date(windowStart);

                    currentStart.setHours(
                        0,
                        0,
                        0,
                        0
                    );

                    let direction = 0;

                    if (
                        currentStart.getTime() <
                        today.getTime()
                    ) {
                        direction = 1;
                    } else if (
                        currentStart.getTime() >
                        today.getTime()
                    ) {
                        direction = -1;
                    }

                    windowStart =
                        new Date(today);

                    selectedKey =
                        todayKey;

                    render(
                        todayKey,
                        direction
                    );
                };

            render(selectedKey);
        }

        const dashboardData = {
            apptCounts: {!! json_encode($calendarAppointmentCounts) !!},
            apptDetails: {!! json_encode($calendarAppointmentDetails) !!},

            dashboardAppointmentDetails: {!! json_encode($dashboardAppointmentDetails ?? []) !!},

            unavailableDates: {!! json_encode($blockedDates ?? []) !!},
            holidays: {!! json_encode($philippineHolidays ?? []) !!},
        };

        const KPI_DATA = {!! json_encode(
            (object) [
                'dentalCases' => $dentalCasesThisMonth ?? 0,
        
                'dentalCasesDelta' => $dentalCasesDelta,
        
                'totalAppts' => $totalApptsThisMonth ?? 0,
        
                'totalApptsDelta' => $totalApptsDelta,
        
                'todayCount' => $todayAppointments?->whereNotIn('status', ['cancelled'])?->count() ?? 0,
        
                'todayUpcoming' => $todayAppointments?->whereIn('status', ['upcoming', 'rescheduled'])?->count() ?? 0,
        
                'todayCompleted' => $todayAppointments?->where('status', 'completed')?->count() ?? 0,
            ],
        ) !!};

        const TODAY_APPOINTMENTS = {!! json_encode($todayAppointments ?? []) !!};
        const MEDICAL_SUPPLIES = {!! json_encode($medicalSupplies ?? []) !!};
        const MEDICINE_SUPPLIES = {!! json_encode($medicineSupplies ?? []) !!};
        const DASHBOARD_GAD_URL =
            "{{ route('dentist.dentist.report.gad-data') }}";

        function getDashboardCssVariable(name) {
            return getComputedStyle(
                    document.documentElement
                )
                .getPropertyValue(name)
                .trim();
        }

        async function renderGadChart() {
            const container =
                document.getElementById(
                    'gadAnalyticsContainer'
                );

            if (!container) {
                return false;
            }

            const now = new Date();

            const period =
                now.toLocaleDateString(
                    'en-US', {
                        month: 'short',
                        year: 'numeric'
                    }
                );

            try {
                const response =
                    await fetch(
                        `${DASHBOARD_GAD_URL}?period=${encodeURIComponent(period)}`, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        }
                    );

                if (!response.ok) {
                    throw new Error(
                        'Unable to load GAD data.'
                    );
                }

                const data =
                    await response.json();

                const labels =
                    Array.isArray(data.labels) ?
                    data.labels : [];

                const female =
                    Array.isArray(data.female) ?
                    data.female : [];

                const male =
                    Array.isArray(data.male) ?
                    data.male : [];

                const totalFemale =
                    female.reduce(
                        (sum, value) =>
                        sum + Number(value || 0),
                        0
                    );

                const totalMale =
                    male.reduce(
                        (sum, value) =>
                        sum + Number(value || 0),
                        0
                    );

                const totalCases =
                    totalFemale + totalMale;

                const monthLabel =
                    now.toLocaleDateString(
                        'en-US', {
                            month: 'long',
                            year: 'numeric'
                        }
                    );

                const headerHtml = `
<header class="card-header">

    <div class="card-header-left">

        <span class="card-header-icon">
            <i class="fa-solid fa-chart-simple"></i>
        </span>

        <div>
            <h3 class="card-title">
                GAD Analytics
            </h3>

            <p class="card-subtitle">
                Gender-disaggregated clinic records · ${monthLabel}
            </p>
        </div>

    </div>

    <div class="gad-summary">

    <span class="status-pill gad-chip-female">
        Female ${totalFemale}
    </span>

    <span class="status-pill gad-chip-male">
        Male ${totalMale}
    </span>

    <span class="status-pill status-default">
        Total ${totalCases}
    </span>

</div>

</header>
`;

                if (
                    data.empty ||
                    totalCases <= 0
                ) {
                    const emptyStateHtml =
                        window.EmptyState?.buildHtml({
                            title: 'No GAD records found',

                            message: 'No gender-disaggregated clinic records are available for the current month.',

                            icon: 'fa-chart-column',

                            className: 'empty-state-compact'
                        }) || '';

                    const html = `
<article class="card gad-dashboard-card">

    ${headerHtml}

    <div class="card-body gad-dashboard-empty-body">
        ${emptyStateHtml}
    </div>

</article>
`;

                    await swapSkeletonContent(
                        'gadAnalyticsContainer',
                        html
                    );

                    return true;
                }

                const html = `
<article class="card">

    ${headerHtml}

    <div class="card-body">

        <div class="gad-chart-shell">

            <canvas
                id="dashboardGadChart"
                aria-label="Gender-disaggregated clinic records"
            ></canvas>

        </div>

    </div>

</article>
`;

                await swapSkeletonContent(
                    'gadAnalyticsContainer',
                    html
                );

                try {
                    await window.loadChartJs();
                } catch (error) {
                    console.error(
                        'Dashboard GAD: Unable to load Chart.js.',
                        error
                    );

                    return false;
                }

                window.setTimeout(() => {

                    const canvas =
                        document.getElementById(
                            'dashboardGadChart'
                        );

                    if (!canvas) {
                        console.error(
                            'Dashboard GAD: canvas was not found after skeleton swap.'
                        );

                        return;
                    }

                    const existingChart =
                        window.Chart.getChart(
                            canvas
                        );

                    if (existingChart) {
                        existingChart.destroy();
                    }

                    new window.Chart(
                        canvas, {
                            type: 'bar',

                            data: {
                                labels,

                                datasets: [{
                                        label: 'Female',
                                        data: female,
                                        backgroundColor: '#EC4899',
                                        borderColor: '#EC4899',
                                        borderRadius: 4
                                    },
                                    {
                                        label: 'Male',
                                        data: male,
                                        backgroundColor: '#60A5FA',
                                        borderColor: '#60A5FA',
                                        borderRadius: 4
                                    }
                                ]
                            },

                            options: {
                                responsive: true,
                                maintainAspectRatio: false,

                                animation: window.matchMedia(
                                        '(max-width: 767px)'
                                    ).matches ?
                                    false : {
                                        duration: 350
                                    },

                                indexAxis: 'y',

                                plugins: {
                                    legend: {
                                        position: 'top',

                                        labels: {
                                            font: {
                                                
                                                size: 11
                                            },

                                            usePointStyle: true,
                                            boxWidth: 8,

                                            color: getDashboardCssVariable(
                                                '--text-2'
                                            )
                                        }
                                    },

                                    tooltip: window
                                        .getGlobalChartTooltipOptions
                                        ?.({
                                            label(context) {
                                                return `${context.dataset.label}: ${context.parsed.x} cases`;
                                            }
                                        }) || {}
                                },

                                scales: {
                                    x: {
                                        beginAtZero: true,

                                        ticks: {
                                            precision: 0,

                                            color: getDashboardCssVariable(
                                                '--text-2'
                                            ),

                                            font: {
                                                size: 11
                                            }
                                        },

                                        grid: {
                                            borderDash: [4, 4],

                                            color: getDashboardCssVariable(
                                                '--border'
                                            )
                                        },

                                        border: {
                                            display: false
                                        },

                                        title: {
                                            display: true,
                                            text: 'Number of Cases',

                                            color: getDashboardCssVariable(
                                                '--text-2'
                                            ),

                                            font: {                                              
                                                size: 10
                                            }
                                        }
                                    },

                                    y: {
                                        ticks: {
                                            color: getDashboardCssVariable(
                                                '--text-2'
                                            ),

                                            font: {
                                                size: 11
                                            }
                                        },

                                        grid: {
                                            display: false
                                        },

                                        border: {
                                            display: false
                                        }
                                    }
                                }
                            }
                        }
                    );

                }, 200);

                return true;

            } catch (error) {
                console.error(
                    'Dashboard GAD:',
                    error
                );

                const emptyStateHtml =
                    window.EmptyState?.buildHtml({
                        title: 'Unable to load GAD data',

                        message: 'The GAD analytics could not be loaded right now.',

                        icon: 'fa-chart-column',

                        className: 'empty-state-compact'
                    }) || '';

                const html = `
<article class="card gad-dashboard-card">

    <div class="card-body gad-dashboard-empty-body">
        ${emptyStateHtml}
    </div>

</article>
`;

                await swapSkeletonContent(
                    'gadAnalyticsContainer',
                    html
                );

                return false;
            }
        }

        function buildTodayTreatmentProgress() {
            const appointments =
                Array.isArray(TODAY_APPOINTMENTS) ?
                TODAY_APPOINTMENTS : [];

            const normalizeStatus = value =>
                String(value || '')
                .toLowerCase()
                .trim();

            const completed =
                appointments.filter(
                    appointment =>
                    normalizeStatus(
                        appointment.status
                    ) === 'completed'
                ).length;

            const remaining =
                appointments.filter(
                    appointment => [
                        'upcoming',
                        'rescheduled',
                        'pending',
                        'confirmed',
                    ].includes(
                        normalizeStatus(
                            appointment.status
                        )
                    )
                ).length;

            const followUps =
                appointments.filter(
                    appointment =>
                    Boolean(
                        appointment.is_follow_up
                    )
                ).length;

            const walkIns =
                appointments.filter(
                    appointment =>
                    Boolean(
                        appointment.is_walk_in
                    )
                ).length;

            const totalActive =
                completed + remaining;

            const progress =
                totalActive > 0 ?
                Math.round(
                    (
                        completed /
                        totalActive
                    ) * 100
                ) :
                0;

            const html = `
<article class="card today-progress-card">

    <header class="card-header card-header-inline">

        <div class="card-header-left">

            <span class="card-header-icon">
                <i class="fa-solid fa-stethoscope"></i>
            </span>

            <div>
                <h3 class="card-title">
                    Today's Treatment Progress
                </h3>

                <p class="card-subtitle">
                    Current clinic workload and treatment activity
                </p>
            </div>

        </div>

        <span class="status-pill status-default">
            ${progress}% done
        </span>

    </header>

    <div class="card-body today-progress-body">

    <div class="today-progress-grid">

        <div class="today-progress-item today-progress-completed">

    <i
        class="fa-solid fa-circle-check today-progress-bg-icon"
        aria-hidden="true"
    ></i>

    <div class="today-progress-label">
        <span class="today-progress-icon">
            <i class="fa-solid fa-check"></i>
        </span>

        <span>Completed</span>
    </div>

    <strong class="today-progress-value">
        ${completed}
    </strong>

</div>

<div class="today-progress-item today-progress-remaining">

    <i
        class="fa-solid fa-clock today-progress-bg-icon"
        aria-hidden="true"
    ></i>

    <div class="today-progress-label">
        <span class="today-progress-icon">
            <i class="fa-solid fa-clock"></i>
        </span>

        <span>Remaining</span>
    </div>

    <strong class="today-progress-value">
        ${remaining}
    </strong>

</div>

<div class="today-progress-item today-progress-followup">

    <i
        class="fa-solid fa-calendar-plus today-progress-bg-icon"
        aria-hidden="true"
    ></i>

    <div class="today-progress-label">
        <span class="today-progress-icon">
            <i class="fa-solid fa-calendar-plus"></i>
        </span>

        <span>Follow-ups</span>
    </div>

    <strong class="today-progress-value">
        ${followUps}
    </strong>

</div>

<div class="today-progress-item today-progress-walkin">

    <i
        class="fa-solid fa-person-walking today-progress-bg-icon"
        aria-hidden="true"
    ></i>

    <div class="today-progress-label">
        <span class="today-progress-icon">
            <i class="fa-solid fa-person-walking"></i>
        </span>

        <span>Walk-ins</span>
    </div>

    <strong class="today-progress-value">
        ${walkIns}
    </strong>

</div>

    </div>

</div>

</article>
`;

            renderDashboardContent(
                'todayTreatmentProgressContainer',
                html
            );
        }

        function buildKpiGrid() {
            const kpiData = KPI_DATA;

            const clinicStatusLabel =
                dentistIsIn ? 'Open' : 'Closed';

            const clinicStatusIcon =
                dentistIsIn ?
                'fa-door-open' :
                'fa-door-closed';

            const deltaBadge = (value) => {
                if (value === null || typeof value === 'undefined') return '';
                const tone = value >= 0 ? 'status-completed' : 'status-cancelled';
                return `<span class="status-pill ${tone}">${value >= 0 ? '+' : ''}${value}%</span>`;
            };

            const html = `
<article class="stat-card s-crimson" >
<div class="stat-card-info">
<span class="stat-label">Dental Cases</span>
<strong class="stat-num">${kpiData.dentalCases}</strong>
<div class="stat-footer">${deltaBadge(kpiData.dentalCasesDelta)}</div>
</div>
<div class="stat-icon-wrapper"><i class="fa-solid fa-tooth"></i></div>
</article>

<article class="stat-card s-red">
<div class="stat-card-info">
<span class="stat-label">Appointments</span>
<strong class="stat-num">${kpiData.totalAppts}</strong>
<div class="stat-footer">${deltaBadge(kpiData.totalApptsDelta)}</div>
</div>
<div class="stat-icon-wrapper"><i class="fa-regular fa-calendar-check"></i></div>
</article>

<article class="stat-card s-blue">
<div class="stat-card-info">
<span class="stat-label">Today's Patients</span>
<strong class="stat-num">${kpiData.todayCount}</strong>
<div class="stat-footer dashboard-kpi-breakdown">
<span>${kpiData.todayCompleted} completed</span>
<span>${kpiData.todayUpcoming} upcoming</span>
</div>
</div>
<div class="stat-icon-wrapper"><i class="fa-solid fa-user-clock"></i></div>
</article>

<article
    id="clinicStatusStatCard"
    class="stat-card ${dentistIsIn ? 's-active' : 's-cancelled'} dashboard-clinic-status-card">
    <div class="stat-card-info">
<span class="stat-label">Clinic Status</span>
<strong
    id="statusKpiLabel"
    class="stat-num"
>
${clinicStatusLabel}
</strong>
<div class="stat-footer dashboard-live-clock">
<span class="dashboard-live-dot"></span>
<span id="kpi-clock-hhmm">00:00</span>
<span id="kpi-clock-ampm">AM</span>
<span id="kpi-clock-ss" hidden></span>
</div>
</div>
<div class="dashboard-kpi-actions">

    <div class="stat-icon-wrapper">
        <i
            id="statusKpiIcon"
            class="fa-solid ${clinicStatusIcon}"
        ></i>
    </div>

    <button
        type="button"
        onclick="openStatusModal()"
        class="ui-action-btn ui-action-view"
        aria-label="Change clinic status"
        data-tooltip="Change clinic status"
        data-tooltip-tone="view">
        <i class="fa-solid fa-pen-to-square"></i>
    </button>

</div>
</article>
`;

            renderDashboardContent('statCards', html);
        }

        function buildMedicalSupplies() {
            const medicalSupplies = MEDICAL_SUPPLIES;

            let tableHtml = '';
            if (medicalSupplies.length > 0) {
                const rows = medicalSupplies.map(item => {
                    const balance = item.qty - item.used;
                    const pct = item.qty > 0 ? (balance / item.qty) * 100 : 100;
                    const isLow = pct <= 30;

                    const balanceClass = isLow ?
                        'table-tag table-tag-danger' :
                        'table-tag table-tag-info';

                    return `
<tr >
<td class="table-cell-main">
<strong>${escHtml(item.name)}</strong>
</td>
<td class="table-cell-center">${item.qty}</td>
<td class="table-cell-center">${item.used}</td>
<td class="table-cell-center">
<span class="${balanceClass}">
${balance}
${isLow ? '<i class="fa-solid fa-triangle-exclamation"></i>' : ''}
</span>
</td>
</tr>
`;
                }).join('');

                tableHtml = `
<div class="table-body-surface table-scroll inventory-dashboard-table-scroll">
<table class="data-table inventory-dashboard-table">
<thead>
<tr class="table-column-header">
<th>Item</th>
<th class="table-cell-center">Stock</th>
<th class="table-cell-center">Used</th>
<th class="table-cell-center">Balance</th>
</tr>
</thead>
<tbody>
${rows}
</tbody>
</table>
</div>
`;
            } else {
                tableHtml = `
<div class="empty-state inventory-dashboard-empty">
<div class="inventory-empty-icon">
<i class="fa-solid fa-box-open"></i>
</div>

<h4 class="empty-state-title">No medical supplies yet</h4>

<p class="empty-state-sub">
Medical supply records will appear here once inventory items are available.
</p>
</div>
`;
            }

            const html = `
<article class="card inventory-dashboard-card">
<header class="card-header card-header-inline">

    <div class="card-header-left">

        <span class="card-header-icon">
            <i class="fa-solid fa-boxes-stacked"></i>
        </span>

        <div>
            <h3 class="card-title">
                Medical Supplies
            </h3>

            <p class="card-subtitle">
                Top Inventory
            </p>
        </div>

    </div>

    <a
        href="{{ route('dentist.dentist.inventory') }}"
        class="card-link"
    >
        View All
        <i class="fa-solid fa-arrow-right"></i>
    </a>

</header>
<div class="card-body">${tableHtml}</div>
</article>
`;

            renderDashboardContent('medicalSuppliesContainer', html);
        }

        function buildMedicineSupplies() {
            const medicineSupplies = MEDICINE_SUPPLIES;

            let tableHtml = '';
            if (medicineSupplies.length > 0) {
                const rows = medicineSupplies.map(item => {
                    const balance = item.qty - item.used;
                    const pct = item.qty > 0 ? (balance / item.qty) * 100 : 100;
                    const isLow = pct <= 30;

                    const balanceClass = isLow ?
                        'table-tag table-tag-danger' :
                        'table-tag table-tag-info';

                    return `
<tr >
<td class="table-cell-main">
<strong>${escHtml(item.name)}</strong>
</td>
<td class="table-cell-center">${escHtml(item.form || '—')}</td>
<td class="table-cell-center">${item.qty}</td>
<td class="table-cell-center">
<span class="${balanceClass}">
${balance}
${isLow ? '<i class="fa-solid fa-triangle-exclamation"></i>' : ''}
</span>
</td>
</tr>
`;
                }).join('');

                tableHtml = `
<div class="table-body-surface table-scroll inventory-dashboard-table-scroll">
<table class="data-table inventory-dashboard-table">
<thead>
<tr class="table-column-header">
<th>Medicine</th>
<th class="table-cell-center">Form</th>
<th class="table-cell-center">Stock</th>
<th class="table-cell-center">Balance</th>
</tr>
</thead>
<tbody>
${rows}
</tbody>
</table>
</div>
`;
            } else {
                tableHtml = `
<div class="empty-state inventory-dashboard-empty">
<div class="inventory-empty-icon">
<i class="fa-solid fa-prescription-bottle-medical"></i>
</div>

<h4 class="empty-state-title">No medicine items yet</h4>

<p class="empty-state-sub">
Medicine inventory records will appear here once items are available.
</p>
</div>
`;
            }

            const html = `
<article class="card inventory-dashboard-card">
<header class="card-header card-header-inline">

    <div class="card-header-left">

        <span class="card-header-icon">
            <i class="fa-solid fa-pills"></i>
        </span>

        <div>
            <h3 class="card-title">
                Medicine Supplies
            </h3>

            <p class="card-subtitle">
                Top Inventory
            </p>
        </div>

    </div>

    <a
        href="{{ route('dentist.dentist.inventory') }}"
        class="card-link"
    >
        View All
        <i class="fa-solid fa-arrow-right"></i>
    </a>

</header>
<div class="card-body">${tableHtml}</div>
</article>
`;

            renderDashboardContent('medicineSuppliesContainer', html);
        }

        document.addEventListener("DOMContentLoaded", () => {
            const nameEl = document.getElementById("dentistName");
            const greetingEl = document.getElementById("greetingText");

            const h = new Date().getHours();
            let greeting = "";

            if (h < 12) {
                greeting = "Good Morning,";
            } else if (h < 18) {
                greeting = "Good Afternoon,";
            } else {
                greeting = "Good Evening,";
            }

            greetingEl.textContent = greeting + " ";

            const subtitleEl = document.getElementById('rotatingSubtitle');

            const subtitles = [
                'Delivering brighter smiles today.',
                'Healthy smiles start here.',
                'Providing quality dental care.',
                'Creating confident smiles every day.',
                'Another day of patient care excellence.',
                'Ready for today’s consultations.',
                'Compassionate care for every patient.',
                'Helping every patient smile with confidence.'
            ];

            if (subtitleEl) {
                const randomIndex = Math.floor(Math.random() * subtitles.length);
                subtitleEl.textContent = subtitles[randomIndex];
            }

            const loadingPhases = [{
                    label: 'Loading clinic dashboard',
                    tasks: [
                        buildKpiGrid
                    ]
                },
                {
                    label: 'Loading charts and summaries',
                    tasks: [
                        renderGadChart,
                        buildTodayTreatmentProgress,
                        buildUpcomingAppointments
                    ]
                },
                {
                    label: 'Loading inventory',
                    tasks: [
                        buildMedicalSupplies,
                        buildMedicineSupplies
                    ]
                },
            ];

            window.setDashboardLoadingStatus?.(
                'Loading clinic dashboard',
                18
            );

            window.runEnterpriseLoading(
                loadingPhases, {
                    initialDelay: 80,
                    phaseGap: 220,
                    taskGap: 120
                }
            );

            return;
        });

        (function() {

            function tickClock() {
                const now = new Date();
                let h = now.getHours(),
                    m = now.getMinutes(),
                    s = now.getSeconds();
                const ampm = h >= 12 ? 'PM' : 'AM';
                const isDaytime = h >= 6 && h < 18;
                const displayHour = h % 12 || 12;

                const hmEl = document.getElementById('kpi-clock-hhmm');
                if (!hmEl) return;

                hmEl.textContent = String(displayHour).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                document.getElementById('kpi-clock-ss').textContent = ':' + String(s).padStart(2, '0');
                document.getElementById('kpi-clock-ampm').textContent = ampm;

            }
            tickClock();
            setInterval(tickClock, 1000);
        })();

        let dentistIsIn = @json(($clinicStatus ?? 'in') === 'in');

        function openStatusModal() {
            const modal =
                document.getElementById(
                    'statusModal'
                );

            const icon =
                document.getElementById(
                    'statusModalIcon'
                );

            const title =
                document.getElementById(
                    'statusModalTitle'
                );

            const subtitle =
                document.getElementById(
                    'statusModalSubtitle'
                );

            const alertTitle =
                document.getElementById(
                    'statusModalAlertTitle'
                );

            const body =
                document.getElementById(
                    'statusModalBody'
                );

            const confirmBtn =
                document.getElementById(
                    'confirmStatusBtn'
                );

            modal.classList.remove(
                'modal-theme-warning',
                'modal-theme-success',
                'modal-theme-danger'
            );

            if (dentistIsIn) {
                modal.classList.add(
                    'modal-theme-danger'
                );

                icon.innerHTML =
                    '<i class="fa-solid fa-door-closed"></i>';

                title.textContent =
                    'Close the Clinic';

                subtitle.textContent =
                    'You are about to mark yourself as OUT.';

                alertTitle.textContent =
                    'Mark clinic as OUT?';

                body.textContent =
                    'Patients will not be able to book new appointments, but existing appointments will remain active. ';

                confirmBtn.className =
                    'ui-btn ui-btn-danger';

                confirmBtn.innerHTML =
                    '<i class="fa-solid fa-door-closed"></i> Mark as OUT';
            } else {
                modal.classList.add(
                    'modal-theme-success'
                );

                icon.innerHTML =
                    '<i class="fa-solid fa-door-open"></i>';

                title.textContent =
                    'Open the Clinic?';

                subtitle.textContent =
                    'You are about to mark yourself as IN.';

                alertTitle.textContent =
                    'Mark clinic as IN?';

                body.textContent =
                    'Patients will be able to view your availability and book appointments again.';

                confirmBtn.className =
                    'ui-btn ui-btn-success';

                confirmBtn.innerHTML =
                    '<i class="fa-solid fa-door-open"></i> Mark as IN';
            }

            window.openModal?.(
                'statusModal'
            );
        }

        function closeStatusModal() {
            window.closeModal?.(
                'statusModal'
            );
        }

        async function confirmStatus() {
            const confirmBtn = document.getElementById('confirmStatusBtn');
            const newStatus = dentistIsIn ? 'out' : 'in';

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            try {
                const response = await fetch("{{ route('dentist.clinic-status.update') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                            .getAttribute(
                                "content")
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || "Failed to update clinic status.");
                }

                dentistIsIn = data.status === 'in';

                const btn = document.getElementById('statusBtn');
                const label = document.getElementById('statusLabel');
                const dot = document.getElementById('statusDot');
                const kpiLabel = document.getElementById('statusKpiLabel');
                const kpiIcon = document.getElementById('statusKpiIcon');
                const statCard = document.getElementById('clinicStatusStatCard');

                if (dentistIsIn) {
                    btn.classList.remove(
                        'ui-btn-danger'
                    );

                    btn.classList.add(
                        'ui-btn-success'
                    );

                    btn.dataset.tooltip =
                        'Clinic is open';

                    btn.dataset.tooltipTone =
                        'start';

                    label.textContent =
                        'IN';

                    dot.className =
                        'status-dot status-active';

                    if (kpiLabel) {
                        kpiLabel.textContent = 'Open';
                    }

                    if (kpiIcon) {
                        kpiIcon.className =
                            'fa-solid fa-door-open';
                    }

                    if (statCard) {
                        statCard.classList.remove(
                            's-cancelled'
                        );

                        statCard.classList.add(
                            's-active'
                        );
                    }
                } else {
                    btn.classList.remove(
                        'ui-btn-success'
                    );

                    btn.classList.add(
                        'ui-btn-danger'
                    );

                    btn.dataset.tooltip =
                        'Clinic is closed';

                    btn.dataset.tooltipTone =
                        'cancel';

                    label.textContent =
                        'OUT';

                    dot.className =
                        'status-dot status-cancelled';

                    if (kpiLabel) {
                        kpiLabel.textContent = 'Closed';
                    }

                    if (kpiIcon) {
                        kpiIcon.className =
                            'fa-solid fa-door-closed';
                    }

                    if (statCard) {
                        statCard.classList.remove(
                            's-active'
                        );

                        statCard.classList.add(
                            's-cancelled'
                        );
                    }
                }

                closeStatusModal();

            } catch (error) {
                console.error(error);
                alert(error.message || "Something went wrong while updating clinic status.");
            } finally {
                confirmBtn.disabled = false;
                if (dentistIsIn) {
                    confirmBtn.className =
                        'ui-btn ui-btn-danger';

                    confirmBtn.innerHTML =
                        '<i class="fa-solid fa-door-closed"></i> Mark as OUT';
                } else {
                    confirmBtn.className =
                        'ui-btn ui-btn-success';

                    confirmBtn.innerHTML =
                        '<i class="fa-solid fa-door-open"></i> Mark as IN';
                }
            }
        }

        function escHtml(str = '') {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function escJs(str = '') {
            return String(str)
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'");
        }

        function buildDayHoverCard(
            dateStr,
            appointments,
            placement = 'hover-bottom',
            alignment = 'hover-align-center'
        ) {
            const safeAppointments =
                (
                    Array.isArray(appointments) ?
                    appointments : []
                ).filter(appt => {
                    const status =
                        normalizeDashboardAppointmentStatus(
                            appt?.status
                        );

                    return [
                        'upcoming',
                        'rescheduled'
                    ].includes(status);
                });

            if (!safeAppointments.length) {
                return '';
            }

            const items =
                safeAppointments
                .slice(0, 3)
                .map(appt => {
                    const status =
                        normalizeDashboardAppointmentStatus(
                            appt.status
                        );

                    const isActiveAppointment = [
                        'upcoming',
                        'rescheduled'
                    ].includes(
                        status
                    );

                    const canReschedule =
                        isActiveAppointment;

                    const canCancel =
                        isActiveAppointment;

                    const patientName =
                        appt.name ||
                        'Unknown Patient';

                    const service =
                        appt.service ||
                        'General Service';

                    const time =
                        appt.time ||
                        '—';

                    const photo =
                        appt.patientPhotoUrl ||
                        appt.patient_photo_url ||
                        appt.profile_photo_url ||
                        appt.avatar ||
                        '';

                    const rawProfileUrl =
                        appt.patientProfileUrl ||
                        '#';

                    const profileUrl =
                        `${rawProfileUrl}${
rawProfileUrl.includes('?')
? '&'
: '?'
}from=dashboard`;

                    const safeName =
                        escJs(patientName);

                    const safeService =
                        escJs(service);

                    const safeSchedule =
                        escJs(
                            `${
formatModalDate(
appt.date ||
dateStr
)
} • ${time}`
                        );

                    return `
<article class="global-record-card scheduled-hover-patient-card">

<div class="global-record-card-body scheduled-hover-patient-body">

<a
href="${escHtml(profileUrl)}"
class="patient-avatar patient-avatar-sm"
data-patient-avatar
data-patient-name="${escHtml(patientName)}"
data-patient-url="${escHtml(photo)}"
aria-label="View ${escHtml(patientName)} profile"
></a>

<div class="global-record-identity scheduled-hover-patient-info">

<div class="scheduled-hover-patient-name-row">

<p class="global-record-name" data-patient-name>
    ${escHtml(patientName)}
</p>

${buildAppointmentTypeIcons(appt)}

</div>

<div class="global-record-subline scheduled-hover-meta">

    <span>
        ${escHtml(service)}
    </span>

    <span aria-hidden="true">·</span>

    <span>
        ${escHtml(time)}
    </span>

</div>

<div class="scheduled-hover-status-row">
    ${buildAppointmentStatus(appt)}
</div>

</div>

<div class="ui-action-group scheduled-hover-actions">

<a
href="${escHtml(profileUrl)}"
class="ui-action-btn ui-action-view"
aria-label="View profile"
data-tooltip="View profile"
data-tooltip-tone="view"
>
<i class="fa-regular fa-user"></i>
</a>

${
canReschedule
? `
                                                                                                                                                                                                                                                                                <button
                                                                                                                                                                                                                                                                                type="button"
                                                                                                                                                                                                                                                                                class="ui-action-btn ui-action-warning"
                                                                                                                                                                                                                                                                                aria-label="Reschedule appointment"
                                                                                                                                                                                                                                                                                data-tooltip="Reschedule appointment"
                                                                                                                                                                                                                                                                                data-tooltip-tone="reschedule"
                                                                                                                                                                                                                                                                                onclick="
                                                                                                                                                                                                                                                                                event.preventDefault();
                                                                                                                                                                                                                                                                                event.stopPropagation();
                                                                                                                                                                                                                                                                                openRescheduleModalFromDay(
                                                                                                                                                                                                                                                                                '${escJs(appt.id)}',
                                                                                                                                                                                                                                                                                '${safeName}',
                                                                                                                                                                                                                                                                                '${safeSchedule}',
                                                                                                                                                                                                                                                                                '${safeService}',
                                                                                                                                                                                                                                                                                '${escJs(appt.rescheduleUrl || '#')}'
                                                                                                                                                                                                                                                                                );
                                                                                                                                                                                                                                                                                "
                                                                                                                                                                                                                                                                                >
                                                                                                                                                                                                                                                                                <i class="fa-solid fa-rotate-right"></i>
                                                                                                                                                                                                                                                                                </button>
                                                                                                                                                                                                                                                                                `
: ''
}

${
canCancel
? `
                                                                                                                                                                                                                                                                                <button
                                                                                                                                                                                                                                                                                type="button"
                                                                                                                                                                                                                                                                                class="ui-action-btn ui-action-delete"
                                                                                                                                                                                                                                                                                aria-label="Cancel appointment"
                                                                                                                                                                                                                                                                                data-tooltip="Cancel appointment"
                                                                                                                                                                                                                                                                                data-tooltip-tone="cancel"
                                                                                                                                                                                                                                                                                onclick="
                                                                                                                                                                                                                                                                                event.preventDefault();
                                                                                                                                                                                                                                                                                event.stopPropagation();
                                                                                                                                                                                                                                                                                cancelAppointmentFromModal(
                                                                                                                                                                                                                                                                                '${escJs(appt.cancelUrl || '#')}',
                                                                                                                                                                                                                                                                                '${safeName}',
                                                                                                                                                                                                                                                                                '${safeSchedule}'
                                                                                                                                                                                                                                                                                );
                                                                                                                                                                                                                                                                                "
                                                                                                                                                                                                                                                                                >
                                                                                                                                                                                                                                                                                <i class="fa-solid fa-ban"></i>
                                                                                                                                                                                                                                                                                </button>
                                                                                                                                                                                                                                                                                `
: ''
}

</div>

</div>

</article>
`;
                })
                .join('');

            return `
<div
class="card day-hover-card ${placement} ${alignment}"
>
<div class="day-hover-bridge"></div>

<header class="card-header card-header-inline">

<div class="card-header-left">
<div>
<p class="card-title">
Scheduled Patients
</p>

<p class="card-subtitle">
${escHtml(
formatModalDate(
dateStr
)
)}
</p>
</div>
</div>

<a
href="{{ route('dentist.dentist.appointments') }}"
class="card-link"
>
View all
</a>

</header>

<div class="card-body scheduled-hover-list">
${items}
</div>
</div>
`;
        }

        function formatModalDate(dateStr) {
            const date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString('en-PH', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function getStatusBadgeClass(
            status
        ) {
            const normalized =
                normalizeDashboardAppointmentStatus(
                    status
                );

            return {
                upcoming: 'appointment-status-badge appointment-status-upcoming',

                rescheduled: 'appointment-status-badge appointment-status-rescheduled',

                completed: 'appointment-status-badge appointment-status-completed',

                cancelled: 'appointment-status-badge appointment-status-cancelled'
            } [normalized] ||
            'appointment-status-badge appointment-status-upcoming';
        }

        function getAppointmentStatusLabel(
            status
        ) {
            const normalized =
                normalizeDashboardAppointmentStatus(
                    status
                );

            return {
                upcoming: 'Upcoming',

                rescheduled: 'Rescheduled',

                completed: 'Completed',

                cancelled: 'Cancelled'
            } [normalized] || 'Upcoming';
        }

        function openDayAppointmentsModal(dateStr, appointmentsJson) {
            const dateEl = document.getElementById('dayAppointmentsModalDate');
            const listEl = document.getElementById('dayAppointmentsModalList');

            let appointments = [];

            try {
                const parsedAppointments =
                    JSON.parse(appointmentsJson);

                appointments =
                    (
                        Array.isArray(parsedAppointments) ?
                        parsedAppointments : []
                    ).filter(appt => {
                        const status =
                            normalizeDashboardAppointmentStatus(
                                appt?.status
                            );

                        return [
                            'upcoming',
                            'rescheduled'
                        ].includes(status);
                    });
            } catch (e) {
                appointments = [];
            }

            dateEl.textContent = formatModalDate(dateStr);

            if (!appointments.length) {
                listEl.innerHTML =
                    window.EmptyState?.buildHtml({
                        title: 'No appointments for this date',
                        message: 'There are no scheduled patients for this date.',
                        icon: 'fa-calendar-xmark',
                        className: 'empty-state-compact'
                    }) || '';

                return;
            } else {
                listEl.innerHTML = appointments
                    .map(appt => {
                        const status =
                            normalizeDashboardAppointmentStatus(
                                appt.status
                            );

                        const isActiveAppointment = [
                            'upcoming',
                            'rescheduled'
                        ].includes(
                            status
                        );

                        const canReschedule =
                            isActiveAppointment;

                        const canCancel =
                            isActiveAppointment;

                        const patientName =
                            appt.name ||
                            'Unknown Patient';

                        const service =
                            appt.service ||
                            'General Service';

                        const time =
                            appt.time ||
                            '—';

                        const photo =
                            appt.patientPhotoUrl ||
                            appt.patient_photo_url ||
                            appt.profile_photo_url ||
                            appt.avatar ||
                            '';

                        const rawProfileUrl =
                            appt.patientProfileUrl ||
                            '#';

                        const profileUrl =
                            `${rawProfileUrl}${
rawProfileUrl.includes('?')
? '&'
: '?'
}from=dashboard`;

                        const safeName =
                            escJs(patientName);

                        const safeService =
                            escJs(service);

                        const safeSchedule =
                            escJs(
                                `${formatModalDate(
appt.date || dateStr
)} • ${time}`
                            );

                        const statusClass = {
                            upcoming: 'status-upcoming',

                            rescheduled: 'status-rescheduled',

                            completed: 'status-completed',

                            cancelled: 'status-cancelled'
                        } [status] || 'status-upcoming';

                        return `
<article class="global-record-card">

<div class="global-record-card-body scheduled-modal-patient-body">

<a
href="${escHtml(profileUrl)}"
onclick="closeDayAppointmentsModal()"
class="patient-avatar patient-avatar-sm"
data-patient-avatar
data-patient-name="${escHtml(patientName)}"
data-patient-url="${escHtml(photo)}"
aria-label="View ${escHtml(patientName)} profile"
></a>

<div class="global-record-identity scheduled-modal-patient-info">

<div class="scheduled-modal-name-row">

    <p class="global-record-name" data-patient-name>
        ${escHtml(patientName)}
    </p>

    ${buildAppointmentTypeIcons(appt)}

    <div class="hidden lg:block shrink-0">
        ${buildAppointmentStatus(appt)}
    </div>

</div>

<div class="global-record-subline scheduled-modal-meta">

    <span>
        ${escHtml(service)}
    </span>

    <span aria-hidden="true">
        ·
    </span>

    <span>
        ${escHtml(time)}
    </span>

</div>

</div>

<div class="flex flex-col items-end gap-2 shrink-0">

    <div class="lg:hidden shrink-0">
        ${buildAppointmentStatus(appt)}
    </div>

    <div class="ui-action-group scheduled-modal-actions">

<a
href="${escHtml(profileUrl)}"
onclick="closeDayAppointmentsModal()"
class="ui-action-btn ui-action-view"
aria-label="View profile"
data-tooltip="View profile"
data-tooltip-tone="view"
>
<i class="fa-regular fa-user"></i>
</a>

${
canReschedule
? `
                                                                                                                                                                                                                                                                                <button
                                                                                                                                                                                                                                                                                type="button"
                                                                                                                                                                                                                                                                                class="ui-action-btn ui-action-warning"
                                                                                                                                                                                                                                                                                aria-label="Reschedule appointment"
                                                                                                                                                                                                                                                                                data-tooltip="Reschedule appointment"
                                                                                                                                                                                                                                                                                data-tooltip-tone="reschedule"
                                                                                                                                                                                                                                                                                onclick="
                                                                                                                                                                                                                                                                                openRescheduleModalFromDay(
                                                                                                                                                                                                                                                                                '${escJs(appt.id)}',
                                                                                                                                                                                                                                                                                '${safeName}',
                                                                                                                                                                                                                                                                                '${safeSchedule}',
                                                                                                                                                                                                                                                                                '${safeService}',
                                                                                                                                                                                                                                                                                '${escJs(appt.rescheduleUrl || '#')}'
                                                                                                                                                                                                                                                                                )
                                                                                                                                                                                                                                                                                "
                                                                                                                                                                                                                                                                                >
                                                                                                                                                                                                                                                                                <i class="fa-solid fa-rotate-right"></i>
                                                                                                                                                                                                                                                                                </button>
                                                                                                                                                                                                                                                                                `
: ''
}

${
canCancel
? `
                                                                                                                                                                                                                                                                                <button
                                                                                                                                                                                                                                                                                type="button"
                                                                                                                                                                                                                                                                                class="ui-action-btn ui-action-delete"
                                                                                                                                                                                                                                                                                aria-label="Cancel appointment"
                                                                                                                                                                                                                                                                                data-tooltip="Cancel appointment"
                                                                                                                                                                                                                                                                                data-tooltip-tone="cancel"
                                                                                                                                                                                                                                                                                onclick="
                                                                                                                                                                                                                                                                                cancelAppointmentFromModal(
                                                                                                                                                                                                                                                                                '${escJs(appt.cancelUrl || '#')}',
                                                                                                                                                                                                                                                                                '${safeName}',
                                                                                                                                                                                                                                                                                '${safeSchedule}'
                                                                                                                                                                                                                                                                                )
                                                                                                                                                                                                                                                                                "
                                                                                                                                                                                                                                                                                >
                                                                                                                                                                                                                                                                                <i class="fa-solid fa-ban"></i>
                                                                                                                                                                                                                                                                                </button>
                                                                                                                                                                                                                                                                                `
: ''
}

</div>

</div>

</div>

</article>
`;
                    })
                    .join('');

                window.PatientUI
                    ?.initAvatars
                    ?.(listEl);
                window.openModal?.(
                    'dayAppointmentsModal'
                );
            }
        }

        function closeDayAppointmentsModal() {
            window.closeModal?.(
                'dayAppointmentsModal'
            );
        }
    </script>

    @include('components.appointment-calendar-script', [
        'mode' => 'dentist-dashboard',
        'renderStyle' => 'dentist',
        'calendarContainerId' => 'dentistCalendarContainer',
        'dateInputId' => null,
        'timeInputId' => null,
        'slotEndpoint' => null,
        'scheduleRules' => $schedules ?? [],
        'blockedDates' => $blockedDates ?? [],
        'appointmentCountsPerDay' => $calendarAppointmentCounts ?? [],
        'appointmentDetails' => $calendarAppointmentDetails ?? [],
        'philippineHolidays' => $philippineHolidays ?? [],
        'useDynamicScheduleRules' => true,
        'disallowToday' => false,
        'allowPastDates' => true,
        'allowToggleOffDate' => false,
        'historyMonths' => 0,
        'maxFutureMonths' => 3,
    ])
@endsection
