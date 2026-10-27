@extends('layouts.app')

@section('layout-role', 'admin')

@section('title', 'AI Generated Report')

@section('styles')
    @vite('resources/css/pages/admin/ai-generated-report.css')
@endsection

@section('content')
    @php
        $riskLevel = data_get($aiReport, 'risk_level', 'Low');
        $riskExplanation = data_get(
            $aiReport,
            'risk_explanation',
            'Operational risk details are currently unavailable for this report.',
        );
        $riskClass = match ($riskLevel) {
            'High' => 'air-status-card--red',
            'Moderate' => 'air-status-card--amber',
            default => 'air-status-card--green',
        };
        $badgeClass = match ($riskLevel) {
            'High' => 'air-badge--red',
            'Moderate' => 'air-badge--amber',
            default => 'air-badge--green',
        };
    @endphp

    <main id="mainContent" class="app-page-shell page-enter mode-list">
        <div class="w-full">

            <div class="page-banner">
                <div class="page-banner-inner air-ai-banner-inner">
                    <div class="air-banner-breadcrumb">
                        <i class="fa-solid fa-table-columns"></i>
                        Reports
                        <span>›</span>
                        AI generated report
                    </div>

                    <div class="air-banner-row">
                        <div class="air-banner-title">
                            <div class="air-banner-icon">
                                <i class="fa-solid fa-brain"></i>
                            </div>
                            <div>
                                <div class="air-report-title-row">
                                    <h1 class="air-banner-heading">Administrative AI Generated Overall Report</h1>
                                    <span class="air-advisory-badge">AI-Generated &bull; Advisory Only</span>
                                </div>
                                <p class="air-banner-sub">{{ $aiReport['period'] }} · Monthly analysis</p>
                            </div>
                        </div>

                        <div class="air-banner-actions">
                            <a href="{{ route(request()->routeIs('dentist.*') ? 'dentist.reports' : 'admin.reports') }}" class="ui-btn ui-btn-secondary">

                                <i class="fa-solid fa-arrow-left"></i>
                                Back to reports
                            </a>

                            <button type="button" id="openPrintReportModal" class="ui-btn ui-btn-primary">

                                <i class="fa-solid fa-file-arrow-down"></i>
                                Save as PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <section id="aiReportScreenArea" class="air-screen">
                <div class="air-advisory-notice">
                    <p class="air-advisory-note">This report contains AI-generated summaries and interpretations intended to assist administrative review. Please verify all figures and findings against the underlying clinic records before making operational or administrative decisions.</p>
                    @if (($aiReport['generation_source'] ?? null) !== 'ai')
                        <p class="air-analysis-unavailable" role="status">AI-generated analysis is currently unavailable. Please refer to the verified clinic statistics shown in this report.</p>
                    @endif
                </div>

                <div class="air-status-strip">
                    <div class="air-status-card air-status-card--red">
                        <span class="air-status-label">Report period</span>
                        <span class="air-status-value">{{ $aiReport['period'] }}</span>
                        <span class="air-status-sub">
                            <i class="fa-regular fa-calendar"></i> Monthly analysis
                        </span>
                    </div>
                    <div class="air-status-card air-status-card--purple">
                        <span class="air-status-label">Generated at</span>
                        <span class="air-status-value air-status-value--sm">{{ $aiReport['generated_at'] }}</span>
                        <span class="air-status-sub">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            {{ $aiReport['generation_source'] === 'ai' ? 'AI-generated insight' : 'AI analysis unavailable' }}
                        </span>
                    </div>
                    <div class="air-status-card {{ $riskClass }}">
                        <span class="air-status-label">Operational risk</span>
                        <span class="air-status-value air-status-value--inline">
                            {{ $riskLevel }}
                            <span class="air-badge {{ $badgeClass }}">{{ $riskLevel }}</span>
                        </span>
                        <span class="air-status-sub">
                            <i class="fa-solid fa-shield-halved"></i> System assessment
                        </span>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <i class="fa-solid fa-brain"></i>
                            </div>
                            <div>
                                <h2 class="card-title">Executive summary</h2>
                                <p class="card-subtitle">AI-generated advisory overview based on system statistics</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="air-body-text">{{ $aiReport['executive_summary'] }}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div>
                                <h2 class="card-title">Key findings</h2>
                                <p class="card-subtitle">Important observations from the report data</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="air-findings-list">
                            @foreach ($aiReport['key_findings'] as $finding)
                                <div class="air-finding-item">
                                    <span class="air-finding-dot"></span>
                                    {{ $finding }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="air-two-col">
                    <div class="card">
                        @php
                            $screenTreatmentsRecorded =
                                data_get($aiReport, 'print_metrics.treatments_recorded') ??
                                data_get($aiReport, 'metrics.treatments_recorded') ??
                                data_get($aiReport, 'treatments.total_treatments', '—');
                            $screenTreatmentBreakdown = collect(
                                data_get(
                                    $aiReport,
                                    'print_metrics.treatment_breakdown',
                                    data_get($aiReport, 'treatments.breakdown', []),
                                ),
                            );
                        @endphp
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-tooth"></i>
                                </div>
                                <div>
                                    <h2 class="card-title">Treatment analysis</h2>
                                    <p class="card-subtitle">Treatment statistics interpretation</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="air-report-metrics-table">
                                <thead>
                                    <tr>
                                        <th>Treatment metric</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Treatments recorded</td>
                                        <td>{{ $screenTreatmentsRecorded }}</td>
                                    </tr>
                                    @foreach ($screenTreatmentBreakdown as $case)
                                        <tr>
                                            <td>{{ data_get($case, 'name', 'Other') }}</td>
                                            <td>{{ data_get($case, 'count', 0) }} /
                                                {{ rtrim(rtrim(number_format((float) data_get($case, 'pct', 0), 1), '0'), '.') }}%</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td>Period covered</td>
                                        <td>{{ $aiReport['period'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="air-findings-list">
                                @foreach ($aiReport['treatment_analysis'] as $item)
                                    <div class="air-finding-item">
                                        <span class="air-finding-dot"></span>
                                        {{ $item }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </div>
                                <div>
                                    <h2 class="card-title">Inventory analysis</h2>
                                    <p class="card-subtitle">Inventory condition interpretation</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="air-findings-list">
                                @foreach ($aiReport['inventory_analysis'] as $item)
                                    <div class="air-finding-item">
                                        <span class="air-finding-dot"></span>
                                        {{ $item }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $screenDocumentRequestAnalysis = $aiReport['document_request_analysis'] ?? [];
                @endphp

                @if (!empty($screenDocumentRequestAnalysis))
                    <div class="card mb-3">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-file-signature"></i>
                                </div>
                                <div>
                                    <h2 class="card-title">Document request analysis</h2>
                                    <p class="card-subtitle">System-generated summary of document request records</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="air-findings-list">
                                @foreach ($screenDocumentRequestAnalysis as $item)
                                    <div class="air-finding-item">
                                        <span class="air-finding-dot"></span>
                                        {{ $item }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card mb-3">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h2 class="card-title">Risk interpretation</h2>
                                <p class="card-subtitle">System-generated risk classification</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="air-risk-callout">
                            <div class="air-risk-icon">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="air-risk-text">
                                <strong>{{ $riskLevel }} risk</strong>
                                <p>{{ $riskExplanation }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $recommendationTitles = [
                        'Appointment scheduling',
                        'Cancellation follow-up',
                        'Inventory oversight',
                        'Treatment tracking',
                        'Administrative planning',
                        'Operational monitoring',
                        'Document request processing',
                    ];
                @endphp
                <div class="card mb-8">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <i class="fa-solid fa-lightbulb"></i>
                            </div>
                            <div>
                                <h2 class="card-title">Recommendations</h2>
                                <p class="card-subtitle">Suggested actions based on the analysis</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="air-rec-list">
                            @foreach ($aiReport['recommendations'] as $index => $recommendation)
                                <div class="air-rec-item">
                                    <span class="air-rec-num">{{ $index + 1 }}</span>
                                    <div class="air-rec-body">
                                        <strong>{{ $recommendationTitles[$index] ?? 'Recommendation' }}</strong>
                                        <p>{{ $recommendation }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    @php
        $totalPatients = data_get($aiReport, 'print_metrics.total_patients') ?? data_get($aiReport, 'metrics.total_patients') ?? 'Unavailable';
        $newPatients = data_get($aiReport, 'print_metrics.new_patients') ?? data_get($aiReport, 'metrics.new_patients') ?? 'Unavailable';
        $totalAppointments = data_get($aiReport, 'print_metrics.total_appointments') ?? data_get($aiReport, 'metrics.total_appointments') ?? 'Unavailable';
        $cancelledAppointments = data_get($aiReport, 'print_metrics.cancelled_appointments') ?? data_get($aiReport, 'metrics.cancelled_appointments') ?? 'Unavailable';
        $completionRate = data_get($aiReport, 'print_metrics.completion_rate') ?? data_get($aiReport, 'metrics.completion_rate') ?? 'Unavailable';
        $cancellationRate = data_get($aiReport, 'print_metrics.cancellation_rate') ?? data_get($aiReport, 'metrics.cancellation_rate') ?? 'Unavailable';
        $treatmentsRecorded = data_get($aiReport, 'print_metrics.treatments_recorded') ?? data_get($aiReport, 'metrics.treatments_recorded') ?? 'Unavailable';
        $lowStockCount = data_get($aiReport, 'print_metrics.low_stock_count') ?? data_get($aiReport, 'metrics.low_stock_count') ?? 'Unavailable';
        $criticalStockCount = data_get($aiReport, 'print_metrics.critical_stock_count') ?? data_get($aiReport, 'metrics.critical_stock_count') ?? 'Unavailable';
        $treatmentBreakdown = collect(data_get($aiReport, 'print_metrics.treatment_breakdown', []));

        $documentRequestAnalysis = $aiReport['document_request_analysis'] ?? [];
        $hasDocumentRequestAnalysis = !empty($documentRequestAnalysis);
        $totalReportPages = $hasDocumentRequestAnalysis ? 4 : 3;

        $docTotal = data_get($aiReport, 'print_metrics.document_requests_total', 0);
        $docPending = data_get($aiReport, 'print_metrics.document_requests_pending', 0);
        $docApproved = data_get($aiReport, 'print_metrics.document_requests_approved', 0);
        $docRejected = data_get($aiReport, 'print_metrics.document_requests_rejected', 0);
        $docApprovalRate = data_get($aiReport, 'print_metrics.document_requests_approval_rate', 0);
        $docPendingRate = data_get($aiReport, 'print_metrics.document_requests_pending_rate', 0);
        $docRejectionRate = data_get($aiReport, 'print_metrics.document_requests_rejection_rate', 0);
        $docMostRequested = data_get(
            $aiReport,
            'print_metrics.document_requests_most_requested',
            'No dominant document type yet',
        );
        $docMostRequestedCount = data_get($aiReport, 'print_metrics.document_requests_most_requested_count', 0);

        $docApprovalRateLabel = is_numeric($docApprovalRate)
            ? rtrim(rtrim(number_format((float) $docApprovalRate, 1), '0'), '.') . '%'
            : $docApprovalRate;

        $docPendingRateLabel = is_numeric($docPendingRate)
            ? rtrim(rtrim(number_format((float) $docPendingRate, 1), '0'), '.') . '%'
            : $docPendingRate;

        $docRejectionRateLabel = is_numeric($docRejectionRate)
            ? rtrim(rtrim(number_format((float) $docRejectionRate, 1), '0'), '.') . '%'
            : $docRejectionRate;

        $completionRateLabel = is_numeric($completionRate)
            ? rtrim(rtrim(number_format((float) $completionRate, 1), '0'), '.') . '%'
            : $completionRate;
        $cancellationRateLabel = is_numeric($cancellationRate)
            ? rtrim(rtrim(number_format((float) $cancellationRate, 1), '0'), '.') . '%'
            : $cancellationRate;

        $printMetricCards = [
            ['value' => $totalPatients, 'label' => 'Total patients', 'class' => 'metric-red'],
            ['value' => $newPatients, 'label' => 'New patients', 'class' => 'metric-red'],
            ['value' => $totalAppointments, 'label' => 'Total appointments', 'class' => 'metric-blue'],
            ['value' => $cancelledAppointments, 'label' => 'Cancellations', 'class' => 'metric-orange'],
            ['value' => $completionRateLabel, 'label' => 'Completion rate', 'class' => 'metric-darkred'],
            ['value' => $cancellationRateLabel, 'label' => 'Cancellation rate', 'class' => 'metric-red'],
        ];
    @endphp

    <section id="aiFullPrintDocument" class="air-print-doc">

        <div class="air-print-page" data-print-page="1">
            <div class="air-advisory-notice">
                <p class="air-advisory-note">This report contains AI-generated summaries and interpretations intended to assist administrative review. Please verify all figures and findings against the underlying clinic records before making operational or administrative decisions.</p>
                @if (($aiReport['generation_source'] ?? null) !== 'ai')
                    <p class="air-analysis-unavailable" role="status">AI-generated analysis is currently unavailable. Please refer to the verified clinic statistics shown in this report.</p>
                @endif
            </div>
            <div class="air-print-fixed-header">
                <strong>AI Generated Overall Report</strong>
                <span>· {{ $aiReport['period'] }} · Summary and metrics</span>
                <span class="air-advisory-badge">AI-Generated &bull; Advisory Only</span>
            </div>

            <div class="air-print-meta-grid">
                <div class="air-print-meta-card">
                    <span>Report period</span>
                    <strong>{{ $aiReport['period'] }}</strong>
                </div>
                <div class="air-print-meta-card">
                    <span>Generated at</span>
                    <strong>{{ $aiReport['generated_at'] }}</strong>
                </div>
                <div class="air-print-meta-card">
                    <span>Operational risk level</span>
                    <strong>{{ $riskLevel }}</strong>
                </div>
            </div>

            <section class="air-print-section">
                <div class="air-print-section-title"><span>01</span>
                    <h2>Executive summary</h2>
                </div>
                <p class="air-print-body-text">{{ $aiReport['executive_summary'] }}</p>
            </section>

            <section class="air-print-section">
                <div class="air-print-section-title"><span>02</span>
                    <h2>Key findings</h2>
                </div>

                <div class="air-print-kpi-grid">
                    @foreach ($printMetricCards as $metric)
                        <div class="air-print-kpi-card {{ $metric['class'] }}">
                            <strong>{{ $metric['value'] }}</strong>
                            <span>{{ $metric['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                <ul class="air-print-dot-list">
                    @foreach ($aiReport['key_findings'] as $finding)
                        <li>{{ $finding }}</li>
                    @endforeach
                </ul>
            </section>

            <div class="air-print-fixed-footer">
                <div class="air-print-footer-left">
                    <strong>This document contains personal-identifiable information subject to Data Privacy.</strong>
                    <span>Please keep this document protected and in a safe place.</span>
                </div>

                <div class="air-print-footer-right">
                    This is system-generated, signature is not required.
                </div>
            </div>
        </div>

        <div class="air-print-page" data-print-page="2">
            <div class="air-print-fixed-header">
                <strong>AI Generated Overall Report</strong>
                <span>· {{ $aiReport['period'] }} · Findings and analysis</span>
            </div>

            <section class="air-print-section">
                <div class="air-print-section-title"><span>03</span>
                    <h2>Treatment analysis</h2>
                </div>
                <table class="air-print-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Treatments recorded</td>
                            <td>{{ $treatmentsRecorded }}</td>
                        </tr>
                        @foreach ($treatmentBreakdown as $case)
                            <tr>
                                <td>{{ data_get($case, 'name', 'Other') }}</td>
                                <td>{{ data_get($case, 'count', 0) }} /
                                    {{ rtrim(rtrim(number_format((float) data_get($case, 'pct', 0), 1), '0'), '.') }}%</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td>Period covered</td>
                            <td>{{ $aiReport['period'] }}</td>
                        </tr>
                    </tbody>
                </table>
                @if (!empty($aiReport['treatment_analysis']))
                    <p class="air-print-body-text">{{ implode(' ', $aiReport['treatment_analysis']) }}</p>
                @endif
            </section>

            <section class="air-print-section">
                <div class="air-print-section-title"><span>04</span>
                    <h2>Inventory analysis</h2>
                </div>
                <table class="air-print-table air-print-table--inventory">
                    <thead>
                        <tr>
                            <th>Inventory metric</th>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Low-stock items</td>
                            <td class="status-ok">{{ (int) $lowStockCount > 0 ? 'Needs review' : '✓ Stable' }}</td>
                            <td><strong>{{ $lowStockCount }}</strong></td>
                        </tr>
                        <tr>
                            <td>Critical-stock items</td>
                            <td class="status-ok">{{ (int) $criticalStockCount > 0 ? 'Urgent' : '✓ None' }}</td>
                            <td><strong>{{ $criticalStockCount }}</strong></td>
                        </tr>
                        <tr>
                            <td>Items under monitoring</td>
                            <td class="status-ok">
                                {{ (int) $lowStockCount + (int) $criticalStockCount > 0 ? 'Monitor' : '✓ Clear' }}</td>
                            <td><strong>{{ (int) $lowStockCount + (int) $criticalStockCount }}</strong></td>
                        </tr>
                    </tbody>
                </table>
                @if (!empty($aiReport['inventory_analysis']))
                    <p class="air-print-body-text">{{ implode(' ', $aiReport['inventory_analysis']) }}</p>
                @endif
            </section>
            <div class="air-print-fixed-footer">
                <div class="air-print-footer-left">
                    <strong>This document contains personal-identifiable information subject to Data Privacy.</strong>
                    <span>Please keep this document protected and in a safe place.</span>
                </div>

                <div class="air-print-footer-right">
                    This is system-generated, signature is not required.
                </div>
            </div>
        </div>

        <div class="air-print-page" data-print-page="3">
            <div class="air-print-fixed-header">
                <strong>AI Generated Overall Report</strong>
                <span>· {{ $aiReport['period'] }} · {{ $hasDocumentRequestAnalysis ? 'Document requests and risk' : 'Risk and recommendations' }}</span>
            </div>

            @if ($hasDocumentRequestAnalysis)
                <section class="air-print-section">
                    <div class="air-print-section-title"><span>05</span>
                        <h2>Document request analysis</h2>
                    </div>

                    <table class="air-print-table air-print-table--inventory">
                        <thead>
                            <tr>
                                <th>Document request metric</th>
                                <th>Status</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total requests</td>
                                <td class="status-ok">This month</td>
                                <td><strong>{{ $docTotal }}</strong></td>
                            </tr>
                            <tr>
                                <td>Pending requests</td>
                                <td class="status-ok">{{ (int) $docPending > 0 ? 'Needs review' : '✓ Clear' }}</td>
                                <td><strong>{{ $docPending }}</strong></td>
                            </tr>
                            <tr>
                                <td>Approved requests</td>
                                <td class="status-ok">{{ $docApprovalRateLabel }} approval rate</td>
                                <td><strong>{{ $docApproved }}</strong></td>
                            </tr>
                            <tr>
                                <td>Rejected requests</td>
                                <td class="status-ok">{{ $docRejectionRateLabel }} rejection rate</td>
                                <td><strong>{{ $docRejected }}</strong></td>
                            </tr>
                            <tr>
                                <td>Most requested document</td>
                                <td class="status-ok">{{ $docMostRequestedCount }} request/s</td>
                                <td><strong>{{ $docMostRequested }}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="air-print-body-text">{{ implode(' ', $documentRequestAnalysis) }}</p>
                </section>

                <section class="air-print-section">
                    <div class="air-print-section-title"><span>06</span>
                        <h2>Risk interpretation</h2>
                    </div>
                    <div class="air-print-risk-box">
                        <strong>{{ $riskLevel }} risk</strong>
                        <p>{{ $riskExplanation }}</p>
                    </div>
                </section>
            @else
                <section class="air-print-section">
                    <div class="air-print-section-title"><span>06</span>
                        <h2>Risk interpretation</h2>
                    </div>
                    <div class="air-print-risk-box">
                        <strong>{{ $riskLevel }} risk</strong>
                        <p>{{ $riskExplanation }}</p>
                    </div>
                </section>

                <section class="air-print-section">
                    <div class="air-print-section-title"><span>07</span>
                        <h2>Recommendations</h2>
                    </div>
                    <div class="air-print-rec-list">
                        @foreach ($aiReport['recommendations'] as $index => $recommendation)
                            <div class="air-print-rec-card">
                                <strong>{{ $recommendationTitles[$index] ?? 'Recommendation' }}</strong>
                                <p>{{ $recommendation }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="air-print-fixed-footer">
                <div class="air-print-footer-left">
                    <strong>This document contains personal-identifiable information subject to Data Privacy.</strong>
                    <span>Please keep this document protected and in a safe place.</span>
                </div>

                <div class="air-print-footer-right">
                    This is system-generated, signature is not required.
                </div>
            </div>
        </div>

        @if ($hasDocumentRequestAnalysis)
            <div class="air-print-page" data-print-page="4">
                <div class="air-print-fixed-header">
                    <strong>AI Generated Overall Report</strong>
                    <span>· {{ $aiReport['period'] }} · Recommendations</span>
                </div>

                <section class="air-print-section">
                    <div class="air-print-section-title"><span>07</span>
                        <h2>Recommendations</h2>
                    </div>
                    <div class="air-print-rec-list">
                        @foreach ($aiReport['recommendations'] as $index => $recommendation)
                            <div class="air-print-rec-card">
                                <strong>{{ $recommendationTitles[$index] ?? 'Recommendation' }}</strong>
                                <p>{{ $recommendation }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="air-print-fixed-footer">
                    <div class="air-print-footer-left">
                        <strong>This document contains personal-identifiable information subject to Data Privacy.</strong>
                        <span>Please keep this document protected and in a safe place.</span>
                    </div>

                    <div class="air-print-footer-right">
                        This is system-generated, signature is not required.
                    </div>
                </div>
            </div>
        @endif
    </section>

    <div id="printReportModal" class="ui-modal" aria-hidden="true">

        <div class="ui-modal-card modal-xl air-modal" role="dialog" aria-modal="true"
            aria-labelledby="printReportTitle" data-ui-click="action-22499e15f3ee">

            <div class="air-modal-preview modal-preview-body" data-global-preview-zoom data-preview-min="0.5"
                data-preview-max="2" data-preview-step="0.1">

                <div class="modal-preview-toolbar">
                    <span class="modal-helper-text">
                        Use the controls to adjust the report preview size.
                    </span>

                    <div class="modal-preview-toolbar-actions">
                        <button type="button" class="ui-action-btn ui-action-neutral" data-preview-zoom-out
                            data-tooltip="Zoom out" aria-label="Zoom out">

                            <i class="fa-solid fa-minus"></i>
                        </button>

                        <span class="modal-preview-zoom-value" data-preview-zoom-value>
                            100%
                        </span>

                        <button type="button" class="ui-action-btn ui-action-neutral" data-preview-zoom-in
                            data-tooltip="Zoom in" aria-label="Zoom in">

                            <i class="fa-solid fa-plus"></i>
                        </button>

                        <button type="button" class="ui-action-btn ui-action-reset" data-preview-zoom-reset
                            data-tooltip="Reset zoom" aria-label="Reset zoom">

                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-preview-stage" data-preview-stage>

                    <div class="modal-preview-canvas" data-preview-canvas>

                        <div class="air-modal-preview-pages" data-preview-content>

                            <div class="air-modal-print-sheet" data-modal-preview-page="1">
                                <div class="air-modal-print-top-rule"></div>

                                <div class="air-modal-print-hero">
                                    <h1>PUP Taguig Dental Management System</h1>
                                    <p>Administrative AI Generated Overall Report</p>
                                    <span class="air-advisory-badge">AI-Generated &bull; Advisory Only</span>
                                </div>

                                <div class="air-advisory-notice">
                                    <p class="air-advisory-note">This report contains AI-generated summaries and interpretations intended to assist administrative review. Please verify all figures and findings against the underlying clinic records before making operational or administrative decisions.</p>
                                    @if (($aiReport['generation_source'] ?? null) !== 'ai')
                                        <p class="air-analysis-unavailable" role="status">AI-generated analysis is currently unavailable. Please refer to the verified clinic statistics shown in this report.</p>
                                    @endif
                                </div>

                                <div class="air-modal-print-meta-grid">
                                    <div class="air-modal-print-meta-card">
                                        <span>Report period</span>
                                        <strong>{{ $aiReport['period'] }}</strong>
                                    </div>
                                    <div class="air-modal-print-meta-card">
                                        <span>Generated at</span>
                                        <strong>{{ $aiReport['generated_at'] }}</strong>
                                    </div>
                                    <div class="air-modal-print-meta-card">
                                        <span>Operational risk level</span>
                                        <strong>{{ $riskLevel }}</strong>
                                    </div>
                                </div>

                                <section class="air-modal-print-section">
                                    <div class="air-modal-print-section-title">
                                        <span>01</span>
                                        <h2>Executive summary</h2>
                                    </div>
                                    <p class="air-modal-print-body-text">{{ $aiReport['executive_summary'] }}</p>
                                </section>

                                <section class="air-modal-print-section">
                                    <div class="air-modal-print-section-title">
                                        <span>02</span>
                                        <h2>Key findings</h2>
                                    </div>

                                    <div class="air-modal-print-kpi-grid">
                                        @foreach ($printMetricCards as $metric)
                                            <div class="air-modal-print-kpi-card {{ $metric['class'] }}">
                                                <strong>{{ $metric['value'] }}</strong>
                                                <span>{{ $metric['label'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <ul class="air-modal-print-dot-list">
                                        @foreach ($aiReport['key_findings'] as $finding)
                                            <li>{{ $finding }}</li>
                                        @endforeach
                                    </ul>
                                </section>

                                <div class="air-modal-print-footer">
                                    <div>
                                        <strong>This document contains personal-identifiable information subject to Data
                                            Privacy.</strong>
                                        <span>Please keep this document protected and in a safe place.</span>
                                    </div>
                                    <em>This is system-generated, signature is not required.</em>
                                </div>
                            </div>

                            <div class="air-modal-print-sheet" data-modal-preview-page="2">
                                <div class="air-modal-print-page-heading">
                                    <strong>AI Generated Overall Report</strong>
                                    <span> · {{ $aiReport['period'] }} · Findings and analysis</span>
                                </div>

                                <section class="air-modal-print-section">
                                    <div class="air-modal-print-section-title">
                                        <span>03</span>
                                        <h2>Treatment analysis</h2>
                                    </div>

                                    <table class="air-modal-print-table">
                                        <tbody>
                                            <tr>
                                                <th>Metric</th>
                                                <th>Value</th>
                                            </tr>
                                            <tr>
                                                <td>Treatments recorded</td>
                                                <td>{{ $treatmentsRecorded }}</td>
                                            </tr>
                                            @foreach ($treatmentBreakdown as $case)
                                                <tr>
                                                    <td>{{ data_get($case, 'name', 'Other') }}</td>
                                                    <td>{{ data_get($case, 'count', 0) }} /
                                                        {{ rtrim(rtrim(number_format((float) data_get($case, 'pct', 0), 1), '0'), '.') }}%</td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td>Period covered</td>
                                                <td>{{ $aiReport['period'] }}</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    @if (!empty($aiReport['treatment_analysis']))
                                        <p class="air-modal-print-body-text">
                                            {{ implode(' ', $aiReport['treatment_analysis']) }}
                                        </p>
                                    @endif
                                </section>

                                <section class="air-modal-print-section">
                                    <div class="air-modal-print-section-title">
                                        <span>04</span>
                                        <h2>Inventory analysis</h2>
                                    </div>

                                    <table class="air-modal-print-table">
                                        <tbody>
                                            <tr>
                                                <th>Inventory metric</th>
                                                <th>Count</th>
                                            </tr>
                                            <tr>
                                                <td>Low-stock items</td>
                                                <td>{{ $lowStockCount }}</td>
                                            </tr>
                                            <tr>
                                                <td>Critical-stock items</td>
                                                <td>{{ $criticalStockCount }}</td>
                                            </tr>
                                            <tr>
                                                <td>Items under monitoring</td>
                                                <td>{{ (int) $lowStockCount + (int) $criticalStockCount }}</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                @if (!empty($aiReport['inventory_analysis']))
                                    <p class="air-modal-print-body-text">
                                        {{ implode(' ', $aiReport['inventory_analysis']) }}
                                    </p>
                                @endif
                                </section>

                                <div class="air-modal-print-footer">
                                    <div>
                                        <strong>This document contains personal-identifiable information subject to Data
                                            Privacy.</strong>
                                        <span>Please keep this document protected and in a safe place.</span>
                                    </div>
                                    <em>This is system-generated, signature is not required.</em>
                                </div>
                            </div>

                            <div class="air-modal-print-sheet" data-modal-preview-page="3">
                                <div class="air-modal-print-page-heading">
                                    <strong>AI Generated Overall Report</strong>
                                    <span> · {{ $aiReport['period'] }} · {{ $hasDocumentRequestAnalysis ? 'Document requests and risk' : 'Risk and recommendations' }}</span>
                                </div>

                                @if ($hasDocumentRequestAnalysis)
                                    <section class="air-modal-print-section">
                                        <div class="air-modal-print-section-title">
                                            <span>05</span>
                                            <h2>Document request analysis</h2>
                                        </div>

                                        <table class="air-modal-print-table">
                                            <tbody>
                                                <tr>
                                                    <th>Document request metric</th>
                                                    <th>Value</th>
                                                </tr>
                                                <tr>
                                                    <td>Total requests</td>
                                                    <td>{{ $docTotal }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Pending requests</td>
                                                    <td>{{ $docPending }} / {{ $docPendingRateLabel }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Approved requests</td>
                                                    <td>{{ $docApproved }} / {{ $docApprovalRateLabel }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Rejected requests</td>
                                                    <td>{{ $docRejected }} / {{ $docRejectionRateLabel }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Most requested document</td>
                                                    <td>{{ $docMostRequested }} — {{ $docMostRequestedCount }} request/s
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <p class="air-modal-print-body-text">{{ implode(' ', $documentRequestAnalysis) }}
                                        </p>
                                    </section>

                                    <section class="air-modal-print-section">
                                        <div class="air-modal-print-section-title">
                                            <span>06</span>
                                            <h2>Risk interpretation</h2>
                                        </div>

                                        <div class="air-modal-print-risk-box">
                                            <strong>{{ $riskLevel }} risk</strong>
                                            <p>{{ $riskExplanation }}</p>
                                        </div>
                                    </section>
                                @else
                                    <section class="air-modal-print-section">
                                        <div class="air-modal-print-section-title">
                                            <span>06</span>
                                            <h2>Risk interpretation</h2>
                                        </div>

                                        <div class="air-modal-print-risk-box">
                                            <strong>{{ $riskLevel }} risk</strong>
                                            <p>{{ $riskExplanation }}</p>
                                        </div>
                                    </section>

                                    <section class="air-modal-print-section">
                                        <div class="air-modal-print-section-title">
                                            <span>07</span>
                                            <h2>Recommendations</h2>
                                        </div>

                                        <div class="air-modal-print-rec-list">
                                            @foreach ($aiReport['recommendations'] as $index => $recommendation)
                                                <div class="air-modal-print-rec-card">
                                                    <strong>{{ $recommendationTitles[$index] ?? 'Recommendation' }}</strong>
                                                    <p>{{ $recommendation }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endif

                                <div class="air-modal-print-footer">
                                    <div>
                                        <strong>This document contains personal-identifiable information subject to Data
                                            Privacy.</strong>
                                        <span>Please keep this document protected and in a safe place.</span>
                                    </div>
                                    <em>This is system-generated, signature is not required.</em>
                                </div>
                            </div>

                            @if ($hasDocumentRequestAnalysis)
                                <div class="air-modal-print-sheet" data-modal-preview-page="4">
                                    <div class="air-modal-print-page-heading">
                                        <strong>AI Generated Overall Report</strong>
                                        <span> · {{ $aiReport['period'] }} · Recommendations</span>
                                    </div>

                                    <section class="air-modal-print-section">
                                        <div class="air-modal-print-section-title">
                                            <span>07</span>
                                            <h2>Recommendations</h2>
                                        </div>

                                        <div class="air-modal-print-rec-list">
                                            @foreach ($aiReport['recommendations'] as $index => $recommendation)
                                                <div class="air-modal-print-rec-card">
                                                    <strong>{{ $recommendationTitles[$index] ?? 'Recommendation' }}</strong>
                                                    <p>{{ $recommendation }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>

                                    <div class="air-modal-print-footer">
                                        <div>
                                            <strong>This document contains personal-identifiable information subject to
                                                Data
                                                Privacy.</strong>
                                            <span>Please keep this document protected and in a safe place.</span>
                                        </div>
                                        <em>This is system-generated, signature is not required.</em>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <form id="printReportForm" class="air-modal-panel modal-card-form" data-global-validation novalidate>

                <div class="modal-hd">
                    <div class="modal-heading">
                        <span class="modal-icon">
                            <i class="fa-solid fa-file-pdf"></i>
                        </span>

                        <div class="modal-copy">
                            <h2 id="printReportTitle" class="modal-title">
                                Save report as PDF
                            </h2>

                            <p class="modal-subtitle">
                                Preview the report before downloading it as PDF.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="modal-x" data-close-print-modal aria-label="Close print modal">

                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-bd air-modal-options">
                    <div class="modal-form-grid">

                        <div class="global-confirm-alert">
                            <i class="fa-regular fa-file-pdf"></i>

                            <div>
                                <p>PDF download</p>

                                <span>
                                    The generated report will be downloaded
                                    directly to your device.
                                </span>
                            </div>
                        </div>

                        <div class="modal-field">
                            <label class="global-form-label" for="printPages">
                                Pages
                            </label>

                            <select id="printPages" name="print_pages" class="js-custom-select"
                                data-placeholder="Select pages">

                                <option value="all" selected>
                                    All pages
                                </option>

                                <option value="custom">
                                    Custom pages
                                </option>
                            </select>
                        </div>

                        <div id="customPageRangeWrap" class="modal-field" data-global-field hidden>

                            <label class="global-form-label" for="customPageRange">
                                Custom page range
                            </label>

                            <input type="text" id="customPageRange" name="custom_page_range"
                                class="form-input-custom" placeholder="Example: 1, 2-3"
                                data-field-label="Custom Page Range" data-error-target="customPageRangeError">

                            <p class="modal-helper-text">
                                <i class="fa-solid fa-circle-info"></i>
                                Available report pages: 1 to {{ $totalReportPages }}
                            </p>

                            <div id="customPageRangeError" class="global-field-error" data-error-for="customPageRange"
                                aria-live="polite" aria-hidden="true">
                            </div>
                        </div>

                        <div class="modal-field">
                            <label class="global-form-label" for="printLayout">
                                Layout
                            </label>

                            <select id="printLayout" name="print_layout" class="js-custom-select"
                                data-placeholder="Select layout">

                                <option value="portrait" selected>
                                    Portrait
                                </option>

                                <option value="landscape">
                                    Landscape
                                </option>
                            </select>
                        </div>

                        <div class="global-confirm-alert">
                            <i class="fa-solid fa-circle-info"></i>

                            <div>
                                <p>Before downloading</p>

                                <span>
                                    Confirm the selected pages and layout,
                                    then click Save as PDF.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-ft">
                    <button type="button" class="ui-btn ui-btn-secondary" data-close-print-modal>

                        Cancel
                    </button>

                    <button type="submit" id="confirmPrintReport" class="ui-btn ui-btn-primary" aria-busy="false">

                        <i class="fa-solid fa-file-arrow-down" data-print-button-icon>
                        </i>

                        <span data-print-button-label>
                            Save as PDF
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/reports/pdf-tools.js')

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('printReportModal');
            const openButton = document.getElementById('openPrintReportModal');
            const confirmButton = document.getElementById('confirmPrintReport');
            const closeButtons = document.querySelectorAll('[data-close-print-modal]');

            const printPages = document.getElementById('printPages');
            const printLayout = document.getElementById('printLayout');

            const printForm =
                document.getElementById(
                    'printReportForm'
                );

            const confirmButtonIcon =
                confirmButton?.querySelector(
                    '[data-print-button-icon]'
                );

            const confirmButtonLabel =
                confirmButton?.querySelector(
                    '[data-print-button-label]'
                );

            const customPageRangeWrap = document.getElementById('customPageRangeWrap');
            const customPageRange = document.getElementById('customPageRange');
            const customPageRangeError = document.getElementById('customPageRangeError');

            function getPreviewPages() {
                return Array.from(document.querySelectorAll('.air-modal-preview-pages .air-modal-print-sheet'));
            }

            function parsePageRange(value, maxPage) {
                const selected = new Set();
                const clean = String(value || '').replace(/\s+/g, '');

                if (!clean) return null;

                for (const part of clean.split(',')) {
                    if (!part) return null;

                    if (part.includes('-')) {
                        const [startValue, endValue] = part.split('-');
                        const start = Number(startValue);
                        const end = Number(endValue);

                        if (!Number.isInteger(start) || !Number.isInteger(end)) return null;
                        if (start < 1 || end < 1 || start > end || end > maxPage) return null;

                        for (let page = start; page <= end; page++) {
                            selected.add(page);
                        }
                    } else {
                        const page = Number(part);

                        if (!Number.isInteger(page) || page < 1 || page > maxPage) return null;

                        selected.add(page);
                    }
                }

                return selected;
            }

            function getSelectedPreviewPages() {
                const pages = getPreviewPages();

                if (printPages?.value !== 'custom') {
                    return pages;
                }

                const selected = parsePageRange(customPageRange?.value, pages.length);

                if (!selected || selected.size === 0) {
                    window.showFormInputValidationMessage?.(
                        customPageRange,
                        `Please enter a valid page range from 1 to ${pages.length}.`
                    );

                    window.focusGlobalInvalidField?.(
                        customPageRange
                    );

                    return null;
                }

                window.showFormInputValidationMessage?.(
                    customPageRange,
                    ''
                );

                return pages.filter((page, index) => selected.has(index + 1));
            }

            function updatePreviewSettings() {
                const layout = printLayout?.value || 'portrait';
                const pages = printPages?.value || 'all';

                if (customPageRangeWrap) {
                    customPageRangeWrap.hidden = pages !== 'custom';
                }

                if (pages !== 'custom' && customPageRangeError) {
                    customPageRangeError.hidden = true;
                }

                const modalBox = modal?.querySelector('.air-modal');

                if (modalBox) {
                    modalBox.classList.toggle('is-preview-landscape', layout === 'landscape');
                }

                requestAnimationFrame(() => {
                    window.refreshGlobalPreviewZoom?.(
                        modal
                    );
                });
            }

            async function savePreviewAsPdf() {
                try {
                    await window.loadReportPdfTools?.();
                } catch (error) {
                    window.showToast?.({ type: 'error', title: 'PDF export unavailable', message: 'Please retry loading the PDF tools.' });
                    return;
                }
                const canvasRenderer = window.html2canvas || window.html2canvasPro;

                if (!canvasRenderer || !window.jspdf) {
                    window.showToast?.({
                        type: 'warning',
                        title: 'PDF tool is loading',
                        message: 'Please wait a moment and try again.'
                    });
                    return;
                }

                const selectedPages = getSelectedPreviewPages();

                if (!selectedPages || selectedPages.length === 0) {
                    return;
                }

                const previousPreviewZoom =
                    window.getGlobalPreviewZoom?.(
                        modal
                    ) || 1;

                window.setGlobalPreviewZoom?.(
                    modal,
                    1
                );

                await new Promise(resolve => {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(
                            resolve
                        );
                    });
                });

                const layout = printLayout?.value || 'portrait';
                const isLandscape = layout === 'landscape';

                const pdfWidthPt = isLandscape ? 792 : 612;
                const pdfHeightPt = isLandscape ? 612 : 792;

                confirmButton.disabled = true;

                confirmButton.setAttribute(
                    'aria-busy',
                    'true'
                );

                if (confirmButtonIcon) {
                    confirmButtonIcon.className =
                        'fa-solid fa-spinner fa-spin';
                }

                if (confirmButtonLabel) {
                    confirmButtonLabel.textContent =
                        'Saving...';
                }

                const {
                    jsPDF
                } = window.jspdf;

                const pdf = new jsPDF({
                    orientation: layout,
                    unit: 'pt',
                    format: 'letter',
                    compress: true
                });

                try {
                    if (document.fonts && document.fonts.ready) {
                        await document.fonts.ready;
                    }

                    for (let index = 0; index < selectedPages.length; index++) {
                        const page = selectedPages[index];

                        page.scrollIntoView({
                            block: 'center',
                            inline: 'center'
                        });

                        await new Promise(resolve => setTimeout(resolve, 350));

                        const canvas = await canvasRenderer(page, {
                            scale: 3,
                            useCORS: true,
                            allowTaint: true,
                            backgroundColor: '#ffffff',
                            scrollX: 0,
                            scrollY: 0,
                            logging: true,
                            removeContainer: true,
                            onclone: function(clonedDocument) {
                                const style = clonedDocument.createElement('style');
                                style.nonce = document.querySelector('meta[name="csp-nonce"]')?.content || '';

                                style.textContent = `
                        html,
                        body {
                            background: #ffffff !important;
                        }

                        .air-modal-print-sheet {
                            box-shadow: none !important;
                            transform: none !important;
                            border-radius: 0 !important;
                            background: #ffffff !important;
                            overflow: hidden !important;
                        }

                        .air-modal-print-sheet *,
                        .air-modal-print-sheet {
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                            color-adjust: exact !important;
                        }
                    `;

                                clonedDocument.head.appendChild(style);
                            }
                        });

                        const imageData = canvas.toDataURL('image/jpeg', 1.0);

                        if (index > 0) {
                            pdf.addPage('letter', layout);
                        }

                        pdf.addImage(imageData, 'JPEG', 0, 0, pdfWidthPt, pdfHeightPt);
                    }

                    const fileDate = new Date().toISOString().slice(0, 10);
                    pdf.save(`AI-Generated-Report-${fileDate}.pdf`);

                    window.showToast?.({
                        type: 'success',
                        title: 'Report downloaded',
                        message: 'The AI-generated report was saved as a PDF.'
                    });

                    closePrintModal();

                } catch (error) {
                    console.error('PDF save error:', error);

                    window.showToast?.({
                        type: 'error',
                        title: 'PDF download failed',
                        message: error?.message ||
                            'Unable to save the report as PDF.'
                    });
                } finally {
                    window.setGlobalPreviewZoom?.(
                        modal,
                        previousPreviewZoom
                    );

                    confirmButton.disabled = false;

                    confirmButton.setAttribute(
                        'aria-busy',
                        'false'
                    );

                    if (confirmButtonIcon) {
                        confirmButtonIcon.className =
                            'fa-solid fa-file-arrow-down';
                    }

                    if (confirmButtonLabel) {
                        confirmButtonLabel.textContent =
                            'Save as PDF';
                    }
                }
            }

            function openPrintModal() {
                if (!modal) return;

                window.initCustomSelects?.(modal);

                modal
                    .querySelectorAll(
                        '.custom-select'
                    )
                    .forEach(wrapper => {
                        window.syncCustomSelect?.(
                            wrapper
                        );
                    });

                window.openModal?.(
                    'printReportModal'
                );

                requestAnimationFrame(() => {
                    updatePreviewSettings();

                    window.resetGlobalPreviewZoom?.(
                        modal
                    );
                });
            }

            function closePrintModal() {
                window.closeModal?.('printReportModal');
            }

            customPageRange?.addEventListener(
                'input',
                () => {
                    window.showFormInputValidationMessage?.(
                        customPageRange,
                        ''
                    );
                }
            );

            openButton?.addEventListener(
                'click',
                openPrintModal
            );

            closeButtons.forEach(button => {
                button.addEventListener(
                    'click',
                    closePrintModal
                );
            });

            printForm?.addEventListener(
                'submit',
                async event => {
                    event.preventDefault();

                    updatePreviewSettings();

                    await savePreviewAsPdf();
                }
            );

            printPages?.addEventListener(
                'change',
                updatePreviewSettings
            );

            printLayout?.addEventListener(
                'change',
                updatePreviewSettings
            );

            updatePreviewSettings();
        });
    </script>
@endpush
