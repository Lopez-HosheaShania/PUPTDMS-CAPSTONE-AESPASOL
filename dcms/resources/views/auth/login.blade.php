@extends('layouts.auth')

@section('styles')
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

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-light);
      color: var(--text-main);
      overflow-x: hidden;
      line-height: 1.6;
    }

    /* ─── KEYFRAMES / ANIMATIONS ─── */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes textShimmer {
      0% {
        background-position: 200% center;
      }
      100% {
        background-position: 0% center;
      }
    }

    @keyframes pulseGlow {
      0% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
      }

      70% {
        box-shadow: 0 0 0 12px rgba(255, 255, 255, 0);
      }

      100% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
      }
    }

    @keyframes floatCard {

      0%,
      100% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-8px);
      }
    }

    /* ─── NAV ─── */
    nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 200;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 4rem;
      height: 70px;
      background: rgba(255, 255, 255, 0.531);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
      /* Nav animation */
      animation: fadeInUp 0.8s ease-out;
    }

    .nav-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      transition: transform 0.3s ease;
    }

    .nav-brand:hover {
      transform: scale(1.02);
    }

    .nav-brand-text {
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 0.02em;
      color: var(--crimson);
      margin-top: 2px;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 2.5rem;
      list-style: none;
    }

    .nav-links a {
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: #1F2937;
      text-decoration: none;
      transition: all 0.3s ease;
      position: relative;
    }

    .nav-links a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -4px;
      left: 0;
      background-color: var(--crimson);
      transition: width 0.3s ease;
    }

    .nav-links a:hover::after {
      width: 100%;
    }

    .nav-links a:hover {
      color: var(--crimson);
    }

    .nav-cta {
      background: var(--crimson) !important;
      color: var(--white) !important;
      padding: 10px 20px !important;
      border-radius: 6px !important;
      letter-spacing: 0.05em !important;
      transition: background 0.2s, transform 0.2s !important;
    }

    .nav-cta:hover {
      background: var(--crimson-dark) !important;
      transform: translateY(-2px);
    }

    /* ─── HERO ─── */
    .hero {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      overflow: hidden;
      background: url('{{ asset("images/PUP TAGUIG CAMPUS.jpg") }}') center center / cover no-repeat;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);
      z-index: -1;
    }

    .hero-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      max-width: 800px;
      margin: 0 auto;
      position: relative;
    }

    /* GLASSMORPHISM LOGOS */
    .hero-logos {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 24px;
      padding: 18px 20px;
    }

    .hero-logo-img {
      width: 70px;
      height: 70px;
      object-fit: contain;
      filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    }

    /* HERO LOAD ANIMATIONS */
    .hero-content>.hero-logos {
      opacity: 0;
      animation: fadeInUp 0.8s ease-out forwards;
      animation-delay: 0.1s;
    }

    .hero-content>.eyebrow {
      opacity: 0;
      animation: fadeInUp 0.8s ease-out forwards;
      animation-delay: 0.3s;
    }

    .hero-content>.hero-title {
      opacity: 0;
      animation: fadeInUp 0.8s ease-out forwards;
      animation-delay: 0.5s;
    }

    .hero-content>.hero-desc {
      opacity: 0;
      animation: fadeInUp 0.8s ease-out forwards;
      animation-delay: 0.7s;
    }

    .hero-content>.hero-features {
      opacity: 0;
      animation: fadeInUp 0.8s ease-out forwards;
      animation-delay: 0.9s;
    }

    .hero-content>#login {
      opacity: 0;
      animation: fadeInUp 0.8s ease-out forwards;
      animation-delay: 1.1s;
    }

    .eyebrow {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 20px;
    }

    .eyebrow-line {
      width: 30px;
      height: 2px;
      background: var(--gold);
    }

    .eyebrow-text {
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.05em;
      color: var(--text-main);
    }

    /* hero-title — defined below in responsive block with gradient */

    .hero-desc {
      font-size: 16px;
      line-height: 1.7;
      color: var(--text-main);
      max-width: 600px;
      margin: 0 auto 60px;
    }

    .hero-features {
      list-style: none;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      margin-bottom: 48px;
    }

    .hero-features li {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-main);
      transition: transform 0.2s ease;
    }

    .hero-features li:hover {
      transform: translateX(5px);
    }

    .feat-dot {
      width: 22px;
      height: 22px;
      background: rgba(139, 0, 0, 0.08);
      color: var(--crimson);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      flex-shrink: 0;
    }

    /* GLASSMORPHISM BUTTON WITH PULSE */
    .btn-sso {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 14px 32px;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      text-decoration: none;
      color: var(--crimson);

      background: rgba(255, 255, 255, 0.3);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(139, 0, 0, 0.15);
      box-shadow: 0 8px 32px 0 rgba(139, 0, 0, 0.08);

      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      width: fit-content;
      margin: 0 auto;

      animation: pulseGlow 2.5s infinite;
    }

    .btn-sso:hover {
      background: rgba(255, 255, 255, 0.6);
      border-color: rgba(139, 0, 0, 0.3);
      transform: translateY(-4px) scale(1.02);
      box-shadow: 0 12px 40px 0 rgba(139, 0, 0, 0.15);
      animation: none;
      /* Stop pulse on hover */
    }

    .btn-sso-icon {
      width: 32px;
      height: 32px;
      background: rgba(139, 0, 0, 0.08);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s ease, transform 0.3s ease;
    }

    .btn-sso:hover .btn-sso-icon {
      background: rgba(139, 0, 0, 0.15);
      transform: translateX(3px);
    }

    /* ─── MAIN CONTENT ─── */
    main {
      position: relative;
      z-index: 1;
    }

    .section-wrap {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 4rem;
    }

    .section-label {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .section-label-line {
      width: 15px;
      height: 3px;
      background: var(--gold);
      border-radius: 2px;
    }

    .section-label-text {
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--crimson);
    }

    .section-heading {
      font-size: clamp(2rem, 3vw, 2.5rem);
      font-weight: 900;
      letter-spacing: -0.02em;
      color: var(--text-main);
      line-height: 1.2;
      margin-bottom: 16px;
    }

    .section-sub {
      font-size: 15px;
      color: var(--text-muted);
      line-height: 1.7;
      max-width: 600px;
    }

    /* ─── ABOUT ─── */
    #about {
      padding: 100px 0;
      background: var(--white);
    }

    .about-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
    }

    .about-statement {
      font-size: clamp(1.2rem, 1.8vw, 1.6rem);
      font-weight: 800;
      line-height: 1.5;
      color: var(--text-main);
      margin-bottom: 24px;
    }

    .about-statement strong {
      color: var(--crimson);
    }

    .about-body {
      font-size: 15px;
      line-height: 1.8;
      color: var(--text-muted);
    }

    .about-right {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }

    .pillar-card {
      background: var(--bg-light);
      border: 1px solid var(--gray-200);
      border-radius: 16px;
      padding: 24px;
      display: flex;
      align-items: flex-start;
      gap: 20px;
      transition: all 0.3s ease;
    }

    .pillar-card:hover {
      background: var(--white);
      box-shadow: var(--shadow-md);
      border-color: rgba(139, 0, 0, 0.2);
      transform: translateX(8px);
      /* Slide in effect */
    }

    .pillar-icon {
      width: 48px;
      height: 48px;
      background: rgba(139, 0, 0, 0.08);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: var(--crimson);
      flex-shrink: 0;
      transition: transform 0.3s ease;
    }

    .pillar-card:hover .pillar-icon {
      transform: scale(1.1) rotate(5deg);
    }

    .pillar-title {
      font-size: 15px;
      font-weight: 800;
      color: var(--crimson-dark);
      margin-bottom: 6px;
    }

    .pillar-body {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.6;
    }

    /* ─── DENTIST ─── */
    #dentist {
      padding: 100px 0;
      background: var(--bg-light);
      border-top: 1px solid var(--gray-200);
      border-bottom: 1px solid var(--gray-200);
    }

    .dentist-inner {
      display: grid;
      grid-template-columns: 1fr 420px;
      gap: 80px;
      align-items: center;
    }

    .dentist-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 32px;
    }

    .dtag {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 8px;
      padding: 8px 16px;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-main);
      box-shadow: var(--shadow-sm);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .dtag:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }

    .dtag i {
      color: var(--gold);
      font-size: 14px;
    }

    .dentist-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 24px;
      overflow: hidden;
      box-shadow: var(--shadow-md);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .dentist-card:hover {
      box-shadow: var(--shadow-lg);
      transform: translateY(-5px);
    }

    .dentist-card-top {
      background: var(--crimson);
      padding: 40px 32px 32px;
      text-align: center;
    }

    .dentist-avatar {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      border: 4px solid var(--white);
      overflow: hidden;
      margin: 0 auto 16px;
      background: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      color: var(--crimson);
      transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dentist-card:hover .dentist-avatar {
      transform: scale(1.1);
    }

    .dentist-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .dentist-name {
      font-size: 22px;
      font-weight: 900;
      color: var(--white);
    }

    .dentist-role {
      font-size: 13px;
      color: var(--gold);
      font-weight: 600;
      letter-spacing: 0.05em;
      margin-top: 4px;
    }

    .dentist-card-body {
      padding: 32px;
    }

    .info-row {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 14px 0;
      border-bottom: 1px solid var(--gray-100);
      font-size: 14px;
      color: var(--text-muted);
      font-weight: 500;
      transition: color 0.2s ease;
    }

    .info-row:hover {
      color: var(--crimson);
    }

    .info-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .info-row i {
      color: var(--crimson);
      font-size: 16px;
      width: 20px;
      text-align: center;
    }

    /* ─── SERVICES ─── */
    #services {
      padding: 100px 0;
      background: var(--white);
    }

    .services-header {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      margin-bottom: 48px;
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
    }

    .svc-card {
      background: var(--bg-light);
      border: 1px solid var(--gray-200);
      border-radius: 16px;
      padding: 32px;
      display: flex;
      align-items: flex-start;
      gap: 24px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .svc-card:hover {
      background: var(--white);
      border-color: var(--crimson);
      box-shadow: var(--shadow-md);
      transform: translateY(-6px);
    }

    .svc-icon {
      width: 60px;
      height: 60px;
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: var(--crimson);
      flex-shrink: 0;
      box-shadow: var(--shadow-sm);
      transition: all 0.3s ease;
    }

    .svc-card:hover .svc-icon {
      background: var(--crimson);
      color: var(--white);
      border-color: var(--crimson);
      transform: rotate(-10deg);
    }

    .svc-body h4 {
      font-size: 18px;
      font-weight: 800;
      color: var(--text-main);
      margin-bottom: 8px;
    }

    .svc-body p {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.6;
    }

    /* ─── FAQ ─── */
    .faq-section {
      padding: 100px 0;
      background: var(--bg-light);
      border-top: 1px solid var(--gray-200);
      border-bottom: 1px solid var(--gray-200);
    }

    .faq-header-row {
      text-align: center;
      margin-bottom: 48px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .section-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(139, 0, 0, 0.08);
      color: var(--crimson);
      padding: 6px 16px;
      border-radius: 50px;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      margin-bottom: 16px;
    }

    .faq-section-title {
      font-size: clamp(2rem, 3vw, 2.5rem);
      font-weight: 900;
      letter-spacing: -0.02em;
      color: var(--text-main);
      line-height: 1.2;
      margin-bottom: 12px;
    }

    .faq-section-sub {
      font-size: 15px;
      color: var(--text-muted);
      max-width: 600px;
      margin: 0 auto;
    }

    #faqList {
      max-width: 800px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .faq-item-new {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 12px;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .faq-item-new:hover {
      border-color: rgba(139, 0, 0, 0.3);
      transform: translateX(4px);
    }

    .faq-item-new.open {
      border-color: var(--crimson);
      box-shadow: var(--shadow-md);
      transform: translateX(0);
    }

    .faq-trigger {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 20px 24px;
      background: none;
      border: none;
      cursor: pointer;
      text-align: left;
    }

    .faq-trigger-left {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .faq-num {
      font-size: 13px;
      font-weight: 800;
      color: var(--crimson);
      background: rgba(139, 0, 0, 0.08);
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .faq-item-new.open .faq-num {
      background: var(--crimson);
      color: var(--white);
    }

    .faq-q {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-main);
      transition: color 0.3s ease;
    }

    .faq-item-new.open .faq-q {
      color: var(--crimson);
    }

    .faq-chevron {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: var(--bg-light);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .faq-item-new.open .faq-chevron {
      transform: rotate(180deg);
      background: rgba(139, 0, 0, 0.1);
      color: var(--crimson);
    }

    .faq-body {
      max-height: 0;
      overflow: hidden;
      opacity: 0;
      transition: max-height 0.35s ease, opacity 0.3s ease;
    }

    .faq-item-new.open .faq-body {
      opacity: 1;
    }

    .faq-body-inner {
      padding: 0 24px 24px 72px;
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.7;
    }

    @media (max-width: 768px) {
      .faq-body-inner {
        padding-left: 24px;
      }
    }

    /* ─── TEAM ─── */
    #team {
      padding: 100px 0;
      background: var(--white);
    }

    .team-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 24px;
      margin-top: 48px;
    }

    .team-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 16px;
      overflow: hidden;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
    }

    .team-card:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-6px);
      border-color: rgba(139, 0, 0, 0.2);
    }

    .team-img {
      aspect-ratio: 1;
      background: var(--bg-light);
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 48px;
      color: var(--gray-200);
      border-bottom: 1px solid var(--gray-200);
      transition: background 0.3s ease;
    }

    .team-card:hover .team-img {
      background: var(--gray-100);
    }

    .team-img img {
      transition: transform 0.4s ease;
    }

    .team-card:hover .team-img img {
      transform: scale(1.08);
    }

    .team-info {
      padding: 20px;
      text-align: center;
    }

    .team-name {
      font-size: 15px;
      font-weight: 800;
      color: var(--text-main);
      margin-bottom: 6px;
    }

    .team-badge {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: var(--crimson);
      background: rgba(139, 0, 0, 0.08);
      padding: 4px 12px;
      border-radius: 4px;
    }

    /* ─── CLOSING ─── */
    .closing {
      background: var(--ink, #1C0A0A);
      padding: 80px 0;
      position: relative;
      overflow: hidden;
    }

    .closing::before {
      content: '';
      position: absolute;
      width: 800px;
      height: 800px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(139, 0, 0, 0.25) 0%, transparent 70%);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    .closing-inner {
      position: relative;
      z-index: 1;
      text-align: center;
    }

    .closing-inner .eyebrow-text {
      color: var(--gold);
    }

    .closing-inner .eyebrow-line {
      background: var(--gold);
    }

    .closing-heading {
      font-size: clamp(2rem, 4vw, 3.5rem);
      font-weight: 900;
      letter-spacing: -0.025em;
      color: var(--white);
      line-height: 1.1;
      margin-bottom: 20px;
    }

    .closing-heading em {
      color: var(--gold-light, #F5C842);
      font-style: italic;
      font-weight: 300;
    }

    .closing-desc {
      font-size: 15px;
      color: rgba(255, 255, 255, 0.45);
      max-width: 480px;
      margin: 0 auto 40px;
      line-height: 1.8;
    }

    .closing-eyebrow {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 24px;
    }

    .btn-sso-alt {
      background: var(--white);
      color: var(--crimson-dark);
      animation: none;
    }

    .btn-sso-alt:hover {
      background: var(--gray-100);
      color: var(--crimson);
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.15);
    }

    .btn-sso-alt .btn-sso-icon {
      background: rgba(139, 0, 0, 0.1);
    }

    /* ─── IMPROVED REVEAL (Scroll Animation) ─── */
    .reveal {
      opacity: 0;
      transform: translateY(35px) scale(0.98);
      transition: opacity 0.8s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    .reveal.visible {
      opacity: 1;
      transform: translateY(0) scale(1);
    }

    .reveal-d1 {
      transition-delay: 0.1s;
    }

    .reveal-d2 {
      transition-delay: 0.2s;
    }

    .reveal-d3 {
      transition-delay: 0.3s;
    }

    .reveal-d4 {
      transition-delay: 0.4s;
    }

    /* ─── HERO TITLE GRADIENT ─── */
    .hero-title {
      font-size: clamp(2rem, 5vw, 5rem);
      font-weight: 800;
      line-height: 1.3; 
      letter-spacing: -0.02em;
      margin-bottom: 24px;
      padding-bottom: 0.2em;
      
      background: linear-gradient(
        to right, 
        #8B0000 0%, 
        #b5282a 25%, 
        #FFD700 50%, 
        #b5282a 75%, 
        #8B0000 100%
      );
      background-size: 200% auto; 
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: textShimmer 4s linear infinite;
    }

    /* ─── MOBILE HAMBURGER ─── */
    .nav-hamburger {
      display: none;
      flex-direction: column;
      gap: 5px;
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      z-index: 300;
    }

    .nav-hamburger span {
      display: block;
      width: 24px;
      height: 2px;
      background: var(--crimson);
      border-radius: 2px;
      transition: all 0.3s ease;
    }

    .nav-hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .nav-hamburger.open span:nth-child(2) { opacity: 0; }
    .nav-hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    /* ─── MOBILE MENU — compact slide-down panel ─── */
    .mobile-menu {
      display: none;
      position: fixed;
      top: 70px;
      left: 0;
      right: 0;
      background: rgba(255,255,255,0.97);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      z-index: 150;
      flex-direction: column;
      align-items: stretch;
      opacity: 0;
      pointer-events: none;
      transform: translateY(-8px);
      transition: opacity 0.22s ease, transform 0.22s ease;
      border-bottom: 1px solid var(--gray-200);
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      padding: 0.5rem 0 0.75rem;
    }

    .mobile-menu.open {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(0);
    }

    .mobile-menu a {
      font-size: 0.82rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      color: var(--text-main);
      text-decoration: none;
      text-transform: uppercase;
      padding: 0.7rem 1.5rem;
      transition: background 0.15s, color 0.15s;
      border-left: 3px solid transparent;
    }

    .mobile-menu a:hover {
      background: rgba(139,0,0,0.04);
      color: var(--crimson);
      border-left-color: var(--crimson);
    }

    .mobile-menu .mob-divider {
      height: 1px;
      background: var(--gray-200);
      margin: 0.4rem 1.5rem;
    }

    .mobile-menu .nav-cta-mob {
      margin: 0.4rem 1.5rem 0 !important;
      background: var(--crimson) !important;
      color: #fff !important;
      padding: 0.65rem 1.25rem !important;
      border-radius: 8px !important;
      font-size: 0.82rem !important;
      font-weight: 700 !important;
      letter-spacing: 0.06em !important;
      text-align: center !important;
      border-left: none !important;
    }

    .mobile-menu .nav-cta-mob:hover {
      background: var(--crimson-dark) !important;
      border-left-color: transparent !important;
    }

    /* ─── RESPONSIVE ─── */
    @media (max-width: 1024px) {
      .about-grid,
      .dentist-inner,
      .faq-layout {
        grid-template-columns: 1fr;
        gap: 40px;
      }

      .faq-sticky { position: static; }

      .team-grid { grid-template-columns: repeat(2, 1fr); }

      .dentist-card {
        max-width: 400px;
        margin: 0 auto;
      }
    }

    @media (max-width: 768px) {
      /* Nav */
      nav {
        padding: 0 1.25rem;
      }

      .nav-links { display: none; }
      .nav-hamburger { display: flex; }
      .mobile-menu { display: flex; }

      /* Hero */
      .hero {
        padding: 0 1.25rem;
        min-height: 100svh;
      }

      .hero-content {
        padding: 90px 0 2.5rem;
        width: 100%;
      }

      .hero-logos {
        gap: 16px;
        padding: 14px 16px;
      }

      .hero-logo-img { width: 56px; height: 56px; }

      .hero-title {
        font-size: clamp(2rem, 9vw, 2.8rem);
        margin-bottom: 18px;
      }

      .hero-desc {
        font-size: 14px;
        margin-bottom: 24px;
        padding: 0 0.25rem;
      }

      .hero-features {
        gap: 12px;
        margin-bottom: 32px;
        flex-direction: column;
        align-items: flex-start;
        padding: 0 0.25rem;
      }

      .hero-features li { font-size: 13px; }

      .btn-sso {
        padding: 12px 24px;
        font-size: 12px;
        width: 100%;
        max-width: 300px;
        justify-content: center;
      }

      /* Sections */
      .section-wrap {
        padding: 0 1.25rem;
      }

      #about,
      #dentist,
      #services,
      #team,
      .faq-section {
        padding: 60px 0;
      }

      .section-heading { font-size: 1.6rem; }

      .services-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 28px;
      }

      .services-header .section-sub { text-align: left !important; max-width: 100%; }

      .services-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .svc-card {
        padding: 20px;
        gap: 16px;
      }

      .svc-icon { width: 48px; height: 48px; font-size: 20px; border-radius: 12px; }

      .about-right { grid-template-columns: 1fr; }

      .pillar-card { padding: 18px; }

      .dentist-inner { gap: 32px; }

      .dentist-card { max-width: 100%; }

      .dentist-card-top { padding: 28px 20px 24px; }

      .dentist-avatar { width: 76px; height: 76px; font-size: 28px; }

      .dentist-name { font-size: 18px; }

      .dentist-card-body { padding: 20px; }

      .info-row { font-size: 13px; }

      .team-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }

      .team-info { padding: 14px; }

      .team-name { font-size: 13px; }

      .faq-body-inner { padding-left: 24px; }

      .faq-trigger { padding: 16px; }

      .faq-q { font-size: 13px; }

      .faq-section-title { font-size: 1.6rem; }

      .closing { padding: 60px 0; }

      .closing-heading { font-size: clamp(1.8rem, 6vw, 2.5rem); }

      footer {
        padding: 24px 1.25rem;
        flex-direction: column;
        align-items: flex-start;
      }
    }

    @media (max-width: 480px) {
      .hero-title { font-size: clamp(1.7rem, 8vw, 2.2rem); }

      .team-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }

      .team-name { font-size: 12px; }

      .dtag { font-size: 11px; padding: 6px 12px; }

      .dentist-tags { gap: 8px; }
    }

    /* ─── DARK MODE SUPPORT ─── */
    html[data-theme="dark"] {
      --crimson: #8B0000;
      --crimson-dark: #C1121F;
      --white: #0D1117;
      --bg-main: #000D1A;
      --bg-card: #0D1117;
      --bg-card-soft: #111827;
      --bg-panel: #161B22;
      --bg-panel-soft: #1C2128;
      --bg-light: #000D1A;
      --gray-100: #111827;
      --gray-200: #1C2128;
      --border-dark: rgba(255, 255, 255, 0.10);
      --text-primary: #F3F4F6;
      --text-secondary: #C9D1D9;
      --text-main: #F3F4F6;
      --text-muted: #8B949E;
    }

    html[data-theme="dark"] body {
      background: var(--bg-main);
      color: var(--text-main);
    }

    html[data-theme="dark"] .nav-brand-text,
    html[data-theme="dark"] .nav-links a,
    html[data-theme="dark"] .hero-title {
      color: var(--text-primary);
    }

    html[data-theme="dark"] .nav-cta {
      color: var(--text-primary) !important;
    }

    html[data-theme="dark"] nav {
      background: rgba(13, 17, 23, 0.88);
      border-bottom-color: var(--border-dark);
    }

    html[data-theme="dark"] .hero {
      background-color: var(--bg-main);
    }

    html[data-theme="dark"] .hero::before {
      background: linear-gradient(to bottom, rgba(0, 13, 26, 0.78) 0%, rgba(0, 13, 26, 0.95) 100%);
    }

    html[data-theme="dark"] .hero-desc,
    html[data-theme="dark"] .eyebrow-text,
    html[data-theme="dark"] .hero-features li {
      color: var(--text-secondary);
    }

    html[data-theme="dark"] .btn-sso {
      color: var(--text-primary);
      background: rgba(22, 27, 34, 0.82);
      border-color: rgba(139, 0, 0, 0.24);
      box-shadow: 0 8px 32px 0 rgba(139, 0, 0, 0.18);
    }

    html[data-theme="dark"] .btn-sso:hover {
      background: rgba(28, 33, 40, 0.92);
      border-color: rgba(139, 0, 0, 0.38);
      box-shadow: 0 12px 40px 0 rgba(139, 0, 0, 0.24);
    }

    html[data-theme="dark"] .hero-title {
      background: linear-gradient(
        to right,
        #8B0000 0%,
        #b5282a 25%,
        #FFD700 50%,
        #b5282a 75%,
        #8B0000 100%
      );
      background-size: 200% auto;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    html[data-theme="dark"] .section-wrap {
      color: var(--text-main);
    }

    html[data-theme="dark"] .about-statement,
    html[data-theme="dark"] .section-heading,
    html[data-theme="dark"] .faq-section-title,
    html[data-theme="dark"] .closing-heading {
      color: var(--text-main);
    }

    html[data-theme="dark"] .pillar-card {
      background: var(--bg-card);
      border-color: var(--border-dark);
    }

    html[data-theme="dark"] .svc-card {
      background: var(--bg-card);
      border-color: var(--border-dark);
    }

    html[data-theme="dark"] .faq-item-new {
      background: var(--bg-card);
      border-color: var(--border-dark);
    }

    html[data-theme="dark"] .faq-body-inner {
      color: var(--text-muted);
    }

    html[data-theme="dark"] .closing {
      background: var(--bg-main);
    }

    html[data-theme="dark"] .mobile-menu {
      background: rgba(13, 17, 23, 0.97);
    }

    html[data-theme="dark"] .mobile-menu a {
      color: var(--text-primary);
    }
  </style>
@endsection

@section('content')

  <nav>
    <div class="nav-brand">
      <span class="nav-brand-text">PUP Taguig Dental Clinic</span>
    </div>

    <ul class="nav-links">
      <li><a href="#home">Home</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#services">Services</a></li>
      <li><a href="#faq">FAQ</a></li>
      <li><a href="#team">Team</a></li>
      <li><a href="/auth/oidc/redirect" class="nav-cta">Login</a></li>
    </ul>

    <button class="auth-theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
      <i class="fas fa-moon" id="themeIcon"></i>
    </button>

    <button class="nav-hamburger" id="hamburgerBtn" aria-label="Toggle menu" onclick="toggleMobileMenu()">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </nav>

  <!-- Mobile Menu — compact dropdown panel -->
  <div class="mobile-menu" id="mobileMenu">
    <a href="#home"     onclick="closeMobileMenu()">Home</a>
    <a href="#about"    onclick="closeMobileMenu()">About</a>
    <a href="#services" onclick="closeMobileMenu()">Services</a>
    <a href="#faq"      onclick="closeMobileMenu()">FAQ</a>
    <a href="#team"     onclick="closeMobileMenu()">Team</a>
    <div class="mob-divider"></div>
    <a href="/auth/oidc/redirect" class="nav-cta-mob">
      <i class="fa-solid fa-arrow-right-to-bracket" style="margin-right:6px;font-size:11px;"></i>Login with SSO
    </a>
  </div>

  <section class="hero" id="home">
    <div class="hero-content">

      <div class="hero-logos reveal">
        <img src="{{ asset('images/PUP.png') }}" class="hero-logo-img" alt="PUP Logo">
        <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" class="hero-logo-img" alt="Clinic Logo">
      </div>
    </div>

    <h1 class="hero-title reveal reveal-d2">
      <span class="t1">PUP</span>
      <span class="t2">Taguig</span>
      <span class="t3">Dental Clinic</span>
    </h1>

    <p class="hero-desc reveal reveal-d3">
      Professional, accessible, and high-quality dental care exclusively for students, faculty, and staff of
      PUP Taguig. Manage your oral health seamlessly.
    </p>

    <ul class="hero-features reveal reveal-d4">
      <li><span class="feat-dot"><i class="fa-solid fa-check"></i></span> Secure online appointment booking</li>
      <li><span class="feat-dot"><i class="fa-solid fa-check"></i></span> Comprehensive digital patient records</li>
      <li><span class="feat-dot"><i class="fa-solid fa-check"></i></span> Professional campus dental services</li>
    </ul>

    <div id="login" class="reveal" style="animation-delay: 1.1s;">
      <a href="/auth/oidc/redirect" class="btn-sso">
        <div class="btn-sso-icon">
          <i class="fa-solid fa-arrow-right-to-bracket" style="font-size:12px;"></i>
        </div>
        Login with SSO
      </a>
    </div>
    </div>
  </section>

  <section id="about">
    <div class="section-wrap">
      <div class="about-grid reveal">
        <div class="about-left">
          <div class="section-label">
            <span class="section-label-line"></span>
            <span class="section-label-text">About the Clinic</span>
          </div>
          <h2 class="section-heading">Commitment to Oral Health</h2>
          <p class="about-statement">
            Providing <strong>free, professional dental care</strong> to the PUP Taguig community in a safe and
            welcoming clinical environment.
          </p>
          <p class="about-body">
            The PUP Taguig Dental Clinic was established to ensure every member of the university community has access
            to quality oral health services — without cost or barriers. Operated by a licensed campus dentist, the
            clinic handles everything from routine check-ups to comprehensive dental procedures.
          </p>
        </div>
        <div class="about-right">
          <div class="pillar-card reveal reveal-d1">
            <div class="pillar-icon"><i class="fa-solid fa-shield-heart"></i></div>
            <div>
              <div class="pillar-title">Free Dental Care</div>
              <div class="pillar-body">All standard dental services are provided at no cost to eligible PUP Taguig
                students, faculty, and staff.</div>
            </div>
          </div>
          <div class="pillar-card reveal reveal-d2">
            <div class="pillar-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
              <div class="pillar-title">Easy Scheduling</div>
              <div class="pillar-body">Book real-time slots online through the Dental Management System with instant
                confirmations.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="dentist">
    <div class="section-wrap">
      <div class="dentist-inner reveal">
        <div class="dentist-left">
          <div class="section-label">
            <span class="section-label-line"></span>
            <span class="section-label-text">Our Dentist</span>
          </div>
          <h2 class="section-heading">Led by an Experienced Professional</h2>
          <p class="section-sub">
            The clinic is headed by <strong>Dr. Nelson P. Angeles</strong>, providing professional, safe, and reliable
            dental care to the university community. With a commitment to
            patient comfort and oral health excellence, the clinic supports consultations, treatment planning, and
            preventive care.
          </p>
          <div class="dentist-tags">
            <span class="dtag"><i class="fa-solid fa-circle-check"></i> Licensed Dentist</span>
            <span class="dtag"><i class="fa-solid fa-circle-check"></i> Campus Specialist</span>
          </div>
        </div>

        <div class="dentist-card">
          <div class="dentist-card-top">
            <div class="dentist-avatar">
              <img src="{{ asset('images/Nelson-Angeles.jpg') }}" alt="Dr. Nelson P. Angeles"
                onerror="this.src='https://ui-avatars.com/api/?name=Nelson+Angeles&background=660000&color=FFFFFF&size=88'">
            </div>
            <div class="dentist-name">Dr. Nelson P. Angeles</div>
            <div class="dentist-role">University Campus Dentist</div>
          </div>
          <div class="dentist-card-body">
            <div class="info-row"><i class="fa-solid fa-location-dot"></i> PUP Taguig Campus Dental Clinic</div>
            <div class="info-row"><i class="fa-regular fa-clock"></i> Mon – Fri, 8:00 AM – 5:00 PM</div>
            <div class="info-row"><i class="fa-solid fa-users"></i> Students, Alumni, Faculty & Staff</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="services">
    <div class="section-wrap">
      <div class="services-header reveal">
        <div>
          <div class="section-label">
            <span class="section-label-line"></span>
            <span class="section-label-text">Services</span>
          </div>
          <h2 class="section-heading">What We Offer</h2>
        </div>
        <p class="section-sub" style="max-width:360px; text-align:right;">Preventive and restorative dental procedures
          provided safely and efficiently.</p>
      </div>

      <div class="services-grid reveal">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-hand-holding-medical"></i></div>
          <div class="svc-body">
            <h4>Oral Check-Up & Consultation</h4>
            <p>Routine oral examinations, dental consultations, and comprehensive oral health assessments.</p>
          </div>
        </div>
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-droplet"></i></div>
          <div class="svc-body">
            <h4>Dental Cleaning</h4>
            <p>Professional oral hygiene treatment to remove plaque, tartar, and surface stains securely.</p>
          </div>
        </div>
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-teeth"></i></div>
          <div class="svc-body">
            <h4>Restoration & Prosthesis</h4>
            <p>Fillings, crowns, inlays, and other repairs to effectively restore damaged or missing teeth.</p>
          </div>
        </div>
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-crutch"></i></div>
          <div class="svc-body">
            <h4>Dental Surgery</h4>
            <p>Tooth extractions, supernumerary removal, and other minor surgical dental procedures.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="faq" class="faq-section reveal">
    <div class="section-wrap">
      <div class="faq-header-row">
        <div>
          <div class="section-pill"><i class="fa-solid fa-circle-question"></i> FAQs</div>
          <h2 class="faq-section-title">Frequently Asked Questions</h2>
          <p class="faq-section-sub">Quick answers about the PUP Taguig Dental Management System.</p>
        </div>
      </div>

      @php
      $faqs = [
      [
      'q' => 'Who can avail of the dental services?',
      'a' => 'All students, faculty, and staff of the Polytechnic University of the Philippines – Taguig Campus
      are eligible for free dental services.',
      ],
      [
      'q' => 'How do I book an appointment?',
      'a' => 'You can book an appointment online through the Dental Management System portal. Simply log in, choose your
      preferred schedule, and confirm your booking.',
      ],
      [
      'q' => 'Will the dentist prescribe medications?',
      'a' => 'Yes. Depending on your dental condition, Dr. Angeles may prescribe antibiotics, pain relievers, or other
      necessary medications during your visit.',
      ],
      [
      'q' => 'Can I book an appointment anytime?',
      'a' => 'Appointments are subject to slot availability. Since the clinic operates with a single dentist and limited
      daily slots, early booking is highly recommended.',
      ],
      [
      'q' => 'How do I cancel or reschedule?',
      'a' => 'You can cancel or reschedule through the Dental Management System portal or by contacting the clinic
      directly — at least three (3) days before your scheduled appointment.',
      ],
      [
      'q' => 'What if the dentist is unavailable on my scheduled day?',
      'a' => 'If Dr. Angeles is unavailable, your confirmed appointment will be rescheduled to the next available slot
      and you will be notified accordingly.',
      ],
      [
      'q' => 'What services are available at the clinic?',
      'a' => 'The clinic provides oral check-ups, dental cleaning, fillings, extractions, dental surgery, restoration,
      prosthetics, and preventive care services.',
      ],
      [
      'q' => 'Are urgent dental cases given priority?',
      'a' => 'Yes, urgent cases may be prioritized depending on the daily schedule and the dentist\'s discretion.
      Contact the clinic directly for urgent concerns.',
      ],
      [
      'q' => 'Are there restrictions for certain treatments?',
      'a' => 'Some advanced procedures may not be available due to the clinic\'s resources and equipment. The dentist
      will guide you on available alternatives if needed.',
      ],
      [
      'q' => 'Are follow-up appointments required?',
      'a' => 'Some treatments require follow-up visits. Dr. Angeles will advise you if a follow-up is necessary after
      your initial treatment.',
      ],
      ];
      @endphp

      <div id="faqList">
        @foreach ($faqs as $i => $faq)
        <div class="faq-item-new reveal" style="transition-delay: {{ $i * 0.04 }}s;">
          <button class="faq-trigger" onclick="toggleFaq(this)" aria-expanded="false">
            <div class="faq-trigger-left">
              <span class="faq-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
              <span class="faq-q">{{ $faq['q'] }}</span>
            </div>
            <span class="faq-chevron"><i class="fa-solid fa-chevron-down text-xs"></i></span>
          </button>
          <div class="faq-body">
            <div class="faq-body-inner">{{ $faq['a'] }}</div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  <section id="team">
    <div class="section-wrap">
      <div class="section-label reveal">
        <span class="section-label-line"></span>
        <span class="section-label-text">Development Team</span>
      </div>
      <h2 class="section-heading reveal">The System Developers</h2>

      @php
      $devs = [
      ['img' => 'Althea-Aragon.jpg', 'name' => 'Althea Mae Aragon', 'role' => 'Developer'],
      ['img' => 'Grace-Lim.jpg', 'name' => 'Grace Anne Lim', 'role' => 'Developer'],
      ['img' => 'Hoshea-Lopez.jpg', 'name' => 'Hoshea Shania Lopez', 'role' => 'Developer'],
      ['img' => 'Rain-Romero.jpg', 'name' => 'Dianna Rain Romero', 'role' => 'Developer'],
      ];
      @endphp

      <div class="team-grid">
        @foreach ($devs as $i => $dev)
        <div class="team-card reveal {{ $i > 0 ? 'reveal-d' . $i : '' }}">
          <div class="team-img">
            <img src="{{ asset('images/' . $dev['img']) }}" alt="{{ $dev['name'] }}"
              onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($dev['name']) }}&background=660000&color=FFFFFF&size=250'">
          </div>
          <div class="team-info">
            <div class="team-name">{{ $dev['name'] }}</div>
            <span class="team-badge">{{ $dev['role'] }}</span>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  <section class="closing">
    <div class="section-wrap">
      <div class="closing-inner reveal">
        <div class="closing-eyebrow">
          <span class="eyebrow-line" style="background:var(--gold);"></span>
          <span class="eyebrow-text" style="color:var(--gold);">PUP Taguig Dental Clinic</span>
          <span class="eyebrow-line" style="background:var(--gold);"></span>
        </div>
        <h2 class="closing-heading">Mula Sayo,<br><em>Para Sa Bayan.</em></h2>
        <p class="closing-desc">Developed to manage appointments and records more effectively, supporting accessible and
          efficient dental care for the entire PUP Taguig community.</p>
        <a href="/auth/oidc/redirect" class="btn-sso btn-sso-alt" style="margin:0 auto;">
          <div class="btn-sso-icon"><i class="fa-solid fa-arrow-right-to-bracket" style="font-size:11px;"></i></div>
          Login with SSO
        </a>
      </div>
    </div>
  </section>

  <script>
    function toggleMobileMenu() {
      const menu = document.getElementById('mobileMenu');
      const btn  = document.getElementById('hamburgerBtn');
      const open = menu.classList.toggle('open');
      btn.classList.toggle('open', open);
      document.body.style.overflow = open ? 'hidden' : '';
    }

    function closeMobileMenu() {
      document.getElementById('mobileMenu').classList.remove('open');
      document.getElementById('hamburgerBtn').classList.remove('open');
      document.body.style.overflow = '';
    }

    function toggleFaq(btn) {
      const item = btn.closest('.faq-item-new');
      const answer = item.querySelector('.faq-body');
      const isOpen = item.classList.contains('open');

      document.querySelectorAll('.faq-item-new.open').forEach(el => {
        el.classList.remove('open');
        el.querySelector('.faq-body').style.maxHeight = '0';
        el.querySelector('.faq-trigger').setAttribute('aria-expanded', 'false');
      });

      if (!isOpen) {
        item.classList.add('open');
        answer.style.maxHeight = answer.scrollHeight + 'px';
        btn.setAttribute('aria-expanded', 'true');
      }
    }

    const revealObs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          revealObs.unobserve(e.target);
        }
      });
    }, { threshold: 0.08 });

    document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));
  </script>
@endsection