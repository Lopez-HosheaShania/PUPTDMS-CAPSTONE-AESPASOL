<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AI Generated Overall Report</title>
    <style>
        .air-advisory-badge {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid #e5caca;
            border-radius: 12px;
            background: #fff1f2;
            color: #7f1417;
            font-size: 8px;
            font-weight: bold;
        }

        .air-advisory-notice { margin-bottom: 10px; }
        .air-advisory-note { margin: 6px 0 0; color: #52525b; font-size: 8px; line-height: 1.5; }
        .air-analysis-unavailable { color: #7f1417; font-size: 8px; font-weight: bold; }

        @page {
            margin: 26px 28px 34px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #2f2f2f;
            font-size: 12px;
            line-height: 1.45;
        }

        .header {
            border-bottom: 2px solid #9b1c1f;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #7f1417;
        }

        .header p {
            margin: 4px 0 0;
            color: #666;
            font-size: 11px;
        }

        .meta-grid {
            width: 100%;
            margin-bottom: 18px;
        }

        .meta-card {
            width: 31%;
            display: inline-block;
            vertical-align: top;
            margin-right: 2%;
            padding: 10px 12px;
            background: #f7f1f1;
            border: 1px solid #ead5d5;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .meta-card:last-child {
            margin-right: 0;
        }

        .meta-card span {
            display: block;
            font-size: 8px;
            color: #7a7a7a;
            text-transform: uppercase;
        }

        .meta-card strong {
            display: block;
            margin-top: 4px;
            font-size: 13px;
            color: #8b1619;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            margin: 0 0 10px;
            color: #8b1619;
            font-size: 15px;
        }

        .body-text {
            margin: 0 0 12px;
            text-align: justify;
        }

        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .metrics-table th,
        .metrics-table td {
            border: 1px solid #d9d9d9;
            padding: 8px 9px;
            text-align: left;
            vertical-align: top;
        }

        .metrics-table th {
            background: #9b1c1f;
            color: #fff;
            font-size: 11px;
        }

        .metrics-table tr:nth-child(even) td {
            background: #f7f4f4;
        }

        .kpi-grid {
            margin-bottom: 12px;
        }

        .kpi-card {
            width: 31%;
            display: inline-block;
            vertical-align: top;
            margin-right: 2%;
            margin-bottom: 10px;
            padding: 10px 12px;
            background: #faf5f5;
            border: 1px solid #ead9d9;
            border-top: 4px solid #9b1c1f;
            box-sizing: border-box;
        }

        .kpi-card:nth-child(3n) {
            margin-right: 0;
        }

        .kpi-card strong {
            display: block;
            font-size: 16px;
            color: #8b1619;
        }

        .kpi-card span {
            display: block;
            margin-top: 4px;
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
        }

        ul {
            margin: 0;
            padding-left: 18px;
        }

        li {
            margin-bottom: 6px;
        }

        .footer-note {
            margin-top: 24px;
            font-size: 8px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        $riskLevel = data_get($aiReport, 'risk_level', 'Low');
        $riskExplanation = data_get(
            $aiReport,
            'risk_explanation',
            'Operational risk details are currently unavailable for this report.',
        );
        $totalPatients = data_get($aiReport, 'print_metrics.total_patients') ?? data_get($aiReport, 'metrics.total_patients') ?? 'Unavailable';
        $newPatients = data_get($aiReport, 'print_metrics.new_patients') ?? data_get($aiReport, 'metrics.new_patients') ?? 'Unavailable';
        $totalAppointments = data_get($aiReport, 'print_metrics.total_appointments') ?? data_get($aiReport, 'metrics.total_appointments') ?? 'Unavailable';
        $cancelledAppointments = data_get($aiReport, 'print_metrics.cancelled_appointments') ?? data_get($aiReport, 'metrics.cancelled_appointments') ?? 'Unavailable';
        $completionRate = data_get($aiReport, 'print_metrics.completion_rate') ?? data_get($aiReport, 'metrics.completion_rate') ?? 'Unavailable';
        $cancellationRate = data_get($aiReport, 'print_metrics.cancellation_rate') ?? data_get($aiReport, 'metrics.cancellation_rate') ?? 'Unavailable';
        $treatmentsRecorded = data_get($aiReport, 'print_metrics.treatments_recorded') ?? data_get($aiReport, 'metrics.treatments_recorded') ?? 'Unavailable';
        $dominantTreatment = data_get($aiReport, 'print_metrics.dominant_treatment', 'None identified');
        $dominantTreatmentCount = (int) data_get($aiReport, 'print_metrics.dominant_treatment_count', 0);
        $dominantTreatmentRate = (float) data_get($aiReport, 'print_metrics.dominant_treatment_rate', 0);
        $treatmentBreakdown = collect(data_get($aiReport, 'print_metrics.treatment_breakdown', data_get($aiReport, 'treatments.breakdown', [])));
        $lowStockCount = data_get($aiReport, 'print_metrics.low_stock_count', 0);
        $criticalStockCount = data_get($aiReport, 'print_metrics.critical_stock_count', 0);
        $docTotal = data_get($aiReport, 'print_metrics.document_requests_total', 0);
        $docPending = data_get($aiReport, 'print_metrics.document_requests_pending', 0);
        $docApproved = data_get($aiReport, 'print_metrics.document_requests_approved', 0);
        $docRejected = data_get($aiReport, 'print_metrics.document_requests_rejected', 0);
        $docApprovalRate = data_get($aiReport, 'print_metrics.document_requests_approval_rate', 0);
        $docPendingRate = data_get($aiReport, 'print_metrics.document_requests_pending_rate', 0);
        $docRejectionRate = data_get($aiReport, 'print_metrics.document_requests_rejection_rate', 0);
        $docMostRequested = data_get($aiReport, 'print_metrics.document_requests_most_requested', 'No dominant document type yet');
        $docMostRequestedCount = data_get($aiReport, 'print_metrics.document_requests_most_requested_count', 0);

        $formatPercent = function ($value) {
            return is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 1), '0'), '.') . '%' : $value;
        };
    @endphp

    <div class="header">
        <h1>Administrative AI Generated Overall Report</h1>
        <p>{{ $aiReport['period'] }} · PUP Taguig Dental Clinic · System-generated PDF export</p>
        <span class="air-advisory-badge">AI-Generated &bull; Advisory Only</span>
    </div>

    <div class="air-advisory-notice">
        <p class="air-advisory-note">This report contains AI-generated summaries and interpretations intended to assist administrative review. Please verify all figures and findings against the underlying clinic records before making operational or administrative decisions.</p>
        @if (($aiReport['generation_source'] ?? null) !== 'ai')
            <p class="air-analysis-unavailable" role="status">AI-generated analysis is currently unavailable. Please refer to the verified clinic statistics shown in this report.</p>
        @endif
    </div>

    <div class="meta-grid">
        <div class="meta-card">
            <span>Report Period</span>
            <strong>{{ $aiReport['period'] }}</strong>
        </div>
        <div class="meta-card">
            <span>Generated At</span>
            <strong>{{ $aiReport['generated_at'] }}</strong>
        </div>
        <div class="meta-card">
            <span>Operational Risk</span>
            <strong>{{ $riskLevel }}</strong>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">Executive Summary</h2>
        <p class="body-text">{{ $aiReport['executive_summary'] }}</p>
    </div>

    <div class="section">
        <h2 class="section-title">Key Findings</h2>
        <div class="kpi-grid">
            <div class="kpi-card">
                <strong>{{ $totalPatients }}</strong>
                <span>Total Patients</span>
            </div>
            <div class="kpi-card">
                <strong>{{ $newPatients }}</strong>
                <span>New Patients</span>
            </div>
            <div class="kpi-card">
                <strong>{{ $totalAppointments }}</strong>
                <span>Total Appointments</span>
            </div>
            <div class="kpi-card">
                <strong>{{ $cancelledAppointments }}</strong>
                <span>Cancelled Appointments</span>
            </div>
            <div class="kpi-card">
                <strong>{{ $formatPercent($completionRate) }}</strong>
                <span>Completion Rate</span>
            </div>
            <div class="kpi-card">
                <strong>{{ $formatPercent($cancellationRate) }}</strong>
                <span>Cancellation Rate</span>
            </div>
        </div>

        <ul>
            @foreach ($aiReport['key_findings'] ?? [] as $finding)
                <li>{{ $finding }}</li>
            @endforeach
        </ul>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2 class="section-title">Treatment Analysis</h2>
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Dental Case Summary</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total treatments recorded</td>
                    <td>{{ $treatmentsRecorded }}</td>
                    <td>100%</td>
                </tr>
                <tr>
                    <td>{{ $dominantTreatment }}</td>
                    <td>{{ $dominantTreatmentCount }}</td>
                    <td>{{ $formatPercent($dominantTreatmentRate) }}</td>
                </tr>
                @foreach ($treatmentBreakdown as $item)
                    <tr>
                        <td>{{ data_get($item, 'name', 'Other') }}</td>
                        <td>{{ data_get($item, 'count', 0) }}</td>
                        <td>{{ $formatPercent(data_get($item, 'pct', 0)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (!empty($aiReport['treatment_analysis']))
            <ul>
                @foreach ($aiReport['treatment_analysis'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="section">
        <h2 class="section-title">Inventory Analysis</h2>
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Inventory Metric</th>
                    <th>Count</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Low-stock items</td>
                    <td>{{ $lowStockCount }}</td>
                    <td>{{ (int) $lowStockCount > 0 ? 'Needs review' : 'Stable' }}</td>
                </tr>
                <tr>
                    <td>Critical-stock items</td>
                    <td>{{ $criticalStockCount }}</td>
                    <td>{{ (int) $criticalStockCount > 0 ? 'Urgent' : 'None' }}</td>
                </tr>
                <tr>
                    <td>Items under monitoring</td>
                    <td>{{ (int) $lowStockCount + (int) $criticalStockCount }}</td>
                    <td>{{ (int) $lowStockCount + (int) $criticalStockCount > 0 ? 'Monitor' : 'Clear' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2 class="section-title">Document Request Analysis</h2>
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Document Request Summary</th>
                    <th>Value</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total requests</td>
                    <td>{{ $docTotal }}</td>
                    <td>100%</td>
                </tr>
                <tr>
                    <td>Pending requests</td>
                    <td>{{ $docPending }}</td>
                    <td>{{ $formatPercent($docPendingRate) }}</td>
                </tr>
                <tr>
                    <td>Approved requests</td>
                    <td>{{ $docApproved }}</td>
                    <td>{{ $formatPercent($docApprovalRate) }}</td>
                </tr>
                <tr>
                    <td>Rejected requests</td>
                    <td>{{ $docRejected }}</td>
                    <td>{{ $formatPercent($docRejectionRate) }}</td>
                </tr>
                <tr>
                    <td>Most requested document</td>
                    <td>{{ $docMostRequested }} - {{ $docMostRequestedCount }} request/s</td>
                    <td>{{ $docTotal > 0 ? $formatPercent(($docMostRequestedCount / $docTotal) * 100) : '0%' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Risk Interpretation and Recommendations</h2>
        <p class="body-text"><strong>{{ $riskLevel }} risk:</strong> {{ $riskExplanation }}</p>

        <ul>
            @foreach ($aiReport['recommendations'] ?? [] as $recommendation)
                <li>{{ $recommendation }}</li>
            @endforeach
        </ul>
    </div>

    <div class="footer-note">
        This document contains personal-identifiable information subject to Data Privacy. This is system-generated,
        signature is not required.
    </div>
</body>

</html>
