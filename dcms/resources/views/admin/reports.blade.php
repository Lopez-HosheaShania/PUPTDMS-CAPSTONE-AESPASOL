@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('styles')
<style>
    * { box-sizing: border-box; }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #bbb; }

    .page-banner {
        background: linear-gradient(135deg, #6b0000 0%, #8B0000 60%, #c0392b 100%);
        padding: 1.75rem 2rem 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(139, 0, 0, .25);
        border-radius: 16px;
        margin-bottom: 1.5rem;
    }

    .page-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .page-banner-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 900;
        color: #fff;
        line-height: 1.1;
        letter-spacing: -.02em;
    }

    .page-actions {
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .section-label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: .75rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .section-label::before {
        content: '';
        display: inline-block;
        width: 3px;
        height: 14px;
        border-radius: 2px;
        background: linear-gradient(180deg, #8B0000, #6b0000);
    }

    hr.section-divider {
        border: none;
        border-top: 1px solid #f3f4f6;
        margin: 1.75rem 0 1.25rem;
    }

    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .metric-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.25rem 1.4rem;
        border: 1px solid rgba(0,0,0,.05);
        box-shadow: 0 4px 20px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04);
        transition: transform .2s, box-shadow .2s;
        position: relative;
        overflow: hidden;
    }

    .metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(0,0,0,.1), 0 2px 6px rgba(0,0,0,.05);
    }

    .metric-card-accent {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #8B0000, #c0392b);
    }

    .m-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #9ca3af;
        margin-bottom: .35rem;
    }

    .m-value {
        font-size: 2.1rem;
        font-weight: 900;
        line-height: 1;
        color: #1a202c;
        letter-spacing: -.03em;
        margin-bottom: .4rem;
    }

    .m-sub {
        font-size: .7rem;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    .m-sub.up { color: #0f6e56; }
    .m-sub.down { color: #993c1d; }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.25rem;
    }

    .card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,.05);
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        overflow: hidden;
        animation: fadeSlideUp .4s ease .2s both;
    }

    .card-header {
        padding: .9rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fafafa;
    }

    .card-header-left {
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .card-header-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #fdf1f1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #8B0000;
    }

    .card-title {
        font-size: .82rem;
        font-weight: 800;
        color: #1a202c;
    }

    .card-sub {
        font-size: .7rem;
        color: #9ca3af;
        margin-top: 2px;
    }

    .card-body {
        padding: 1.25rem;
    }

    .chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 12px;
        font-size: .7rem;
        color: #6b7280;
    }

    .chart-legend span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 2px;
        flex-shrink: 0;
    }

    .chart-wrap {
        position: relative;
    }

    .appt-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .appt-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,.05);
        box-shadow: 0 4px 20px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04);
        padding: 1.25rem 1.4rem;
        transition: transform .2s, box-shadow .2s;
        position: relative;
        overflow: hidden;
    }

    .appt-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(0,0,0,.1);
    }

    .appt-card-accent {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .a-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #9ca3af;
        margin-bottom: .35rem;
    }

    .a-value {
        font-size: 2.1rem;
        font-weight: 900;
        line-height: 1;
        color: #1a202c;
        letter-spacing: -.03em;
        margin-bottom: .4rem;
    }

    .progress-bar {
        height: 4px;
        border-radius: 2px;
        background: #f3f4f6;
        margin-top: 10px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        border-radius: 2px;
    }

    .alert-warning {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #FAEEDA;
        border: 1px solid #FAC775;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: .76rem;
        color: #633806;
        margin-bottom: 1.25rem;
    }

    .inv-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .8rem;
    }

    .inv-table th {
        text-align: left;
        font-size: .68rem;
        font-weight: 700;
        color: #fff; 
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: .75rem 1rem;
        border-bottom: none;
        background: linear-gradient(90deg, #8B0000, #c0392b); 
        box-shadow: inset 0 -1px 0 rgba(255,255,255,0.1);
    }

    .inv-table td {
        padding: .8rem 1rem;
        border-bottom: 1px solid #f9fafb;
        color: #4a5568;
    }

    .item-cell {
        font-weight: 600;
        color: #1a202c;
        background: transparent; 
        border-left: none; 
    }

    .inv-table tr:last-child td {
        border-bottom: none;
    }

    .inv-table tbody tr:hover td {
        background: #fafafa;
    }

    .status-pill {
        display: inline-block;
        font-size: .68rem;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 700;
    }

    .pill-ok { background: #EAF3DE; color: #3B6D11; }
    .pill-low { background: #FAEEDA; color: #854F0B; }
    .pill-critical { background: #FCEBEB; color: #A32D2D; }

    [data-theme="dark"] .card {
        background: #161b22 !important;
        border-color: #21262d !important;
    }

    [data-theme="dark"] .metric-card {
        background:
            radial-gradient(circle at bottom right, rgba(255,255,255,.08), transparent 40%),
            linear-gradient(135deg, rgba(22,27,34,.65), rgba(13,17,23,.55)) !important;

        border: 1px solid rgba(255,255,255,.10) !important;

        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.10),
            0 0 0 1px rgba(255,255,255,.03),
            0 10px 24px rgba(0,0,0,.35) !important;
    }

    [data-theme="dark"] .appt-card {
        background:
            radial-gradient(circle at bottom right, rgba(255,255,255,.07), transparent 40%),
            linear-gradient(135deg, rgba(22,27,34,.65), rgba(13,17,23,.55)) !important;

        border: 1px solid rgba(255,255,255,.10) !important;

        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);

        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.10),
            0 10px 24px rgba(0,0,0,.35) !important;
    }

    [data-theme="dark"] .metric-card:hover,
    [data-theme="dark"] .appt-card:hover {
        border-color: rgba(255,255,255,.16) !important;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.12),
            0 0 18px rgba(255,255,255,.04),
            0 14px 28px rgba(0,0,0,.45) !important;
    }

    [data-theme="dark"] .card-header {
        background: #0d1117 !important;
        border-color: #21262d !important;
    }

    [data-theme="dark"] .card-header-icon {
        background: rgba(255,255,255,0.06) !important;
        border: 1px solid rgba(255,255,255,0.10);
        color: #FCA5A5 !important;

        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }

    [data-theme="dark"] .card-title {
        color: #FCA5A5 !important;
    }


    [data-theme="dark"] .card-title,
    [data-theme="dark"] .m-value,
    [data-theme="dark"] .a-value {
        color: #f3f4f6;
    }

    [data-theme="dark"] .inv-table th {
        background: #0d1117;
        color: #6b7280;
        border-color: #21262d;
    }

    [data-theme="dark"] .inv-table td {
        color: #d1d5db;
        border-color: #1c2128;
    }

    [data-theme="dark"] .inv-table tbody tr:hover td {
        background: #1c2128;
    }

    [data-theme="dark"] hr.section-divider {
        border-color: #21262d;
    }

    @media (max-width: 767px) {
        #mainContent {
            padding-left: .75rem !important;
            padding-right: .75rem !important;
            padding-top: 78px !important;
            padding-bottom: 1rem !important;
        }

        .page-banner {
            padding: 1.5rem 1rem 1.75rem;
        }

        .chart-grid {
            grid-template-columns: 1fr;
        }

        .page-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }

    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .metric-card { animation: fadeSlideUp .4s ease both; }
    .metric-card:nth-child(1) { animation-delay: .05s; }
    .metric-card:nth-child(2) { animation-delay: .10s; }
    .metric-card:nth-child(3) { animation-delay: .15s; }
    .metric-card:nth-child(4) { animation-delay: .20s; }
    .metric-card:nth-child(5) { animation-delay: .25s; }

    .appt-card { animation: fadeSlideUp .4s ease both; }
    .appt-card:nth-child(1) { animation-delay: .05s; }
    .appt-card:nth-child(2) { animation-delay: .10s; }
    .appt-card:nth-child(3) { animation-delay: .15s; }
    .appt-card:nth-child(4) { animation-delay: .20s; }
    .appt-card:nth-child(5) { animation-delay: .25s; }
    .appt-card:nth-child(6) { animation-delay: .30s; }

    .empty-state {
        min-height: 220px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem 1.25rem;
        color: #9ca3af;
    }

    .empty-state-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: #f9fafb;
        border: 1px solid #eceff3;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: .9rem;
        color: #c08484;
        font-size: 1.2rem;
    }

    [data-theme="dark"] .empty-state-icon {
        background: rgba(255,255,255,0.06) !important;
        border: 1px solid rgba(255,255,255,0.10);
        color: #FCA5A5;

        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }

    .empty-state-title {
        font-size: .9rem;
        font-weight: 800;
        color: #6b7280;
        margin-bottom: .25rem;
    }

    .empty-state-text {
        font-size: .75rem;
        color: #9ca3af;
        max-width: none;      
        white-space: nowrap;   
    }
