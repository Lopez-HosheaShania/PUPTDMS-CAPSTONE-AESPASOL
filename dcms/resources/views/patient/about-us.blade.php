@extends('layouts.patient')

@section('title', 'About Us | PUP Taguig Dental Clinic')

@section('styles')
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #111827;
            overflow-x: hidden;
        }

        /* Page Header */
        .page-header-title {
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            line-height: 1.1;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, #660000, #8B0000);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Layout Grids */
        .about-top-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            margin-top: 24px;
            margin-bottom: 24px;
        }

        @media (max-width: 900px) {
            .about-top-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Generic Card Styles */
        .about-card {
            background: white;
            border-radius: 1.25rem;
            border: 1px solid rgba(139, 0, 0, 0.1);
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .about-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 34px rgba(139, 0, 0, 0.08);
        }

        .card-icon-bg {
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 140px;
            color: rgba(139, 0, 0, 0.03);
            pointer-events: none;
            z-index: 0;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 800;
            color: #8B0000;
            margin-bottom: 1.25rem;
            position: relative;
            z-index: 1;
        }

        .card-title i {
            background: #FDF1F1;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 1rem;
            color: #8B0000;
        }

        /* Guidelines List */
        .guideline-list {
            list-style: none;
            padding: 0;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .guideline-list li {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 1.25rem;
        }

        .guideline-list li:last-child {
            margin-bottom: 0;
        }

        .guide-icon {
            width: 28px;
            height: 28px;
            background: rgba(139, 0, 0, 0.08);
            color: #8B0000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .guide-text h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .guide-text p {
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.6;
        }

        /* Info Rows (Hours & Location) */
        .info-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px dashed rgba(139, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-row i {
            font-size: 1.25rem;
            color: #8B0000;
            width: 24px;
            text-align: center;
        }

        .info-text p {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 2px;
        }

        .info-text strong {
            font-size: 0.95rem;
            color: #374151;
            font-weight: 700;
        }

        /* Dentist Mini Card */
        .dentist-mini {
            display: flex;
            align-items: center;
            gap: 20px;
            background: linear-gradient(135deg, #8B0000, #660000);
            border-radius: 1rem;
            padding: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            z-index: 1;
            margin-top: 1rem;
        }

        .dentist-mini::before {
            content: '';
            position: absolute;
            right: -20px;
            bottom: -20px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .dentist-img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            flex-shrink: 0;
        }

        .dentist-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dentist-details h4 {
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .dentist-details p {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Developers Grid */
        .dev-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 1.5rem;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 1024px) {
            .dev-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .dev-grid {
                grid-template-columns: 1fr;
            }
        }

        .dev-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f9fafb;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #f3f4f6;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .dev-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 0, 0, 0.05);
        }

        .dev-img {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            overflow: hidden;
            background: #e5e7eb;
            flex-shrink: 0;
        }

        .dev-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dev-info h5 {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1f2937;
        }

        .dev-info span {
            font-size: 0.7rem;
            color: #8B0000;
            font-weight: 600;
            background: rgba(139, 0, 0, 0.08);
            padding: 2px 8px;
            border-radius: 999px;
            display: inline-block;
            margin-top: 4px;
        }

        /* Animations */
        .fade-up {
            animation: fadeUp 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 
        ========================================
           DARK MODE STYLES
        ========================================
        */
        [data-theme="dark"] body,
        [data-theme="dark"] #mainContent {
            background: #000D1A !important;
            color: #F3F4F6 !important;
        }

        [data-theme="dark"] .page-header-title {
            background: linear-gradient(135deg, #FCA5A5, #EF4444) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
        }

        [data-theme="dark"] .about-card {
            background: #161B22 !important;
            border-color: rgba(255, 255, 255, 0.10) !important;
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.3) !important;
        }

        [data-theme="dark"] .about-card:hover {
            border-color: rgba(252, 165, 165, 0.28) !important;
        }

        [data-theme="dark"] .card-icon-bg {
            color: rgba(255, 255, 255, 0.02) !important;
        }

        [data-theme="dark"] .card-title {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .card-title i {
            background: rgba(252, 165, 165, 0.1) !important;
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .guide-text h4,
        [data-theme="dark"] .info-text strong,
        [data-theme="dark"] .dev-info h5 {
            color: #F3F4F6 !important;
        }

        [data-theme="dark"] .guide-text p,
        [data-theme="dark"] .info-text p {
            color: #9CA3AF !important;
        }

        [data-theme="dark"] .guide-icon {
            background: rgba(252, 165, 165, 0.1) !important;
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .info-row {
            border-bottom-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-theme="dark"] .info-row i {
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .dev-item {
            background: #0D1117 !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
        }

        [data-theme="dark"] .dev-info span {
            background: rgba(252, 165, 165, 0.15) !important;
            color: #FCA5A5 !important;
        }

        [data-theme="dark"] .dentist-mini {
            background: linear-gradient(135deg, #7A0000, #4A0000) !important;
        }
    </style>
@endsection

@section('content')
<main id="mainContent" class="page-enter pt-[90px] px-3 md:px-6 py-6 min-h-screen flex-1">
    <div class="w-full">
        
        <div class="mb-4">
            <h1 class="page-header-title">About the Clinic & System</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Information, policies, and the development team behind PUPTDMS.</p>
        </div>

        <!-- Top Grid: Guidelines and Clinic Info -->
        <div class="about-top-grid">
            
            <!-- LEFT COLUMN: Policies -->
            <div class="about-card fade-up delay-1">
                <i class="fa-solid fa-clipboard-check card-icon-bg"></i>
                <h2 class="card-title"><i class="fa-solid fa-list-check"></i> Patient Guidelines</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 relative z-10">To ensure smooth operations and accommodate as many patients as possible, please observe the following clinic rules:</p>
                
                <ul class="guideline-list">
                    <li>
                        <div class="guide-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="guide-text">
                            <h4>Strictly by Appointment</h4>
                            <p>Walk-ins are currently discouraged unless it is a dental emergency. Please book your slots via this portal.</p>
                        </div>
                    </li>
                    <li>
                        <div class="guide-icon"><i class="fa-solid fa-clock"></i></div>
                        <div class="guide-text">
                            <h4>Punctuality is Required</h4>
                            <p>Arrive at the clinic at least 10-15 minutes before your scheduled appointment time. Latecomers may have their slots forfeited.</p>
                        </div>
                    </li>
                    <li>
                        <div class="guide-icon"><i class="fa-solid fa-ban"></i></div>
                        <div class="guide-text">
                            <h4>Appointment Modifications</h4>
                            <p>Patients cannot cancel appointments directly through the system. If you cannot make it to your schedule, please notify the clinic directly so your slot can be given to others.</p>
                        </div>
                    </li>
                    <li>
                        <div class="guide-icon"><i class="fa-solid fa-id-card"></i></div>
                        <div class="guide-text">
                            <h4>Eligibility</h4>
                            <p>Free dental services are strictly for the PUP Taguig community. Please bring a valid PUP ID or registration card on your visit.</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- RIGHT COLUMN: Clinic Info & Dentist -->
            <div class="about-card fade-up delay-2">
                <i class="fa-solid fa-map-location-dot card-icon-bg"></i>
                <h2 class="card-title"><i class="fa-solid fa-building"></i> Clinic Information</h2>
                
                <div class="info-row">
                    <i class="fa-regular fa-clock"></i>
                    <div class="info-text">
                        <p>Operating Hours</p>
                        <strong>Monday – Friday<br>8:00 AM – 5:00 PM</strong>
                    </div>
                </div>
                
                <div class="info-row">
                    <i class="fa-solid fa-location-dot"></i>
                    <div class="info-text">
                        <p>Location</p>
                        <strong>PUP Taguig Campus<br>Gen. Santos Ave, Lower Bicutan</strong>
                    </div>
                </div>

                <div class="info-row">
                    <i class="fa-solid fa-users"></i>
                    <div class="info-text">
                        <p>Patients Accepted</p>
                        <strong>PUPT Students, Alumni,<br>Faculty & Staff</strong>
                    </div>
                </div>

                <!-- Dentist Profile Included inside Clinic Info -->
                <div class="dentist-mini mt-6">
                    <div class="dentist-img">
                        <img src="{{ asset('images/Nelson-Angeles.jpg') }}" alt="Dr. Nelson P. Angeles"
                            onerror="this.src='https://ui-avatars.com/api/?name=Nelson+Angeles&background=660000&color=FFFFFF&size=150'">
                    </div>
                    <div class="dentist-details">
                        <h4>Dr. Nelson Angeles</h4>
                        <p>Campus Dentist</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Bottom Full Width: About PUPTDMS -->
        <div class="about-card fade-up delay-3 mb-6">
            <i class="fa-solid fa-code card-icon-bg"></i>
            <h2 class="card-title"><i class="fa-solid fa-laptop-code"></i> About PUPTDMS</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 relative z-10 max-w-4xl">
                The <strong>PUP Taguig Dental Management System (PUPTDMS)</strong> is a capstone project developed to digitize records, streamline appointment scheduling, and improve the overall efficiency of the campus dental clinic.
            </p>

            <div class="dev-grid">
                @php
                    $devs = [
                        ['img' => 'Althea-Aragon.jpg', 'name' => 'Althea Mae Aragon'],
                        ['img' => 'Grace-Lim.jpg', 'name' => 'Grace Anne Lim'],
                        ['img' => 'Hoshea-Lopez.jpg', 'name' => 'Hoshea Shania Lopez'],
                        ['img' => 'Rain-Romero.jpg', 'name' => 'Dianna Rain Romero'],
                    ];
                @endphp

                @foreach ($devs as $dev)
                    <div class="dev-item">
                        <div class="dev-img">
                            <img src="{{ asset('images/' . $dev['img']) }}" alt="{{ $dev['name'] }}"
                                onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($dev['name']) }}&background=8B0000&color=FFFFFF&size=100'">
                        </div>
                        <div class="dev-info">
                            <h5>{{ $dev['name'] }}</h5>
                            <span>Developer</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</main>
@endsection