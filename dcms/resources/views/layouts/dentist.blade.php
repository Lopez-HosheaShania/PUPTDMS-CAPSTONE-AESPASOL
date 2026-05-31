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
                document.documentElement.style.backgroundColor = '#000D1A';
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.style.backgroundColor = '#F4F4F4';
            }
        })();
    </script>
    <title>@yield('title', 'PUP Taguig Dental Clinic')</title>
    @vite(['resources/css/app.css', 'resources/css/dentist.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/PUPT-DMS-Logo.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/header.css') }}">

    @include('partials.dentist.styles')
    @include('partials.terms-styles')
    @include('partials.global-toast-styles')

    @yield('styles')

</head>

<body class="role-dentist @yield('body-class', 'bg-[#F4F4F4]')">

    @include('partials.header', [
        'role' => 'dentist',
        'notifications' => $notifications ?? [],
        'showMobileMenu' => true,
        'showSettings' => false,
    ])

    @include('partials.dentist.sidebar')
    @include('partials.dentist.drawer')

    @include('partials.impersonation-banner')

    <div id="toastContainer"></div>

    @include('components.reschedule-modal')
    @include('components.cancel-modal')

    @yield('content')

    @include('partials.footer')

    @include('partials.dentist.scripts')

    @include('partials.voice-logic')
    @include('components.discard-changes')

    <script src="https://cdn.jsdelivr.net/npm/sienna-accessibility@latest/dist/sienna-accessibility.umd.js"
        data-position="bottom-right" data-offset="18,24" defer></script>

    @include('partials.global-toast')

    {{-- GLOBAL TERMS MODAL --}}
    @include('partials.terms-modal')
    @include('partials.terms-scripts')

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
    <script src="{{ asset('js/header.js') }}"></script>
</body>

</html>
