<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.security-bootstrap')
    @vite('resources/css/vendor-fonts.css')
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patient Registration</title>
  <link rel="icon" type="image/png" href="{{ asset('images/PUPT-DMS-Logo.png') }}">

  <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #8B0000 0%, #660000 50%, #C9A84C 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px 16px;
      position: relative;
      overflow-x: hidden;
    }

    /* ── STARS ── */
    #stars {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
    }

    /* ── AMBIENT GLOW ── */
    .glow-orb {
      position: fixed;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.18;
      pointer-events: none;
      z-index: 0;
    }

    .glow-orb-1 {
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, #C9A84C, transparent 70%);
      top: -100px;
      left: -100px;
      animation: orbFloat1 12s ease-in-out infinite alternate;
    }

    .glow-orb-2 {
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, #8B0000, transparent 70%);
      bottom: -80px;
      right: -80px;
      animation: orbFloat2 15s ease-in-out infinite alternate;
    }

    @keyframes orbFloat1 {
      from {
        transform: translate(0, 0);
      }

      to {
        transform: translate(60px, 40px);
      }
    }

    @keyframes orbFloat2 {
      from {
        transform: translate(0, 0);
      }

      to {
        transform: translate(-40px, -60px);
      }
    }

    /* ── BG GRID LAYER ── */
    .bg-layer {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
    }

    .bg-layer::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(201, 168, 76, .06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201, 168, 76, .06) 1px, transparent 1px);
      background-size: 40px 40px;
    }

    /* ── FLOATING TEETH ── */
    .tooth-float {
      position: fixed;
      z-index: 0;
      pointer-events: none;
      opacity: 0.095;
    }

    .tooth-1 {
      top: 8%;
      left: 5%;
      animation: floatA 14s ease-in-out infinite alternate;
    }

    .tooth-2 {
      top: 60%;
      right: 4%;
      animation: floatB 18s ease-in-out infinite alternate;
    }

    .tooth-3 {
      bottom: 12%;
      left: 12%;
      animation: floatA 20s ease-in-out infinite alternate-reverse;
    }

    @keyframes floatA {
      from {
        transform: translateY(0) rotate(-8deg);
      }

      to {
        transform: translateY(20px) rotate(4deg);
      }
    }

    @keyframes floatB {
      from {
        transform: translateY(0) rotate(6deg);
      }

      to {
        transform: translateY(-18px) rotate(-3deg);
      }
    }

    /* ── MAIN CENTERING ── */
    main {
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10;
      min-height: calc(100vh - 120px);
    }

    /* ── CARD ── */
    .register-card {
      position: relative;
      width: min(1060px, 100%);
      display: grid;
      grid-template-columns: 1fr 1.3fr;
      border-radius: 24px;
      overflow: hidden;
      box-shadow:
        0 0 0 1px rgba(201, 168, 76, .15),
        0 24px 80px rgba(0, 0, 0, 0.178),
        0 8px 24px rgba(0, 0, 0, 0.157);
      animation: cardUp .9s cubic-bezier(.22, 1, .36, 1) .1s both;
    }

    @keyframes cardUp {
      from {
        opacity: 0;
        transform: translateY(32px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ── LEFT: PHOTO ── */
    .photo-side {
      position: relative;
      overflow: hidden;
      min-height: 540px;
    }

    .photo-side .campus-bg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      filter: brightness(.35) saturate(.5);
      transform: scale(1.02);
      transition: transform 12s ease;
    }

    .photo-side:hover .campus-bg {
      transform: scale(1.08);
    }

    .photo-side::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg,
          rgba(61, 0, 0, .8) 0%,
          rgba(90, 0, 0, .4) 40%,
          rgba(0, 0, 0, .15) 100%);
    }

    /* Medical cross accents */
    .medical-cross {
      position: absolute;
      z-index: 2;
      opacity: 0.07;
    }

    .medical-cross-1 {
      top: 10%;
      right: 8%;
      width: 80px;
    }

    .medical-cross-2 {
      bottom: 18%;
      left: 6%;
      width: 50px;
    }

    .photo-content {
      position: absolute;
      inset: 0;
      z-index: 4;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 32px 64px;
      text-align: center;
    }

    /* Shine text */
    .shine-text {
      background: linear-gradient(90deg,
          #6B0000 0%,
          #FFD700 35%,
          #fff 50%,
          #FFD700 65%,
          #6B0000 100%);
      background-size: 250% auto;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: shine 16s linear infinite;
      display: block;
    }

    @keyframes shine {
      from {
        background-position: 250% center;
      }

      to {
        background-position: -250% center;
      }
    }

    .photo-title-main {
      font-size: clamp(24px, 3.2vw, 38px);
      font-weight: 900;
      letter-spacing: .05em;
      line-height: 1;
    }

    .photo-title-sub {
      font-size: clamp(9px, 1.1vw, 13px);
      font-weight: 700;
      letter-spacing: .16em;
      margin-top: 6px;
    }

    /* Divider */
    .photo-divider {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin: 18px 0;
    }

    .photo-divider-line {
      width: 40px;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(201, 168, 76, .45));
    }

    .photo-divider-line.r {
      background: linear-gradient(90deg, rgba(201, 168, 76, .45), transparent);
    }

    .photo-divider-cross {
      color: rgba(201, 168, 76, .55);
      font-size: 10px;
    }

    /* Logos */
    .logo-row {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 20px;
    }

    .logo-circle {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .08);
      border: 1.5px solid rgba(201, 168, 76, .25);
      display: flex;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
      transition: border-color .3s;
    }

    .logo-circle:hover {
      border-color: rgba(201, 168, 76, .55);
    }

    .logo-circle img {
      width: 42px;
      height: 42px;
      object-fit: contain;
    }

    .logo-v-divider {
      width: 1px;
      height: 32px;
      background: linear-gradient(to bottom, transparent, rgba(201, 168, 76, .4), transparent);
    }

    /* Feature pills */
    .feature-pills {
      display: flex;
      flex-direction: column;
      gap: 8px;
      width: 100%;
      max-width: 260px;
    }

    .feature-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, .05);
      border: 1px solid rgba(201, 168, 76, .15);
      border-radius: 10px;
      padding: 9px 14px;
      backdrop-filter: blur(4px);
      text-align: left;
      transition: background .2s, border-color .2s;
    }

    .feature-pill:hover {
      background: rgba(255, 255, 255, .09);
      border-color: rgba(201, 168, 76, .3);
    }

    .feature-pill-icon {
      width: 26px;
      height: 26px;
      background: rgba(201, 168, 76, .12);
      border-radius: 7px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .feature-pill-icon i {
      font-size: 11px;
      color: #C9A84C;
    }

    .feature-pill-text {
      font-size: 11px;
      font-weight: 500;
      color: rgba(255, 255, 255, .65);
      line-height: 1.3;
    }

    .photo-badge {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 5;
      display: flex;
      align-items: center;
      gap: 6px;
      background: rgba(201, 168, 76, .1);
      border: 1px solid rgba(201, 168, 76, .25);
      border-radius: 20px;
      padding: 5px 14px;
      font-size: 9px;
      font-weight: 700;
      letter-spacing: .16em;
      text-transform: uppercase;
      color: rgba(255, 215, 0, .65);
      white-space: nowrap;
    }

    /* ── RIGHT: FORM ── */
    .form-side {
      background: #F4F4F4;
      padding: 40px 48px 44px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      overflow-y: auto;
    }

    .form-side::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 120px;
      height: 120px;
      background: radial-gradient(circle at top right, rgba(201, 168, 76, .08), transparent 70%);
      pointer-events: none;
    }

    .form-heading {
      font-size: 28px;
      font-weight: 800;
      color: #333;
      line-height: 1.15;
      margin-bottom: 4px;
    }

    .form-heading span {
      color: #8B0000;
    }

    .form-sub {
      font-size: 13px;
      color: #757575;
      margin-bottom: 22px;
      line-height: 1.5;
    }

    /* ── FORM GRID ── */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px 16px;
    }

    .col-span-2 {
      grid-column: span 2;
    }

    .col-span-1 {
      grid-column: span 1;
    }

    /* ── FIELD ── */
    .field {
      display: flex;
      flex-direction: column;
    }

    .field-label {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 600;
      color: #6B5B5B;
      margin-bottom: 7px;
      letter-spacing: .05em;
      text-transform: uppercase;
    }

    .field-label i {
      font-size: 10px;
      color: #8B0000;
      opacity: .7;
    }

    .field-input {
      width: 100%;
      padding: 12px 16px;
      background: #fff;
      border: 1.5px solid #E8E0D8;
      border-radius: 12px;
      font-family: 'Inter', sans-serif;
      font-size: 13.5px;
      color: #1A0A0A;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
      appearance: none;
      -webkit-appearance: none;
    }

    .field-input::placeholder {
      color: #CEC6BE;
      font-size: 13px;
    }

    .field-input:focus {
      border-color: #8B0000;
      box-shadow: 0 0 0 4px rgba(139, 0, 0, .07);
      background: #FFFDF9;
    }

    .field-input.border-red-500 {
      border-color: #EF4444;
    }

    .field-input.border-green-500 {
      border-color: #22C55E;
    }

    /* Password wrap */
    .pw-wrap {
      position: relative;
    }

    .pw-toggle {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #CEC6BE;
      transition: color .2s;
      display: flex;
      align-items: center;
      padding: 4px;
    }

    .pw-toggle:hover {
      color: #8B0000;
    }

    .pw-wrap .field-input {
      padding-right: 46px;
    }

    /* ── PASSWORD RULES ── */
    #passwordRules {
      margin-top: 8px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4px 12px;
      font-size: 11px;
      list-style: none;
    }

    #passwordRules li {
      display: flex;
      align-items: center;
      gap: 5px;
      color: #9CA3AF;
    }

    #passwordRules li.text-green-600 {
      color: #16A34A;
    }

    .fade-out {
      opacity: 0;
      transition: opacity 0.4s ease;
    }

    /* ── AUTOFILL FIX ── */
    input:-webkit-autofill,
    input:-webkit-autofill:focus {
      -webkit-box-shadow: 0 0 0 40px #fff inset !important;
      -webkit-text-fill-color: #1A0A0A !important;
    }

    /* ── FIELD ERROR ── */
    .field-error {
      font-size: 11px;
      color: #DC2626;
      margin-top: 3px;
      display: none;
    }

    .field-error.visible {
      display: block;
    }

    /* ── SUBMIT BUTTON ── */
    .btn-submit {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #8B0000 0%, #B22222 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-family: 'Inter', sans-serif;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: .04em;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      position: relative;
      overflow: hidden;
      transition: transform .18s, box-shadow .18s;
      box-shadow: 0 6px 20px rgba(139, 0, 0, .35);
    }

    .btn-submit::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255, 255, 255, .12) 0%, transparent 60%);
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(139, 0, 0, .45);
    }

    .btn-submit:active {
      transform: translateY(0);
    }

    .btn-arrow {
      width: 28px;
      height: 28px;
      background: rgba(255, 255, 255, .15);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform .2s, background .2s;
    }

    .btn-submit:hover .btn-arrow {
      transform: translateX(3px);
      background: rgba(255, 255, 255, .25);
    }

    /* ── LOGIN LINK ROW ── */
    .login-row {
      text-align: center;
      margin-top: 18px;
      padding-top: 16px;
      border-top: 1px solid #F0EAE2;
      font-size: 13px;
      color: #757575;
    }

    .login-row a {
      color: #8B0000;
      font-weight: 700;
      text-decoration: none;
      transition: opacity .2s;
    }

    .login-row a:hover {
      opacity: .75;
      text-decoration: underline;
    }

    /* ── TOAST ── */
    #toastContainer {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 99999;
      display: flex;
      flex-direction: column;
      gap: 10px;
      pointer-events: none;
    }

    .toast {
      min-width: 300px;
      background: white;
      border-radius: 14px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, .18);
      padding: 14px 18px 14px 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      opacity: 0;
      transform: translateX(340px);
      transition: all .35s cubic-bezier(.68, -.55, .265, 1.55);
      position: relative;
      overflow: hidden;
      pointer-events: all;
    }

    .toast::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
    }

    .toast.error::before {
      background: #8B0000;
    }

    .toast.success::before {
      background: #15803d;
    }

    .toast.show {
      opacity: 1;
      transform: translateX(0);
    }

    .toast.hide {
      opacity: 0;
      transform: translateX(340px);
    }

    .toast-icon-wrap {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .toast.error .toast-icon-wrap {
      background: rgba(139, 0, 0, .08);
    }

    .toast.success .toast-icon-wrap {
      background: rgba(21, 128, 61, .08);
    }

    .toast-icon {
      font-size: 17px;
    }

    .toast.error .toast-icon {
      color: #8B0000;
    }

    .toast.success .toast-icon {
      color: #15803d;
    }

    .toast-body {
      flex: 1;
      min-width: 0;
    }

    .toast-title {
      font-size: 13px;
      font-weight: 700;
      color: #1A0A0A;
    }

    .toast-msg {
      font-size: 12px;
      color: #888;
      margin-top: 2px;
      line-height: 1.4;
    }

    .toast-close {
      background: none;
      border: none;
      cursor: pointer;
      color: #CCC;
      font-size: 13px;
      flex-shrink: 0;
      padding: 2px 4px;
      transition: color .2s;
    }

    .toast-close:hover {
      color: #888;
    }

    /* ════════════════════════════
           LOGIN FOOTER
        ════════════════════════════ */
    .login-footer {
      width: 100%;
      margin-top: 30px;
      padding: 18px 16px 24px;
      display: flex;
      justify-content: center;
      z-index: 5;
    }

    .login-footer-inner {
      text-align: center;
      color: rgba(255, 255, 255, .75);
      font-size: 12px;
      letter-spacing: .04em;
    }

    .footer-divider {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 8px;
    }

    .footer-divider span {
      width: 40px;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(201, 168, 76, .6), transparent);
    }

    .footer-divider i {
      font-size: 11px;
      color: rgba(255, 215, 0, .65);
    }

    .footer-text {
      font-weight: 500;
    }

    .footer-year {
      color: rgba(255, 255, 255, .45);
    }

    .footer-links {
      margin-top: 6px;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      font-size: 11px;
    }

    .footer-links a {
      color: rgba(255, 215, 0, .75);
      text-decoration: none;
      transition: opacity .2s;
    }

    .footer-links a:hover {
      opacity: .7;
    }

    .footer-links .dot {
      width: 4px;
      height: 4px;
      background: rgba(255, 215, 0, .45);
      border-radius: 50%;
    }

    /* ════════════════════════════
           MOBILE RESPONSIVE
        ════════════════════════════ */
    @media (max-width: 767px) {
      body {
        padding: 16px;
        justify-content: flex-start;
        padding-top: 32px;
      }

      main {
        min-height: unset;
      }

      .register-card {
        grid-template-columns: 1fr;
        width: 100%;
        max-width: 440px;
        border-radius: 20px;
        margin: 0 auto;
      }

      .photo-side {
        min-height: unset;
        height: auto;
      }

      .photo-side .campus-bg {
        display: none;
      }

      .photo-side::after {
        display: none;
      }

      .medical-cross {
        display: none;
      }

      .photo-content {
        position: relative;
        padding: 28px 24px 24px;
        background: linear-gradient(135deg,
            rgba(61, 0, 0, .92) 0%,
            rgba(90, 0, 0, .85) 50%,
            rgba(30, 0, 0, .9) 100%);
      }

      .photo-title-main {
        font-size: 28px;
      }

      .photo-title-sub {
        font-size: 10px;
        letter-spacing: .14em;
      }

      .logo-circle {
        width: 52px;
        height: 52px;
      }

      .logo-circle img {
        width: 36px;
        height: 36px;
      }

      .feature-pills {
        display: none;
      }

      .photo-badge {
        position: relative;
        bottom: unset;
        left: unset;
        transform: none;
        margin: 12px auto 0;
        display: inline-flex;
      }

      .form-side {
        padding: 28px 24px 36px;
      }

      .form-heading {
        font-size: 22px;
      }

      .form-sub {
        margin-bottom: 18px;
        font-size: 12.5px;
      }

      .form-grid {
        grid-template-columns: 1fr;
        gap: 12px;
      }

      .col-span-2,
      .col-span-1 {
        grid-column: span 1;
      }

      .field-input {
        padding: 12px 14px;
        font-size: 15px;
      }

      .btn-submit {
        padding: 13px;
      }

      .login-row {
        font-size: 12.5px;
      }

      #toastContainer {
        left: 12px;
        right: 12px;
        top: 12px;
      }

      .toast {
        min-width: unset;
        transform: translateY(-80px);
      }

      .toast.show {
        transform: translateY(0);
      }

      .toast.hide {
        transform: translateY(-80px);
      }
    }

    @media (max-width: 380px) {
      body {
        padding: 12px;
        padding-top: 20px;
      }

      .form-side {
        padding: 24px 18px 28px;
      }

      .photo-content {
        padding: 22px 20px 20px;
      }

      .photo-title-main {
        font-size: 24px;
      }

      .logo-circle {
        width: 46px;
        height: 46px;
      }

      .logo-circle img {
        width: 32px;
        height: 32px;
      }
    }
  </style>
