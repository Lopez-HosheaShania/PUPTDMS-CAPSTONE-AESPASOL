@extends('layouts.admin')

@section('title', 'Service Types | Admin Dashboard')

@section('content')
<main id="mainContent" class="admin-page-shell page-enter mode-list">

    <div class="page-banner">
        <div class="page-banner-inner">
            <div>
                <h1 class="page-title">Service Types</h1>
            </div>

            <div class="admin-banner-actions">
                <span class="admin-banner-pill" id="serviceActiveCountPill">
                    Active Services: <span data-service-count>{{ $services->count() }}</span>
                </span>
            </div>
        </div>
    </div>

    <div class="relative z-10 mt-4 px-4 sm:px-6 lg:px-7 pb-8">

    <div class="content-lift">
        <div class="main-grid">

            <div class="admin-stack">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon"><i class="fa-solid fa-plus"></i></div>
                            <span class="card-title">Add New Service</span>
                        </div>
                    </div>

                    <div class="admin-card-pad">
                        <form id="addServiceForm" method="POST" action="{{ route('admin.service-types.store') }}"
                            novalidate>
                            @csrf

                            <div class="st-form-group">
                                <label class="st-label">Service Name</label>
                                <div class="st-voice-row">
                                    <div class="st-input-wrap">
                                        <i class="fa-solid fa-tag st-input-icon"></i>
                                        <input type="text" id="serviceNameInput" name="name"
                                            placeholder="e.g. Tooth Extraction" autocomplete="off"
                                            value="{{ old('name') }}" class="st-input with-icon no-voice">
                                        <button type="button" id="serviceNameClearBtn" class="st-voice-clear-btn hidden"
                                            aria-label="Clear service name" title="Clear">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                    {{-- Mic toggle is a sibling of st-input-wrap, not inside it --}}
                                    <div class="service-voice-toggle" id="serviceNameVoiceToggle"></div>
                                </div>

                                <div id="nameClientError" class="st-field-error admin-hidden">
                                    <i class="fa-solid fa-circle-exclamation"></i> Please provide a service name.
                                </div>

                                @error('name')
                                <div class="st-field-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="st-form-group">
                                <div class="st-label-row">
                                    <label class="st-label">Description (Optional)</label>

                                    <button type="button" class="st-copy-bullet-box" data-copy-bullet
                                        data-copy-target="#serviceDescInput" title="Copy bullet">
                                        <span class="st-copy-bullet-symbol">•</span>
                                        <span class="st-copy-bullet-label">Copy this bullet</span>
                                    </button>
                                </div>

                                <div class="st-voice-row is-textarea">
                                    <div class="st-input-wrap st-textarea-wrap">
                                        <textarea id="serviceDescInput" name="description"
                                            placeholder="Brief details about the service..."
                                            class="st-input st-textarea no-voice"
                                            maxlength="255">{{ old('description') }}</textarea>

                                        <div id="serviceDescCount" class="st-char-count">0 / 255</div>
                                        <button type="button" id="serviceDescClearBtn" class="st-voice-clear-btn hidden"
                                            aria-label="Clear description" title="Clear">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                    <div class="service-voice-toggle" id="serviceDescVoiceToggle"></div>
                                </div>

                                @error('description')
                                <div class="st-field-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}
                                </div>
                                @enderror
                            </div>

                            <button type="submit" class="st-btn st-btn-primary st-save-service-btn">
                                <span class="btn-confirm-icon">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                </span>
                                Save Service
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="admin-stack">
                <div class="card">
                    <div class="card-header service-list-card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon"><i class="fa-solid fa-list-check"></i></div>
                            <span class="card-title">Existing Services</span>
                        </div>

                        <div class="service-card-header-actions">
                            <span class="entry-badge" id="serviceEntryCountBadge">
                                {{ $services->count() }} {{ Str::plural('Item', $services->count()) }}
                            </span>

                            <div class="view-toggle-container service-type-view-toggle" id="serviceTypeViewToggle"
                                aria-label="View options">
                                <span class="view-slider" aria-hidden="true"></span>

                                <button type="button" class="btn-view-mode active" id="serviceTypeListBtn"
                                    title="List view" aria-label="List view" aria-pressed="true">
                                    <i class="fa-solid fa-table-list"></i>
                                </button>

                                <button type="button" class="btn-view-mode" id="serviceTypeGridBtn" title="Grid view"
                                    aria-label="Grid view" aria-pressed="false">
                                    <i class="fa-solid fa-grip"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="service-type-view" id="serviceTypeListView">
                        <div class="admin-scroll-x">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="service-col-id">ID</th>
                                        <th class="service-col-name">Service Name</th>
                                        <th>Description</th>
                                        <th class="service-col-visibility">Booking Visibility</th>
                                        <th class="service-col-action">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="serviceTypeTableBody">
                                    @forelse($services as $service)
                                    <tr>
                                        <td><span class="service-badge">#{{ $service->id }}</span></td>
                                        <td>
                                            <div class="service-name-cell">
                                                <div class="service-name-icon">
                                                    <i class="fa-solid fa-tooth"></i>
                                                </div>
                                                <span class="service-name-text">
                                                    {{ $service->name }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="service-desc-cell">
                                            {{ $service->description ?: '—' }}
                                        </td>
                                        <td class="service-center-cell">
                                            <div class="service-visibility-actions">
                                                @if ($service->is_active_for_booking)
                                                <span class="service-visibility-badge is-visible">
                                                    <i class="fa-solid fa-thumbtack"></i> Visible
                                                </span>
                                                @else
                                                <span class="service-visibility-badge is-hidden">
                                                    <i class="fa-solid fa-eye-slash"></i> Hidden
                                                </span>
                                                @endif

                                                @if ($service->is_default)
                                                <span class="service-badge service-badge-bookable">
                                                    Default
                                                </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="service-center-cell">
                                            <div class="service-inline-actions">
                                                <button type="button" class="action-btn btn-edit" title="Manage service"
                                                    onclick="openManageServiceModal(
                                                                '{{ route('admin.service-types.update', $service->id) }}',
                                                                @js($service->name),
                                                                @js($service->description),
                                                                {{ $service->is_active_for_booking ? 'true' : 'false' }},
                                                                {{ $service->is_default ? 'true' : 'false' }}
                                                            )">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>

                                                @if (!$service->is_default)
                                                <button type="button" class="action-btn btn-delete-service"
                                                    title="Delete service"
                                                    onclick="openDeleteModal('{{ route('admin.service-types.destroy', $service->id) }}', '{{ addslashes($service->name) }}')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <div class="empty-icon"><i class="fa-solid fa-folder-open"></i>
                                                </div>
                                                <p class="service-empty-title">
                                                    No services found
                                                </p>
                                                <p class="service-empty-subtitle">
                                                    Your clinic doesn't have any service types yet. Use the form to
                                                    add
                                                    one.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="service-type-view" id="serviceTypeGridView" hidden>
                        @if ($services->count())
                        <div class="service-types-grid" id="serviceTypeGridContainer">
                            @foreach ($services as $service)
                            <div class="service-type-card">
                                <div class="service-type-card-top">
                                    <span class="service-badge service-type-card-id">#{{ $service->id }}</span>

                                    @if ($service->is_default)
                                    <span class="service-badge service-badge-bookable">
                                        Default
                                    </span>
                                    @endif
                                </div>

                                <div class="service-type-card-name-wrap">
                                    <div class="service-type-card-icon">
                                        <i class="fa-solid fa-tooth"></i>
                                    </div>
                                    <div class="service-type-card-name">{{ $service->name }}</div>
                                </div>

                                <div class="service-type-card-desc-wrap">
                                    <div class="service-type-card-label">Description</div>
                                    <div class="service-type-card-desc">
                                        {{ $service->description ?: '—' }}
                                    </div>
                                </div>

                                <div class="service-type-card-footer">
                                    <div class="service-card-actions">
                                        @if ($service->is_active_for_booking)
                                        <span class="service-visibility-badge is-visible">
                                            <i class="fa-solid fa-thumbtack"></i> Visible
                                        </span>
                                        @else
                                        <span class="service-visibility-badge is-hidden">
                                            <i class="fa-solid fa-eye-slash"></i> Hidden
                                        </span>
                                        @endif
                                    </div>

                                    <div class="service-type-card-actions">
                                        <button type="button" class="action-btn btn-edit" title="Manage service"
                                            onclick="openManageServiceModal(
                                                            '{{ route('admin.service-types.update', $service->id) }}',
                                                            @js($service->name),
                                                            @js($service->description),
                                                            {{ $service->is_active_for_booking ? 'true' : 'false' }},
                                                            {{ $service->is_default ? 'true' : 'false' }}
                                                        )">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        @if (!$service->is_default)
                                        <button type="button" class="action-btn btn-delete-service"
                                            title="Delete service"
                                            onclick="openDeleteModal('{{ route('admin.service-types.destroy', $service->id) }}', '{{ addslashes($service->name) }}')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                            <p class="service-empty-title">
                                No services found
                            </p>
                            <p class="service-empty-subtitle">
                                Your clinic doesn't have any service types yet. Use the form to add one.
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ui-modal modal-overlay st-delete-confirm-modal" id="deleteServiceModal"
        onclick="closeModalOnBackdrop(event, 'deleteServiceModal')" aria-hidden="true">

        <div class="modal-box-inner um-user-modal um-user-modal-sm st-delete-user-modal"
            onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="deleteServiceTitle">

            <div class="st-delete-head">
                <div class="st-delete-head-left">
                    <div class="st-delete-head-icon">
                        <i class="fa-solid fa-trash"></i>
                    </div>

                    <div>
                        <h3 id="deleteServiceTitle" class="st-delete-title">Delete Service Type</h3>
                        <p class="st-delete-subtitle">This action requires confirmation</p>
                    </div>
                </div>

                <button type="button" onclick="closeDeleteModal()" class="um-modal-x" aria-label="Close delete modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="st-delete-body">
                <div class="st-delete-alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>

                    <div>
                        <p>
                            Are you sure you want to delete
                            <strong id="deleteServiceName" class="st-delete-name"></strong>?
                        </p>
                        <span>This action cannot be undone.</span>
                    </div>
                </div>

                <div class="st-delete-actions">
                    <button type="button" onclick="closeDeleteModal()" class="modal-btn-ghost">
                        Cancel
                    </button>

                    <form id="deleteServiceForm" method="POST" action="" class="service-delete-form">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="st-delete-confirm-btn">
                            <i class="fa-solid fa-trash"></i>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="ui-modal st-manage-modal" id="manageServiceModal"
        onclick="closeModalOnBackdrop(event, 'manageServiceModal')">
        <div class="ui-modal-card st-modal-box" role="dialog" aria-modal="true" aria-labelledby="manageServiceTitle">
            <form id="manageServiceForm" method="POST" class="st-manage-form">
                @csrf
                @method('PUT')

                <div class="st-modal-header">
                    <div class="st-modal-header-left">
                        <div class="st-modal-header-icon">
                            <i class="fa-solid fa-pen"></i>
                        </div>

                        <div>
                            <h3 class="st-modal-title" id="manageServiceTitle">Manage Service Type</h3>
                            <p class="st-modal-subtitle">Update service details and booking visibility</p>
                        </div>
                    </div>

                    <button type="button" class="st-modal-close" onclick="closeModal('manageServiceModal')"
                        aria-label="Close modal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="st-modal-body">
                    <div class="st-panel">
                        <label class="st-modal-label">Service Name <span class="text-red-500">*</span></label>

                        <div class="st-modal-voice-row">
                            <div class="st-modal-field-wrap">
                                <span class="st-modal-field-icon"><i class="fa-solid fa-tag"></i></span>
                                <input type="text" id="manageServiceName" name="name" class="st-modal-input no-voice"
                                    maxlength="255" required>
                            </div>

                            <div class="service-voice-toggle" id="manageServiceNameVoiceToggle"></div>
                        </div>
                    </div>

                    <div class="st-panel">
                        <div class="st-label-row">
                            <label class="st-modal-label">Description</label>

                            <button type="button" class="st-copy-bullet-box" data-copy-bullet
                                data-copy-target="#manageServiceDescription" title="Copy bullet">
                                <span class="st-copy-bullet-symbol">•</span>
                                <span class="st-copy-bullet-label">Copy this bullet</span>
                            </button>
                        </div>

                        <div class="st-modal-voice-row st-modal-voice-row--textarea">
                            <div class="st-modal-textarea-wrap">
                                <textarea id="manageServiceDescription" name="description"
                                    class="st-modal-textarea no-voice" maxlength="255"
                                    placeholder="Brief details about the service..."></textarea>
                            </div>

                            <div class="service-voice-toggle" id="manageServiceDescVoiceToggle"></div>
                        </div>
                    </div>

                    <div class="st-panel st-col-span-2">
                        <div class="st-active-card">
                            <div class="st-active-card-left">
                                <div class="st-active-badge">
                                    <i class="fa-solid fa-thumbtack"></i>
                                </div>

                                <div>
                                    <p class="st-active-title">Show in Book Appointment</p>
                                    <p class="st-active-desc">Turn this off if you want the service hidden from booking
                                        but still kept in Service Types.</p>
                                </div>
                            </div>

                            <label class="st-switch">
                                <input type="checkbox" id="manageServiceBookingToggle" name="is_active_for_booking"
                                    value="1">
                                <span class="st-switch-slider"></span>
                            </label>
                        </div>

                        <div id="manageDefaultNote" class="st-default-note admin-hidden">
                            This is a default service type. It can be edited and hidden from booking, but it cannot be
                            deleted.
                        </div>
                    </div>
                </div>

                <div class="st-modal-footer">
                    <button type="button" class="st-btn st-btn-ghost" onclick="closeModal('manageServiceModal')">
                        Cancel
                    </button>

                    <button type="submit" class="st-btn st-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>
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

