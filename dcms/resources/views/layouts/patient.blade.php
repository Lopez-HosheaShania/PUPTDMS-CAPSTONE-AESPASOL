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
    @vite(['resources/css/app.css', 'resources/css/patient.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/PUPT-DMS-Logo.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">

    @yield('styles')

</head>

<body class="role-patient @yield('body-class', 'bg-[#F4F4F4]')">

    <script>
        if (localStorage.getItem('sidebar-collapsed-patient') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    </script>

    @include('partials.header', [
        'role' => 'patient',
        'patient' => $patient ?? null,
        'notifications' => $notifications ?? [],
        'showMobileMenu' => false,
        'showSettings' => false,
    ])

    @include('partials.patient.sidebar')
    @include('partials.patient.mobile-nav')
    @include('components.patient-document-modals')
    @include('components.patient-record-modal')
    @include('partials.impersonation-banner')

    @yield('content')

    @include('partials.footer')

    {{-- Sitewide scripts --}}
    @include('partials.patient.script')

    {{-- Add the global voice logic here --}}
    @include('partials.voice-logic')

    {{-- Sienna Accessibility Widget --}}
    <script src="https://cdn.jsdelivr.net/npm/sienna-accessibility@latest/dist/sienna-accessibility.umd.js"
        data-position="bottom-right" data-offset="18,118" defer></script>

    @include('partials.global-toast')

    {{-- GLOBAL TERMS MODAL --}}
    @include('partials.terms-modal')

    @stack('scripts')
    @yield('scripts')

    @include('partials.chatbot')
    <script src="{{ asset('js/header.js') }}"></script>

    <script>
        function openQuickAction(type) {
            if (type === 'record') {
                document.getElementById('dentalHealthRecordModal')?.showModal();
            }

            if (type === 'clearance') {
                document.getElementById('dentalClearanceModal')?.showModal();
            }
        }
    </script>

</body>

</html>
