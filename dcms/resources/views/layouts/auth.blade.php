<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">

<head>
    <meta charset="UTF-8" />
    <title>@yield('title', 'PUP Taguig Dental Clinic')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/PUPT-DMS-Logo.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = '#0F172A';
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.classList.remove('dark');
                document.documentElement.style.backgroundColor = '#F8F9FA';
            }
        })();
    </script>

    <script>
        window.chatbotContext = {
            page: 'login',
            pageLabel: 'Login page',
            isGuest: true
        };
    </script>

    <style>
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --crimson: #8B0000;
            --crimson-dark: #660000;
            --gold: #FFD700;

            --white: #FFFFFF;
            --bg-light: #F8F9FA;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --text-main: #1F2937;
            --text-muted: #4B5563;

            --shadow-sm: 0 2px 8px rgba(139, 0, 0, 0.05);
            --shadow-md: 0 8px 24px rgba(139, 0, 0, 0.08);
            --shadow-lg: 0 16px 48px rgba(139, 0, 0, 0.12);
        }

        /* Dark mode CSS variables */
        html[data-theme="dark"] {
            --white: #1A1F2E;
            --bg-light: #0F172A;
            --gray-100: #1E293B;
            --gray-200: #334155;
            --text-main: #F1F5F9;
            --text-muted: #94A3B8;

            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.5);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes textShimmer {
            0% {
                background-position: 0% center;
            }

            100% {
                background-position: 200% center;
            }
        }

        @keyframes pulseGlow {

            0%,
            100% {
                box-shadow: 0 8px 32px 0 rgba(139, 0, 0, 0.08);
            }

            70% {
                box-shadow: 0 8px 32px 0 rgba(139, 0, 0, 0.2);
            }
        }

        /* Content wrapper */
        .auth-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
    </style>

    @yield('styles')
</head>

<body>
    <div class="auth-container">
        @yield('content')
        @include('partials.voice-logic')
        @include('partials.chatbot')

        {{-- Sienna Accessibility Widget --}}
        <script src="https://cdn.jsdelivr.net/npm/sienna-accessibility@latest/dist/sienna-accessibility.umd.js"
            data-position="bottom-right" data-offset="18,118" defer></script>


        @include('partials.footer')
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;

        function updateThemeIcon() {
            const isDark = html.getAttribute('data-theme') === 'dark';
            themeIcon.classList = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';

            html.setAttribute('data-theme', newTheme);
            html.classList.toggle('dark', newTheme === 'dark');
            localStorage.setItem('theme', newTheme);

            if (newTheme === 'dark') {
                html.style.backgroundColor = '#0F172A';
            } else {
                html.style.backgroundColor = '#F8F9FA';
            }

            updateThemeIcon();
        });

        // Set initial icon
        updateThemeIcon();
    </script>

    @yield('scripts')
</body>

</html>
