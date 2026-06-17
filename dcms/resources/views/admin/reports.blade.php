@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
<main id="mainContent" class="admin-page-shell page-enter mode-list">
    <div class="w-full">

            <div class="page-banner" style="margin-bottom:1.75rem;">
                <div class="page-banner-inner"
                    style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                    <div>
                        <h1 class="page-title">Reports & Analytics</h1>
                    </div>

                    <a href="{{ route('admin.reports.ai-generated') }}"
                        style="display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1rem;border-radius:12px;background:#8B0000;color:#fff;text-decoration:none;font-size:.85rem;font-weight:700;box-shadow:0 8px 20px rgba(139,0,0,.18);">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        AI Generated Report
                    </a>
                </div>
            </div>

        <div class="section-label">
            <i class="fa-solid fa-users" style="font-size:11px;color:#8B0000;"></i>
            Patient statistics
        </div>

        <div class="metric-grid">
            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#8B0000,#c0392b);"></div>
                <div class="m-label">Total patients</div>
                <div class="m-value">{{ number_format($stats['total_patients']) }}</div>
                <div class="m-sub up">
                    <i class="fa-solid fa-arrow-trend-up" style="font-size:.65rem;"></i>
                    +{{ $stats['new_this_week'] }} this week
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#3b82f6,#2563eb);"></div>
                <div class="m-label">New today</div>
                <div class="m-value" style="color:#2563eb;">{{ $stats['new_today'] }}</div>
                <div class="m-sub up">
                    <i class="fa-solid fa-arrow-trend-up" style="font-size:.65rem;"></i>
                    +{{ $stats['new_today_diff'] }} vs yesterday
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#22c55e,#16a34a);"></div>
                <div class="m-label">New this month</div>
                <div class="m-value" style="color:#16a34a;">{{ $stats['new_this_month'] }}</div>
                <div class="m-sub up">
                    <i class="fa-solid fa-arrow-trend-up" style="font-size:.65rem;"></i>
                    +{{ $stats['new_month_pct'] }}% vs last month
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#7c3aed,#6d28d9);"></div>
                <div class="m-label">Returning</div>
                <div class="m-value" style="color:#7c3aed;">{{ $stats['returning_pct'] }}%</div>
                <div class="m-sub">
                    <i class="fa-solid fa-rotate" style="font-size:.65rem;"></i>
                    of monthly patients
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#d97706,#b45309);"></div>
                <div class="m-label">Avg. visits / patient</div>
                <div class="m-value" style="color:#d97706;">{{ number_format($stats['avg_visits'], 1) }}</div>
                <div class="m-sub">
                    <i class="fa-solid fa-calendar-days" style="font-size:.65rem;"></i>
                    per year
                </div>
            </div>
        </div>

        {{-- ===== TREATMENT REPORTS ===== --}}
        <hr class="section-divider">
        <div class="section-label">
            <i class="fa-solid fa-tooth" style="font-size:11px;color:#8B0000;"></i>
            Treatment reports
        </div>

        <div class="metric-grid">
            <div class="metric-card">
                <div class="metric-card-accent"></div>
                <div class="m-label">Total procedures</div>
                <div class="m-value">{{ number_format($treatments['total']) }}</div>
                <div class="m-sub">
                    <i class="fa-regular fa-calendar" style="font-size:.65rem;"></i>
                    this month
                </div>
            </div>

            @php $tColors = ['#378ADD','#1D9E75','#D85A30','#BA7517','#7F77DD','#9ca3af']; @endphp
            @foreach ($treatments['breakdown'] as $i => $proc)
            <div class="metric-card">
                <div class="metric-card-accent" style="background:{{ $tColors[$i] ?? '#9ca3af' }};"></div>
                <div class="m-label">{{ $proc['name'] }}</div>
                <div class="m-value" style="color:{{ $tColors[$i] ?? '#9ca3af' }};">{{ $proc['count'] }}</div>
                <div class="m-sub">
                    <i class="fa-solid fa-percent" style="font-size:.6rem;"></i>
                    {{ $proc['pct'] }}% of total
                </div>
            </div>
            @endforeach
        </div>

        {{-- ===== APPOINTMENT REPORTS ===== --}}
        <hr class="section-divider">
        <div class="section-label">
            <i class="fa-solid fa-calendar-check" style="font-size:11px;color:#8B0000;"></i>
            Appointment reports
        </div>

        <div class="appt-grid">
            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#378ADD,#2563eb);"></div>
                <div class="a-label">Total this month</div>
                <div class="a-value">{{ $appointments['total'] }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:100%;background:#378ADD;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#1D9E75,#16a34a);"></div>
                <div class="a-label">Completed</div>
                <div class="a-value" style="color:#0f6e56;">{{ $appointments['completed'] }}</div>
                <div class="progress-bar">
                    <div class="progress-fill"
                        style="width:{{ $appointments['completion_rate'] }}%;background:#1D9E75;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#D85A30,#c0392b);"></div>
                <div class="a-label">Cancelled</div>
                <div class="a-value" style="color:#993c1d;">{{ $appointments['cancelled'] }}</div>
                <div class="progress-bar">
                    <div class="progress-fill"
                        style="width:{{ $appointments['cancelled_rate'] ?? 0 }}%;background:#D85A30;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#EF9F27,#d97706);"></div>
                <div class="a-label">No-shows</div>
                <div class="a-value" style="color:#854f0b;">{{ $appointments['no_show'] }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $appointments['no_show_rate'] }}%;background:#EF9F27;">
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#378ADD,#2563eb);"></div>
                <div class="a-label">No-show rate</div>
                <div class="a-value">{{ number_format($appointments['no_show_rate'], 1) }}%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $appointments['no_show_rate'] }}%;background:#378ADD;">
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#1D9E75,#16a34a);"></div>
                <div class="a-label">Completion rate</div>
                <div class="a-value" style="color:#0f6e56;">{{ number_format($appointments['completion_rate'], 0) }}%
                </div>
                <div class="progress-bar">
                    <div class="progress-fill"
                        style="width:{{ $appointments['completion_rate'] }}%;background:#1D9E75;"></div>
                </div>
            </div>
        </div>

                {{-- ===== DOCUMENT REQUEST REPORTS ===== --}}
        <hr class="section-divider">

        <div class="section-label">
            <i class="fa-solid fa-file-signature" style="font-size:11px;color:#8B0000;"></i>
            Document request reports
        </div>

        @php
            $docTotal = (int) ($documentRequests['total'] ?? 0);
            $docSafeTotal = max(1, $docTotal);

            $docPending = (int) ($documentRequests['pending'] ?? 0);
            $docApproved = (int) ($documentRequests['approved'] ?? 0);
            $docRejected = (int) ($documentRequests['rejected'] ?? 0);

            $docPendingRate = round(($docPending / $docSafeTotal) * 100, 1);
            $docApprovedRate = round(($docApproved / $docSafeTotal) * 100, 1);
            $docRejectedRate = round(($docRejected / $docSafeTotal) * 100, 1);

            $docApprovalRate = (float) ($documentRequests['approval_rate'] ?? 0);
            $docMostRequested = $documentRequests['most_requested'] ?? 'No requests yet';
            $docMostRequestedCount = (int) ($documentRequests['most_requested_count'] ?? 0);
        @endphp

        <div class="appt-grid">
            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#8B0000,#c0392b);"></div>
                <div class="a-label">Total this month</div>
                <div class="a-value">{{ number_format($docTotal) }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:100%;background:#8B0000;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#EF9F27,#d97706);"></div>
                <div class="a-label">Pending</div>
                <div class="a-value" style="color:#854f0b;">{{ number_format($docPending) }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $docPendingRate }}%;background:#EF9F27;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#1D9E75,#16a34a);"></div>
                <div class="a-label">Approved</div>
                <div class="a-value" style="color:#0f6e56;">{{ number_format($docApproved) }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $docApprovedRate }}%;background:#1D9E75;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#D85A30,#c0392b);"></div>
                <div class="a-label">Rejected</div>
                <div class="a-value" style="color:#993c1d;">{{ number_format($docRejected) }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $docRejectedRate }}%;background:#D85A30;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#378ADD,#2563eb);"></div>
                <div class="a-label">Approval rate</div>
                <div class="a-value" style="color:#2563eb;">{{ number_format($docApprovalRate, 1) }}%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $docApprovalRate }}%;background:#378ADD;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-card-accent" style="background:linear-gradient(90deg,#7c3aed,#6d28d9);"></div>
                <div class="a-label">Most requested</div>
                <div class="a-value"
                    style="color:#7c3aed;font-size:1rem;line-height:1.2;word-break:break-word;">
                    {{ $docMostRequested }}
                </div>
                <div class="m-sub">
                    <i class="fa-solid fa-file-lines" style="font-size:.65rem;"></i>
                    {{ number_format($docMostRequestedCount) }}
                    {{ Str::plural('request', $docMostRequestedCount) }}
                </div>
            </div>
        </div>
        
            {{-- ===== INVENTORY USAGE ===== --}}
            @if (isset($inventory))
                @php
                    $inventoryItems = collect($inventory['items'] ?? []);
                    $inventoryDaysElapsed = max(1, now()->day);

                    $inventoryLowStockCount =
                        (int) ($inventory['low_stock_count'] ??
                            $inventoryItems
                                ->filter(function ($item) {
                                    $inStock = (int) ($item['in_stock'] ?? 0);
                                    $minLevel = (int) ($item['min_level'] ?? 0);

                                    return $minLevel > 0 && $inStock < $minLevel;
                                })
                                ->count());

                    $inventoryTotalUsed = $inventoryItems->sum(fn($item) => (int) ($item['used'] ?? 0));
                    $inventoryTotalStock = $inventoryItems->sum(fn($item) => (int) ($item['in_stock'] ?? 0));

                    $inventoryCriticalCount = $inventoryItems
                        ->filter(function ($item) {
                            $inStock = (int) ($item['in_stock'] ?? 0);
                            $minLevel = (int) ($item['min_level'] ?? 0);

                            return $inStock <= 0 || ($minLevel > 0 && $inStock < $minLevel * 0.5);
                        })
                        ->count();

                    $inventoryReorderUnits = $inventoryItems->sum(function ($item) {
                        $used = (int) ($item['used'] ?? 0);
                        $inStock = (int) ($item['in_stock'] ?? 0);
                        $minLevel = (int) ($item['min_level'] ?? 0);
                        $targetStock = max($minLevel * 2, $used);

                        return max(0, $targetStock - $inStock);
                    });
                @endphp

                <hr class="section-divider">
                <div class="section-label">
                    <i class="fa-solid fa-boxes-stacked" style="font-size:11px;color:#8B0000;"></i>
                    Inventory usage
                </div>

                @if ($inventoryLowStockCount > 0)
                    <div class="alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"
                            style="color:#BA7517;font-size:13px;flex-shrink:0;"></i>
                        <span>
                            <strong>{{ $inventoryLowStockCount }}
                                {{ Str::plural('item', $inventoryLowStockCount) }}</strong>
                            below minimum stock threshold — review inventory before next clinic day.
                        </span>
                    </div>
                    @endif

                    <div class="card report-inventory-card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-box-open"></i></div>
                                <div>
                                    <div class="card-title">Dental supplies usage</div>
                                    <div class="card-subtitle">Units consumed this month vs available stock</div>
                                </div>
                            </div>
                        </div>

                    @if ($inventoryItems->count())
                        <div class="report-inventory-table-wrap">
                            <table class="inv-table report-inventory-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Used</th>
                                        <th>In stock</th>
                                        <th>Min. level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventoryItems as $item)
                                        <tr>
                                            <td class="item-cell">{{ $item['name'] }}</td>
                                            <td>{{ $item['used'] }}</td>
                                            <td>{{ $item['in_stock'] }}</td>
                                            <td>{{ $item['min_level'] }}</td>
                                            <td>
                                                @if ($item['in_stock'] <= 0)
                                                    <span class="status-pill report-stock-pill pill-out">Out of
                                                        stock</span>
                                                @elseif($item['in_stock'] < $item['min_level'] * 0.5)
                                                    <span class="status-pill report-stock-pill pill-critical">Critical
                                                        level</span>
                                                @elseif($item['in_stock'] < $item['min_level'])
                                                    <span class="status-pill report-stock-pill pill-low">Below minimum
                                                        level</span>
                                                        @else
                                                        <span class="status-pill report-stock-pill pill-ok">Sufficient
                                                            stock</span>
                                                        @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <div class="empty-state-title">No dental supply records available</div>
                            <div class="empty-state-text">There are no inventory usage records to display yet.</div>
                        </div>
                    @endif
                </div>

                <div class="card report-inventory-card report-movement-card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                            <div>
                                <div class="card-title">Stock movement & reorder forecast</div>
                                <div class="card-subtitle">Estimated movement, daily consumption, days remaining,
                                    and suggested reorder quantity</div>
                            </div>
                        </div>
                    </div>

                    @if ($inventoryItems->count())
                        <div class="inventory-kpi-grid">
                            <div class="inventory-kpi-card kpi-used">
                                <span class="inventory-kpi-label">Total used</span>
                                <strong>{{ number_format($inventoryTotalUsed) }}</strong>
                                <small>This month</small>
                            </div>

                            <div class="inventory-kpi-card kpi-stock">
                                <span class="inventory-kpi-label">Available stock</span>
                                <strong>{{ number_format($inventoryTotalStock) }}</strong>
                                <small>Units remaining</small>
                            </div>

                            <div class="inventory-kpi-card kpi-critical">
                                <span class="inventory-kpi-label">Critical items</span>
                                <strong>{{ number_format($inventoryCriticalCount) }}</strong>
                                <small>Need urgent review</small>
                            </div>

                            <div class="inventory-kpi-card kpi-reorder">
                                <span class="inventory-kpi-label">Suggested reorder</span>
                                <strong>{{ number_format($inventoryReorderUnits) }}</strong>
                                <small>Total units</small>
                            </div>
                        </div>

                        <div class="forecast-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Opening stock is estimated from current stock and monthly usage unless actual
                            opening/restock data is already provided by the inventory records.
                        </div>

                        <div class="report-inventory-table-wrap report-movement-table-wrap">
                            <table class="inv-table report-movement-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Opening stock</th>
                                        <th>Stock in</th>
                                        <th>Used</th>
                                        <th>Ending stock</th>
                                        <th>Avg/day</th>
                                        <th>Days left</th>
                                        <th>Suggested reorder</th>
                                        <th>Reorder status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventoryItems as $item)
                                        @php
                                            $used = (int) ($item['used'] ?? 0);
                                            $inStock = (int) ($item['in_stock'] ?? 0);
                                            $minLevel = (int) ($item['min_level'] ?? 0);
                                            $stockIn =
                                                (int) ($item['stock_in'] ??
                                                    ($item['restocked'] ?? ($item['received'] ?? 0)));
                                            $openingStock =
                                                (int) ($item['opening_stock'] ?? max(0, $inStock + $used - $stockIn));
                                            $averageDailyUsage = $used > 0 ? $used / $inventoryDaysElapsed : 0;
                                            $daysRemaining =
                                                $averageDailyUsage > 0
                                                    ? (int) floor($inStock / $averageDailyUsage)
                                                    : null;
                                            $targetStock = max($minLevel * 2, $used);
                                            $suggestedReorder = max(0, $targetStock - $inStock);
                                            $reorderClass = 'pill-ok';
                                            $reorderLabel = 'Stable';

                                            if ($inStock <= 0) {
                                                $reorderClass = 'pill-out';
                                                $reorderLabel = 'Out of stock';
                                            } elseif ($minLevel > 0 && $inStock < $minLevel * 0.5) {
                                                $reorderClass = 'pill-critical';
                                                $reorderLabel = 'Critical reorder';
                                            } elseif (
                                                ($minLevel > 0 && $inStock < $minLevel) ||
                                                (!is_null($daysRemaining) && $daysRemaining <= 7)
                                            ) {
                                                $reorderClass = 'pill-low';
                                                $reorderLabel = 'Reorder soon';
                                            } elseif (!is_null($daysRemaining) && $daysRemaining <= 14) {
                                                $reorderClass = 'pill-watch';
                                                $reorderLabel = 'Monitor stock';
                                            }
                                        @endphp <tr>
                                            <td class="item-cell" data-label="Item">{{ $item['name'] }}</td>
                                            <td class="forecast-num forecast-opening" data-label="Opening stock">
                                                {{ number_format($openingStock) }}</td>
                                            <td class="forecast-num forecast-stock-in" data-label="Stock in">
                                                {{ $stockIn > 0 ? number_format($stockIn) : '—' }}</td>
                                            <td class="forecast-num forecast-used" data-label="Used">
                                                {{ number_format($used) }}</td>
                                            <td class="forecast-num forecast-ending" data-label="Ending stock">
                                                {{ number_format($inStock) }}</td>
                                            <td class="forecast-num forecast-average" data-label="Avg/day">
                                                {{ number_format($averageDailyUsage, 1) }}</td>
                                            <td class="forecast-days" data-label="Days left">
                                                {{ is_null($daysRemaining)
                                                    ? 'No usage yet'
                                                    : number_format($daysRemaining) . ' ' . Str::plural('day', $daysRemaining) }}
                                            </td>
                                            <td class="forecast-num forecast-reorder" data-label="Suggested reorder">
                                                {{ number_format($suggestedReorder) }}</td>
                                            <td data-label="Reorder status">
                                                <span
                                                    class="status-pill report-stock-pill {{ $reorderClass }}">{{ $reorderLabel }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fa-solid fa-truck-ramp-box"></i>
                            </div>
                            <div class="empty-state-title">No stock movement data available</div>
                            <div class="empty-state-text">There are no inventory records available for reorder
                                forecasting yet.</div>
                        </div>
                    @endif
                </div>
            @endif

                    {{-- ===== CHARTS ===== --}}
                    <hr class="section-divider">
                    <div class="section-label">
                        <i class="fa-solid fa-chart-column" style="font-size:11px;color:#8B0000;"></i>
                        Charts & visualization
                    </div>

                    <div class="chart-grid">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><i class="fa-solid fa-chart-bar"></i></div>
                                    <div>
                                        <div class="card-title">Treatments per month</div>
                                        <div class="card-subtitle">Number of procedures performed</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-legend">
                                    <span><span class="legend-dot" style="background:#378ADD;"></span>Procedures</span>
                                </div>
                                <div class="chart-wrap" style="height:220px;">
                                    <canvas id="barChart" role="img"
                                        aria-label="Bar chart of treatments per month"></canvas>
                                </div>
                            </div>
                        </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon"><i class="fa-solid fa-chart-pie"></i></div>
                            <div>
                                <div class="card-title">Procedure types</div>
                                <div class="card-subtitle">Distribution of procedures this month</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (collect($treatments['breakdown'])->count())
                            <div class="chart-legend">
                                @foreach ($treatments['breakdown'] as $i => $proc)
                                    @php$colors = [
                                            '#378ADD',
                                            '#1D9E75',
                                            '#D85A30',
                                            '#BA7517',
                                            '#7F77DD',
                                            '#9ca3af',
                                        ];
                                    @endphp
                                    <span>
                                        <span class="legend-dot"
                                            style="background:{{ $colors[$i] ?? '#9ca3af' }};border-radius:50%;"></span>
                                        {{ $proc['name'] }} {{ $proc['pct'] }}%
                                    </span>
                                @endforeach
                            </div>
                            <div class="chart-wrap" style="height:220px;">
                                <canvas id="pieChart" role="img"
                                    aria-label="Pie chart of procedure type distribution"></canvas>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fa-solid fa-chart-pie"></i>
                                </div>
                                <div class="empty-state-title">No procedure data available</div>
                                <div class="empty-state-text">There are no recorded procedure types to display yet.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

                    <div class="card" style="margin-bottom:2rem;">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-chart-line"></i></div>
                                <div>
                                    <div class="card-title">Patient growth</div>
                                    <div class="card-subtitle">Cumulative and new patients over time</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-legend">
                                <span><span class="legend-dot"
                                        style="background:#378ADD;border-radius:50%;"></span>Total
                                    patients</span>
                                <span><span class="legend-dot" style="background:#1D9E75;border-radius:50%;"></span>New
                                    patients</span>
                            </div>
                            <div class="chart-wrap" style="height:240px;">
                                <canvas id="lineChart" role="img"
                                    aria-label="Line chart showing patient growth over time"></canvas>
                            </div>
                        </div>
                    </div>

    </div>
</main>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const textColor = 'rgba(107,114,128,1)';
        const gridColor = 'rgba(0,0,0,0.06)';

        const sharedScales = {
            x: {
                ticks: {
                    color: textColor,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: gridColor
                },
                border: {
                    display: false
                }
            },
            y: {
                ticks: {
                    color: textColor,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: gridColor
                },
                border: {
                    display: false
                }
            }
        };

        const barLabels = @json($charts['bar']['labels']);
        const barData = @json($charts['bar']['data']);

        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{
                    label: 'Procedures',
                    data: barData,
                    backgroundColor: '#378ADD',
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: sharedScales
            }
        });

        const pieLabels = @json($charts['pie']['labels']);
        const pieData = @json($charts['pie']['data']);
        const pieColors = ['#378ADD', '#1D9E75', '#D85A30', '#BA7517', '#7F77DD', '#9ca3af'];

        if (document.getElementById('pieChart') && pieLabels.length && pieData.length) {
            new Chart(document.getElementById('pieChart'), {
                type: 'doughnut',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: pieColors.slice(0, pieLabels.length),
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverOffset: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (c) => ' ' + c.label + ': ' + c.formattedValue
                            }
                        }
                    }
                }
            });
        }

        const lineLabels = @json($charts['line']['labels']);
        const lineTotals = @json($charts['line']['totals']);
        const lineNew = @json($charts['line']['new_patients']);

        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: lineLabels,
                datasets: [{
                    label: 'Total patients',
                    data: lineTotals,
                    borderColor: '#378ADD',
                    backgroundColor: 'rgba(55,138,221,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#378ADD',
                    borderWidth: 2
                },
                {
                    label: 'New patients',
                    data: lineNew,
                    borderColor: '#1D9E75',
                    backgroundColor: 'rgba(29,158,117,0.07)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#1D9E75',
                    borderWidth: 2,
                    borderDash: [5, 3]
                }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: textColor,
                            font: {
                                size: 11
                            },
                            maxRotation: 0
                        },
                        grid: {
                            color: gridColor
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        ticks: {
                            color: textColor,
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: gridColor
                        },
                        border: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endpush