$serviceTypeUpdateRoute = route('admin.service-types.update', 0);
$serviceTypeDestroyRoute = route('admin.service-types.destroy', 0);

$serviceTypeRoutes = [
'store' => route('admin.service-types.store'),
'update' => preg_replace('/0$/', '__SERVICE_ID__', $serviceTypeUpdateRoute),
'destroy' => preg_replace('/0$/', '__SERVICE_ID__', $serviceTypeDestroyRoute),
];
@endphp

@section('scripts')
<script>
    const addServiceForm = document.getElementById('addServiceForm');
    const serviceNameInput = document.getElementById('serviceNameInput');
    const nameClientError = document.getElementById('nameClientError');

    function setAddServiceNameError(message = '') {
        if (!serviceNameInput || !nameClientError) return;

        if (message) {
            nameClientError.classList.remove('admin-hidden');

            if (typeof window.setFieldState === 'function') {
                window.setFieldState('serviceNameInput', 'nameClientError', message);
            } else {
                serviceNameInput.classList.add('is-invalid');
                serviceNameInput.classList.remove('is-valid');
                nameClientError.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${message}`;
            }

            return;
        }

        nameClientError.classList.add('admin-hidden');

        if (typeof window.setFieldState === 'function') {
            window.setFieldState('serviceNameInput', 'nameClientError', '');
        } else {
            serviceNameInput.classList.remove('is-invalid');
            serviceNameInput.classList.add('is-valid');
            nameClientError.innerHTML = '';
        }
    }

    addServiceForm?.addEventListener('submit', function (e) {
        const value = serviceNameInput?.value.trim() || '';

        if (!value) {
            e.preventDefault();
            setAddServiceNameError('Please provide a service name.');
            serviceNameInput?.focus();
        }
    });

    serviceNameInput?.addEventListener('input', function () {
        if (this.value.trim()) {
            setAddServiceNameError('');
        } else {
            this.classList.remove('is-valid');
        }
    });

    function openDeleteModal(actionUrl, serviceName) {
        const form = document.getElementById('deleteServiceForm');
        const name = document.getElementById('deleteServiceName');

        if (!form || !name) return;

        name.textContent = serviceName;
        form.action = actionUrl;

        window.openModal('deleteServiceModal');
    }

    function closeDeleteModal() {
        window.closeModal('deleteServiceModal');
    }

    function openManageServiceModal(actionUrl, serviceName, serviceDescription, isActiveForBooking, isDefault) {
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
        nameInput.value = serviceName ?? '';
        descInput.value = serviceDescription ?? '';
        bookingToggle.checked = Boolean(isActiveForBooking);

        defaultNote.classList.toggle('admin-hidden', !isDefault);
        defaultNote.style.display = isDefault ? 'block' : 'none';

        window.openModal('manageServiceModal');

        setTimeout(() => {
            nameInput.focus();
        }, 180);
    }

    const SERVICE_TYPE_VIEW_KEY = 'serviceTypeView';

    function getPreferredServiceTypeView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem(SERVICE_TYPE_VIEW_KEY) || 'list';
    }

    function applyServiceTypeView(view, save = true) {
        const root = document.getElementById('mainContent');
        const listView = document.getElementById('serviceTypeListView');
        const gridView = document.getElementById('serviceTypeGridView');
        const listBtn = document.getElementById('serviceTypeListBtn');
        const gridBtn = document.getElementById('serviceTypeGridBtn');

        if (!listView || !gridView) return;

        const finalView = window.innerWidth <= 767 ? 'grid' : (view === 'grid' ? 'grid' : 'list');
        const isGrid = finalView === 'grid';

        listView.hidden = isGrid;
        gridView.hidden = !isGrid;

        root?.classList.toggle('mode-grid', isGrid);
        root?.classList.toggle('mode-list', !isGrid);

        if (listBtn) {
            listBtn.classList.toggle('active', !isGrid);
            listBtn.setAttribute('aria-pressed', !isGrid ? 'true' : 'false');
        }

        if (gridBtn) {
            gridBtn.classList.toggle('active', isGrid);
            gridBtn.setAttribute('aria-pressed', isGrid ? 'true' : 'false');
        }

        if (save && window.innerWidth > 767) {
            localStorage.setItem(SERVICE_TYPE_VIEW_KEY, finalView);
        }
    }

    function initServiceTypeViewToggle() {
        const listBtn = document.getElementById('serviceTypeListBtn');
        const gridBtn = document.getElementById('serviceTypeGridBtn');

        applyServiceTypeView(getPreferredServiceTypeView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', () => applyServiceTypeView('list', true));
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', () => applyServiceTypeView('grid', true));
        }
    }

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

    function initServiceBulletCopy() {
        document.querySelectorAll('[data-copy-bullet]').forEach((button) => {
            if (button.dataset.copyInitialized === 'true') return;

            button.dataset.copyInitialized = 'true';

            const label = button.querySelector('.st-copy-bullet-label');
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

                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initServiceBulletCopy);

    document.addEventListener('DOMContentLoaded', () => {
        initServiceTypeViewToggle();

        function bindVoiceClear(fieldId, clearBtnId) {
            const field = document.getElementById(fieldId);
            const clearBtn = document.getElementById(clearBtnId);
            if (!field || !clearBtn) return;

            const toggleClear = () => {
                if ((field.value || '').trim().length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            };

            field.addEventListener('input', toggleClear);

            clearBtn.addEventListener('click', () => {
                field.value = '';
                field.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                field.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                toggleClear();
                field.focus();
            });

            toggleClear();
        }

        bindVoiceClear('serviceNameInput', 'serviceNameClearBtn');
        bindVoiceClear('serviceDescInput', 'serviceDescClearBtn');

        // ─────────────────────────────────────────────────────────────
        // Voice controllers for service inputs
        // ─────────────────────────────────────────────────────────────
        (function initServiceVoice() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) return;

            const voiceInputs = [{
                inputId: 'serviceNameInput',
                toggleWrapperId: 'serviceNameVoiceToggle',
                micId: 'serviceNameMicBtn'
            },
            {
                inputId: 'serviceDescInput',
                toggleWrapperId: 'serviceDescVoiceToggle',
                micId: 'serviceDescMicBtn'
            },
            {
                inputId: 'manageServiceName',
                toggleWrapperId: 'manageServiceNameVoiceToggle',
                micId: 'manageServiceNameMicBtn'
            },
            {
                inputId: 'manageServiceDescription',
                toggleWrapperId: 'manageServiceDescVoiceToggle',
                micId: 'manageServiceDescMicBtn'
            }
            ];

            voiceInputs.forEach(config => {
                const input = document.getElementById(config.inputId);
                const toggleWrapper = document.getElementById(config.toggleWrapperId);
                if (!input || !toggleWrapper) return;

                // Build mic button
                const micBtn = document.createElement('button');
                micBtn.type = 'button';
                micBtn.id = config.micId;
                micBtn.className = 'voice-search-mic external';
                micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
                micBtn.title = 'Toggle voice input';
                toggleWrapper.appendChild(micBtn);

                // Build status chip
                const status = document.createElement('span');
                status.className = 'voice-status hidden';
                status.setAttribute('aria-hidden', 'true');
                status.setAttribute('aria-live', 'polite');
                toggleWrapper.appendChild(status);

                // State
                let recognition = null;
                let listening = false;
                let manualStop = false;
                let capturedText = false; // ← tracks whether any speech was recorded

                const setStatus = (text, state) => {
                    status.textContent = text || '';
                    status.className = state ? `voice-status is-${state}` : 'voice-status';
                    if (!text) status.classList.add('hidden');
                    else status.classList.remove('hidden');
                };

                const setMicState = (active) => {
                    micBtn.classList.toggle('mic-active', !!active);
                    micBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
                    micBtn.innerHTML = active ?
                        '<i class="fa-solid fa-stop"></i>' :
                        '<i class="fa-solid fa-microphone"></i>';
                };

                // ── FIX: manual stop now checks whether speech was captured ──
                const stopNow = () => {
                    manualStop = true;
                    listening = false;
                    setMicState(false);

                    if (capturedText) {
                        setStatus('Voice captured.', 'success');
                        setTimeout(() => setStatus('', null), 1200);
                    } else {
                        setStatus("Didn't catch that. Try again.", 'error');
                        setTimeout(() => setStatus('', null), 2500);
                    }

                    if (recognition) {
                        try {
                            recognition.abort();
                        } catch (e) {
                            try {
                                recognition.stop();
                            } catch (_) { }
                        }
                    }
                };

                const createRecognition = () => {
                    capturedText = false; // reset per session

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;

                    let sawSpeech = false;
                    let timeoutId = null;
                    const LISTEN_TIMEOUT = 6000;

                    const clearTimeout_ = () => {
                        if (timeoutId) {
                            clearTimeout(timeoutId);
                            timeoutId = null;
                        }
                    };

                    r.onstart = () => {
                        timeoutId = setTimeout(() => {
                            if (listening && !sawSpeech) {
                                try {
                                    r.stop();
                                } catch (e) { }
                            }
                        }, LISTEN_TIMEOUT);
                    };

                    r.onspeechend = () => {
                        clearTimeout_();
                        try {
                            r.stop();
                        } catch (e) { }
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const result = event.results[i];
                            const chunk = (result?.[0]?.transcript ?? '').trim();
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (result.isFinal) {
                                transcript = (transcript + ' ' + chunk).trim();
                            } else if (!transcript) {
                                transcript = chunk;
                            }
                        }
                        transcript = transcript.trim();
                        if (transcript) {
                            clearTimeout_();
                            capturedText = true; // ← speech was actually received
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', {
                                bubbles: true
                            }));
                            input.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                            setStatus('Listening...', 'listening');
                        }
                    };

                    r.onerror = () => {
                        clearTimeout_();
                        listening = false;
                        if (manualStop) {
                            manualStop = false;
                            return;
                        }
                        setMicState(false);
                        setStatus("Didn't catch that. Try again.", 'error');
                        setTimeout(() => setStatus('', null), 2500);
                    };

                    r.onend = () => {
                        clearTimeout_();
                        if (manualStop) {
                            manualStop = false;
                            listening = false;
                            setMicState(false);
                            return;
                        }
                        const hadSpeech = sawSpeech || capturedText;
                        listening = false;
                        setMicState(false);
                        if (hadSpeech) {
                            setStatus('Voice captured.', 'success');
                            setTimeout(() => setStatus('', null), 2200);
                        } else {
                            setStatus("Didn't catch that. Try again.", 'error');
                            setTimeout(() => setStatus('', null), 2500);
                        }
                    };

                    return r;
                };

                // Click: toggle on / off
                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        stopNow();
                        return;
                    }
                    recognition = createRecognition();
                    try {
                        recognition.start();
                    } catch (e) {
                        setStatus('Unable to start voice input.', 'error');
                        setTimeout(() => setStatus('', null), 2500);
                        setMicState(false);
                        listening = false;
                        return;
                    }
                    listening = true;
                    setMicState(true);
                    setStatus('Listening...', 'listening');
                });

                // pointerdown: immediate stop while active
                micBtn.addEventListener('pointerdown', (ev) => {
                    if (listening && recognition) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        manualStop = true;
                        try {
                            recognition.stop();
                        } catch (e) { }
                    }
                }, {
                    passive: false
                });
            });
        })();

        const descInput = document.getElementById('serviceDescInput');
        const charCount = document.getElementById('serviceDescCount');
        const maxChars = 255;

        if (descInput && charCount) {
            function updateCharCount() {
                if (descInput.value.length > maxChars) {
                    descInput.value = descInput.value.slice(0, maxChars);
                }

                const currentLength = descInput.value.length;
                charCount.textContent = `${currentLength} / ${maxChars}`;

                charCount.classList.toggle('near-limit', currentLength >= maxChars * 0.8 && currentLength <
                    maxChars);
                charCount.classList.toggle('at-limit', currentLength >= maxChars);
            }

            updateCharCount();
            descInput.addEventListener('input', updateCharCount);
            descInput.addEventListener('change', updateCharCount);
            descInput.addEventListener('paste', () => requestAnimationFrame(updateCharCount));
        }
    });

    window.addEventListener('resize', () => {
        applyServiceTypeView(getPreferredServiceTypeView(), false);
    });

    /* ─────────────────────────────────────────────────────────────
       Service Types AJAX + viewport-centered modals
       Keeps add/edit/delete from reloading the page.
    ───────────────────────────────────────────────────────────── */
    (() => {
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

        const serviceUpdateUrl = (id) => routes.update.replace('__SERVICE_ID__', encodeURIComponent(id));
        const serviceDestroyUrl = (id) => routes.destroy.replace('__SERVICE_ID__', encodeURIComponent(id));

        function sortServices() {
            serviceTypeServices.sort((a, b) => String(a.name).localeCompare(String(b.name)));
        }

        function servicePlural(count) {
            return count === 1 ? 'Item' : 'Items';
        }

        function updateServiceCounts() {
            const count = serviceTypeServices.length;
            document.querySelectorAll('[data-service-count]').forEach((node) => {
                node.textContent = count;
            });

            const entryBadge = document.getElementById('serviceEntryCountBadge');
            if (entryBadge) {
                entryBadge.textContent = `${count} ${servicePlural(count)}`;
            }
        }

        function visibilityBadge(service) {
            return service.is_active_for_booking ?
                `<span class="service-visibility-badge is-visible"><i class="fa-solid fa-thumbtack"></i> Visible</span>` :
                `<span class="service-visibility-badge is-hidden"><i class="fa-solid fa-eye-slash"></i> Hidden</span>`;
        }

        function defaultBadge(service) {
            return service.is_default ?
                `<span class="service-badge service-badge-bookable">Default</span>` :
                '';
        }

        function actionButtons(service) {
            const deleteButton = service.is_default ? '' : `
            <button type="button"
                class="action-btn btn-delete-service"
                title="Delete service"
                data-service-action="delete"
                data-service-id="${service.id}">
                <i class="fa-solid fa-trash"></i>
            </button>`;

            return `
            <button type="button"
                class="action-btn btn-edit"
                title="Manage service"
                data-service-action="edit"
                data-service-id="${service.id}">
                <i class="fa-solid fa-pen"></i>
            </button>
            ${deleteButton}`;
        }

        function emptyStateHtml() {
            return `
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                <p class="service-empty-title">No services found</p>
                <p class="service-empty-subtitle">
                    Your clinic doesn't have any service types yet. Use the form to add one.
                </p>
            </div>`;
        }

        function renderServiceTable() {
            const tbody = document.getElementById('serviceTypeTableBody');
            if (!tbody) return;

            if (!serviceTypeServices.length) {
                tbody.innerHTML = `<tr><td colspan="5">${emptyStateHtml()}</td></tr>`;
                return;
            }

            tbody.innerHTML = serviceTypeServices.map((service) => `
            <tr data-service-id="${service.id}">
                <td><span class="service-badge">#${service.id}</span></td>
                <td>
                    <div class="service-name-cell">
                        <div class="service-name-icon">
                            <i class="fa-solid fa-tooth"></i>
                        </div>
                        <span class="service-name-text">${escapeHtml(service.name)}</span>
                    </div>
                </td>
                <td class="service-desc-cell">${service.description ? escapeHtml(service.description) : '—'}</td>
                <td class="service-center-cell">
                    <div class="service-visibility-actions">
                        ${visibilityBadge(service)}
                        ${defaultBadge(service)}
                    </div>
                </td>
                <td class="service-center-cell">
                    <div class="service-inline-actions">
                        ${actionButtons(service)}
                    </div>
                </td>
            </tr>
        `).join('');
        }

        function renderServiceGrid() {
            const gridView = document.getElementById('serviceTypeGridView');
            if (!gridView) return;

            if (!serviceTypeServices.length) {
                gridView.innerHTML = emptyStateHtml();
                return;
            }

            gridView.innerHTML = `
            <div class="service-types-grid" id="serviceTypeGridContainer">
                ${serviceTypeServices.map((service) => `
                        <div class="service-type-card" data-service-id="${service.id}">
                            <div class="service-type-card-top">
                                <span class="service-badge service-type-card-id">#${service.id}</span>
                                ${defaultBadge(service)}
                            </div>

                            <div class="service-type-card-name-wrap">
                                <div class="service-type-card-icon">
                                    <i class="fa-solid fa-tooth"></i>
                                </div>
                                <div class="service-type-card-name">${escapeHtml(service.name)}</div>
                            </div>

                            <div class="service-type-card-desc-wrap">
                                <div class="service-type-card-label">Description</div>
                                <div class="service-type-card-desc">
                                    ${service.description ? escapeHtml(service.description) : '—'}
                                </div>
                            </div>

                            <div class="service-type-card-footer">
                                <div class="service-card-actions">
                                    ${visibilityBadge(service)}
                                </div>

                                <div class="service-type-card-actions">
                                    ${actionButtons(service)}
                                </div>
                            </div>
                        </div>
                    `).join('')}
            </div>`;
        }

        function renderServices() {
            sortServices();
            updateServiceCounts();
            renderServiceTable();
            renderServiceGrid();

            if (typeof applyServiceTypeView === 'function') {
                applyServiceTypeView(getPreferredServiceTypeView(), false);
            }
        }

        function showServiceToast(type, title, message) {
            if (typeof window.showToast === 'function') {
                window.showToast({
                    type,
                    title,
                    message
                });
                return;
            }

            console[type === 'error' ? 'error' : 'log'](`${title}: ${message}`);
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
            const existingIndex = serviceTypeServices.findIndex((item) => Number(item.id) === Number(normalized
                .id));

            if (existingIndex >= 0) {
                serviceTypeServices[existingIndex] = normalized;
            } else {
                serviceTypeServices.push(normalized);
            }

            renderServices();
        }

        function removeService(id) {
            serviceTypeServices = serviceTypeServices.filter((service) => Number(service.id) !== Number(id));
            renderServices();
        }

        function closeServiceModal(id) {
            if (typeof window.closeModal === 'function') {
                window.closeModal(id);
                return;
            }

            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-lock');
        }

        function openServiceModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.remove('closing');
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-lock');

            if (typeof window.openModal === 'function') {
                window.openModal(id);
            }
        }

        function relocateServiceModals() {
            ['deleteServiceModal', 'manageServiceModal'].forEach((id) => {
                const modal = document.getElementById(id);

                if (modal && modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
            });
        }

        window.closeModalOnBackdrop = window.closeModalOnBackdrop || function (event, modalId) {
            if (event.target?.id === modalId) {
                closeServiceModal(modalId);
            }
        };

        window.closeDeleteModal = function () {
            closeServiceModal('deleteServiceModal');
        };

        window.openManageServiceById = function (id) {
            const service = serviceTypeServices.find((item) => Number(item.id) === Number(id));

            if (!service) {
                showServiceToast('error', 'Service not found', 'Unable to find the selected service.');
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

        window.openDeleteServiceById = function (id) {
            const service = serviceTypeServices.find((item) => Number(item.id) === Number(id));

            if (!service) {
                showServiceToast('error', 'Service not found', 'Unable to find the selected service.');
                return;
            }

            window.openDeleteModal(serviceDestroyUrl(service.id), service.name, service.id);
        };

        window.openDeleteModal = function (actionUrl, serviceName, serviceId = null) {
            relocateServiceModals();

            const form = document.getElementById('deleteServiceForm');
            const name = document.getElementById('deleteServiceName');

            if (!form || !name) return;

            name.textContent = serviceName || '';
            form.action = actionUrl;
            form.dataset.serviceId = serviceId || String(actionUrl).split('/').filter(Boolean).pop() || '';

            openServiceModal('deleteServiceModal');
        };

        window.openManageServiceModal = function (actionUrl, serviceName, serviceDescription, isActiveForBooking,
            isDefault, serviceId = null) {
            relocateServiceModals();

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
            form.dataset.serviceId = serviceId || String(actionUrl).split('/').filter(Boolean).pop() || '';

            nameInput.value = serviceName ?? '';
            descInput.value = serviceDescription ?? '';
            bookingToggle.checked = Boolean(isActiveForBooking);

            defaultNote.classList.toggle('admin-hidden', !isDefault);
            defaultNote.style.display = isDefault ? 'block' : 'none';

            openServiceModal('manageServiceModal');

            setTimeout(() => {
                nameInput.focus();
            }, 180);
        };

        function bindAjaxForms() {
            const addForm = document.getElementById('addServiceForm');
            const manageForm = document.getElementById('manageServiceForm');
            const deleteForm = document.getElementById('deleteServiceForm');

            addForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();

                const nameInput = document.getElementById('serviceNameInput');
                const submitButton = addForm.querySelector('[type="submit"]');
                const value = nameInput?.value.trim() || '';

                if (!value) {
                    if (typeof setAddServiceNameError === 'function') {
                        setAddServiceNameError('Please provide a service name.');
                    }

                    nameInput?.focus();
                    return;
                }

                try {
                    const data = await submitJson(addForm, submitButton, 'Saving...');
                    addOrUpdateService(data.service);

                    addForm.reset();
                    document.getElementById('serviceDescCount') && (document.getElementById(
                        'serviceDescCount').textContent = '0 / 255');
                    document.getElementById('serviceNameClearBtn')?.classList.add('hidden');
                    document.getElementById('serviceDescClearBtn')?.classList.add('hidden');

                    if (typeof setAddServiceNameError === 'function') {
                        setAddServiceNameError('');
                    }

                    document.getElementById('serviceNameInput')?.classList.remove('is-valid',
                        'is-invalid');
                    document.getElementById('serviceDescInput')?.classList.remove('is-valid',
                        'is-invalid');

                    showServiceToast(
                        'success',
                        'Service added',
                        data.service?.is_active_for_booking
                            ? 'Patients can now select it when booking.'
                            : 'Saved in Service Types and hidden from booking.'
                    );
                } catch (error) {
                    const message = firstValidationMessage(error, 'Unable to add service type.');

                    if (error?.errors?.name && typeof setAddServiceNameError === 'function') {
                        setAddServiceNameError(message);
                        nameInput?.focus();
                    } else {
                        showServiceToast('error', 'Add failed', message);
                    }
                }
            }, true);

            manageForm?.addEventListener('submit', async (event) => {
                event.preventDefault();

                const submitButton = manageForm.querySelector('[type="submit"]');

                try {
                    const data = await submitJson(manageForm, submitButton, 'Saving...');
                    addOrUpdateService(data.service);
                    closeServiceModal('manageServiceModal');
                    showServiceToast(
                        'success',
                        'Changes saved',
                        'Service details and booking visibility updated.'
                    );
                } catch (error) {
                    showServiceToast('error', 'Update failed', firstValidationMessage(error,
                        'Unable to update service type.'));
                }
            });

            deleteForm?.addEventListener('submit', async (event) => {
                event.preventDefault();

                const submitButton = deleteForm.querySelector('[type="submit"]');

                try {
                    const data = await submitJson(deleteForm, submitButton, 'Deleting...');
                    removeService(data.deleted_id || deleteForm.dataset.serviceId);
                    closeServiceModal('deleteServiceModal');
                    showServiceToast(
                        'success',
                        'Service deleted',
                        'Removed from the service list.'
                    );
                } catch (error) {
                    showServiceToast('error', 'Delete failed', firstValidationMessage(error,
                        'Unable to delete service type.'));
                }
            });
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
        });

        document.addEventListener('DOMContentLoaded', () => {
            relocateServiceModals();
            renderServices();
            bindAjaxForms();
        });
    })();
</script>
@endsection