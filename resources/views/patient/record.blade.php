@extends('layouts.app')

@section('layout-role', 'patient')

@section('title', 'Dental Records')

@section('styles')
    @vite('resources/css/pages/patient/records.css')
@endsection

@section('content')

    @php
        $notifications = collect($notifications ?? []);
        $notifCount = $notifications->count();
    @endphp

    @php
        $patientRecordsPageData = [
            'records' => [
                'currentPage' => $records->currentPage(),
                'lastPage' => $records->lastPage(),
                'total' => $records->total(),
                'from' => $records->firstItem() ?? 0,
                'to' => $records->lastItem() ?? 0,
            ],

            'documentRequests' => [
                'currentPage' => $documentRequests->currentPage(),
                'lastPage' => $documentRequests->lastPage(),
                'total' => $documentRequests->total(),
                'from' => $documentRequests->firstItem() ?? 0,
                'to' => $documentRequests->lastItem() ?? 0,
            ],
        ];
    @endphp

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" id="patientRecordsPageData" type="application/json">
    {!! json_encode(
        $patientRecordsPageData,
        JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    ) !!}
</script>

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        window.patientRecordsPageData =
            JSON.parse(
                document.getElementById(
                    'patientRecordsPageData'
                )?.textContent || '{}'
            );
    </script>

    <main id="mainContent" class="app-page-shell page-enter patient-records-page" data-patient-records-page>
        <div class="w-full">

            @php
                $totalRecords = $records?->total() ?? 0;
            @endphp

            <section class="patient-hero">
                <div class="patient-hero-inner">

                    <div class="patient-hero-copy">
                        <h1 class="patient-hero-title">
                            Dental Records Overview
                        </h1>

                        <p class="patient-hero-subtitle">
                            A complete history of your dental visits,
                            treatments, and prescriptions.
                        </p>
                    </div>

                    <div class="patient-hero-stats">
                        <span class="patient-hero-stat">
                            <i class="fa-solid fa-list-check"></i>

                            {{ $totalRecords }}
                            {{ $totalRecords === 1 ? 'Total Visit' : 'Total Visits' }}
                        </span>

                        @if ($latestDate)
                            <span class="patient-hero-stat">
                                <i class="fa-regular fa-calendar-check"></i>

                                Latest: {{ $latestDate }}
                            </span>
                        @endif
                    </div>

                </div>
            </section>

            <div class="records-sections">

                <section id="visitHistorySection" class="card records-visits-card">
                    <div class="card-body">
                        <div class="records-section-title">
                            <span>
                                <i class="fa-solid fa-clock-rotate-left mr-1"></i>
                                Visit History
                            </span>

                            <div></div>
                        </div>

                        @if ($totalRecords)
                            <x-pagination-bar id="recordsPagebarTop" info-id="recordsPageInfoTop"
                                pagination-id="recordsPaginationTop" position="top" :show-entries="true"
                                page-size-id="recordsPageSize" page-size-callback="changeRecordsPageSize" :page-size-value="$records->perPage()"
                                page-size-label="per page" label="visits" :total="$records->total()" :from="$records->firstItem() ?? 0"
                                :to="$records->lastItem() ?? 0" />

                            <div id="visitHistoryList" class="space-y-0">
                                @foreach ($records as $i => $record)
                                    <x-appointment-record-card :appointment="$record" variant="past" :show-details="true"
                                        :show-countdown="false" :show-time-range="false" :animation-delay="$i * 0.08" :previous-odontogram="$previousOdontogramByVisitId[$record->id] ?? []" />
                                @endforeach
                            </div>

                            <x-pagination-bar id="recordsPagebarBottom" info-id="recordsPageInfoBottom"
                                pagination-id="recordsPaginationBottom" position="bottom" :show-entries="false" label="visits"
                                :total="$records->total()" :from="$records->firstItem() ?? 0" :to="$records->lastItem() ?? 0" />
                        @else
                            <div class="empty-state fade-up">
                                <div class="empty-state-icon">
                                    <i class="fa-solid fa-folder-open"></i>
                                </div>

                                <p class="empty-state-title">
                                    No records yet
                                </p>

                                <p class="empty-state-sub">
                                    Completed appointment records will appear here after your first dental visit.
                                </p>

                                <a href="{{ route('patient.book.appointment') }}" class="empty-state-btn">
                                    <i class="fa-solid fa-calendar-plus"></i>
                                    Book First Appointment
                                </a>
                            </div>
                        @endif
                    </div>
                </section>

                <section id="documentRequestHistory" class="card document-history-card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>

                            <div>
                                <h2 class="card-title">
                                    Document Request History
                                </h2>

                                <p class="card-subtitle">
                                    Track documents you requested from the dental clinic.
                                </p>
                            </div>
                        </div>

                        <div class="card-header-right">
                            <span class="card-header-badge">
                                {{ $documentRequestStats['total'] ?? 0 }}
                                {{ ($documentRequestStats['total'] ?? 0) === 1 ? 'Request' : 'Requests' }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="global-info-grid document-request-summary-grid">
                            <div class="global-info-item">
                                <div class="global-info-icon status-pending">
                                    <i class="fa-regular fa-clock"></i>
                                </div>

                                <div class="global-info-copy">
                                    <span class="global-info-label">
                                        Pending
                                    </span>

                                    <strong class="global-info-value">
                                        {{ $documentRequestStats['pending'] ?? 0 }}
                                    </strong>
                                </div>
                            </div>

                            <div class="global-info-item">
                                <div class="global-info-icon status-approved">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>

                                <div class="global-info-copy">
                                    <span class="global-info-label">
                                        Approved
                                    </span>

                                    <strong class="global-info-value">
                                        {{ $documentRequestStats['approved'] ?? 0 }}
                                    </strong>
                                </div>
                            </div>

                            <div class="global-info-item">
                                <div class="global-info-icon status-rejected">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </div>

                                <div class="global-info-copy">
                                    <span class="global-info-label">
                                        Rejected
                                    </span>

                                    <strong class="global-info-value">
                                        {{ $documentRequestStats['rejected'] ?? 0 }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        @if ($documentRequests->count())

                            <x-pagination-bar id="documentRequestsPagebarTop" info-id="documentRequestsPageInfoTop"
                                pagination-id="documentRequestsPaginationTop" position="top" :show-entries="true"
                                page-size-id="documentRequestsPageSize" page-size-callback="changeDocumentRequestsPageSize"
                                :page-size-value="$documentRequests->perPage()" page-size-label="per page" label="requests" :total="$documentRequests->total()"
                                :from="$documentRequests->firstItem() ?? 0" :to="$documentRequests->lastItem() ?? 0" />

                            <div id="documentRequestsList" class="document-history-list">
                                @foreach ($documentRequests as $documentRequest)
                                    @php
                                        $rawStatus = strtolower(
                                            str_replace('_', '-', $documentRequest->status ?? 'pending'),
                                        );

                                        $displayStatus = in_array(
                                            $rawStatus,
                                            ['approved', 'ready', 'ready-for-pickup', 'ready-for-release', 'released'],
                                            true,
                                        )
                                            ? 'approved'
                                            : ($rawStatus === 'rejected'
                                                ? 'rejected'
                                                : 'pending');

                                        $requestDate = $documentRequest->request_date
                                            ? \Carbon\Carbon::parse($documentRequest->request_date)->format('F d, Y')
                                            : optional($documentRequest->created_at)->format('F d, Y');

                                        $requestTime = $documentRequest->request_time
                                            ? \Carbon\Carbon::parse($documentRequest->request_time)->format('g:i A')
                                            : optional($documentRequest->created_at)->format('g:i A');

                                        $processedDate = $documentRequest->approved_at
                                            ? \Carbon\Carbon::parse($documentRequest->approved_at)->format('F d, Y')
                                            : null;

                                        $statusLabel = match ($displayStatus) {
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                            default => 'Pending',
                                        };
                                    @endphp

                                    <article class="global-record-card document-request-item"
                                        data-ui-style="
                            --record-accent:
                            {{ $displayStatus === 'approved'
                                ? 'var(--status-approved-solid)'
                                : ($displayStatus === 'rejected'
                                    ? 'var(--status-rejected-solid)'
                                    : 'var(--status-pending-solid)') }};
                        ">
                                        <div class="global-record-card-grid">
                                            <div class="document-request-topline">
                                                <span class="document-history-reference">
                                                    {{ $documentRequest->reference_number ?? '—' }}
                                                </span>

                                                <span class="status-pill status-{{ $displayStatus }}">
                                                    <span class="status-dot"></span>
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>

                                            <div>
                                                <h3 class="global-record-name">
                                                    {{ \Illuminate\Support\Str::of($documentRequest->document_type ?? 'Document')->replace(['_', '-'], ' ')->title() }}
                                                </h3>

                                                <div class="global-record-subline">
                                                    <span class="global-info-pill">
                                                        <i class="fa-regular fa-calendar"></i>
                                                        {{ $requestDate ?: '—' }}
                                                    </span>

                                                    @if ($requestTime)
                                                        <span class="global-info-pill">
                                                            <i class="fa-regular fa-clock"></i>
                                                            {{ $requestTime }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="document-request-purpose">
                                                <i class="fa-solid fa-bullseye"></i>

                                                <span class="document-request-purpose-label">
                                                    Purpose:
                                                </span>

                                                <strong>
                                                    {{ $documentRequest->purpose ?: '—' }}
                                                </strong>
                                            </div>

                                            @if ($displayStatus === 'approved' && $processedDate)
                                                <div class="document-request-status-note status-approved">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Processed on {{ $processedDate }}
                                                </div>
                                            @elseif ($displayStatus === 'rejected' && $documentRequest->rejection_reason)
                                                <div class="document-request-status-note status-rejected">
                                                    <i class="fa-solid fa-circle-xmark"></i>
                                                    {{ $documentRequest->rejection_reason }}
                                                </div>
                                            @elseif ($displayStatus === 'pending')
                                                <div class="document-request-status-note status-pending">
                                                    <i class="fa-regular fa-clock"></i>
                                                    Waiting for clinic review
                                                </div>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <x-pagination-bar id="documentRequestsPagebarBottom" info-id="documentRequestsPageInfoBottom"
                                pagination-id="documentRequestsPaginationBottom" position="bottom" :show-entries="false"
                                label="requests" />
                        @else
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fa-solid fa-file-circle-question"></i>
                                </div>

                                <p class="empty-state-title">
                                    No document requests yet
                                </p>

                                <p class="empty-state-sub">
                                    Documents you request from the clinic will appear here with their current status.
                                </p>
                            </div>
                        @endif

                    </div>
                </section>

            </div>
        </div>
    </main>
@endsection
