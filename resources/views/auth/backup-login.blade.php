@extends('layouts.auth')

@section('title', 'Backup Login | PUP Taguig Dental Clinic')

@section('body-class', 'auth-guest-body backup-login-body')

@section('styles')
    @vite('resources/css/pages/auth/backup-login.css')
@endsection

@section('content')
<div class="backup-page">
  <section class="backup-shell">
    <div class="backup-panel">
      <div class="backup-brand">
        <div>
          <div class="backup-brand-top">
            <div class="backup-brand-logos">
              <img src="{{ asset('images/PUP.png') }}" alt="PUP Logo">
              <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" alt="Clinic Logo">
            </div>
            <div class="backup-brand-name">PUP Taguig Dental Clinic</div>
          </div>

          <h1 class="backup-brand-title">Admin-only backup access.</h1>
          <p class="backup-brand-copy">
            Secure fallback access for administrators when the primary SSO service is temporarily
            unavailable.
          </p>

          <div class="backup-brand-status" aria-label="Backup access status">
            <span>
              <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
              Admin only
            </span>

            <span>
              <i class="fa-solid fa-lock" aria-hidden="true"></i>
              Secure fallback
            </span>
          </div>
        </div>

        <div class="backup-brand-footer">
          <span>Local fallback authentication</span>
        </div>
      </div>

      <div class="backup-form-wrap">
        <div class="backup-form-card">
          <div class="backup-form-toolbar">
            <a href="{{ route('login') }}" class="backup-back-link">
              <i class="fa-solid fa-arrow-left"></i>
              Back to landing page
            </a>

            <button type="button" class="auth-landing-theme-toggle" data-global-theme-toggle
              data-tooltip="Switch to dark mode" data-tooltip-tone="neutral" aria-label="Switch to dark mode"
              aria-pressed="false">

              <i class="fa-solid fa-moon" data-global-theme-icon aria-hidden="true">
              </i>
            </button>
          </div>

          <div class="backup-form-heading-row">
            <span class="backup-admin-pill">
              <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
              Administrator access
            </span>
          </div>

          <h2 class="backup-form-title">Sign in with your local admin account.</h2>
          <p class="backup-form-copy">
            {{ $idpStatusMessage ?? 'Primary SSO is temporarily unavailable.' }} Sign in using your local administrator credentials.
          </p>

          <form method="POST" action="{{ route('login.store') }}" class="backup-form">
            @csrf
            <input type="hidden" name="browser_name" id="browserNameInput">

            <div class="backup-field">
              <label for="email">Email Address</label>

              <div class="backup-input-wrap">
                <i class="fa-regular fa-envelope backup-input-leading-icon" aria-hidden="true"></i>

                <input id="email" name="email" type="email" placeholder="name@pup.edu.ph" value="{{ old('email') }}"
                  required>
              </div>
            </div>

            <div class="backup-field">
              <label for="password">Local Password</label>

              <div class="backup-input-wrap backup-password-wrap">
                <i class="fa-solid fa-lock backup-input-leading-icon" aria-hidden="true"></i>

                <input id="password" name="password" type="password" placeholder="Enter your password" required>

                <button type="button" class="backup-password-toggle" id="backupPasswordToggle"
                  data-tooltip="Show password" data-tooltip-tone="neutral" aria-label="Show password"
                  aria-pressed="false">

                  <i class="fa-regular fa-eye" id="backupPasswordIcon" aria-hidden="true">
                  </i>
                </button>
              </div>
            </div>

            <button type="submit" class="backup-submit">Continue Locally</button>
          </form>

          <div class="backup-switch">
            <span>Prefer the standard sign-in method?</span>

            <a href="/auth/oidc/redirect" class="backup-sso-btn" data-oidc-login-link>
              <i class="fa-solid fa-arrow-right-to-bracket"></i>
              Use SSO Instead
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
  function detectBrowserName() {
    const userAgent = navigator.userAgent || '';

    if (navigator.brave) {
      return 'Brave';
    }

    if (userAgent.includes('Edg/')) {
      return 'Edge';
    }

    if (userAgent.includes('OPR/')) {
      return 'Opera';
    }

    if (userAgent.includes('Firefox/')) {
      return 'Firefox';
    }

    if (userAgent.includes('Chrome/')) {
      return 'Chrome';
    }

    if (userAgent.includes('Safari/')) {
      return 'Safari';
    }

    return 'Browser';
  }

  function applyBrowserHintToOidcLinks() {
    const browserName = detectBrowserName();

    document.querySelectorAll('[data-oidc-login-link]').forEach(link => {
      const url = new URL(link.getAttribute('href'), window.location.origin);
      url.searchParams.set('browser_name', browserName);
      link.setAttribute('href', url.pathname + url.search);
    });

    const input = document.getElementById('browserNameInput');

    if (input) {
      input.value = browserName;
    }
  }

  function initBackupPasswordToggle() {
    const passwordInput =
      document.getElementById('password');

    const toggleButton =
      document.getElementById('backupPasswordToggle');

    const icon =
      document.getElementById('backupPasswordIcon');

    if (!passwordInput || !toggleButton || !icon) {
      return;
    }

    toggleButton.addEventListener('click', () => {
      const isHidden =
        passwordInput.type === 'password';

      passwordInput.type =
        isHidden ? 'text' : 'password';

      icon.className = isHidden ?
        'fa-regular fa-eye-slash' :
        'fa-regular fa-eye';

      const label = isHidden ?
        'Hide password' :
        'Show password';

      toggleButton.setAttribute(
        'aria-label',
        label
      );

      toggleButton.setAttribute(
        'data-tooltip',
        label
      );

      toggleButton.setAttribute(
        'aria-pressed',
        isHidden ? 'true' : 'false'
      );
    });
  }

  applyBrowserHintToOidcLinks();
  initBackupPasswordToggle();
</script>
@endsection
