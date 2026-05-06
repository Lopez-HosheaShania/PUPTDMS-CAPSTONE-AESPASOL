@extends('layouts.patient')

@section('title', 'Patient Dashboard | PUP Taguig Dental Clinic')

@section('styles')
<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in {
        animation: fadeIn 0.6s ease-out forwards;
    }

    @keyframes fadeUp {
        0% {
            opacity: 0;
            transform: translateY(10px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-up {
        animation: fadeUp 0.6s ease-out forwards;
    }

    @keyframes shimmerBtn {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    .shimmer-btn {
        background: linear-gradient(110deg, #660000 25%, rgba(255, 80, 80, .87) 37%, #660000 63%);
        background-size: 200% 100%;
        animation: shimmerBtn 10s linear infinite;
    }

    @keyframes wave {
        0% {
            transform: rotate(0);
        }

        20% {
            transform: rotate(14deg);
        }

        40% {
            transform: rotate(-8deg);
        }

        60% {
            transform: rotate(14deg);
        }

        80% {
            transform: rotate(-4deg);
        }

        100% {
            transform: rotate(0);
        }
    }

    .wave-hand {
        transform-origin: 70% 70%;
        animation: wave 2.5s ease-in-out infinite;
    }

    @keyframes spinSlow {
        from {
            transform: rotate(0);
        }

        to {
            transform: rotate(360deg);
        }
    }

    @keyframes floatMoon {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-3px);
        }
    }

    @keyframes driftCloud {

        0%,
        100% {
            transform: translateX(0);
        }

        50% {
            transform: translateX(3px);
        }
    }

    .request-doc-card {
        will-change: transform;
        transform-style: preserve-3d;
    }

    .request-doc-card .request-ripple {
        position: absolute;
        border-radius: 999px;
        transform: scale(0);
        background: rgba(139, 0, 0, 0.18);
        pointer-events: none;
        animation: requestRipple 0.55s ease-out forwards;
        z-index: 1;
    }

    .request-doc-card>* {
        position: relative;
        z-index: 2;
    }

    .upcoming-card-polished {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, 0.10), transparent 30%),
            radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.08), transparent 26%),
            linear-gradient(145deg, #ffffff, #fff7f7) !important;
    }

    .upcoming-card-polished::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, transparent, rgba(255, 255, 255, .28), transparent);
        transform: translateX(-120%) skewX(-18deg);
        pointer-events: none;
    }

    .check-dates-btn {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        background:
            linear-gradient(135deg, rgba(139, 0, 0, 0.95), rgba(179, 0, 0, 0.88)) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, .18);
        box-shadow: 0 10px 24px rgba(139, 0, 0, .18);
        transition: transform .22s ease, box-shadow .22s ease, filter .22s ease;
    }

    .check-dates-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 32px rgba(139, 0, 0, .28);
        filter: brightness(1.05);
    }

    .check-dates-btn:active {
        transform: scale(.97);
    }

    .check-dates-btn i {
        animation: checkDatesArrowBounce 1.45s ease-in-out infinite;
    }

    @keyframes checkDatesArrowBounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(3px);
        }
    }

    .check-dates-btn .btn-ripple {
        position: absolute;
        border-radius: 999px;
        background: rgba(255, 255, 255, .28);
        transform: scale(0);
        animation: btnRipple .55s ease-out;
        pointer-events: none;
        z-index: -1;
    }

    @keyframes btnRipple {
        to {
            transform: scale(3);
            opacity: 0;
        }
    }

    [data-theme="dark"] #mainContent .upcoming-card-polished {
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, 0.22), transparent 34%),
            radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.08), transparent 28%),
            linear-gradient(145deg, #0D1117, #111827) !important;
        border-color: rgba(255, 255, 255, .10) !important;
    }

    [data-theme="dark"] #mainContent .check-dates-btn {
        background:
            linear-gradient(135deg, #5A0000, #8B0000, #A31212) !important;
        box-shadow: 0 14px 30px rgba(0, 0, 0, .38);
    }

    @keyframes pulseGlow {
        0% {
            box-shadow: 0 0 0 0 rgba(139, 0, 0, .3);
        }

        100% {
            box-shadow: 0 0 0 12px rgba(139, 0, 0, 0);
        }
    }

    @keyframes requestRipple {
        to {
            transform: scale(3.2);
            opacity: 0;
        }
    }

    [data-theme="dark"] .request-doc-card .request-ripple {
        background: rgba(252, 165, 165, 0.16);
    }

    /* tablet */
    @media only screen and (min-width: 768px) and (max-width: 1199px) {
        #mainContent {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .dashboard-grid-tight {
            gap: 1rem !important;
        }

        .greeting-banner-inner {
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .greeting-banner-actions {
            width: 100%;
        }

        .greeting-banner-actions a {
            flex: 1;
        }
    }

    /* mobile 320px and up */
    @media only screen and (min-width: 320px) and (max-width: 600px) {
        #mainContent {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            padding-bottom: 1rem !important;
        }

        .dashboard-grid-tight {
            gap: 0.85rem !important;
        }

        .request-doc-card {
            padding: 0.85rem !important;
            border-radius: 0.85rem !important;
        }

        .request-doc-card>div {
            gap: 0.75rem !important;
        }

        .request-doc-card .icon-box {
            width: 3rem !important;
            height: 3rem !important;
            border-radius: 0.8rem !important;
        }

        .request-doc-card h3 {
            font-size: 0.92rem !important;
            line-height: 1.2 !important;
        }

        .request-doc-card p {
            font-size: 0.76rem !important;
            line-height: 1.45 !important;
        }

        .request-doc-card .doc-arrow {
            font-size: 0.9rem !important;
        }

        #requestDocsContainer .p-4,
        #requestDocsContainer .sm\:p-5 {
            padding: 0.9rem !important;
        }

        .greeting-name-line {
            font-size: clamp(1.25rem, 8vw, 1.55rem) !important;
        }

        #greetingText {
            font-size: 0.9rem !important;
        }

        .greeting-banner-copy p {
            font-size: 0.72rem !important;
        }
    }

    @media (hover: none) {
        .request-doc-card:hover {
            transform: none;
        }
    }

    .request-doc-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(139, 0, 0, 0.08), transparent 60%);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .request-doc-card:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 20px 40px rgba(139, 0, 0, 0.12);
        border-color: rgba(139, 0, 0, 0.25);
    }

    .request-doc-card:hover::before {
        opacity: 1;
    }

    .request-doc-card:hover .icon-box {
        transform: scale(1.08);
        box-shadow: 0 10px 25px rgba(139, 0, 0, 0.25);
    }

    .request-doc-card:hover .doc-arrow {
        transform: translateX(3px);
        opacity: 1;
    }

    .greet-spin {
        animation: spinSlow 8s linear infinite;
        display: inline-block;
    }

    .greet-float {
        animation: floatMoon 3s ease-in-out infinite;
        display: inline-block;
    }

    .greet-drift {
        animation: driftCloud 3s ease-in-out infinite;
        display: inline-block;
    }

    .greeting-row {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
        flex-wrap: nowrap;
    }

    .greeting-banner {
        position: relative;
        overflow: hidden;
        width: 100%;
        border-radius: 24px;
        padding: 22px 24px;
        background: linear-gradient(135deg,
                rgba(139, 0, 0, 0.96) 0%,
                rgba(102, 0, 0, 0.93) 48%,
                rgba(179, 52, 18, 0.92) 100%);
        box-shadow: 0 14px 40px rgba(14, 116, 144, 0.10);
        border: 1px solid rgba(255, 255, 255, .20);
    }

    .greeting-banner::before,
    .greeting-banner::after {
        content: '';
        position: absolute;
        border-radius: 999px;
        background: rgba(255, 215, 0, 0.07);
        pointer-events: none;
    }

    .greeting-banner::before {
        width: 240px;
        height: 240px;
        top: -145px;
        left: -40px;
    }

    .greeting-banner::after {
        width: 220px;
        height: 220px;
        right: -55px;
        bottom: -150px;
    }

    .greeting-banner-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .greeting-banner-copy {
        min-width: 0;
    }

    .greeting-heading {
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin: 0;
        color: #fff;
    }

    .greeting-line {
        display: block;
    }

    #greetingText {
        font-size: 1.45rem;
        font-weight: 500;
        line-height: 1.15;
    }

    .greeting-name-line {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        font-size: clamp(1.55rem, 3vw, 2.35rem);
        font-weight: 800;
        line-height: 1.15;
    }

    .greeting-banner-copy p {
        color: rgba(255, 255, 255, .92);
        font-size: 0.95rem;
        margin-top: 0.45rem;
        font-weight: 500;
    }

    .greeting-banner-actions {
        display: flex;
        align-items: center;
        flex-shrink: 0;
        gap: 12px;
    }

    .book-appointment-btn {
        position: relative;
        overflow: hidden;
        padding: 10px 18px;
        min-width: 182px;
        font-size: 0.95rem;
        font-weight: 700;
        white-space: nowrap;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.449);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .18);
        background: linear-gradient(110deg, #660000 25%, #8B0000 45%, #660000 65%);
        background-size: 200% 100%;
        animation: shimmerBtn 4s linear infinite;
    }

    .book-appointment-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 60%;
        height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.35), transparent);
        transform: skewX(-20deg);
        animation: shine 2.8s infinite;
    }

    @keyframes shine {
        0% {
            left: -100%;
        }

        100% {
            left: 120%;
        }
    }

    .book-appointment-btn i,
    .book-appointment-btn span {
        position: relative;
        z-index: 1;
    }

    .book-appointment-btn:hover {
        background: linear-gradient(110deg, #4d0000 25%, #7a0000 45%, #4d0000 65%);
        background-size: 200% 100%;
        transform: translateY(-1px);
    }

    @media only screen and (max-width: 600px) {
        .greeting-row {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem !important;
            margin-bottom: 1rem !important;
        }

        .greeting-banner {
            width: 100%;
            padding: 14px 12px;
            border-radius: 18px;
        }

        .greeting-banner-inner {
            flex-direction: column;
            align-items: stretch;
            gap: 0.85rem;
        }

        .greeting-banner-actions {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        #greetingText {
            font-size: 0.95rem;
            font-weight: 600;
        }

        .greeting-name-line {
            font-size: 1.45rem;
            line-height: 1.1;
        }

        .greeting-banner-copy p {
            font-size: 0.74rem;
            margin-top: 6px;
            opacity: 0.9;
        }

        .book-appointment-btn {
            width: 100%;
            min-width: 0;
            padding: 10px 14px;
            font-size: 0.84rem;
        }

        .upcoming-card-mobile {
            border-radius: 18px !important;
            margin-bottom: 1rem !important;
        }

        .upcoming-card-mobile .upcoming-card-header {
            padding: 0.8rem 0.9rem !important;
            gap: 0.5rem !important;
        }

        .upcoming-card-mobile .upcoming-card-header-icon {
            width: 1.95rem !important;
            height: 1.95rem !important;
            font-size: 0.88rem !important;
        }

        .upcoming-card-mobile .upcoming-card-title {
            font-size: 0.9rem !important;
            line-height: 1.2 !important;
        }

        .upcoming-card-mobile .upcoming-status-pill {
            font-size: 0.62rem !important;
            padding: 0.28rem 0.48rem !important;
            border-radius: 999px !important;
        }

        .upcoming-card-mobile .upcoming-card-body {
            padding: 0.8rem 0.9rem 0.9rem !important;
        }
    }

    @media only screen and (min-width: 600px) and (max-width: 767px) {

        .greeting-row {
            flex-direction: column;
            align-items: stretch;
            gap: 0.9rem;
        }

        .greeting-banner {
            padding: 18px 16px;
            border-radius: 20px;
        }

        .greeting-banner-inner {
            flex-direction: column;
            align-items: stretch;
            gap: 0.9rem;
        }

        .greeting-banner-actions {
            width: 100%;
        }

        .book-appointment-btn {
            width: 100%;
            min-width: 0;
        }
    }

    @media only screen and (min-width: 768px) and (max-width: 991px) {

        .greeting-row {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .greeting-banner {
            padding: 20px 20px;
            border-radius: 22px;
        }

        .greeting-banner-inner {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        .greeting-banner-actions {
            width: auto;
        }

        .book-appointment-btn {
            width: auto;
            min-width: 170px;
        }
    }

    @media only screen and (max-width: 600px) {
        #upcomingAppointmentWrapper .upcoming-card-mobile {
            margin-bottom: 0.9rem !important;
        }

        #upcomingAppointmentWrapper {
            gap: 0.55rem !important;
        }

        #upcomingAppointmentWrapper {
            padding: 0.7rem 0.75rem !important;
        }
    }

    @media only screen and (min-width: 1200px) {
        #mainContent {
            padding-top: 78px !important;
        }

        .greeting-row {
            margin-bottom: 0.75rem !important;
        }

        .greeting-banner {
            border-radius: 20px !important;
            padding: 18px 20px !important;
        }

        #greetingText {
            font-size: 1.2rem !important;
        }

        .greeting-name-line {
            font-size: clamp(1.9rem, 2.4vw, 2.5rem) !important;
        }

        .greeting-banner-copy p {
            font-size: 0.88rem !important;
            margin-top: 0.35rem !important;
        }

        .greeting-banner-actions {
            gap: 10px !important;
        }

        .book-appointment-btn {
            min-width: 164px !important;
            padding: 9px 16px !important;
            font-size: 0.88rem !important;
        }

        .dashboard-card-compact {
            border-radius: 16px !important;
        }

        .dashboard-card-header-compact {
            padding: 14px 18px !important;
        }

        .dashboard-card-body-compact {
            padding: 16px 18px !important;
        }

        .dashboard-section-tight {
            gap: 1rem !important;
        }

        .dashboard-grid-tight {
            gap: 1.25rem !important;
        }
    }

    @media only screen and (min-width: 1200px) {

        #requestDocsContainer>div,
        #dentalOverviewContainer>div {
            min-height: 100%;
        }
    }

    @keyframes cardReveal {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.985);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes iconFloatSoft {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-3px);
        }
    }

    @keyframes statSoftRise {
        0% {
            opacity: 0;
            transform: translateY(8px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .greeting-time-line {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
    }

    .greeting-time-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.82);
    }

    .greeting-time-icon.is-sun {
        color: #fde68a;
        animation: spinSlow 8s linear infinite;
    }

    .greeting-time-icon.is-moon {
        color: rgba(255, 255, 255, 0.78);
        animation: floatMoon 3s ease-in-out infinite;
    }

    .profile-toggle-btn {
        transition: all 0.2s ease;
    }

    .profile-toggle-btn:hover {
        transform: translateY(-1px);
    }

    .card-reveal {
        animation: cardReveal 0.45s ease-out both;
    }

    .card-lift {
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }

    .card-lift:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 36px rgba(139, 0, 0, 0.08);
    }

    .stat-soft-rise {
        animation: statSoftRise 0.45s ease-out both;
    }

    .stat-soft-rise:nth-child(2) {
        animation-delay: 0.06s;
    }

    .stat-soft-rise:nth-child(3) {
        animation-delay: 0.12s;
    }

    @media (prefers-reduced-motion: reduce) {

        .card-reveal,
        .card-lift,
        .stat-soft-rise,
        .wave-hand,
        .fade-in,
        .fade-up {
            animation: none !important;
            transition: none !important;
            transform: none !important;
        }
    }

    @media only screen and (min-width: 1200px) {

        #profileSkeletonContainer,
        #calendarSkeletonContainer {
            min-height: 100%;
        }
    }

    @media only screen and (min-width: 1200px) {
        #profileSkeletonContainer>div {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
    }

    .upcoming-timeline-dot {
        box-shadow: 0 0 0 3px #fff;
    }

    @keyframes upcomingGlowPulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.22);
            transform: scale(1);
        }

        50% {
            box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
            transform: scale(1.08);
        }
    }

    .upcoming-live-dot {
        animation: upcomingGlowPulse 2.2s ease-in-out infinite;
    }

    .upcoming-tooth-glass {
        position: relative;
        overflow: hidden;
        background:
            linear-gradient(135deg, rgba(139, 0, 0, 0.92) 0%, rgba(102, 0, 0, 0.82) 55%, rgba(179, 52, 18, 0.72) 100%);
        border: 1px solid rgba(255, 255, 255, 0.26);
        box-shadow:
            0 10px 24px rgba(139, 0, 0, 0.18),
            inset 0 1px 0 rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .upcoming-tooth-glass::before {
        content: '';
        position: absolute;
        inset: 1px;
        border-radius: inherit;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.20), rgba(255, 255, 255, 0.04));
        pointer-events: none;
    }

    .upcoming-tooth-glass::after {
        content: '';
        position: absolute;
        top: 7px;
        left: 8px;
        width: 52%;
        height: 34%;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        filter: blur(1px);
        pointer-events: none;
    }

    .glass-icon-red {
        position: relative;
        overflow: hidden;
        background:
            linear-gradient(135deg, rgba(139, 0, 0, 0.92) 0%, rgba(102, 0, 0, 0.82) 55%, rgba(179, 52, 18, 0.72) 100%);
        border: 1px solid rgba(255, 255, 255, 0.25);
        box-shadow:
            0 8px 20px rgba(139, 0, 0, 0.18),
            inset 0 1px 0 rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .glass-icon-red::before {
        content: '';
        position: absolute;
        inset: 1px;
        border-radius: inherit;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.05));
    }

    .glass-icon-red::after {
        content: '';
        position: absolute;
        top: 6px;
        left: 7px;
        width: 55%;
        height: 35%;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        filter: blur(1px);
    }

    .glass-icon-red i {
        position: relative;
        z-index: 2;
        color: #fff;
    }

    .upcoming-reminder-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        min-height: 42px;
        padding: 0.65rem 0.9rem;
        border-radius: 0.9rem;
        background: linear-gradient(135deg, rgba(255, 247, 247, 0.98) 0%, rgba(255, 252, 252, 1) 100%);
        border: 1px solid rgba(139, 0, 0, 0.10);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .upcoming-reminder-icon {
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(139, 0, 0, 0.08);
        color: #8B0000;
        flex-shrink: 0;
    }

    .upcoming-reminder-copy {
        display: flex;
        flex-direction: column;
        min-width: 0;
        line-height: 1.15;
    }

    .upcoming-reminder-label {
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #9a3412;
    }

    .upcoming-reminder-text {
        font-size: 0.76rem;
        font-weight: 700;
        color: #7f1d1d;
    }

    @media only screen and (max-width: 640px) {
        .upcoming-reminder-chip {
            width: 100%;
            justify-content: flex-start;
        }
    }

    :root {
        --bg-main: #000D1A;
        --bg-card: #0D1117;
        --bg-card-soft: #111827;
        --bg-panel: #161B22;
        --bg-panel-soft: #1C2128;

        --border-dark: rgba(255, 255, 255, 0.10);

        --text-primary: #F3F4F6;
        --text-secondary: #C9D1D9;
        --text-muted: #8B949E;

        --brand-red: #8B0000;
        --brand-red-soft: rgba(139, 0, 0, 0.18);

        --theme-speed: 180ms;
        --theme-curve: cubic-bezier(0.4, 0, 0.2, 1);
    }

    .upcoming-ready-line {
        background: linear-gradient(90deg, #8B0000, #FCA5A5);
        box-shadow: 0 0 10px rgba(139, 0, 0, 0.25);
    }

    [data-theme="dark"] #mainContent .upcoming-ready-line {
        background: linear-gradient(90deg, #FCA5A5, rgba(252, 165, 165, 0.15));
        box-shadow: 0 0 12px rgba(252, 165, 165, 0.25);
    }

    [data-theme="dark"] body {
        background-color: var(--bg-main) !important;
        color: var(--text-primary);
    }

    [data-theme="dark"] #mainContent {
        background: var(--bg-main) !important;
        color: var(--text-primary);
    }

    [data-theme="dark"] #mainContent .bg-white,
    [data-theme="dark"] #mainContent .skeleton-card,
    [data-theme="dark"] #mainContent #profileSkeletonContainer,
    [data-theme="dark"] #mainContent #requestDocsContainer>div,
    [data-theme="dark"] #mainContent .dental-overview-card,
    [data-theme="dark"] #mainContent .bg-gradient-to-br.from-\[\#ffffff\] {
        background: linear-gradient(145deg, var(--bg-card), var(--bg-card-soft)) !important;
        border-color: var(--border-dark) !important;
        box-shadow: 0 18px 38px rgba(0, 0, 0, 0.35) !important;
    }

    [data-theme="dark"] #mainContent .bg-gradient-to-br,
    [data-theme="dark"] #mainContent .bg-gradient-to-b,
    [data-theme="dark"] #mainContent .bg-gradient-to-r,
    [data-theme="dark"] #mainContent .bg-gray-50,
    [data-theme="dark"] #mainContent .bg-gray-100,
    [data-theme="dark"] #mainContent .bg-red-50,
    [data-theme="dark"] #mainContent .bg-amber-50,
    [data-theme="dark"] #mainContent .bg-emerald-50 {
        background: var(--bg-panel) !important;
    }

    [data-theme="dark"] #mainContent .border-gray-200,
    [data-theme="dark"] #mainContent .border-gray-100,
    [data-theme="dark"] #mainContent .border-red-100,
    [data-theme="dark"] #mainContent .border-amber-100,
    [data-theme="dark"] #mainContent .border-emerald-100,
    [data-theme="dark"] #mainContent .border-\[\#eadede\],
    [data-theme="dark"] #mainContent .border-\[\#efe3e3\] {
        border-color: var(--border-dark) !important;
    }

    [data-theme="dark"] #mainContent .text-gray-900,
    [data-theme="dark"] #mainContent .text-gray-800,
    [data-theme="dark"] #mainContent .text-gray-700 {
        color: var(--text-primary) !important;
    }

    [data-theme="dark"] #mainContent .text-gray-600,
    [data-theme="dark"] #mainContent .text-gray-500 {
        color: var(--text-secondary) !important;
    }

    [data-theme="dark"] #mainContent .text-gray-400,
    [data-theme="dark"] #mainContent .text-gray-300 {
        color: var(--text-muted) !important;
    }

    [data-theme="dark"] #mainContent .text-\[\#8B0000\],
    [data-theme="dark"] #mainContent .text-red-800 {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent .text-\[\#c96a00\] {
        color: #FCD34D !important;
    }

    [data-theme="dark"] #mainContent .text-emerald-700 {
        color: #86EFAC !important;
    }

    [data-theme="dark"] #mainContent .rounded-full.bg-white\/10 {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    [data-theme="dark"] #mainContent .upcoming-reminder-chip {
        background: var(--bg-panel-soft) !important;
        border-color: var(--border-dark) !important;
        box-shadow: none !important;
    }

    [data-theme="dark"] #mainContent .upcoming-reminder-icon {
        background: var(--brand-red-soft) !important;
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent .greeting-banner {
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, 0.30), transparent 34%),
            linear-gradient(135deg, rgba(13, 17, 23, 0.96), rgba(22, 27, 34, 0.92)) !important;
        border: 1px solid rgba(255, 255, 255, 0.10) !important;
        box-shadow:
            0 18px 38px rgba(0, 0, 0, 0.38),
            inset 0 1px 0 rgba(255, 255, 255, 0.07) !important;
    }

    [data-theme="dark"] #mainContent dialog .modal-box {
        background: var(--bg-card) !important;
        color: var(--text-primary) !important;
        border: 1px solid var(--border-dark) !important;
    }

    [data-theme="dark"] #mainContent .profile-emergency-panel {
        background: #161B22 !important;
        border-top-color: rgba(255, 255, 255, 0.10) !important;
    }

    [data-theme="dark"] #mainContent .profile-emergency-panel p,
    [data-theme="dark"] #mainContent .profile-emergency-panel span {
        color: #C9D1D9 !important;
    }

    [data-theme="dark"] #mainContent .profile-emergency-panel .text-red-800 {
        color: #FCA5A5 !important;
    }

    [data-theme="dark"] #mainContent .overview-status-text {
        color: #D98A8A !important;
    }

    [data-theme="dark"] .request-doc-card {
        background: linear-gradient(145deg, #0D1117, #161B22);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    [data-theme="dark"] .request-doc-card:hover {
        box-shadow: 0 22px 50px rgba(0, 0, 0, 0.6);
        border-color: rgba(139, 0, 0, 0.45);
    }

    [data-theme="dark"] .request-doc-card::before {
        background: radial-gradient(circle at top right, rgba(139, 0, 0, 0.18), transparent 60%);
    }

    @keyframes greetingGradientFlow {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    .greeting-banner {
        background:
            radial-gradient(circle at top left, rgba(255, 215, 0, 0.10), transparent 28%),
            linear-gradient(270deg, #8B0000, #660000, #9f1d1d, #5A0000) !important;
        background-size: 260% 260% !important;
        animation: greetingGradientFlow 13s ease infinite;
    }

    .greeting-insight-chip {
        transition: transform .2s ease, background .2s ease, border-color .2s ease;
    }

    .greeting-insight-chip:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, .16);
        border-color: rgba(255, 255, 255, .28);
    }

    .greeting-scroll-cue {
        color: rgba(255, 255, 255, .72);
        transition: all .2s ease;
    }

    .greeting-scroll-cue:hover {
        color: #fff;
        transform: translateY(2px);
    }

    .greeting-name-line:hover {
        letter-spacing: .3px;
        transition: letter-spacing .25s ease;
    }

    .book-appointment-btn:hover {
        box-shadow: 0 0 24px rgba(255, 255, 255, .16), 0 14px 30px rgba(139, 0, 0, .35);
    }

    [data-theme="dark"] #mainContent .greeting-banner {
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, .34), transparent 34%),
            radial-gradient(circle at bottom right, rgba(245, 158, 11, .10), transparent 28%),
            linear-gradient(270deg, #0D1117, #161B22, #111827, #1C2128) !important;
        background-size: 260% 260% !important;
    }
