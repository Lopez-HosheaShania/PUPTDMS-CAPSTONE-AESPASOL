<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.security-bootstrap')

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Page Unavailable')</title>

    <link rel="icon" type="image/png" href="{{ asset('images/PUPT-DMS-Logo.png') }}">

    @vite(['resources/css/app.css', 'resources/css/pages/error-pages.css', 'resources/js/app.js'])
</head>

<body class="error-page error-page--@yield('tone', 'general')">

    @php
        $errorCode = trim($__env->yieldContent('code'));

        $customPrimaryUrl = trim($__env->yieldContent('primary_url'));
        $customPrimaryLabel = trim($__env->yieldContent('primary_label'));

        $primaryUrl = $customPrimaryUrl ?: route('dashboard');
        $primaryLabel = $customPrimaryLabel ?: 'Back to Dashboard';

        if (!request()->isMethodSafe() && $errorCode === '503') {
            $primaryUrl = route('dashboard');
            $primaryLabel = 'Back to Dashboard';
        }

        $showRetry =
            request()->isMethodSafe() &&
            in_array($errorCode, ['500', '503'], true) &&
            $primaryUrl !== request()->fullUrl();
    @endphp

    <main class="error-stage">

        <section class="error-panel" aria-labelledby="errorPageTitle">

            <header class="error-panel__header">

                <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" alt="PUP Taguig Dental Clinic Logo"
                    class="error-brand-logo" width="72" height="72">

                <p class="error-brand">
                    PUP Taguig Dental Clinic
                </p>

            </header>

            <div class="error-panel__body">

                <div class="error-code" aria-hidden="true">
                    @yield('code')
                </div>

                <h1 id="errorPageTitle" class="error-heading">
                    @yield('message')
                </h1>

                <div class="error-description">
                    <p>@yield('description')</p>

                    <p>@yield('notice_text')</p>
                </div>

                <div class="error-actions">

                    <a href="{{ $primaryUrl }}" class="error-action error-action--primary">

                        {{ $primaryLabel }}

                    </a>

                    @if ($showRetry)
                        <a href="{{ request()->fullUrl() }}" class="error-action error-action--secondary">

                            Try Again

                        </a>
                    @elseif ($errorCode === '503' && request()->isMethodSafe())
                        <a href="{{ route('dashboard') }}" class="error-action error-action--secondary">

                            Back to Dashboard

                        </a>
                    @else
                        <button type="button" class="error-action error-action--secondary"
                            data-ui-click="action-1f0252ba48f2">

                            Go Back

                        </button>
                    @endif

                </div>

                <footer class="error-support">
                    <p>
                        Still not working? Contact the system administrator
                        and mention error code
                        <strong>@yield('code')</strong>.
                    </p>
                </footer>

            </div>

        </section>

    </main>

</body>

</html>
