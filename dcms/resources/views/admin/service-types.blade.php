@extends('layouts.admin')

@section('title', 'Service Types | Admin Dashboard')

@section('content')
    <main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-screen">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Service Types</h1>
                </div>

                <div class="flex items-center gap-2 whitespace-nowrap">
                    <span style="
                        background: rgba(255,255,255,.12);
                        border: 1px solid rgba(255,255,255,.18);
                        color: #fff;
                        padding: .6rem 1rem;
                        border-radius: 10px;
                        font-size: .75rem;
                        font-weight: 700;
                        line-height: 1;
                    ">
                        Active Services: {{ $services->count() }}
                    </span>

                    <div class="service-type-view-toggle" id="serviceTypeViewToggle" style="margin-left:4px;">
                        <button type="button" class="service-type-view-btn active" id="serviceTypeListBtn" title="List view" aria-label="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="service-type-view-btn" id="serviceTypeGridBtn" title="Grid view" aria-label="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-lift">
            <div class="main-grid">

                <div style="display:flex;flex-direction:column;gap:1.25rem;">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-plus"></i></div>
                                <span class="card-title">Add New Service</span>
                            </div>
                        </div>

                        <div style="padding:1.25rem;">
                            <form id="addServiceForm" method="POST" action="{{ route('admin.service-types.store') }}" novalidate>
                                @csrf

                                <div class="st-form-group">
                                    <label class="st-label">Service Name</label>
                                    <div class="st-voice-row">
                                        <div class="st-input-wrap">
                                            <i class="fa-solid fa-tag st-input-icon"></i>
                                            <input type="text" id="serviceNameInput" name="name"
                                                placeholder="e.g. Tooth Extraction" autocomplete="off"
                                                value="{{ old('name') }}" class="st-input with-icon no-voice">
                                            {{-- Clear button lives INSIDE the input-wrap, right of text --}}
                                            <button type="button" id="serviceNameClearBtn" class="st-voice-clear-btn hidden">Clear</button>
                                        </div>
                                        {{-- Mic toggle is a sibling of st-input-wrap, not inside it --}}
                                        <div class="service-voice-toggle" id="serviceNameVoiceToggle"></div>
                                    </div>

                                    <div id="nameClientError" class="st-field-error" style="display: none;">
                                        <i class="fa-solid fa-circle-exclamation"></i> Please provide a service name.
                                    </div>

                                    @error('name')
                                        <div class="st-field-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="st-form-group">
                                    <label class="st-label">Description (Optional)</label>

                                    <div class="st-copy-bullet-wrap">
                                        <div class="st-copy-bullet-box" title="Copy this bullet and paste into the description">
                                            <span class="st-copy-bullet-symbol">•</span>
                                            <span class="st-copy-bullet-label">Copy this bullet</span>
                                        </div>
                                    </div>

                                    <div class="st-voice-row is-textarea">
                                        <div class="st-input-wrap st-textarea-wrap">
                                            <textarea id="serviceDescInput"
                                                name="description"
                                                placeholder="Brief details about the service..."
                                                class="st-input st-textarea no-voice"
                                                maxlength="255">{{ old('description') }}</textarea>

                                            <div id="serviceDescCount" class="st-char-count">0 / 255</div>
                                            <button type="button" id="serviceDescClearBtn" class="st-voice-clear-btn hidden">Clear</button>
                                        </div>
                                        <div class="service-voice-toggle" id="serviceDescVoiceToggle"></div>
                                    </div>

                                    @error('description')
                                        <div class="st-field-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn-submit">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Save Service
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:1.25rem;">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-list-check"></i></div>
                                <span class="card-title">Existing Services</span>
                            </div>
                            <span
                                style="font-size:.65rem;font-weight:700;background:#fef2f2;color:var(--crimson);padding:.25rem .6rem;border-radius:20px;border:1px solid #fce8e8;">
                                {{ $services->count() }} {{ Str::plural('Item', $services->count()) }}
                            </span>
                        </div>

                        <div class="service-type-view" id="serviceTypeListView">
                            <div style="overflow-x:auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th style="width:70px;">ID</th>
                                            <th style="width:250px;">Service Name</th>
                                            <th>Description</th>
                                            <th style="width:220px; text-align:center;">Booking Visibility</th>
                                            <th style="width:100px; text-align:center;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($services as $service)
                                            <tr>
                                                <td><span class="service-badge">#{{ $service->id }}</span></td>
                                                <td>
                                                    <div style="display:flex; align-items:center; gap:.6em; min-height:40px;">
                                                        <div
                                                            style="width:26px; height:26px; background:#fef2f2; color:var(--crimson); border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:11px;">
                                                            <i class="fa-solid fa-tooth"></i>
                                                        </div>
                                                        <span style="font-size:.78rem;font-weight:700;color:#1a202c;">
                                                            {{ $service->name }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td style="font-size:.72rem; line-height:1.5;">
                                                    {{ $service->description ?: '—' }}
                                                </td>
                                                <td style="text-align:center;">
                                                    <div style="display:inline-flex; align-items:center; justify-content:center; gap:.45rem; flex-wrap:wrap;">
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
                                                            <span class="service-badge" style="background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;">
                                                                Default
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td style="text-align:center;">
                                                    <div style="display:flex; align-items:center; justify-content:center; gap:.4rem;">
                                                        <button
                                                            type="button"
                                                            class="btn-manage-sm"
                                                            title="Manage"
                                                            onclick="openManageServiceModal(
                                                                '{{ route('admin.service-types.update', $service->id) }}',
                                                                @js($service->name),
                                                                @js($service->description),
                                                                {{ $service->is_active_for_booking ? 'true' : 'false' }},
                                                                {{ $service->is_default ? 'true' : 'false' }}
                                                            )"
                                                        >
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>

                                                        @if (!$service->is_default)
                                                            <button
                                                                type="button"
                                                                class="btn-delete-sm"
                                                                title="Delete"
                                                                onclick="openDeleteModal('{{ route('admin.service-types.destroy', $service->id) }}', '{{ addslashes($service->name) }}')"
                                                            >
                                                                <i class="fa-solid fa-trash-can"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5">
                                                    <div class="empty-state">
                                                        <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                                                        <p style="font-size:.82rem;font-weight:700;color:#6b7280;margin-bottom:.25rem;">
                                                            No services found
                                                        </p>
                                                        <p style="font-size:.72rem;color:#b0b7c3;">
                                                            Your clinic doesn't have any service types yet. Use the form to add one.
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
                            @if($services->count())
                                <div class="service-types-grid">
                                    @foreach($services as $service)
                                        <div class="service-type-card">
                                            <div class="service-type-card-top">
                                                <div class="service-type-card-id">#{{ $service->id }}</div>

                                                @if ($service->is_default)
                                                    <span class="service-badge" style="background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;">
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
                                                <div style="display:flex; align-items:center; gap:.45rem; flex-wrap:wrap;">
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
                                                    <button
                                                        type="button"
                                                        class="btn-manage-sm"
                                                        title="Manage"
                                                        onclick="openManageServiceModal(
                                                            '{{ route('admin.service-types.update', $service->id) }}',
                                                            @js($service->name),
                                                            @js($service->description),
                                                            {{ $service->is_active_for_booking ? 'true' : 'false' }},
                                                            {{ $service->is_default ? 'true' : 'false' }}
                                                        )"
                                                    >
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>

                                                    @if (!$service->is_default)
                                                        <button
                                                            type="button"
                                                            class="btn-delete-sm"
                                                            title="Delete"
                                                            onclick="openDeleteModal('{{ route('admin.service-types.destroy', $service->id) }}', '{{ addslashes($service->name) }}')"
                                                        >
                                                            <i class="fa-solid fa-trash-can"></i>
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
                                    <p style="font-size:.82rem;font-weight:700;color:#6b7280;margin-bottom:.25rem;">
                                        No services found
                                    </p>
                                    <p style="font-size:.72rem;color:#b0b7c3;">
                                        Your clinic doesn't have any service types yet. Use the form to add one.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <dialog id="deleteServiceModal">
            <div class="del-modal-icon">
                <i class="fa-solid fa-trash-can"></i>
            </div>
            <h2 class="del-modal-title">Delete Service Type</h2>
            <div class="del-modal-body">
                Are you sure you want to delete <span class="del-modal-name" id="deleteServiceName"></span>?
                <span class="del-modal-warning">This action cannot be undone.</span>
            </div>
            <div class="del-modal-actions">
                <button type="button" class="del-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <form id="deleteServiceForm" method="POST" action="" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="del-btn-confirm">Yes, delete it</button>
                </form>
            </div>
        </dialog>

        <div class="modal-overlay st-manage-modal" id="manageServiceModal" onclick="closeModalOutside(event, 'manageServiceModal')">
            <div class="st-modal-box">
                <form id="manageServiceForm" method="POST" class="st-manage-form">
                    @csrf
                    @method('PUT')

                    <div class="st-modal-header">
                        <div class="st-modal-header-left">
                            <div class="st-modal-header-icon">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </div>
                            <div>
                                <h3 class="st-modal-title">Manage Service Type</h3>
                                <p class="st-modal-subtitle">Update service details and booking visibility</p>
                            </div>
                        </div>

                        <button type="button" class="st-modal-close" onclick="closeModal('manageServiceModal')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="st-modal-body">
                        <div class="st-panel">
                            <label class="st-modal-label">Service Name <span class="text-red-500">*</span></label>
                            <div class="st-modal-voice-row">
                                <div class="st-modal-field-wrap">
                                    <span class="st-modal-field-icon"><i class="fa-solid fa-tag"></i></span>
                                    <input type="text" id="manageServiceName" name="name" class="st-modal-input no-voice" maxlength="255" required>
                                </div>
                                <div class="service-voice-toggle" id="manageServiceNameVoiceToggle"></div>
                            </div>
                        </div>

                        <div class="st-panel">
                            <label class="st-modal-label">Description</label>

                            <div class="st-copy-bullet-wrap">
                                <div class="st-copy-bullet-box" title="Copy this bullet and paste into the description">
                                    <span class="st-copy-bullet-symbol">•</span>
                                    <span class="st-copy-bullet-label">Copy this bullet</span>
                                </div>
                            </div>

                            <div class="st-modal-voice-row st-modal-voice-row--textarea">
                                <div class="st-modal-textarea-wrap">
                                    <textarea id="manageServiceDescription" name="description" class="st-modal-textarea no-voice" maxlength="255"
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
                                        <p class="st-active-desc">Turn this off if you want the service hidden from booking but still kept in Service Types.</p>
                                    </div>
                                </div>

                                <label class="st-switch">
                                    <input type="checkbox" id="manageServiceBookingToggle" name="is_active_for_booking" value="1">
                                    <span class="st-switch-slider"></span>
                                </label>
                            </div>

                            <div id="manageDefaultNote" class="st-default-note" style="display:none;">
                                This is a default service type. It can be edited and hidden from booking, but it cannot be deleted.
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

