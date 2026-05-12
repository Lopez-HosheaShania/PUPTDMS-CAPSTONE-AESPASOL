@extends('layouts.admin')

@section('title', 'Dental Records | PUP Taguig Dental Clinic')

@section('styles')
<style>
    .dental-records-page {
        margin-left: var(--sidebar-w, 256px);
        padding: calc(var(--header-h, 70px) + 12px) 1.5rem 2rem;
        min-height: 100vh;
        background: #f6f7f9;
        overflow-x: hidden;
    }

    /* ── Page Banner ── */
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

    /* ── Stats Grid ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 1.75rem;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        padding: 1.1rem 1.1rem 1rem;
        padding-top: 2.3rem;
        position: relative;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .07);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 14px 14px 0 0;
    }

    .stat-card.total::before    { background: #e5e7eb; }
    .stat-card.today::before    { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .stat-card.pending::before  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

    .stat-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        margin-bottom: .75rem;
    }

    .stat-card.total .stat-icon   { background: #f3f4f6; color: #6b7280; }
    .stat-card.today .stat-icon   { background: #dbeafe; color: #2563eb; }
    .stat-card.pending .stat-icon { background: #fef3c7; color: #d97706; }

    .stat-val {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -.03em;
        margin-bottom: .25rem;
    }

    .stat-card.total .stat-val   { color: #111827; }
    .stat-card.today .stat-val   { color: #2563eb; }
    .stat-card.pending .stat-val { color: #d97706; }

    .stat-lbl {
        font-size: .67rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #9ca3af;
    }

    .stat-trend {
        position: absolute;
        top: .7rem;
        right: .7rem;
        font-size: .62rem;
        font-weight: 700;
        padding: .22rem .5rem;
        border-radius: 999px;
        z-index: 2;
    }

    .stat-card.pending .stat-trend { background: #fef3c7; color: #92400e; }

    /* ── Toolbar ── */
    .toolbar {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .search-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #FAFAF9;
        border: 1.5px solid #E0DDD8;
        border-radius: 12px;
        padding: 0 14px;
        height: 38px;
        transition: border-color .2s, box-shadow .2s;
        flex: 1;
        min-width: 200px;
        max-width: 320px;
    }

    .search-wrap:focus-within {
        border-color: #8B0000;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
    }

    .search-wrap .search-icon {
        color: #8B0000;
        font-size: 13px;
        flex-shrink: 0;
        pointer-events: none;
    }

    .search-wrap input {
        border: none;
        background: none;
        outline: none;
        font-size: 13px;
        color: #333;
        width: 100%;
        padding: 0;
    }

    .search-wrap input::placeholder { color: #B0ABA6; }

    /* ── Buttons ── */
    .btn-primary,
    .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: .15s ease;
    }

    .btn-primary {
        padding: .52rem 1.1rem;
        border: none;
        background: #8B0000;
        color: #fff;
    }

    .btn-primary:hover { background: #6b0000; }

    .btn-secondary {
        padding: .5rem 1rem;
        background: rgba(255,255,255,.15);
        color: #fff;
        border: 1px solid rgba(255,255,255,.25);
    }

    .btn-secondary:hover { background: rgba(255,255,255,.25); }

    /* ── Table wrapper ── */
    .tbl-wrap {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        overflow: hidden;
    }

    .tbl {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .tbl th {
        padding: .7rem 1rem;
        font-size: .67rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .08em;
        text-align: left;
        background: #fafafa;
        border-bottom: 1px solid #f3f4f6;
        white-space: nowrap;
    }

    .tbl td {
        padding: .65rem .75rem;
        font-size: .8rem;
        color: #374151;
        border-bottom: 1px solid #f8f5f5;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tbl td.wrap { white-space: normal; }

    .tbl tbody tr:last-child td { border-bottom: none; }
    .tbl tbody tr:hover td { background: #fffafa; }

    /* Col widths */
    .tbl th:nth-child(1), .tbl td:nth-child(1) { width: 22%; }
    .tbl th:nth-child(2), .tbl td:nth-child(2) { width: 22%; }
    .tbl th:nth-child(3), .tbl td:nth-child(3) { width: 18%; }
    .tbl th:nth-child(4), .tbl td:nth-child(4) { width: 14%; }
    .tbl th:nth-child(5), .tbl td:nth-child(5) { width: 12%; }
    .tbl th:nth-child(6), .tbl td:nth-child(6) { width: 12%; text-align: right; }

    /* Status pills */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .65rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-pill.completed { background: #d1fae5; color: #065f46; }
    .status-pill.pending   { background: #fef3c7; color: #92400e; }
    .status-pill.ongoing   { background: #dbeafe; color: #1e40af; }

    .act-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: .7rem;
        transition: .12s ease;
        text-decoration: none;
    }

    .act-btn.view { background: #fef2f2; color: #8B0000; }
    .act-btn.view:hover { background: #8B0000; color: #fff; }

    /* ── Panel ── */
    .content-grid {
        display: grid;
        grid-template-columns: minmax(0,1fr) 320px;
        gap: 1rem;
        align-items: start;
    }

    .panel-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        overflow: hidden;
        position: sticky;
        top: calc(var(--header-h, 70px) + 12px);
    }

    .panel-header {
        padding: .9rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        background: #fafafa;
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .panel-header-icon {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: linear-gradient(135deg, #8B0000, #6b0000);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* ── Insights (side panel bottom card) ── */
    .insights-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        overflow: hidden;
        margin-top: 1rem;
    }

    .insight-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .8rem 1.25rem;
        border-bottom: 1px solid #f8f5f5;
    }

    .insight-row:last-child { border-bottom: none; }

    .insight-lbl {
        font-size: .67rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #9ca3af;
        margin-bottom: .2rem;
    }

    .insight-val {
        font-size: .9rem;
        font-weight: 800;
        color: #111827;
    }

    /* ── Empty state ── */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .empty-state i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 1rem;
        color: #e5e7eb;
    }

    /* ── Quick Actions ── */
    .quick-action {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .8rem 1rem;
        border-bottom: 1px solid #f8f5f5;
        text-decoration: none;
        transition: background .12s ease;
    }

    .quick-action:last-child { border-bottom: none; }

    .quick-action:hover { background: #fffafa; }

    .quick-action-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #fef2f2;
        color: #8B0000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        flex-shrink: 0;
        transition: .12s ease;
    }

    .quick-action:hover .quick-action-icon {
        background: #8B0000;
        color: #fff;
    }

    .quick-action-title {
        font-size: .8rem;
        font-weight: 700;
        color: #111827;
    }

    .quick-action-sub {
        font-size: .67rem;
        color: #9ca3af;
        margin-top: 1px;
    }

    .quick-action-arrow {
        margin-left: auto;
        color: #d1d5db;
        font-size: .65rem;
        transition: .12s ease;
    }

    .quick-action:hover .quick-action-arrow { color: #8B0000; }

    /* ── Dark mode ── */
    [data-theme="dark"] .dental-records-page {
        background: #0b1117 !important;
    }

    [data-theme="dark"] .tbl-wrap,
    [data-theme="dark"] .panel-card,
    [data-theme="dark"] .insights-card {
        background: #161b22 !important;
        border-color: #21262d !important;
    }

    [data-theme="dark"] .stat-card {
        background:
            radial-gradient(circle at bottom right, rgba(255,255,255,.07), transparent 42%),
            linear-gradient(135deg, rgba(22, 27, 34, .68), rgba(17, 24, 39, .50)) !important;
        border: 1px solid rgba(255,255,255,.10) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.10),
            0 0 0 1px rgba(255,255,255,.025),
            0 8px 18px rgba(0,0,0,.16) !important;
    }

    [data-theme="dark"] .stat-card:hover {
        border-color: rgba(255,255,255,.16) !important;
    }

    [data-theme="dark"] .stat-card.total .stat-icon   { background: #21262d !important; color: #9ca3af !important; }
    [data-theme="dark"] .stat-card.today .stat-icon   { background: rgba(59,130,246,.12) !important; color: #60a5fa !important; }
    [data-theme="dark"] .stat-card.pending .stat-icon { background: rgba(245,158,11,.12) !important; color: #fbbf24 !important; }

    [data-theme="dark"] .stat-card.total .stat-val   { color: #f3f4f6 !important; }
    [data-theme="dark"] .stat-card.today .stat-val   { color: #60a5fa !important; }
    [data-theme="dark"] .stat-card.pending .stat-val { color: #fbbf24 !important; }

    [data-theme="dark"] .stat-lbl { color: #9ca3af !important; }

    [data-theme="dark"] .stat-card.pending .stat-trend {
        background: rgba(245,158,11,.12) !important;
        color: #fbbf24 !important;
    }

    [data-theme="dark"] .search-wrap {
        background: #0d1117 !important;
        border-color: #21262d !important;
    }

    [data-theme="dark"] .search-wrap:focus-within {
        border-color: #8B0000 !important;
        box-shadow: 0 0 0 3px rgba(139,0,0,.14) !important;
    }

    [data-theme="dark"] .search-wrap input { color: #f3f4f6 !important; }
    [data-theme="dark"] .search-wrap input::placeholder { color: #6b7280 !important; }
    [data-theme="dark"] .search-wrap .search-icon { color: #f87171 !important; }

    [data-theme="dark"] .tbl th {
        background: #0d1117 !important;
        color: #6b7280 !important;
        border-bottom-color: #21262d !important;
    }

    [data-theme="dark"] .tbl td {
        color: #d1d5db !important;
        border-bottom-color: #1c2128 !important;
    }

    [data-theme="dark"] .tbl tbody tr:hover td { background: #1c2128 !important; }

    [data-theme="dark"] .panel-header {
        background: #0d1117 !important;
        border-bottom-color: #21262d !important;
    }

    [data-theme="dark"] .panel-header .font-bold,
    [data-theme="dark"] #panelRecordTitle { color: #f3f4f6 !important; }
    [data-theme="dark"] #panelRecordTitle.has-ref { color: #FCA5A5 !important; }

    [data-theme="dark"] #panelBody { color: #d1d5db !important; }

    [data-theme="dark"] #panelBody [style*="background:#fef2f2"] {
        background: rgba(139,0,0,.12) !important;
        border-color: rgba(139,0,0,.24) !important;
    }

    [data-theme="dark"] #panelBody [style*="color:#111"] { color: #f3f4f6 !important; }
    [data-theme="dark"] #panelBody [style*="color:#374151"] { color: #d1d5db !important; }
    [data-theme="dark"] #panelBody [style*="color:#9ca3af"] { color: #9ca3af !important; }

    [data-theme="dark"] #panelFoot {
        background: #0d1117 !important;
        border-top-color: #21262d !important;
    }

    [data-theme="dark"] .act-btn.view {
        background: rgba(139,0,0,.12) !important;
        color: #fca5a5 !important;
    }

    [data-theme="dark"] .act-btn.view:hover {
        background: #8B0000 !important;
        color: #fff !important;
    }

    [data-theme="dark"] .status-pill.completed { background: rgba(16,185,129,.12) !important; color: #34d399 !important; }
    [data-theme="dark"] .status-pill.pending   { background: rgba(245,158,11,.12) !important; color: #fbbf24 !important; }
    [data-theme="dark"] .status-pill.ongoing   { background: rgba(59,130,246,.12) !important; color: #60a5fa !important; }

    [data-theme="dark"] .insight-row { border-bottom-color: #1c2128 !important; }
    [data-theme="dark"] .insight-lbl { color: #9ca3af !important; }
    [data-theme="dark"] .insight-val { color: #f3f4f6 !important; }

    [data-theme="dark"] .quick-action { border-bottom-color: #1c2128 !important; }
    [data-theme="dark"] .quick-action:hover { background: #1c2128 !important; }
    [data-theme="dark"] .quick-action-icon { background: rgba(139,0,0,.12) !important; color: #fca5a5 !important; }
    [data-theme="dark"] .quick-action:hover .quick-action-icon { background: #8B0000 !important; color: #fff !important; }
    [data-theme="dark"] .quick-action-title { color: #f3f4f6 !important; }
    [data-theme="dark"] .quick-action-sub { color: #9ca3af !important; }
    [data-theme="dark"] .quick-action:hover .quick-action-arrow { color: #fca5a5 !important; }

    [data-theme="dark"] .empty-state i { color: #374151 !important; }

    [data-theme="dark"] .btn-secondary {
        background: #161b22 !important;
        color: #e5e7eb !important;
        border-color: #2b313a !important;
    }

    [data-theme="dark"] .btn-secondary:hover { background: #1c2128 !important; }

    [data-theme="dark"] .btn-primary { background: #8B0000 !important; }
    [data-theme="dark"] .btn-primary:hover { background: #6b0000 !important; }

    /* ── Responsive ── */
    @media (max-width: 1280px) {
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 1024px) {
        .content-grid { grid-template-columns: 1fr !important; }
        .panel-card { position: static; }
    }

    @media (max-width: 900px) {
        .dental-records-page {
            margin-left: 0;
            padding: calc(var(--header-h, 70px) + 12px) 1rem 2rem;
        }

        .search-wrap { max-width: 100%; width: 100%; height: 42px; }
        .page-banner { border-radius: 14px; padding: 1.1rem 1.1rem 1.4rem; }
        .page-title { font-size: 1.45rem; }
        .page-banner-inner { flex-direction: column; gap: .6rem; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
@endsection

@section('content')
@php
    use Carbon\Carbon;
@endphp

<div class="dental-records-page">
    <main class="w-full">
        <div class="max-w-[1280px] mx-auto">

            {{-- ── Banner ── --}}
            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Dental Records</h1>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('admin.reports.index') }}" class="btn-secondary">
                            <i class="fa-solid fa-chart-column text-xs"></i>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── Stats ── --}}
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
                    <div class="stat-val">{{ number_format($totalRecords ?? 0) }}</div>
                    <div class="stat-lbl">Total Records</div>
                </div>

                <div class="stat-card today">
                    <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="stat-val">{{ $recordsToday ?? 0 }}</div>
                    <div class="stat-lbl">Added Today</div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-icon"><i class="fa-solid fa-user-clock"></i></div>
                    <div class="stat-val">{{ $pending ?? 0 }}</div>
                    <div class="stat-lbl">Pending Records</div>
                    @if(($pending ?? 0) > 0)
                        <span class="stat-trend">Needs action</span>
                    @endif
                </div>
            </div>

            {{-- ── Toolbar ── --}}
            <div class="toolbar">
                <div class="search-wrap">
                    <i class="fa fa-search search-icon"></i>
                    <input
                        type="text"
                        id="dentalRecordSearch"
                        placeholder="Search patient / procedure..."
                        autocomplete="off"
                    >
                </div>
            </div>

            {{-- ── Main content grid ── --}}
            <div class="content-grid">

                {{-- Table --}}
                <div class="tbl-wrap">
                    @if(($records ?? collect())->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-inbox"></i>
                            <p class="font-semibold text-gray-500 mb-1">No dental records found</p>
                            <p class="text-sm">New records will appear here once added.</p>
                        </div>
                    @else
                        <div style="overflow-x:auto;">
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Procedure</th>
                                        <th>Dentist</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $record)
                                        @php
                                            $status      = strtolower($record->status ?? 'pending');
                                            $pillClass   = match($status) {
                                                'completed' => 'completed',
                                                'ongoing'   => 'ongoing',
                                                default     => 'pending',
                                            };
                                            $patientName = $record->patient_name ?? 'Unknown Patient';
                                            $initial     = strtoupper(substr($patientName, 0, 1));
                                        @endphp
                                        <tr
                                            class="dental-record-row"
                                            data-patient="{{ strtolower($patientName) }}"
                                            data-procedure="{{ strtolower($record->procedure ?? '') }}"
                                            data-dentist="{{ strtolower($record->dentist_name ?? '') }}"
                                            data-status="{{ $status }}"
                                        >
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <div style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#6b0000);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0;">
                                                        {{ $initial }}
                                                    </div>
                                                    <span class="font-semibold text-gray-800" style="font-size:.78rem;">
                                                        {{ $patientName }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="wrap">{{ $record->procedure ?? '—' }}</td>
                                            <td>{{ $record->dentist_name ?? '—' }}</td>
                                            <td>
                                                {{ !empty($record->date) ? Carbon::parse($record->date)->format('M d, Y') : '—' }}
                                            </td>
                                            <td>
                                                <span class="status-pill {{ $pillClass }}">
                                                    <i class="fa-solid fa-circle" style="font-size:.4rem;"></i>
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </td>
                                            <td style="text-align:right;">
                                                @if(!empty($record->id))
                                                    <button
                                                        type="button"
                                                        class="act-btn view"
                                                        onclick="openRecordPanel({{ $record->id }})"
                                                        title="View"
                                                    >
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div id="dentalRecordClientEmpty" class="empty-state" style="display:none;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <p class="font-semibold text-gray-500 mb-1">No matching records found</p>
                            <p class="text-sm">Try a different patient name or procedure.</p>
                        </div>
                    @endif
                </div>

                {{-- Side panel --}}
                <div>
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-header-icon">
                                <i class="fa-solid fa-notes-medical text-white" style="font-size:.7rem;"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-800 text-sm" id="panelRecordTitle">Select a record</div>
                                <div class="text-[11px] text-gray-400">Dental Record</div>
                            </div>
                        </div>

                        <div style="padding:1.25rem;" id="panelBody">
                            <div style="text-align:center;padding:2.5rem 0 2rem;color:#d1d5db;">
                                <div style="width:52px;height:52px;border-radius:14px;background:#fef2f2;display:flex;
                                    align-items:center;justify-content:center;margin:0 auto .9rem;">
                                    <i class="fa-solid fa-notes-medical" style="font-size:1.4rem;color:#f9c1c1;"></i>
                                </div>
                                <p style="font-size:.78rem;font-weight:600;color:#cbd5e1;margin-bottom:.25rem;">No record selected</p>
                                <p style="font-size:.7rem;color:#e2e8f0;">Click a row to view details</p>
                            </div>
                        </div>

                        <div id="panelFoot" style="padding:.9rem 1.25rem;border-top:1px solid #f3f4f6;background:#fafafa;display:none;gap:.5rem;flex-wrap:wrap;"></div>
                    </div>

                    {{-- Record Insights --}}
                    <div class="insights-card">
                        <div class="panel-header">
                            <div class="panel-header-icon">
                                <i class="fa-solid fa-chart-pie text-white" style="font-size:.7rem;"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-800 text-sm">Record Insights</div>
                                <div class="text-[11px] text-gray-400">Summary statistics</div>
                            </div>
                        </div>

                        <div>
                            <div class="insight-row">
                                <div>
                                    <div class="insight-lbl">Most Common Procedure</div>
                                    <div class="insight-val">{{ $topProcedure ?? 'No data yet' }}</div>
                                </div>
                                <i class="fa-solid fa-tooth" style="color:#8B0000;font-size:.9rem;"></i>
                            </div>
                            <div class="insight-row">
                                <div>
                                    <div class="insight-lbl">Completed This Week</div>
                                    <div class="insight-val">{{ $completedThisWeek ?? 0 }}</div>
                                </div>
                                <i class="fa-solid fa-circle-check" style="color:#16a34a;font-size:.9rem;"></i>
                            </div>
                            <div class="insight-row">
                                <div>
                                    <div class="insight-lbl">Patients For Follow-Up</div>
                                    <div class="insight-val">{{ $patientsForFollowUp ?? 0 }}</div>
                                </div>
                                <i class="fa-solid fa-user-clock" style="color:#d97706;font-size:.9rem;"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="insights-card">
                        <div class="panel-header">
                            <div class="panel-header-icon">
                                <i class="fa-solid fa-bolt text-white" style="font-size:.7rem;"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-800 text-sm">Quick Actions</div>
                                <div class="text-[11px] text-gray-400">Common tasks</div>
                            </div>
                        </div>

                        <div>
                            <a href="{{ route('admin.reports.index') }}" class="quick-action">
                                <div class="quick-action-icon"><i class="fa-solid fa-chart-column"></i></div>
                                <div>
                                    <div class="quick-action-title">Dental Reports</div>
                                    <div class="quick-action-sub">View analytics and summaries</div>
                                </div>
                                <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                            </a>
                            <a href="{{ route('admin.appointments') }}" class="quick-action">
                                <div class="quick-action-icon"><i class="fa-solid fa-calendar-check"></i></div>
                                <div>
                                    <div class="quick-action-title">Appointments</div>
                                    <div class="quick-action-sub">Check scheduled clinic visits</div>
                                </div>
                                <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>
@endsection

@section('scripts')
<script>
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    const statusBg = { completed:'#d1fae5', pending:'#fef3c7', ongoing:'#dbeafe' };
    const statusTx = { completed:'#065f46', pending:'#92400e', ongoing:'#1e40af' };

    function detailRow(label, value) {
        return `<div style="display:flex;gap:.5rem;margin-bottom:.6rem;font-size:.8rem;">
            <span style="color:#9ca3af;min-width:110px;flex-shrink:0;">${label}</span>
            <span style="color:#111;font-weight:600;">${value}</span>
        </div>`;
    }

    async function openRecordPanel(id) {
        const title     = document.getElementById('panelRecordTitle');
        const panelBody = document.getElementById('panelBody');
        const panelFoot = document.getElementById('panelFoot');

        title.textContent = 'Loading...';
        title.classList.remove('has-ref');
        panelBody.innerHTML = `<div style="text-align:center;padding:2rem 0;color:#d1d5db;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;"></i></div>`;
        panelFoot.style.display = 'none';
        panelFoot.innerHTML = '';

        try {
            const res = await fetch(`/admin/dental-records/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (!res.ok) throw new Error('Failed to fetch.');
            const data = await res.json();

            const status  = (data.status || 'pending').toLowerCase();
            const initial = ((data.patient_name || '?')[0] || '?').toUpperCase();

            title.textContent = data.patient_name || 'Record Details';
            title.classList.add('has-ref');

            panelBody.innerHTML = `
                <div style="background:#fef2f2;border-radius:10px;padding:.9rem 1rem;margin-bottom:1rem;
                    display:flex;align-items:center;gap:.75rem;border:1px solid #fce8e8;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#6b0000);
                        color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                        ${initial}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.84rem;font-weight:700;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            ${data.patient_name || '—'}
                        </div>
                    </div>
                    <span style="background:${statusBg[status] || '#f3f4f6'};color:${statusTx[status] || '#6b7280'};
                        padding:.2rem .65rem;border-radius:999px;font-size:.68rem;font-weight:700;white-space:nowrap;">
                        ${status.charAt(0).toUpperCase() + status.slice(1)}
                    </span>
                </div>
                ${detailRow('Procedure', data.procedure || '—')}
                ${detailRow('Dentist', data.dentist_name || '—')}
                ${detailRow('Date', data.date || '—')}
                ${data.notes ? detailRow('Notes', data.notes) : ''}
            `;

            panelFoot.style.display = 'flex';
            panelFoot.innerHTML = `
                <a href="/admin/dental-records/${data.id}" class="btn-primary" style="font-size:.75rem;padding:.4rem .9rem;text-decoration:none;">
                    <i class="fa-solid fa-arrow-right text-xs"></i> View Full Record
                </a>
            `;
        } catch {
            panelBody.innerHTML = `<p style="color:#dc2626;text-align:center;padding:1.5rem;">Failed to load details.</p>`;
        }
    }

    /* ── Client-side search ── */
    function filterDentalRecords() {
        const input      = document.getElementById('dentalRecordSearch');
        const rows       = Array.from(document.querySelectorAll('.dental-record-row'));
        const emptyState = document.getElementById('dentalRecordClientEmpty');
        if (!input) return;

        const q = input.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach(row => {
            const haystack = [
                row.dataset.patient,
                row.dataset.procedure,
                row.dataset.dentist,
                row.dataset.status
            ].join(' ');
            const match = !q || haystack.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        if (emptyState) emptyState.style.display = visible === 0 ? 'flex' : 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('dentalRecordSearch');
        if (input) input.addEventListener('input', filterDentalRecords);
    });
</script>
@endsection