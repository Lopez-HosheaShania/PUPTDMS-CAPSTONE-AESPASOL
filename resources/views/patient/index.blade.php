@extends('layouts.app')

@section('layout-role', 'patient')

@section('title', 'Dashboard')

@section('styles')
    @vite('resources/css/pages/patient/dashboard.css')
@endsection

@section('content')

    @php
        $notifications = collect($notifications ?? []);
        $notifCount = $notifications->count();
        $odontogramSnapshotService = app(\App\Services\AppointmentOdontogramSnapshotService::class);

        $previousOdontogramByAppointment = $previousOdontogramByAppointment ?? [];
        $homeRecords = ($records ?? collect())
            ->filter(function ($r) {
                return in_array(strtolower($r->status ?? ''), ['completed', 'cancelled']);
            })
            ->map(function ($r) use ($odontogramSnapshotService, $previousOdontogramByAppointment) {
                $appointmentOdontogram = $odontogramSnapshotService->appointmentSnapshot(
                    $r->procedure?->odontogram_data ?? [],
                    $previousOdontogramByAppointment[$r->id] ?? [],
                );
                $followUp = $r->followUpAppointments
                    ?->sortBy(function ($followUpAppt) {
                        return sprintf(
                            '%s %s',
                            $followUpAppt->appointment_date ?? '',
                            $followUpAppt->appointment_time ?? '',
                        );
                    })
                    ?->first();
                return [
                    'id' => $r->id,
                    'service' => $r->service_type,
                    'date' => $r->appointment_date ? \Carbon\Carbon::parse($r->appointment_date)->format('F d, Y') : '',
                    'time' => $r->appointment_time ?? '',
                    'status' => strtolower($r->status ?? ''),
                    'dentist_name' =>
                        $r->dentist_name ??
                        (optional($r->dentist)->name ?? (optional($r->originalDentist)->name ?? 'Not assigned')),
                    'follow_up' => $followUp
                        ? [
                            'date' => $followUp->appointment_date
                                ? \Carbon\Carbon::parse($followUp->appointment_date)->format('F d, Y')
                                : null,

                            'time' => $followUp->appointment_time
                                ? \Carbon\Carbon::parse($followUp->appointment_time)->format('g:i A')
                                : null,

                            'service' => $followUp->service_type ?? 'Follow-up',

                            'status' => $followUp->status ?? 'upcoming',

                            'reason' => $followUp->follow_up_reason ?? null,
                        ]
                        : null,
                    'duration' => $r->procedure?->procedure_duration_seconds
                        ? \Carbon\CarbonInterval::seconds((int) $r->procedure->procedure_duration_seconds)
                            ->cascade()
                            ->forHumans(['short' => true, 'minimumUnit' => 'second'])
                        : $r->duration ?? ($r->procedure_duration ?? ($r->treatment_duration ?? '60 mins')),
                    'remarks' => $r->remarks ?? '',
                    'oral' => $r->procedure?->oral_examination ?? '',
                    'diagnosis' => $r->procedure?->diagnosis ?? '',
                    'prescription' => $r->procedure?->prescriptions ?? '',
                    'odontogram' => $r->procedure?->odontogram_data ?? [],

                    'previous_odontogram' => $previousOdontogramByAppointment[$r->id] ?? [],

                    'treatment_items' => \App\Support\OdontogramTreatmentDisplay::items($appointmentOdontogram),
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

        $completedCalendarAppointments = [];

        foreach (
            collect($records ?? [])->filter(function ($record) {
                return strtolower($record->status ?? '') === 'completed';
            })
            as $record
        ) {
            if (empty($record->appointment_date)) {
                continue;
            }

            $appointmentOdontogram = $odontogramSnapshotService->appointmentSnapshot(
                $record->procedure?->odontogram_data ?? [],
                $previousOdontogramByAppointment[$record->id] ?? [],
            );

            $dateKey = \Carbon\Carbon::parse($record->appointment_date)->format('Y-m-d');

            $completedCalendarAppointments[$dateKey] ??= [];

            $completedCalendarAppointments[$dateKey][] = [
                'service' => $record->service_type ?? 'Dental Appointment',

                'time' => !empty($record->appointment_time)
                    ? \Carbon\Carbon::parse($record->appointment_time)->format('g:i A')
                    : 'Time not recorded',

                'status' => 'completed',

                'dentist' => $record->dentist_name ?? (optional($record->dentist)->name ?? 'Assigned Dentist'),

                'duration' => $record->procedure?->procedure_duration_seconds
                    ? \Carbon\CarbonInterval::seconds((int) $record->procedure->procedure_duration_seconds)
                        ->cascade()
                        ->forHumans(['short' => true, 'minimumUnit' => 'second'])
                    : $record->duration ?? ($record->procedure_duration ?? ($record->treatment_duration ?? '60 mins')),
                'remarks' => $record->remarks ?? null,
                'oral' => $record->procedure?->oral_examination ?? null,
                'diagnosis' => $record->procedure?->diagnosis ?? null,
                'prescription' => $record->procedure?->prescriptions ?? null,
                'treatment_items' => \App\Support\OdontogramTreatmentDisplay::items($appointmentOdontogram),
            ];
        }

        $dashboardDisplayName = optional($patient)->name ?? (auth()->user()->name ?? 'Patient User');
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

        $birthdate = optional($patient)->birthdate ?: optional(optional($patient)->user)->birthdate;
        $gender = optional($patient)->gender ?: optional(optional($patient)->user)->gender;
        $age = null;
        $birthdateDisplay = 'N/A';

        if ($birthdate) {
            try {
                $birthdateCarbon = \Carbon\Carbon::parse($birthdate);
                $age = $birthdateCarbon->age;
                $birthdateDisplay = $birthdateCarbon->format('M d, Y');
            } catch (\Throwable $e) {
                $age = null;
                $birthdateDisplay = 'N/A';
            }
        }
    @endphp

    <main id="mainContent" class="app-page-shell patient-dashboard-page page-enter">
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
                                    <span id="patientName" data-patient-name></span>
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
                                    {{ isset($upcomingAppointment) && $upcomingAppointment ? 'Appointment scheduled' : 'Ready to book' }}
                                </span>

                                <span
                                    class="greeting-insight-chip inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                                    <i class="fa-regular fa-calendar"></i>
                                    Next Visit: {{ $nextVisitText }}
                                </span>

                                <span
                                    class="greeting-insight-chip inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-semibold text-white">
                                    <i class="fa-solid fa-tooth"></i>
                                    {{ $recordCount > 0
                                        ? 'Last Visit: ' . ($latestRecordDate ?? 'Available')
                                        : 'No dental
                                                                                                                                                                                                                                                        record yet' }}
                                </span>

                            </div>
                        </div>

                        <div class="greeting-banner-actions">
                            <a href="{{ route('patient.book.appointment') }}" class="ui-btn ui-btn-primary ui-btn-shimmer"
                                onclick="
                                if (
                                    window.UPCOMING_DATA?.exists &&
                                    ['upcoming', 'rescheduled'].includes(
                                        String(window.UPCOMING_DATA.status || '').toLowerCase()
                                    )
                                ) {
                                    event.preventDefault();
                                    window.openModal?.('activeAppointmentModal');
                                }
                            ">
                                <i class="fa-solid fa-calendar-plus"></i>
                                <span>Book Appointment</span>
                            </a>

                            <a href="{{ route('patient.record') }}" class="ui-btn ui-btn-ghost-light">
                                <i class="fa-solid fa-folder-open"></i>
                                <span>View Records</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if (collect($reservedBookingReminders ?? [])->isNotEmpty())
                <div class="grid gap-4 mt-4" aria-label="Reserved booking reminders">

                    @foreach ($reservedBookingReminders as $reservedPeriod)
                        <section class="card">

                            <div class="card-header">

                                <div class="card-header-left">

                                    <span class="card-header-icon status-pending" aria-hidden="true">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </span>

                                    <div class="min-w-0">

                                        <div class="flex flex-wrap items-center gap-2">

                                            <h2 class="card-title">
                                                {{ $reservedPeriod->title }}
                                            </h2>

                                            <span class="status-pill status-pending">
                                                <span class="status-dot" aria-hidden="true"></span>
                                                <span>Booking required</span>
                                            </span>

                                        </div>

                                        <div class="card-subtitle flex flex-wrap items-center gap-x-4 gap-y-1">

                                            <span class="inline-flex items-center gap-1.5">
                                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>

                                                {{ $reservedPeriod->reserved_date->format('M d, Y') }}
                                            </span>

                                            <span class="inline-flex items-center gap-1.5">
                                                <i class="fa-regular fa-clock" aria-hidden="true"></i>

                                                {{ \Carbon\Carbon::parse($reservedPeriod->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($reservedPeriod->end_time)->format('g:i A') }}
                                            </span>

                                            <span class="inline-flex items-center gap-1.5">
                                                <i class="fa-solid fa-users" aria-hidden="true"></i>

                                                {{ $reservedPeriod->target_label }}
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="card-body">

                                <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-3 items-center">

                                    <div class="info-card flex items-center gap-3">

                                        <span class="card-header-icon status-pending" aria-hidden="true">
                                            <i class="fa-regular fa-bell"></i>
                                        </span>

                                        <div class="min-w-0">

                                            <div class="card-title">
                                                Reserved Appointment Reminder
                                            </div>

                                            <p class="card-subtitle">
                                                This dental appointment period is scheduled for your patient group.
                                                Complete your booking before it reaches capacity.
                                            </p>

                                        </div>

                                    </div>

                                    <a href="{{ route('book.appointment.reserved', $reservedPeriod) }}"
                                        class="ui-btn ui-btn-primary w-full lg:w-auto">

                                        <span>Book Reserved Appointment</span>

                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>

                                    </a>

                                </div>

                            </div>

                        </section>
                    @endforeach

                </div>
            @endif

            <div id="upcomingAppointmentWrapper"
                class="skeleton-section {{ collect($reservedBookingReminders ?? [])->isNotEmpty() ? 'mt-4' : '' }}">
                <div class="card skeleton-card skeleton-shell skeleton-fade-swap">

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

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-stretch dashboard-grid-tight patient-calendar-row">
                <div class="xl:col-span-4">
                    <div id="profileSkeletonContainer"
                        class="dashboard-glass rounded-[1rem] overflow-hidden skeleton-section skeleton-shell skeleton-fade-swap mt-3">
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

            <div
                class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-stretch dashboard-grid-tight dashboard-services-row mt-6">
                <div class="xl:col-span-5 flex">
                    <div id="requestDocsContainer" class="w-full h-full">
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

                <div class="xl:col-span-7 flex">
                    <div id="dentalOverviewContainer" class="w-full h-full skeleton-fade-swap">
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
            </div>
    </main>

    <template id="dashboardRecordCardsTemplate">
        <div class="space-y-3">

            @foreach (collect($homeRecords ?? [])->take(3) as $index => $record)
                <x-appointment-record-card :appointment="$record" variant="past" :show-details="true" :show-countdown="false"
                    :show-time-range="false" :compact="true" :animation-delay="$index * 0.08" :previous-odontogram="$record['previous_odontogram'] ?? []" />
            @endforeach

            <div class="pt-2">
                <a href="{{ route('patient.record') }}" class="ui-btn ui-btn-primary w-full">
                    <i class="fa-solid fa-folder-open"></i>

                    <span>
                        View All Records
                    </span>

                    <i class="fa-solid fa-arrow-right text-[11px]"></i>
                </a>
            </div>

        </div>
    </template>
    @if (session('appointment_confirmation'))
        @php
            $appointmentConfirmation = session('appointment_confirmation');
        @endphp

        <x-booking.confirmed-modal id="appointmentConfirmedModal" eyebrow="Appointment Booking"
            title="Appointment Confirmed" subtitle="Your appointment has been successfully scheduled."
            header-icon="fa-check" section-icon="fa-calendar-check" section-eyebrow="Booking Status"
            section-title="Booking successfully completed"
            section-message="Your selected appointment schedule has been saved and confirmed."
            detail-label="Appointment Status" :result-title="$appointmentConfirmation['status'] ?? 'Confirmed'" message-title="Schedule details"
            message-id="appointmentConfirmedMessage">
            <div class="confirmed-modal-schedule-grid">

                <div class="confirmed-modal-schedule-item">
                    <span class="confirmed-modal-schedule-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </span>

                    <div>
                        <span class="confirmed-modal-schedule-label">
                            Date
                        </span>

                        <strong class="confirmed-modal-schedule-value">
                            {{ $appointmentConfirmation['date'] ?? 'N/A' }}
                        </strong>
                    </div>
                </div>

                <div class="confirmed-modal-schedule-item">
                    <span class="confirmed-modal-schedule-icon">
                        <i class="fa-regular fa-clock"></i>
                    </span>

                    <div>
                        <span class="confirmed-modal-schedule-label">
                            Time
                        </span>

                        <strong class="confirmed-modal-schedule-value">
                            {{ $appointmentConfirmation['time'] ?? 'N/A' }}
                        </strong>
                    </div>
                </div>

            </div>

            <div class="confirmed-modal-schedule-note">
                <i class="fa-solid fa-circle-info"></i>

                <span>
                    Please arrive on time and bring your
                    school or office ID.
                </span>
            </div>

            <x-slot:footer>
                <button type="button" id="appointmentConfirmedDoneBtn" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-check"></i>
                    Done
                </button>
            </x-slot:footer>

        </x-booking.confirmed-modal>
    @endif

    <div id="privateInformationModal" class="ui-modal modal-theme-warning" role="dialog" aria-modal="true"
        aria-labelledby="privateInformationModalTitle" aria-describedby="privateInformationModalDescription">
        <div class="ui-modal-card modal-sm" tabindex="-1">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <div class="modal-copy">
                        <h2 id="privateInformationModalTitle" class="modal-title">
                            Show Private Information?
                        </h2>

                        <p id="privateInformationModalDescription" class="modal-subtitle">
                            Your personal contact and identification details will become visible.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" onclick="closePrivateInformationModal()"
                    aria-label="Close private information modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="global-confirm-alert">
                    <i class="fa-solid fa-eye"></i>

                    <div>
                        <p>Private information will be displayed.</p>

                        <span>
                            Make sure no one else can see your screen before continuing.
                        </span>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" onclick="closePrivateInformationModal()">
                    Cancel
                </button>

                <button type="button" class="ui-btn ui-btn-primary" onclick="confirmShowPrivateInformation()">
                    <i class="fa-regular fa-eye"></i>
                    Show Information
                </button>
            </div>
        </div>
    </div>

    @include('components.appointment-calendar-script', [
        'mode' => 'patient-dashboard',
        'renderStyle' => 'patient',
        'calendarContainerId' => 'calendarSkeletonContainer',
    
        'dateInputId' => null,
        'timeInputId' => null,
    
        'slotEndpoint' => route('book.appointment.slots'),
        'bookingUrl' => route('patient.book.appointment'),
    
        'scheduleRules' => isset($schedules)
            ? $schedules
            : (isset($scheduleRules)
                ? $scheduleRules
                : \App\Models\ClinicSchedule::active()->get()->values()->toArray()),
    
        'blockedDates' => $unavailableDates ?? [],
        'appointmentCountsPerDay' => $appointmentCountsPerDay ?? [],
        'philippineHolidays' => $philippineHolidays ?? [],
        'personalAppointments' => $calendarAppointments ?? [],
        'completedAppointments' => $completedCalendarAppointments ?? [],
    
        'useDynamicScheduleRules' => true,
        'disallowToday' => true,
        'allowToggleOffDate' => false,
    
        'maxFutureMonths' => 6,
        'historyMonths' => 12,
    
        'appointmentHistoryUrl' => route('patient.record'),
    ])
@endsection

@section('scripts')
    <script>
        @if (session('appointment_draft_completed'))
            localStorage.removeItem(
                "appointmentDraft:v1"
            );
        @endif

        @if (session('appointment_confirmation'))
            document.addEventListener(
                'DOMContentLoaded',
                () => {
                    window.openModal?.(
                        'appointmentConfirmedModal'
                    );

                    document.documentElement.classList.add(
                        'appointment-confirmed-open',
                        'modal-lock'
                    );

                    document.body.classList.add(
                        'appointment-confirmed-open',
                        'modal-lock'
                    );

                    document
                        .getElementById(
                            'appointmentConfirmedDoneBtn'
                        )
                        ?.addEventListener(
                            'click',
                            (event) => {

                                const button = event.currentTarget;

                                button.blur();

                                requestAnimationFrame(() => {

                                    window.closeModal?.(
                                        'appointmentConfirmedModal'
                                    );

                                    document.documentElement.classList.remove(
                                        'appointment-confirmed-open',
                                        'modal-lock'
                                    );

                                    document.body.classList.remove(
                                        'appointment-confirmed-open',
                                        'modal-lock'
                                    );

                                });

                            }
                        );
                }
            );
        @endif

        function renderGreeting() {
            const nameEl = document.getElementById("patientName");
            const greetingEl = document.getElementById("greetingText");
            const iconEl = document.getElementById("greetingIcon");

            if (!nameEl || !greetingEl || !iconEl) return;

            const rawPatientName =
                @json($patient->name ?? (auth()->user()->name ?? 'Patient'));

            nameEl.textContent =
                window.formatPatientName?.(
                    rawPatientName
                ) ||
                rawPatientName;

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
                    ->filter(function ($appt) {
                        $status = strtolower($appt->status ?? '');
                        return !in_array($status, ['completed', 'cancelled', 'declined']);
                    })
                    ->filter(function ($appt) {
                        return \Carbon\Carbon::parse($appt->appointment_date)->startOfDay()->gte(\Carbon\Carbon::today());
                    })
                    ->sortBy(function ($appt) {
                        return \Carbon\Carbon::parse($appt->appointment_date . ' ' . ($appt->appointment_time ?? '00:00:00'));
                    })
                    ->first();
            }

            $upcomingJs = null;
            if (isset($upcomingAppointment) && $upcomingAppointment) {
                $uD = \Carbon\Carbon::parse($upcomingAppointment->appointment_date);
                $uT = \Carbon\Carbon::parse($upcomingAppointment->appointment_time);
                $upcomingJs = [
                    'exists' => true,
                    'service' => $upcomingAppointment->service_type ?? '—',
                    'date' => $uD->format('M d, Y'),
                    'time_raw' => $upcomingAppointment->appointment_time,
                    'time_fmt' => $uT->format('g:i A'),
                    'dentist' => $upcomingAppointment->dentist_name ?? 'Dr. Nelson P. Angeles',
                    'status' => ucfirst($upcomingAppointment->status),
                    'isRescheduled' => strtolower($upcomingAppointment->status) === 'rescheduled',
                    'indexUrl' => route('patient.appointment.index'),
                    'bookUrl' => route('patient.book.appointment'),
                ];
            } else {
                $upcomingJs = [
                    'exists' => false,
                    'bookUrl' => route('patient.book.appointment'),
                ];
            }

            $profileRows = [['Date of Birth', $birthdateDisplay !== 'N/A' ? \Carbon\Carbon::parse($birthdate)->format('F d, Y') : '—'], ['Age', $age !== null ? $age . ' yrs' : '—'], ['Gender', $gender ?? '—'], ['Contact', $patient->phone ?? '—'], ['Email', $patient->email ?? '—']];
        @endphp

        var UPCOMING_DATA = @json($upcomingJs);
        var PATIENT_NAME = "{{ urlencode($patient->name ?? 'Guest') }}";

        var PROFILE_COMPLETION = {{ $profileCompletionPercent }};
        var TOTAL_VISITS = {{ $recordCount }};
        var LAST_VISIT = @json($latestRecordDate ?? 'No record yet');
        var NEXT_VISIT = @json($nextVisitText);

        var PROFILE_DATA = {
            name: @json($patient->name ?? 'Guest'),
            roleLabel: "{{ $patient->faculty_code ? 'Faculty' : ($patient->student_no ? 'Student' : 'Patient') }}",
            facultyCode: "{{ $patient->faculty_code ?? '' }}",
            studentNo: "{{ $patient->student_no ?? '' }}",
            age: "{{ $age ?? '' }}",
            birthdate: "{{ $birthdateDisplay }}",
            gender: "{{ $gender ?? 'N/A' }}",
            contact: "{{ $patient->phone ?? 'N/A' }}",
            email: "{{ $patient->email ?? 'N/A' }}",
            emergencyName: "{{ optional($patient->medicalHistory)->emergency_person ?? 'Not specified' }}",
            emergencyNumber: @json(optional($patient->medicalHistory)->emergency_number ?? 'N/A'),
            emergencyRelation: @json(optional($patient->medicalHistory)->emergency_relation ?? ''),
            hasAlert: @json(
                (isset($patient->medicalHistory->diseaseAnswers) && $patient->medicalHistory->diseaseAnswers->count() > 0) ||
                    (isset($patient->medicalHistoryAnswers) &&
                        $patient->medicalHistoryAnswers->where('question.code', 'allergy_medicine')->where('answer_bool', true)->count() > 0)),
            avatar: @json($dashboardAvatarUrl)
        };

        var ROUTE_BOOK = "{{ route('patient.book.appointment') }}";
        var ROUTE_RECORD = "{{ route('patient.record') }}";

        @if (session('activeAppointmentModal'))
            document.addEventListener('DOMContentLoaded', function() {
                window.openModal?.(
                    'activeAppointmentModal'
                );
            });
        @endif

        document.addEventListener('DOMContentLoaded', function() {
            const draftSavedToast =
                sessionStorage.getItem(
                    'appointmentDraftSavedToast'
                );

            if (
                draftSavedToast ===
                '1'
            ) {
                sessionStorage.removeItem(
                    'appointmentDraftSavedToast'
                );

                window.showToast?.({
                    type: 'success',
                    title: 'Draft saved',
                    message: 'Your appointment draft has been saved.',
                    duration: 3500,
                });
            }

            const quickAction =
                new URLSearchParams(
                    window.location.search
                ).get(
                    'quick_action'
                );

            const privateInformationModal =
                document.getElementById(
                    'privateInformationModal'
                );

            privateInformationModal?.addEventListener(
                'click',
                function(event) {
                    if (event.target === privateInformationModal) {
                        closePrivateInformationModal();
                    }
                }
            );

            renderGreeting();

            if (quickAction === 'record') {
                setTimeout(() => {
                    window.openDocModal ?
                        window.openDocModal('dentalHealthRecordModal') :
                        document.getElementById('dentalHealthRecordModal')?.showModal();
                }, 150);
            }

            if (quickAction === 'clearance') {
                setTimeout(() => {
                    window.openDocModal ?
                        window.openDocModal('dentalClearanceModal') :
                        document.getElementById('dentalClearanceModal')?.showModal();
                }, 150);
            }

            if (quickAction) {
                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('quick_action');
                window.history.replaceState({}, '', cleanUrl.toString());
            }

            window.runEnterpriseLoading([{
                    label: 'Loading calendar and appointment details',
                    tasks: [
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
                function(s) {
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

        let pendingPrivateInformationButton = null;

        function setPrivateInformationVisibility(button, shouldMask) {
            if (!button || !window.profileMaskedState) {
                return;
            }

            setMaskedContent(
                'maskedIdentityValue',
                window.profileMaskedState.identityMasked,
                window.profileMaskedState.identityRaw,
                shouldMask
            );

            setMaskedContent(
                'maskedContactValue',
                window.profileMaskedState.contactMasked,
                window.profileMaskedState.contactRaw,
                shouldMask
            );

            setMaskedContent(
                'maskedEmailValue',
                window.profileMaskedState.emailMasked,
                window.profileMaskedState.emailRaw,
                shouldMask
            );

            setMaskedContent(
                'maskedEmergencyNumber',
                window.profileMaskedState.emergencyMasked,
                window.profileMaskedState.emergencyRaw,
                shouldMask
            );

            const tooltip = shouldMask ?
                'Show private information' :
                'Hide private information';

            button.setAttribute(
                'data-masked',
                shouldMask ? 'true' : 'false'
            );

            button.setAttribute(
                'aria-pressed',
                shouldMask ? 'false' : 'true'
            );

            button.setAttribute('aria-label', tooltip);
            button.setAttribute('data-tooltip', tooltip);

            button.innerHTML = shouldMask ?
                '<i class="fa-regular fa-eye"></i>' :
                '<i class="fa-regular fa-eye-slash"></i>';
        }

        function handlePrivateInformationToggle(button) {
            if (!button) {
                return;
            }

            const isCurrentlyMasked =
                button.getAttribute('data-masked') !== 'false';

            if (!isCurrentlyMasked) {
                setPrivateInformationVisibility(button, true);
                return;
            }

            openPrivateInformationModal(button);
        }

        function openPrivateInformationModal(button) {
            const modal = document.getElementById(
                'privateInformationModal'
            );

            if (!modal) {
                return;
            }

            pendingPrivateInformationButton = button;

            modal.classList.remove('closing');
            modal.classList.add('open');

            document.documentElement.classList.add('modal-lock');
            document.body.classList.add('modal-lock');

            requestAnimationFrame(() => {
                modal
                    .querySelector('.ui-modal-card')
                    ?.focus();
            });
        }

        function hideActiveGlobalTooltip() {
            window.hideGlobalActionTooltip?.();

            document
                .getElementById('globalActionTooltip')
                ?.classList.remove('show');
        }

        function closePrivateInformationModal() {
            const modal = document.getElementById(
                'privateInformationModal'
            );

            if (!modal || !modal.classList.contains('open')) {
                pendingPrivateInformationButton = null;
                return;
            }

            hideActiveGlobalTooltip();

            modal.classList.add('closing');

            window.setTimeout(() => {
                modal.classList.remove('open', 'closing');

                document.documentElement.classList.remove('modal-lock');
                document.body.classList.remove('modal-lock');

                pendingPrivateInformationButton = null;
            }, 170);
        }

        function confirmShowPrivateInformation() {
            const button =
                pendingPrivateInformationButton ||
                document.getElementById(
                    'dashboardProfilePrivacyToggle'
                );

            if (button) {
                setPrivateInformationVisibility(button, false);
            }

            closePrivateInformationModal();
        }

        function renderUpcomingAppointment() {
            const wrapper =
                document.getElementById(
                    'upcomingAppointmentWrapper'
                );

            if (!wrapper) {
                return;
            }

            const d = UPCOMING_DATA;

            if (d.exists) {
                const statusClass =
                    d.isRescheduled ?
                    'status-rescheduled' :
                    'status-upcoming';

                window.swapSkeletonContent(
                    'upcomingAppointmentWrapper',

                    '<section class="card">' +

                    '<div class="card-header">' +

                    '<div class="card-header-left">' +

                    '<span class="card-header-icon ' +
                    statusClass +
                    '">' +
                    '<i class="fa-solid fa-tooth"></i>' +
                    '</span>' +

                    '<div class="min-w-0">' +

                    '<div class="flex flex-wrap items-center gap-2">' +

                    '<h3 class="card-title">' +
                    window.escapeHtml(
                        d.service
                    ) +
                    '</h3>' +

                    '<span class="status-pill ' +
                    statusClass +
                    '">' +

                    '<span class="status-dot"></span>' +

                    window.escapeHtml(
                        d.status
                    ) +

                    '</span>' +

                    '</div>' +

                    '<div class="card-subtitle flex flex-wrap items-center gap-x-4 gap-y-1">' +

                    '<span class="inline-flex items-center gap-1.5">' +
                    '<i class="fa-regular fa-calendar"></i>' +
                    window.escapeHtml(
                        d.date
                    ) +
                    '</span>' +

                    '<span class="inline-flex items-center gap-1.5">' +
                    '<i class="fa-regular fa-clock"></i>' +
                    window.escapeHtml(
                        d.time_fmt
                    ) +
                    '</span>' +

                    '<span class="inline-flex items-center gap-1.5 min-w-0">' +
                    '<i class="fa-solid fa-user-doctor"></i>' +
                    '<span class="truncate">' +
                    window.escapeHtml(
                        d.dentist
                    ) +
                    '</span>' +
                    '</span>' +

                    '</div>' +

                    '</div>' +

                    '</div>' +

                    '</div>' +


                    '<div class="card-body">' +

                    '<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-3 items-center">' +

                    '<div class="info-card flex items-center gap-3">' +

                    '<span class="card-header-icon ' +
                    statusClass +
                    '">' +
                    '<i class="fa-regular fa-bell"></i>' +
                    '</span>' +

                    '<div class="min-w-0">' +

                    '<div class="card-title">' +
                    'Appointment Reminder' +
                    '</div>' +

                    '<div class="card-subtitle">' +
                    'Please arrive 10 minutes early.' +
                    '</div>' +

                    '</div>' +

                    '</div>' +


                    '<a href="' +
                    window.escapeHtml(
                        d.indexUrl
                    ) +
                    '" class="ui-btn ui-btn-primary">' +

                    '<span>Manage Appointment</span>' +
                    '<i class="fa-solid fa-arrow-right"></i>' +

                    '</a>' +

                    '</div>' +

                    '</div>' +

                    '</section>'
                );

                return;
            }


            window.swapSkeletonContent(
                'upcomingAppointmentWrapper',

                '<section class="card dashboard-upcoming-empty">' +

                '<div class="card-header dashboard-upcoming-empty-header">' +

                '<div class="card-header-left">' +

                '<span class="card-header-icon status-default">' +
                '<i class="fa-regular fa-calendar"></i>' +
                '</span>' +

                '<div class="min-w-0">' +

                '<div class="flex flex-wrap items-center gap-2">' +

                '<h3 class="card-title">' +
                'No upcoming regular appointment' +
                '</h3>' +

                '</div>' +

                '<p class="card-subtitle">' +
                'Choose a preferred date and time to schedule your next dental visit.' +
                '</p>' +

                '</div>' +

                '</div>' +

                '<button type="button" ' +
                'onclick="scrollToCalendar(event)" ' +
                'class="ui-btn {{ collect($reservedBookingReminders ?? [])->isNotEmpty() ? 'ui-btn-secondary' : 'ui-btn-primary' }}">' +

                '<i class="fa-solid fa-calendar-days"></i>' +
                '<span>Check Available Dates</span>' +

                '</button>' +

                '</div>' +

                '</section>'
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
                    '<span class="badge-role role-faculty">' +
                    '<i class="fa-solid fa-user-tie"></i>' +
                    '<span>Faculty</span>' +
                    '</span>';
                identityLabel = 'Faculty Code';
                identityRaw = pData.facultyCode;
                identityMasked = maskIdCode(pData.facultyCode);
            } else if (pData.studentNo && pData.studentNo !== 'null') {
                roleBadge =
                    '<span class="badge-role role-student">' +
                    '<i class="fa-solid fa-user-graduate"></i>' +
                    '<span>Student</span>' +
                    '</span>';
                identityLabel = 'Student No';
                identityRaw = pData.studentNo;
                identityMasked = maskIdCode(pData.studentNo);
            } else {
                roleBadge =
                    '<span class="badge-role role-patient">' +
                    '<i class="fa-solid fa-user"></i>' +
                    '<span>Patient</span>' +
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
                '<button type="button" ' +
                'id="dashboardProfilePrivacyToggle" ' +
                'onclick="handlePrivateInformationToggle(this)" ' +
                'class="ui-icon-btn neutral patient-privacy-toggle" ' +
                'data-masked="true" ' +
                'data-tooltip="Show private information" ' +
                'data-tooltip-tone="neutral" ' +
                'aria-label="Show private information" ' +
                'aria-pressed="false">' +
                '<i class="fa-regular fa-eye"></i>' +
                '</button>';

            var identityRow = identityLabel ?
                '<div class="patient-profile-row">' +
                '<span class="patient-profile-label">' +
                '<i class="fa-regular fa-id-badge"></i>' +
                '<span>' + window.escapeHtml(identityLabel) + '</span>' +
                '</span>' +
                '<span id="maskedIdentityValue" data-masked="true" class="patient-profile-value">' +
                window.escapeHtml(identityMasked) +
                '</span>' +
                '</div>' :
                '';

            var emergencySection = hasEmergency ?
                '<div class="patient-profile-emergency-content">' +
                '<p class="patient-profile-emergency-name">' +
                window.escapeHtml(pData.emergencyName) +
                '</p>' +

                '<div class="patient-profile-emergency-meta">' +

                '<span class="patient-profile-emergency-relation">' +
                (
                    pData.emergencyRelation ?
                    '(' + window.escapeHtml(pData.emergencyRelation) + ')' :
                    ''
                ) +
                '</span>' +

                '<span id="maskedEmergencyNumber" data-masked="true" class="patient-profile-emergency-number">' +
                window.escapeHtml(maskedEmergency) +
                '</span>' +

                '</div>' +
                '</div>' :

                '<div class="patient-profile-empty">' +
                '<i class="fa-solid fa-user-plus"></i>' +
                '<p>No emergency contact added</p>' +
                '</div>';

            window.swapSkeletonContent(
                'profileSkeletonContainer',

                '<div class="dashboard-card-polished dashboard-glass patient-profile-card patient-profile-card-compact">' +

                '<div class="patient-profile-topbar">' +

                '<div class="patient-profile-topbar-copy">' +
                '<span class="patient-profile-eyebrow">' +
                '<i class="fa-solid fa-user"></i>' +
                '<span>Patient Profile</span>' +
                '</span>' +
                '<span class="patient-profile-topbar-label">' +
                'Personal information' +
                '</span>' +
                '</div>' +

                globalToggle +

                '</div>' +

                '<div class="patient-profile-identity">' +

                '<div class="patient-profile-avatar">' +
                (
                    window.PatientUI?.buildAvatarHtml({
                        name: pData.name,
                        url: pData.avatar,
                        size: 'lg',
                        escapeHtml: window.escapeHtml
                    }) || ''
                ) +
                '</div>' +

                '<div class="patient-profile-identity-copy">' +

                '<h2 class="patient-profile-name">' +
                window.escapeHtml(pData.name) +
                '</h2>' +

                '<div class="patient-profile-badges">' +
                roleBadge +

                '<span class="status-pill status-active">' +
                '<span class="status-dot"></span>' +
                '<span>Profile Active</span>' +
                '</span>' +

                '</div>' +

                '</div>' +

                '</div>' +

                '<div class="patient-profile-divider"></div>' +

                '<div class="patient-profile-details">' +

                '<div class="patient-profile-summary-grid">' +

                '<div class="patient-profile-summary-card">' +

                '<span class="patient-profile-summary-icon">' +
                '<i class="fa-solid fa-cake-candles"></i>' +
                '</span>' +

                '<div class="patient-profile-summary-copy">' +

                '<span class="patient-profile-summary-label">' +
                'Age' +
                '</span>' +

                '<strong class="patient-profile-summary-value">' +
                window.escapeHtml(
                    pData.age ?
                    pData.age + ' yrs' :
                    'N/A'
                ) +
                '</strong>' +

                '<small>' +
                window.escapeHtml(pData.birthdate) +
                '</small>' +

                '</div>' +

                '</div>' +

                '<div class="patient-profile-summary-card">' +

                '<span class="patient-profile-summary-icon">' +
                '<i class="fa-solid fa-venus-mars"></i>' +
                '</span>' +

                '<div class="patient-profile-summary-copy">' +

                '<span class="patient-profile-summary-label">' +
                'Gender' +
                '</span>' +

                '<strong class="patient-profile-summary-value">' +
                window.escapeHtml(pData.gender) +
                '</strong>' +

                '</div>' +

                '</div>' +

                '</div>' +

                identityRow +

                '<div class="patient-profile-row">' +

                '<span class="patient-profile-label">' +
                '<i class="fa-solid fa-phone"></i>' +
                '<span>Contact</span>' +
                '</span>' +

                '<span id="maskedContactValue" data-masked="true" class="patient-profile-value">' +
                window.escapeHtml(maskedContact) +
                '</span>' +

                '</div>' +

                '<div class="patient-profile-row">' +

                '<span class="patient-profile-label">' +
                '<i class="fa-solid fa-envelope"></i>' +
                '<span>Email</span>' +
                '</span>' +

                '<span id="maskedEmailValue" data-masked="true" class="patient-profile-value">' +
                window.escapeHtml(maskedEmail) +
                '</span>' +

                '</div>' +

                '</div>' +

                '<div class="patient-profile-section">' +

                '<div class="patient-profile-section-heading">' +

                '<div class="patient-profile-section-title">' +
                '<i class="fa-solid fa-user-check"></i>' +
                '<span>Profile Completion</span>' +
                '</div>' +

                '<strong class="patient-profile-section-percentage">' +
                window.escapeHtml(String(PROFILE_COMPLETION)) +
                '%' +
                '</strong>' +

                '</div>' +

                '<div class="patient-profile-progress">' +
                '<span style="width:' +
                Math.max(0, Math.min(100, Number(PROFILE_COMPLETION) || 0)) +
                '%"></span>' +
                '</div>' +

                '<p class="patient-profile-section-note">' +
                'Keep your information updated for smoother appointments.' +
                '</p>' +

                '</div>' +

                '<div class="patient-profile-section patient-profile-activity">' +

                '<div class="patient-profile-section-title">' +
                '<i class="fa-solid fa-tooth"></i>' +
                '<span>Dental Activity</span>' +
                '</div>' +

                '<div class="patient-profile-activity-grid">' +

                '<div class="patient-profile-activity-item">' +
                '<span>Total Visits</span>' +
                '<strong>' +
                window.escapeHtml(String(TOTAL_VISITS)) +
                '</strong>' +
                '</div>' +

                '<div class="patient-profile-activity-item">' +
                '<span>Last Visit</span>' +
                '<strong>' +
                window.escapeHtml(LAST_VISIT) +
                '</strong>' +
                '</div>' +

                '</div>' +

                '<div class="patient-profile-next-visit">' +

                '<div>' +
                '<span>Next Visit</span>' +
                '<strong>' +
                window.escapeHtml(NEXT_VISIT) +
                '</strong>' +
                '</div>' +

                '</div>' +

                '</div>' +

                '<div class="patient-profile-emergency">' +

                '<div class="patient-profile-emergency-title">' +
                '<i class="fa-solid fa-heart-pulse"></i>' +
                '<span>Emergency Contact</span>' +
                '</div>' +

                emergencySection +

                '</div>' +

                '</div>'
            );
        }

        function renderRequestDocs() {
            var container =
                document.getElementById(
                    'requestDocsContainer'
                );

            if (!container) {
                return;
            }

            window.swapSkeletonContent(
                'requestDocsContainer',

                '<section class="card dashboard-service-card">' +

                '<div class="card-header">' +

                '<div class="card-header-left">' +

                '<span class="card-header-icon">' +
                '<i class="fa-solid fa-folder-open"></i>' +
                '</span>' +

                '<div class="min-w-0">' +

                '<h2 class="card-title">' +
                'Request Documents' +
                '</h2>' +

                '<p class="card-subtitle">' +
                'Choose a clinic document to request.' +
                '</p>' +

                '</div>' +

                '</div>' +

                '<span class="card-header-badge">' +
                '2 Services' +
                '</span>' +

                '</div>' +

                '<div class="card-body dashboard-service-body">' +

                '<div class="quick-actions-list dashboard-document-actions">' +


                '<button type="button" ' +
                'data-doc-open="dentalHealthRecordModal" ' +
                'class="quick-action quick-action-card dashboard-document-action">' +

                '<span class="quick-action-icon">' +
                '<i class="fa-solid fa-file-medical"></i>' +
                '</span>' +

                '<span class="quick-action-copy">' +

                '<span class="dashboard-document-title-row">' +

                '<span class="quick-action-title">' +
                'Dental Health Record' +
                '</span>' +

                '<span class="status-pill status-cancelled">' +
                '<span>Most Requested</span>' +
                '</span>' +

                '</span>' +

                '<span class="quick-action-sub">' +
                'Your dental history, diagnoses, treatments, and related medical information.' +
                '</span>' +

                '<span class="dashboard-document-meta">' +

                '<span class="status-pill status-default">' +
                '<span class="status-dot"></span>' +
                '<span>Dental Records</span>' +
                '</span>' +

                '<span class="status-pill status-default">' +
                '<span class="status-dot"></span>' +
                '<span>Medical</span>' +
                '</span>' +

                '<span class="status-pill status-default">' +
                '<span class="status-dot"></span>' +
                '<span>Diagnosis</span>' +
                '</span>' +

                '</span>' +

                '</span>' +

                '<span class="quick-action-arrow">' +
                '<i class="fa-solid fa-chevron-right"></i>' +
                '</span>' +

                '<i class="fa-solid fa-file-medical quick-action-bg-icon"></i>' +

                '</button>' +


                '<button type="button" ' +
                'data-doc-open="dentalClearanceModal" ' +
                'class="quick-action quick-action-card dashboard-document-action">' +

                '<span class="quick-action-icon dashboard-document-icon-warning">' +
                '<i class="fa-solid fa-file-circle-check"></i>' +
                '</span>' +

                '<span class="quick-action-copy">' +

                '<span class="dashboard-document-title-row">' +

                '<span class="quick-action-title">' +
                'Dental Clearance' +
                '</span>' +

                '<span class="status-pill status-upcoming">' +
                '<span>School Requirement</span>' +
                '</span>' +

                '</span>' +

                '<span class="quick-action-sub">' +
                'For school submission, annual compliance, and other official requirements.' +
                '</span>' +

                '<span class="dashboard-document-meta">' +

                '<span class="status-pill status-default">' +
                '<span class="status-dot"></span>' +
                '<span>Clearance</span>' +
                '</span>' +

                '<span class="status-pill status-default">' +
                '<span class="status-dot"></span>' +
                '<span>Official Copy</span>' +
                '</span>' +

                '</span>' +

                '</span>' +

                '<span class="quick-action-arrow">' +
                '<i class="fa-solid fa-chevron-right"></i>' +
                '</span>' +

                '<i class="fa-solid fa-file-circle-check quick-action-bg-icon"></i>' +

                '</button>' +


                '</div>' +

                '</div>' +

                '</section>'
            );
        }

        function openDashboardRecordModal(encodedRecord) {
            try {
                const record = JSON.parse(
                    decodeURIComponent(encodedRecord)
                );

                if (typeof window.openRecordModal === 'function') {
                    window.openRecordModal(record);
                    return;
                }

                if (typeof openRecordModal === 'function') {
                    openRecordModal(record);
                }
            } catch (error) {
                console.error(
                    'Unable to open dental record.',
                    error
                );
            }
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
                window.swapSkeletonContent(
                    'dentalOverviewContainer',

                    '<section class="card dashboard-service-card dental-summary-section">' +

                    '<div class="card-header">' +

                    '<div class="card-header-left">' +

                    '<span class="card-header-icon">' +
                    '<i class="fa-solid fa-chart-line"></i>' +
                    '</span>' +

                    '<div class="min-w-0">' +

                    '<h2 class="card-title">' +
                    'Dental Overview' +
                    '</h2>' +

                    '<p class="card-subtitle">' +
                    'Quick summary of your visits and dental activity.' +
                    '</p>' +

                    '</div>' +

                    '</div>' +

                    '<span class="card-header-badge">' +
                    'Patient Summary' +
                    '</span>' +

                    '</div>' +

                    '<div class="dental-summary-overview">' +

                    '<div class="dental-summary-stats">' +

                    '<div class="dental-summary-stat">' +
                    '<span class="dental-summary-stat-label">Total Visits</span>' +
                    '<strong class="dental-summary-stat-value">0</strong>' +
                    '</div>' +

                    '<div class="dental-summary-stat">' +
                    '<span class="dental-summary-stat-label">Latest Record</span>' +
                    '<strong class="dental-summary-stat-value dental-summary-stat-value-sm">' +
                    'No record yet' +
                    '</strong>' +
                    '</div>' +

                    '<div class="dental-summary-stat dental-summary-stat-wide">' +
                    '<span class="dental-summary-stat-label">Status</span>' +
                    '<strong class="dental-summary-status">' +
                    'Waiting for first completed visit' +
                    '</strong>' +
                    '</div>' +

                    '</div>' +

                    '</div>' +

                    '<div class="dental-summary-body">' +

                    '<div class="empty-state empty-state-compact">' +

                    '<div class="empty-state-icon">' +
                    '<i class="fa-solid fa-tooth"></i>' +
                    '</div>' +

                    '<h3 class="empty-state-title">' +
                    'No dental activity yet' +
                    '</h3>' +

                    '<p class="empty-state-sub">' +
                    'Your completed visits and latest dental treatment activity will appear here after your first finished appointment.' +
                    '</p>' +

                    '<a href="' + ROUTE_BOOK + '" class="ui-btn ui-btn-primary">' +
                    '<i class="fa-solid fa-calendar-plus"></i>' +
                    '<span>Book First Appointment</span>' +
                    '</a>' +

                    '</div>' +
                    '</div>' +

                    '</section>'
                );
                if (viewAll) viewAll.classList.add("hidden");
                return;
            }

            if (viewAll) viewAll.classList.remove("hidden");

            const recordsTemplate =
                document.getElementById(
                    'dashboardRecordCardsTemplate'
                );

            const html =
                recordsTemplate ?
                recordsTemplate.innerHTML :
                '';

            window.swapSkeletonContent(
                'dentalOverviewContainer',

                '<section class="card dashboard-service-card dental-summary-section">' +

                '<div class="card-header">' +

                '<div class="card-header-left">' +

                '<span class="card-header-icon">' +
                '<i class="fa-solid fa-chart-line"></i>' +
                '</span>' +

                '<div class="min-w-0">' +

                '<h2 class="card-title">' +
                'Dental Overview' +
                '</h2>' +

                '<p class="card-subtitle">' +
                'Latest records from your dental activity.' +
                '</p>' +

                '</div>' +

                '</div>' +

                '<span class="card-header-badge">' +
                'Patient Summary' +
                '</span>' +

                '</div>' +

                '<div class="dental-summary-overview">' +

                '<div class="dental-summary-stats">' +

                '<div class="dental-summary-stat">' +
                '<span class="dental-summary-stat-label">Total Visits</span>' +
                '<strong class="dental-summary-stat-value">' +
                count +
                '</strong>' +
                '</div>' +

                '<div class="dental-summary-stat">' +
                '<span class="dental-summary-stat-label">Latest Record</span>' +
                '<strong class="dental-summary-stat-value dental-summary-stat-value-sm">' +
                window.escapeHtml(dispLatestDate) +
                '</strong>' +
                '</div>' +

                '<div class="dental-summary-stat dental-summary-stat-wide">' +
                '<span class="dental-summary-stat-label">Status</span>' +
                '<strong class="dental-summary-status">' +
                window.escapeHtml(dispOverviewStatus) +
                '</strong>' +
                '</div>' +

                '</div>' +

                '</div>' +

                '<div class="dental-summary-body">' +
                html +
                '</div>' +

                '</section>'
            );
        }

        function initRequestDocInteractions() {
            document.querySelectorAll('.request-doc-card').forEach(function(card) {
                if (card.dataset.interactionsReady === 'true') return;
                card.dataset.interactionsReady = 'true';

                card.addEventListener('mousemove', function(e) {
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

                card.addEventListener('mouseleave', function() {
                    card.style.transform = '';
                });

                card.addEventListener('click', function(e) {
                    const rect = card.getBoundingClientRect();
                    const ripple = document.createElement('span');
                    const size = Math.max(rect.width, rect.height);

                    ripple.className = 'request-ripple';
                    ripple.style.width = size + 'px';
                    ripple.style.height = size + 'px';
                    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                    ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';

                    card.appendChild(ripple);

                    setTimeout(function() {
                        ripple.remove();
                    }, 600);
                });
            });
        }

        function scrollToCalendar(event) {
            event?.preventDefault();

            const calendar =
                document.getElementById(
                    'calendarSkeletonContainer'
                );

            if (!calendar) {
                return;
            }

            calendar.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            calendar.classList.add(
                'calendar-focus-pulse'
            );

            window.setTimeout(() => {
                calendar.classList.remove(
                    'calendar-focus-pulse'
                );
            }, 1200);
        }

        document.addEventListener('keydown', function(event) {
            if (
                event.key === 'Escape' &&
                document
                .getElementById('privateInformationModal')
                ?.classList.contains('open')
            ) {
                closePrivateInformationModal();
            }
        });
    </script>
@endsection