</style>
@endsection

@section('content')
@php
$notifications = collect($notifications ?? []);
$notifCount = $notifications->count();
$homeRecords = ($records ?? collect())
->filter(function ($r) {
return in_array(strtolower($r->status ?? ''), ['completed', 'cancelled']);
})
->map(function ($r) {
return [
'service' => $r->service_type,
'date' => $r->appointment_date ? \Carbon\Carbon::parse($r->appointment_date)->format('F d, Y') : '',
'time' => $r->appointment_time ?? '',
'status' => strtolower($r->status ?? ''),
'duration' => $r->duration ?? '',
'remarks' => $r->remarks ?? '',
'oral' => $r->oral_examination ?? '',
'diagnosis' => $r->diagnosis ?? '',
'prescription' => $r->prescription ?? '',
];
})
->values();

$calendarAppointments = [];
foreach (
collect($appointments ?? [])->filter(function ($appt) {
$status = strtolower($appt->status ?? '');
return !in_array($status, ['completed', 'cancelled']);
})
as $appt
) {
$calendarAppointments[\Carbon\Carbon::parse($appt->appointment_date)->format('Y-m-d')] =
'My Appointment: ' .
$appt->service_type .
' • ' .
\Carbon\Carbon::parse($appt->appointment_time)->format('g:i A');
}

