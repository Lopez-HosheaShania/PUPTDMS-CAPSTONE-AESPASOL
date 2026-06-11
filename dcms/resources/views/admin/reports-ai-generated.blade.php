@extends('layouts.admin')

@section('title', 'AI Generated Report')

@section('content')
    <main id="mainContent" class="admin-page-shell page-enter mode-list">
        <div class="w-full">

            {{-- ── Top bar ─────────────────────────────────────────────────── --}}
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
                                <i class="fa-solid fa-print"></i> Print / Save as PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Screen report area ──────────────────────────────────────── --}}
            <section id="aiReportScreenArea" class="air-screen">

                {{-- Status strip --}}
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

                {{-- Executive Summary --}}
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

                {{-- Key Findings --}}
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

                {{-- Treatment + Inventory --}}
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

                {{-- Risk Interpretation --}}
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

                {{-- Recommendations --}}
                @php
                    $recommendationTitles = [
                        'Appointment scheduling',
                        'Cancellation follow-up',
                        'Inventory oversight',
                        'Treatment tracking',
                        'Administrative planning',
                        'Operational monitoring',
                    ];
                @endphp
                <div class="air-card" style="margin-bottom:2rem;">
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

            </section>{{-- /aiReportScreenArea --}}

        </div>
    </main>

    {{-- ─────────────────────────────────────────────────────────────────────
         PRINT-ONLY DOCUMENT
    ───────────────────────────────────────────────────────────────────────── --}}
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

        {{-- PAGE 1 — Header + Executive Summary + Key Findings --}}
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

            {{-- PAGE 2 — Treatment & Inventory Analysis --}}
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

                <div class="air-print-footer">
                    <div class="air-print-footer-left">
                        <strong>This document contains personal-identifiable information subject to Data Privacy.</strong>
                        <span>Please keep this document protected and in a safe place.</span>
                    </div>
                    <span class="air-print-footer-right">This is system-generated, signature is not required.</span>
                </div>
            </div>

            {{-- PAGE 3 — Risk Interpretation & Recommendations --}}
            <div class="air-print-page" data-print-page="3">
                <div class="air-print-fixed-header">
                    <strong>AI Generated Overall Report</strong>
                    <span>· {{ $aiReport['period'] }} · Risk and recommendations</span>
                </div>

                <section class="air-print-section">
                    <div class="air-print-section-title"><span>05</span>
                        <h2>Risk interpretation</h2>
                    </div>
                    <div class="air-print-risk-box">
                        <strong>{{ $aiReport['risk_level'] }} risk</strong>
                        <p>{{ $aiReport['risk_explanation'] }}</p>
                    </div>
                </section>

                <section class="air-print-section">
                    <div class="air-print-section-title"><span>06</span>
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

    </section>{{-- /aiFullPrintDocument --}}


    {{-- ─────────────────────────────────────────────────────────────────────
         PRINT MODAL
    ───────────────────────────────────────────────────────────────────────── --}}
    <div id="printReportModal" class="air-modal-overlay" aria-hidden="true">
        <div class="air-modal" role="dialog" aria-modal="true" aria-labelledby="printReportTitle">

            {{-- Left preview pane --}}
            <div class="air-modal-preview">
                <div class="air-modal-preview-pages">

                    {{-- Preview Page 1 --}}
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

                        <div class="air-modal-print-footer">
                            <div>
                                <strong>This document contains personal-identifiable information subject to Data
                                    Privacy.</strong>
                                <span>Please keep this document protected and in a safe place.</span>
                            </div>
                            <em>This is system-generated, signature is not required.</em>
                        </div>
                    </div>

                    {{-- Preview Page 2 --}}
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

                    {{-- Preview Page 3 --}}
                    <div class="air-modal-print-sheet" data-modal-preview-page="3">
                        <div class="air-modal-print-page-heading">
                            <strong>AI Generated Overall Report</strong>
                            <span> · {{ $aiReport['period'] }} · Risk and recommendations</span>
                        </div>

                        <section class="air-modal-print-section">
                            <div class="air-modal-print-section-title">
                                <span>05</span>
                                <h2>Risk interpretation</h2>
                            </div>

                            <div class="air-modal-print-risk-box">
                                <strong>{{ $aiReport['risk_level'] }} risk</strong>
                                <p>{{ $aiReport['risk_explanation'] }}</p>
                            </div>
                        </section>

                        <section class="air-modal-print-section">
                            <div class="air-modal-print-section-title">
                                <span>06</span>
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

            {{-- Right panel --}}
            <div class="air-modal-panel">
                <div class="air-modal-header">
                    <div>
                        <h2 id="printReportTitle">Print report</h2>
                        <p>Preview and configure before printing or saving as PDF.</p>
                    </div>
                    <button type="button" class="air-modal-close" data-close-print-modal aria-label="Close print modal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="air-modal-options">
                    <input type="hidden" id="printPages" value="all">
                    <input type="hidden" id="printLayout" value="portrait">

                    {{-- Destination (static) --}}
                    <div class="air-option-row">
                        <label>Destination</label>
                        <div class="air-option-static">
                            <div class="air-option-icon"><i class="fa-regular fa-file-pdf"></i></div>
                            <div>
                                <strong>Save as PDF / Printer</strong>
                                <span>Selected inside the browser print dialog</span>
                            </div>
                        </div>
                    </div>

                    {{-- Pages --}}
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

                    {{-- Layout --}}
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
                        <span>Select <strong>Save as PDF</strong> in the browser print dialog to download the report.</span>
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
                            <i class="fa-solid fa-print"></i>
                        </span>
                        <span>Print / Save as PDF</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* ═══════════════════════════════════════════════════════════════
                                                                                                               SCREEN STYLES
                                                                                                            ═══════════════════════════════════════════════════════════════ */

        .air-banner-shell {
            background: transparent !important;
            padding: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .air-ai-banner-inner {
            display: block;
        }

        .air-banner-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .75rem;
            color: rgba(255, 255, 255, .7);
            margin-bottom: 10px;
        }

        .air-banner-breadcrumb span {
            color: rgba(255, 255, 255, .45);
        }

        .air-banner-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .75rem;
            color: rgba(255, 255, 255, .5);
            margin-bottom: 10px;
        }

        .air-banner-breadcrumb span {
            color: rgba(255, 255, 255, .3);
        }

        .air-banner-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .air-banner-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .air-banner-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .13);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .air-banner-heading {
            font-size: 1.65rem;
            font-weight: 800;
            color: #fff;
            margin: 0;
            line-height: 1.2;
        }

        .air-banner-sub {
            font-size: .82rem;
            color: rgba(255, 255, 255, .58);
            margin: 3px 0 0;
        }

        .air-banner-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        .air-banner-btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            border: 1.5px solid rgba(255, 255, 255, .3);
            background: rgba(255, 255, 255, .08);
            color: #fff;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background .18s, border-color .18s;
        }

        .air-banner-btn-ghost:hover {
            background: rgba(255, 255, 255, .16);
            border-color: rgba(255, 255, 255, .5);
        }

        .air-banner-btn-white {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            border: 0;
            background: #fff;
            color: #7a0000;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .18s;
        }

        .air-banner-btn-white:hover {
            background: #f3f4f6;
        }

        /* ── Screen wrapper ── */
        .air-screen {
            padding-top: 1.25rem;
        }

        /* ── Status strip ── */
        .air-status-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .air-status-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 5px;
            position: relative;
            overflow: hidden;
        }

        .air-status-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
        }

        .air-status-card--red::before {
            background: #8B0000;
        }

        .air-status-card--purple::before {
            background: #534AB7;
        }

        .air-status-card--amber::before {
            background: #BA7517;
        }

        .air-status-card--green::before {
            background: #3B6D11;
        }

        .air-status-label {
            font-size: .72rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .air-status-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }

        .air-status-value--sm {
            font-size: .9rem;
        }

        .air-status-value--inline {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .air-status-sub {
            font-size: .75rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Badges ── */
        .air-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
        }

        .air-badge--red {
            background: #FCEBEB;
            color: #791F1F;
        }

        .air-badge--amber {
            background: #FAEEDA;
            color: #633806;
        }

        .air-badge--green {
            background: #EAF3DE;
            color: #27500A;
        }

        /* ── Section cards ── */
        .air-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            margin-bottom: .75rem;
            overflow: hidden;
        }

        .air-card--flush {
            margin-bottom: 0;
        }

        .air-card-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .9rem 1.1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .air-card-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: .9rem;
            flex-shrink: 0;
        }

        .air-card-icon--accent {
            background: #FCEBEB;
            color: #8B0000;
        }

        .air-card-title {
            font-size: .88rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .air-card-sub {
            font-size: .75rem;
            color: #9ca3af;
            margin: 2px 0 0;
        }

        .air-card-body {
            padding: 1rem 1.1rem;
        }

        .air-body-text {
            font-size: .9rem;
            line-height: 1.75;
            color: #374151;
            margin: 0;
        }

        /* ── Two-col grid ── */
        .air-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-bottom: .75rem;
        }

        @media (max-width: 620px) {
            .air-two-col {
                grid-template-columns: 1fr;
            }
        }

        /* ── Findings list ── */
        .air-findings-list {
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .air-finding-item {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            padding: .65rem .85rem;
            border-radius: 9px;
            background: #f9fafb;
            font-size: .85rem;
            line-height: 1.55;
            color: #374151;
        }

        .air-finding-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #8B0000;
            margin-top: 5px;
            flex-shrink: 0;
        }

        /* ── Risk callout ── */
        .air-risk-callout {
            display: flex;
            gap: .85rem;
            padding: 1rem;
            border-radius: 12px;
            background: #FCEBEB;
            border: 1px solid #F7C1C1;
        }

        .air-risk-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #F7C1C1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #791F1F;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .air-risk-text strong {
            display: block;
            font-size: .88rem;
            font-weight: 700;
            color: #791F1F;
            margin-bottom: 4px;
        }

        .air-risk-text p {
            margin: 0;
            font-size: .85rem;
            line-height: 1.6;
            color: #501313;
        }

        /* ── Recommendations ── */
        .air-rec-list {
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }

        .air-rec-item {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .85rem 1rem;
            border-radius: 10px;
            border: 1px solid #f3f4f6;
            background: #fff;
        }

        .air-rec-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #FCEBEB;
            color: #791F1F;
            font-size: .72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .air-rec-body strong {
            display: block;
            font-size: .85rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
        }

        .air-rec-body p {
            margin: 0;
            font-size: .83rem;
            color: #6b7280;
            line-height: 1.5;
        }

        /* dark mode */
        [data-theme="dark"] .air-page-title {
            color: #f9fafb;
        }

        [data-theme="dark"] .air-status-card,
        [data-theme="dark"] .air-card {
            background: #000D1A;
            border-color: rgba(255, 255, 255, .08);
        }

        [data-theme="dark"] .air-status-value {
            color: #f9fafb;
        }

        [data-theme="dark"] .air-card-title {
            color: #f9fafb;
        }

        [data-theme="dark"] .air-card-head {
            border-bottom-color: rgba(255, 255, 255, .06);
        }

        [data-theme="dark"] .air-body-text,
        [data-theme="dark"] .air-finding-item {
            color: #d1d5db;
        }

        [data-theme="dark"] .air-finding-item {
            background: rgba(255, 255, 255, .04);
        }

        [data-theme="dark"] .air-card-icon--accent {
            background: rgba(139, 0, 0, .25);
        }

        [data-theme="dark"] .air-risk-callout {
            background: rgba(139, 0, 0, .18);
            border-color: rgba(139, 0, 0, .3);
        }

        [data-theme="dark"] .air-risk-icon {
            background: rgba(139, 0, 0, .3);
        }

        [data-theme="dark"] .air-risk-text strong {
            color: #fca5a5;
        }

        [data-theme="dark"] .air-risk-text p {
            color: #fecaca;
        }

        [data-theme="dark"] .air-rec-item {
            background: #000D1A;
            border-color: rgba(255, 255, 255, .06);
        }

        [data-theme="dark"] .air-rec-body strong {
            color: #f9fafb;
        }

        [data-theme="dark"] .air-rec-body p {
            color: #9ca3af;
        }

        [data-theme="dark"] .air-btn {
            background: #001f3f;
            border-color: rgba(255, 255, 255, .14);
            color: #f9fafb;
        }

        /* ═══════════════════════════════════════════════════════════════
                                                                                                               MODAL STYLES
                                                                                                            ═══════════════════════════════════════════════════════════════ */
        .air-modal-overlay {
            position: fixed !important;
            inset: 0 !important;
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: rgba(0, 13, 26, .68);
            backdrop-filter: blur(4px);
            overflow-y: auto;
        }

        .air-modal-overlay.show {
            display: flex;
        }

        .air-modal {
            width: min(1050px, calc(100vw - 4rem));
            max-height: calc(100vh - 4rem);
            margin: auto;
            display: grid;
            grid-template-columns: minmax(320px, 1.15fr) minmax(320px, .85fr);
            overflow: hidden;
            border-radius: 20px;
            background: #f8fafc;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
            animation: airModalPop .18s ease-out;
        }

        @keyframes airModalPop {
            from {
                opacity: 0;
                transform: scale(.97) translateY(8px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Preview pane */
        .air-modal-preview {
            padding: 1.5rem;
            background: #e5e7eb;
            overflow: auto;
        }

        .air-modal-print-sheet {
            width: min(430px, 100%);
            aspect-ratio: 210 / 297;
            min-height: 620px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            padding: 0 34px 54px;
            background: #fff;
            border: 1px solid #d1d5db;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .18);
            font-family: Arial, Helvetica, sans-serif;
        }

        .air-modal-print-top-rule {
            height: 7px;
            width: 100%;
            margin-top: 24px;
            background: #9b1c1f;
        }

        .air-modal-print-hero {
            padding: 18px 0 15px;
            margin-bottom: 12px;
            border-bottom: 1px solid #e8e8e8;
        }

        .air-modal-print-hero h1 {
            margin: 0;
            color: #9b1c1f;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.15;
        }

        .air-modal-print-hero p {
            margin: 3px 0 0;
            color: #888;
            font-size: 6px;
        }

        .air-modal-print-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
            margin-bottom: 14px;
        }

        .air-modal-print-meta-card {
            min-height: 38px;
            padding: 7px 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
            box-sizing: border-box;
        }

        .air-modal-print-meta-card span {
            display: block;
            margin-bottom: 3px;
            color: #999;
            font-size: 5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .air-modal-print-meta-card strong {
            display: block;
            color: #111;
            font-size: 7px;
            font-weight: 900;
            line-height: 1.2;
        }

        .air-modal-print-section {
            margin-bottom: 14px;
        }

        .air-modal-print-section-title {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 9px;
        }

        .air-modal-print-section-title span {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 999px;
            background: #f5e6e6;
            color: #9b1c1f;
            font-size: 6px;
            font-weight: 900;
        }

        .air-modal-print-section-title h2 {
            margin: 0;
            color: #191919;
            font-size: 10px;
            font-weight: 900;
        }

        .air-modal-print-body-text {
            margin: 0;
            color: #454545;
            font-size: 6.7px;
            line-height: 1.65;
        }

        .air-modal-print-kpi-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 4px;
            margin-bottom: 12px;
        }

        .air-modal-print-kpi-card {
            position: relative;
            min-height: 41px;
            padding: 10px 3px 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f7f7f7;
            text-align: center;
            overflow: hidden;
            box-sizing: border-box;
        }

        .air-modal-print-kpi-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: #b42125;
        }

        .air-modal-print-kpi-card.metric-blue::before {
            background: #0f71c8;
        }

        .air-modal-print-kpi-card.metric-orange::before {
            background: #f06423;
        }

        .air-modal-print-kpi-card.metric-darkred::before {
            background: #7d2528;
        }

        .air-modal-print-kpi-card strong {
            display: block;
            color: #a51f22;
            font-size: 14px;
            font-weight: 900;
            line-height: 1;
        }

        .air-modal-print-kpi-card.metric-blue strong {
            color: #0f71c8;
        }

        .air-modal-print-kpi-card.metric-orange strong {
            color: #e45712;
        }

        .air-modal-print-kpi-card.metric-darkred strong {
            color: #7d2528;
        }

        .air-modal-print-kpi-card span {
            display: block;
            margin-top: 7px;
            color: #777;
            font-size: 5px;
            line-height: 1.15;
        }

        .air-modal-print-dot-list {
            display: grid;
            gap: 7px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .air-modal-print-dot-list li {
            position: relative;
            padding-left: 14px;
            color: #454545;
            font-size: 6.7px;
            line-height: 1.45;
        }

        .air-modal-print-dot-list li::before {
            content: "";
            position: absolute;
            left: 2px;
            top: 3px;
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #9b1c1f;
        }

        .air-modal-print-footer {
            position: absolute;
            left: 34px;
            right: 34px;
            bottom: 22px;
            padding-top: 8px;
            border-top: 1px solid #d8d8d8;
            font-size: 5.5px;
            line-height: 1.35;
        }

        .air-modal-print-footer strong {
            display: block;
            color: #9b1c1f;
            font-weight: 900;
        }

        .air-modal-print-footer span {
            display: block;
            color: #9b1c1f;
            margin-top: 2px;
        }

        .air-modal-print-footer em {
            display: block;
            margin-top: 3px;
            color: #333;
            font-style: normal;
            text-align: right;
        }

        .air-paper {
            width: min(420px, 100%);
            min-height: 560px;
            margin: 0 auto;
            position: relative;
            background: #fff;
            border: 1px solid #d1d5db;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .18);
        }

        .air-paper-rule {
            height: 12px;
            margin: 24px 26px 0;
            background: #8B0000;
        }

        .air-paper-meta {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            padding: 10px 26px 0;
            font-size: .48rem;
            color: #111827;
        }

        .air-paper-body {
            padding: 34px 34px 64px;
        }

        .air-paper-body h2 {
            margin: 0;
            color: #111827;
            font-size: 1.2rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .air-paper-sub {
            margin: .45rem 0 1.1rem;
            color: #6b7280;
            font-size: .68rem;
            font-weight: 700;
        }

        .air-paper-summary {
            padding: .8rem;
            border-radius: 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            margin-bottom: .85rem;
        }

        .air-paper-summary strong {
            display: block;
            margin-bottom: .4rem;
            color: #8B0000;
            font-size: .68rem;
            font-weight: 800;
        }

        .air-paper-summary p {
            margin: 0;
            color: #374151;
            font-size: .62rem;
            line-height: 1.5;
        }

        .air-paper-pages {
            display: grid;
            gap: .3rem;
            font-size: .62rem;
        }

        .air-paper-pages strong {
            display: block;
            margin-bottom: .3rem;
            color: #8B0000;
            font-size: .68rem;
            font-weight: 800;
        }

        .air-paper-pages span {
            padding: .3rem .5rem;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #374151;
        }

        .air-paper-footer {
            position: absolute;
            left: 26px;
            right: 26px;
            bottom: 16px;
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            font-size: .48rem;
            color: #111827;
        }

        /* Panel */
        .air-modal-panel {
            display: flex;
            flex-direction: column;
            background: #fff;
            border-left: 1px solid #e5e7eb;
            min-height: 0;
        }

        .air-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.4rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .air-modal-header h2 {
            margin: 0;
            color: #111827;
            font-size: 1.15rem;
            font-weight: 800;
        }

        .air-modal-header p {
            margin: .3rem 0 0;
            color: #6b7280;
            font-size: .8rem;
            line-height: 1.5;
        }

        .air-modal-close {
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 50%;
            background: #f3f4f6;
            color: #374151;
            cursor: pointer;
            font-size: .95rem;
            transition: background .2s, color .2s, transform .2s;
        }

        .air-modal-close:hover {
            background: #FCEBEB;
            color: #8B0000;
            transform: rotate(90deg);
        }

        .air-modal-options {
            display: grid;
            gap: .85rem;
            padding: 1.25rem 1.4rem;
            overflow: visible;
        }

        .air-option-row {
            display: grid;
            gap: .4rem;
        }

        .air-option-row>label {
            color: #374151;
            font-size: .76rem;
            font-weight: 800;
        }

        .air-option-static {
            display: flex;
            align-items: center;
            gap: .8rem;
            min-height: 54px;
            padding: .75rem .9rem;
            border-radius: 13px;
            border: 1px solid #d8dee8;
            background: #fdfdfd;
            color: #111827;
        }

        .air-option-static strong {
            display: block;
            font-size: .85rem;
            font-weight: 800;
            color: #111827;
        }

        .air-option-static span {
            display: block;
            margin-top: .12rem;
            font-size: .73rem;
            color: #6b7280;
        }

        .air-option-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #FCEBEB;
            color: #8B0000;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Dropdown */
        .air-dropdown {
            position: relative;
        }

        .air-dropdown-toggle {
            width: 100%;
            min-height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .8rem;
            padding: .75rem .9rem;
            border-radius: 13px;
            border: 1px solid #d8dee8;
            background: #fdfdfd;
            color: #111827;
            font-size: .85rem;
            font-weight: 800;
            cursor: pointer;
            transition: border-color .18s, box-shadow .18s;
        }

        .air-dropdown-toggle:hover,
        .air-dropdown.is-open .air-dropdown-toggle {
            border-color: #8B0000;
            box-shadow: 0 0 0 3px rgba(139, 0, 0, .08);
        }

        .air-dropdown-left {
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .air-dropdown-left>i {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: #FCEBEB;
            color: #8B0000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .air-dropdown-chevron {
            color: #8B0000;
            font-size: .75rem;
            transition: transform .2s;
        }

        .air-dropdown.is-open .air-dropdown-chevron {
            transform: rotate(180deg);
        }

        .air-dropdown-menu {
            position: absolute;
            top: calc(100% + .4rem);
            left: 0;
            right: 0;
            z-index: 100000;
            display: none;
            padding: .4rem;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            background: #fff;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .16);
        }

        .air-dropdown.is-open .air-dropdown-menu {
            display: grid;
            gap: .3rem;
            animation: airDropFade .15s ease-out;
        }

        @keyframes airDropFade {
            from {
                opacity: 0;
                transform: translateY(-4px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .air-dropdown-item {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .7rem;
            padding: .7rem .8rem;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #111827;
            text-align: left;
            cursor: pointer;
            transition: background .16s;
        }

        .air-dropdown-item:hover {
            background: rgba(139, 0, 0, .06);
        }

        .air-dropdown-item strong {
            display: block;
            font-size: .82rem;
            font-weight: 800;
        }

        .air-dropdown-item small {
            display: block;
            margin-top: .12rem;
            color: #6b7280;
            font-size: .7rem;
        }

        .air-dropdown-item>i {
            display: none;
            color: #8B0000;
        }

        .air-dropdown-item.is-selected {
            background: rgba(139, 0, 0, .08);
        }

        .air-dropdown-item.is-selected>i {
            display: inline-flex;
        }

        /* Custom range */
        .air-custom-range {
            display: grid;
            gap: .4rem;
            padding: .8rem;
            border-radius: 13px;
            background: rgba(139, 0, 0, .04);
            border: 1px dashed rgba(139, 0, 0, .22);
        }

        .air-custom-range[hidden] {
            display: none !important;
        }

        .air-custom-range>label {
            font-size: .74rem;
            font-weight: 800;
            color: #374151;
        }

        .air-custom-range-input {
            display: flex;
            align-items: center;
            gap: .6rem;
            min-height: 44px;
            padding: 0 .8rem;
            border-radius: 10px;
            border: 1px solid #d8dee8;
            background: #fff;
        }

        .air-custom-range-input i {
            color: #8B0000;
        }

        .air-custom-range-input input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: #111827;
            font-size: .84rem;
            font-weight: 700;
        }

        .air-custom-range small {
            color: #6b7280;
            font-size: .7rem;
        }

        .air-custom-range-error {
            color: #dc2626;
            font-size: .72rem;
            font-weight: 700;
        }

        .air-modal-note {
            display: flex;
            gap: .6rem;
            padding: .8rem;
            border-radius: 12px;
            background: rgba(139, 0, 0, .06);
            color: #374151;
            font-size: .76rem;
            line-height: 1.5;
        }

        .air-modal-note i {
            margin-top: .15rem;
            color: #8B0000;
        }

        .air-modal-actions {
            margin-top: auto;
            display: flex;
            justify-content: flex-end;
            gap: .65rem;
            padding: 1.1rem 1.4rem;
            border-top: 1px solid #e5e7eb;
        }

        .air-modal-actions {
            margin-top: auto;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .75rem;
            padding: 1rem 1.4rem;
            border-top: 1px solid #e5e7eb;
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(10px);
            box-shadow: 0 -10px 30px rgba(15, 23, 42, .06);
        }

        .air-action-btn {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            padding: .75rem 1.05rem;
            border-radius: 14px;
            font-size: .88rem;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
        }

        .air-action-btn-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            font-size: .8rem;
            flex-shrink: 0;
        }

        .air-action-btn--secondary {
            border: 1px solid #d8dee8;
            background: #ffffff;
            color: #334155;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .05);
        }

        .air-action-btn--secondary .air-action-btn-icon {
            background: #FCEBEB;
            color: #8B0000;
        }

        .air-action-btn--secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
        }

        .air-action-btn--primary {
            border: 0;
            background: linear-gradient(135deg, #8B0000, #b91c1c);
            color: #ffffff;
            box-shadow: 0 10px 24px rgba(139, 0, 0, .24);
        }

        .air-action-btn--primary .air-action-btn-icon {
            background: rgba(255, 255, 255, .18);
            color: #ffffff;
        }

        .air-action-btn--primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(139, 0, 0, .32);
        }

        .air-action-btn:active {
            transform: translateY(0);
        }

        .air-action-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 4px rgba(139, 0, 0, .14);
        }

        /* Dark mode */
        [data-theme="dark"] .air-modal-actions {
            background: rgba(0, 13, 26, .96);
            border-top-color: rgba(255, 255, 255, .08);
        }

        [data-theme="dark"] .air-action-btn--secondary {
            background: #001f3f;
            border-color: rgba(255, 255, 255, .14);
            color: #f9fafb;
        }

        [data-theme="dark"] .air-action-btn--secondary:hover {
            background: #002a52;
            border-color: rgba(255, 255, 255, .22);
        }

        /* Mobile */
        @media (max-width: 520px) {
            .air-modal-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .air-action-btn {
                width: 100%;
            }
        }

        /* dark mode modal */
        [data-theme="dark"] .air-modal {
            background: #00152a;
        }

        [data-theme="dark"] .air-modal-panel {
            background: #000D1A;
            border-left-color: rgba(255, 255, 255, .08);
        }

        [data-theme="dark"] .air-modal-header,
        [data-theme="dark"] .air-modal-actions {
            border-color: rgba(255, 255, 255, .08);
        }

        [data-theme="dark"] .air-modal-header h2 {
            color: #f9fafb;
        }

        [data-theme="dark"] .air-modal-header p,
        [data-theme="dark"] .air-option-row>label {
            color: #d1d5db;
        }

        [data-theme="dark"] .air-modal-close {
            background: #001f3f;
            color: #f9fafb;
            border: 0;
        }

        [data-theme="dark"] .air-option-static,
        [data-theme="dark"] .air-dropdown-toggle,
        [data-theme="dark"] .air-dropdown-menu {
            background: #001f3f;
            border-color: rgba(255, 255, 255, .12);
            color: #f9fafb;
        }

        [data-theme="dark"] .air-option-static strong,
        [data-theme="dark"] .air-dropdown-item,
        [data-theme="dark"] .air-dropdown-item strong {
            color: #f9fafb;
        }

        [data-theme="dark"] .air-option-static span,
        [data-theme="dark"] .air-dropdown-item small,
        [data-theme="dark"] .air-custom-range small {
            color: #d1d5db;
        }

        [data-theme="dark"] .air-dropdown-item:hover,
        [data-theme="dark"] .air-dropdown-item.is-selected {
            background: rgba(139, 0, 0, .35);
        }

        [data-theme="dark"] .air-custom-range {
            background: rgba(139, 0, 0, .2);
            border-color: rgba(255, 255, 255, .12);
        }

        [data-theme="dark"] .air-custom-range>label {
            color: #f9fafb;
        }

        [data-theme="dark"] .air-custom-range-input {
            background: #001f3f;
            border-color: rgba(255, 255, 255, .12);
        }

        [data-theme="dark"] .air-custom-range-input input {
            color: #f9fafb;
        }

        [data-theme="dark"] .air-modal-note {
            background: rgba(139, 0, 0, .2);
            color: #d1d5db;
        }

        @media (max-width: 820px) {
            .air-modal {
                width: min(520px, calc(100vw - 2rem));
                grid-template-columns: 1fr;
            }

            .air-modal-preview {
                display: none;
            }
        }

        .air-modal {
            width: min(1120px, calc(100vw - 4rem));
            height: min(760px, calc(100vh - 4rem));
            max-height: calc(100vh - 4rem);
        }

        .air-modal-preview {
            padding: 1.25rem;
            background: #e5e7eb;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0;
        }

        .air-modal-preview-pages {
            display: grid;
            gap: 1.25rem;
            justify-items: center;
            padding-bottom: 1.25rem;
        }

        .air-modal-panel {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .air-modal-options {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }

        .air-modal-actions {
            flex-shrink: 0;
            position: sticky;
            bottom: 0;
            z-index: 5;
            background: #fff;
        }

        [data-theme="dark"] .air-modal-actions {
            background: #000D1A;
        }

        .air-modal-print-sheet {
            width: min(430px, 100%);
            aspect-ratio: 210 / 297;
            min-height: auto;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            padding: 0 34px 54px;
            background: #fff;
            border: 1px solid #d1d5db;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .18);
            font-family: Arial, Helvetica, sans-serif;
        }

        /* Landscape preview */
        .air-modal.is-preview-landscape {
            width: min(1320px, calc(100vw - 3rem));
            grid-template-columns: minmax(660px, 1.25fr) minmax(360px, .75fr);
        }

        .air-modal.is-preview-landscape .air-modal-print-sheet {
            width: 640px !important;
            max-width: 100% !important;
            aspect-ratio: 297 / 210 !important;
            min-height: auto !important;
            height: auto !important;
            padding: 0 28px 42px !important;
        }

        .air-modal.is-preview-landscape .air-modal-preview {
            overflow-x: auto;
        }

        .air-modal.is-preview-landscape .air-modal-preview-pages {
            align-items: center;
        }

        .air-modal.is-preview-landscape .air-modal-print-meta-grid {
            gap: 8px;
        }

        .air-modal.is-preview-landscape .air-modal-print-section {
            margin-bottom: 10px;
        }

        .air-modal.is-preview-landscape .air-modal-print-body-text,
        .air-modal.is-preview-landscape .air-modal-print-dot-list li {
            font-size: 5.8px;
            line-height: 1.45;
        }

        .air-modal.is-preview-landscape .air-modal-print-footer {
            left: 28px;
            right: 28px;
            bottom: 16px;
        }

        .air-modal-print-page-heading {
            padding: 28px 0 10px;
            margin-bottom: 22px;
            border-bottom: 1px solid #ddd;
            font-size: 7px;
            color: #888;
        }

        .air-modal-print-page-heading strong {
            color: #9b1c1f;
            font-weight: 900;
        }

        .air-modal-print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 6.5px;
        }

        .air-modal-print-table th {
            padding: 7px;
            background: #9b1c1f;
            color: #fff;
            text-align: left;
            font-weight: 900;
            border: 1px solid #d8d8d8;
        }

        .air-modal-print-table td {
            padding: 7px;
            color: #454545;
            border: 1px solid #ddd;
        }

        .air-modal-print-table tr:nth-child(even) td {
            background: #f4f4f4;
        }

        .air-modal-print-risk-box {
            padding: 10px 12px;
            border-radius: 5px;
            background: #f4f4f4;
            border-left: 4px solid #9b1c1f;
        }

        .air-modal-print-risk-box strong {
            display: block;
            margin-bottom: 6px;
            color: #9b1c1f;
            font-size: 8px;
            font-weight: 900;
        }

        .air-modal-print-risk-box p {
            margin: 0;
            color: #444;
            font-size: 6.5px;
            line-height: 1.55;
        }

        .air-modal-print-rec-list {
            display: grid;
            gap: 8px;
        }

        .air-modal-print-rec-card {
            padding: 8px 10px;
            background: #f8f8f8;
            border-left: 4px solid #9b1c1f;
        }

        .air-modal-print-rec-card strong {
            display: block;
            margin-bottom: 4px;
            color: #9b1c1f;
            font-size: 7px;
            font-weight: 900;
        }

        .air-modal-print-rec-card p {
            margin: 0;
            color: #444;
            font-size: 6.5px;
            line-height: 1.45;
        }

        /* ═══════════════════════════════════════════════════════════════
                                                                                                               PRINT STYLES
                                                                                                            ═══════════════════════════════════════════════════════════════ */
        .air-print-doc {
            display: none;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            html,
            body {
                width: 100% !important;
                min-height: auto !important;
                overflow: visible !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body * {
                visibility: hidden !important;
            }

            #aiFullPrintDocument,
            #aiFullPrintDocument * {
                visibility: visible !important;
            }

            #aiFullPrintDocument {
                display: block !important;
                position: absolute !important;
                inset: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                font-family: Arial, Helvetica, sans-serif !important;
            }

            /* Page shell */
            .air-print-page {
                position: relative !important;
                display: block !important;
                width: 210mm !important;
                min-height: 297mm !important;
                padding: 24mm 18mm 28mm !important;
                margin: 0 auto !important;
                background: #fff !important;
                overflow: hidden !important;
                break-after: page !important;
                page-break-after: always !important;
                box-sizing: border-box !important;
            }

            body.print-layout-landscape .air-print-page {
                width: 297mm !important;
                min-height: 210mm !important;
                padding: 22mm 18mm 24mm !important;
            }

            .air-print-page:last-child {
                break-after: auto !important;
                page-break-after: auto !important;
            }

            .air-print-top-rule,
            .air-print-hero,
            .air-print-page-heading,
            .air-print-footer {
                display: none !important;
            }

            .air-print-fixed-header {
                position: absolute !important;
                top: 13mm !important;
                left: 18mm !important;
                right: 18mm !important;
                display: flex !important;
                align-items: center !important;
                gap: 4px !important;
                padding-bottom: 5mm !important;
                border-bottom: 1px solid #d8d8d8 !important;
                font-size: 12px !important;
                line-height: 1.2 !important;
            }

            .air-print-fixed-header strong {
                color: #9b1c1f !important;
                font-weight: 900 !important;
            }

            .air-print-fixed-header span {
                color: #777 !important;
                font-weight: 400 !important;
            }

            .air-print-fixed-footer {
                position: absolute !important;
                left: 18mm !important;
                right: 18mm !important;
                bottom: 9mm !important;
                display: grid !important;
                grid-template-columns: 1fr auto !important;
                align-items: end !important;
                gap: 8mm !important;
                padding-top: 3mm !important;
                border-top: 1px solid #d8d8d8 !important;
                font-size: 9px !important;
                line-height: 1.35 !important;
            }

            .air-print-fixed-footer .air-print-footer-left {
                display: block !important;
                width: auto !important;
            }

            .air-print-fixed-footer .air-print-footer-left strong {
                display: block !important;
                color: #9b1c1f !important;
                font-size: 9px !important;
                font-weight: 900 !important;
            }

            .air-print-fixed-footer .air-print-footer-left span {
                display: block !important;
                color: #9b1c1f !important;
                font-size: 8.5px !important;
                margin-top: .8mm !important;
            }

            .air-print-fixed-footer .air-print-footer-right {
                display: block !important;
                width: auto !important;
                text-align: right !important;
                color: #333 !important;
                font-size: 8.5px !important;
                white-space: nowrap !important;
            }

            body.print-custom-pages #aiFullPrintDocument .air-print-page.is-print-page-hidden {
                display: none !important;
            }

            /* Top rule */
            .air-print-top-rule {
                display: block !important;
                height: 9px !important;
                width: 100% !important;
                background: #9b1c1f !important;
            }

            /* Hero */
            .air-print-hero {
                display: flex !important;
                align-items: flex-start !important;
                gap: 16px !important;
                padding: 10mm 0 8mm !important;
                margin-bottom: 5mm !important;
                border-bottom: 1px solid #e8e8e8 !important;
            }

            .air-print-hero h1 {
                margin: 0 !important;
                color: #9b1c1f !important;
                font-size: 22px !important;
                font-weight: 900 !important;
                line-height: 1.1 !important;
            }

            .air-print-hero p {
                margin: 4px 0 0 !important;
                color: #888 !important;
                font-size: 12px !important;
            }

            /* Meta strip */
            .air-print-meta-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 4mm !important;
                margin-bottom: 8mm !important;
            }

            .air-print-meta-card {
                min-height: 20mm !important;
                padding: 4mm 5mm !important;
                border: 1px solid #ddd !important;
                border-radius: 7px !important;
                background: #fff !important;
                box-sizing: border-box !important;
            }

            .air-print-meta-card span {
                display: block !important;
                margin-bottom: 3px !important;
                color: #999 !important;
                font-size: 8px !important;
                font-weight: 900 !important;
                text-transform: uppercase !important;
                letter-spacing: .5px !important;
            }

            .air-print-meta-card strong {
                display: block !important;
                color: #111 !important;
                font-size: 13px !important;
                font-weight: 900 !important;
                line-height: 1.25 !important;
            }

            /* Page heading (pages 2–3) */
            .air-print-page-heading {
                display: block !important;
                padding: 7mm 0 5px !important;
                margin-bottom: 9mm !important;
                border-bottom: 1px solid #ddd !important;
                font-size: 12px !important;
                color: #888 !important;
            }

            .air-print-page-heading strong {
                color: #9b1c1f !important;
                font-weight: 900 !important;
            }

            /* Section wrapper */
            .air-print-section {
                display: block !important;
                margin: 0 0 8mm !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            /* Section title */
            .air-print-section-title {
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
                margin-bottom: 5mm !important;
            }

            .air-print-section-title span {
                width: 9mm !important;
                height: 9mm !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
                border-radius: 999px !important;
                background: #f5e6e6 !important;
                color: #9b1c1f !important;
                font-size: 10px !important;
                font-weight: 900 !important;
            }

            .air-print-section-title h2 {
                margin: 0 !important;
                color: #191919 !important;
                font-size: 17px !important;
                font-weight: 900 !important;
            }

            /* Body text */
            .air-print-body-text {
                margin: 0 !important;
                color: #454545 !important;
                font-size: 12.5px !important;
                line-height: 1.65 !important;
            }

            /* KPI grid */
            .air-print-kpi-grid {
                display: grid !important;
                grid-template-columns: repeat(6, 1fr) !important;
                gap: 2mm !important;
                margin: 0 0 6mm !important;
            }

            .air-print-kpi-card {
                position: relative !important;
                min-height: 24mm !important;
                padding: 5mm 2mm 3.5mm !important;
                border: 1px solid #ddd !important;
                border-radius: 6px !important;
                background: #f7f7f7 !important;
                text-align: center !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .air-print-kpi-card::before {
                content: "" !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 3.5mm !important;
                background: #b42125 !important;
            }

            .air-print-kpi-card.metric-blue::before {
                background: #0f71c8 !important;
            }

            .air-print-kpi-card.metric-orange::before {
                background: #f06423 !important;
            }

            .air-print-kpi-card.metric-darkred::before {
                background: #7d2528 !important;
            }

            .air-print-kpi-card strong {
                display: block !important;
                margin-top: 2mm !important;
                color: #a51f22 !important;
                font-size: 24px !important;
                font-weight: 900 !important;
                line-height: 1 !important;
            }

            .air-print-kpi-card.metric-blue strong {
                color: #0f71c8 !important;
            }

            .air-print-kpi-card.metric-orange strong {
                color: #e45712 !important;
            }

            .air-print-kpi-card.metric-darkred strong {
                color: #7d2528 !important;
            }

            .air-print-kpi-card span {
                display: block !important;
                margin-top: 4mm !important;
                color: #777 !important;
                font-size: 10px !important;
                line-height: 1.2 !important;
            }

            /* Dot list */
            .air-print-dot-list {
                display: grid !important;
                gap: 3.5mm !important;
                margin: 0 !important;
                padding: 0 !important;
                list-style: none !important;
            }

            .air-print-dot-list li {
                position: relative !important;
                padding-left: 7mm !important;
                color: #454545 !important;
                font-size: 12.5px !important;
                line-height: 1.45 !important;
            }

            .air-print-dot-list li::before {
                content: "" !important;
                position: absolute !important;
                left: 1mm !important;
                top: 4px !important;
                width: 3mm !important;
                height: 3mm !important;
                border-radius: 999px !important;
                background: #9b1c1f !important;
            }

            /* Tables */
            .air-print-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 0 0 5mm !important;
                table-layout: fixed !important;
                font-size: 12.5px !important;
            }

            .air-print-table th {
                padding: 4mm !important;
                background: #9b1c1f !important;
                border: 1px solid #d8d8d8 !important;
                color: #fff !important;
                font-size: 12.5px !important;
                font-weight: 900 !important;
                text-align: left !important;
            }

            .air-print-table td {
                padding: 4mm !important;
                border: 1px solid #ddd !important;
                background: #fff !important;
                color: #454545 !important;
                vertical-align: middle !important;
            }

            .air-print-table tbody tr:nth-child(even) td {
                background: #f4f4f4 !important;
            }

            .air-print-table--inventory th:nth-child(2),
            .air-print-table--inventory th:nth-child(3),
            .air-print-table--inventory td:nth-child(2),
            .air-print-table--inventory td:nth-child(3) {
                text-align: center !important;
            }

            .air-print-table .status-ok {
                color: #247a2f !important;
            }

            /* Risk box */
            .air-print-risk-box {
                max-width: 112mm !important;
                padding: 4mm 5mm !important;
                border-radius: 5px !important;
                background: #f4f4f4 !important;
                border-left: 4px solid #9b1c1f !important;
            }

            .air-print-risk-box strong {
                display: block !important;
                margin-bottom: 3mm !important;
                color: #9b1c1f !important;
                font-size: 15px !important;
                font-weight: 900 !important;
            }

            .air-print-risk-box p {
                margin: 0 !important;
                color: #444 !important;
                font-size: 12.5px !important;
                line-height: 1.55 !important;
            }

            /* Recommendation cards */
            .air-print-rec-list {
                display: grid !important;
                gap: 4mm !important;
                margin-top: 2mm !important;
            }

            .air-print-rec-card {
                display: block !important;
                padding: 4mm 5mm !important;
                border-left: 4px solid #9b1c1f !important;
                background: #f8f8f8 !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .air-print-rec-card strong {
                display: block !important;
                margin-bottom: 2mm !important;
                color: #9b1c1f !important;
                font-size: 13px !important;
                font-weight: 900 !important;
            }

            .air-print-rec-card p {
                margin: 0 !important;
                color: #444 !important;
                font-size: 12.5px !important;
                line-height: 1.5 !important;
            }

            /* Fixed footer */
            .air-print-footer {
                position: absolute !important;
                left: 18mm !important;
                right: 18mm !important;
                bottom: 10mm !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0 !important;
                padding-top: 2.5mm !important;
                border-top: 1px solid #d8d8d8 !important;
                font-size: 11px !important;
                line-height: 1.35 !important;
            }

            .air-print-footer-right {
                display: block !important;
                width: 100% !important;
                text-align: right !important;
                color: #333 !important;
                font-size: 11px !important;
                margin-bottom: 1.5mm !important;
            }

            .air-print-footer-left {
                display: block !important;
                width: 100% !important;
            }

            .air-print-footer-left strong {
                display: block !important;
                color: #9b1c1f !important;
                font-size: 11px !important;
                font-weight: 900 !important;
            }

            .air-print-footer-left span {
                display: block !important;
                color: #9b1c1f !important;
                font-size: 11px !important;
                margin-top: 1mm !important;
            }

            body.print-layout-landscape .air-print-footer {
                bottom: 7mm !important;
            }

            /* hide non-print elements */
            .air-modal-overlay,
            .air-banner-shell,
            #aiReportScreenArea,
            header,
            aside,
            nav,
            footer {
                display: none !important;
            }
        }
    </style>
@endpush

@push('scripts')
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
            const reportPrintPages = document.querySelectorAll('#aiFullPrintDocument .air-print-page');

            let printPageStyle = document.getElementById('dynamicPrintPageStyle');
            if (!printPageStyle) {
                printPageStyle = document.createElement('style');
                printPageStyle.id = 'dynamicPrintPageStyle';
                document.head.appendChild(printPageStyle);
            }

            function updatePrintSettings() {
                const layout = printLayout?.value || 'portrait';
                const pages = printPages?.value || 'all';

                if (customPageRangeWrap) customPageRangeWrap.hidden = pages !== 'custom';
                if (pages !== 'custom') clearCustomPageVisibility();

                printPageStyle.textContent = `@media print { @page { size: A4 ${layout}; margin: 0; } }`;

                const modalBox = modal?.querySelector('.air-modal');

                if (modalBox) {
                    modalBox.classList.toggle('is-preview-landscape', layout === 'landscape');
                }
            }

            function parsePageRange(value, maxPage) {
                const selected = new Set();
                const clean = String(value || '').replace(/\s+/g, '');
                if (!clean) return null;
                for (const part of clean.split(',')) {
                    if (!part) return null;
                    if (part.includes('-')) {
                        const [a, b] = part.split('-');
                        const start = Number(a),
                            end = Number(b);
                        if (!Number.isInteger(start) || !Number.isInteger(end)) return null;
                        if (start < 1 || end < 1 || start > end || end > maxPage) return null;
                        for (let p = start; p <= end; p++) selected.add(p);
                    } else {
                        const p = Number(part);
                        if (!Number.isInteger(p) || p < 1 || p > maxPage) return null;
                        selected.add(p);
                    }
                }
                return selected;
            }

            function clearCustomPageVisibility() {
                document.body.classList.remove('print-custom-pages');
                reportPrintPages.forEach(p => p.classList.remove('is-print-page-hidden'));
                if (customPageRangeError) customPageRangeError.hidden = true;
            }

            function applyCustomPageVisibility() {
                clearCustomPageVisibility();
                if (printPages?.value !== 'custom') return true;
                const selected = parsePageRange(customPageRange?.value, reportPrintPages.length);
                if (!selected || selected.size === 0) {
                    if (customPageRangeError) customPageRangeError.hidden = false;
                    customPageRange?.focus();
                    return false;
                }
                document.body.classList.add('print-custom-pages');
                reportPrintPages.forEach(page => {
                    if (!selected.has(Number(page.dataset.printPage)))
                        page.classList.add('is-print-page-hidden');
                });
                return true;
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
                dropdowns.forEach(d => d.classList.remove('is-open'));
            }

            dropdowns.forEach(function(dropdown) {
                const toggle = dropdown.querySelector('[data-print-dropdown-toggle]');
                const label = dropdown.querySelector('[data-print-dropdown-label]');
                const items = dropdown.querySelectorAll('.air-dropdown-item');

                toggle?.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdowns.forEach(d => {
                        if (d !== dropdown) d.classList.remove('is-open');
                    });
                    dropdown.classList.toggle('is-open');
                });

                items.forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const targetInput = document.getElementById(item.dataset.target);
                        if (targetInput) targetInput.value = item.dataset.value;
                        if (label) label.textContent = item.dataset.label;
                        items.forEach(i => i.classList.remove('is-selected'));
                        item.classList.add('is-selected');
                        dropdown.classList.remove('is-open');
                        updatePrintSettings();
                    });
                });
            });

            document.addEventListener('click', () => dropdowns.forEach(d => d.classList.remove('is-open')));
            customPageRange?.addEventListener('input', () => {
                if (customPageRangeError) customPageRangeError.hidden = true;
            });
            openButton?.addEventListener('click', openModal);
            closeButtons.forEach(b => b.addEventListener('click', closeModal));
            modal?.addEventListener('click', e => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && modal?.classList.contains('show')) closeModal();
            });

            confirmButton?.addEventListener('click', function() {
                updatePrintSettings();
                if (!applyCustomPageVisibility()) return;
                closeModal();
                setTimeout(() => window.print(), 180);
            });

            window.addEventListener('afterprint', clearCustomPageVisibility);
            updatePrintSettings();
        });
    </script>
@endpush
