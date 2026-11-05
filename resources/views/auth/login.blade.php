@extends('layouts.auth')

@section('title', 'Login')

@section('body-class', 'auth-guest-body auth-landing-body')

@section('styles')
    @vite('resources/css/pages/auth/login.css')
@endsection

@section('content')
<div class="auth-landing-page">
  <header class="auth-landing-header">
    <a href="#home" class="auth-landing-brand" aria-label="PUP Taguig Dental Clinic home">
      <img src="{{ asset('images/PUP.png') }}" alt="PUP Logo" class="auth-landing-brand-logo">
      <span class="auth-landing-brand-divider"></span>
      <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" alt="Dental Clinic Logo"
        class="auth-landing-brand-logo auth-landing-brand-logo-clinic">
      <span class="auth-landing-brand-copy">
        <strong>PUP Taguig</strong>
        <small>Dental Clinic</small>
      </span>
    </a>

    <nav class="auth-landing-desktop-nav" aria-label="Primary navigation">
      <a href="#home">Home</a>
      <a href="#about">About</a>
      <a href="#services">Services</a>
      <a href="#faq">FAQ</a>
      <a href="#team">Team</a>
    </nav>

    <div class="auth-landing-header-actions">
      <button type="button" class="auth-landing-theme-toggle" data-global-theme-toggle
        data-tooltip="Switch to dark mode" data-tooltip-tone="neutral" aria-label="Switch to dark mode"
        aria-pressed="false">

        <i class="fa-solid fa-moon" data-global-theme-icon aria-hidden="true">
        </i>
      </button>

      <a href="/auth/oidc/redirect" class="auth-landing-header-login" data-oidc-login-link>
        <span>Login</span>
        <i class="fa-solid fa-arrow-right"></i>
      </a>

      <button type="button" class="auth-landing-menu-btn" id="hamburgerBtn" aria-label="Toggle navigation"
        aria-expanded="false" data-ui-click="action-a21abd208743">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </header>

  <div class="auth-landing-mobile-menu" id="mobileMenu">
    <nav aria-label="Mobile navigation">
      <a href="#home" data-ui-click="action-bc43f465120b">Home</a>
      <a href="#about" data-ui-click="action-bc43f465120b">About</a>
      <a href="#services" data-ui-click="action-bc43f465120b">Services</a>
      <a href="#faq" data-ui-click="action-bc43f465120b">FAQ</a>
      <a href="#team" data-ui-click="action-bc43f465120b">Team</a>
    </nav>

    <div class="auth-landing-mobile-login-actions">
      <a href="/auth/oidc/redirect" class="auth-landing-mobile-sso" data-oidc-login-link>
        <i class="fa-solid fa-arrow-right-to-bracket"></i>
        Login with SSO
      </a>
      @unless ($idpAvailable ?? true)
      <a href="{{ route('backup.login') }}" class="auth-landing-mobile-local">
        <i class="fa-solid fa-key"></i>
        Login Locally
      </a>
      @endunless
    </div>
  </div>

  <main>
    <section class="auth-landing-hero" id="home">
      <div class="auth-landing-hero-grid">
        <div class="auth-landing-hero-copy auth-landing-reveal">
          <h1 class="auth-landing-hero-title">
            PUP Taguig Dental Clinic
          </h1>

          <p class="auth-landing-hero-desc">
            Professional, accessible, and organized dental care for students, faculty, and staff of
            PUP Taguig — from appointment booking to digital patient records.
          </p>

          <div class="auth-landing-hero-actions">
            <a href="/auth/oidc/redirect" class="auth-landing-primary-login" data-oidc-login-link>
              <span class="auth-landing-action-icon">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
              </span>
              <span>
                <small>Recommended</small>
                Login with SSO
              </span>
              <i class="fa-solid fa-arrow-right auth-landing-action-arrow"></i>
            </a>

            @unless ($idpAvailable ?? true)
            <a href="{{ route('backup.login') }}" class="auth-landing-secondary-login">
              <span class="auth-landing-action-icon">
                <i class="fa-solid fa-key"></i>
              </span>
              <span>
                <small>Admin fallback</small>
                Login Locally
              </span>
            </a>
            @endunless
          </div>

          <div class="auth-landing-trust-row">
            <div>
              <i class="fa-solid fa-shield-halved"></i>
              <span>Secure authentication</span>
            </div>
            <div>
              <i class="fa-solid fa-calendar-check"></i>
              <span>Online appointments</span>
            </div>
            <div>
              <i class="fa-solid fa-folder-open"></i>
              <span>Digital dental records</span>
            </div>
          </div>
        </div>

        <div class="auth-landing-hero-visual auth-landing-reveal auth-landing-reveal-d2">
          @php
          $campusImageCandidates = [
          [
          'file' => 'PUPT_Dental_1.png',
          'alt' => 'PUP Taguig Campus',
          ],
          [
          'file' => 'PUPT_Dental_2.png',
          'alt' => 'PUP Taguig Campus facilities',
          ],
          [
          'file' => 'PUPT_Dental_3.png',
          'alt' => 'PUP Taguig Campus grounds',
          ],
          [
          'file' => 'PUPT_Dental_4.png',
          'alt' => 'PUP Taguig Campus building',
          ],
          [
          'file' => 'PUPT_Dental_5.png',
          'alt' => 'PUP Taguig Campus building',
          ]
          ];

          $campusSlides = collect($campusImageCandidates)
          ->filter(fn($slide) => file_exists(public_path('images/' . $slide['file'])))
          ->values();
          @endphp

          <div class="auth-landing-campus-card auth-landing-campus-card-animated" data-campus-carousel
            data-carousel-interval="3200">

            <div class="auth-landing-campus-image">
              <div class="auth-landing-campus-slides">
                @foreach ($campusSlides as $index => $slide)
                <figure class="auth-landing-campus-slide {{ $index === 0 ? 'is-active' : '' }}" data-campus-slide
                  aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                  <img src="{{ asset('images/' . $slide['file']) }}" alt="{{ $slide['alt'] }}">
                </figure>
                @endforeach
              </div>

              <div class="auth-landing-campus-overlay"></div>

              <div class="auth-landing-campus-brand">
                <img src="{{ asset('images/PUP.png') }}" alt="">
                <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" alt="">
              </div>

              <div class="auth-landing-campus-caption">
                <span>Polytechnic University of the Philippines</span>
                <strong>Taguig Campus Dental Clinic</strong>
              </div>

              @if ($campusSlides->count() > 1)
              <div class="auth-landing-campus-carousel-controls" aria-label="Campus photo carousel">
                <button type="button" class="auth-landing-campus-carousel-btn" data-campus-prev
                  aria-label="Previous campus photo">
                  <i class="fa-solid fa-chevron-left"></i>
                </button>

                <div class="auth-landing-campus-carousel-dots">
                  @foreach ($campusSlides as $index => $slide)
                  <button type="button" class="auth-landing-campus-carousel-dot {{ $index === 0 ? 'is-active' : '' }}"
                    data-campus-dot="{{ $index }}" aria-label="Show campus photo {{ $index + 1 }}"
                    aria-current="{{ $index === 0 ? 'true' : 'false' }}">
                  </button>
                  @endforeach
                </div>

                <button type="button" class="auth-landing-campus-carousel-btn" data-campus-next
                  aria-label="Next campus photo">
                  <i class="fa-solid fa-chevron-right"></i>
                </button>
              </div>
              @endif
            </div>

            <div class="auth-landing-campus-meta">
              <div>
                <span class="auth-landing-meta-icon"><i class="fa-solid fa-user-doctor"></i></span>
                <span>
                  <small>Campus Dentist</small>
                  <strong>Dr. Nelson P. Angeles</strong>
                </span>
              </div>

              <div>
                <span class="auth-landing-meta-icon"><i class="fa-regular fa-clock"></i></span>
                <span>
                  <small>Clinic Hours</small>
                  <strong>Mon–Fri · 8:00 AM–5:00 PM</strong>
                </span>
              </div>
            </div>
          </div>

          <div class="auth-landing-floating-card auth-landing-floating-card-top auth-landing-float-top">
            <span class="auth-landing-floating-icon">
              <i class="fa-solid fa-calendar-check"></i>
            </span>
            <span>
              <small>Book online</small>
              <strong>Choose available clinic slots</strong>
            </span>
          </div>

          <div class="auth-landing-floating-card auth-landing-floating-card-bottom auth-landing-float-bottom">
            <span class="auth-landing-floating-icon">
              <i class="fa-solid fa-shield-heart"></i>
            </span>
            <span>
              <small>Campus care</small>
              <strong>Secure & patient-centered</strong>
            </span>
          </div>
        </div>
      </div>
    </section>

    <section class="auth-landing-about" id="about">
      <div class="auth-landing-section-wrap">
        <div class="auth-landing-section-intro auth-landing-reveal">
          <div class="auth-landing-section-kicker">About the Clinic</div>
          <h2>Dental care designed around the PUP Taguig community.</h2>
          <p>
            The clinic combines professional dental services with a digital workflow that helps patients
            schedule visits, access records, and stay informed throughout their care.
          </p>
        </div>

        <div class="auth-landing-about-grid">
          <article class="auth-landing-feature-card auth-landing-reveal">
            <div class="auth-landing-feature-card-icon">
              <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
            <span>01</span>
            <h3>Accessible Campus Care</h3>
            <p>Dental services are available to eligible PUP Taguig students, faculty, and staff.</p>
          </article>

          <article class="auth-landing-feature-card auth-landing-reveal auth-landing-reveal-d1">
            <div class="auth-landing-feature-card-icon">
              <i class="fa-solid fa-calendar-days"></i>
            </div>
            <span>02</span>
            <h3>Organized Scheduling</h3>
            <p>Book available appointments online with clearer clinic schedules and visit tracking.</p>
          </article>

          <article class="auth-landing-feature-card auth-landing-reveal auth-landing-reveal-d2">
            <div class="auth-landing-feature-card-icon">
              <i class="fa-solid fa-file-waveform"></i>
            </div>
            <span>03</span>
            <h3>Connected Records</h3>
            <p>Dental history and treatment information stay organized in one secure patient portal.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="auth-landing-dentist" id="dentist">
      <div class="auth-landing-section-wrap">
        <div class="auth-landing-dentist-grid">
          <div class="auth-landing-dentist-profile auth-landing-reveal">
            <div class="auth-landing-dentist-photo">
              <img src="{{ asset('images/Nelson-Angeles.jpg') }}" alt="Dr. Nelson P. Angeles"
                data-ui-error="action-01ea3b1981b5">
            </div>
            <div class="auth-landing-dentist-profile-copy">
              <small>University Campus Dentist</small>
              <h3>Dr. Nelson P. Angeles</h3>
              <p>PUP Taguig Campus Dental Clinic</p>
            </div>
          </div>

          <div class="auth-landing-dentist-copy auth-landing-reveal auth-landing-reveal-d1">
            <div class="auth-landing-section-kicker">Our Dentist</div>
            <h2>Professional care with a focus on comfort, prevention, and continuity.</h2>
            <p>
              The clinic supports consultations, treatment planning, preventive care, and follow-up
              visits through a streamlined digital workflow.
            </p>

            <div class="auth-landing-dentist-points">
              <div>
                <i class="fa-solid fa-circle-check"></i>
                <span>Licensed campus dentist</span>
              </div>
              <div>
                <i class="fa-solid fa-circle-check"></i>
                <span>Patient-centered treatment planning</span>
              </div>
              <div>
                <i class="fa-solid fa-circle-check"></i>
                <span>Integrated visit and record management</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="auth-landing-services" id="services">
      <div class="auth-landing-section-wrap">
        <div class="auth-landing-section-intro auth-landing-section-intro-split auth-landing-reveal">
          <div>
            <div class="auth-landing-section-kicker">Dental Services</div>
            <h2>Essential services for preventive and restorative dental care.</h2>
          </div>
          <p>
            Available procedures are managed through the clinic system so appointments and treatment
            records stay connected.
          </p>
        </div>

        <div class="auth-landing-services-grid">
          <article class="auth-landing-service-card auth-landing-reveal">
            <div class="auth-landing-service-icon"><i class="fa-solid fa-stethoscope"></i></div>
            <div>
              <span>Consultation</span>
              <h3>Oral Check-Up</h3>
              <p>Routine examinations and professional dental consultations.</p>
            </div>
          </article>

          <article class="auth-landing-service-card auth-landing-reveal auth-landing-reveal-d1">
            <div class="auth-landing-service-icon"><i class="fa-solid fa-teeth-open"></i></div>
            <div>
              <span>Preventive</span>
              <h3>Oral Prophylaxis</h3>
              <p>Professional cleaning for plaque, tartar, and surface stain removal.</p>
            </div>
          </article>

          <article class="auth-landing-service-card auth-landing-reveal auth-landing-reveal-d2">
            <div class="auth-landing-service-icon"><i class="fa-solid fa-tooth"></i></div>
            <div>
              <span>Restorative</span>
              <h3>Restoration & Prosthesis</h3>
              <p>Treatment options for damaged or missing teeth based on clinical assessment.</p>
            </div>
          </article>

          <article class="auth-landing-service-card auth-landing-reveal auth-landing-reveal-d3">
            <div class="auth-landing-service-icon"><i class="fa-solid fa-user-doctor"></i></div>
            <div>
              <span>Procedure</span>
              <h3>Dental Surgery</h3>
              <p>Minor surgical procedures and extractions when clinically indicated.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="auth-landing-faq" id="faq">
      <div class="auth-landing-section-wrap auth-landing-faq-wrap">
        <div class="auth-landing-faq-intro auth-landing-reveal">
          <div class="auth-landing-section-kicker">Frequently Asked Questions</div>
          <h2>Need a quick answer before you sign in?</h2>
          <p>Here are the most common questions about clinic access, appointments, and services.</p>
        </div>

        @php
        $faqs = [
        [
        'q' => 'Who can avail of the dental services?',
        'a' =>
        'All students, faculty, and staff of the Polytechnic University of the Philippines – Taguig Campus are eligible
        for free dental services.',
        ],
        [
        'q' => 'How do I book an appointment?',
        'a' =>
        'Log in to the Dental Management System, select an available schedule, complete the required information, and
        confirm your appointment.',
        ],
        [
        'q' => 'Will the dentist prescribe medications?',
        'a' =>
        'Yes. Depending on your dental condition, the dentist may prescribe antibiotics, pain relievers, or other
        necessary medications.',
        ],
        [
        'q' => 'Can I book an appointment anytime?',
        'a' =>
        'Appointments depend on available clinic slots. Early booking is recommended because daily capacity is
        limited.',
        ],
        [
        'q' => 'Can I cancel or reschedule my appointment?',
        'a' =>
        'Patients cannot cancel or reschedule appointments directly through the system. If changes are needed, please
        contact the Dental Clinic. Only the assigned dentist can cancel or reschedule an appointment.',
        ],
        [
        'q' => 'What if the dentist declares OUT on the day of my appointment?',
        'a' =>
        'If the dentist declares OUT and becomes unavailable on the day of your appointment, the affected appointment
        will be cancelled. You will be notified through the system and will need to book a new appointment based on the
        next available clinic schedule.',
        ],
        [
        'q' => 'What services are available?',
        'a' =>
        'Services include oral check-ups, dental cleaning, fillings, extractions, minor dental surgery, restoration,
        prosthetic care, and preventive services.',
        ],
        [
        'q' => 'Are urgent dental cases given priority?',
        'a' =>
        'Urgent cases may be prioritized depending on the clinic schedule and the dentist’s clinical assessment.',
        ],
        [
        'q' => 'Are there restrictions for certain treatments?',
        'a' =>
        'Some procedures may depend on available clinic resources and equipment. The dentist will discuss available
        alternatives when needed.',
        ],
        [
        'q' => 'Are follow-up appointments required?',
        'a' =>
        'Some treatments need follow-up visits. The dentist will advise you when continued care is necessary.',
        ],
        ];
        @endphp

        <div class="auth-landing-faq-list" id="faqList">
          @foreach ($faqs as $i => $faq)
          <article class="auth-landing-faq-item auth-landing-reveal">
            <button type="button" class="auth-landing-faq-trigger" data-ui-click="action-9f9589849531" aria-expanded="false">
              <span class="auth-landing-faq-number">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
              <span class="auth-landing-faq-question">{{ $faq['q'] }}</span>
              <span class="auth-landing-faq-chevron"><i class="fa-solid fa-plus"></i></span>
            </button>
            <div class="auth-landing-faq-answer">
              <div>{{ $faq['a'] }}</div>
            </div>
          </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="auth-landing-team" id="team">
      <div class="auth-landing-section-wrap">
        <div class="auth-landing-section-intro auth-landing-reveal">
          <div class="auth-landing-section-kicker">Development Team</div>
          <h2>Built for the PUP Taguig Dental Clinic.</h2>
          <p>The system was developed to support a more organized and accessible clinic experience.</p>
        </div>

        @php
        $devs = [
        ['img' => 'Althea-Aragon.jpg', 'name' => 'Althea Mae Aragon', 'role' => 'Developer'],
        ['img' => 'Grace-Lim.jpg', 'name' => 'Grace Anne Lim', 'role' => 'Developer'],
        ['img' => 'Hoshea-Lopez.jpg', 'name' => 'Hoshea Shania Lopez', 'role' => 'Developer'],
        ['img' => 'Rain-Romero.jpg', 'name' => 'Dianna Rain Romero', 'role' => 'Developer'],
        ];
        @endphp

        <div class="auth-landing-team-grid">
          @foreach ($devs as $dev)
          <article class="auth-landing-team-card auth-landing-reveal">
            <div class="auth-landing-team-photo">
              <img src="{{ asset('images/' . $dev['img']) }}" alt="{{ $dev['name'] }}"
                data-ui-error="action-8551a98bacd1" data-ui-error-arg-0="https://ui-avatars.com/api/?name={{ urlencode($dev['name']) }}&amp;background=660000&amp;color=FFFFFF&amp;size=400">
            </div>
            <div class="auth-landing-team-copy">
              <h3>{{ $dev['name'] }}</h3>
              <span>{{ $dev['role'] }}</span>
            </div>
          </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="auth-landing-closing">
      <div class="auth-landing-section-wrap">
        <div class="auth-landing-closing-inner auth-landing-reveal">
          <div class="auth-landing-closing-eyebrow">
            <span>PUP Taguig Dental Clinic</span>
          </div>

          <h2 class="auth-landing-closing-heading">
            Mula Sayo,<br>
            <em>Para Sa Bayan.</em>
          </h2>

          <p class="auth-landing-closing-desc">
            Developed to manage appointments and records more effectively, supporting accessible and
            efficient dental care for the entire PUP Taguig community.
          </p>
        </div>
      </div>
    </section>
  </main>
