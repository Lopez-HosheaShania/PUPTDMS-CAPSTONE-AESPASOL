@extends($profileLayout ?? 'layouts.dentist')

@section('title', 'Patient Profile | PUP Taguig Dental Clinic')

@section('usesPatientProfile', true)

@section('content')
    @php
        use Carbon\Carbon;

        $profileMode = $profileMode ?? 'dentist';
        $isDentistProfile = $profileMode === 'dentist';

        $patientName = $patient->name ?? 'Unknown Patient';
        $displayName = ucwords(strtolower($patient->name ?? 'Guest'));
        $age = $patient->birthdate ? Carbon::parse($patient->birthdate)->age : null;
        $birthdateFormatted = $patient->birthdate ? Carbon::parse($patient->birthdate)->format('M d, Y') : 'N/A';

        $futureCount = isset($futureVisits) ? $futureVisits->count() : 0;
        $pastCount = isset($pastVisits) ? $pastVisits->count() : 0;

        $medicalAnswers = optional($patient->medicalHistory)->answers ?? collect();
        $dentalDates = optional($patient->dentalHistoryDates);

        $from = request('from');

        if ($profileMode === 'admin') {
            $backUrl = $from === 'patients' ? route('admin.admin.patients') : route('admin.admin.appointments');

            $backLabel = $from === 'patients' ? 'Patients' : 'Appointments';
        } else {
            $backUrl =
                $from === 'dashboard' ? route('dentist.dentist.dashboard') : route('dentist.dentist.appointments');

            $backLabel = $from === 'dashboard' ? 'Dashboard' : 'Appointments';
        }

        $patientAvatar = $patient->profile_image ?? null;
        $userAvatar = optional($patient->user)->profile_image ?? null;

        if (!empty($patientAvatar)) {
            $avatarUrl = asset('storage/' . $patientAvatar);
        } elseif (!empty($userAvatar)) {
            $avatarUrl = asset('storage/' . $userAvatar);
        } else {
            $avatarUrl =
                'https://ui-avatars.com/api/?name=' .
                urlencode($displayName) .
                '&background=8B0000&color=ffffff&bold=true';
        }

        $patientType = $patient->faculty_code ? 'Faculty' : ($patient->student_no ? 'Student' : 'Patient');

        $procedureAppointment = $nextAppointment ?? collect($futureVisits ?? [])->first();
    @endphp

    <main id="mainContent" class="patient-profile-page pt-[100px] px-3 md:px-6 py-6 min-h-screen flex-1">
        <div class="w-full fade-in">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <p class="text-xs text-gray-500 mb-1.5 font-medium uppercase tracking-wider">
                        <a href="{{ $backUrl }}" class="hover:text-[#8B0000] transition">{{ $backLabel }}</a>
                        <span class="mx-1">/</span> Patient Record
                    </p>

                    <div class="flex items-center gap-3">
                        <a href="{{ $backUrl }}"
                            class="flex items-center justify-center w-8 h-8 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition shadow-sm">
                            <i class="fa-solid fa-arrow-left text-sm"></i>
                        </a>

                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Patient Profile</h1>
                    </div>
                </div>

                @if ($isDentistProfile && $procedureAppointment)
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openStartModal()"
                            class="flex items-center gap-2 px-5 py-2.5 bg-[#8B0000] text-white rounded-lg text-sm font-bold shadow-md hover:bg-[#6b0000] transition">
                            <i class="fa-solid fa-play text-xs"></i> Start Procedure
                        </button>
                    </div>
                @endif
            </div>

            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <div class="w-full lg:w-[400px] xl:w-[450px] 2xl:w-[480px] flex-shrink-0 lg:sticky lg:top-[80px]">
                    <div id="profileContainer">
                        <div class="glass-card overflow-hidden fade-up">
                            <div class="h-24 bg-gradient-to-r from-[#8B0000] to-[#b30000] relative"></div>

                            <div class="px-5 pb-5 relative flex flex-col items-center mt-[-40px]">
                                <div class="relative mb-3">
                                    <img src="{{ $avatarUrl }}" alt="Profile"
                                        class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md bg-white">
                                </div>

                                <h2 class="text-[19px] font-extrabold text-gray-900 text-center leading-tight">
                                    {{ $displayName }}
                                </h2>

                                <p class="text-[13px] font-medium text-gray-500 mt-1 text-center">
                                    {{ $patientType }}
                                </p>

                                @if ($patient->faculty_code)
                                    <div
                                        class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-xs font-bold tracking-wide">
                                        <i class="fa-regular fa-id-badge text-[10px]"></i>
                                        Faculty Code: {{ $patient->faculty_code }}
                                    </div>
                                @elseif($patient->student_no)
                                    <div
                                        class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-xs font-bold tracking-wide">
                                        <i class="fa-regular fa-id-badge text-[10px]"></i>
                                        Student No: {{ $patient->student_no }}
                                    </div>
                                @endif
                            </div>

                            <div class="border-t border-gray-100"></div>

                            <div class="px-5 py-4 space-y-3 text-sm">
                                <div class="flex justify-between items-center gap-4">
                                    <span class="text-gray-400 font-semibold text-xs flex items-center gap-2">
                                        <i class="fa-solid fa-cake-candles w-3"></i>
                                        Age <br> Date of Birth
                                    </span>

                                    <span class="text-gray-800 font-medium text-right">
                                        {{ $age ? $age . ' yrs' : 'N/A' }}
                                        <span class="text-gray-400 text-xs font-normal block">
                                            {{ $birthdateFormatted }}
                                        </span>
                                    </span>
                                </div>

                                <div class="flex justify-between items-center gap-4">
                                    <span class="text-gray-400 font-semibold text-xs flex items-center gap-2">
                                        <i class="fa-solid fa-venus-mars w-3"></i>
                                        Gender
                                    </span>

                                    <span class="text-gray-800 font-medium text-right">
                                        {{ $patient->gender ?? 'N/A' }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-start gap-4">
                                    <span class="text-gray-400 font-semibold text-xs flex items-center gap-2 mt-0.5">
                                        <i class="fa-solid fa-phone w-3"></i>
                                        Contact
                                    </span>

                                    <span class="text-gray-800 font-medium text-right">
                                        {{ $patient->phone ?? 'N/A' }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-start gap-3">
                                    <span
                                        class="text-gray-400 font-semibold text-xs flex items-center gap-2 mt-0.5 flex-shrink-0 w-[92px]">
                                        <i class="fa-solid fa-envelope w-3"></i>
                                        Email
                                    </span>

                                    <span
                                        class="text-gray-800 font-medium text-right break-words leading-snug flex-1 min-w-0">
                                        {{ $patient->email ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <div class="bg-red-50/50 px-5 py-4 border-t border-red-100">
                                <p
                                    class="text-[10px] font-bold text-red-800 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-heart-pulse"></i>
                                    Emergency Contact
                                </p>

                                @if (optional($patient->medicalHistory)->emergency_person)
                                    <p class="text-sm font-bold text-gray-900">
                                        {{ optional($patient->medicalHistory)->emergency_person }}
                                    </p>

                                    <p class="text-xs font-medium text-gray-600 mt-0.5">
                                        <i class="fa-solid fa-phone text-[10px] mr-1"></i>
                                        {{ optional($patient->medicalHistory)->emergency_number ?? 'N/A' }}

                                        @if (optional($patient->medicalHistory)->emergency_relation)
                                            <span class="ml-1 text-gray-400">
                                                ({{ optional($patient->medicalHistory)->emergency_relation }})
                                            </span>
                                        @endif
                                    </p>
                                @else
                                    <div class="text-center py-2">
                                        <i class="fa-solid fa-user-plus text-red-300 text-lg mb-1"></i>
                                        <p class="text-xs text-gray-400 font-medium mb-2">
                                            No emergency contact added
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 min-w-0 flex flex-col gap-6 max-w-[1100px]">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="glass-card p-4 flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                                <i class="fa-regular fa-calendar-check text-xl"></i>
                            </div>

                            <div>
                                <p class="text-2xl font-extrabold text-gray-900 leading-none">
                                    {{ $totalVisits ?? $pastCount + $futureCount }}
                                </p>
                                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mt-1">
                                    Total Visits
                                </p>
                            </div>
                        </div>

                        <div class="glass-card p-4 flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 flex-shrink-0">
                                <i class="fa-solid fa-clock-rotate-left text-xl"></i>
                            </div>

                            <div>
                                <p class="text-sm font-bold text-gray-900 truncate max-w-[120px]">
                                    {{ $lastVisit?->appointment_date
                                        ? Carbon::parse($lastVisit->appointment_date)->format('M d, Y')
                                        : 'No past visits' }}
                                </p>
                                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mt-1">
                                    Last Visit
                                </p>
                            </div>
                        </div>

                        <div class="glass-card p-4 flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center text-orange-500 flex-shrink-0">
                                <i class="fa-regular fa-calendar-plus text-xl"></i>
                            </div>

                            <div>
                                <p class="text-sm font-bold text-gray-900 truncate max-w-[120px]">
                                    {{ $nextAppointment?->appointment_date
                                        ? Carbon::parse($nextAppointment->appointment_date)->format('M d, Y')
                                        : 'No schedule' }}
                                </p>
                                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mt-1">
                                    Next Appointment
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                                <i class="fa-solid fa-folder-open text-[#8B0000]"></i>
                                Treatment History
                            </h2>
                        </div>

                        <div class="flex gap-2 mb-6 bg-gray-100 p-1 rounded-lg w-fit">
                            <button id="futureTab" onclick="showFuture()"
                                class="visit-tab px-4 py-1.5 text-sm font-bold text-[#8B0000] bg-white shadow-sm rounded-md transition-all">
                                Upcoming ({{ $futureCount }})
                            </button>

                            <button id="pastTab" onclick="showPast()"
                                class="visit-tab px-4 py-1.5 text-sm font-semibold text-gray-500 hover:text-gray-700 rounded-md transition-all">
                                Past Visits ({{ $pastCount }})
                            </button>
                        </div>

                        <div id="futureContent" class="space-y-3">
                            @forelse($futureVisits ?? [] as $visit)
                                @php
                                    $visitDate = $visit->appointment_date
                                        ? Carbon::parse($visit->appointment_date)->format('d M Y')
                                        : 'N/A';
                                    $visitTime = $visit->appointment_time
                                        ? Carbon::parse($visit->appointment_time)->format('g:i A')
                                        : 'N/A';
                                    $visitService = $visit->service_type ?? 'Appointment';
                                    $visitStatus = $visit->status ?? 'upcoming';
                                @endphp

                                <div
                                    class="group border border-gray-200 rounded-xl p-4 flex flex-col md:flex-row md:items-center gap-4 hover:border-[#8B0000]/30 hover:shadow-md transition-all bg-white relative overflow-hidden">
                                    <div class="status-accent accent-gray js-status-accent"
                                        data-status="{{ strtolower($visitStatus) }}"></div>

                                    <div class="flex-shrink-0 w-[140px] pl-2">
                                        <p class="font-extrabold text-gray-900 text-sm">{{ $visitDate }}</p>
                                        <p class="text-[12px] font-medium text-gray-500 mt-0.5">
                                            <i class="fa-regular fa-clock mr-1"></i>
                                            {{ $visitTime }}
                                        </p>
                                    </div>

                                    <div class="flex-1">
                                        <span class="status-badge js-status-badge"
                                            data-status="{{ strtolower($visitStatus) }}">
                                            {{ $visitStatus }}
                                        </span>

                                        <p class="text-sm font-bold text-gray-800">{{ $visitService }}</p>
                                        <p class="text-[11px] font-semibold text-gray-400 mt-0.5">
                                            Dentist:
                                            <span class="text-gray-600">
                                                {{ $visit->dentist->name ?? 'Dr. Angeles' }}
                                            </span>
                                        </p>
                                    </div>

                                    <div class="flex-shrink-0">
                                        <button
                                            onclick="openDetailsDrawer({{ $visit->id }}, @js($visitDate), @js($visitTime), @js($visitService), @js($visitStatus))"
                                            class="w-full md:w-auto px-4 py-2 bg-gray-50 hover:bg-[#8B0000] text-gray-600 hover:text-white border border-gray-200 hover:border-[#8B0000] rounded-lg text-xs font-bold transition-colors">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="py-8 text-center border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50">
                                    <p class="text-gray-600 font-bold text-sm">No upcoming appointments</p>
                                </div>
                            @endforelse
                        </div>

                        <div id="pastContent" class="hidden space-y-3">
                            @forelse($pastVisits ?? [] as $visit)
                                @php
                                    $visitDate = $visit->appointment_date
                                        ? Carbon::parse($visit->appointment_date)->format('d M Y')
                                        : 'N/A';
                                    $visitTime = $visit->appointment_time
                                        ? Carbon::parse($visit->appointment_time)->format('g:i A')
                                        : 'N/A';
                                    $visitService = $visit->service_type ?? 'Appointment';
                                    $visitStatus = $visit->status ?? 'completed';
                                @endphp

                                <div
                                    class="group border border-gray-200 rounded-xl p-4 flex flex-col md:flex-row md:items-center gap-4 hover:border-gray-300 hover:shadow-sm transition-all bg-white relative overflow-hidden">
                                    <div class="status-accent accent-gray js-status-accent"
                                        data-status="{{ strtolower($visitStatus) }}"></div>

                                    <div class="flex-shrink-0 w-[140px] pl-2">
                                        <p class="font-extrabold text-gray-600 text-sm">{{ $visitDate }}</p>
                                        <p class="text-[12px] font-medium text-gray-400 mt-0.5">
                                            <i class="fa-regular fa-clock mr-1"></i>
                                            {{ $visitTime }}
                                        </p>
                                    </div>

                                    <div class="flex-1">
                                        <span class="status-badge js-status-badge"
                                            data-status="{{ strtolower($visitStatus) }}">
                                            {{ $visitStatus }}
                                        </span>

                                        <p class="text-sm font-bold text-gray-700">{{ $visitService }}</p>
                                    </div>

                                    <div class="flex-shrink-0">
                                        <button
                                            onclick="openDetailsDrawer({{ $visit->id }}, @js($visitDate), @js($visitTime), @js($visitService), @js($visitStatus))"
                                            class="w-full md:w-auto px-4 py-2 bg-white hover:bg-gray-50 text-gray-600 border border-gray-200 rounded-lg text-xs font-bold transition-colors">
                                            View Record
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="py-8 text-center border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50">
                                    <p class="text-gray-600 font-bold text-sm">No past records</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="glass-card p-6 mb-10">
                        <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-4">
                            <h2 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                                <i class="fa-solid fa-notes-medical text-[#8B0000]"></i>
                                Health & Lifestyle Information
                            </h2>

                            <span class="text-[10px] text-gray-400 font-medium bg-gray-100 px-2 py-1 rounded">
                                Latest Record
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-6">
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">
                                        Dental History
                                    </p>

                                    <div class="space-y-2 text-sm">
                                        <p><span class="font-semibold text-gray-600">Last Dental Visit:</span>
                                            {{ optional($patient->dentalHistory)->last_dental_visit ?? 'N/A' }}</p>
                                        <p><span class="font-semibold text-gray-600">Previous Dentist:</span>
                                            {{ optional($patient->dentalHistory)->previous_dentist ?? 'N/A' }}</p>
                                        <p><span class="font-semibold text-gray-600">Extraction Date:</span>
                                            {{ $dentalDates->extraction_date ?? 'N/A' }}</p>
                                        <p><span class="font-semibold text-gray-600">Dentures Date:</span>
                                            {{ $dentalDates->dentures_date ?? 'N/A' }}</p>
                                        <p><span class="font-semibold text-gray-600">Orthodontic Treatment Date:</span>
                                            {{ $dentalDates->ortho_date ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">
                                        Dental Symptoms & Habits
                                    </p>

                                    <div class="flex flex-wrap gap-1.5">
                                        @php $hasDentalAnswer = false; @endphp

                                        @foreach ($patient->dentalHistoryAnswers ?? [] as $dentAnswer)
                                            @if ($dentAnswer->answer)
                                                @php $hasDentalAnswer = true; @endphp
                                                <span
                                                    class="bg-teal-50 text-teal-700 text-[11px] font-bold px-2.5 py-1 rounded border border-teal-100">
                                                    {{ str_replace('_', ' ', Str::title(optional($dentAnswer->condition)->code ?? 'Symptom')) }}
                                                </span>
                                            @endif
                                        @endforeach

                                        @if (!$hasDentalAnswer)
                                            <span
                                                class="text-xs text-gray-400 font-medium bg-gray-50 px-3 py-1 rounded border border-gray-100">
                                                No symptoms reported
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">
                                        Medical History
                                    </p>

                                    <div class="space-y-2 text-sm">
                                        @forelse($medicalAnswers as $mAns)
                                            @if ($mAns->answer_bool === true || !empty($mAns->answer_text) || !empty($mAns->answer_date))
                                                <p>
                                                    <span class="font-semibold text-gray-600">
                                                        {{ str_replace('_', ' ', Str::title(optional($mAns->question)->code ?? 'Question')) }}:
                                                    </span>
                                                    @if ($mAns->answer_bool === true)
                                                        YES
                                                    @endif
                                                    @if (!empty($mAns->answer_text))
                                                        {{ $mAns->answer_text }}
                                                    @endif
                                                    @if (!empty($mAns->answer_date))
                                                        {{ $mAns->answer_date }}
                                                    @endif
                                                </p>
                                            @endif
                                        @empty
                                            <p class="text-xs text-gray-400">No medical records found.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">
                                        Medical Conditions
                                    </p>

                                    <div class="flex flex-wrap gap-1.5">
                                        @if (isset($patient->medicalHistory->diseaseAnswers) && $patient->medicalHistory->diseaseAnswers->count() > 0)
                                            @foreach ($patient->medicalHistory->diseaseAnswers as $diseaseAnswer)
                                                <span
                                                    class="bg-purple-50 text-purple-700 text-[11px] font-bold px-2.5 py-1 rounded border border-purple-100">
                                                    {{ $diseaseAnswer->disease->label ?? 'Condition' }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span
                                                class="text-xs text-gray-400 font-medium bg-gray-50 px-3 py-1 rounded border border-gray-100">
                                                None reported
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-5 border-t border-gray-100">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">
                                Additional Dental Concerns
                            </p>

                            @php
                                $concerns = optional($patient->dentalHistoryConcerns)->additional_concerns ?? null;
                            @endphp

                            @if ($concerns)
                                <div
                                    class="text-[13px] text-gray-700 leading-relaxed bg-yellow-50/50 p-4 rounded-lg border border-yellow-100">
                                    {{ $concerns }}
                                </div>
                            @else
                                <p class="text-xs text-gray-400 italic">No additional concerns added.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @if ($isDentistProfile)
        <div id="startModal"
            class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden z-50 transition-opacity items-center justify-center">

            <div class="bg-white w-full max-w-md rounded-2xl p-6 md:p-8 relative shadow-2xl mx-4 transform transition-transform scale-95"
                id="startModalContent">

                <div class="w-12 h-12 bg-red-50 text-[#8B0000] rounded-full flex items-center justify-center mb-5">
                    <i class="fa-solid fa-play text-xl"></i>
                </div>

                <h2 class="text-xl font-extrabold text-gray-900 mb-2">Start Procedure?</h2>

                <p class="text-sm text-gray-500 mb-6">
                    You are about to start a new dental procedure session for this patient. Do you want to continue?
                </p>

                <div class="flex gap-3">
                    <button type="button" onclick="closeStartModal()"
                        class="flex-1 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 font-bold px-4 py-2.5 rounded-xl transition text-sm">
                        Cancel
                    </button>

                    <button type="button" onclick="confirmStart()"
                        class="flex-1 bg-[#8B0000] hover:bg-[#6b0000] text-white shadow-md shadow-red-900/20 font-bold px-4 py-2.5 rounded-xl transition text-sm">
                        Yes, Start
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div id="drawerOverlay" class="drawer-overlay fixed left-0 right-0 bottom-0 z-[110]" style="top: var(--header-h);"
        onclick="closeDetailsDrawer()"></div>

    <div id="detailsDrawer"
        class="side-drawer fixed right-0 bottom-0 w-full max-w-[500px] bg-white shadow-[-10px_0_40px_rgba(0,0,0,0.1)] z-[120] flex flex-col"
        style="top: var(--header-h); height: calc(100vh - var(--header-h));">
        <div
            class="bg-gradient-to-r from-[#8B0000] to-[#b30000] px-6 py-5 md:py-6 flex items-start justify-between text-white flex-shrink-0">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/70 mb-1">
                    Appointment Details
                </p>
                <h2 id="drawerService" class="text-xl font-extrabold leading-tight">Service Type</h2>
                <div class="flex items-center gap-3 mt-2 text-sm font-medium text-white/90">
                    <span class="flex items-center gap-1.5"><i class="fa-regular fa-calendar"></i> <span
                            id="drawerDate">Date</span></span>
                    <span>|</span>
                    <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock"></i> <span
                            id="drawerTime">Time</span></span>
                </div>
            </div>

            <button onclick="closeDetailsDrawer()"
                class="w-8 h-8 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="px-6 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center flex-shrink-0">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Status</span>
            <span id="drawerStatus"
                class="inline-flex px-3 py-1 rounded-md text-[11px] font-extrabold bg-orange-100 text-orange-700 uppercase tracking-wide">
                STATUS
            </span>
        </div>

        <div id="drawerBody" class="flex-1 overflow-y-auto p-6 space-y-6 bg-[#F9FAFB]">
            <section id="statusMetaSection" class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hidden">
                <h3
                    class="flex items-center gap-2 text-sm font-bold text-[#8B0000] uppercase tracking-widest mb-4 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-circle-info"></i> Status Details
                </h3>

                <div class="space-y-2 text-sm">
                    <p id="rescheduledToMetaRow" class="hidden">
                        <span class="font-semibold text-gray-600">Rescheduled To:</span>
                        <span id="detailRescheduledTo">Not available</span>
                    </p>
                </div>
            </section>

            <section class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h3
                    class="flex items-center gap-2 text-sm font-bold text-[#8B0000] uppercase tracking-widest mb-4 border-b border-gray-100 pb-2">
                    <i class="fa-regular fa-calendar"></i> Appointment Information
                </h3>

                <div class="space-y-2 text-sm">
                    <p><span class="font-semibold text-gray-600">Appointment Date:</span> <span
                            id="detailAppointmentDate">N/A</span></p>
                    <p><span class="font-semibold text-gray-600">Appointment Time:</span> <span
                            id="detailAppointmentTime">N/A</span></p>
                    <p><span class="font-semibold text-gray-600">Service Type:</span> <span
                            id="detailServiceType">N/A</span></p>
                    <p><span class="font-semibold text-gray-600">Status:</span> <span id="detailStatusText">N/A</span></p>
                </div>
            </section>

            <section class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h3
                    class="flex items-center gap-2 text-sm font-bold text-[#8B0000] uppercase tracking-widest mb-4 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-notes-medical"></i> Clinical Notes
                </h3>

                <div class="space-y-4 text-sm">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase mb-1">Treatment</p>
                        <p id="detailTreatment" class="text-gray-800">No treatment record yet.</p>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase mb-1">Oral Examination</p>
                        <p id="detailOralExam" class="text-gray-800">No oral examination record yet.</p>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase mb-1">Diagnosis</p>
                        <p id="detailDiagnosis" class="text-gray-800">No diagnosis record yet.</p>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold text-gray-400 uppercase mb-1">Prescription</p>
                        <p id="detailPrescription" class="text-gray-800">No prescription recorded.</p>
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h3
                    class="flex items-center gap-2 text-sm font-bold text-[#8B0000] uppercase tracking-widest mb-4 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-calendar-plus"></i> Follow-up Appointment
                </h3>

                <p id="detailFollowUp" class="text-sm text-gray-800">No follow-up appointment scheduled.</p>
            </section>

            <section class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h3
                    class="flex items-center gap-2 text-sm font-bold text-[#8B0000] uppercase tracking-widest mb-4 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-tooth"></i> Odontogram
                </h3>

                <div id="detailOdontogram" class="text-sm text-gray-800">No odontogram record yet.</div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const STATUS_THEME = {
            today: {
                badge: 'status-blue',
                accent: 'accent-blue'
            },
            scheduled_today: {
                badge: 'status-blue',
                accent: 'accent-blue'
            },
            upcoming: {
                badge: 'status-orange',
                accent: 'accent-orange'
            },
            rescheduled: {
                badge: 'status-yellow',
                accent: 'accent-yellow'
            },
            cancelled: {
                badge: 'status-red',
                accent: 'accent-red'
            },
            completed: {
                badge: 'status-green',
                accent: 'accent-green'
            },
            default: {
                badge: 'status-gray',
                accent: 'accent-gray'
            }
        };

        function getStatusTheme(status) {
            const s = (status || '').toLowerCase().trim();

            if (s === 'scheduled today' || s === 'today') return STATUS_THEME.today;
            if (s.includes('upcoming')) return STATUS_THEME.upcoming;
            if (s.includes('rescheduled')) return STATUS_THEME.rescheduled;
            if (s.includes('cancelled')) return STATUS_THEME.cancelled;
            if (s.includes('completed')) return STATUS_THEME.completed;

            return STATUS_THEME.default;
        }

        function applyStatusTheme(el, type, status) {
            const theme = getStatusTheme(status);

            if (type === 'badge') {
                el.classList.remove('status-blue', 'status-orange', 'status-yellow', 'status-red', 'status-green',
                    'status-gray');
                el.classList.add(theme.badge);
            }

            if (type === 'accent') {
                el.classList.remove('accent-blue', 'accent-orange', 'accent-yellow', 'accent-red', 'accent-green',
                    'accent-gray');
                el.classList.add(theme.accent);
            }
        }

        function initStatusThemes() {
            document.querySelectorAll('.js-status-badge').forEach(el => {
                applyStatusTheme(el, 'badge', el.dataset.status);
            });

            document.querySelectorAll('.js-status-accent').forEach(el => {
                applyStatusTheme(el, 'accent', el.dataset.status);
            });
        }

        function showFuture() {
            document.getElementById('futureContent').classList.remove('hidden');
            document.getElementById('pastContent').classList.add('hidden');

            document.getElementById('futureTab').className =
                'visit-tab px-4 py-1.5 text-sm font-bold text-[#8B0000] bg-white shadow-sm rounded-md transition-all';
            document.getElementById('pastTab').className =
                'visit-tab px-4 py-1.5 text-sm font-semibold text-gray-500 hover:text-gray-700 rounded-md transition-all';
        }

        function showPast() {
            document.getElementById('pastContent').classList.remove('hidden');
            document.getElementById('futureContent').classList.add('hidden');

            document.getElementById('pastTab').className =
                'visit-tab px-4 py-1.5 text-sm font-bold text-[#8B0000] bg-white shadow-sm rounded-md transition-all';
            document.getElementById('futureTab').className =
                'visit-tab px-4 py-1.5 text-sm font-semibold text-gray-500 hover:text-gray-700 rounded-md transition-all';
        }

        function openStartModal() {
            const modal = document.getElementById('startModal');
            const content = document.getElementById('startModalContent');

            if (!modal || !content) return;

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            setTimeout(() => {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);

            document.body.style.overflow = 'hidden';
        }

        function closeStartModal() {
            const modal = document.getElementById('startModal');
            const content = document.getElementById('startModalContent');

            if (!modal || !content) return;

            content.classList.remove('scale-100');
            content.classList.add('scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }, 200);
        }

        function confirmStart() {
            window.location.href = "{{ route('dentist.odontogram.start', ['patient' => $patient->id]) }}";
        }

        function openDetailsDrawer(appointmentId, date, time, service, status) {
            document.getElementById('drawerDate').innerText = date;
            document.getElementById('drawerTime').innerText = time;
            document.getElementById('drawerService').innerText = service;

            const statusEl = document.getElementById('drawerStatus');
            const statusLower = (status || '').toLowerCase();

            statusEl.innerText = status;
            statusEl.className = 'inline-flex px-3 py-1 rounded-md text-[11px] font-extrabold uppercase tracking-wide';
            applyStatusTheme(statusEl, 'badge', status);

            document.getElementById('detailAppointmentDate').innerText = date;
            document.getElementById('detailAppointmentTime').innerText = time;
            document.getElementById('detailServiceType').innerText = service;
            document.getElementById('detailStatusText').innerText = status;

            document.getElementById('detailTreatment').innerText = 'No treatment record yet.';
            document.getElementById('detailOralExam').innerText = 'No oral examination record yet.';
            document.getElementById('detailDiagnosis').innerText = 'No diagnosis record yet.';
            document.getElementById('detailPrescription').innerText = 'No prescription recorded.';
            document.getElementById('detailFollowUp').innerText = 'No follow-up appointment scheduled.';
            document.getElementById('detailOdontogram').innerHTML = 'No odontogram record yet.';

            const statusMetaSection = document.getElementById('statusMetaSection');
            const rescheduledToMetaRow = document.getElementById('rescheduledToMetaRow');

            statusMetaSection.classList.add('hidden');
            rescheduledToMetaRow.classList.add('hidden');
            document.getElementById('detailRescheduledTo').innerText = 'Not available';

            if (statusLower.includes('rescheduled')) {
                statusMetaSection.classList.remove('hidden');
                rescheduledToMetaRow.classList.remove('hidden');
                document.getElementById('detailRescheduledTo').innerText = date + ' • ' + time;
            }

            document.getElementById('drawerOverlay').classList.add('open');
            document.getElementById('detailsDrawer').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeDetailsDrawer() {
            document.getElementById('drawerOverlay').classList.remove('open');
            document.getElementById('detailsDrawer').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            initStatusThemes();

            document.getElementById('startModal')?.addEventListener('click', function(e) {
                if (e.target === this) closeStartModal();
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeStartModal();
                    closeDetailsDrawer();
                }
            });
        });
    </script>
@endsection