</style>
@endsection

@section('content')
<main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-screen">
    <div class="max-w-[1280px] mx-auto">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Reports & Analytics</h1>
                </div>
            </div>
        </div>

        {{-- ===== PATIENT STATISTICS ===== --}}
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
            @foreach($treatments['breakdown'] as $i => $proc)
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
            <div class="appt-card">
                <div class="appt-card-accent" style="background:linear-gradient(90deg,#378ADD,#2563eb);"></div>
                <div class="a-label">Total this month</div>
                <div class="a-value">{{ $appointments['total'] }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:100%;background:#378ADD;"></div>
                </div>
            </div>

            <div class="appt-card">
                <div class="appt-card-accent" style="background:linear-gradient(90deg,#1D9E75,#16a34a);"></div>
                <div class="a-label">Completed</div>
                <div class="a-value" style="color:#0f6e56;">{{ $appointments['completed'] }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $appointments['completion_rate'] }}%;background:#1D9E75;"></div>
                </div>
            </div>

            <div class="appt-card">
                <div class="appt-card-accent" style="background:linear-gradient(90deg,#D85A30,#c0392b);"></div>
                <div class="a-label">Cancelled</div>
                <div class="a-value" style="color:#993c1d;">{{ $appointments['cancelled'] }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $appointments['cancelled_rate'] ?? 0 }}%;background:#D85A30;"></div>
                </div>
            </div>

            <div class="appt-card">
                <div class="appt-card-accent" style="background:linear-gradient(90deg,#EF9F27,#d97706);"></div>
                <div class="a-label">No-shows</div>
                <div class="a-value" style="color:#854f0b;">{{ $appointments['no_show'] }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $appointments['no_show_rate'] }}%;background:#EF9F27;"></div>
                </div>
            </div>

            <div class="appt-card">
                <div class="appt-card-accent" style="background:linear-gradient(90deg,#378ADD,#2563eb);"></div>
                <div class="a-label">No-show rate</div>
                <div class="a-value">{{ number_format($appointments['no_show_rate'], 1) }}%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $appointments['no_show_rate'] }}%;background:#378ADD;"></div>
                </div>
            </div>

            <div class="appt-card">
                <div class="appt-card-accent" style="background:linear-gradient(90deg,#1D9E75,#16a34a);"></div>
                <div class="a-label">Completion rate</div>
                <div class="a-value" style="color:#0f6e56;">{{ number_format($appointments['completion_rate'], 0) }}%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:{{ $appointments['completion_rate'] }}%;background:#1D9E75;"></div>
                </div>
            </div>
        </div>

        {{-- ===== INVENTORY USAGE ===== --}}
        @if(isset($inventory))
        <hr class="section-divider">
        <div class="section-label">
            <i class="fa-solid fa-boxes-stacked" style="font-size:11px;color:#8B0000;"></i>
            Inventory usage
        </div>

        @if($inventory['low_stock_count'] > 0)
        <div class="alert-warning">
            <i class="fa-solid fa-triangle-exclamation" style="color:#BA7517;font-size:13px;flex-shrink:0;"></i>
            <span>
                <strong>{{ $inventory['low_stock_count'] }} {{ Str::plural('item', $inventory['low_stock_count']) }}</strong>
                below minimum stock threshold — review inventory before next clinic day.
            </span>
        </div>
        @endif

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <div class="card-header-left">
                    <div class="card-header-icon"><i class="fa-solid fa-box-open"></i></div>
                    <div>
                        <div class="card-title">Dental supplies usage</div>
                        <div class="card-sub">Units consumed this month vs available stock</div>
                    </div>
                </div>
            </div>

            @if(collect($inventory['items'])->count())
                <div style="overflow-x:auto;">
                    <table class="inv-table">
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
                            @foreach($inventory['items'] as $item)
                            <tr>
                                <td class="item-cell">{{ $item['name'] }}</td>
                                <td>{{ $item['used'] }}</td>
                                <td>{{ $item['in_stock'] }}</td>
                                <td>{{ $item['min_level'] }}</td>
                                <td>
                                    @if($item['in_stock'] <= 0)
                                        <span class="status-pill pill-critical">Out of Stock</span>
                                    @elseif($item['in_stock'] < $item['min_level'])
                                        <span class="status-pill pill-{{ $item['in_stock'] < $item['min_level'] * 0.5 ? 'critical' : 'low' }}">
                                            {{ $item['in_stock'] < $item['min_level'] * 0.5 ? 'Critical Level' : 'Below Minimum Level' }}
                                        </span>
                                    @else
                                        <span class="status-pill pill-ok">Sufficient Stock</span>
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
                            <div class="card-sub">Number of procedures performed</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-legend">
                        <span><span class="legend-dot" style="background:#378ADD;"></span>Procedures</span>
                    </div>
                    <div class="chart-wrap" style="height:220px;">
                        <canvas id="barChart" role="img" aria-label="Bar chart of treatments per month"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <div class="card-header-icon"><i class="fa-solid fa-chart-pie"></i></div>
                        <div>
                            <div class="card-title">Procedure types</div>
                            <div class="card-sub">Distribution of procedures this month</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(collect($treatments['breakdown'])->count())
                        <div class="chart-legend">
                            @foreach($treatments['breakdown'] as $i => $proc)
                            @php $colors = ['#378ADD','#1D9E75','#D85A30','#BA7517','#7F77DD','#9ca3af']; @endphp
                            <span>
                                <span class="legend-dot" style="background:{{ $colors[$i] ?? '#9ca3af' }};border-radius:50%;"></span>
                                {{ $proc['name'] }} {{ $proc['pct'] }}%
                            </span>
                            @endforeach
                        </div>
                        <div class="chart-wrap" style="height:220px;">
                            <canvas id="pieChart" role="img" aria-label="Pie chart of procedure type distribution"></canvas>
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
                        <div class="card-sub">Cumulative and new patients over time</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-legend">
                    <span><span class="legend-dot" style="background:#378ADD;border-radius:50%;"></span>Total patients</span>
                    <span><span class="legend-dot" style="background:#1D9E75;border-radius:50%;"></span>New patients</span>
                </div>
                <div class="chart-wrap" style="height:240px;">
                    <canvas id="lineChart" role="img" aria-label="Line chart showing patient growth over time"></canvas>
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
            ticks: { color: textColor, font: { size: 11 } },
            grid: { color: gridColor },
            border: { display: false }
        },
        y: {
            ticks: { color: textColor, font: { size: 11 } },
            grid: { color: gridColor },
            border: { display: false }
        }
    };

    const barLabels = @json($charts['bar']['labels']);
    const barData   = @json($charts['bar']['data']);

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
            plugins: { legend: { display: false } },
            scales: sharedScales
        }
    });

    const pieLabels = @json($charts['pie']['labels']);
    const pieData   = @json($charts['pie']['data']);
    const pieColors = ['#378ADD','#1D9E75','#D85A30','#BA7517','#7F77DD','#9ca3af'];

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
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: (c) => ' ' + c.label + ': ' + c.formattedValue }
                    }
                }
            }
        });
    }

    const lineLabels = @json($charts['line']['labels']);
    const lineTotals = @json($charts['line']['totals']);
    const lineNew    = @json($charts['line']['new_patients']);

    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [
                {
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
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: {
                    ticks: { color: textColor, font: { size: 11 }, maxRotation: 0 },
                    grid: { color: gridColor },
                    border: { display: false }
                },
                y: {
                    ticks: { color: textColor, font: { size: 11 } },
                    grid: { color: gridColor },
                    border: { display: false }
                }
            }
        }
    });
});
</script>
@endpush