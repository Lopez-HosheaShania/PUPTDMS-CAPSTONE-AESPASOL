@extends('layouts.app')
@section('layout-role', 'admin')
@section('title', 'Retention Review')
@section('styles')
    @vite('resources/css/pages/admin/system-settings.css')
@endsection
@section('content')
    @php
        $definition = config('retention.categories.'.$category);
        $policyTone = match ($policy->status) {
            'active' => 'status-active',
            'approved' => 'status-approved',
            default => 'status-pending',
        };
    @endphp
    <main id="mainContent" class="system-settings-page app-page-shell">
        <div class="section-card">
            <div class="section-card-hdr retention-header">
                <h1 class="setting-row-label">Records for Retention Review</h1>
                <a class="ui-btn ui-btn-secondary" href="{{ route('admin.system_settings', ['tab' => 'retention']) }}">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Retention Settings
                </a>
            </div>
            <div class="section-card-body">
                <p class="setting-row-desc">Review dates are calculated from each record's actual event date. “Keep record” means the data is still needed; it does not block the patient's login or booking access. This page does not archive or delete records.</p>
                <form method="GET" action="{{ route('admin.retention.review') }}" class="retention-review-filter" data-global-selects>
                    <input type="hidden" name="per_page" value="{{ $records->perPage() }}">
                    <div class="global-form-group">
                        <label class="global-form-label" for="retention-review-category">Category</label>
                        <select id="retention-review-category" name="category" class="form-select-custom js-custom-select">
                            @foreach (config('retention.categories') as $key => $categoryDefinition)
                                <option value="{{ $key }}" @selected($category === $key)>{{ $categoryDefinition['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="ui-btn ui-btn-primary" type="submit">View Records</button>
                </form>
                <div class="retention-status">
                    <span class="status-pill {{ $policyTone }}">{{ $policy->status_label }}</span>
                    <span class="global-info-subvalue">Last updated: {{ $policy->updated_at?->format('M d, Y') ?? 'Not recorded' }} · {{ $policy->period_label }}</span>
                </div>
                <div class="global-info-grid global-info-grid-2 retention-review-summary">
                    <div class="global-info-item">
                        <div><span class="global-info-label">Count from</span><strong class="global-info-value">{{ $policy->countingRules()[$policy->counting_rule] ?? 'Choose a supported counting rule' }}</strong></div>
                    </div>
                    <div class="global-info-item">
                        <div><span class="global-info-label">Policy effective from</span><strong class="global-info-value">{{ $policy->effective_from?->format('M d, Y') ?? 'Awaiting approval' }}</strong>
                            <small class="global-info-subvalue">This is not the record's starting date.</small></div>
                    </div>
                    <div class="global-info-item global-info-item-wide">
                        <div><span class="global-info-label">Approval reference</span><strong class="global-info-value">{{ $policy->approval_reference ?? 'Awaiting documented approval' }}</strong></div>
                    </div>
                </div>
                @if ($policyNotice)
                    <div class="global-confirm-alert retention-review-notice" role="status">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <div><p>Policy not yet in use</p><span>{{ $policyNotice }}</span></div>
                    </div>
                @endif
                <div class="table-card retention-review-table">
                    <x-pagination-bar id="retentionPagebarTop" info-id="retentionPageInfoTop"
                        pagination-id="retentionPaginationTop" position="top" label="records" :paginator="$records"
                        :show-entries="true" page-size-id="retentionPerPage" page-size-callback="changeRetentionPageSize" />
                    <div class="table-scroll" role="region" aria-label="Retention review records" tabindex="0">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Record</th>
                                    <th scope="col">Starting date</th>
                                    <th scope="col">Review date</th>
                                    <th scope="col">Retention status</th>
                                    <th scope="col">Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    @php($assessment = $assessments[$record->id])
                                    <tr>
                                        <td>@include('admin.partials.retention-record-identity')</td>
                                        <td>{{ $assessment['start_display'] }}</td>
                                        <td>{{ $assessment['due_display'] }}
                                            @if ($assessment['due_note'])<small class="global-info-subvalue">{{ $assessment['due_note'] }}</small>@endif
                                        </td>
                                        <td><span class="status-pill {{ $assessment['status_tone'] }}">{{ $assessment['status'] }}</span></td>
                                        <td>{{ $assessment['reason'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">No records found for this category.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-pagination-bar id="retentionPagebarBottom" info-id="retentionPageInfoBottom"
                        pagination-id="retentionPaginationBottom" position="bottom" label="records" :paginator="$records" />
                </div>
                <p class="setting-row-desc">A review date is not a deletion date. Related records and preservation requirements still need to be reviewed.</p>
            </div>
        </div>
    </main>
@endsection
@section('scripts')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        function navigateRetentionRecords(page, pageSize = null) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', String(page));
            if (pageSize !== null) url.searchParams.set('per_page', String(pageSize));
            window.location.assign(url.toString());
        }

        window.changeRetentionPageSize = size => navigateRetentionRecords(1, size);

        async function initRetentionPagination() {
            await window.loadPaginationBarModule();
            const top = document.getElementById('retentionPagebarTop');
            window.renderGlobalPagination({
                currentPage: Number(top.dataset.currentPage),
                lastPage: Number(top.dataset.lastPage),
                total: Number(top.dataset.total),
                from: Number(top.dataset.from),
                to: Number(top.dataset.to),
                containers: [document.getElementById('retentionPaginationTop'), document.getElementById('retentionPaginationBottom')],
                infoElements: [document.getElementById('retentionPageInfoTop'), document.getElementById('retentionPageInfoBottom')],
                itemLabel: 'records',
                onPageChange: page => navigateRetentionRecords(page),
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initRetentionPagination, { once: true });
        } else {
            initRetentionPagination();
        }
    </script>
@endsection
