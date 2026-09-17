<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const theme = savedTheme === 'dark' ? 'dark' : 'light';
            const isDark = theme === 'dark';

            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
            document.documentElement.style.backgroundColor = isDark ? '#101111' : '#F4F4F4';
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
    @stack('styles')
</head>

<body class="@yield('body-class', 'auth-guest-body')">
    @yield('content')

    @include('partials.global-toast')

    <div id="globalActionTooltip" class="global-action-tooltip" role="tooltip" aria-hidden="true"></div>

    <script>
        window.chatbotContext = {
            page: 'login',
            pageLabel: 'Login page',
            isGuest: true
        };
    </script>

    <script src="{{ asset('vendor/sienna/sienna-accessibility.umd.js') }}" data-position="bottom-right" data-offset="18,118"
        defer></script>

    @include('partials.chatbot')
    @include('partials.footer')

    @stack('scripts')
    @yield('scripts')
</body>

</html>