$dashboardDisplayName = ucwords(
strtolower(optional($patient)->name ?? (auth()->user()->name ?? 'Patient User')),
);
$dashboardPatientImage = optional($patient)->profile_image ?? null;
$dashboardUserImage = auth()->user()->profile_image ?? null;

if (!empty($dashboardPatientImage)) {
$dashboardAvatarUrl = asset('storage/' . $dashboardPatientImage);
} elseif (!empty($dashboardUserImage)) {
$dashboardAvatarUrl = asset('storage/' . $dashboardUserImage);
} else {
$dashboardAvatarUrl =
'https://ui-avatars.com/api/?name=' .
urlencode($dashboardDisplayName) .
'&background=8B0000&color=ffffff&bold=true';
}

$recordCount = collect($homeRecords ?? [])->count();

$latestRecordDate = $recordCount ? collect($homeRecords)->first()['date'] ?? null : null;

$pendingDocumentRequests = collect($documentRequests ?? [])
->whereIn('status', ['pending', 'processing'])
->count();

$profileCompletion = collect([
optional($patient)->name,
optional($patient)->birthdate,
optional($patient)->gender,
optional($patient)->phone,
optional($patient)->email,
optional(optional($patient)->medicalHistory)->emergency_person,
optional(optional($patient)->medicalHistory)->emergency_number,
])
->filter(fn($v) => !blank($v))
->count();

$profileCompletionPercent = round(($profileCompletion / 7) * 100);

$nextVisitText =
isset($upcomingAppointment) && $upcomingAppointment
? \Carbon\Carbon::parse($upcomingAppointment->appointment_date)->format('M d, Y')
: 'No appointment yet';
@endphp

