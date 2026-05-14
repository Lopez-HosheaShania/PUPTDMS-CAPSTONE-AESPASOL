@extends('layouts.admin')

@section('title', 'Patient List | PUP Taguig Dental Clinic')

@section('content')
@php
use Carbon\Carbon;

$appointments = $appointments ?? collect([]);
$allAppointments = $appointments instanceof \Illuminate\Pagination\AbstractPaginator
? collect($appointments->items())
: collect($appointments);

$today = Carbon::today()->toDateString();

$todayCount = $todayCount ?? 0;
$upcomingCount = $upcomingCount ?? 0;
$rescheduledCount = $rescheduledCount ?? 0;
$cancelledCount = $cancelledCount ?? 0;
$completedCount = $completedCount ?? 0;
$allCount = $allCount ?? $allAppointments->count();
@endphp

<main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-screen">
    <div class="max-w-[1280px] mx-auto">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Patient List</h1>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="page-badge">
                        <span class="page-badge-dot"></span>
                        {{ $allCount }} {{ \Illuminate\Support\Str::plural('record', $allCount) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-left">
                    <div class="card-header-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="card-title">Patient Directory</span>
                    <span id="entryBadge" class="entry-badge">
                        {{ $allCount }} {{ \Illuminate\Support\Str::plural('entry', $allCount) }}
                    </span>
                </div>

                <div class="card-header-right">
                    <div class="search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input id="searchInput" class="no-voice" type="text" placeholder="Search patients...">
                    </div>

                    <button type="button" id="searchClearBtn" class="search-clear-btn hidden"
                        title="Clear">Clear</button>

                    <div class="patient-voice-toggle">
                        <button type="button" id="micToggleBtn" class="voice-search-mic external"
                            aria-label="Toggle voice input" aria-pressed="false">
                            <i class="fa-solid fa-microphone"></i>
                        </button>
                        <span id="patientVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                            const input = document.getElementById('searchInput');
                            const micBtn = document.getElementById('micToggleBtn');
                            const status = document.getElementById('patientVoiceStatus');

                            if (!input || !micBtn || !status) return;

                            if (!SpeechRecognition) {
                                micBtn.disabled = true;
                                micBtn.setAttribute('aria-disabled', 'true');
                                return;
                            }

                            let listening = false;
                            let manualStop = false;

                            const setStatus = (text, state) => {
                                status.textContent = text;
                                status.className = 'patient-voice-status';
                                if (state) status.classList.add(`is-${state}`);
                                status.classList.remove('hidden');
                            };

                            const hideStatus = (delay = 0) => {
                                window.setTimeout(() => status.classList.add('hidden'), delay);
                            };

                            const setMicState = (isActive) => {
                                micBtn.classList.toggle('mic-active', isActive);
                                micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                                micBtn.innerHTML = isActive
                                    ? '<i class="fa-solid fa-stop"></i>'
                                    : '<i class="fa-solid fa-microphone"></i>';
                            };

                            const stopListeningNow = () => {
                                manualStop = true;
                                listening = false;
                                setMicState(false);
                                setStatus('Voice input stopped.', 'success');
                                hideStatus(1200);

                                if (recognition) {
                                    try {
                                        recognition.abort();
                                    } catch (e) {
                                        try { recognition.stop(); } catch (err) { }
                                    }
                                }
                            };

                            // Create a fresh recognition instance each time to avoid state bugs
                            const createRecognition = () => {
                                const r = new SpeechRecognition();
                                r.lang = 'en-US';
                                r.continuous = false;
                                r.interimResults = true;
                                r.maxAlternatives = 1;

                                let sawSpeech = false;
                                let timeoutId = null;
                                const LISTEN_TIMEOUT = 6000; // 6 seconds

                                const clearTimeout_ = () => {
                                    if (timeoutId) {
                                        clearTimeout(timeoutId);
                                        timeoutId = null;
                                    }
                                };

                                r.onstart = () => {
                                    timeoutId = window.setTimeout(() => {
                                        if (listening && !sawSpeech) {
                                            r.stop();
                                        }
                                    }, LISTEN_TIMEOUT);
                                };

                                r.onspeechend = () => {
                                    clearTimeout_(timeoutId);
                                    try { r.stop(); } catch (e) { }
                                };

                                r.onresult = (event) => {
                                    let transcript = '';

                                    for (let i = event.resultIndex; i < event.results.length; i++) {
                                        const result = event.results[i];
                                        const chunk = result?.[0]?.transcript?.trim() || '';
                                        if (!chunk) continue;

                                        sawSpeech = true;

                                        if (result.isFinal) {
                                            transcript = `${transcript} ${chunk}`.trim();
                                        } else if (!transcript) {
                                            transcript = chunk;
                                        }
                                    }

                                    transcript = transcript.trim();

                                    if (transcript) {
                                        clearTimeout_(timeoutId);
                                        input.value = transcript;
                                        input.dispatchEvent(new Event('input', { bubbles: true }));
                                        input.dispatchEvent(new Event('change', { bubbles: true }));

                                        setStatus('Listening...', 'listening');
                                    }
                                };

                                r.onerror = () => {
                                    clearTimeout_();
                                    listening = false;
                                    if (manualStop) {
                                        manualStop = false;
                                        return;
                                    }
                                    setMicState(false);
                                    setStatus("Didn't catch that. Try again.", 'error');
                                    hideStatus(2500);
                                };

                                r.onend = () => {
                                    clearTimeout_();
                                    if (manualStop) {
                                        manualStop = false;
                                        listening = false;
                                        setMicState(false);
                                        return;
                                    }

                                    const hadSpeech = sawSpeech || !!input.value.trim();
                                    listening = false;
                                    setMicState(false);
                                    if (hadSpeech) {
                                        setStatus('Voice captured.', 'success');
                                        hideStatus(2200);
                                    } else {
                                        setStatus("Didn't catch that. Try again.", 'error');
                                        hideStatus(2500);
                                    }
                                };

                                return r;
                            };

                            let recognition = null;

                            micBtn.addEventListener('click', () => {
                                if (listening && recognition) {
                                    stopListeningNow();
                                    return;
                                }

                                // create fresh instance and start
                                recognition = createRecognition();

                                try {
                                    recognition.start();
                                } catch (error) {
                                    setStatus('Unable to start voice input.', 'error');
                                    hideStatus(2500);
                                    setMicState(false);
                                    listening = false;
                                    return;
                                }

                                listening = true;
                                setMicState(true);
                                setStatus('Listening...', 'listening');
                            });
                        });
                    </script>

                    <button type="button" id="openFilterBtn" class="filter-btn">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Filter</span>
                        <span id="filterDot" class="filter-dot"></span>
                    </button>

                    <div class="view-toggle" id="patientViewToggle">
                        <button type="button" class="view-toggle-btn active" data-view="list" id="patientListViewBtn"
                            title="List view" aria-label="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="view-toggle-btn" data-view="grid" id="patientGridViewBtn"
                            title="Grid view" aria-label="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="tab-strip px-4 pt-4">
                <button class="tab-btn tab-today tab-active" data-filter="today" type="button">
                    <div class="tab-top-row">
                        <div class="tab-icon-wrap"><i class="fa-solid fa-calendar-days"></i></div>
                        <span class="tab-count">{{ $todayCount }}</span>
                    </div>
                    <span class="tab-label">Scheduled Today</span>
                </button>

                <button class="tab-btn tab-upcoming" data-filter="upcoming" type="button">
                    <div class="tab-top-row">
                        <div class="tab-icon-wrap"><i class="fa-solid fa-hourglass-half"></i></div>
                        <span class="tab-count">{{ $upcomingCount }}</span>
                    </div>
                    <span class="tab-label">Upcoming</span>
                </button>

                <button class="tab-btn tab-rescheduled" data-filter="rescheduled" type="button">
                    <div class="tab-top-row">
                        <div class="tab-icon-wrap"><i class="fa-solid fa-rotate"></i></div>
                        <span class="tab-count">{{ $rescheduledCount }}</span>
                    </div>
                    <span class="tab-label">Rescheduled</span>
                </button>

                <button class="tab-btn tab-cancelled" data-filter="cancelled" type="button">
                    <div class="tab-top-row">
                        <div class="tab-icon-wrap"><i class="fa-solid fa-xmark"></i></div>
                        <span class="tab-count">{{ $cancelledCount }}</span>
                    </div>
                    <span class="tab-label">Cancelled</span>
                </button>

                <button class="tab-btn tab-completed" data-filter="completed" type="button">
                    <div class="tab-top-row">
                        <div class="tab-icon-wrap"><i class="fa-solid fa-circle-check"></i></div>
                        <span class="tab-count">{{ $completedCount }}</span>
                    </div>
                    <span class="tab-label">Completed</span>
                </button>

                <button class="tab-btn tab-all" data-filter="all" type="button">
                    <div class="tab-top-row">
                        <div class="tab-icon-wrap"><i class="fa-solid fa-clipboard-list"></i></div>
                        <span class="tab-count">{{ $allCount }}</span>
                    </div>
                    <span class="tab-label">All Patients</span>
                </button>
            </div>

            <div id="patientListWrap" class="patient-list-wrap">
                @if($allAppointments->count())
                <div class="patient-view" id="patientListView">
                    @foreach($allAppointments as $appt)
                    @php
                    $status = strtolower($appt->status ?? '');
                    $isCancelled = $status === 'cancelled';
                    $isCompleted = $status === 'completed';
                    $isRescheduled = $status === 'rescheduled';
                    $isToday = $appt->appointment_date === $today && !$isCancelled && !$isCompleted;
                    $isUpcoming = $appt->appointment_date > $today && in_array($status, ['upcoming', 'rescheduled',
                    'pending', 'confirmed'], true);

                    $tabClass = $isCancelled ? 'cancelled' : ($isCompleted ? 'completed' : ($isRescheduled ?
                    'rescheduled' : ($isToday ? 'today' : ($isUpcoming ? 'upcoming' : 'all'))));

                    $patientName = $appt->patient->name ?? 'Unknown Patient';
                    $dateLabel = Carbon::parse($appt->appointment_date)->format('d M Y');
                    $timeLabel = Carbon::parse($appt->appointment_time)->format('g:i A');
                    $serviceLabel = ($appt->service_type === 'Others') ? ($appt->other_services ?: 'Others') :
                    $appt->service_type;

                    $accentClass = $isCancelled ? 'accent-cancelled' : ($isCompleted ? 'accent-completed' :
                    ($isRescheduled ? 'accent-rescheduled' : ($isToday ? 'accent-today' : ($isUpcoming ?
                    'accent-upcoming' : 'accent-default'))));
                    $iconBg = $isCancelled ? 'background:#FEE2E2;color:#DC2626;' : ($isCompleted ?
                    'background:#F0FDF4;color:#16A34A;' : ($isRescheduled ? 'background:#FEFCE8;color:#A16207;' :
                    ($isToday ? 'background:#EFF6FF;color:#2563EB;' : ($isUpcoming ? 'background:#FFF7ED;color:#EA580C;'
                    : 'background:#F3F4F6;color:#6B7280;'))));
                    $pillClass = $isCancelled ? 'pill-cancelled' : ($isCompleted ? 'pill-completed' : ($isRescheduled ?
                    'pill-rescheduled' : ($isToday ? 'pill-today' : ($isUpcoming ? 'pill-upcoming' : 'pill-default'))));
                    $pillText = $isCancelled ? 'Cancelled' : ($isCompleted ? 'Completed' : ($isRescheduled ?
                    'Rescheduled' : ($isToday ? 'Appointment Today' : ($isUpcoming ? ($status === 'upcoming' ?
                    'Upcoming' : 'Upcoming · '.ucfirst($status)) : ucfirst($status ?: 'Pending')))));
                    @endphp

                    <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                        class="patient-card patient-item" data-tab="{{ $tabClass }}"
                        data-name="{{ strtolower($patientName) }}" data-service="{{ strtolower($serviceLabel) }}"
                        data-course="{{ strtolower($appt->patient->course ?? '') }}"
                        data-year="{{ strtolower($appt->patient->year_level ?? '') }}"
                        data-section="{{ strtolower($appt->patient->section ?? '') }}"
                        data-department="{{ strtolower($appt->patient->department ?? '') }}"
                        data-date="{{ $appt->appointment_date }}" data-record-key="appt-{{ $appt->id }}"
                        data-view-type="list">

                        <div class="accent-bar {{ $accentClass }}"></div>

                        <div class="patient-card-body">
                            <img src="{{ $appt->patient->profile_image ? asset('storage/'.$appt->patient->profile_image) : 'https://ui-avatars.com/api/?name='.urlencode($patientName).'&background=660000&color=FFFFFF&rounded=true&size=128' }}"
                                alt="{{ $patientName }}" class="patient-avatar">

                            <div class="patient-main">
                                <div class="patient-name">{{ $patientName }}</div>
                                <span class="patient-id">ID #{{ $appt->patient_id }}</span>
                            </div>

                            <div class="divider-y"></div>

                            <div class="detail-box" style="width: 210px; flex-shrink:0;">
                                <div class="detail-icon" style="background:#EFF6FF;color:#2563EB;">
                                    <i class="fa-regular fa-calendar"></i>
                                </div>
                                <div>
                                    <div class="detail-label">Date & Time</div>
                                    <div class="detail-value">{{ $dateLabel }}</div>
                                    <div class="detail-sub">{{ $timeLabel }}</div>
                                </div>
                            </div>

                            <div class="divider-y"></div>

                            <div class="detail-box" style="flex:1; min-width:220px;">
                                <div class="detail-icon" style="{{ $iconBg }}">
                                    <i class="fa-solid fa-tooth"></i>
                                </div>
                                <div>
                                    <div class="detail-label">Service</div>
                                    <div class="detail-value">{{ $serviceLabel }}</div>
                                    <span class="status-pill {{ $pillClass }}">
                                        <span class="pill-dot"></span>{{ $pillText }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-arrow-btn">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>

                <div class="patient-view" id="patientGridView" hidden>
                    <div class="patient-grid">
                        @foreach($allAppointments as $appt)
                        @php
                        $status = strtolower($appt->status ?? '');
                        $isCancelled = $status === 'cancelled';
                        $isCompleted = $status === 'completed';
                        $isRescheduled = $status === 'rescheduled';
                        $isToday = $appt->appointment_date === $today && !$isCancelled && !$isCompleted;
                        $isUpcoming = $appt->appointment_date > $today && in_array($status, ['upcoming', 'rescheduled',
                        'pending', 'confirmed'], true);

                        $tabClass = $isCancelled ? 'cancelled' : ($isCompleted ? 'completed' : ($isRescheduled ?
                        'rescheduled' : ($isToday ? 'today' : ($isUpcoming ? 'upcoming' : 'all'))));

                        $patientName = $appt->patient->name ?? 'Unknown Patient';
                        $dateLabel = Carbon::parse($appt->appointment_date)->format('d M Y');
                        $timeLabel = Carbon::parse($appt->appointment_time)->format('g:i A');
                        $serviceLabel = ($appt->service_type === 'Others') ? ($appt->other_services ?: 'Others') :
                        $appt->service_type;

                        $accentClass = $isCancelled ? 'accent-cancelled' : ($isCompleted ? 'accent-completed' :
                        ($isRescheduled ? 'accent-rescheduled' : ($isToday ? 'accent-today' : ($isUpcoming ?
                        'accent-upcoming' : 'accent-default'))));
                        $pillClass = $isCancelled ? 'pill-cancelled' : ($isCompleted ? 'pill-completed' :
                        ($isRescheduled ? 'pill-rescheduled' : ($isToday ? 'pill-today' : ($isUpcoming ? 'pill-upcoming'
                        : 'pill-default'))));
                        $pillText = $isCancelled ? 'Cancelled' : ($isCompleted ? 'Completed' : ($isRescheduled ?
                        'Rescheduled' : ($isToday ? 'Appointment Today' : ($isUpcoming ? ($status === 'upcoming' ?
                        'Upcoming' : 'Upcoming · '.ucfirst($status)) : ucfirst($status ?: 'Pending')))));
                        @endphp

                        <a href="{{ route('admin.admin.patient.profile', ['patient' => $appt->patient_id]) }}"
                            class="patient-grid-card patient-item" data-tab="{{ $tabClass }}"
                            data-name="{{ strtolower($patientName) }}" data-service="{{ strtolower($serviceLabel) }}"
                            data-course="{{ strtolower($appt->patient->course ?? '') }}"
                            data-year="{{ strtolower($appt->patient->year_level ?? '') }}"
                            data-section="{{ strtolower($appt->patient->section ?? '') }}"
                            data-department="{{ strtolower($appt->patient->department ?? '') }}"
                            data-date="{{ $appt->appointment_date }}" data-record-key="appt-{{ $appt->id }}"
                            data-view-type="grid">

                            <div class="accent-bar {{ $accentClass }}"></div>

                            <div class="patient-grid-card-body">
                                <div class="patient-grid-top">
                                    <img src="{{ $appt->patient->profile_image ? asset('storage/'.$appt->patient->profile_image) : 'https://ui-avatars.com/api/?name='.urlencode($patientName).'&background=660000&color=FFFFFF&rounded=true&size=128' }}"
                                        alt="{{ $patientName }}" class="patient-avatar">

                                    <div class="patient-grid-main">
                                        <div class="patient-grid-name">{{ $patientName }}</div>
                                        <span class="patient-grid-id">ID #{{ $appt->patient_id }}</span>
                                    </div>
                                </div>

                                <div class="patient-grid-meta">
                                    <div>
                                        <div class="detail-label">Date & Time</div>
                                        <div class="detail-value">{{ $dateLabel }}</div>
                                        <div class="detail-sub">{{ $timeLabel }}</div>
                                    </div>

                                    <div>
                                        <div class="detail-label">Service</div>
                                        <div class="detail-value">{{ $serviceLabel }}</div>
                                        <span class="status-pill {{ $pillClass }}">
                                            <span class="pill-dot"></span>{{ $pillText }}
                                        </span>
                                    </div>
                                </div>

                                <div class="patient-grid-actions">
                                    <div class="card-arrow-btn">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @else
                <div id="serverEmptyState" class="empty-state visible">
                    <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-users text-3xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 font-semibold text-base">No appointments found</p>
                    <p class="text-gray-400 text-sm mt-1">There are no patient records to display yet.</p>
                </div>
                @endif

                <div id="clientEmptyState" class="empty-state">
                    <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-filter-circle-xmark text-3xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 font-semibold text-base">No matching records found</p>
                    <p class="text-gray-400 text-sm mt-1">Try changing the search, tab, or filter options.</p>
                </div>
            </div>

            <div class="pagebar">
                <span class="pagebar-info">
                    Showing <strong id="visibleCount">{{ $allCount }}</strong> of <strong id="totalCount">{{ $allCount
                        }}</strong> entries
                </span>
            </div>
        </div>
    </div>
</main>

<div id="filterModalBackdrop" class="filter-modal-backdrop">
    <div class="filter-modal">
        <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-sliders text-[#8B0000]"></i>
                <h2 class="text-lg font-semibold text-[#8B0000]">Filter Patients</h2>
            </div>
            <button type="button" id="closeFilterModal"
                class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 hover:bg-gray-200">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="filter-modal-body space-y-5">
            <div class="space-y-3">
                <p class="text-sm text-gray-500">Sort</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 text-sm text-gray-700">
                        <input type="radio" name="sort" value="az" class="radio-red"> A-Z
                    </label>
                    <label class="flex items-center gap-3 text-sm text-gray-700">
                        <input type="radio" name="sort" value="za" class="radio-red"> Z-A
                    </label>
                </div>
            </div>

            <div class="h-px bg-gray-200"></div>

            <div class="space-y-3">
                <p class="text-sm text-gray-500">Date Range</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-sm text-gray-700">From</label>
                        <input type="date" id="fromDate"
                            class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm text-gray-700">To</label>
                        <input type="date" id="toDate"
                            class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <div class="h-px bg-gray-200"></div>

            <div class="space-y-3">
                <p class="text-sm text-gray-500">Course</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-x-6 gap-y-3 text-sm text-gray-700">
                    @foreach(['BSIT','BSECE','BSBA - HRM','BSED - ENG','BSOA','BSPSYCH','DIT','BSME','BSBA - MM','BSED -
                    MATH','DOMT'] as $course)
                    <label class="flex items-center gap-2">
                        <input type="radio" name="course" value="{{ strtolower($course) }}" class="radio-red"> {{
                        $course }}
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="h-px bg-gray-200"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Year</p>
                    <div class="grid grid-cols-2 gap-y-3 text-sm text-gray-700">
                        @foreach(['1st Year','2nd Year','3rd Year','4th Year'] as $year)
                        <label class="flex items-center gap-3">
                            <input type="radio" name="year" value="{{ strtolower($year) }}" class="radio-red"> {{ $year
                            }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-sm text-gray-500">Section</p>
                    <div class="space-y-3 text-sm text-gray-700">
                        <label class="flex items-center gap-3">
                            <input type="radio" name="section" value="1" class="radio-red"> 1
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="radio" name="section" value="2" class="radio-red"> 2
                        </label>
                    </div>
                </div>
            </div>

            <div class="h-px bg-gray-200"></div>

            <div class="space-y-3">
                <p class="text-sm text-gray-500">Department</p>
                <div class="flex flex-wrap gap-x-8 gap-y-3 text-sm text-gray-700">
                    @foreach(['Administrative','Faculty','Dependent'] as $department)
                    <label class="flex items-center gap-3">
                        <input type="radio" name="department" value="{{ strtolower($department) }}" class="radio-red">
                        {{ $department }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-5 py-4 flex items-center justify-between border-t border-gray-200 bg-white">
            <button type="button" id="clearFiltersBtn"
                class="text-[#8B0000] text-sm font-medium hover:underline">Clear</button>
            <button type="button" id="applyFiltersBtn"
                class="bg-[#8B0000] text-white px-8 py-2 rounded-md text-sm font-medium shadow hover:bg-[#760000] transition">
                Apply
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const allCards = Array.from(document.querySelectorAll('.patient-item'));
        const listCards = allCards.filter(card => card.dataset.viewType === 'list');
        const gridCards = allCards.filter(card => card.dataset.viewType === 'grid');

        const searchInput = document.getElementById('searchInput');
        const searchClearBtn = document.getElementById('searchClearBtn');
        const tabButtons = Array.from(document.querySelectorAll('.tab-btn[data-filter]'));
        const visibleCount = document.getElementById('visibleCount');
        const totalCount = document.getElementById('totalCount');
        const clientEmptyState = document.getElementById('clientEmptyState');
        const serverEmptyState = document.getElementById('serverEmptyState');

        const filterModalBackdrop = document.getElementById('filterModalBackdrop');
        const openFilterBtn = document.getElementById('openFilterBtn');
        const closeFilterModal = document.getElementById('closeFilterModal');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        const filterDot = document.getElementById('filterDot');

        const patientListView = document.getElementById('patientListView');
        const patientGridView = document.getElementById('patientGridView');
        const patientListViewBtn = document.getElementById('patientListViewBtn');
        const patientGridViewBtn = document.getElementById('patientGridViewBtn');

        let activeTab = 'today';
        let currentView = getPreferredPatientView();

        let filters = {
            search: '',
            sort: '',
            fromDate: '',
            toDate: '',
            course: '',
            year: '',
            section: '',
            department: ''
        };

        function getPreferredPatientView() {
            if (window.innerWidth <= 767) return 'grid';
            return localStorage.getItem('patientView') || 'list';
        }

        function applyPatientView(view, save = true) {
            if (!patientListView || !patientGridView) return;

            const finalView = window.innerWidth <= 767 ? 'grid' : view;
            currentView = finalView;

            if (finalView === 'grid') {
                patientListView.setAttribute('hidden', 'hidden');
                patientGridView.removeAttribute('hidden');
            } else {
                patientGridView.setAttribute('hidden', 'hidden');
                patientListView.removeAttribute('hidden');
            }

            if (patientListViewBtn) patientListViewBtn.classList.toggle('active', finalView === 'list');
            if (patientGridViewBtn) patientGridViewBtn.classList.toggle('active', finalView === 'grid');

            if (save && window.innerWidth > 767) {
                localStorage.setItem('patientView', finalView);
            }
        }

        function updateSearchClear() {
            if (!searchClearBtn || !searchInput) return;
            searchClearBtn.classList.toggle('hidden', searchInput.value.trim().length === 0);
        }

        function hasActiveFilters() {
            return !!filters.sort || !!filters.fromDate || !!filters.toDate || !!filters.course ||
                !!filters.year || !!filters.section || !!filters.department;
        }

        function updateFilterDot() {
            if (!filterDot || !openFilterBtn) return;
            filterDot.classList.toggle('visible', hasActiveFilters());
            openFilterBtn.classList.toggle('active', hasActiveFilters());
        }

        function cardMatchesFilters(card) {
            if (activeTab !== 'all' && card.dataset.tab !== activeTab) return false;

            if (filters.search) {
                const q = filters.search.toLowerCase();
                const matchesSearch =
                    (card.dataset.name || '').includes(q) ||
                    (card.dataset.service || '').includes(q) ||
                    (card.textContent || '').toLowerCase().includes(q);

                if (!matchesSearch) return false;
            }

            if (filters.course && (card.dataset.course || '') !== filters.course) return false;
            if (filters.year && (card.dataset.year || '') !== filters.year) return false;
            if (filters.section && (card.dataset.section || '') !== filters.section) return false;
            if (filters.department && (card.dataset.department || '') !== filters.department) return false;
            if (filters.fromDate && (card.dataset.date || '') < filters.fromDate) return false;
            if (filters.toDate && (card.dataset.date || '') > filters.toDate) return false;

            return true;
        }

        function applySorting(cards) {
            if (filters.sort === 'az') {
                cards.sort((a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || ''));
            } else if (filters.sort === 'za') {
                cards.sort((a, b) => (b.dataset.name || '').localeCompare(a.dataset.name || ''));
            }
            return cards;
        }

        function applyCardFilters() {
            const listWrap = patientListView;
            const gridWrap = patientGridView ? patientGridView.querySelector('.patient-grid') : null;

            allCards.forEach(card => {
                card.style.display = 'none';
            });

            let filteredListCards = listCards.filter(cardMatchesFilters);
            let filteredGridCards = gridCards.filter(cardMatchesFilters);

            filteredListCards = applySorting(filteredListCards);
            filteredGridCards = applySorting(filteredGridCards);

            if (listWrap) {
                filteredListCards.forEach(card => {
                    card.style.display = '';
                    listWrap.appendChild(card);
                });
            }

            if (gridWrap) {
                filteredGridCards.forEach(card => {
                    card.style.display = '';
                    gridWrap.appendChild(card);
                });
            }

            const visible = filteredListCards.length;

            if (visibleCount) visibleCount.textContent = visible;
            if (totalCount) totalCount.textContent = listCards.length;

            if (serverEmptyState) {
                serverEmptyState.classList.remove('visible');
            }

            if (clientEmptyState) {
                clientEmptyState.classList.toggle('visible', visible === 0 && listCards.length > 0);
            }

            updateFilterDot();
        }

        tabButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                tabButtons.forEach(b => b.classList.remove('tab-active'));
                this.classList.add('tab-active');
                activeTab = this.dataset.filter;
                applyCardFilters();
            });
        });

        if (patientListViewBtn) {
            patientListViewBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                applyPatientView('list', true);
            });
        }

        if (patientGridViewBtn) {
            patientGridViewBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                applyPatientView('grid', true);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                filters.search = this.value.trim();
                updateSearchClear();
                applyCardFilters();
            });
        }

        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', function () {
                const micBtn = document.getElementById('micToggleBtn');
                if (micBtn && micBtn.classList.contains('mic-active')) {
                    micBtn.click();
                }

                searchInput.value = '';
                filters.search = '';
                updateSearchClear();
                applyCardFilters();
                document.getElementById('patientVoiceStatus')?.classList.add('hidden');
                searchInput.focus();
            });
        }

        if (openFilterBtn) {
            openFilterBtn.addEventListener('click', function () {
                filterModalBackdrop.classList.add('show');
            });
        }

        if (closeFilterModal) {
            closeFilterModal.addEventListener('click', function () {
                filterModalBackdrop.classList.remove('show');
            });
        }

        if (filterModalBackdrop) {
            filterModalBackdrop.addEventListener('click', function (e) {
                if (e.target === filterModalBackdrop) {
                    filterModalBackdrop.classList.remove('show');
                }
            });
        }

        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function () {
                filters.sort = document.querySelector('input[name="sort"]:checked')?.value || '';
                filters.fromDate = document.getElementById('fromDate')?.value || '';
                filters.toDate = document.getElementById('toDate')?.value || '';
                filters.course = document.querySelector('input[name="course"]:checked')?.value || '';
                filters.year = document.querySelector('input[name="year"]:checked')?.value || '';
                filters.section = document.querySelector('input[name="section"]:checked')?.value || '';
                filters.department = document.querySelector('input[name="department"]:checked')?.value || '';

                filterModalBackdrop.classList.remove('show');
                applyCardFilters();
            });
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function () {
                document.querySelectorAll('#filterModalBackdrop input[type="radio"]').forEach(input => {
                    input.checked = false;
                });

                const fromDate = document.getElementById('fromDate');
                const toDate = document.getElementById('toDate');

                if (fromDate) fromDate.value = '';
                if (toDate) toDate.value = '';

                filters.sort = '';
                filters.fromDate = '';
                filters.toDate = '';
                filters.course = '';
                filters.year = '';
                filters.section = '';
                filters.department = '';

                applyCardFilters();
            });
        }

        window.addEventListener('resize', function () {
            applyPatientView(getPreferredPatientView(), false);
        });

        applyPatientView(currentView, false);
        updateSearchClear();
        applyCardFilters();
    });
</script>
@endsection