@extends('layouts.app')

@section('layout-role', $layoutRole ?? (request()->routeIs('dentist.*') ? 'dentist' : 'admin'))

@section('title', 'Service Types')

@section('styles')
    @vite('resources/css/pages/shared/service-types.css')
@endsection

@section('content')

    @php
        $layoutRole = $layoutRole ?? (request()->routeIs('dentist.*') ? 'dentist' : 'admin');

        $isDentistView = $layoutRole === 'dentist';

        $routePrefix = $isDentistView ? 'dentist' : 'admin';

        $authUser = auth()->user();
        $canViewServiceTypes = $authUser?->hasPermission('view_service_type') ?? false;
        $canCreateServiceType = $authUser?->hasPermission('create_service_type') ?? false;
        $canUpdateServiceType = $authUser?->hasPermission('update_default_service_type') ?? false;
        $canDeleteServiceType = $authUser?->hasPermission('delete_service_type') ?? false;
    @endphp

    <main id="mainContent"
        class="app-page-shell
           shared-service-types-page
           {{ $isDentistView ? 'dentist-service-types-view' : 'admin-service-types-view' }}
           page-enter
           mode-list">

        @if ($isDentistView)
            <div class="dentist-hero mb-6">
                <div class="dentist-hero-content">
                    <div class="dentist-hero-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="dentist-hero-eyebrow">
                            <i class="fa-solid fa-tooth"></i>
                            Clinical Services
                        </div>

                        <h2 class="dentist-hero-title">
                            Service Types
                        </h2>
                    </div>
                </div>
            </div>
        @else
            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Service Types</h1>
                    </div>

                    <div class="admin-banner-actions">
                        <span class="admin-banner-pill" id="serviceActiveCountPill">
                            Active Services:
                            <span data-service-count>
                                {{ $services->where('is_active_for_booking', true)->count() }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <div class="stat-grid">
            <div class="stat-card s-crimson">
                <div class="stat-card-info">
                    <span class="stat-label">Total Services</span>

                    <strong class="stat-value" data-service-stat-total>
                        {{ $services->count() }}
                    </strong>
                </div>
            </div>

            <div class="stat-card s-active">
                <div class="stat-card-info">
                    <span class="stat-label">Visible for Booking</span>

                    <strong class="stat-value" data-service-stat-visible>
                        {{ $services->where('is_active_for_booking', true)->count() }}
                    </strong>
                </div>
            </div>

            <div class="stat-card s-inactive">
                <div class="stat-card-info">
                    <span class="stat-label">Hidden</span>

                    <strong class="stat-value" data-service-stat-hidden>
                        {{ $services->where('is_active_for_booking', false)->count() }}
                    </strong>
                </div>
            </div>

            <div class="stat-card s-purple">
                <div class="stat-card-info">
                    <span class="stat-label">Default Services</span>

                    <strong class="stat-value" data-service-stat-default>
                        {{ $services->where('is_default', true)->count() }}
                    </strong>
                </div>
            </div>
        </div>

        <div class="content-lift">
            <div class="service-types-grid">

                @if ($canCreateServiceType)
                    <div class="min-w-0">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><i class="fa-solid fa-plus"></i></div>
                                    <span class="card-title">Add New Service</span>
                                </div>
                            </div>

                            <div class="card-body">
                                <form id="addServiceForm" method="POST"
                                    action="{{ route($routePrefix . '.service-types.store') }}" data-global-validation
                                    novalidate>
                                    @csrf

                                    <div class="global-form-group" data-global-field>
                                        <label for="serviceNameInput" class="global-form-label">
                                            Service Name
                                            <span class="required-mark">*</span>
                                        </label>

                                        <div class="global-voice-row is-textarea" data-voice-field>

                                            <div class="global-voice-control" data-clearable-field>
                                                <div class="global-control-wrap">

                                                    <i class="fa-solid fa-tag global-control-icon"></i>

                                                    <input type="text" id="serviceNameInput" name="name"
                                                        value="{{ old('name') }}"
                                                        class="form-input-custom global-form-icon global-control-with-action"
                                                        placeholder="e.g. Tooth Extraction" autocomplete="off"
                                                        maxlength="255" pattern="[A-Za-zÑñ ]+"
                                                        data-field-label="Service Name"
                                                        data-required-message="Please enter a service name."
                                                        data-pattern-message="Service name can only contain letters and spaces."
                                                        data-clearable-input required>

                                                    <button type="button" class="search-clear field-clear-btn"
                                                        data-field-clear aria-label="Clear service name">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>

                                                </div>
                                            </div>

                                            <x-voice-input target="#serviceNameInput" status-id="serviceNameVoiceStatus"
                                                label="Voice input for service name" title="Voice input" />

                                        </div>
                                    </div>

                                    <div class="global-form-group" data-global-field>
                                        <div class="global-label-row">
                                            <label for="serviceDescInput" class="global-form-label">
                                                Description
                                                <span class="field-optional">(Optional)</span>
                                            </label>

                                            <button type="button" class="service-copy-bullet-box" data-copy-bullet>
                                                <span class="service-copy-bullet-symbol">•</span>

                                                <span class="service-copy-bullet-label">
                                                    Copy this bullet
                                                </span>
                                            </button>
                                        </div>

                                        <div class="global-voice-row is-textarea" data-voice-field>

                                            <div class="global-voice-control" data-clearable-field>
                                                <div class="global-form-textarea-wrap">

                                                    <textarea id="serviceDescInput" name="description" class="form-input-custom global-form-textarea"
                                                        placeholder="Brief details about the service..." maxlength="255" data-char-limit="255"
                                                        data-char-counter="#serviceDescCount" data-clearable-input>{{ old('description') }}</textarea>

                                                    <button type="button"
                                                        class="search-clear field-clear-btn field-clear-btn--textarea"
                                                        data-field-clear aria-label="Clear description">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>

                                                    <span id="serviceDescCount" class="char-counter">
                                                        0 / 255 characters
                                                    </span>

                                                </div>
                                            </div>

                                            <x-voice-input target="#serviceDescInput" status-id="serviceDescVoiceStatus"
                                                label="Voice input for service description" title="Voice input" />

                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="ui-btn ui-btn-primary">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            <span>Save Service</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>

                                <span class="card-title">Existing Services</span>
                            </div>

                            <div class="card-header-right">
                                <x-view-toggle id="serviceTypeViewToggle" class="service-type-view-toggle"
                                    storage-key="serviceTypeView" list-view="#serviceTypeListView"
                                    grid-view="#serviceTypeGridView" />
                            </div>
                        </div>

                        <div id="serviceTypeListView" class="service-type-view table-list-view">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Service Name</th>
                                        <th class="table-cell-center">
                                            Booking Visibility
                                        </th>
                                        <th class="table-cell-center">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody id="serviceTypeTableBody">
                                    @forelse ($services->sortBy('id') as $service)
                                        <tr data-service-id="{{ $service->id }}">
                                            <td>
                                                <span class="table-tag table-tag-neutral">
                                                    #{{ $service->id }}
                                                </span>
                                            </td>

                                            <td class="table-cell-main">
                                                <div class="table-primary">
                                                    <span class="service-table-icon" aria-hidden="true">
                                                        <i class="fa-solid fa-tooth"></i>
                                                    </span>

                                                    <span class="card-title">
                                                        {{ $service->name }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="table-cell-center">
                                                @if ($service->is_active_for_booking)
                                                    <span class="status-pill status-active">
                                                        <span class="status-dot"></span>
                                                        Visible
                                                    </span>
                                                @else
                                                    <span class="status-pill status-inactive">
                                                        <span class="status-dot"></span>
                                                        Hidden
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="table-cell-center table-action-cell">
                                                <div class="ui-action-group">
                                                    @if ($canUpdateServiceType)
                                                        <button type="button" class="ui-action-btn ui-action-edit"
                                                            data-tooltip="Manage service" data-tooltip-tone="edit"
                                                            aria-label="Manage {{ $service->name }}"
                                                            data-service-action="edit"
                                                            data-service-id="{{ $service->id }}">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>
                                                    @endif

                                                    @if ($canDeleteServiceType && !$service->is_default)
                                                        <button type="button" class="ui-action-btn ui-action-delete"
                                                            data-tooltip="Delete service"
                                                            aria-label="Delete {{ $service->name }}"
                                                            data-service-action="delete"
                                                            data-service-id="{{ $service->id }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    @endif

                                                    @if ($canViewServiceTypes)
                                                        <button type="button" class="ui-action-btn ui-action-view"
                                                            data-tooltip="View details"
                                                            aria-label="View {{ $service->name }} details"
                                                            data-service-action="view"
                                                            data-service-id="{{ $service->id }}">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="table-empty-state-cell">
                                                <div id="serviceTypeListEmptyState" class="empty-state-host">
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>


                        <div id="serviceTypeGridView" class="service-type-view table-grid-view" hidden>
                            @if ($services->count())
                                <div id="serviceTypeGridContainer" class="table-record-grid">
                                    @foreach ($services->sortBy('id') as $service)
                                        <article class="table-record-card" data-service-id="{{ $service->id }}">
                                            <div class="service-grid-card">
                                                <div class="service-grid-topline">

                                                    <span class="table-tag table-tag-neutral">
                                                        #{{ $service->id }}
                                                    </span>

                                                    <div class="service-grid-statuses">

                                                        @if ($service->is_active_for_booking)
                                                            <span class="status-pill status-active">
                                                                <span class="status-dot"></span>
                                                                Visible
                                                            </span>
                                                        @else
                                                            <span class="status-pill status-inactive">
                                                                <span class="status-dot"></span>
                                                                Hidden
                                                            </span>
                                                        @endif

                                                        @if ($service->is_default)
                                                            <span class="status-pill status-default">
                                                                <span class="status-dot"></span>
                                                                Default
                                                            </span>
                                                        @endif

                                                    </div>

                                                </div>

                                                <div class="service-grid-main">

                                                    <span class="service-grid-icon" aria-hidden="true">
                                                        <i class="fa-solid fa-tooth"></i>
                                                    </span>

                                                    <div class="service-grid-copy">

                                                        <h3 class="table-record-title">
                                                            {{ $service->name }}
                                                        </h3>

                                                    </div>

                                                </div>

                                                <p class="service-type-card-desc">
                                                    {{ $service->description ?: 'No description provided.' }}
                                                </p>

                                                <div class="service-grid-footer">
                                                    <div class="ui-action-group">
                                                        @if ($canUpdateServiceType)
                                                            <button type="button" class="ui-action-btn ui-action-edit"
                                                                data-tooltip="Manage service" data-tooltip-tone="edit"
                                                                aria-label="Manage {{ $service->name }}"
                                                                data-service-action="edit"
                                                                data-service-id="{{ $service->id }}">
                                                                <i class="fa-solid fa-pen"></i>
                                                            </button>
                                                        @endif

                                                        @if ($canDeleteServiceType && !$service->is_default)
                                                            <button type="button" class="ui-action-btn ui-action-delete"
                                                                data-tooltip="Delete service"
                                                                aria-label="Delete {{ $service->name }}"
                                                                data-service-action="delete"
                                                                data-service-id="{{ $service->id }}">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        @endif

                                                        @if ($canViewServiceTypes)
                                                            <button type="button" class="ui-action-btn ui-action-view"
                                                                data-tooltip="View details"
                                                                aria-label="View {{ $service->name }} details"
                                                                data-service-action="view"
                                                                data-service-id="{{ $service->id }}">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div id="serviceTypeGridEmptyState" class="empty-state-host table-grid-empty"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @if ($canDeleteServiceType)
        <x-delete-confirm-modal id="deleteServiceModal" form-id="deleteServiceForm" name-id="deleteServiceName"
            title="Delete Service Type" helper="This service type will be permanently removed." />
    @endif

    <div class="ui-modal modal-theme-edit" id="manageServiceModal" aria-hidden="true">
        <form id="manageServiceForm" method="POST" class="ui-modal-card modal-lg" role="dialog" aria-modal="true"
            aria-labelledby="manageServiceTitle" data-global-validation data-discard-form
            data-discard-title="Discard service changes?" data-discard-subtitle="You have unsaved service updates."
            data-discard-message="Closing this modal will remove your changes to this service. Do you want to discard them?"
            novalidate onclick="event.stopPropagation()">
            @csrf
            @method('PUT')

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-pen"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title" id="manageServiceTitle">
                            Manage Service Type
                        </h3>

                        <p class="modal-subtitle">
                            Update service details and booking visibility
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="manageServiceModal" aria-label="Close modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="modal-form-grid-2">

                    <div class="global-form-group" data-global-field>
                        <label for="manageServiceName" class="global-form-label">
                            Service Name
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-voice-row is-textarea" data-voice-field>

                            <div class="global-voice-control">
                                <div class="global-control-wrap">

                                    <i class="fa-solid fa-tag global-control-icon"></i>

                                    <input type="text" id="manageServiceName" name="name"
                                        class="form-input-custom global-form-icon global-control-with-action" maxlength="255"
                                        autocomplete="off" pattern="[A-Za-zÑñ ]+" data-field-label="Service Name"
                                        data-required-message="Please enter a service name."
                                        data-pattern-message="Service name can only contain letters and spaces." required>

                                </div>
                            </div>

                            <x-voice-input target="#manageServiceName" status-id="manageServiceNameVoiceStatus"
                                label="Voice input for service name" title="Voice input" />

                        </div>
                    </div>

                    <div class="global-form-group" data-global-field>
                        <div class="global-label-row">
                            <label for="manageServiceDescription" class="global-form-label">
                                Description
                                <span class="field-optional">(Optional)</span>
                            </label>

                            <button type="button" class="service-copy-bullet-box" data-copy-bullet>
                                <span class="service-copy-bullet-symbol">•</span>

                                <span class="service-copy-bullet-label">
                                    Copy this bullet
                                </span>
                            </button>
                        </div>

                        <div class="global-voice-row is-textarea" data-voice-field>

                            <div class="global-voice-control">
                                <div class="global-form-textarea-wrap">

                                    <textarea id="manageServiceDescription" name="description" class="form-input-custom global-form-textarea"
                                        maxlength="255" data-char-limit="255" data-char-counter="#manageServiceDescCount"
                                        placeholder="Brief details about the service..."></textarea>

                                    <span id="manageServiceDescCount" class="char-counter">
                                        0 / 255 characters
                                    </span>

                                </div>
                            </div>

                            <x-voice-input target="#manageServiceDescription" status-id="manageServiceDescVoiceStatus"
                                label="Voice input for service description" title="Voice input" />

                        </div>
                    </div>

                    <div class="global-form-group field-group full" data-global-field>
                        <div class="service-booking-row">
                            <div class="service-booking-copy">
                                <div class="service-booking-icon">
                                    <i class="fa-solid fa-thumbtack"></i>
                                </div>

                                <div>
                                    <p class="service-booking-title">
                                        Show in Book Appointment
                                    </p>

                                    <p class="service-booking-description">
                                        Turn this off to hide the service from booking
                                        while keeping it in Service Types.
                                    </p>
                                </div>
                            </div>

                            <label class="global-switch">
                                <input type="checkbox" id="manageServiceBookingToggle" name="is_active_for_booking"
                                    value="1" class="global-switch-input"
                                    aria-label="Show service in Book Appointment">

                                <span class="global-switch-track" aria-hidden="true"></span>
                            </label>
                        </div>

                        <div id="manageDefaultNote" class="modal-helper-text">
                            This is a default service type. It can be edited
                            and hidden from booking, but it cannot be deleted.
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="manageServiceModal">
                    Cancel
                </button>

                <button type="submit" class="ui-btn ui-btn-edit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>

    <div id="serviceDetailsModal" class="ui-modal modal-theme-primary" aria-hidden="true">
        <div class="ui-modal-card modal-md" role="dialog" aria-modal="true" aria-labelledby="serviceDetailsTitle"
            onclick="event.stopPropagation()">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-eye"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 id="serviceDetailsTitle" class="modal-title">
                            Service Details
                        </h3>

                        <p class="modal-subtitle">
                            Complete service type information
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" onclick="closeModal('serviceDetailsModal')"
                    aria-label="Close service details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">

                <div class="modal-profile-card">

                    <div class="modal-profile-main">

                        <div class="modal-profile-main-copy">

                            <span class="modal-profile-eyebrow">
                                Service Type
                            </span>

                            <strong id="serviceDetailsName" class="modal-profile-name">
                                —
                            </strong>

                            <div class="service-details-status-row">

                                <span id="serviceDetailsVisibility" class="status-pill status-active">
                                    <span class="status-dot"></span>
                                    Visible
                                </span>

                                <span id="serviceDetailsDefault" class="status-pill status-default">
                                    <span class="status-dot"></span>
                                    Default
                                </span>

                            </div>

                        </div>

                    </div>

                    <div class="modal-profile-details modal-profile-details-single">

                        <div class="modal-profile-detail">

                            <div class="modal-profile-detail-icon">
                                <i class="fa-solid fa-hashtag"></i>
                            </div>

                            <div>
                                <span class="modal-profile-label">
                                    Service ID
                                </span>

                                <strong id="serviceDetailsId" class="modal-profile-value">
                                    —
                                </strong>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-form-section">

                    <div class="modal-section-heading">

                        <div class="modal-section-icon">
                            <i class="fa-solid fa-align-left"></i>
                        </div>

                        <div>
                            <h4>Description</h4>

                            <p>
                                Information shown for this service.
                            </p>
                        </div>

                    </div>

                    <div class="global-readonly-field">

                        <i class="fa-solid fa-file-lines"></i>

                        <span id="serviceDetailsDescription">
                            No description provided.
                        </span>

                    </div>

                </div>

            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" onclick="closeModal('serviceDetailsModal')">
                    Close
                </button>
            </div>
        </div>
    </div>
@endsection

@php
    $serviceTypePayload = $services
        ->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'is_active_for_booking' => (bool) $service->is_active_for_booking,
                'is_default' => (bool) $service->is_default,
            ];
        })
        ->values()
        ->toArray();

    $serviceTypeUpdateRoute = route($routePrefix . '.service-types.update', 0);
    $serviceTypeDestroyRoute = route($routePrefix . '.service-types.destroy', 0);

    $serviceTypeRoutes = [
        'update' => preg_replace('/0$/', '__SERVICE_ID__', $serviceTypeUpdateRoute),
        'destroy' => preg_replace('/0$/', '__SERVICE_ID__', $serviceTypeDestroyRoute),
    ];
