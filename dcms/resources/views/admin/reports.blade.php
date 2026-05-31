@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    <main id="mainContent" class="admin-page-shell page-enter mode-list">
        <div class="w-full">

            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Reports & Analytics</h1>
                    </div>
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
                        <div class="progress-fill"
                            style="width:{{ $appointments['no_show_rate'] }}%;background:#EF9F27;">
                        </div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-card-accent" style="background:linear-gradient(90deg,#378ADD,#2563eb);"></div>
                    <div class="a-label">No-show rate</div>
                    <div class="a-value">{{ number_format($appointments['no_show_rate'], 1) }}%</div>
                    <div class="progress-bar">
                        <div class="progress-fill"
                            style="width:{{ $appointments['no_show_rate'] }}%;background:#378ADD;">
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

            {{-- ===== INVENTORY USAGE ===== --}}
            @if (isset($inventory))
                <hr class="section-divider">
                <div class="section-label">
                    <i class="fa-solid fa-boxes-stacked" style="font-size:11px;color:#8B0000;"></i>
                    Inventory usage
                </div>

                @if ($inventory['low_stock_count'] > 0)
                    <div class="alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"
                            style="color:#BA7517;font-size:13px;flex-shrink:0;"></i>
                        <span>
                            <strong>{{ $inventory['low_stock_count'] }}
                                {{ Str::plural('item', $inventory['low_stock_count']) }}</strong>
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

                    @if (collect($inventory['items'])->count())
                        <div style="overflow-x:auto;">
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
                                    @foreach ($inventory['items'] as $item)
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
                                    @php $colors = ['#378ADD','#1D9E75','#D85A30','#BA7517','#7F77DD','#9ca3af']; @endphp
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
                                <div class="empty-state-text">There are no recorded procedure types to display yet.</div>
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
                        <span><span class="legend-dot" style="background:#378ADD;border-radius:50%;"></span>Total
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
        document.addEventListener('DOMContentLoaded', function() {
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
