@php
    $layoutRole =
        trim($__env->yieldContent('layout-role')) ?:
        optional(optional(auth()->user())->role)->slug ?? (session('role') ?? 'patient');

    if ($layoutRole === 'super_admin') {
        $layoutRole = 'admin';
    }

    $isAdmin = $layoutRole === 'admin';
    $isDentist = $layoutRole === 'dentist';
    $isPatient = $layoutRole === 'patient';

    $hideSidebar = View::hasSection('hide-sidebar');
    $hideMobileNav = View::hasSection('hide-mobile-nav');
    $hidePatientModals = View::hasSection('hide-patient-modals');
    $hideFloatingActions = View::hasSection('hide-floating-actions');
    $hideFooter = View::hasSection('hide-footer');

    $showMobileMenu = ($isAdmin || $isDentist) && !$hideSidebar;
    $showSettings = $isAdmin;

    $accessibilityOffset = $isPatient && !$hideMobileNav ? '18,118' : '18,24';
@endphp

<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @if (auth()->check())
        <meta name="auth-user-id" content="{{ auth()->id() }}">
    @endif

    <script>
        (function() {
            const savedTheme =
                localStorage.getItem('theme');

            const theme =
                savedTheme === 'dark' ?
                'dark' :
                'light';

            const isDark =
                theme === 'dark';

            const role =
                @json($layoutRole);

            document.documentElement.setAttribute(
                'data-theme',
                theme
            );

            document.documentElement.classList.toggle(
                'dark',
                isDark
            );

            document.documentElement.style.colorScheme =
                isDark ?
                'dark' :
                'light';

            document.documentElement.style.backgroundColor =
                isDark ?
                '#101111' :
                '#F4F4F4';

            const sidebarKeys = {
                admin: 'adminSidebarCollapsed',
                dentist: 'dentistSidebarCollapsed',
                patient: 'patientSidebarCollapsed'
            };

            const sidebarStorageKey =
                sidebarKeys[role] ??
                'sidebarCollapsed';

            const savedSidebarCollapsed =
                localStorage.getItem(
                    sidebarStorageKey
                ) === '1';

            const sidebarCanRender =
                role === 'patient' ?
                window.matchMedia(
                    '(min-width: 1200px)'
                ).matches :
                window.matchMedia(
                    '(min-width: 768px)'
                ).matches;

            document.documentElement
                .classList
                .add('sidebar-preload');

            document.documentElement
                .classList
                .toggle(
                    'sidebar-collapsed-init',
                    savedSidebarCollapsed &&
                    sidebarCanRender
                );
        })();
    </script>

    <title>
        @hasSection('title')
            @yield('title') | PUP Taguig Dental Clinic
        @else
            PUP Taguig Dental Clinic
        @endif
    </title>

    <link rel="icon" type="image/png" href="{{ asset('images/PUPT-DMS-Logo.png') }}">

    <style>
        @keyframes page-enter {
            from {
                opacity: .92;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-enter {
            animation: page-enter 180ms ease-out both;
        }


        @if ($isPatient)
            @media (max-width: 640px) {
                html body.role-patient .asw-menu-btn {
                    opacity: 0 !important;
                    visibility: hidden !important;
                    pointer-events: none !important;
                    transform: scale(0.01) !important;
                    transition: none !important;
                }
            }
        @endif

        @media (prefers-reduced-motion: reduce) {
            .page-enter {
                animation: none;
            }
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($isAdmin)
        @vite('resources/css/pages/admin/admin-shared.css')
    @elseif ($isDentist)
        @vite('resources/css/pages/dentist/dentist-shared.css')
    @elseif ($isPatient)
        @vite('resources/css/pages/patient/patient-shared.css')
    @endif

    @yield('styles')
    @stack('styles')
</head>

<body
    class="
        role-{{ $layoutRole }}
        {{ $hideSidebar ? 'layout-no-sidebar' : '' }}
        {{ $hideMobileNav ? 'layout-no-mobile-nav' : '' }}
        {{ $hideFloatingActions ? 'layout-no-floating-actions' : '' }}
        @yield('body-class', 'bg-[#F4F4F4]')">

    @include('partials.header', [
        'role' => $layoutRole,
        'patient' => $patient ?? null,
        'notifications' => $notifications ?? [],
        'showMobileMenu' => $showMobileMenu,
        'showSettings' => $showSettings,
    ])

    @if (!$hideSidebar)
        @include('components.global-sidebar', [
            'role' => $layoutRole,
        ])
    @endif

    @if ($isPatient && !$hideMobileNav)
        @include('partials.patient.mobile-nav')
    @endif

    @if ($isPatient && !$hidePatientModals)
        @include('components.patient-document-modals')
        @include('components.patient-record-modal')
    @endif

    @if ($isPatient)
        @include('partials.impersonation-banner')
    @endif

    @if ($isDentist || ($isAdmin && View::hasSection('usesAppointmentCalendar')))
        @include('partials.impersonation-banner')
        @include('components.reschedule-modal')
        @include('components.cancel-modal')
        @include('components.patient-record-modal')
    @endif

    @if ($isAdmin && !View::hasSection('usesAppointmentCalendar'))
        @include('components.patient-record-modal')
    @endif

    @yield('content')

    @if (!$hideFooter)
        @include('partials.footer')
    @endif

    @include('components.active-appointment-modal')

    @include('components.discard-changes')

    @include('partials.global-toast')

    <div id="logoutConfirmModal" class="ui-modal logout-confirm-modal" role="dialog" aria-modal="true"
        aria-labelledby="logoutConfirmTitle" aria-describedby="logoutConfirmDescription" aria-hidden="true">
        <div class="ui-modal-card modal-sm logout-confirm-card">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon logout-confirm-icon">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </div>

                    <div class="modal-copy">
                        <h2 id="logoutConfirmTitle" class="modal-title">
                            Confirm Logout
                        </h2>

                        <p class="modal-subtitle">
                            You are about to end your current session.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div class="logout-confirm-message">
                    <div class="logout-confirm-message-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <div>
                        <p id="logoutConfirmDescription">
                            Are you sure you want to log out?
                        </p>

                        <span>
                            You will need to sign in again to access your account.
                        </span>
                    </div>
                </div>
            </div>

            <div class="modal-ft logout-confirm-actions">
                <button type="button" class="btn-close-modal" data-logout-modal-close>
                    Stay Signed In
                </button>

                <button type="button" id="confirmLogoutBtn" class="modal-btn-confirm danger">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Log Out</span>
                </button>
            </div>
        </div>
    </div>

    @include('partials.terms-modal')

    @if (($isDentist || $isAdmin) && View::hasSection('usesAppointmentCalendar'))
        @include('components.appointment-calendar-script', [
            'mode' => 'booking',
            'calendarContainerId' => 'calGridWrapReschedule',
            'calGridId' => 'calGrid',
            'calMonthLabelId' => 'calMonthLabel',
            'calYearLabelId' => 'calYearLabel',
            'dateInputId' => 'new_appointment_date',
            'timeInputId' => 'new_appointment_time',
            'dateBannerId' => 'dateBanner',
            'slotPlaceholderId' => 'slotPlaceholder',
            'slotContainerId' => 'slotContainer',
            'slotGridId' => 'slotGrid',
            'selectedSlotDisplayId' => 'selectedSlotDisplay',
            'selectedSlotTextId' => 'selectedSlotText',
            'selectedTimePillId' => 'selectedTimePill',
            'selectedTimeTextId' => 'selectedTimeText',
            'datePillId' => 'datePill',
            'dateErrorId' => 'dateError',
            'timeErrorId' => 'timeError',
            'clearSlotButtonId' => 'clearSlotSelectionBtn',
            'calendarWrapSelector' => '#rescheduleModal .cal-wrap',
            'slotsWrapSelector' => '#rescheduleModal .slots-wrap',
            'slotEndpoint' => route('dentist.appointment.slots'),
        
            'scheduleRules' => isset($schedules)
                ? $schedules
                : (isset($scheduleRules)
                    ? $scheduleRules
                    : \App\Models\ClinicSchedule::active()->get()->values()->toArray()),
        
            'blockedDates' => $blockedDates ?? [],
            'appointmentCountsPerDay' => $appointmentCountsPerDay ?? [],
            'philippineHolidays' => $philippineHolidays ?? [],
        
            'disallowToday' => true,
            'allowToggleOffDate' => true,
            'useDynamicScheduleRules' => true,
            'renderStyle' => 'dentist',
        ])
    @endif

    @if ($isPatient && !$hideMobileNav && !$hidePatientModals)
        <script>
            function openQuickAction(type) {
                if (type === 'record') {
                    document
                        .getElementById('dentalHealthRecordModal')
                        ?.showModal();
                }

                if (type === 'clearance') {
                    document
                        .getElementById('dentalClearanceModal')
                        ?.showModal();
                }
            }
        </script>
    @endif

    <div id="globalActionTooltip" class="global-action-tooltip" role="tooltip" aria-hidden="true">
    </div>

    @if (!$hideFloatingActions)
        <script src="{{ asset('vendor/sienna/sienna-accessibility.umd.js') }}" data-position="bottom-right"
            data-offset="{{ $accessibilityOffset }}" defer></script>

        @include('partials.chatbot')
    @endif

    @stack('scripts')
    @yield('scripts')
</body>

</html>
