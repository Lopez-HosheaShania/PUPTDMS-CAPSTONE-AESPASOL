@extends('layouts.admin')

@section('title', 'Dental Records | PUP Taguig Dental Clinic')

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
            <div class="stat-grid">
                <div class="stat-card total">
                    <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
                    <div class="stat-value">{{ number_format($totalRecords ?? 0) }}</div>
                    <div class="stat-lbl">Total Records</div>
                </div>

                <div class="stat-card today">
                    <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="stat-value">{{ $recordsToday ?? 0 }}</div>
                    <div class="stat-lbl">Added Today</div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-icon"><i class="fa-solid fa-user-clock"></i></div>
                    <div class="stat-value">{{ $pending ?? 0 }}</div>
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
                    <input type="text" id="dentalRecordSearch" placeholder="Search patient / procedure..."
                        autocomplete="off">
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
                                $status = strtolower($record->status ?? 'pending');
                                $pillClass = match($status) {
                                'completed' => 'completed',
                                'ongoing' => 'ongoing',
                                default => 'pending',
                                };
                                $patientName = $record->patient_name ?? 'Unknown Patient';
                                $initial = strtoupper(substr($patientName, 0, 1));
                                @endphp
                                <tr class="dental-record-row" data-patient="{{ strtolower($patientName) }}"
                                    data-procedure="{{ strtolower($record->procedure ?? '') }}"
                                    data-dentist="{{ strtolower($record->dentist_name ?? '') }}"
                                    data-status="{{ $status }}">
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div
                                                style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#6b0000);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0;">
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
                                        {{ !empty($record->date) ? Carbon::parse($record->date)->format('M d, Y') : '—'
                                        }}
                                    </td>
                                    <td>
                                        <span class="status-pill {{ $pillClass }}">
                                            <i class="fa-solid fa-circle" style="font-size:.4rem;"></i>
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        @if(!empty($record->id))
                                        <button type="button" class="action-btn view"
                                            onclick="openRecordPanel({{ $record->id }})" title="View">
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
                                <p style="font-size:.78rem;font-weight:600;color:#cbd5e1;margin-bottom:.25rem;">No
                                    record selected</p>
                                <p style="font-size:.7rem;color:#e2e8f0;">Click a row to view details</p>
                            </div>
                        </div>

                        <div id="panelFoot"
                            style="padding:.9rem 1.25rem;border-top:1px solid #f3f4f6;background:#fafafa;display:none;gap:.5rem;flex-wrap:wrap;">
                        </div>
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
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    const statusBg = { completed: '#d1fae5', pending: '#fef3c7', ongoing: '#dbeafe' };
    const statusTx = { completed: '#065f46', pending: '#92400e', ongoing: '#1e40af' };

    function detailRow(label, value) {
        return `<div style="display:flex;gap:.5rem;margin-bottom:.6rem;font-size:.8rem;">
            <span style="color:#9ca3af;min-width:110px;flex-shrink:0;">${label}</span>
            <span style="color:#111;font-weight:600;">${value}</span>
        </div>`;
    }

    async function openRecordPanel(id) {
        const title = document.getElementById('panelRecordTitle');
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

            const status = (data.status || 'pending').toLowerCase();
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
        const input = document.getElementById('dentalRecordSearch');
        const rows = Array.from(document.querySelectorAll('.dental-record-row'));
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