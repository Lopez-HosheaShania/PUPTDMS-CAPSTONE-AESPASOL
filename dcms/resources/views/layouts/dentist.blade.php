<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    @if (auth()->check())
        <meta name="auth-user-id" content="{{ auth()->id() }}">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.style.backgroundColor = '#0d0f12';
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.style.backgroundColor = '#F4F4F4';
            }
        })();

        (function() {
            var theme = localStorage.getItem('theme') || 'light';

            document.documentElement.setAttribute('data-theme', theme === 'dark' ? 'dark' : 'light');
            document.documentElement.style.backgroundColor = theme === 'dark' ? '#000D1A' : '#F4F4F4';

            var path = window.location.pathname || '';

            var role = path.indexOf('/admin') === 0 ?
                'admin' :
                (path.indexOf('/dentist') === 0 ? 'dentist' : 'patient');

            var keys = {
                admin: 'adminSidebarCollapsed',
                dentist: 'dentistSidebarCollapsed',
                patient: 'patientSidebarCollapsed'
            };

            document.documentElement.classList.add('sidebar-preload');

            try {
                if (localStorage.getItem(keys[role]) === '1') {
                    document.documentElement.classList.add('sidebar-collapsed-init');
                }
            } catch (e) {}
        })();
    </script>

    <title>@yield('title', 'PUP Taguig Dental Clinic')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/PUPT-DMS-Logo.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ asset('css/header.css') }}">

    @yield('styles')

</head>

<body class="role-dentist @yield('body-class', 'bg-[#F4F4F4]')">

    @include('partials.header', [
        'role' => 'dentist',
        'notifications' => $notifications ?? [],
        'showMobileMenu' => true,
        'showSettings' => false,
    ])

    @include('components.global-sidebar', ['role' => 'dentist'])

    @include('partials.impersonation-banner')

    @include('components.reschedule-modal')
    @include('components.cancel-modal')

    @yield('content')

    @include('partials.footer')

    @include('partials.voice-logic')
    @include('components.discard-changes')

    <script src="https://cdn.jsdelivr.net/npm/sienna-accessibility@latest/dist/sienna-accessibility.umd.js"
        data-position="bottom-right" data-offset="18,24" defer></script>

    @include('partials.global-toast')

    {{-- GLOBAL TERMS MODAL --}}
    @include('partials.terms-modal')

    @if (View::hasSection('usesAppointmentCalendar'))
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
            'calendarWrapSelector' => '#rescheduleModal .cal-wrap',
            'slotsWrapSelector' => '#rescheduleModal .slots-wrap',
            'slotEndpoint' => route('dentist.appointment.slots'),
            'scheduleRules' => isset($schedules)
                ? $schedules
                : (isset($scheduleRules)
                    ? $scheduleRules
                    : \App\Models\ClinicSchedule::active()->get()->values()->toArray()),
            'blockedDates' => isset($blockedDates) ? $blockedDates : [],
            'appointmentCountsPerDay' => isset($appointmentCountsPerDay) ? $appointmentCountsPerDay : [],
            'philippineHolidays' => isset($philippineHolidays) ? $philippineHolidays : [],
            'disallowToday' => true,
            'allowToggleOffDate' => true,
            'useDynamicScheduleRules' => true,
            'renderStyle' => 'dentist',
        ])
    @endif

    @include('components.reschedule-modal-script')
    @include('components.cancel-modal-script')

    @stack('scripts')
    @yield('scripts')

    @include('partials.chatbot')
</body>

</html>