@endphp

@section('scripts')
    <script>
        function copyTextToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }

            return new Promise((resolve, reject) => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                textarea.style.top = '-9999px';

                document.body.appendChild(textarea);
                textarea.select();

                try {
                    document.execCommand('copy');
                    resolve();
                } catch (error) {
                    reject(error);
                } finally {
                    textarea.remove();
                }
            });
        }

        function showServiceToast(
            type,
            title,
            message
        ) {
            if (
                typeof window.showToast ===
                'function'
            ) {
                window.showToast({
                    type,
                    title,
                    message
                });

                return;
            }

            console[
                type === 'error' ?
                'error' :
                'log'
            ](`${title}: ${message}`);
        }

        function initServiceBulletCopy() {
            document.querySelectorAll('[data-copy-bullet]').forEach((button) => {
                if (button.dataset.copyInitialized === 'true') return;

                button.dataset.copyInitialized = 'true';

                const label =
                    button.querySelector('.service-copy-bullet-label');
                const originalText = label?.textContent || 'Copy this bullet';

                button.addEventListener('click', async () => {
                    try {
                        await copyTextToClipboard('•');

                        button.classList.add('copied');

                        if (label) {
                            label.textContent = 'Copied';
                        }

                        showServiceToast(
                            'success',
                            'Copied',
                            'Bullet copied. You can now paste it in the description.'
                        );

                        setTimeout(() => {
                            button.classList.remove('copied');

                            if (label) {
                                label.textContent = originalText;
                            }
                        }, 1400);
                    } catch (error) {
                        showServiceToast(
                            'error',
                            'Copy failed',
                            'Unable to copy bullet.'
                        );

                        console.error('Copy failed:', error);
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', initServiceBulletCopy);

        window.openServiceDetailsModal = function(
            name,
            description,
            isActiveForBooking,
            isDefault,
            id
        ) {
            const nameElement =
                document.getElementById(
                    'serviceDetailsName'
                );

            const descriptionElement =
                document.getElementById(
                    'serviceDetailsDescription'
                );

            const idElement =
                document.getElementById(
                    'serviceDetailsId'
                );

            const visibilityElement =
                document.getElementById(
                    'serviceDetailsVisibility'
                );

            const defaultElement =
                document.getElementById(
                    'serviceDetailsDefault'
                );

            if (
                !nameElement ||
                !descriptionElement ||
                !idElement ||
                !visibilityElement ||
                !defaultElement
            ) {
                return;
            }

            nameElement.textContent =
                name || 'Unnamed service';

            descriptionElement.textContent =
                description ||
                'No description provided.';

            idElement.textContent =
                `#${id}`;

            visibilityElement.className =
                isActiveForBooking ?
                'status-pill status-active' :
                'status-pill status-inactive';

            visibilityElement.innerHTML =
                `
        <span class="status-dot"></span>
        ${isActiveForBooking
                ? 'Visible'
                : 'Hidden'
            }
    `;

            defaultElement.className =
                'status-pill status-default';

            defaultElement.innerHTML =
                `
        <span class="status-dot"></span>
        ${isDefault
                ? 'Default'
                : 'Custom'
            }
    `;

            window.openModal(
                'serviceDetailsModal'
            );
        };

        (() => {
            const CAN_VIEW_SERVICE_TYPES = @json($canViewServiceTypes);
            const CAN_CREATE_SERVICE_TYPE = @json($canCreateServiceType);
            const CAN_UPDATE_SERVICE_TYPE = @json($canUpdateServiceType);
            const CAN_DELETE_SERVICE_TYPE = @json($canDeleteServiceType);

            const initialServices = @json($serviceTypePayload);

            let serviceTypeServices = Array.isArray(initialServices) ? [...initialServices] : [];

            const routes = @json($serviceTypeRoutes);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value ||
                '';

            const escapeHtml = (value = '') => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const normalizeService = (service) => ({
                id: Number(service.id),
                name: service.name || '',
                description: service.description || '',
                is_active_for_booking: Boolean(service.is_active_for_booking),
                is_default: Boolean(service.is_default),
            });

            const serviceUpdateUrl = (id) => routes.update.replace('__SERVICE_ID__', encodeURIComponent(
                id));
            const serviceDestroyUrl = (id) => routes.destroy.replace('__SERVICE_ID__', encodeURIComponent(
                id));

            function sortServices() {
                serviceTypeServices.sort(
                    (a, b) => Number(a.id) - Number(b.id)
                );
            }

            function updateServiceCounts() {
                const totalCount = serviceTypeServices.length;

                const visibleCount = serviceTypeServices.filter(
                    service => service.is_active_for_booking
                ).length;

                const hiddenCount = totalCount - visibleCount;

                const defaultCount = serviceTypeServices.filter(
                    service => service.is_default
                ).length;

                document.querySelectorAll('[data-service-count]').forEach((node) => {
                    node.textContent = visibleCount;
                });

                document.querySelectorAll('[data-service-stat-total]').forEach((node) => {
                    node.textContent = totalCount;
                });

                document.querySelectorAll('[data-service-stat-visible]').forEach((node) => {
                    node.textContent = visibleCount;
                });

                document.querySelectorAll('[data-service-stat-hidden]').forEach((node) => {
                    node.textContent = hiddenCount;
                });

                document.querySelectorAll('[data-service-stat-default]').forEach((node) => {
                    node.textContent = defaultCount;
                });
            }

            function actionButtons(service) {
                const editButton = !CAN_UPDATE_SERVICE_TYPE ?
                    '' :
                    `
                <button
                    type="button"
                    class="ui-action-btn ui-action-edit"
                    data-tooltip="Manage service"
                    data-tooltip-tone="edit"
                    aria-label="Manage ${escapeHtml(service.name)}"
                    data-service-action="edit"
                    data-service-id="${service.id}"
                >
                    <i class="fa-solid fa-pen"></i>
                </button>
            `;

                const deleteButton = !CAN_DELETE_SERVICE_TYPE || service.is_default ?
                    '' :
                    `
                <button
                    type="button"
                    class="ui-action-btn ui-action-delete"
                    data-tooltip="Delete service"
                    aria-label="Delete ${escapeHtml(service.name)}"
                    data-service-action="delete"
                    data-service-id="${service.id}"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;

                const viewButton = !CAN_VIEW_SERVICE_TYPES ?
                    '' :
                    `
        <button
            type="button"
            class="ui-action-btn ui-action-view"
            data-tooltip="View details"
            aria-label="View ${escapeHtml(service.name)} details"
            data-service-action="view"
            data-service-id="${service.id}"
        >
            <i class="fa-solid fa-eye"></i>
        </button>
    `;

                return `
        ${editButton}
        ${deleteButton}
        ${viewButton}
    `;
            }

            function renderServiceEmptyState(
                host
            ) {
                if (!host) {
                    return;
                }

                window.EmptyState?.render({
                    host,
                    icon: 'fa-tooth',
                    title: 'No services found',
                    message: 'Service types will appear here once they are added.',
                });
            }

            function renderServiceTable() {
                const tbody =
                    document.getElementById(
                        'serviceTypeTableBody'
                    );

                if (!tbody) return;

                if (!serviceTypeServices.length) {
                    tbody.innerHTML = `
        <tr>
            <td
                colspan="4"
                class="table-empty-state-cell"
            >
                <div
                    id="serviceTypeListEmptyState"
                    class="empty-state-host"
                ></div>
            </td>
        </tr>
    `;

                    renderServiceEmptyState(
                        document.getElementById(
                            'serviceTypeListEmptyState'
                        )
                    );

                    return;
                }

                tbody.innerHTML =
                    serviceTypeServices
                    .map(function(service) {
                        const visibility =
                            service.is_active_for_booking ?
                            `
                    <span class="status-pill status-active">
                        <span class="status-dot"></span>
                        Visible
                    </span>
                ` :
                            `
                    <span class="status-pill status-inactive">
                        <span class="status-dot"></span>
                        Hidden
                    </span>
                `;

                        return `
            <tr data-service-id="${service.id}">
                        <td>
                            <span class="table-tag table-tag-neutral">
                                #${service.id}
                            </span>
                        </td>

                        <td class="table-cell-main">
                            <div class="table-primary">
                                <span class="service-table-icon" aria-hidden="true">
                                    <i class="fa-solid fa-tooth"></i>
                                </span>

                                <span class="card-title">
                                    ${escapeHtml(service.name)}
                                </span>
                            </div>
                        </td>

                        <td class="table-cell-center">
                            ${visibility}
                        </td>

                        <td class="table-cell-center table-action-cell">
                            <div class="ui-action-group">
                                ${actionButtons(service)}
                            </div>
                        </td>
                    </tr>
                `;
                    })
                    .join('');
            }

            function renderServiceGrid() {
                const gridView =
                    document.getElementById(
                        'serviceTypeGridView'
                    );

                if (!gridView) return;

                if (!serviceTypeServices.length) {
                    gridView.innerHTML = `
        <div
            id="serviceTypeGridEmptyState"
            class="empty-state-host table-grid-empty"
        ></div>
    `;

                    renderServiceEmptyState(
                        document.getElementById(
                            'serviceTypeGridEmptyState'
                        )
                    );

                    return;
                }

                const cards =
                    serviceTypeServices
                    .map(function(service) {
                        const visibility =
                            service.is_active_for_booking ?
                            `
                                <span class="status-pill status-active">
                                    <span class="status-dot"></span>
                                    Visible
                                </span>
                            ` :
                            `
                                <span class="status-pill status-inactive">
                                    <span class="status-dot"></span>
                                    Hidden
                                </span>
                            `;

                        const defaultBadge =
                            service.is_default ?
                            `
            <span class="status-pill status-default">
                <span class="status-dot"></span>
                Default
            </span>
        ` :
                            '';

                        const description =
                            service.description ?
                            escapeHtml(service.description) :
                            'No description provided.';

                        return `
                    <article
                        class="table-record-card"
                        data-service-id="${service.id}">
                        <div class="service-grid-card">
                            <div class="service-grid-topline">

                                <span class="table-tag table-tag-neutral">
                                    #${service.id}
                                </span>

                                <div class="service-grid-statuses">
                                    ${visibility}
                                    ${defaultBadge}
                                </div>

                            </div>

                            <div class="service-grid-main">

                                <span class="service-grid-icon" aria-hidden="true">
                                    <i class="fa-solid fa-tooth"></i>
                                </span>

                                <div class="service-grid-copy">

                                    <h3 class="table-record-title">
                                        ${escapeHtml(service.name)}
                                    </h3>

                                </div>

                            </div>

                            <p class="service-type-card-desc">
                                ${description}
                            </p>

                           <div class="service-grid-footer">
                                <div class="ui-action-group">
                                    ${actionButtons(service)}
                                </div>
                            </div>
                        </div>
                    </article>
                `;
                    })
                    .join('');

                gridView.innerHTML = `
        <div
            id="serviceTypeGridContainer"
            class="table-record-grid"
        >
            ${cards}
        </div>
    `;
            }

            function renderServices() {
                sortServices();
                updateServiceCounts();
                renderServiceTable();
                renderServiceGrid();
            }

            function firstValidationMessage(data, fallback = 'Please check the form and try again.') {
                const errors = data?.errors || {};
                const firstKey = Object.keys(errors)[0];

                if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey][0]) {
                    return errors[firstKey][0];
                }

                return data?.message || fallback;
            }

            async function parseJsonResponse(response) {
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    return response.json();
                }

                return {
                    success: false,
                    message: 'The server returned an unexpected response. Please check your route or controller.',
                };
            }

            function setButtonLoading(button, isLoading, loadingText = 'Saving...') {
                if (!button) return;

                if (isLoading) {
                    button.dataset.originalHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = `<i class="fa-solid fa-spinner spin"></i> ${loadingText}`;
                    return;
                }

                button.disabled = false;

                if (button.dataset.originalHtml) {
                    button.innerHTML = button.dataset.originalHtml;
                    delete button.dataset.originalHtml;
                }
            }

            async function submitJson(form, submitButton, loadingText = 'Saving...') {
                setButtonLoading(submitButton, true, loadingText);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrfToken ? {
                                'X-CSRF-TOKEN': csrfToken
                            } : {}),
                        },
                        body: new FormData(form),
                    });

                    const data = await parseJsonResponse(response);

                    if (!response.ok || data.success === false) {
                        throw data;
                    }

                    return data;
                } finally {
                    setButtonLoading(submitButton, false);
                }
            }

            function addOrUpdateService(service) {
                const normalized = normalizeService(service);
                const existingIndex = serviceTypeServices.findIndex((item) => Number(item.id) ===
                    Number(normalized
                        .id));

                if (existingIndex >= 0) {
                    serviceTypeServices[existingIndex] = normalized;
                } else {
                    serviceTypeServices.push(normalized);
                }

                renderServices();
            }

            function removeService(id) {
                serviceTypeServices = serviceTypeServices.filter((service) => Number(service.id) !==
                    Number(id));
                renderServices();
            }

            window.openManageServiceById = function(id) {
                if (!CAN_UPDATE_SERVICE_TYPE) {
                    showServiceToast('error', 'Access denied', 'You are not allowed to update service types.');
                    return;
                }

                const service = serviceTypeServices.find((item) => Number(item.id) === Number(id));

                if (!service) {
                    showServiceToast('error', 'Service not found',
                        'Unable to find the selected service.');
                    return;
                }

                window.openManageServiceModal(
                    serviceUpdateUrl(service.id),
                    service.name,
                    service.description,
                    service.is_active_for_booking,
                    service.is_default,
                    service.id
                );
            };

            window.openDeleteServiceById = function(id) {
                if (!CAN_DELETE_SERVICE_TYPE) {
                    showServiceToast('error', 'Access denied', 'You are not allowed to delete service types.');
                    return;
                }

                const service = serviceTypeServices.find((item) => Number(item.id) === Number(id));

                if (!service) {
                    showServiceToast('error', 'Service not found',
                        'Unable to find the selected service.');
                    return;
                }

                window.openDeleteModal(serviceDestroyUrl(service.id), service.name, service.id);
            };

            window.openDeleteModal = function(
                actionUrl,
                serviceName,
                serviceId = null
            ) {
                if (!CAN_DELETE_SERVICE_TYPE) {
                    showServiceToast('error', 'Access denied', 'You are not allowed to delete service types.');
                    return;
                }

                const form =
                    window.openDeleteConfirmModal?.({
                        modalId: 'deleteServiceModal',

                        formId: 'deleteServiceForm',

                        nameId: 'deleteServiceName',

                        action: actionUrl,

                        itemName: serviceName,

                        recordId: serviceId,
                    });

                if (!form) return;

                form.dataset.serviceId =
                    serviceId ||
                    String(actionUrl)
                    .split('/')
                    .filter(Boolean)
                    .pop() ||
                    '';
            };

            window.openManageServiceModal = function(actionUrl, serviceName, serviceDescription,
                isActiveForBooking,
                isDefault, serviceId = null) {
                if (!CAN_UPDATE_SERVICE_TYPE) {
                    showServiceToast('error', 'Access denied', 'You are not allowed to update service types.');
                    return;
                }

                const form = document.getElementById('manageServiceForm');
                const nameInput = document.getElementById('manageServiceName');
                const descInput = document.getElementById('manageServiceDescription');
                const bookingToggle = document.getElementById('manageServiceBookingToggle');
                const defaultNote = document.getElementById('manageDefaultNote');

                if (!form || !nameInput || !descInput || !bookingToggle || !defaultNote) {
                    console.error('Manage modal elements not found.');
                    return;
                }

                form.action = actionUrl;
                form.dataset.serviceId = serviceId || String(actionUrl).split('/').filter(Boolean)
                    .pop() || '';

                nameInput.value = serviceName ?? '';
                descInput.value = serviceDescription ?? '';

                window.initCharLimitFields?.(
                    document.getElementById(
                        'manageServiceModal'
                    )
                );

                descInput.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );

                bookingToggle.checked = Boolean(isActiveForBooking);

                defaultNote.style.display = isDefault ? 'block' : 'none';

                window.openModal(
                    'manageServiceModal'
                );

                requestAnimationFrame(() => {
                    window.DiscardChanges
                        ?.captureForm(form);
                });

                window.initGlobalVoiceInputs?.(
                    form
                );

                document.dispatchEvent(
                    new CustomEvent(
                        'voice:refresh', {
                            detail: {
                                root: document.getElementById(
                                    'manageServiceModal'
                                )
                            }
                        }
                    )
                );
                setTimeout(() => {
                    nameInput.focus();
                }, 180);
            };

            function showServiceFormErrors(
                form,
                response
            ) {
                const errors =
                    response?.errors || {};

                Object.entries(errors)
                    .forEach(([name, messages]) => {
                        const field =
                            form.querySelector(
                                `[name="${CSS.escape(name)}"]`
                            );

                        if (!field) return;

                        const message =
                            Array.isArray(messages) ?
                            messages[0] :
                            messages;

                        window
                            .showFormInputValidationMessage
                            ?.(field, String(message || ''));
                    });
            }

            function bindAjaxForms() {
                const addForm =
                    document.getElementById(
                        'addServiceForm'
                    );

                const manageForm =
                    document.getElementById(
                        'manageServiceForm'
                    );

                const deleteForm =
                    document.getElementById(
                        'deleteServiceForm'
                    );

                addForm?.addEventListener(
                    'submit',
                    async event => {
                        event.preventDefault();

                        const validation =
                            window.validateGlobalForm?.(
                                addForm
                            );

                        if (
                            validation &&
                            !validation.valid
                        ) {
                            return;
                        }

                        if (!CAN_CREATE_SERVICE_TYPE) {
                            showServiceToast('error', 'Access denied',
                                'You are not allowed to create service types.');
                            return;
                        }

                        const submitButton =
                            addForm.querySelector(
                                '[type="submit"]'
                            );

                        try {
                            const data =
                                await submitJson(
                                    addForm,
                                    submitButton,
                                    'Saving...'
                                );

                            addOrUpdateService(
                                data.service
                            );

                            addForm.reset();

                            requestAnimationFrame(() => {
                                addForm
                                    .querySelectorAll(
                                        '[data-clearable-input]'
                                    )
                                    .forEach(field => {
                                        field.dispatchEvent(
                                            new Event(
                                                'input', {
                                                    bubbles: true
                                                }
                                            )
                                        );
                                    });
                            });

                            document
                                .getElementById(
                                    'serviceNameInput'
                                )
                                ?.classList.remove(
                                    'is-valid',
                                    'is-invalid'
                                );

                            document
                                .getElementById(
                                    'serviceDescInput'
                                )
                                ?.classList.remove(
                                    'is-valid',
                                    'is-invalid'
                                );

                            showServiceToast(
                                'success',
                                'Service added',
                                data.service
                                ?.is_active_for_booking ?
                                'Patients can now select it when booking.' :
                                'Saved in Service Types and hidden from booking.'
                            );
                        } catch (error) {
                            showServiceFormErrors(
                                addForm,
                                error
                            );

                            showServiceToast(
                                'error',
                                'Add failed',
                                firstValidationMessage(
                                    error,
                                    'Unable to add service type.'
                                )
                            );
                        }
                    }
                );

                manageForm?.addEventListener(
                    'submit',
                    async event => {
                        event.preventDefault();

                        const validation =
                            window.validateGlobalForm?.(
                                manageForm
                            );

                        if (
                            validation &&
                            !validation.valid
                        ) {
                            return;
                        }

                        if (!CAN_UPDATE_SERVICE_TYPE) {
                            showServiceToast('error', 'Access denied',
                                'You are not allowed to update service types.');
                            return;
                        }

                        const submitButton =
                            manageForm.querySelector(
                                '[type="submit"]'
                            );

                        try {
                            const data =
                                await submitJson(
                                    manageForm,
                                    submitButton,
                                    'Saving...'
                                );

                            addOrUpdateService(
                                data.service
                            );

                            window.DiscardChanges
                                ?.captureForm(
                                    manageForm
                                );

                            window.closeModal(
                                'manageServiceModal'
                            );

                            showServiceToast(
                                'success',
                                'Changes saved',
                                'Service details and booking visibility updated.'
                            );
                        } catch (error) {
                            showServiceFormErrors(
                                manageForm,
                                error
                            );

                            showServiceToast(
                                'error',
                                'Update failed',
                                firstValidationMessage(
                                    error,
                                    'Unable to update service type.'
                                )
                            );
                        }
                    }
                );

                deleteForm?.addEventListener(
                    'submit',
                    async event => {
                        event.preventDefault();

                        if (!CAN_DELETE_SERVICE_TYPE) {
                            showServiceToast('error', 'Access denied',
                                'You are not allowed to delete service types.');
                            return;
                        }

                        const submitButton =
                            deleteForm.querySelector(
                                '[type="submit"]'
                            );

                        try {
                            const data =
                                await submitJson(
                                    deleteForm,
                                    submitButton,
                                    'Deleting...'
                                );

                            removeService(
                                data.deleted_id ||
                                deleteForm.dataset.serviceId
                            );

                            window.closeModal(
                                'deleteServiceModal'
                            );

                            showServiceToast(
                                'success',
                                'Service deleted',
                                'Removed from the service list.'
                            );
                        } catch (error) {
                            showServiceToast(
                                'error',
                                'Delete failed',
                                firstValidationMessage(
                                    error,
                                    'Unable to delete service type.'
                                )
                            );
                        }
                    }
                );
            }

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-service-action]');
                if (!button) return;

                const id = button.dataset.serviceId;

                if (button.dataset.serviceAction === 'edit') {
                    window.openManageServiceById(id);
                }

                if (button.dataset.serviceAction === 'delete') {
                    window.openDeleteServiceById(id);
                }

                if (
                    button.dataset.serviceAction ===
                    'view'
                ) {
                    if (!CAN_VIEW_SERVICE_TYPES) {
                        showServiceToast('error', 'Access denied',
                            'You are not allowed to view service details.');
                        return;
                    }

                    const service =
                        serviceTypeServices.find(
                            item =>
                            Number(item.id) ===
                            Number(id)
                        );

                    if (!service) return;

                    window.openServiceDetailsModal(
                        service.name,
                        service.description,
                        service.is_active_for_booking,
                        service.is_default,
                        service.id
                    );
                }

            });

            function initServiceTypesPage() {
                renderServices();
                bindAjaxForms();
            }

            if (
                document.readyState ===
                'loading'
            ) {
                document.addEventListener(
                    'DOMContentLoaded',
                    initServiceTypesPage, {
                        once: true
                    }
                );
            } else {
                initServiceTypesPage();
            }

        })();
    </script>
@endsection