</head>

<body>

  <!-- Grid background -->
  <div class="bg-layer"></div>

  <!-- Floating teeth -->
  <div class="tooth-float tooth-1">
    <svg xmlns="http://www.w3.org/2000/svg" width="120" height="140" viewBox="0 0 100 110">
      <path
        d="M50 4 C34 4 18 15 16 32 C14 46 19 57 21 68 C23 79 25 96 32 98 C39 100 41 84 45 75 C47 69 49 66 50 66 C51 66 53 69 55 75 C59 84 61 100 68 98 C75 96 77 79 79 68 C81 57 86 46 84 32 C82 15 66 4 50 4Z"
        fill="#C9A84C" />
    </svg>
  </div>
  <div class="tooth-float tooth-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="160" height="190" viewBox="0 0 100 110">
      <path
        d="M50 4 C34 4 18 15 16 32 C14 46 19 57 21 68 C23 79 25 96 32 98 C39 100 41 84 45 75 C47 69 49 66 50 66 C51 66 53 69 55 75 C59 84 61 100 68 98 C75 96 77 79 79 68 C81 57 86 46 84 32 C82 15 66 4 50 4Z"
        fill="#8B0000" />
    </svg>
  </div>
  <div class="tooth-float tooth-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="90" height="110" viewBox="0 0 100 110">
      <path
        d="M50 4 C34 4 18 15 16 32 C14 46 19 57 21 68 C23 79 25 96 32 98 C39 100 41 84 45 75 C47 69 49 66 50 66 C51 66 53 69 55 75 C59 84 61 100 68 98 C75 96 77 79 79 68 C81 57 86 46 84 32 C82 15 66 4 50 4Z"
        fill="#C9A84C" />
    </svg>
  </div>

  <canvas id="stars"></canvas>
  <div class="glow-orb glow-orb-1"></div>
  <div class="glow-orb glow-orb-2"></div>
  <div id="toastContainer"></div>

  <main>
    <div class="register-card">

      <!-- LEFT: Photo Panel -->
      <div class="photo-side">
        <img src="{{ asset('images/PUP TAGUIG CAMPUS.jpg') }}" alt="PUP Taguig Campus" class="campus-bg">

        <!-- Medical cross accents -->
        <svg class="medical-cross medical-cross-1" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="22" y="0" width="16" height="60" rx="4" fill="white" />
          <rect x="0" y="22" width="60" height="16" rx="4" fill="white" />
        </svg>
        <svg class="medical-cross medical-cross-2" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="22" y="0" width="16" height="60" rx="4" fill="white" />
          <rect x="0" y="22" width="60" height="16" rx="4" fill="white" />
        </svg>

        <div class="photo-content">
          <h2>
            <span class="shine-text photo-title-main">PUP TAGUIG</span>
            <span class="shine-text photo-title-sub">DENTAL CLINIC</span>
          </h2>

          <div class="photo-divider">
            <div class="photo-divider-line"></div>
            <i class="fa-solid fa-plus photo-divider-cross"></i>
            <div class="photo-divider-line r"></div>
          </div>

          <!-- Logos -->
          <div class="logo-row">
            <div class="logo-circle">
              <img src="{{ asset('images/PUP.png') }}" alt="PUP">
            </div>
            <div class="logo-v-divider"></div>
            <div class="logo-circle">
              <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" alt="DMS">
            </div>
          </div>

          <!-- Feature pills -->
          <div class="feature-pills">
            <div class="feature-pill">
              <div class="feature-pill-icon"><i class="fa-solid fa-user-plus"></i></div>
              <span class="feature-pill-text">Create your free patient account</span>
            </div>
            <div class="feature-pill">
              <div class="feature-pill-icon"><i class="fa-solid fa-calendar-check"></i></div>
              <span class="feature-pill-text">Book appointments online, anytime</span>
            </div>
            <div class="feature-pill">
              <div class="feature-pill-icon"><i class="fa-solid fa-shield-heart"></i></div>
              <span class="feature-pill-text">Secure & private patient portal</span>
            </div>
          </div>

          <div class="photo-badge">
            <i class="fa-solid fa-tooth" data-ui-presentation="presentation-c42f23b8ec8e"></i>
            Patient Portal
          </div>
        </div>
      </div>

      <!-- RIGHT: Form Panel -->
      <div class="form-side">

        <h1 class="form-heading">Create <span>Account</span></h1>
        <p class="form-sub">Register a new patient account to get started.</p>

        <form method="POST" action="/register" id="registerForm">
          @csrf

          <div class="form-grid">

            <!-- Full Name -->
            <div class="field col-span-2">
              <label class="field-label"><i class="fa-solid fa-user"></i> Full Name</label>
              <input id="name" name="name" value="{{ old('name') }}" placeholder="Enter your full name" required
                class="field-input">
              <p id="name-error" class="field-error"></p>
            </div>

            <!-- Email -->
            <div class="field col-span-1">
              <label class="field-label"><i class="fa-solid fa-envelope"></i> Email</label>
              <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email"
                required class="field-input">
              <p id="email-error" class="field-error"></p>
            </div>

            <!-- Phone -->
            <div class="field col-span-1">
              <label class="field-label"><i class="fa-solid fa-phone"></i> Phone</label>
              <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter phone number"
                class="field-input">
              <p id="phone-error" class="field-error"></p>
            </div>

            <!-- Birthdate -->
            <div class="field col-span-1">
              <label class="field-label"><i class="fa-solid fa-calendar"></i> Birthdate</label>
              <input id="birthdate" type="date" name="birthdate" value="{{ old('birthdate') }}" required
                class="field-input">
              <p id="birthdate-error" class="field-error"></p>
            </div>

            <!-- Gender -->
            <div class="field col-span-1">
              <label class="field-label"><i class="fa-solid fa-venus-mars"></i> Gender</label>
              <select id="gender" name="gender" required class="field-input">
                <option value="">Select Gender</option>
                <option value="Male" {{ old('gender')=='Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ old('gender')=='Female' ? 'selected' : '' }}>Female</option>
              </select>
              <p id="gender-error" class="field-error"></p>
            </div>

            <!-- Password -->
            <div class="field col-span-2">
              <label class="field-label"><i class="fa-solid fa-lock"></i> Password</label>
              <div class="pw-wrap">
                <input id="password" type="password" name="password" placeholder="Enter your password" required
                  class="field-input">
                <button type="button" class="pw-toggle" data-ui-click="action-84cd2f54c0c6">
                  <svg id="eyePassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
              <ul id="passwordRules">
                <li id="rule-length"><i class="fa-solid fa-circle-xmark text-[10px]"></i> At least 8 characters</li>
                <li id="rule-letter"><i class="fa-solid fa-circle-xmark text-[10px]"></i> At least one letter</li>
                <li id="rule-number"><i class="fa-solid fa-circle-xmark text-[10px]"></i> At least one number</li>
                <li id="rule-special"><i class="fa-solid fa-circle-xmark text-[10px]"></i> One special symbol</li>
              </ul>
            </div>

            <!-- Confirm Password -->
            <div class="field col-span-2">
              <label class="field-label"><i class="fa-solid fa-lock"></i> Confirm Password</label>
              <div class="pw-wrap">
                <input id="confirmPassword" type="password" name="password_confirmation"
                  placeholder="Re-enter your password" required class="field-input">
                <button type="button" class="pw-toggle"
                  data-ui-click="action-5817faec28d5">
                  <svg id="eyeConfirmPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
              <p id="passwordMismatch" class="field-error"><i class="fa-solid fa-circle-xmark"></i> Passwords do not
                match</p>
              <p id="passwordMatch" class="field-error" data-ui-presentation="presentation-9ce5128fda59"><i
                  class="fa-solid fa-circle-check"></i> Passwords match</p>
            </div>

            <!-- Submit -->
            <div class="col-span-2" data-ui-presentation="presentation-36e81f861247">
              <button type="submit" class="btn-submit">
                Register
                <div class="btn-arrow">
                  <i class="fa-solid fa-user-plus" data-ui-presentation="presentation-025fe5129171"></i>
                </div>
              </button>
            </div>

            <!-- Login link -->
            <div class="login-row col-span-2">
              Already have an account? <a href="/login">Login here</a>
            </div>

          </div>
        </form>

      </div>
    </div>
  </main>

  <!-- ══════ FOOTER ══════ -->
  <footer class="login-footer">
    <div class="login-footer-inner">
      <div class="footer-divider">
        <span></span>
        <i class="fa-solid fa-shield-halved"></i>
        <span></span>
      </div>
      <p class="footer-text">
        <span class="footer-year">© 1998–2026</span>
        <strong>Polytechnic University of the Philippines</strong>
      </p>
      <div class="footer-links">
        <a href="https://www.pup.edu.ph/terms/" target="_blank">Terms of Use</a>
        <span class="dot"></span>
        <a href="https://www.pup.edu.ph/privacy/" target="_blank">Privacy Statement</a>
      </div>
    </div>
  </footer>

  @if($errors->any())
  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
      @foreach($errors -> all() as $error)
      showToast('Validation Error', "{{ $error }}", 'error');
      @endforeach
    });
  </script>
  @endif

  @if(session('error'))
  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
      showToast('Error', "{{ session('error') }}", 'error');
    });
  </script>
  @endif

  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    /* ══════ STARS ══════ */
    (function () {
      const canvas = document.getElementById('stars');
      const ctx = canvas.getContext('2d');
      let w, h, stars = [];

      function resize() {
        w = canvas.width = window.innerWidth;
        h = canvas.height = window.innerHeight;
        stars = Array.from({ length: 200 }, () => ({
          x: Math.random() * w,
          y: Math.random() * h,
          r: Math.random() * 1.6 + 0.3,
          v: Math.random() * 0.25 + 0.06,
          a: Math.random() * 0.7 + 0.3,
        }));
      }

      function draw() {
        ctx.clearRect(0, 0, w, h);
        for (const s of stars) {
          ctx.globalAlpha = s.a * 0.75;
          ctx.fillStyle = '#FFF8DC';
          ctx.beginPath();
          ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
          ctx.fill();
          s.y -= s.v;
          if (s.y < 0) { s.y = h; s.x = Math.random() * w; }
        }
        ctx.globalAlpha = 1;
        requestAnimationFrame(draw);
      }

      window.addEventListener('resize', resize);
      resize(); draw();
    })();

    /* ══════ TOAST ══════ */
    function showToast(title, message, type = 'error') {
      const container = document.getElementById('toastContainer');
      const t = document.createElement('div');
      t.className = `toast ${type}`;
      const icon = type === 'error'
        ? '<i class="fa-solid fa-circle-exclamation toast-icon"></i>'
        : '<i class="fa-solid fa-circle-check toast-icon"></i>';
      t.innerHTML = `
                <div class="toast-icon-wrap">${icon}</div>
                <div class="toast-body">
                    <div class="toast-title">${title}</div>
                    <div class="toast-msg">${message}</div>
                </div>
                <button class="toast-close" data-ui-click="action-6f467bb30429">
                    <i class="fa-solid fa-xmark"></i>
                </button>`;
      container.appendChild(t);
      requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
      setTimeout(() => dismissToast(t), type === 'success' ? 4000 : 5000);
    }

    function dismissToast(t) {
      t.classList.remove('show');
      t.classList.add('hide');
      setTimeout(() => { if (t.parentElement) t.remove(); }, 400);
    }

    /* ══════ VALIDATION HELPERS ══════ */
    function setError(inputEl, errorId, msg) {
      const e = document.getElementById(errorId);
      e.textContent = msg;
      e.classList.add('visible');
      inputEl.classList.add('border-red-500');
      inputEl.classList.remove('border-green-500');
    }

    function clearError(inputEl, errorId) {
      const e = document.getElementById(errorId);
      e.classList.remove('visible');
      inputEl.classList.remove('border-red-500');
      inputEl.classList.add('border-green-500');
    }

    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const birthdateInput = document.getElementById('birthdate');
    const genderInput = document.getElementById('gender');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');

    function validateName() {
      const v = nameInput.value.trim();
      if (v.length < 2) { setError(nameInput, 'name-error', 'Name must be at least 2 characters'); return false; }
      if (v.length > 255) { setError(nameInput, 'name-error', 'Name must not exceed 255 characters'); return false; }
      clearError(nameInput, 'name-error'); return true;
    }

    function validateEmail() {
      const pat = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailInput.value) { setError(emailInput, 'email-error', 'Email is required'); return false; }
      if (!pat.test(emailInput.value)) { setError(emailInput, 'email-error', 'Please enter a valid email'); return false; }
      clearError(emailInput, 'email-error'); return true;
    }

    function validatePhone() {
      const pat = /^[\d\s\-\+\(\)]+$/;
      if (phoneInput.value && !pat.test(phoneInput.value)) { setError(phoneInput, 'phone-error', 'Invalid phone number'); return false; }
      if (phoneInput.value && phoneInput.value.length > 20) { setError(phoneInput, 'phone-error', 'Phone too long'); return false; }
      clearError(phoneInput, 'phone-error'); return true;
    }

    function validateBirthdate() {
      const sel = new Date(birthdateInput.value);
      const today = new Date();
      const min = new Date(); min.setFullYear(today.getFullYear() - 120);
      if (!birthdateInput.value) { setError(birthdateInput, 'birthdate-error', 'Birthdate is required'); return false; }
      if (sel > today) { setError(birthdateInput, 'birthdate-error', 'Cannot be in the future'); return false; }
      if (sel < min) { setError(birthdateInput, 'birthdate-error', 'Please enter a valid date'); return false; }
      clearError(birthdateInput, 'birthdate-error'); return true;
    }

    function validateGender() {
      if (!genderInput.value) { setError(genderInput, 'gender-error', 'Please select a gender'); return false; }
      clearError(genderInput, 'gender-error'); return true;
    }

    function validatePasswordRules() {
      const v = password.value;
      return v.length >= 8 && /[A-Za-z]/.test(v) && /[0-9]/.test(v) && /[^A-Za-z0-9]/.test(v);
    }

    function validatePasswordMatch() {
      return confirmPassword.value && confirmPassword.value === password.value;
    }

    nameInput.addEventListener('blur', validateName);
    nameInput.addEventListener('input', validateName);
    emailInput.addEventListener('blur', validateEmail);
    emailInput.addEventListener('input', validateEmail);
    phoneInput.addEventListener('blur', validatePhone);
    phoneInput.addEventListener('input', validatePhone);
    birthdateInput.addEventListener('blur', validateBirthdate);
    birthdateInput.addEventListener('change', validateBirthdate);
    genderInput.addEventListener('change', validateGender);

    document.getElementById('registerForm').addEventListener('submit', function (e) {
      const nameOk = validateName();
      const emailOk = validateEmail();
      const phoneOk = validatePhone();
      const birthOk = validateBirthdate();
      const genderOk = validateGender();
      const rulesOk = validatePasswordRules();
      const matchOk = validatePasswordMatch();

      if (!rulesOk) showToast('Password Error', 'Password does not meet the requirements.', 'error');
      if (!matchOk) showToast('Password Mismatch', 'Passwords do not match.', 'error');

      if (!nameOk || !emailOk || !phoneOk || !birthOk || !genderOk || !rulesOk || !matchOk) {
        e.preventDefault();
      }
    });

    /* ══════ PASSWORD RULES ══════ */
    const rulesBox = document.getElementById('passwordRules');
    const mismatchTxt = document.getElementById('passwordMismatch');
    const matchTxt = document.getElementById('passwordMatch');
    let hideTimer, mismatchTimer;

    const rules = {
      length: document.getElementById('rule-length'),
      letter: document.getElementById('rule-letter'),
      number: document.getElementById('rule-number'),
      special: document.getElementById('rule-special')
    };

    function updateRule(el, ok) {
      const icon = el.querySelector('i');
      if (ok) {
        el.style.color = '#16A34A';
        icon.className = 'fa-solid fa-circle-check';
      } else {
        el.style.color = '';
        icon.className = 'fa-solid fa-circle-xmark';
      }
    }

    password.addEventListener('input', function () {
      const v = password.value;
      const checks = {
        length: v.length >= 8,
        letter: /[A-Za-z]/.test(v),
        number: /[0-9]/.test(v),
        special: /[^A-Za-z0-9]/.test(v)
      };
      updateRule(rules.length, checks.length);
      updateRule(rules.letter, checks.letter);
      updateRule(rules.number, checks.number);
      updateRule(rules.special, checks.special);

      rulesBox.style.display = 'grid';
      rulesBox.style.opacity = '1';
      clearTimeout(hideTimer);

      if (Object.values(checks).every(Boolean)) {
        hideTimer = setTimeout(() => {
          rulesBox.classList.add('fade-out');
          setTimeout(() => { rulesBox.style.display = 'none'; }, 400);
        }, 600);
      }
    });

    confirmPassword.addEventListener('input', function () {
      clearTimeout(mismatchTimer);
      mismatchTimer = setTimeout(() => {
        if (!confirmPassword.value) {
          mismatchTxt.style.display = 'none';
          matchTxt.style.display = 'none';
          return;
        }
        if (confirmPassword.value === password.value) {
          mismatchTxt.style.display = 'none';
          matchTxt.style.display = 'block';
          confirmPassword.classList.remove('border-red-500');
          confirmPassword.classList.add('border-green-500');
        } else {
          matchTxt.style.display = 'none';
          mismatchTxt.style.display = 'block';
          confirmPassword.classList.add('border-red-500');
          confirmPassword.classList.remove('border-green-500');
        }
      }, 200);
    });

    /* ══════ PASSWORD TOGGLE ══════ */
    function togglePassword(inputId, eyeId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(eyeId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML =
          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.042-3.368M6.223 6.223A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.132 5.411M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>' +
          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"/>';
      } else {
        input.type = 'password';
        icon.innerHTML =
          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>' +
          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
      }
    }
  </script>

  {{-- SESSION TOASTS --}}
  @if(session('error'))
  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', () => showToast('Error', "{{ session('error') }}", 'error'));
  </script>
  @endif

  @if(session('success'))
  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', () => showToast('Account Created!', "{{ session('success') }}", 'success'));
  </script>
  @endif

  @if(session('registered'))
  <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
      showToast('Account Created Successfully!', 'Redirecting you to login&hellip;', 'success');
      setTimeout(() => { window.location.href = '/login'; }, 3000);
    });
  </script>
  @endif

</body>

</html>