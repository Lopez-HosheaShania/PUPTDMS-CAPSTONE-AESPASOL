@php
$profileMode = $profileMode ?? 'dentist';

$layoutRole = $profileMode === 'admin' ? 'admin' : 'dentist';
$canBookAppointment =
auth()->check() &&
auth()->user()->hasPermission('book_appointments');
$bookingMode = $bookingMode ?? false;
@endphp

@extends('layouts.app')

@section('layout-role', $layoutRole)

@section('title', 'Patient Profile')

@section('usesPatientProfile', true)

@section('styles')
@vite('resources/css/pages/patient/patient-profile.css')
@endsection

@section('content')

@php
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\AppointmentOdontogramSnapshotService;
$isDentistProfile = $profileMode === 'dentist';

$patientName = $patient->name ?? 'Unknown Patient';
$displayName = $patient->name ?? 'Guest';
$age = $patient->birthdate ? Carbon::parse($patient->birthdate)->age : null;
$birthdateFormatted = $patient->birthdate ? Carbon::parse($patient->birthdate)->format('M d, Y') : 'N/A';

$futureCount = isset($futureVisits) ? $futureVisits->count() : 0;
$pastCount = isset($pastVisits) ? $pastVisits->count() : 0;

$odontogramSnapshotService =
    app(AppointmentOdontogramSnapshotService::class);

$previousOdontogramByVisitId =
    $odontogramSnapshotService
        ->previousSnapshotsByAppointment(
            collect($pastVisits ?? [])
        );

$medicalAnswers = optional($patient->medicalHistory)->answers ?? collect();
$dentalDates = optional($patient->dentalHistoryDates);
$signatureReviewStatus = optional($patient->medicalHistory)->signature_review_status;
$signatureReviewNotes = optional($patient->medicalHistory)->signature_review_notes;
$signaturePath = optional($patient->medicalHistory)->patient_signature;
$signatureUrl = $signaturePath ? asset('storage/' . $signaturePath) : null;
$isPendingManualReview = $signatureReviewStatus === 'pending_manual_review';
$isInvalidSignature = $signatureReviewStatus === 'invalid_reupload_required';
$canReviewSignature = auth()->user()?->hasPermission('create_medical_records') ?? false;
$showManualSignatureReview =
in_array($profileMode, ['admin', 'dentist'], true) &&
in_array($signatureReviewStatus, ['pending_manual_review', 'invalid_reupload_required'], true) &&
!empty($signaturePath) &&
$canReviewSignature;

$from = request('from');

