@extends('layouts.patient')

@section('title', 'About Us | PUP Taguig Dental Clinic')

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