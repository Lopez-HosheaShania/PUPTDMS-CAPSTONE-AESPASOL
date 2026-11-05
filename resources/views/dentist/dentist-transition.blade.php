@extends('layouts.app')

@section('layout-role', 'dentist')

@if (isset($transition))
    @section('title', 'Dentist Transition Details')
@else
    @section('title', 'Dentist Continuity')
@endif

@if (isset($transition))
    @section('styles')
        @vite('resources/css/pages/admin/dentist-continuity.css')
    @endsection
@endif

@section('content')
    @if (isset($transition))
        <main id="mainContent" class="app-page-shell page-enter mode-list continuity-page mode-show">
            <div class="w-full dt-wrap">
                <x-page-banner class="dt-hero">
                    <x-slot:content>
                        <div class="dt-hero-copy">
                            <h1 class="page-title">{{ $transition->dentist->name ?? 'Unknown dentist' }}</h1>
                            <p class="dt-subtitle">
                                {{ str_replace('_', ' ', ucfirst($transition->transition_type)) }} transition.
                                Last working date: {{ optional($transition->last_working_date)->format('M d, Y') }}.
                                Access ends: {{ optional($transition->access_ends_at)->format('M d, Y h:i A') }}.
                            </p>
                        </div>
                        <div class="dt-btn-row">
                            <span
                                class="dt-badge dt-badge-{{ $transition->status }}">{{ str_replace('_', ' ', ucfirst($transition->status)) }}</span>
                            <a href="{{ route('dentist.dentist.transitions.index') }}" class="dt-btn dt-btn-light">Back to
                                list</a>
                        </div>
                    </x-slot:content>
                </x-page-banner>

                @if (session('success'))
                    <div class="dt-alert dt-alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="dt-alert dt-alert-error">{{ session('error') }}</div>
                @endif

                <div class="admin-page-body dt-show-layout">
                    <div class="dt-summary-grid stat-grid admin-dashboard-stat-grid dt-stat-grid mb-6">
                        <x-stat-card tone="all" label="Future Appointments"
                            value="{{ $summary['future_appointments'] }}" value-class="stat-num"
                            icon="fa-solid fa-calendar-check" footer="Appointments currently under the departing dentist."
                            footer-icon="fa-solid fa-calendar-check" />

                        <x-stat-card tone="ongoing" label="Ready To Transfer" value="{{ $summary['ready_to_transfer'] }}"
                            value-class="stat-num" icon="fa-solid fa-people-arrows-left-right"
                            footer="Items with valid successor coverage."
                            footer-icon="fa-solid fa-people-arrows-left-right" />

                        <x-stat-card tone="approved" label="Transferred" value="{{ $summary['transferred_items'] }}"
                            value-class="stat-num" icon="fa-solid fa-file-circle-check"
                            footer="Records already completed by the handover process."
                            footer-icon="fa-solid fa-file-circle-check" />

                        <x-stat-card tone="rejected" label="Critical Unresolved"
                            value="{{ $summary['critical_unresolved_items'] }}" value-class="stat-num"
                            icon="fa-solid fa-triangle-exclamation" footer="Items still blocking finalization."
                            footer-icon="fa-solid fa-triangle-exclamation" />
                    </div>

                    <div class="dt-page-grid">
                        <section class="dt-panel table-card dt-show-panel">
                            <div class="dt-section-head">
                                <div class="dt-show-head">
                                    <div class="card-header-icon">
                                        <i class="fa-solid fa-clipboard-list"></i>
                                    </div>
                                    <div>
                                        <h2>Transition Information</h2>
                                        <p>{{ str_replace('_', ' ', ucfirst($transition->transition_type)) }} &middot;
                                            {{ $transition->dentist->name ?? 'Unknown dentist' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="dt-transition-overview dt-transition-overview-card">
                                <div class="dt-transition-progress-ring">
                                    <span>{{ $transition->progress_percentage }}%</span>
                                </div>
                                <div class="dt-transition-overview-copy dt-transition-overview-copy-block">
                                    <span class="dt-transition-overview-title">Checklist Progress</span>
                                    <span
                                        class="dt-transition-overview-subtitle">{{ $transition->checklistItems->where('is_completed', true)->count() }}
                                        of {{ $transition->checklistItems->count() }} completed</span>
                                </div>
                                <div class="dt-transition-overview-bar dt-transition-overview-bar-wide">
                                    <div class="admin-progress-track">
                                        <div class="admin-progress-bar"
                                            data-ui-style="width: {{ $transition->progress_percentage }}%;"></div>
                                    </div>
                                </div>
                            </div>

                            <section class="dt-show-block">
                                <div class="dt-show-block-label"><i class="fa-solid fa-users"></i><span>People</span></div>
                                <div class="dt-people-layout">
                                    <div class="dt-people-primary">
                                        <div class="dt-people-card">
                                            <div class="dt-people-card-icon">
                                                <i class="fa-solid fa-user"></i>
                                            </div>
                                            <div class="dt-people-card-copy">
                                                <span>Departing Dentist</span>
                                                <strong>{{ $transition->dentist->name ?? 'Unknown dentist' }}</strong>
                                            </div>
                                        </div>
                                        <div class="dt-people-card dt-people-card-warning">
                                            <div class="dt-people-card-icon">
                                                <i class="fa-solid fa-user-plus"></i>
                                            </div>
                                            <div class="dt-people-card-copy">
                                                <span>Default Successor</span>
                                                <strong>{{ $transition->defaultSuccessor->name ?? 'Not assigned yet' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <dl class="dt-people-secondary">
                                        <div class="dt-people-secondary-item">
                                            <div class="dt-people-secondary-icon">
                                                <i class="fa-regular fa-user"></i>
                                            </div>
                                            <div>
                                                <dt>Initiated by</dt>
                                                <dd>{{ $transition->initiatedBy->name ?? 'Unknown admin' }}</dd>
                                            </div>
                                        </div>
                                        <div class="dt-people-secondary-item">
                                            <div class="dt-people-secondary-icon">
                                                <i class="fa-regular fa-circle-check"></i>
                                            </div>
                                            <div>
                                                <dt>Reviewed by</dt>
                                                <dd>{{ $transition->reviewedBy->name ?? 'Pending review' }}</dd>
                                            </div>
                                        </div>
                                    </dl>
                                </div>
                            </section>

                            <section class="dt-show-block">
                                <div class="dt-show-block-label"><i
                                        class="fa-regular fa-calendar-days"></i><span>Timeline</span></div>
                                <div class="dt-timeline-strip">
                                    <div class="dt-timeline-strip-line" aria-hidden="true"></div>
                                    <div class="dt-timeline-strip-node" aria-hidden="true"></div>
                                    <div class="dt-timeline-strip-node" aria-hidden="true"></div>
                                    <div class="dt-timeline-strip-node" aria-hidden="true"></div>
                                </div>
                                <div class="dt-timeline-grid">
                                    <div class="dt-timeline-card">
                                        <div class="dt-timeline-card-icon">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </div>
                                        <div class="dt-timeline-card-copy">
                                            <span class="dt-timeline-card-step">01</span>
                                            <span class="dt-timeline-card-label">Transition Type</span>
                                            <strong>{{ str_replace('_', ' ', ucfirst($transition->transition_type)) }}</strong>
                                        </div>
                                    </div>
                                    <div class="dt-timeline-card">
                                        <div class="dt-timeline-card-icon">
                                            <i class="fa-regular fa-calendar"></i>
                                        </div>
                                        <div class="dt-timeline-card-copy">
                                            <span class="dt-timeline-card-step">02</span>
                                            <span class="dt-timeline-card-label">Last Working Date</span>
                                            <strong>{{ optional($transition->last_working_date)->format('M d, Y') ?? 'Not set' }}</strong>
                                        </div>
                                    </div>
                                    <div class="dt-timeline-card">
                                        <div class="dt-timeline-card-icon">
                                            <i class="fa-regular fa-clock"></i>
                                        </div>
                                        <div class="dt-timeline-card-copy">
                                            <span class="dt-timeline-card-step">03</span>
                                            <span class="dt-timeline-card-label">Access Expiration</span>
                                            <strong>{{ optional($transition->access_ends_at)->format('M d, Y h:i A') ?? 'Not set' }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            @if ($transition->handover_notes || $transition->remarks)
                                <div class="dt-note-grid">
                                    @if ($transition->handover_notes)
                                        <div class="dt-note-block">
                                            <h3>Handover Notes</h3>
                                            <p>{{ $transition->handover_notes }}</p>
                                        </div>
                                    @endif
                                    @if ($transition->remarks)
                                        <div class="dt-note-block">
                                            <h3>Admin Remarks</h3>
                                            <p>{{ $transition->remarks }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    </div>

                    <section class="dt-panel table-card dt-show-panel">
                        <div class="dt-section-head">
                            <div class="dt-show-head">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-people-arrows-left-right"></i>
                                </div>
                                <div>
                                    <h2>Active Responsibility Table</h2>
                                    <p>Review the pending appointments and document requests assigned to you.</p>
                                </div>
                            </div>
                        </div>

                        <div class="table-scroll dt-table-wrap dt-responsibility-wrap">
                            <table class="data-table dt-table dt-responsibility-table">
                                <thead>
                                    <tr>
                                        <th>Record</th>
                                        <th>Patient</th>
                                        <th>Assignment</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transition->items as $item)
                                        @php
                                            $taskLabel = match ($item->item_type) {
                                                'document_request' => 'Review and endorse pending document request',
                                                'appointment' => 'Reassign and review upcoming appointment',
                                                default => 'Review and transfer continuity responsibility',
                                            };
                                            $taskHint = match ($item->item_type) {
                                                'document_request' => 'Include this request in the continuity handoff.',
                                                'appointment' => 'Transfer this active appointment for continued care.',
                                                default => 'Mark this item if it should be handed over.',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="dt-record-checkcell" data-transfer-item-row>
                                                    <span class="dt-record-selectmark" aria-hidden="true">
                                                        <i class="fa-solid fa-check"></i>
                                                    </span>
                                                    <span class="dt-table-stack">
                                                        <span
                                                            class="dt-inline-value dt-inline-value-sm">{{ str_replace('_', ' ', ucfirst($item->item_type)) }}</span>
                                                        <span
                                                            class="dt-inline-value dt-inline-value-xs">{{ $item->reference_label }}</span>
                                                    </span>
                                                </div>
                                            </td>
                                            <td><span
                                                    class="dt-inline-value dt-inline-value-sm">{{ $item->patient->name ?? 'Unknown patient' }}</span>
                                            </td>
                                            <td>
                                                <div class="dt-task-cell">
                                                    <span class="dt-task-copy">
                                                        <strong>{{ $taskLabel }}</strong>
                                                        <small>{{ $taskHint }}</small>
                                                    </span>
                                                </div>
                                            </td>
                                            <td><span class="dt-inline-value">{{ $item->remarks ?: '—' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="dt-empty dt-responsibility-empty">
                                                <div class="empty-state empty-state-compact dt-responsibility-empty-state">
                                                    <div class="empty-state-icon">
                                                        <i class="fa-regular fa-clipboard-check"></i>
                                                    </div>
                                                    <h3 class="empty-state-title">No responsibilities to transfer</h3>
                                                    <p class="empty-state-sub">There are currently no active dentist-owned
                                                        records assigned to this account.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dt-responsibility-footnote">
                            <i class="fa-regular fa-shield-check"></i>
                        </div>
                    </section>

                    <section class="dt-panel table-card dt-show-panel">
                        <div class="dt-section-head">
                            <div class="dt-checklist-header">
                                <div class="dt-show-head">
                                    <div class="card-header-icon">
                                        <i class="fa-solid fa-list-check"></i>
                                    </div>
                                    <div>
                                        <h2>Handover Checklist</h2>
                                        <p>Summary of completed handover requirements.</p>
                                    </div>
                                </div>
                                <div class="dt-checklist-summary-pill">
                                    <div class="dt-checklist-summary-main">
                                        <i class="fa-regular fa-circle-check"></i>
                                        <span>{{ $transition->checklistItems->where('is_completed', true)->count() }} of
                                            {{ $transition->checklistItems->count() }} completed</span>
                                    </div>
                                    <strong>{{ $transition->progress_percentage }}%</strong>
                                </div>
                            </div>
                        </div>

                        <div class="dt-checklist-progress-wrap">
                            <div class="dt-checklist-progress-scale">0%</div>
                            <div class="dt-checklist-progress-track">
                                <div class="dt-checklist-progress-fill"
                                    data-ui-style="width: {{ $transition->progress_percentage }}%;"></div>
                            </div>
                            <div class="dt-checklist-progress-scale">100%</div>
                        </div>

                        <div class="dt-checklist-grid">
                            @foreach ($transition->checklistItems as $item)
                                <div
                                    class="dt-checklist-item dt-checklist-card {{ $item->is_completed ? 'is-complete' : '' }}">
                                    <div class="dt-checklist-card-top" data-ui-presentation="presentation-4199b6098b99">
                                        <div class="dt-checklist-icon">
                                            <i
                                                class="fa-solid {{ $item->is_completed ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                        </div>
                                        <div class="dt-checklist-copy">
                                            <strong>{{ $item->label }}</strong>
                                        </div>
                                        <span
                                            class="dt-checklist-status-pill {{ $item->is_completed ? 'is-complete' : '' }}">
                                            {{ $item->is_completed ? 'Completed' : 'Pending' }}
                                        </span>
                                    </div>
                                    @if ($item->is_completed && $item->completedBy)
                                        <div class="dt-checklist-meta">
                                            <i class="fa-regular fa-circle-check"></i>
                                            <span>Completed by {{ $item->completedBy->name ?? 'Unknown user' }} on
                                                {{ optional($item->completed_at)->format('M d, Y h:i A') }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>
        </main>
    @else
        <main id="mainContent" class="app-page-shell page-enter mode-list">
            <div class="w-full">
                <section class="dentist-hero mb-5">
                    <div class="dentist-hero-content">
                        <div class="dentist-hero-icon">
                            <i class="fa-solid fa-users-gear"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="dentist-hero-eyebrow">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                Continuity
                            </div>
                            <h1 class="dentist-hero-title">My Transitions</h1>
                        </div>
                    </div>
                </section>

                @if (session('success'))
                    <div class="dt-alert dt-alert-success mb-4">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="dt-alert dt-alert-error mb-4">{{ session('error') }}</div>
                @endif

                <section class="card">
                    <x-card-header title="Transition Records"
                        subtitle="Plans where you are the departing dentist or assigned successor"
                        icon="fa-solid fa-table-list" />

                    <div class="table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Dentist</th>
                                    <th>Successor</th>
                                    <th>Type</th>
                                    <th>Last Working Date</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transitions as $transition)
                                    <tr>
                                        <td>
                                            <div class="dt-cell-title">
                                                {{ $transition->dentist->name ?? 'Unknown dentist' }}</div>
                                            <div class="dt-cell-sub">{{ $transition->dentist->email ?? 'No email' }}</div>
                                        </td>
                                        <td>{{ $transition->defaultSuccessor->name ?? 'Not assigned' }}</td>
                                        <td>{{ str_replace('_', ' ', ucfirst($transition->transition_type)) }}</td>
                                        <td>{{ optional($transition->last_working_date)->format('M d, Y') ?? '—' }}</td>
                                        <td>
                                            <span class="dt-badge dt-badge-{{ $transition->status }}">
                                                {{ str_replace('_', ' ', ucfirst($transition->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dt-progress">
                                                <div class="dt-progress-bar"
                                                    data-ui-style="width: {{ $transition->progress_percentage }}%;"></div>
                                            </div>
                                            <div class="dt-cell-sub">{{ $transition->progress_percentage }}%</div>
                                        </td>
                                        <td class="table-cell-center">
                                            <x-action-button :href="route('dentist.dentist.transitions.show', $transition)" variant="view" icon="fa-solid fa-eye"
                                                label="View transition" />
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="table-empty-state-cell">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fa-regular fa-clipboard-check"></i>
                                                </div>
                                                <h3 class="empty-state-title">No transitions found</h3>
                                                <p class="empty-state-sub">You are not currently involved in any dentist
                                                    continuity plans.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        {{ $transitions->links() }}
                    </div>
                </section>
            </div>
        </main>
    @endif
@endsection