@section('scripts')
    <script>
        document.getElementById('addServiceForm').addEventListener('submit', function(e) {
            const nameInput = document.getElementById('serviceNameInput');
            const errorDiv = document.getElementById('nameClientError');

            if (nameInput.value.trim() === '') {
                e.preventDefault();
                errorDiv.style.display = 'flex';
                nameInput.classList.add('is-invalid');
                nameInput.focus();
            }
        });

        document.getElementById('serviceNameInput').addEventListener('input', function() {
            document.getElementById('nameClientError').style.display = 'none';
            this.classList.remove('is-invalid');
        });

        function openDeleteModal(actionUrl, serviceName) {
            document.getElementById('deleteServiceName').textContent = serviceName;
            document.getElementById('deleteServiceForm').action = actionUrl;
            document.getElementById('deleteServiceModal').showModal();
        }

        function closeDeleteModal() {
            document.getElementById('deleteServiceModal').close();
        }

        function openManageServiceModal(actionUrl, serviceName, serviceDescription, isActiveForBooking, isDefault) {
            const modal = document.getElementById('manageServiceModal');
            const form = document.getElementById('manageServiceForm');
            const nameInput = document.getElementById('manageServiceName');
            const descInput = document.getElementById('manageServiceDescription');
            const bookingToggle = document.getElementById('manageServiceBookingToggle');
            const defaultNote = document.getElementById('manageDefaultNote');

            if (!modal || !form || !nameInput || !descInput || !bookingToggle || !defaultNote) {
                console.error('Manage modal elements not found.');
                return;
            }

            form.action = actionUrl;
            nameInput.value = serviceName ?? '';
            descInput.value = serviceDescription ?? '';
            bookingToggle.checked = Boolean(isActiveForBooking);
            defaultNote.style.display = isDefault ? 'block' : 'none';

            modal.style.display = 'flex';

            requestAnimationFrame(() => {
                modal.classList.add('open');
            });

            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.remove('open');

            setTimeout(() => {
                modal.style.display = 'none';

                const stillOpen = document.querySelector('.modal-overlay.open');
                if (!stillOpen) {
                    document.body.style.overflow = '';
                }
            }, 200);
        }

        function closeModalOutside(event, id) {
            if (event.target && event.target.id === id) {
                closeModal(id);
            }
        }

        function getPreferredServiceTypeView() {
            if (window.innerWidth <= 767) return 'grid';
            return localStorage.getItem('serviceTypeView') || 'list';
        }

        function applyServiceTypeView(view, save = true) {
            const listView = document.getElementById('serviceTypeListView');
            const gridView = document.getElementById('serviceTypeGridView');
            const listBtn = document.getElementById('serviceTypeListBtn');
            const gridBtn = document.getElementById('serviceTypeGridBtn');

            if (!listView || !gridView) return;

            const finalView = window.innerWidth <= 767 ? 'grid' : view;

            if (finalView === 'grid') {
                listView.hidden = true;
                gridView.hidden = false;
            } else {
                listView.hidden = false;
                gridView.hidden = true;
            }

            if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
            if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

            if (save && window.innerWidth > 767) {
                localStorage.setItem('serviceTypeView', finalView);
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
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
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

                const voiceInputs = [
                    { inputId: 'serviceNameInput',        toggleWrapperId: 'serviceNameVoiceToggle',       micId: 'serviceNameMicBtn'       },
                    { inputId: 'serviceDescInput',        toggleWrapperId: 'serviceDescVoiceToggle',        micId: 'serviceDescMicBtn'       },
                    { inputId: 'manageServiceName',       toggleWrapperId: 'manageServiceNameVoiceToggle',  micId: 'manageServiceNameMicBtn' },
                    { inputId: 'manageServiceDescription',toggleWrapperId: 'manageServiceDescVoiceToggle',  micId: 'manageServiceDescMicBtn' }
                ];

                voiceInputs.forEach(config => {
                    const input        = document.getElementById(config.inputId);
                    const toggleWrapper = document.getElementById(config.toggleWrapperId);
                    if (!input || !toggleWrapper) return;

                    // Build mic button
                    const micBtn = document.createElement('button');
                    micBtn.type      = 'button';
                    micBtn.id        = config.micId;
                    micBtn.className = 'voice-search-mic external';
                    micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
                    micBtn.title     = 'Toggle voice input';
                    toggleWrapper.appendChild(micBtn);

                    // Build status chip
                    const status = document.createElement('span');
                    status.className = 'patient-voice-status hidden';
                    status.setAttribute('aria-hidden', 'true');
                    status.setAttribute('aria-live', 'polite');
                    toggleWrapper.appendChild(status);

                    // State
                    let recognition  = null;
                    let listening    = false;
                    let manualStop   = false;
                    let capturedText = false; // ← tracks whether any speech was recorded

                    const setStatus = (text, state) => {
                        status.textContent = text || '';
                        status.className   = state ? `patient-voice-status is-${state}` : 'patient-voice-status';
                        if (!text) status.classList.add('hidden');
                        else       status.classList.remove('hidden');
                    };

                    const setMicState = (active) => {
                        micBtn.classList.toggle('mic-active', !!active);
                        micBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
                        micBtn.innerHTML = active
                            ? '<i class="fa-solid fa-stop"></i>'
                            : '<i class="fa-solid fa-microphone"></i>';
                    };

                    // ── FIX: manual stop now checks whether speech was captured ──
                    const stopNow = () => {
                        manualStop = true;
                        listening  = false;
                        setMicState(false);

                        if (capturedText) {
                            setStatus('Voice captured.', 'success');
                            setTimeout(() => setStatus('', null), 1200);
                        } else {
                            setStatus("Didn't catch that. Try again.", 'error');
                            setTimeout(() => setStatus('', null), 2500);
                        }

                        if (recognition) {
                            try { recognition.abort(); } catch (e) {
                                try { recognition.stop(); } catch (_) {}
                            }
                        }
                    };

                    const createRecognition = () => {
                        capturedText = false; // reset per session

                        const r           = new SpeechRecognition();
                        r.lang            = 'en-US';
                        r.continuous      = false;
                        r.interimResults  = true;
                        r.maxAlternatives = 1;

                        let sawSpeech  = false;
                        let timeoutId  = null;
                        const LISTEN_TIMEOUT = 6000;

                        const clearTimeout_ = () => {
                            if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                        };

                        r.onstart = () => {
                            timeoutId = setTimeout(() => {
                                if (listening && !sawSpeech) { try { r.stop(); } catch (e) {} }
                            }, LISTEN_TIMEOUT);
                        };

                        r.onspeechend = () => { clearTimeout_(); try { r.stop(); } catch (e) {} };

                        r.onresult = (event) => {
                            let transcript = '';
                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                const result = event.results[i];
                                const chunk  = (result?.[0]?.transcript ?? '').trim();
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
                                capturedText  = true; // ← speech was actually received
                                input.value   = transcript;
                                input.dispatchEvent(new Event('input',  { bubbles: true }));
                                input.dispatchEvent(new Event('change', { bubbles: true }));
                                setStatus('Listening...', 'listening');
                            }
                        };

                        r.onerror = () => {
                            clearTimeout_();
                            listening = false;
                            if (manualStop) { manualStop = false; return; }
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            setTimeout(() => setStatus('', null), 2500);
                        };

                        r.onend = () => {
                            clearTimeout_();
                            if (manualStop) { manualStop = false; listening = false; setMicState(false); return; }
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
                        if (listening && recognition) { stopNow(); return; }
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
                            try { recognition.stop(); } catch (e) {}
                        }
                    }, { passive: false });
                });
            })();

            // Char counter for description textarea
            const descInput = document.getElementById('serviceDescInput');
            const charCount = document.getElementById('serviceDescCount');
            const maxChars  = 255;

            if (descInput && charCount) {
                function updateCharCount() {
                    const currentLength = descInput.value.length;
                    charCount.textContent = `${currentLength} / ${maxChars}`;

                    if (currentLength >= maxChars) {
                        charCount.classList.remove('near-limit');
                        charCount.classList.add('at-limit');
                    } else if (currentLength >= maxChars * 0.8) {
                        charCount.classList.remove('at-limit');
                        charCount.classList.add('near-limit');
                    } else {
                        charCount.classList.remove('at-limit', 'near-limit');
                    }
                }

                updateCharCount();
                descInput.addEventListener('input', updateCharCount);
            }
        });

        window.addEventListener('resize', () => {
            applyServiceTypeView(getPreferredServiceTypeView(), false);
        });
    </script>
@endsection