<main id="mainContent"
    class="pt-[78px] px-3 md:px-5 xl:px-6 pb-5 page-enter min-h-screen flex-1 bg-gray-50 dark:bg-[#000D1A] text-gray-900 dark:text-[#F3F4F6]">
    <div class="w-full fade-in space-y-4 xl:space-y-5">

        <x-dashboard-loading-status />

        <div id="greetingContent" class="greeting-row">
            <div class="greeting-banner w-full">
                <div class="banner-wave"></div>
                <div class="greeting-banner-inner">
                    <div class="greeting-banner-copy min-w-0">
                        <h1 class="greeting-heading">
                            <span class="greeting-line greeting-time-line">
                                <span id="greetingIcon" class="greeting-time-icon">
                                    <i class="fa-solid fa-sun"></i>
                                </span>
                                <span id="greetingText"></span>
                            </span>
                            <span class="greeting-line greeting-name-line">
                                <span id="patientName"></span>
                                <i class="fa-solid fa-hand text-yellow-300 wave-hand"></i>
                            </span>
                        </h1>

                        <p id="greetingSmartMessage" class="mt-2">
                            {{ isset($upcomingAppointment) && $upcomingAppointment
                            ? 'You’re all set for your next dental visit. Please arrive a few minutes early.'
                            : 'Ready when you are. Choose a convenient schedule and keep your dental care on track.' }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                class="greeting-insight-chip inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                                <i class="fa-solid fa-circle-info"></i>
                                {{ isset($upcomingAppointment) && $upcomingAppointment ? 'Appointment scheduled' :
                                'Ready to book' }}
                            </span>

                            <span
                                class="greeting-insight-chip inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                                <i class="fa-regular fa-calendar"></i>
                                Next Visit: {{ $nextVisitText }}
                            </span>

                            <span
                                class="greeting-insight-chip inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                                <i class="fa-solid fa-tooth"></i>
                                {{ $recordCount > 0 ? 'Last Visit: ' . ($latestRecordDate ?? 'Available') : 'No dental
                                record yet' }}
                            </span>

                        </div>
                    </div>

                    <div class="greeting-banner-actions">
                        <a href="{{ route('patient.book.appointment') }}"
                            class="book-appointment-btn inline-flex items-center justify-center gap-2 rounded-full text-white shadow transition">
                            <i class="fa-solid fa-calendar-plus"></i>
                            <span>Book Appointment</span>
                        </a>

                        <a href="{{ route('patient.record') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white/20">
                            <i class="fa-solid fa-folder-open"></i>
                            <span>View Records</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div id="upcomingAppointmentWrapper" class="skeleton-section">
            <div class="dashboard-glass skeleton-card skeleton-shell skeleton-fade-swap rounded-[1rem] overflow-hidden">

                <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 skeleton-circle"></div>
                        <div class="h-4 w-40 skeleton-line"></div>
                    </div>
                    <div class="h-8 w-24 skeleton-pill hidden sm:block"></div>
                </div>

                <div class="px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div class="skeleton-inner-gap">
                            <div class="h-3 w-20 skeleton-line"></div>
                            <div class="h-5 w-full skeleton-line"></div>
                        </div>
                        <div class="skeleton-inner-gap">
                            <div class="h-3 w-28 skeleton-line"></div>
                            <div class="h-5 w-full skeleton-line"></div>
                        </div>
                        <div class="skeleton-inner-gap">
                            <div class="h-3 w-20 skeleton-line"></div>
                            <div class="h-5 w-full skeleton-line"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-stretch dashboard-grid-tight">
            <div class="xl:col-span-4">
                <div id="profileSkeletonContainer"
                    class="dashboard-glass rounded-[1rem] overflow-hidden skeleton-section h-full skeleton-shell skeleton-fade-swap">
                    <div>
                        <div class="bg-gray-200 px-5 sm:px-6 py-5">
                            <div class="h-3 w-28 skeleton-line mb-3"></div>
                            <div class="h-6 w-44 skeleton-line mb-2"></div>
                            <div class="h-4 w-72 max-w-full skeleton-line"></div>
                        </div>

                        <div class="p-4 sm:p-5 space-y-3">
                            <div class="rounded-[0.85rem] border border-gray-100 p-4">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 skeleton-block flex-shrink-0"></div>
                                    <div class="flex-1 space-y-2">
                                        <div class="h-5 w-40 skeleton-line"></div>
                                        <div class="h-4 w-full skeleton-line"></div>
                                        <div class="flex gap-2 pt-1">
                                            <div class="h-6 w-20 skeleton-pill"></div>
                                            <div class="h-6 w-20 skeleton-pill"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[0.85rem] border border-gray-100 p-4">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 skeleton-block flex-shrink-0"></div>
                                    <div class="flex-1 space-y-2">
                                        <div class="h-5 w-36 skeleton-line"></div>
                                        <div class="h-4 w-full skeleton-line"></div>
                                        <div class="flex gap-2 pt-1">
                                            <div class="h-6 w-16 skeleton-pill"></div>
                                            <div class="h-6 w-16 skeleton-pill"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-8">
                <div id="calendarSkeletonContainer" class="w-full h-full min-h-[420px] skeleton-fade-swap"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-stretch dashboard-grid-tight mt-5">
            <div class="xl:col-span-5">
                <div id="requestDocsContainer" class="h-full">
                    <div
                        class="dashboard-glass rounded-[1rem] overflow-hidden h-full skeleton-shell skeleton-fade-swap">
                        <div class="p-4 sm:p-5">
                            <div class="flex items-center gap-4 border border-gray-100 rounded-[0.85rem] p-4  mb-4">
                                <div class="w-12 h-12 skeleton-block flex-shrink-0"></div>
                                <div class="flex-1 space-y-3">
                                    <div class="h-4 w-32 skeleton-line"></div>
                                    <div class="h-3 w-full skeleton-line"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 border border-gray-100 rounded-[0.85rem] p-4 ">
                                <div class="w-12 h-12 skeleton-block flex-shrink-0"></div>
                                <div class="flex-1 space-y-3">
                                    <div class="h-4 w-32 skeleton-line"></div>
                                    <div class="h-3 w-full skeleton-line"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-7">
                <div id="dentalOverviewContainer" class="h-full skeleton-fade-swap">
                    <div class="dashboard-glass skeleton-shell rounded-[1rem] overflow-hidden h-full">
                        <div class="px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-white/10">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="h-3 w-44 skeleton-line mb-3"></div>
                                    <div class="h-6 w-48 skeleton-line mb-2"></div>
                                    <div class="h-4 w-72 max-w-full skeleton-line"></div>
                                </div>
                                <div class="hidden sm:block w-11 h-11 skeleton-block"></div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 xl:grid-cols-3 gap-2.5">
                                <div class="rounded-[0.85rem] border border-gray-100 dark:border-white/10 px-3 py-3">
                                    <div class="h-3 w-20 skeleton-line mb-2"></div>
                                    <div class="h-5 w-12 skeleton-line"></div>
                                </div>
                                <div class="rounded-[0.85rem] border border-gray-100 dark:border-white/10 px-3 py-3">
                                    <div class="h-3 w-24 skeleton-line mb-2"></div>
                                    <div class="h-5 w-28 skeleton-line"></div>
                                </div>
                                <div
                                    class="rounded-[0.85rem] border border-gray-100 dark:border-white/10 px-3 py-3 col-span-2 xl:col-span-1">
                                    <div class="h-3 w-16 skeleton-line mb-2"></div>
                                    <div class="h-5 w-full skeleton-line"></div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 sm:p-5">
                            <div class="space-y-3">
                                <div class="border border-gray-100 dark:border-white/10 rounded-[0.85rem] p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 skeleton-block flex-shrink-0"></div>
                                        <div class="flex-1 space-y-2">
                                            <div class="h-4 w-36 skeleton-line"></div>
                                            <div class="h-3 w-48 skeleton-line"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border border-gray-100 dark:border-white/10 rounded-[0.85rem] p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 skeleton-block flex-shrink-0"></div>
                                        <div class="flex-1 space-y-2">
                                            <div class="h-4 w-32 skeleton-line"></div>
                                            <div class="h-3 w-40 skeleton-line"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <dialog id="activeAppointmentModal" class="modal">
                <div class="modal-box p-8 rounded-[1.5rem] bg-white text-center shadow-2xl w-[min(92vw,400px)]">
                    <div
                        class="mx-auto mb-5 w-20 h-20 rounded-full bg-red-50 flex items-center justify-center border-4 border-white shadow-sm">
                        <i class="fa-solid fa-calendar-xmark text-[#8B0000] text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-800 mb-3">Active Appointment</h3>
                    <p class="text-sm text-gray-500 mb-8 leading-relaxed">You already have an active appointment
                        scheduled.
                        Please complete or cancel it before booking a new one.</p>
                    <div class="flex flex-col gap-3">
                        <a href="{{ route('patient.appointment.index') }}"
                            class="w-full py-3.5 rounded-[0.85rem] bg-[#8B0000] text-white font-bold hover:bg-[#660000] transition-colors shadow-md">View
                            My Appointments</a>
                        <button id="closeActiveApptModalBtn" type="button"
                            class="w-full py-3.5 rounded-[0.85rem] bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition-colors">Close</button>
                    </div>
                </div>
            </dialog>

        </div>
</main>

@include('components.appointment-calendar-script', [
'mode' => 'patient-dashboard',
'renderStyle' => 'patient',
'calendarContainerId' => 'calendarSkeletonContainer',
'dateInputId' => null,
'timeInputId' => null,
'slotEndpoint' => route('book.appointment.slots'),
'blockedDates' => $unavailableDates ?? [],
'appointmentCountsPerDay' => $appointmentCountsPerDay ?? [],
'philippineHolidays' => $philippineHolidays ?? [],
'personalAppointments' => $calendarAppointments ?? [],
'useDynamicScheduleRules' => false,
'disallowToday' => false,
'allowToggleOffDate' => false,
])
@endsection

@section('scripts')
<script>
    function renderGreeting() {
        const nameEl = document.getElementById("patientName");
        const greetingEl = document.getElementById("greetingText");
        const iconEl = document.getElementById("greetingIcon");

        if (!nameEl || !greetingEl || !iconEl) return;

        nameEl.textContent = "{{ auth()->user()->name ?? 'Patient' }}";

        const h = new Date().getHours();

        iconEl.classList.remove("is-sun", "is-moon");

        if (h < 12) {
            greetingEl.textContent = "Good Morning";
            iconEl.innerHTML = '<i class="fa-solid fa-sun"></i>';
            iconEl.classList.add("is-sun");
        } else if (h < 18) {
            greetingEl.textContent = "Good Afternoon";
            iconEl.innerHTML = '<i class="fa-solid fa-sun"></i>';
            iconEl.classList.add("is-sun");
        } else {
            greetingEl.textContent = "Good Evening";
            iconEl.innerHTML = '<i class="fa-solid fa-moon"></i>';
            iconEl.classList.add("is-moon");
        }
    }

    var HOME_RECORDS = @json($homeRecords ?? []);

    @php
    if (!isset($upcomingAppointment) || empty($upcomingAppointment)) {
        $upcomingAppointment = collect($appointments ?? [])
            -> filter(function ($appt) {
                $status = strtolower($appt -> status ?? '');
                return !in_array($status, ['completed', 'cancelled', 'declined']);
            })
            -> filter(function ($appt) {
                return \Carbon\Carbon:: parse($appt -> appointment_date) -> startOfDay() -> gte(\Carbon\Carbon:: today());
            })
            -> sortBy(function ($appt) {
                return \Carbon\Carbon:: parse($appt -> appointment_date. ' '. ($appt -> appointment_time ?? '00:00:00'));
            })
            -> first();
    }

    $upcomingJs = null;
    if (isset($upcomingAppointment) && $upcomingAppointment) {
        $uD = \Carbon\Carbon:: parse($upcomingAppointment -> appointment_date);
        $uT = \Carbon\Carbon:: parse($upcomingAppointment -> appointment_time);
        $upcomingJs = [
            'exists' => true,
            'service' => $upcomingAppointment -> service_type ?? '—',
            'date' => $uD -> format('M d, Y'),
            'time_raw' => $upcomingAppointment -> appointment_time,
            'time_fmt' => $uT -> format('g:i A'),
            'dentist' => $upcomingAppointment -> dentist_name ?? 'Dr. Nelson P. Angeles',
            'status' => ucfirst($upcomingAppointment -> status),
            'isRescheduled' => strtolower($upcomingAppointment -> status) === 'rescheduled',
            'indexUrl' => route('patient.appointment.index'),
            'bookUrl' => route('patient.book.appointment'),
        ];
    } else {
        $upcomingJs = [
            'exists' => false,
            'bookUrl' => route('patient.book.appointment'),
        ];
    }

    $profileRows = [['Date of Birth', isset($patient -> birthdate) ?\Carbon\Carbon:: parse($patient -> birthdate) -> format('F d, Y') : '—'], ['Age', $patient -> age ?? '—'], ['Gender', $patient -> gender ?? '—'], ['Contact', $patient -> phone ?? '—'], ['Email', $patient -> email ?? '—']];
    @endphp

    var UPCOMING_DATA = @json($upcomingJs);
    var PATIENT_NAME = "{{ urlencode($patient->name ?? 'Guest') }}";

    var PROFILE_DATA = {
        name: "{{ ucwords(strtolower($patient->name ?? 'Guest')) }}",
        roleLabel: "{{ $patient->faculty_code ? 'Faculty' : ($patient->student_no ? 'Student' : 'Patient') }}",
        facultyCode: "{{ $patient->faculty_code ?? '' }}",
        studentNo: "{{ $patient->student_no ?? '' }}",
        age: "{{ $patient->age ?? (\Carbon\Carbon::parse($patient->birthdate ?? now())->age ?? 'N/A') }}",
        birthdate: "{{ isset($patient->birthdate) ? \Carbon\Carbon::parse($patient->birthdate)->format('M d, Y') : 'N/A' }}",
        gender: "{{ $patient->gender ?? 'N/A' }}",
        contact: "{{ $patient->phone ?? 'N/A' }}",
        email: "{{ $patient->email ?? 'N/A' }}",
        emergencyName: "{{ optional($patient->medicalHistory)->emergency_person ?? 'Not specified' }}",
        emergencyNumber: "{{ optional($patient->medicalHistory)->emergency_number ?? 'N/A' }}",
        emergencyRelation: "{{ optional($patient->medicalHistory)->emergency_relation ?? '' }}",
        hasAlert: @json(
            (isset($patient -> medicalHistory -> diseaseAnswers) && $patient -> medicalHistory -> diseaseAnswers -> count() > 0) ||
            (isset($patient -> medicalHistoryAnswers) &&
                $patient -> medicalHistoryAnswers -> where('question.code', 'allergy_medicine') -> where('answer_bool', true) -> count() > 0)),
        avatar: @json($dashboardAvatarUrl)
    };

    var ROUTE_BOOK = "{{ route('patient.book.appointment') }}";
    var ROUTE_RECORD = "{{ route('patient.record') }}";

    @if (session('activeAppointmentModal'))
        document.addEventListener("DOMContentLoaded", function () {
            var modal = document.getElementById("activeAppointmentModal");
            var closeBtn = document.getElementById("closeActiveApptModalBtn");
            if (!modal) return;
            modal.showModal();
            modal.addEventListener('click', function (e) {
                var box = modal.querySelector('.modal-box');
                if (box && !box.contains(e.target)) e.preventDefault();
            });
            modal.addEventListener('cancel', function (e) {
                e.preventDefault();
            });
            if (closeBtn) closeBtn.addEventListener("click", function () {
                modal.close();
            });
        });
    @endif

    document.addEventListener('DOMContentLoaded', function () {
        const termsModal = document.getElementById('termsModal');
        const termsCheckbox = document.getElementById('termsCheckbox');
        const termsContinueBtn = document.getElementById('termsContinueBtn');
        const quickAction = new URLSearchParams(window.location.search).get('quick_action');

        if (termsCheckbox && termsContinueBtn) {
            termsCheckbox.checked = false;
            termsContinueBtn.disabled = true;

            termsCheckbox.addEventListener('change', function () {
                termsContinueBtn.disabled = !this.checked;
            });
        }

        @if (session('show_terms_modal'))
            if (termsModal) {
                termsModal.showModal();
            }
        @endif

        renderGreeting();
        initRecordModal();

        if (quickAction === 'record') {
            setTimeout(() => {
                document.getElementById('dentalHealthRecordModal')?.showModal();
            }, 150);
        }

        if (quickAction === 'clearance') {
            setTimeout(() => {
                document.getElementById('dentalClearanceModal')?.showModal();
            }, 150);
        }

        if (quickAction) {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('quick_action');
            window.history.replaceState({}, '', cleanUrl.toString());
        }

        runEnterpriseLoading([
            {
                label: 'Loading calendar and appointment details',
                tasks: [
                    () => {
                        window.setDashboardLoadingStatus('Loading calendar and appointment details', 30);
                        if (typeof renderCalendar === 'function') renderCalendar();
                    },
                    renderUpcomingAppointment
                ]
            },
            {
                label: 'Loading profile information',
                tasks: [
                    () => {
                        window.setDashboardLoadingStatus('Loading profile information', 58);
                        renderProfile();
                    }
                ]
            },
            {
                label: 'Loading records and document services',
                tasks: [
                    () => {
                        window.setDashboardLoadingStatus('Loading records and document services', 82);
                        renderRequestDocs();
                        setTimeout(initRequestDocInteractions, 80);
                    },
                    renderRecords
                ]
            }
        ], {
            initialDelay: 450,
            phaseGap: 260,
            taskGap: 130
        });
    });

    function acceptTerms() {
        const termsModal = document.getElementById('termsModal');
        if (termsModal) {
            termsModal.close();
        }
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
            '&quot;').replace(/'/g, '&#039;');
    }

    function formatTime(raw) {
        if (!raw) return '—';
        raw = String(raw).trim();
        if (/[AaPp][Mm]$/.test(raw)) return raw;
        var m = raw.match(/^(\d{1,2}):(\d{2})/);
        if (!m) return raw;
        var h = parseInt(m[1], 10),
            mn = m[2],
            ampm = h >= 12 ? 'PM' : 'AM',
            hr = h % 12 || 12;
        return hr + ':' + mn + ' ' + ampm;
    }

    function shortDate(raw) {
        if (!raw) return '—';
        return String(raw).replace(
            /^(January|February|March|April|May|June|July|August|September|October|November|December)/,
            function (s) {
                return s.slice(0, 3);
            }
        );
    }

    function maskPhone(value) {
        value = String(value || '').trim();
        if (!value || value === 'N/A') return 'N/A';

        var digits = value.replace(/\D/g, '');
        if (digits.length <= 4) return value;

        return digits.slice(0, 2) + '••• ••• ' + digits.slice(-4);
    }

    function maskEmail(value) {
        value = String(value || '').trim();
        if (!value || value === 'N/A') return 'N/A';

        var parts = value.split('@');
        if (parts.length !== 2) return value;

        var local = parts[0];
        var domain = parts[1];

        var maskedLocal = local.length <= 2 ?
            local.charAt(0) + '•' :
            local.slice(0, 2) + '•••';

        return maskedLocal + '@' + domain;
    }

    function maskIdCode(value) {
        value = String(value || '').trim();
        if (!value || value === 'N/A') return 'N/A';
        if (value.length <= 4) return '••' + value.slice(-2);
        return value.slice(0, 2) + '••••' + value.slice(-2);
    }

    function setMaskedContent(id, maskedValue, rawValue, isMasked) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = isMasked ? maskedValue : rawValue;
        el.setAttribute('data-masked', isMasked ? 'true' : 'false');
    }

    function toggleAllSensitive(button) {
        var isMasked = button.getAttribute('data-masked') !== 'false';
        var nextMasked = !isMasked;

        setMaskedContent('maskedIdentityValue', window.profileMaskedState.identityMasked, window.profileMaskedState
            .identityRaw, nextMasked);
        setMaskedContent('maskedContactValue', window.profileMaskedState.contactMasked, window.profileMaskedState
            .contactRaw, nextMasked);
        setMaskedContent('maskedEmailValue', window.profileMaskedState.emailMasked, window.profileMaskedState.emailRaw,
            nextMasked);
        setMaskedContent('maskedEmergencyNumber', window.profileMaskedState.emergencyMasked, window.profileMaskedState
            .emergencyRaw, nextMasked);

        button.setAttribute('data-masked', nextMasked ? 'true' : 'false');
        button.innerHTML = nextMasked ?
            '<i class="fa-regular fa-eye"></i>' :
            '<i class="fa-regular fa-eye-slash"></i>';
    }

    function renderUpcomingAppointment() {
        var wrapper = document.getElementById('upcomingAppointmentWrapper');
        if (!wrapper) return;

        var d = UPCOMING_DATA;

        if (d.exists) {
            var statusPillCls = d.isRescheduled ?
                'bg-yellow-100 text-yellow-800 border-yellow-200' :
                'bg-green-100 text-green-800 border-green-200';

            var statusDotCls = d.isRescheduled ? 'bg-yellow-500' : 'bg-green-500';
            
            var statusDarkPill = d.isRescheduled ? 
                'dark:bg-yellow-400/20 dark:text-yellow-100 dark:border-yellow-400/30' : 
                'dark:bg-emerald-500/20 dark:text-emerald-100 dark:border-emerald-500/30';

            swapSkeletonContent('upcomingAppointmentWrapper',
                '<div class="upcoming-card-polished bg-white dark:bg-[#161B22] rounded-[1rem] border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">' +

                '<div class="px-4 sm:px-5 py-4 sm:py-4.5">' +
                '<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto] gap-4 xl:gap-5 items-center">' +

                '<div class="min-w-0">' +
                '<div class="flex items-start gap-3">' +

                '<div class="relative flex-shrink-0 mt-0.5">' +
                '<div class="upcoming-tooth-glass w-12 h-12 rounded-[0.95rem] text-white flex items-center justify-center">' +
                '<i class="fa-solid fa-tooth text-[15px] relative z-[1] drop-shadow-[0_1px_2px_rgba(0,0,0,0.22)]"></i>' +
                '</div>' +
                '<span class="upcoming-live-dot absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full ' + statusDotCls + ' border-2 border-white dark:border-[#161B22]"></span>' +
                '</div>' +

                '<div class="min-w-0 flex-1">' +
                '<div class="flex flex-wrap items-center gap-2">' +
                '<h3 class="text-lg sm:text-[1.15rem] font-extrabold text-gray-900 dark:text-[#F3F4F6] leading-tight truncate">' + escapeHtml(d.service) + '</h3>' +
                '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ' + statusPillCls + ' ' + statusDarkPill + '">' +
                '<span class="w-1.5 h-1.5 rounded-full ' + statusDotCls + '"></span>' +
                escapeHtml(d.status) +
                '</span>' +
                '</div>' +

                '<div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600 dark:text-gray-400">' +
                '<span class="inline-flex items-center gap-2">' +
                '<i class="fa-regular fa-calendar text-gray-400 dark:text-gray-500"></i>' +
                '<span class="font-medium">' + escapeHtml(d.date) + '</span>' +
                '</span>' +

                '<span class="inline-flex items-center gap-2">' +
                '<i class="fa-regular fa-clock text-gray-400 dark:text-gray-500"></i>' +
                '<span class="font-medium">' + escapeHtml(d.time_fmt) + '</span>' +
                '</span>' +

                '<span class="inline-flex items-center gap-2 min-w-0">' +
                '<i class="fa-solid fa-user-doctor text-gray-400 dark:text-gray-500"></i>' +
                '<span class="font-medium truncate">' + escapeHtml(d.dentist) + '</span>' +
                '</span>' +
                '</div>' +

                '<div class="mt-3 flex items-center gap-3">' +
                '<div class="h-[2px] w-10 rounded-full bg-gradient-to-r from-red-200 to-red-300 dark:from-red-900/50 dark:to-red-800/50"></div>' +
                '<span class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Upcoming Visit</span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +

                '<div class="xl:min-w-[180px]">' +
                '<div class="flex flex-col sm:flex-row xl:flex-col items-stretch gap-2.5">' +
                '<div class="upcoming-reminder-chip">' +
                '<span class="upcoming-reminder-icon">' +
                '<i class="fa-regular fa-bell text-[12px]"></i>' +
                '</span>' +
                '<span class="text-[0.76rem] font-bold text-[#7f1d1d] dark:text-[#FCA5A5]">Please arrive 10 minutes early</span>' +
                '</div>' +

                '<a href="' + escapeHtml(d.indexUrl) +
                '" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-[0.85rem] bg-[#8B0000] hover:bg-[#660000] text-white text-sm font-bold transition-all duration-300 shadow-sm hover:-translate-y-0.5">' +
                '<span>Manage Appointment</span>' +
                '<i class="fa-solid fa-arrow-right text-[11px]"></i>' +
                '</a>' +
                '</div>' +
                '</div>' +

                '</div>' +
                '</div>' +

                '</div>'
            );
            return;
        }

        swapSkeletonContent('upcomingAppointmentWrapper',
            '<div class="upcoming-card-polished bg-white dark:bg-[#161B22] rounded-[1rem] border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden">' +
            '<div class="px-4 sm:px-5 py-4">' +
            '<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto] gap-4 items-center">' +

            '<div class="flex items-start gap-3">' +
            '<div class="upcoming-tooth-glass w-12 h-12 rounded-[0.95rem] text-white flex items-center justify-center flex-shrink-0">' +
            '<i class="fa-regular fa-calendar text-base relative z-[1] drop-shadow-[0_1px_2px_rgba(0,0,0,0.22)]"></i>' +
            '</div>' +

            '<div class="min-w-0">' +
            '<h3 class="text-lg sm:text-[1.1rem] font-extrabold text-gray-900 dark:text-[#F3F4F6]">No upcoming appointment</h3>' +
            '<p class="mt-1 text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-[560px]">Choose a preferred date and time from the calendar, or book now to secure your next dental visit.</p>' +
            '<div class="mt-3 flex items-center gap-3">' +
            '<div class="upcoming-ready-line h-[2px] w-10 rounded-full"></div>' +
            '<span class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Ready when you are</span>' +
            '</div>' +
            '</div>' +
            '</div>' +

            '<div class="flex flex-col sm:flex-row items-stretch gap-2.5 xl:min-w-[180px]">' +
            '<button type="button" onclick="scrollToCalendar(event)" class="check-dates-btn inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-[0.85rem] text-sm font-bold">' +
            '<i class="fa-solid fa-arrow-down"></i>' +
            '<span>Check Available Dates</span>' +
            '</button>' +
            '</div>' +

            '</div>' +
            '</div>' +
            '</div>'
        );
    }

    function renderProfile() {
        var pData = PROFILE_DATA;

        var hasEmergency = pData.emergencyName && pData.emergencyName !== 'Not specified';

        var roleBadge = '';
        var identityLabel = '';
        var identityRaw = '';
        var identityMasked = '';

        if (pData.facultyCode && pData.facultyCode !== 'null') {
            roleBadge =
                '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 text-[#8B0000] border border-red-100 text-xs font-bold">' +
                '<i class="fa-solid fa-user-tie"></i> Faculty' +
                '</span>';
            identityLabel = 'Faculty Code';
            identityRaw = pData.facultyCode;
            identityMasked = maskIdCode(pData.facultyCode);
        } else if (pData.studentNo && pData.studentNo !== 'null') {
            roleBadge =
                '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 text-[#8B0000] border border-red-100 text-xs font-bold">' +
                '<i class="fa-solid fa-user-graduate"></i> Student' +
                '</span>';
            identityLabel = 'Student No';
            identityRaw = pData.studentNo;
            identityMasked = maskIdCode(pData.studentNo);
        } else {
            roleBadge =
                '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 text-[#8B0000] border border-red-100 text-xs font-bold">' +
                '<i class="fa-solid fa-user"></i> Patient' +
                '</span>';
            identityLabel = '';
            identityRaw = '';
            identityMasked = '';
        }

        var maskedContact = maskPhone(pData.contact);
        var maskedEmail = maskEmail(pData.email);
        var maskedEmergency = maskPhone(pData.emergencyNumber);

        window.profileMaskedState = {
            identityRaw: identityRaw || 'N/A',
            identityMasked: identityMasked || 'N/A',
            contactRaw: pData.contact || 'N/A',
            contactMasked: maskedContact || 'N/A',
            emailRaw: pData.email || 'N/A',
            emailMasked: maskedEmail || 'N/A',
            emergencyRaw: pData.emergencyNumber || 'N/A',
            emergencyMasked: maskedEmergency || 'N/A'
        };

        var globalToggle =
            '<button type="button" id="profileSensitiveToggle" data-masked="true" onclick="toggleAllSensitive(this)" class="profile-toggle-btn inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-500 hover:text-[#8B0000] transition flex-shrink-0" aria-label="Toggle sensitive info">' +
            '<i class="fa-regular fa-eye text-sm"></i>' +
            '</button>';

        var identityRow = identityLabel ?
            '<div class="flex justify-between items-start gap-3">' +
            '<span class="text-gray-400 font-semibold text-xs flex items-center gap-2 mt-0.5 flex-shrink-0 w-[92px]">' +
            '<i class="fa-regular fa-id-badge w-3"></i> ' + escapeHtml(identityLabel) +
            '</span>' +
            '<span id="maskedIdentityValue" data-masked="true" class="text-gray-800 font-medium text-right break-words leading-snug flex-1 min-w-0">' +
            escapeHtml(identityMasked) +
            '</span>' +
            '</div>' :
            '';

        var emergencySection = hasEmergency ?
            '<div class="space-y-1">' +
            '<p class="text-sm font-bold text-gray-900">' + escapeHtml(pData.emergencyName) + '</p>' +
            '<div class="flex items-center justify-between gap-3">' +
            '<p class="text-xs font-medium text-gray-600">' +
            (pData.emergencyRelation ? '<span class="text-gray-400">(' + escapeHtml(pData.emergencyRelation) +
                ')</span>' : '') +
            '</p>' +
            '<span id="maskedEmergencyNumber" data-masked="true" class="text-xs font-medium text-gray-700 text-right">' +
            escapeHtml(maskedEmergency) + '</span>' +
            '</div>' +
            '</div>' :
            '<div class="text-center py-2">' +
            '<i class="fa-solid fa-user-plus text-red-300 text-lg mb-1"></i>' +
            '<p class="text-xs text-gray-400 font-medium">No emergency contact added</p>' +
            '</div>';

        swapSkeletonContent('profileSkeletonContainer',
            '<div class="dashboard-card-polished dashboard-glass overflow-hidden h-full flex flex-col rounded-[1rem]">' +
            '<div class="h-20 bg-gradient-to-r from-[#8B0000] to-[#b30000] relative"></div>' +

            '<div class="px-4 pb-4 relative flex flex-col items-center mt-[-34px]">' +
            '<div class="relative mb-3">' +
            '<img src="' + pData.avatar +
            '" alt="Profile" class="w-[68px] h-[68px] rounded-full object-cover border-4 border-white shadow-md bg-white">' +
            '</div>' +

            '<div class="mt-1 flex items-center justify-center gap-2 max-w-full">' +
            '<h2 class="text-[17px] font-extrabold text-gray-900 text-center leading-tight break-words">' +
            escapeHtml(pData.name) +
            '</h2>' +
            globalToggle +
            '</div>' +

            '<div class="mt-3 flex flex-wrap items-center justify-center gap-2">' +
            roleBadge +
            '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-bold">' +
            '<i class="fa-solid fa-circle-check"></i> Profile Active' +
            '</span>' +
            '</div>' +
            '</div>' +

            '<div class="border-t border-gray-100"></div>' +

            '<div class="px-4 py-3 space-y-2.5 text-sm flex-1">' +

            '<div class="flex justify-between items-center gap-4">' +
            '<span class="text-gray-400 font-semibold text-xs flex items-center gap-2">' +
            '<i class="fa-solid fa-cake-candles w-3"></i> Age <br> Date of Birth' +
            '</span>' +
            '<span class="text-gray-800 font-medium text-right">' +
            escapeHtml(pData.age ? pData.age + " yrs" : "N/A") +
            '<span class="text-gray-400 text-xs font-normal block">' +
            escapeHtml(pData.birthdate) +
            '</span>' +
            '</span>' +
            '</div>' +

            '<div class="flex justify-between items-center gap-4">' +
            '<span class="text-gray-400 font-semibold text-xs flex items-center gap-2">' +
            '<i class="fa-solid fa-venus-mars w-3"></i> Gender' +
            '</span>' +
            '<span class="text-gray-800 font-medium text-right">' +
            escapeHtml(pData.gender) +
            '</span>' +
            '</div>' +

            identityRow +

            '<div class="flex justify-between items-start gap-4">' +
            '<span class="text-gray-400 font-semibold text-xs flex items-center gap-2 mt-0.5">' +
            '<i class="fa-solid fa-phone w-3"></i> Contact' +
            '</span>' +
            '<span id="maskedContactValue" data-masked="true" class="text-gray-800 font-medium text-right break-words leading-snug flex-1 min-w-0">' +
            escapeHtml(maskedContact) +
            '</span>' +
            '</div>' +

            '<div class="flex justify-between items-start gap-3">' +
            '<span class="text-gray-400 font-semibold text-xs flex items-center gap-2 mt-0.5 flex-shrink-0 w-[92px]">' +
            '<i class="fa-solid fa-envelope w-3"></i> Email' +
            '</span>' +
            '<span id="maskedEmailValue" data-masked="true" class="text-gray-800 font-medium text-right break-words leading-snug flex-1 min-w-0">' +
            escapeHtml(maskedEmail) +
            '</span>' +
            '</div>' +

            '</div>' +

            '<div class="profile-emergency-panel bg-red-50/50 px-4 py-3 border-t border-red-100 mt-auto">' +
            '<p class="text-[10px] font-bold text-red-800 uppercase tracking-widest mb-2 flex items-center gap-1.5">' +
            '<i class="fa-solid fa-heart-pulse"></i> Emergency Contact' +
            '</p>' +
            emergencySection +
            '</div>' +
            '</div>'
        );
    }

    function renderRequestDocs() {
        var container = document.getElementById("requestDocsContainer");
        if (!container) return;

        swapSkeletonContent('requestDocsContainer',
            '<div class="dashboard-card-polished dental-overview-card bg-gradient-to-br from-[#ffffff] via-[#fff3f3] to-[#ffeaea] border border-[#eadede] shadow-sm rounded-[1rem] overflow-hidden h-full flex flex-col">' +

            '<div class="px-5 sm:px-6 py-4 border-b border-[#efe3e3] dark:border-white/10 bg-gradient-to-r from-[#fff6f6] via-[#fffdfd] to-[#fff1f1] dark:from-[#161B22] dark:via-[#161B22] dark:to-[#161B22]">' +
            '<div class="flex items-start justify-between gap-4">' +
            '<div class="min-w-0">' +
            '<div class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.18em] uppercase text-[#8B0000] mb-2">' +
            '<i class="fa-solid fa-file-lines"></i>' +
            '<span>Patient Services</span>' +
            '</div>' +
            '<h2 class="text-lg sm:text-xl font-extrabold text-gray-900 leading-tight">Request Documents</h2>' +
            '</div>' +
            '<div class="hidden sm:flex w-11 h-11 rounded-[0.85rem] glass-icon-red items-center justify-center flex-shrink-0">' +
            '<i class="fa-solid fa-folder-open text-base"></i>' +
            '</div>' +
            '</div>' +

            '<div class="mt-4 flex flex-wrap gap-2">' +
            '<span class="inline-flex items-center rounded-full bg-red-50 text-[#8B0000] px-3 py-1 text-[11px] font-bold border border-red-100">Pending: {{ $pendingDocumentRequests }}</span>' +
            '</div>' +
            '</div>' +

            '<div class="p-4 sm:p-5 flex-1 bg-gradient-to-b from-[#fffefe] via-[#fff8f8] to-[#fff3f3]">' +
            '<div class="space-y-3">' +

            '<button type="button" onclick="document.getElementById(\'dentalHealthRecordModal\')?.showModal()" class="group request-doc-card relative w-full text-left rounded-[0.9rem] border border-red-100 bg-white p-4 shadow-sm transition-all duration-200">' +
            '<div class="flex items-start gap-3">' +
            '<div class="icon-box w-14 h-14 rounded-[0.85rem] bg-red-50 text-[#8B0000] flex items-center justify-center flex-shrink-0 transition-all duration-300 group-hover:scale-105">' +
            '<i class="fa-solid fa-file-medical text-lg"></i>' +
            '</div>' +

            '<div class="min-w-0 flex-1">' +
            '<div class="flex flex-wrap items-center gap-2">' +
            '<h3 class="text-base font-extrabold text-gray-900 group-hover:text-[#8B0000] transition-colors">Dental Health Record</h3>' +
            '<span class="inline-flex items-center rounded-full bg-red-50 text-[#8B0000] px-2.5 py-1 text-[10px] font-bold border border-red-100">Most Requested</span>' +
            '</div>' +

            '<p class="mt-2 text-xs text-gray-500 leading-relaxed">Request a copy of your dental history, diagnosis notes, treatment details, and related medical information.</p>' +

            '<div class="mt-3 flex flex-wrap gap-2">' +
            '<span class="inline-flex items-center rounded-full bg-gray-50 text-gray-600 px-2.5 py-1 text-[11px] font-semibold border border-gray-200">All Records</span>' +
            '<span class="inline-flex items-center rounded-full bg-gray-50 text-gray-600 px-2.5 py-1 text-[11px] font-semibold border border-gray-200">Medical</span>' +
            '<span class="inline-flex items-center rounded-full bg-gray-50 text-gray-600 px-2.5 py-1 text-[11px] font-semibold border border-gray-200">Diagnosis</span>' +
            '</div>' +
            '</div>' +

            '<div class="flex-shrink-0 text-gray-300 group-hover:text-[#8B0000] transition-colors">' +
            '<i class="doc-arrow fa-solid fa-arrow-up-right-from-square text-base transition-all duration-300 opacity-70"></i>' +
            '</div>' +
            '</div>' +
            '</button>' +

            '<button type="button" onclick="document.getElementById(\'dentalClearanceModal\')?.showModal()" class="group request-doc-card relative w-full text-left rounded-[0.9rem] border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200">' +
            '<div class="flex items-start gap-3">' +
            '<div class="icon-box w-14 h-14 rounded-[0.85rem] bg-amber-50 text-[#c96a00] flex items-center justify-center flex-shrink-0 transition-all duration-300 group-hover:scale-105">' +
            '<i class="fa-solid fa-file-circle-check text-lg"></i>' +
            '</div>' +

            '<div class="min-w-0 flex-1">' +
            '<div class="flex flex-wrap items-center gap-2">' +
            '<h3 class="text-base font-extrabold text-gray-900 group-hover:text-[#8B0000] transition-colors">Dental Clearance</h3>' +
            '<span class="inline-flex items-center rounded-full bg-amber-50 text-[#b86100] px-2.5 py-1 text-[10px] font-bold border border-amber-100">School / Requirement</span>' +
            '</div>' +

            '<p class="mt-2 text-xs text-gray-500 leading-relaxed">Request a dental clearance for school submission, annual compliance, or other official requirements.</p>' +

            '<div class="mt-3 flex flex-wrap gap-2">' +
            '<span class="inline-flex items-center rounded-full bg-gray-50 text-gray-600 px-2.5 py-1 text-[11px] font-semibold border border-gray-200">Standard</span>' +
            '<span class="inline-flex items-center rounded-full bg-gray-50 text-gray-600 px-2.5 py-1 text-[11px] font-semibold border border-gray-200">Annual</span>' +
            '</div>' +
            '</div>' +

            '<div class="flex-shrink-0 text-gray-300 group-hover:text-[#8B0000] transition-colors">' +
            '<i class="doc-arrow fa-solid fa-arrow-up-right-from-square text-base transition-all duration-300 opacity-70"></i>' +
            '</div>' +
            '</div>' +
            '</button>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
    }

    function renderRecords() {
        var container = document.getElementById("dentalOverviewContainer");
        var viewAll = document.getElementById("viewAllContainer");

        if (!container) return;

        var count = HOME_RECORDS && HOME_RECORDS.length ? HOME_RECORDS.length : 0;
        var latestRecord = count ? HOME_RECORDS[0] : null;

        var dispLatestDate = latestRecord && latestRecord.date ? latestRecord.date : "No record yet";
        var dispOverviewStatus = count === 0 ?
            "Waiting for first completed visit" :
            (count === 1 ? "1 completed visit recorded" : count + " completed visits recorded");

        if (!HOME_RECORDS || HOME_RECORDS.length === 0) {
            swapSkeletonContent('dentalOverviewContainer',
                '<div class="dashboard-card-polished dental-overview-card bg-gradient-to-br from-[#ffffff] via-[#fff3f3] to-[#ffeaea] border border-[#eadede] shadow-sm rounded-[1rem] overflow-hidden h-full flex flex-col">' +
                '<div class="px-5 sm:px-6 py-4 border-b border-[#efe3e3] bg-gradient-to-r from-[#fff6f6] via-[#fffdfd] to-[#fff1f1]">' +
                '<div class="flex items-start justify-between gap-4">' +
                '<div class="min-w-0">' +
                '<div class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.18em] uppercase text-[#8B0000] mb-2">' +
                '<i class="fa-solid fa-tooth"></i><span>Patient Dental Summary</span>' +
                '</div>' +
                '<h2 class="text-lg sm:text-xl font-extrabold text-gray-900 leading-tight">Dental Overview</h2>' +
                '<p class="text-sm text-gray-500 mt-1">Quick summary of your visits, treatments, and latest dental activity.</p>' +
                '</div>' +
                '<div class="hidden sm:flex w-11 h-11 rounded-[0.85rem] glass-icon-red items-center justify-center flex-shrink-0">' +
                '<i class="fa-solid fa-chart-line text-base"></i>' +
                '</div>' +
                '</div>' +
                '<div class="mt-4 grid grid-cols-2 xl:grid-cols-3 gap-2.5">' +
                '<div class="rounded-[0.85rem] bg-white border border-gray-200 px-3 py-3">' +
                '<p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Visits</p>' +
                '<p class="mt-1 text-base font-extrabold text-gray-900">0</p>' +
                '</div>' +
                '<div class="rounded-[0.85rem] bg-white border border-gray-200 px-3 py-3">' +
                '<p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Latest Record</p>' +
                '<p class="mt-1 text-sm font-extrabold text-gray-900">No record yet</p>' +
                '</div>' +
                '<div class="rounded-[0.85rem] bg-white border border-gray-200 px-3 py-3 col-span-2 xl:col-span-1">' +
                '<p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</p>' +
                '<p class="overview-status-text mt-1 text-sm font-extrabold text-[#8B0000]">Waiting for first completed visit</p>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="p-5 sm:p-5 flex-1 flex flex-col bg-gradient-to-b from-[#fffefe] via-[#fff8f8] to-[#fff3f3]">' +
                '<div class="flex-1 flex flex-col justify-center">' +
                '<div class="rounded-[0.95rem] border border-dashed border-gray-200 bg-gradient-to-b from-[#fffdfd] to-[#fff6f6] px-5 py-7 text-center">' +
                '<div class="w-14 h-14 rounded-[0.85rem] bg-red-50 text-[#8B0000]/50 flex items-center justify-center mx-auto mb-4">' +
                '<i class="fa-solid fa-tooth text-xl"></i>' +
                '</div>' +
                '<p class="text-base font-extrabold text-gray-900 mb-2">No dental activity yet</p>' +
                '<p class="text-sm text-gray-500 leading-relaxed max-w-[360px] mx-auto mb-4">Your latest treatment summary, completed visits, and diagnosis highlights will appear here after your first finished appointment.</p>' +
                '<div class="flex flex-wrap items-center justify-center gap-2 mb-5">' +
                '<span class="inline-flex items-center rounded-full bg-white border border-gray-200 text-gray-600 px-3 py-1 text-xs font-semibold">Visit history</span>' +
                '<span class="inline-flex items-center rounded-full bg-white border border-gray-200 text-gray-600 px-3 py-1 text-xs font-semibold">Treatments</span>' +
                '<span class="inline-flex items-center rounded-full bg-white border border-gray-200 text-gray-600 px-3 py-1 text-xs font-semibold">Diagnosis summary</span>' +
                '</div>' +
                '<a href="' + ROUTE_BOOK + '" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[0.85rem] bg-[#8B0000] hover:bg-[#660000] text-white text-sm font-bold transition-all duration-300 hover:-translate-y-0.5">' +
                '<i class="fa-solid fa-calendar-plus"></i> Book First Appointment' +
                '</a>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>'
            );
            if (viewAll) viewAll.classList.add("hidden");
            return;
        }

        if (viewAll) viewAll.classList.remove("hidden");

        var html = '<div class="space-y-3">';
        HOME_RECORDS.slice(0, 3).forEach(function (r, idx) {
            var encoded = encodeURIComponent(JSON.stringify(r));
            var dispTime = formatTime(r.time);
            var dispDate = r.date;

            html +=
            '<div class="dashboard-card-polished rounded-[1rem] border border-gray-200 bg-white p-4 hover:border-red-200 hover:shadow-sm transition-all duration-300 hover:-translate-y-0.5">' +
            '<div class="flex items-start justify-between gap-3">' +
            '<div class="flex items-start gap-3 min-w-0">' +
            '<div class="w-10 h-10 rounded-[0.85rem] ' + (idx === 0 ? 'bg-red-50 text-[#8B0000]' : 'bg-gray-50 text-gray-600') + ' flex items-center justify-center flex-shrink-0">' +
            '<i class="fa-solid fa-tooth text-sm"></i>' +
            '</div>' +
            '<div class="min-w-0">' +
            '<div class="flex flex-wrap items-center gap-2">' +
            '<p class="text-sm font-extrabold text-gray-900 truncate">' + escapeHtml(r.service) + '</p>' +
            '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold border ' +
            ((r.status || '').toLowerCase() === 'completed' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-700 border-red-100') + '">' +
            escapeHtml((r.status || '').toLowerCase() === 'completed' ? 'Completed' : 'Cancelled') +
            '</span>' +
            '</div>' +
            '<div class="mt-2 flex flex-wrap items-center gap-2">' +
            '<span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 border border-gray-200 text-gray-600 px-2.5 py-1 text-[11px] font-bold">' +
            '<i class="fa-regular fa-calendar opacity-70"></i>' + escapeHtml(dispDate) +
            '</span>' +
            '<span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 border border-gray-200 text-gray-600 px-2.5 py-1 text-[11px] font-bold">' +
            '<i class="fa-regular fa-clock opacity-70"></i>' + escapeHtml(dispTime) +
            '</span>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<button type="button" class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-gray-50 hover:bg-[#8B0000] hover:text-white text-gray-700 text-[11px] font-bold border border-gray-200 hover:border-transparent transition-all duration-300 flex-shrink-0" onclick="openRecordModalFromData(\'' + encoded + '\')">' +
            '<i class="fa-solid fa-eye text-[10px]"></i>' +
            '<span>View Details</span>' +
            '</button>' +
            '</div>' +
            '</div>';
        });

        html +=
            '<div class="pt-2">' +
            '<a href="' + ROUTE_RECORD + '" class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-[0.85rem] bg-[#8B0000] hover:bg-[#660000] text-white text-sm font-bold transition-all duration-300 hover:-translate-y-0.5">' +
            '<i class="fa-solid fa-folder-open"></i>' +
            '<span>View All Records</span>' +
            '<i class="fa-solid fa-arrow-right text-[11px]"></i>' +
            '</a>' +
            '</div>' +
            '</div>';

        swapSkeletonContent('dentalOverviewContainer',
            '<div class="dashboard-card-polished dental-overview-card bg-gradient-to-br from-[#ffffff] via-[#fff3f3] to-[#ffeaea] border border-[#eadede] shadow-sm rounded-[1rem] overflow-hidden h-full flex flex-col">' +
            '<div class="px-5 sm:px-6 py-4 border-b border-[#efe3e3] bg-gradient-to-r from-[#fff6f6] via-[#fffdfd] to-[#fff1f1]">' +
            '<div class="flex items-start justify-between gap-4">' +
            '<div class="min-w-0">' +
            '<div class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.18em] uppercase text-[#8B0000] mb-2">' +
            '<i class="fa-solid fa-tooth"></i><span>Patient Dental Summary</span>' +
            '</div>' +
            '<h2 class="text-lg sm:text-xl font-extrabold text-gray-900 leading-tight">Dental Overview</h2>' +
            '<p class="text-sm text-gray-500 mt-1">Latest 3 records from your dental activity.</p>' +
            '</div>' +
            '<div class="hidden sm:flex w-11 h-11 rounded-[0.85rem] glass-icon-red items-center justify-center flex-shrink-0">' +
            '<i class="fa-solid fa-chart-line text-base"></i>' +
            '</div>' +
            '</div>' +

            '<div class="mt-4 grid grid-cols-2 xl:grid-cols-3 gap-2.5">' +
            '<div class="rounded-[0.85rem] bg-white border border-gray-200 px-3 py-3">' +
            '<p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Visits</p>' +
            '<p class="mt-1 text-base font-extrabold text-gray-900">' + count + '</p>' +
            '</div>' +

            '<div class="rounded-[0.85rem] bg-white border border-gray-200 px-3 py-3">' +
            '<p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Latest Record</p>' +
            '<p class="mt-1 text-sm font-extrabold text-gray-900">' + escapeHtml(dispLatestDate) + '</p>' +
            '</div>' +

            '<div class="rounded-[0.85rem] bg-white border border-gray-200 px-3 py-3 col-span-2 xl:col-span-1">' +
            '<p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</p>' +
            '<p class="overview-status-text mt-1 text-sm font-extrabold text-[#8B0000]">' + escapeHtml(dispOverviewStatus) + '</p>' +
            '</div>' +
            '</div>' +
            '</div>' +

            '<div class="p-5 sm:p-5 flex-1 bg-gradient-to-b from-[#fffefe] via-[#fff8f8] to-[#fff3f3]">' +
            html +
            '</div>' +
            '</div>'
        );
    }

    function initRequestDocInteractions() {
        document.querySelectorAll('.request-doc-card').forEach(function (card) {
            if (card.dataset.interactionsReady === 'true') return;
            card.dataset.interactionsReady = 'true';

            card.addEventListener('mousemove', function (e) {
                if (window.matchMedia('(hover: none)').matches) return;

                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const rotateX = ((y - rect.height / 2) / rect.height) * -4;
                const rotateY = ((x - rect.width / 2) / rect.width) * 4;

                card.style.transform =
                    'translateY(-4px) scale(1.01) perspective(900px) rotateX(' +
                    rotateX + 'deg) rotateY(' + rotateY + 'deg)';
            });

            card.addEventListener('mouseleave', function () {
                card.style.transform = '';
            });

            card.addEventListener('click', function (e) {
                const rect = card.getBoundingClientRect();
                const ripple = document.createElement('span');
                const size = Math.max(rect.width, rect.height);

                ripple.className = 'request-ripple';
                ripple.style.width = size + 'px';
                ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';

                card.appendChild(ripple);

                setTimeout(function () {
                    ripple.remove();
                }, 600);
            });
        });
    }

    function scrollToCalendar(event) {
        if (event) {
            event.preventDefault();

            const btn = event.currentTarget;

            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement("span");
            const size = Math.max(rect.width, rect.height);

            ripple.className = "btn-ripple";
            ripple.style.width = size + "px";
            ripple.style.height = size + "px";
            ripple.style.left = (event.clientX - rect.left - size / 2) + "px";
            ripple.style.top = (event.clientY - rect.top - size / 2) + "px";

            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 500);
        }

        const calendar = document.getElementById('calendarSkeletonContainer');
        if (!calendar) return;

        calendar.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        calendar.classList.add('calendar-focus-pulse');

        setTimeout(() => {
            calendar.classList.remove('calendar-focus-pulse');
        }, 1200);
    }
</script>
@endsection