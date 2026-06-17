@extends('layouts.patient')

@section('title', 'Patient Dashboard | PUP Taguig Dental Clinic')

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

<main id="mainContent" class="patient-page-shell patient-dashboard-page page-enter">
    <div class="w-full">

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
                    class="dashboard-glass rounded-[1rem] overflow-hidden skeleton-section h-full skeleton-shell skeleton-fade-swap mt-3">
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
                <div id="calendarSkeletonContainer" class="w-full h-full min-h-[420px] skeleton-fade-swap mt-3"></div>
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
        const quickAction = new URLSearchParams(window.location.search).get('quick_action');

        renderGreeting();
        initRecordModal();

        if (quickAction === 'record') {
            setTimeout(() => {
                window.openDocModal
                    ? window.openDocModal('dentalHealthRecordModal')
                    : document.getElementById('dentalHealthRecordModal')?.showModal();
            }, 150);
        }

        if (quickAction === 'clearance') {
            setTimeout(() => {
                window.openDocModal
                    ? window.openDocModal('dentalClearanceModal')
                    : document.getElementById('dentalClearanceModal')?.showModal();
            }, 150);
        }

        if (quickAction) {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('quick_action');
            window.history.replaceState({}, '', cleanUrl.toString());
        }

        window.runEnterpriseLoading([
            {
                label: 'Loading calendar and appointment details',
                tasks: [
                    () => {
                        if (typeof renderCalendar === 'function') renderCalendar();
                    },
                    renderUpcomingAppointment
                ]
            },
            {
                label: 'Loading profile information',
                tasks: [
                    renderProfile
                ]
            },
            {
                label: 'Loading records and document services',
                tasks: [
                    () => {
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

            window.swapSkeletonContent('upcomingAppointmentWrapper',
                '<div class="upcoming-card-polished bg-white dark:bg-[#161B22] rounded-[1rem] border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">' +

                '<div class="px-4 sm:px-5 py-4 sm:py-4.5 mb-3">' +
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
                '<h3 class="text-lg sm:text-[1.15rem] font-extrabold text-gray-900 dark:text-[#F3F4F6] leading-tight truncate">' + window.escapeHtml(d.service) + '</h3>' +
                '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ' + statusPillCls + ' ' + statusDarkPill + '">' +
                '<span class="w-1.5 h-1.5 rounded-full ' + statusDotCls + '"></span>' +
                window.escapeHtml(d.status) +
                '</span>' +
                '</div>' +

                '<div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600 dark:text-gray-400">' +
                '<span class="inline-flex items-center gap-2">' +
                '<i class="fa-regular fa-calendar text-gray-400 dark:text-gray-500"></i>' +
                '<span class="font-medium">' + window.escapeHtml(d.date) + '</span>' +
                '</span>' +

                '<span class="inline-flex items-center gap-2">' +
                '<i class="fa-regular fa-clock text-gray-400 dark:text-gray-500"></i>' +
                '<span class="font-medium">' + window.escapeHtml(d.time_fmt) + '</span>' +
                '</span>' +

                '<span class="inline-flex items-center gap-2 min-w-0">' +
                '<i class="fa-solid fa-user-doctor text-gray-400 dark:text-gray-500"></i>' +
                '<span class="font-medium truncate">' + window.escapeHtml(d.dentist) + '</span>' +
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

                '<a href="' + window.escapeHtml(d.indexUrl) +
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

        window.swapSkeletonContent('upcomingAppointmentWrapper',
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
            '<i class="fa-regular fa-id-badge w-3"></i> ' + window.escapeHtml(identityLabel) +
            '</span>' +
            '<span id="maskedIdentityValue" data-masked="true" class="text-gray-800 font-medium text-right break-words leading-snug flex-1 min-w-0">' +
            window.escapeHtml(identityMasked) +
            '</span>' +
            '</div>' :
            '';

        var emergencySection = hasEmergency ?
            '<div class="space-y-1">' +
            '<p class="text-sm font-bold text-gray-900">' + window.escapeHtml(pData.emergencyName) + '</p>' +
            '<div class="flex items-center justify-between gap-3">' +
            '<p class="text-xs font-medium text-gray-600">' +
            (pData.emergencyRelation ? '<span class="text-gray-400">(' + window.escapeHtml(pData.emergencyRelation) +
                ')</span>' : '') +
            '</p>' +
            '<span id="maskedEmergencyNumber" data-masked="true" class="text-xs font-medium text-gray-700 text-right">' +
            window.escapeHtml(maskedEmergency) + '</span>' +
            '</div>' +
            '</div>' :
            '<div class="text-center py-2">' +
            '<i class="fa-solid fa-user-plus text-red-300 text-lg mb-1"></i>' +
            '<p class="text-xs text-gray-400 font-medium">No emergency contact added</p>' +
            '</div>';

        window.swapSkeletonContent('profileSkeletonContainer',
            '<div class="dashboard-card-polished dashboard-glass overflow-hidden h-full flex flex-col rounded-[1rem]">' +
            '<div class="h-20 bg-gradient-to-r from-[#8B0000] to-[#b30000] relative"></div>' +

            '<div class="px-4 pb-4 relative flex flex-col items-center mt-[-34px]">' +
            '<div class="relative mb-3">' +
            '<img src="' + pData.avatar +
            '" alt="Profile" class="w-[68px] h-[68px] rounded-full object-cover border-4 border-white shadow-md bg-white">' +
            '</div>' +

            '<div class="mt-1 flex items-center justify-center gap-2 max-w-full">' +
            '<h2 class="text-[17px] font-extrabold text-gray-900 text-center leading-tight break-words">' +
            window.escapeHtml(pData.name) +
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
            window.escapeHtml(pData.age ? pData.age + " yrs" : "N/A") +
            '<span class="text-gray-400 text-xs font-normal block">' +
            window.escapeHtml(pData.birthdate) +
            '</span>' +
            '</span>' +
            '</div>' +

            '<div class="flex justify-between items-center gap-4">' +
            '<span class="text-gray-400 font-semibold text-xs flex items-center gap-2">' +
            '<i class="fa-solid fa-venus-mars w-3"></i> Gender' +
            '</span>' +
            '<span class="text-gray-800 font-medium text-right">' +
            window.escapeHtml(pData.gender) +
            '</span>' +
            '</div>' +

            identityRow +

            '<div class="flex justify-between items-start gap-4">' +
            '<span class="text-gray-400 font-semibold text-xs flex items-center gap-2 mt-0.5">' +
            '<i class="fa-solid fa-phone w-3"></i> Contact' +
            '</span>' +
            '<span id="maskedContactValue" data-masked="true" class="text-gray-800 font-medium text-right break-words leading-snug flex-1 min-w-0">' +
            window.escapeHtml(maskedContact) +
            '</span>' +
            '</div>' +

            '<div class="flex justify-between items-start gap-3">' +
            '<span class="text-gray-400 font-semibold text-xs flex items-center gap-2 mt-0.5 flex-shrink-0 w-[92px]">' +
            '<i class="fa-solid fa-envelope w-3"></i> Email' +
            '</span>' +
            '<span id="maskedEmailValue" data-masked="true" class="text-gray-800 font-medium text-right break-words leading-snug flex-1 min-w-0">' +
            window.escapeHtml(maskedEmail) +
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

        window.swapSkeletonContent('requestDocsContainer',
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

            '</div>' +

            '<div class="p-4 sm:p-5 flex-1 bg-gradient-to-b from-[#fffefe] via-[#fff8f8] to-[#fff3f3]">' +
            '<div class="space-y-3">' +

            '<button type="button" data-doc-open="dentalHealthRecordModal" class="group request-doc-card relative w-full text-left rounded-[0.9rem] border border-red-100 bg-white p-4 shadow-sm transition-all duration-200">' +
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

            '<button type="button" data-doc-open="dentalClearanceModal" class="group request-doc-card relative w-full text-left rounded-[0.9rem] border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200">' +
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
            window.swapSkeletonContent('dentalOverviewContainer',
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
                '<p class="text-sm font-extrabold text-gray-900 truncate">' + window.escapeHtml(r.service) + '</p>' +
                '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold border ' +
                ((r.status || '').toLowerCase() === 'completed' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-700 border-red-100') + '">' +
                window.escapeHtml((r.status || '').toLowerCase() === 'completed' ? 'Completed' : 'Cancelled') +
                '</span>' +
                '</div>' +
                '<div class="mt-2 flex flex-wrap items-center gap-2">' +
                '<span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 border border-gray-200 text-gray-600 px-2.5 py-1 text-[11px] font-bold">' +
                '<i class="fa-regular fa-calendar opacity-70"></i>' + window.escapeHtml(dispDate) +
                '</span>' +
                '<span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 border border-gray-200 text-gray-600 px-2.5 py-1 text-[11px] font-bold">' +
                '<i class="fa-regular fa-clock opacity-70"></i>' + window.escapeHtml(dispTime) +
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

        window.swapSkeletonContent('dentalOverviewContainer',
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
            '<p class="mt-1 text-sm font-extrabold text-gray-900">' + window.escapeHtml(dispLatestDate) + '</p>' +
            '</div>' +

            '<div class="rounded-[0.85rem] bg-white border border-gray-200 px-3 py-3 col-span-2 xl:col-span-1">' +
            '<p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</p>' +
            '<p class="overview-status-text mt-1 text-sm font-extrabold text-[#8B0000]">' + window.escapeHtml(dispOverviewStatus) + '</p>' +
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