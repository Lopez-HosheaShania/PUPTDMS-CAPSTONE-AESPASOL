@extends('layouts.admin')

@section('title', 'AI Generated Report')

@section('content')
    <main id="mainContent" class="admin-page-shell page-enter mode-list">
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
                                <h1 class="air-banner-heading">AI generated overall report</h1>
                                <p class="air-banner-sub">{{ $aiReport['period'] }} · Monthly analysis</p>
                            </div>
                        </div>

                        <div class="air-banner-actions">
                            <a href="{{ route('admin.reports') }}" class="air-banner-btn-ghost">
                                <i class="fa-solid fa-arrow-left"></i> Back to reports
                            </a>
                            <button type="button" id="openPrintReportModal" class="air-banner-btn-white">
                                <i class="fa-solid fa-file-arrow-down"></i> Save as PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <section id="aiReportScreenArea" class="air-screen">

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
                            <i class="fa-solid fa-wand-magic-sparkles"></i> AI-generated insight
                        </span>
                    </div>
                    @php
                        $riskClass = match ($aiReport['risk_level']) {
                            'High' => 'air-status-card--red',
                            'Moderate' => 'air-status-card--amber',
                            default => 'air-status-card--green',
                        };
                        $badgeClass = match ($aiReport['risk_level']) {
                            'High' => 'air-badge--red',
                            'Moderate' => 'air-badge--amber',
                            default => 'air-badge--green',
                        };
                    @endphp
                    <div class="air-status-card {{ $riskClass }}">
                        <span class="air-status-label">Operational risk</span>
                        <span class="air-status-value air-status-value--inline">
                            {{ $aiReport['risk_level'] }}
                            <span class="air-badge {{ $badgeClass }}">{{ $aiReport['risk_level'] }}</span>
                        </span>
                        <span class="air-status-sub">
                            <i class="fa-solid fa-shield-halved"></i> System assessment
                        </span>
                    </div>
                </div>

                <div class="air-card">
                    <div class="air-card-head">
                        <div class="air-card-icon air-card-icon--accent">
                            <i class="fa-solid fa-brain"></i>
                        </div>
                        <div>
                            <h2 class="air-card-title">Executive summary</h2>
                            <p class="air-card-sub">Automatically generated overview based on system statistics</p>
                        </div>
                    </div>
                    <div class="air-card-body">
                        <p class="air-body-text">{{ $aiReport['executive_summary'] }}</p>
                    </div>
                </div>

                <div class="air-card">
                    <div class="air-card-head">
                        <div class="air-card-icon air-card-icon--accent">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <h2 class="air-card-title">Key findings</h2>
                            <p class="air-card-sub">Important observations from the report data</p>
                        </div>
                    </div>
                    <div class="air-card-body">
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
                    <div class="air-card air-card--flush">
                        <div class="air-card-head">
                            <div class="air-card-icon air-card-icon--accent">
                                <i class="fa-solid fa-tooth"></i>
                            </div>
                            <div>
                                <h2 class="air-card-title">Treatment analysis</h2>
                                <p class="air-card-sub">Treatment statistics interpretation</p>
                            </div>
                        </div>
                        <div class="air-card-body">
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

                    <div class="air-card air-card--flush">
                        <div class="air-card-head">
                            <div class="air-card-icon air-card-icon--accent">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                            <div>
                                <h2 class="air-card-title">Inventory analysis</h2>
                                <p class="air-card-sub">Inventory condition interpretation</p>
                            </div>
                        </div>
                        <div class="air-card-body">
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
                    <div class="air-card">
                        <div class="air-card-head">
                            <div class="air-card-icon air-card-icon--accent">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div>
                                <h2 class="air-card-title">Document request analysis</h2>
                                <p class="air-card-sub">Document request status and processing interpretation</p>
                            </div>
                        </div>
                        <div class="air-card-body">
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

                <div class="air-card">
                    <div class="air-card-head">
                        <div class="air-card-icon air-card-icon--accent">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h2 class="air-card-title">Risk interpretation</h2>
                            <p class="air-card-sub">System-generated risk classification</p>
                        </div>
                    </div>
                    <div class="air-card-body">
                        <div class="air-risk-callout">
                            <div class="air-risk-icon">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="air-risk-text">
                                <strong>{{ $aiReport['risk_level'] }} risk</strong>
                                <p>{{ $aiReport['risk_explanation'] }}</p>
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
                <div class="air-card air-card--last">
                    <div class="air-card-head">
                        <div class="air-card-icon air-card-icon--accent">
                            <i class="fa-solid fa-lightbulb"></i>
                        </div>
                        <div>
                            <h2 class="air-card-title">Recommendations</h2>
                            <p class="air-card-sub">Suggested actions based on the analysis</p>
                        </div>
                    </div>
                    <div class="air-card-body">
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
        $printFindings = $aiReport['key_findings'] ?? [];
        $printFindingsText = trim(implode(' ', array_filter($printFindings)));
        $printTreatmentText = trim(implode(' ', array_filter($aiReport['treatment_analysis'] ?? [])));
        $printInventoryText = trim(implode(' ', array_filter($aiReport['inventory_analysis'] ?? [])));

        $extractNumber = function ($patterns, $default = '—') use (
            $printFindingsText,
            $printTreatmentText,
            $printInventoryText,
        ) {
            $haystack = trim($printFindingsText . ' ' . $printTreatmentText . ' ' . $printInventoryText);
            foreach ((array) $patterns as $pattern) {
                if (preg_match($pattern, $haystack, $matches)) {
                    return $matches[1];
                }
            }
            return $default;
        };

        $totalPatients =
            data_get($aiReport, 'print_metrics.total_patients') ??
            (data_get($aiReport, 'metrics.total_patients') ??
                $extractNumber(['/Total patients:\s*(\d+)/i', '/(\d+)\s+total\s+patients?/i']));
        $newPatients =
            data_get($aiReport, 'print_metrics.new_patients') ??
            (data_get($aiReport, 'metrics.new_patients') ??
                $extractNumber(['/new patients?:\s*(\d+)/i', '/(\d+)\s+new\s+patients?/i', '/with\s+(\d+)\s+new/i']));
        $totalAppointments =
            data_get($aiReport, 'print_metrics.total_appointments') ??
            (data_get($aiReport, 'metrics.total_appointments') ??
                $extractNumber([
                    '/appointments?\s+(?:totaled|totalled)\s+(\d+)/i',
                    '/(\d+)\s+total\s+appointments?/i',
                    '/activity\s+included\s+(\d+)\s+total\s+appointments?/i',
                ]));
        $cancelledAppointments =
            data_get($aiReport, 'print_metrics.cancelled_appointments') ??
            (data_get($aiReport, 'metrics.cancelled_appointments') ??
                $extractNumber(['/(\d+)\s+cancellations?/i', '/(\d+)\s+cancelled/i']));
        $completionRate =
            data_get($aiReport, 'print_metrics.completion_rate') ??
            (data_get($aiReport, 'metrics.completion_rate') ??
                $extractNumber(
                    ['/completion\s+rate\s+(?:was\s+)?(\d+(?:\.\d+)?)%/i', '/(\d+(?:\.\d+)?)%\s+completion\s+rate/i'],
                    '0',
                ));
        $cancellationRate =
            data_get($aiReport, 'print_metrics.cancellation_rate') ??
            (data_get($aiReport, 'metrics.cancellation_rate') ??
                $extractNumber(
                    [
                        '/cancellation\s+rate\s+(?:was\s+)?(\d+(?:\.\d+)?)%/i',
                        '/(\d+(?:\.\d+)?)%\s+cancellation\s+rate/i',
                    ],
                    '0',
                ));
        $treatmentsRecorded =
            data_get($aiReport, 'print_metrics.treatments_recorded') ??
            (data_get($aiReport, 'metrics.treatments_recorded') ??
                $extractNumber(
                    [
                        '/Total\s+treatments?\s+recorded\s+(?:were\s+)?(\d+)/i',
                        '/(\d+)\s+treatments?\s+(?:were\s+)?(?:recorded|performed)/i',
                    ],
                    str_contains(strtolower($printTreatmentText . ' ' . $printFindingsText), 'no treatments')
                        ? '0'
                        : '—',
                ));
        $dominantTreatment =
            data_get($aiReport, 'print_metrics.dominant_treatment') ??
            (data_get($aiReport, 'metrics.dominant_treatment') ??
                (data_get($aiReport, 'top_treatment') ?? 'None identified'));
        $lowStockCount =
            data_get($aiReport, 'print_metrics.low_stock_count') ??
            (data_get($aiReport, 'metrics.low_stock_count') ??
                $extractNumber(
                    [
                        '/Inventory\s+showed\s+(\d+)\s+low-stock/i',
                        '/(\d+)\s+low-stock\s+items?/i',
                        '/(\d+)\s+item\/s\s+below/i',
                    ],
                    '0',
                ));
        $criticalStockCount =
            data_get($aiReport, 'print_metrics.critical_stock_count') ??
            (data_get($aiReport, 'metrics.critical_stock_count') ??
                $extractNumber(
                    [
                        '/and\s+(\d+)\s+critical-stock/i',
                        '/(\d+)\s+critical-stock\s+items?/i',
                        '/(\d+)\s+item\/s\s+are\s+considered\s+critical/i',
                    ],
                    '0',
                ));

        $documentRequestAnalysis = $aiReport['document_request_analysis'] ?? [];

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
            <div class="air-print-fixed-header">
                <strong>AI Generated Overall Report</strong>
                <span>· {{ $aiReport['period'] }} · Summary and metrics</span>
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
                    <strong>{{ $aiReport['risk_level'] }}</strong>
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
                            <tr>
                                <td>Dominant treatment category</td>
                                <td>{{ $dominantTreatment }}</td>
                            </tr>
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
                @if (!empty($documentRequestAnalysis))
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
                @endif

                <div class="air-print-footer">
                    <div class="air-print-footer-left">
                        <strong>This document contains personal-identifiable information subject to Data Privacy.</strong>
                        <span>Please keep this document protected and in a safe place.</span>
                    </div>
                    <span class="air-print-footer-right">This is system-generated, signature is not required.</span>
                </div>
            </div>

            <div class="air-print-page" data-print-page="3">
                <div class="air-print-fixed-header">
                    <strong>AI Generated Overall Report</strong>
                    <span>· {{ $aiReport['period'] }} · Risk and recommendations</span>
                </div>

                <section class="air-print-section">
                    <div class="air-print-section-title"><span>06</span>
                        <h2>Risk interpretation</h2>
                    </div>
                    <div class="air-print-risk-box">
                        <strong>{{ $aiReport['risk_level'] }} risk</strong>
                        <p>{{ $aiReport['risk_explanation'] }}</p>
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

                <div class="air-print-fixed-footer">
                    <div class="air-print-footer-left">
                        <strong>This document contains personal-identifiable information subject to Data Privacy.</strong>
                        <span>Please keep this document protected and in a safe place.</span>
                    </div>

                    <div class="air-print-footer-right">
                        This is system-generated, signature is not required.
                    </div>
                </div>

    </section>

    <div id="printReportModal" class="air-modal-overlay" aria-hidden="true">
        <div class="air-modal" role="dialog" aria-modal="true" aria-labelledby="printReportTitle">

            <div class="air-modal-preview">
                <div class="air-modal-preview-pages">

                    <div class="air-modal-print-sheet" data-modal-preview-page="1">
                        <div class="air-modal-print-top-rule"></div>

                        <div class="air-modal-print-hero">
                            <h1>PUP Taguig Dental Management System</h1>
                            <p>Administrative AI Generated Overall Report</p>
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
                                <strong>{{ $aiReport['risk_level'] }}</strong>
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

                        @if (!empty($documentRequestAnalysis))
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
                                            <td>{{ $docMostRequested }} — {{ $docMostRequestedCount }} request/s</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <p class="air-modal-print-body-text">{{ implode(' ', $documentRequestAnalysis) }}</p>
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
                                    <tr>
                                        <td>Dominant treatment category</td>
                                        <td>{{ $dominantTreatment }}</td>
                                    </tr>
                                    <tr>
                                        <td>Period covered</td>
                                        <td>{{ $aiReport['period'] }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            @if (!empty($aiReport['treatment_analysis']))
                                <p class="air-modal-print-body-text">{{ implode(' ', $aiReport['treatment_analysis']) }}
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
                                <p class="air-modal-print-body-text">{{ implode(' ', $aiReport['inventory_analysis']) }}
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
                            <span> · {{ $aiReport['period'] }} · Risk and recommendations</span>
                        </div>

                        <section class="air-modal-print-section">
                            <div class="air-modal-print-section-title">
                                <span>06</span>
                                <h2>Risk interpretation</h2>
                            </div>

                            <div class="air-modal-print-risk-box">
                                <strong>{{ $aiReport['risk_level'] }} risk</strong>
                                <p>{{ $aiReport['risk_explanation'] }}</p>
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

                        <div class="air-modal-print-footer">
                            <div>
                                <strong>This document contains personal-identifiable information subject to Data
                                    Privacy.</strong>
                                <span>Please keep this document protected and in a safe place.</span>
                            </div>
                            <em>This is system-generated, signature is not required.</em>
                        </div>
                    </div>

                </div>
            </div>

            <div class="air-modal-panel">
                <div class="air-modal-header">
                    <div>
                        <h2 id="printReportTitle">Save report as PDF</h2>
                        <p>Preview the report before downloading it as PDF.</p>
                    </div>
                    <button type="button" class="air-modal-close" data-close-print-modal aria-label="Close print modal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="air-modal-options">
                    <input type="hidden" id="printPages" value="all">
                    <input type="hidden" id="printLayout" value="portrait">

                    <div class="air-option-row">
                        <label>Destination</label>
                        <div class="air-option-static">
                            <div class="air-option-icon"><i class="fa-regular fa-file-pdf"></i></div>
                            <div>
                                <strong>PDF download</strong>
                                <span>The report will be downloaded directly as a PDF file.</span>
                            </div>
                        </div>
                    </div>

                    <div class="air-option-row">
                        <label>Pages</label>
                        <div class="air-dropdown" data-print-dropdown>
                            <button type="button" class="air-dropdown-toggle" data-print-dropdown-toggle>
                                <span class="air-dropdown-left">
                                    <i class="fa-regular fa-file-lines"></i>
                                    <span data-print-dropdown-label>All pages</span>
                                </span>
                                <i class="fa-solid fa-chevron-down air-dropdown-chevron"></i>
                            </button>
                            <div class="air-dropdown-menu" data-print-dropdown-menu>
                                <button type="button" class="air-dropdown-item is-selected" data-target="printPages"
                                    data-value="all" data-label="All pages">
                                    <span><strong>All pages</strong><small>Print the complete generated
                                            report</small></span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button" class="air-dropdown-item" data-target="printPages"
                                    data-value="custom" data-label="Custom pages">
                                    <span><strong>Custom pages</strong><small>Print selected report pages
                                            only</small></span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <div id="customPageRangeWrap" class="air-custom-range" hidden>
                            <label for="customPageRange">Custom page range</label>
                            <div class="air-custom-range-input">
                                <i class="fa-regular fa-file-lines"></i>
                                <input type="text" id="customPageRange" placeholder="Example: 1, 2-3">
                            </div>
                            <small>Available report pages: 1 to 3</small>
                            <div id="customPageRangeError" class="air-custom-range-error" hidden>
                                Please enter a valid page range from 1 to 3.
                            </div>
                        </div>
                    </div>

                    <div class="air-option-row">
                        <label>Layout</label>
                        <div class="air-dropdown" data-print-dropdown>
                            <button type="button" class="air-dropdown-toggle" data-print-dropdown-toggle>
                                <span class="air-dropdown-left">
                                    <i class="fa-solid fa-table-columns"></i>
                                    <span data-print-dropdown-label>Portrait</span>
                                </span>
                                <i class="fa-solid fa-chevron-down air-dropdown-chevron"></i>
                            </button>
                            <div class="air-dropdown-menu" data-print-dropdown-menu>
                                <button type="button" class="air-dropdown-item is-selected" data-target="printLayout"
                                    data-value="portrait" data-label="Portrait">
                                    <span><strong>Portrait</strong><small>Best for standard report pages</small></span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button" class="air-dropdown-item" data-target="printLayout"
                                    data-value="landscape" data-label="Landscape">
                                    <span><strong>Landscape</strong><small>Wider layout for report cards</small></span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="air-modal-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Click <strong>Save as PDF</strong> to download the report directly.</span>
                    </div>
                </div>

                <div class="air-modal-actions">
                    <button type="button" class="air-action-btn air-action-btn--secondary" data-close-print-modal>
                        <span class="air-action-btn-icon">
                            <i class="fa-solid fa-xmark"></i>
                        </span>
                        <span>Cancel</span>
                    </button>

                    <button type="button" id="confirmPrintReport" class="air-action-btn air-action-btn--primary">
                        <span class="air-action-btn-icon">
                            <i class="fa-solid fa-file-arrow-down"></i>
                        </span>
                        <span>Save as PDF</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/html2canvas-pro@1.5.8/dist/html2canvas-pro.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('printReportModal');
            const openButton = document.getElementById('openPrintReportModal');
            const confirmButton = document.getElementById('confirmPrintReport');
            const closeButtons = document.querySelectorAll('[data-close-print-modal]');

            const printPages = document.getElementById('printPages');
            const printLayout = document.getElementById('printLayout');
            const dropdowns = document.querySelectorAll('[data-print-dropdown]');

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
                    if (customPageRangeError) customPageRangeError.hidden = false;
                    customPageRange?.focus();
                    return null;
                }

                if (customPageRangeError) customPageRangeError.hidden = true;

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
            }

            async function savePreviewAsPdf() {
                const canvasRenderer = window.html2canvas || window.html2canvasPro;

                if (!canvasRenderer || !window.jspdf) {
                    alert('PDF tool is still loading. Please try again.');
                    return;
                }

                const selectedPages = getSelectedPreviewPages();

                if (!selectedPages || selectedPages.length === 0) {
                    return;
                }

                const layout = printLayout?.value || 'portrait';
                const isLandscape = layout === 'landscape';

                const pdfWidthPt = isLandscape ? 792 : 612;
                const pdfHeightPt = isLandscape ? 612 : 792;

                const originalButtonContent = confirmButton.innerHTML;

                confirmButton.disabled = true;
                confirmButton.innerHTML = `
        <span class="air-action-btn-icon">
            <i class="fa-solid fa-spinner fa-spin"></i>
        </span>
        <span>Saving...</span>
    `;

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

                    const previewContainer = document.querySelector('.air-modal-preview');

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
                } catch (error) {
                    console.error('PDF save error:', error);

                    alert(
                        'Unable to save the report as PDF. Error: ' +
                        (error?.message || error)
                    );
                } finally {
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = originalButtonContent;
                }
            }

            function openModal() {
                if (!modal) return;

                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                if (!modal) return;

                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';

                dropdowns.forEach(dropdown => dropdown.classList.remove('is-open'));
            }

            dropdowns.forEach(function(dropdown) {
                const toggle = dropdown.querySelector('[data-print-dropdown-toggle]');
                const label = dropdown.querySelector('[data-print-dropdown-label]');
                const items = dropdown.querySelectorAll('.air-dropdown-item');

                toggle?.addEventListener('click', function(e) {
                    e.stopPropagation();

                    dropdowns.forEach(function(otherDropdown) {
                        if (otherDropdown !== dropdown) {
                            otherDropdown.classList.remove('is-open');
                        }
                    });

                    dropdown.classList.toggle('is-open');
                });

                items.forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.stopPropagation();

                        const targetInput = document.getElementById(item.dataset.target);

                        if (targetInput) {
                            targetInput.value = item.dataset.value;
                        }

                        if (label) {
                            label.textContent = item.dataset.label;
                        }

                        items.forEach(i => i.classList.remove('is-selected'));
                        item.classList.add('is-selected');
                        dropdown.classList.remove('is-open');

                        updatePreviewSettings();
                    });
                });
            });

            document.addEventListener('click', function() {
                dropdowns.forEach(dropdown => dropdown.classList.remove('is-open'));
            });

            customPageRange?.addEventListener('input', function() {
                if (customPageRangeError) {
                    customPageRangeError.hidden = true;
                }
            });

            openButton?.addEventListener('click', openModal);

            closeButtons.forEach(function(button) {
                button.addEventListener('click', closeModal);
            });

            modal?.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal?.classList.contains('show')) {
                    closeModal();
                }
            });

            confirmButton?.addEventListener('click', function(e) {
                e.preventDefault();
                updatePreviewSettings();
                savePreviewAsPdf();
            });

            updatePreviewSettings();
        });
    </script>
@endpush