</div>
@endsection

@section('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
  function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const button = document.getElementById('hamburgerBtn');
    const isOpen = menu.classList.toggle('open');

    button.classList.toggle('open', isOpen);
    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    document.body.classList.toggle('auth-landing-menu-open', isOpen);
  }

  function closeMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const button = document.getElementById('hamburgerBtn');

    menu?.classList.remove('open');
    button?.classList.remove('open');
    button?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('auth-landing-menu-open');
  }

  function toggleFaq(button) {
    const item = button.closest('.auth-landing-faq-item');
    const answer = item.querySelector('.auth-landing-faq-answer');
    const isOpen = item.classList.contains('open');

    document.querySelectorAll('.auth-landing-faq-item.open').forEach(openItem => {
      openItem.classList.remove('open');
      openItem.querySelector('.auth-landing-faq-answer').style.maxHeight = '0px';
      openItem.querySelector('.auth-landing-faq-trigger').setAttribute('aria-expanded', 'false');
    });

    if (!isOpen) {
      item.classList.add('open');
      answer.style.maxHeight = `${answer.scrollHeight}px`;
      button.setAttribute('aria-expanded', 'true');
    }
  }

  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;

      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    });
  }, {
    threshold: 0.08
  });

  document.querySelectorAll('.auth-landing-reveal').forEach(element => {
    revealObserver.observe(element);
  });

  function initCampusCarousel() {
    document.querySelectorAll('[data-campus-carousel]').forEach(carousel => {
      const slides = Array.from(carousel.querySelectorAll('[data-campus-slide]'));
      const dots = Array.from(carousel.querySelectorAll('[data-campus-dot]'));
      const prevButton = carousel.querySelector('[data-campus-prev]');
      const nextButton = carousel.querySelector('[data-campus-next]');

      if (slides.length <= 1) {
        return;
      }

      const interval = Number(carousel.dataset.carouselInterval || 5200);
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      let currentIndex = 0;
      let timer = null;

      const showSlide = index => {
        currentIndex = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
          const active = slideIndex === currentIndex;
          slide.classList.toggle('is-active', active);
          slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        dots.forEach((dot, dotIndex) => {
          const active = dotIndex === currentIndex;
          dot.classList.toggle('is-active', active);
          dot.setAttribute('aria-current', active ? 'true' : 'false');
        });
      };

      const stopAutoplay = () => {
        if (timer) {
          window.clearInterval(timer);
          timer = null;
        }
      };

      const startAutoplay = () => {
        stopAutoplay();

        if (prefersReducedMotion || document.hidden) {
          return;
        }

        timer = window.setInterval(() => {
          showSlide(currentIndex + 1);
        }, interval);
      };

      prevButton?.addEventListener('click', () => {
        showSlide(currentIndex - 1);
        startAutoplay();
      });

      nextButton?.addEventListener('click', () => {
        showSlide(currentIndex + 1);
        startAutoplay();
      });

      dots.forEach(dot => {
        dot.addEventListener('click', () => {
          showSlide(Number(dot.dataset.campusDot));
          startAutoplay();
        });
      });

      carousel.addEventListener('mouseenter', stopAutoplay);
      carousel.addEventListener('mouseleave', startAutoplay);
      carousel.addEventListener('focusin', stopAutoplay);
      carousel.addEventListener('focusout', event => {
        if (!carousel.contains(event.relatedTarget)) {
          startAutoplay();
        }
      });

      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          stopAutoplay();
        } else {
          startAutoplay();
        }
      });

      showSlide(0);
      startAutoplay();
    });
  }

  initCampusCarousel();

  function detectBrowserName() {
    const userAgent = navigator.userAgent || '';

    if (navigator.brave) return 'Brave';
    if (userAgent.includes('Edg/')) return 'Edge';
    if (userAgent.includes('OPR/')) return 'Opera';
    if (userAgent.includes('Firefox/')) return 'Firefox';
    if (userAgent.includes('Chrome/')) return 'Chrome';
    if (userAgent.includes('Safari/')) return 'Safari';

    return 'Browser';
  }

  function applyBrowserHintToOidcLinks() {
    const browserName = detectBrowserName();

    document.querySelectorAll('[data-oidc-login-link]').forEach(link => {
      const url = new URL(link.getAttribute('href'), window.location.origin);
      url.searchParams.set('browser_name', browserName);
      link.setAttribute('href', url.pathname + url.search);
    });
  }

  applyBrowserHintToOidcLinks();
</script>
@endsection