if ($profileMode === 'admin') {
$backUrl = $bookingMode
? route('admin.book_appointments.start')
: ($from === 'patients' ? route('admin.admin.patients') : route('admin.admin.appointments'));

$backLabel = $bookingMode
? 'Select Patient'
: ($from === 'patients' ? 'Patients' : 'Appointments');
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

$identityLabel = $patient->faculty_code ? 'Faculty Code' : 'Student No';
$identityValue = $patient->faculty_code ?: ($patient->student_no ?: 'N/A');

$emergencyPerson = optional($patient->medicalHistory)->emergency_person;
$emergencyNumber = optional($patient->medicalHistory)->emergency_number ?? 'N/A';
$emergencyRelation = optional($patient->medicalHistory)->emergency_relation;

$profileIsActive = true;

$patientType = $patient->faculty_code ? 'Faculty' : ($patient->student_no ? 'Student' : 'Patient');

$procedureAppointment = collect($futureVisits ?? [])
->first(function ($visit) {
if (empty($visit->appointment_date)) {
return false;
}

$status = strtolower(trim((string) ($visit->status ?? '')));

$eligibleStatus = in_array(
$status,
[
'upcoming',
'rescheduled',
'today',
'scheduled_today',
],
true
);

return $eligibleStatus &&
Carbon::parse($visit->appointment_date)->isToday();
});

$canStartProcedure =
$isDentistProfile &&
!empty($procedureAppointment);
$odontogramData = optional($patient->odontogram)->odontogram_data ?? [];
$odontogramLastUpdatedAt = optional($patient->odontogram)->updated_at;
$odontogramLastUpdatedBy = optional($patient->odontogram)->updated_by;
$odontogramMetaVisit = $lastVisit ?? ($appointment ?? ($nextAppointment ?? null));
$odontogramMetaDate = $odontogramMetaVisit?->appointment_date
? Carbon::parse($odontogramMetaVisit->appointment_date)->format('M d, Y')
: 'Recorded visit';
$odontogramMetaService = $odontogramMetaVisit?->service_type_name ?: 'Dental Treatment';@endphp

<main id="mainContent" class="patient-profile-page pt-[100px] px-3 md:px-6 py-6 min-h-screen flex-1">
    <div class="w-full fade-in">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <p class="text-xs text-gray-500 mb-1.5 font-medium uppercase tracking-wider">
                    <a href="{{ $backUrl }}" class="hover:text-[#8B0000] transition">{{ $backLabel }}</a>
                    <span class="mx-1">/</span> Patient Record
                </p>

                <div class="flex items-center gap-3">
                    <a href="{{ $backUrl }}" class="ui-icon-btn neutral" data-tooltip="Back to {{ $backLabel }}"
                        aria-label="Back to {{ $backLabel }}">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>

                    <h1 class="patient-profile-title">
                        Patient Profile
                    </h1>
                </div>
            </div>

            @if ($isDentistProfile || ($profileMode === 'admin' && $canBookAppointment))
            <div class="flex items-center gap-2">
                @if ($profileMode === 'admin' && $canBookAppointment)
                <a href="{{ route('admin.book_appointments.patient', ['patient' => $patient->id]) }}"
                    class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-calendar-plus text-xs"></i> Book Appointment
                </a>
                @endif
                @if ($isDentistProfile)
                <a href="{{ route('dentist.odontogram.existing-appointment.create', ['patient' => $patient->id]) }}"
                    class="ui-btn ui-btn-secondary">
                    <i class="fa-solid fa-clock-rotate-left text-xs"></i> Add Existing Appointment
                </a>
                @endif
                @if ($canStartProcedure)
                <button type="button" onclick="openStartModal()" class="ui-btn ui-btn-success">
                    <i class="fa-solid fa-play"></i>
                    <span>Start Procedure</span>
                </button>
                @endif
            </div>
            @endif
        </div>

        <div class="patient-profile-layout">
            <aside class="patient-profile-sidebar">
                <div id="profileContainer">
                    <article class="card patient-summary-card fade-up">
                        <div class="patient-summary-cover"></div>

                        <div class="patient-summary-avatar-wrap">
                            <img src="{{ $avatarUrl }}" alt="{{ $displayName }}" class="patient-summary-avatar">
                        </div>

                        <div class="patient-summary-heading">
                            <div class="patient-summary-name-row">
                                <h2 class="patient-summary-name" data-patient-name>
                                    {{ $displayName }}
                                </h2>

                                <button id="patientProfilePrivacyToggle" type="button"
                                    class="ui-icon-btn neutral patient-privacy-toggle" data-masked="true"
                                    data-tooltip="Show private information" data-tooltip-tone="neutral"
                                    aria-label="Show private information" aria-pressed="false"
                                    onclick="togglePatientProfilePrivacy(this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>

                            <div class="patient-summary-badges">
                                @php
                                $patientRoleClass = match ($patientType) {
                                'Faculty' => 'role-faculty',
                                'Student' => 'role-student',
                                default => 'role-patient',
                                };

                                $patientRoleIcon = match ($patientType) {
                                'Faculty' => 'fa-chalkboard-user',
                                'Student' => 'fa-user-graduate',
                                default => 'fa-user',
                                };
                                @endphp

                                <span class="badge-role {{ $patientRoleClass }}">
                                    <i class="fa-solid {{ $patientRoleIcon }}"></i>
                                    <span>{{ $patientType }}</span>
                                </span>

                                <span class="status-pill status-active">
                                    <span class="status-dot"></span>
                                    <span>Profile Active</span>
                                </span>
                            </div>
                        </div>

                        <div class="patient-summary-details">
                            <div class="patient-detail-row patient-detail-row-birth">
                                <div class="patient-detail-label">
                                    <i class="fa-solid fa-cake-candles"></i>

                                    <span>
                                        <span>Age</span>
                                        <span>Date of Birth</span>
                                    </span>
                                </div>

                                <div class="patient-detail-value">
                                    <strong>{{ $age ? $age . ' yrs' : 'N/A' }}</strong>
                                    <span>{{ $birthdateFormatted }}</span>
                                </div>
                            </div>

                            <div class="patient-detail-row">
                                <div class="patient-detail-label">
                                    <i class="fa-solid fa-venus-mars"></i>
                                    <span>Gender</span>
                                </div>

                                <div class="patient-detail-value">
                                    <strong>{{ $patient->gender ?? 'N/A' }}</strong>
                                </div>
                            </div>

                            <div class="patient-detail-row">
                                <div class="patient-detail-label">
                                    <i class="fa-regular fa-id-badge"></i>
                                    <span>{{ $identityLabel }}</span>
                                </div>

                                <div id="profileIdentityValue" class="patient-detail-value patient-sensitive-value"
                                    data-raw="{{ $identityValue }}" data-type="identity">
                                    <strong></strong>
                                </div>
                            </div>

                            <div class="patient-detail-row">
                                <div class="patient-detail-label">
                                    <i class="fa-solid fa-phone"></i>
                                    <span>Contact</span>
                                </div>

                                <div id="profileContactValue" class="patient-detail-value patient-sensitive-value"
                                    data-raw="{{ $patient->phone ?? 'N/A' }}" data-type="phone">
                                    <strong></strong>
                                </div>
                            </div>

                            <div class="patient-detail-row">
                                <div class="patient-detail-label">
                                    <i class="fa-solid fa-envelope"></i>
                                    <span>Email</span>
                                </div>

                                <div id="profileEmailValue" class="patient-detail-value patient-sensitive-value"
                                    data-raw="{{ $patient->email ?? 'N/A' }}" data-type="email">
                                    <strong></strong>
                                </div>
                            </div>
                        </div>

                        <div class="patient-emergency-panel">
                            <p class="patient-emergency-label">
                                <i class="fa-solid fa-heart-pulse"></i>
                                Emergency Contact
                            </p>

                            @if ($emergencyPerson)
                            <div class="patient-emergency-content">
                                <div>
                                    <strong class="patient-emergency-name">
                                        {{ $emergencyPerson }}
                                    </strong>

                                    @if ($emergencyRelation)
                                    <span class="patient-emergency-relation">
                                        ({{ $emergencyRelation }})
                                    </span>
                                    @endif
                                </div>

                                <div id="profileEmergencyValue" class="patient-emergency-number patient-sensitive-value"
                                    data-raw="{{ $emergencyNumber }}" data-type="phone">
                                    <i class="fa-solid fa-phone"></i>
                                    <strong></strong>
                                </div>
                            </div>
                            @else
                            <div class="patient-emergency-empty">
                                <i class="fa-solid fa-user-plus"></i>
                                <span class="global-info-value">No emergency contact added</span>
                            </div>
                            @endif
                        </div>

                        @if ($showManualSignatureReview)
                        <div
                            class="{{ $isInvalidSignature ? 'bg-red-50/70 border-red-100' : 'bg-amber-50/70 border-amber-100' }} px-5 py-4 border-t">
                            <p
                                class="text-[10px] font-bold {{ $isInvalidSignature ? 'text-red-800' : 'text-amber-800' }} uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-file-signature"></i>
                                {{ $isInvalidSignature ? 'Signature Re-upload Requested' : 'Signature Review Required'
                                }}
                            </p>

                            <div
                                class="rounded-xl border {{ $isInvalidSignature ? 'border-red-200' : 'border-amber-200' }} bg-white p-3 shadow-sm">
                                <a href="{{ $signatureUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="block overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                    <img src="{{ $signatureUrl }}" alt="Patient signature for manual review"
                                        class="w-full h-auto object-contain max-h-56">
                                </a>

                                <div class="mt-3 space-y-2">
                                    <div
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $isInvalidSignature ? 'bg-red-100 text-red-800 border-red-200' : 'bg-amber-100 text-amber-800 border-amber-200' }}">
                                        <i
                                            class="fa-solid {{ $isInvalidSignature ? 'fa-circle-xmark' : 'fa-triangle-exclamation' }} text-[10px]"></i>
                                        {{ $isInvalidSignature ? 'Invalid Signature' : 'Pending Manual Review' }}
                                    </div>

                                    <p class="text-xs text-gray-600 leading-relaxed">
                                        {{ $signatureReviewNotes ?:
                                        'The AI signature checker was unavailable during
                                        submission, so this uploaded signature needs manual review.' }}
                                    </p>

                                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                        <a href="{{ $signatureUrl }}" target="_blank" rel="noopener noreferrer"
                                            class="ui-btn ui-btn-secondary ui-btn-sm">
                                            <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                                            Open Full Signature
                                        </a>

                                        @if ($isPendingManualReview && $canReviewSignature)
                                        <form method="POST"
                                            action="{{ $profileMode === 'admin' ? route('admin.patient.signature.invalid', $patient) : route('dentist.patient.signature.invalid', $patient) }}"
                                            onsubmit="return confirm('Mark this uploaded signature as invalid and notify the patient to upload a new one?');"
                                            class="ml-auto">
                                            @csrf
                                            <button type="submit" class="ui-btn ui-btn-secondary ui-btn-sm">
                                                <i class="fa-solid fa-ban text-[8px]"></i>
                                                Invalid Signature
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </article>
                </div>
            </aside>

            <section class="patient-profile-content">
                <div id="statCards" class="stat-grid">
                    <article class="stat-card s-blue">

                        <div class="stat-card-info">
                            <p class="stat-num">
                                {{ $totalVisits ?? $pastCount + $futureCount }}
                            </p>

                            <p class="stat-label">
                                Total Visits
                            </p>
                        </div>

                        <div class="stat-icon-wrapper">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>

                    </article>

                    <article class="stat-card s-purple">

                        <div class="stat-card-info">
                            <p class="stat-value-text">
                                {{ $lastVisit?->appointment_date
                                ? Carbon::parse($lastVisit->appointment_date)->format('M d, Y')
                                : 'No past visits' }}
                            </p>

                            <p class="stat-label">
                                Last Visit
                            </p>
                        </div>

                        <div class="stat-icon-wrapper">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>

                    </article>

                    <article class="stat-card s-amber">

                        <div class="stat-card-info">
                            <p class="stat-value-text">
                                {{ $nextAppointment?->appointment_date
                                ? Carbon::parse($nextAppointment->appointment_date)->format('M d, Y')
                                : 'No schedule' }}
                            </p>

                            <p class="stat-label">
                                Next Appointment
                            </p>
                        </div>

                        <div class="stat-icon-wrapper">
                            <i class="fa-regular fa-calendar-plus"></i>
                        </div>

                    </article>
                </div>

                <section class="card profile-section-card treatment-history-card">
                    <div class="card-header treatment-history-header">
                        <div class="card-header-left">
                            <div class="card-header-icon status-upcoming">
                                <i class="fa-solid fa-folder-open"></i>
                            </div>

                            <div>
                                <h2 class="card-title">Treatment History</h2>
                                <p class="card-subtitle">
                                    Review upcoming appointments and completed patient visits.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body treatment-history-body">
                        <div class="treatment-tabs" role="tablist" aria-label="Treatment history">
                            <button id="futureTab" type="button" class="treatment-tab is-active" role="tab"
                                aria-selected="true" aria-controls="futureContent"
                                onclick="switchTreatmentTab('future')">
                                <i class="fa-regular fa-calendar"></i>

                                <span>Upcoming</span>

                                <span class="treatment-tab-count">
                                    {{ $futureCount }}
                                </span>
                            </button>

                            <button id="pastTab" type="button" class="treatment-tab" role="tab" aria-selected="false"
                                aria-controls="pastContent" onclick="switchTreatmentTab('past')">
                                <i class="fa-solid fa-clock-rotate-left"></i>

                                <span>Past Visits</span>

                                <span class="treatment-tab-count">
                                    {{ $pastCount }}
                                </span>
                            </button>
                        </div>

                        <div id="futureContent" class="treatment-tab-panel is-active">
                            <div data-show-more data-show-more-step="5" data-show-more-label="appointments">
                                <div class="space-y-3" data-show-more-list>
                                    @forelse($futureVisits ?? [] as $visit)

                                    <x-appointment-record-card :appointment="$visit" variant="upcoming"
                                        :show-details="false" :show-countdown="true" :show-time-range="false"
                                        :show-reserved="$isDentistProfile" data-show-more-item />

                                    @empty

                                    <div class="empty-state treatment-history-empty">
                                        <div class="appointment-empty-icon">
                                            <i class="fa-regular fa-calendar-xmark"></i>
                                        </div>

                                        <h3 class="empty-state-title">
                                            No upcoming appointments
                                        </h3>

                                        <p class="empty-state-sub">
                                            This patient currently has no scheduled or rescheduled appointments.
                                        </p>
                                    </div>

                                    @endforelse
                                </div>

                                @if (($futureVisits ?? collect())->count() > 0)
                                <x-show-more label="appointments" />
                                @endif
                            </div>
                        </div>

                        <div id="pastContent" class="treatment-tab-panel" hidden>
                            <div data-show-more data-show-more-step="5" data-show-more-label="visits">
                                <div class="space-y-3" data-show-more-list>
                                    @forelse($pastVisits ?? [] as $visit)

                                    <x-appointment-record-card :appointment="$visit" variant="past" :show-details="true"
                                        :show-countdown="false" :show-time-range="false"
                                        :show-reserved="$isDentistProfile"
                                        :previous-odontogram="$previousOdontogramByVisitId[$visit->id] ?? []"
                                        :record-edit-url="$isDentistProfile && $visit->status === 'completed'
                                        ? route('dentist.odontogram.saved.edit', ['appointment' => $visit->id, 'from' => 'appointments'])
                                        : null" data-show-more-item />

                                    @empty

                                    <div class="empty-state treatment-history-empty">
                                        <div class="appointment-empty-icon">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </div>

                                        <h3 class="empty-state-title">
                                            No past visits
                                        </h3>

                                        <p class="empty-state-sub">
                                            Completed and cancelled appointment records will appear here.
                                        </p>
                                    </div>

                                    @endforelse
                                </div>

                                @if (($pastVisits ?? collect())->count() > 0)
                                <x-show-more label="visits" />
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card health-lifestyle-card mb-10">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon status-completed">
                                <i class="fa-solid fa-notes-medical"></i>
                            </div>

                            <div>
                                <h2 class="card-title">
                                    Health & Lifestyle Information
                                </h2>

                                <p class="card-subtitle">
                                    Patient dental, medical, and lifestyle records.
                                </p>
                            </div>
                        </div>

                        <div class="card-header-right">
                            <span class="status-pill status-completed">
                                <span class="status-dot"></span>
                                <span>Latest Record</span>
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="space-y-6">

                            {{-- =========================
                            DENTAL HISTORY
                            ========================== --}}
                            <div>
                                <div class="section-card-title">
                                    <i class="fa-solid fa-tooth"></i>
                                    <span>Dental History</span>
                                    <span class="section-card-title-line"></span>
                                </div>

                                <div class="global-info-grid global-info-grid-2">

                                    <div class="global-info-item global-info-item-compact">
                                        <span class="global-info-icon status-completed">
                                            <i class="fa-regular fa-calendar-check"></i>
                                        </span>

                                        <div class="global-info-copy">
                                            <span class="global-info-label">
                                                Last Dental Visit
                                            </span>

                                            <strong class="global-info-value">
                                                {{ optional($patient->dentalHistory)->last_dental_visit
                                                ? Carbon::parse(
                                                optional($patient->dentalHistory)->last_dental_visit
                                                )->format('M d, Y')
                                                : 'N/A' }}
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="global-info-item global-info-item-compact">
                                        <span class="global-info-icon status-default">
                                            <i class="fa-solid fa-user-doctor"></i>
                                        </span>

                                        <div class="global-info-copy">
                                            <span class="global-info-label">
                                                Previous Dentist
                                            </span>

                                            <strong class="global-info-value">
                                                {{ optional($patient->dentalHistory)->previous_dentist ?? 'N/A' }}
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="global-info-item global-info-item-compact">
                                        <span class="global-info-icon status-default">
                                            <i class="fa-solid fa-tooth"></i>
                                        </span>

                                        <div class="global-info-copy">
                                            <span class="global-info-label">
                                                Extraction Date
                                            </span>

                                            <strong class="global-info-value">
                                                {{ $dentalDates?->extraction_date
                                                ? Carbon::parse($dentalDates->extraction_date)->format('M d, Y')
                                                : 'N/A' }}
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="global-info-item global-info-item-compact">
                                        <span class="global-info-icon status-default">
                                            <i class="fa-solid fa-teeth-open"></i>
                                        </span>

                                        <div class="global-info-copy">
                                            <span class="global-info-label">
                                                Dentures Date
                                            </span>

                                            <strong class="global-info-value">
                                                {{ $dentalDates?->dentures_date
                                                ? Carbon::parse($dentalDates->dentures_date)->format('M d, Y')
                                                : 'N/A' }}
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="global-info-item global-info-item-compact global-info-item-wide">
                                        <span class="global-info-icon status-default">
                                            <i class="fa-solid fa-teeth"></i>
                                        </span>

                                        <div class="global-info-copy">
                                            <span class="global-info-label">
                                                Orthodontic Treatment Date
                                            </span>

                                            <strong class="global-info-value">
                                                {{ $dentalDates?->ortho_date
                                                ? Carbon::parse($dentalDates->ortho_date)->format('M d, Y')
                                                : 'N/A' }}
                                            </strong>
                                        </div>
                                    </div>

                                </div>
                            </div>


                            {{-- =========================
                            DENTAL SYMPTOMS & HABITS
                            ========================== --}}
                            <div>
                                <div class="section-card-title">
                                    <i class="fa-solid fa-notes-medical"></i>
                                    <span>Dental Symptoms & Habits</span>
                                    <span class="section-card-title-line"></span>
                                </div>

                                <div class="global-info-group">
                                    @php
                                    $hasDentalAnswer = false;
                                    @endphp

                                    @foreach ($patient->dentalHistoryAnswers ?? [] as $dentAnswer)
                                    @if ($dentAnswer->answer)
                                    @php
                                    $hasDentalAnswer = true;
                                    @endphp

                                    <span class="status-pill status-completed">
                                        <span class="status-dot"></span>

                                        <span>
                                            {{ str_replace(
                                            '_',
                                            ' ',
                                            Str::title(
                                            optional($dentAnswer->condition)->code
                                            ?? 'Symptom'
                                            )
                                            ) }}
                                        </span>
                                    </span>
                                    @endif
                                    @endforeach

                                    @if (!$hasDentalAnswer)
                                    <span class="status-pill status-default">
                                        <span class="status-dot"></span>
                                        <span>No symptoms reported</span>
                                    </span>
                                    @endif
                                </div>
                            </div>


                            {{-- =========================
                            MEDICAL HISTORY
                            ========================== --}}
                            <div>
                                <div class="section-card-title">
                                    <i class="fa-solid fa-heart-pulse"></i>
                                    <span>Medical History</span>
                                    <span class="section-card-title-line"></span>
                                </div>

                                <div class="global-info-grid global-info-grid-2">
                                    @php
                                    $hasMedicalRecord = false;
                                    @endphp

                                    @foreach ($medicalAnswers as $mAns)
                                    @if (
                                    $mAns->answer_bool === true ||
                                    !empty($mAns->answer_text) ||
                                    !empty($mAns->answer_date)
                                    )
                                    @php
                                    $hasMedicalRecord = true;

                                    $medicalValue = collect([
                                    $mAns->answer_bool === true
                                    ? 'Yes'
                                    : null,

                                    $mAns->answer_text ?: null,

                                    $mAns->answer_date
                                    ? Carbon::parse($mAns->answer_date)->format('M d, Y')
                                    : null,
                                    ])
                                    ->filter()
                                    ->implode(' · ');
                                    @endphp

                                    <div class="global-info-item global-info-item-compact">
                                        <span class="global-info-icon status-default">
                                            <i class="fa-solid fa-stethoscope"></i>
                                        </span>

                                        <div class="global-info-copy">
                                            <span class="global-info-label">
                                                {{ str_replace(
                                                '_',
                                                ' ',
                                                Str::title(
                                                optional($mAns->question)->code
                                                ?? 'Question'
                                                )
                                                ) }}
                                            </span>

                                            <strong class="global-info-value">
                                                {{ $medicalValue }}
                                            </strong>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach

                                    @if (!$hasMedicalRecord)
                                    <div class="global-info-item global-info-item-compact">
                                        <span class="global-info-icon status-default">
                                            <i class="fa-solid fa-circle-info"></i>
                                        </span>

                                        <div class="global-info-copy">
                                            <span class="global-info-label">
                                                Medical History
                                            </span>

                                            <strong class="global-info-value">
                                                No medical records found
                                            </strong>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>


                            {{-- =========================
                            MEDICAL CONDITIONS
                            ========================== --}}
                            <div>
                                <div class="section-card-title">
                                    <i class="fa-solid fa-disease"></i>
                                    <span>Medical Conditions</span>
                                    <span class="section-card-title-line"></span>
                                </div>

                                <div class="global-info-group">
                                    @if (
                                    isset($patient->medicalHistory->diseaseAnswers) &&
                                    $patient->medicalHistory->diseaseAnswers->count() > 0
                                    )
                                    @foreach (
                                    $patient->medicalHistory->diseaseAnswers
                                    as $diseaseAnswer
                                    )
                                    <span class="status-pill status-pending">
                                        <span class="status-dot"></span>

                                        <span>
                                            {{ $diseaseAnswer->disease->label ?? 'Condition' }}
                                        </span>
                                    </span>
                                    @endforeach
                                    @else
                                    <span class="status-pill status-default">
                                        <span class="status-dot"></span>
                                        <span>None reported</span>
                                    </span>
                                    @endif
                                </div>
                            </div>


                            {{-- =========================
                            PATIENT ODONTOGRAM
                            ========================== --}}
                            <div>
                                <div class="section-card-title">
                                    <i class="fa-solid fa-teeth"></i>
                                    <span>Patient Odontogram</span>
                                    <span class="section-card-title-line"></span>

                                    @if (!empty($odontogramData))
                                    <span class="status-pill status-default">
                                        <span class="status-dot"></span>

                                        <span>
                                            {{ $odontogramLastUpdatedAt
                                            ? 'Updated ' . $odontogramLastUpdatedAt->format('M d, Y')
                                            : 'Saved Chart' }}
                                        </span>
                                    </span>
                                    @endif
                                </div>

                                @if (!empty($odontogramData))
                                @include('components.odontogram-preview', [
                                'odontogramData' => $odontogramData,
                                ])
                                @else
                                <div class="empty-state empty-state-compact">
                                    <div class="empty-state-icon">
                                        <i class="fa-solid fa-tooth"></i>
                                    </div>

                                    <h3 class="empty-state-title">
                                        No odontogram saved yet
                                    </h3>

                                    <p class="empty-state-sub">
                                        This patient does not have a recorded odontogram procedure yet.
                                    </p>
                                </div>
                                @endif
                            </div>


                            {{-- =========================
                            ADDITIONAL CONCERNS
                            ========================== --}}
                            <div>
                                <div class="section-card-title">
                                    <i class="fa-regular fa-comment-dots"></i>
                                    <span>Additional Dental Concerns</span>
                                    <span class="section-card-title-line"></span>
                                </div>

                                @php
                                $concerns =
                                optional($patient->dentalHistoryConcerns)
                                ->additional_concerns
                                ?? null;
                                @endphp

                                @if ($concerns)
                                <div class="global-info-item">
                                    <span class="global-info-icon status-pending">
                                        <i class="fa-regular fa-comment-dots"></i>
                                    </span>

                                    <div class="global-info-copy">
                                        <span class="global-info-label">
                                            Patient Concern
                                        </span>

                                        <span class="global-info-value">
                                            {{ $concerns }}
                                        </span>
                                    </div>
                                </div>
                                @else
                                <div class="global-info-item global-info-item-compact">
                                    <span class="global-info-icon status-default">
                                        <i class="fa-regular fa-comment"></i>
                                    </span>

                                    <div class="global-info-copy">
                                        <span class="global-info-label">
                                            Additional Dental Concerns
                                        </span>

                                        <span class="global-info-value">
                                            No additional concerns added
                                        </span>
                                    </div>
                                </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </section>
        </div>
    </div>
</main>

@if ($canStartProcedure && $procedureAppointment)
@php
$startProcedureSchedule =
Carbon::parse(
$procedureAppointment->appointment_date
)->format('l, F j, Y')
. ' • '
. (
$procedureAppointment->appointment_time
? Carbon::parse(
$procedureAppointment->appointment_time
)->format('g:i A')
: 'Time not recorded'
);

$startProcedureService =
($procedureAppointment->service_type_name ?? '') === 'Others'
? (
$procedureAppointment->other_services
?: 'Others'
)
: (
$procedureAppointment->service_type_name
?: 'Appointment'
);
@endphp

<x-start-procedure-modal id="startModal" subtitle="Open the odontogram to begin this appointment."
    :patient="$patientName" :schedule="$startProcedureSchedule" :service="$startProcedureService" :start-url="route(
        'dentist.dentist.appointments.start',
        ['id' => $procedureAppointment->id, 'from' => 'patient-profile', 'start_procedure' => 1]
    )" />
@endif
@endsection

@section('scripts')
<script>

    function maskPatientPhone(value) {
        const raw = String(value || '').trim();

        if (!raw || raw === 'N/A') {
            return 'N/A';
        }

        const digits = raw.replace(/\D/g, '');

        if (digits.length <= 4) {
            return '••' + digits.slice(-2);
        }

        return digits.slice(0, 2) + '••• ••• ' + digits.slice(-4);
    }

    function maskPatientEmail(value) {
        const raw = String(value || '').trim();

        if (!raw || raw === 'N/A') {
            return 'N/A';
        }

        const [local, domain] = raw.split('@');

        if (!domain) {
            return raw;
        }

        const visibleLocal = local.length > 2 ?
            local.slice(0, 2) :
            local.slice(0, 1);

        return `${visibleLocal}•••@${domain}`;
    }

    function maskPatientIdentity(value) {
        const raw = String(value || '').trim();

        if (!raw || raw === 'N/A') {
            return 'N/A';
        }

        if (raw.length <= 4) {
            return `••${raw.slice(-2)}`;
        }

        return `${raw.slice(0, 2)}••••${raw.slice(-2)}`;
    }

    function getMaskedPatientValue(type, value) {
        if (type === 'email') {
            return maskPatientEmail(value);
        }

        if (type === 'phone') {
            return maskPatientPhone(value);
        }

        return maskPatientIdentity(value);
    }

    function syncPatientSensitiveValues(masked = true) {
        document
            .querySelectorAll('#profileContainer .patient-sensitive-value')
            .forEach(element => {
                const rawValue = element.dataset.raw || 'N/A';
                const type = element.dataset.type || 'identity';
                const output = element.querySelector('strong') || element;

                output.textContent = masked ?
                    getMaskedPatientValue(type, rawValue) :
                    rawValue;

                element.dataset.masked = masked ? 'true' : 'false';
            });
    }

    function togglePatientProfilePrivacy(button) {
        if (!button) {
            return;
        }

        const currentlyMasked = button.dataset.masked !== 'false';
        const nextMasked = !currentlyMasked;

        syncPatientSensitiveValues(nextMasked);

        button.dataset.masked = nextMasked ? 'true' : 'false';
        button.setAttribute('aria-pressed', nextMasked ? 'false' : 'true');

        const tooltip = nextMasked ?
            'Show private information' :
            'Hide private information';

        button.dataset.tooltip = tooltip;
        button.setAttribute('aria-label', tooltip);

        button.innerHTML = nextMasked ?
            '<i class="fa-regular fa-eye"></i>' :
            '<i class="fa-regular fa-eye-slash"></i>';
    }

    document.addEventListener('DOMContentLoaded', () => {
        syncPatientSensitiveValues(true);
    });

    function switchTreatmentTab(tabName) {
        const futureTab =
            document.getElementById('futureTab');

        const pastTab =
            document.getElementById('pastTab');

        const futureContent =
            document.getElementById('futureContent');

        const pastContent =
            document.getElementById('pastContent');

        if (
            !futureTab ||
            !pastTab ||
            !futureContent ||
            !pastContent
        ) {
            return;
        }

        const showFuture = tabName === 'future';
        const activePanel = showFuture
            ? futureContent
            : pastContent;

        const inactivePanel = showFuture
            ? pastContent
            : futureContent;

        futureTab.classList.toggle(
            'is-active',
            showFuture
        );

        pastTab.classList.toggle(
            'is-active',
            !showFuture
        );

        futureTab.setAttribute(
            'aria-selected',
            showFuture ? 'true' : 'false'
        );

        pastTab.setAttribute(
            'aria-selected',
            showFuture ? 'false' : 'true'
        );

        inactivePanel.classList.remove(
            'is-active',
            'is-entering'
        );

        inactivePanel.hidden = true;

        activePanel.hidden = false;
        activePanel.classList.remove(
            'is-active',
            'is-entering'
        );

        void activePanel.offsetWidth;

        activePanel.classList.add(
            'is-active',
            'is-entering'
        );

        window.setTimeout(() => {
            activePanel.classList.remove(
                'is-entering'
            );
        }, 320);
    }

    function openStartModal() {
        window.openModal?.('startModal');
    }
</script>
@endsection