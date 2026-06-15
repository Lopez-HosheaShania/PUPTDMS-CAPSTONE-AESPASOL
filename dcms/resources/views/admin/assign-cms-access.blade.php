@extends('layouts.admin')

@section('title', 'Assign CMS Access | PUP Taguig Dental Clinic')

@section('content')
<main id="mainContent" class="admin-page-shell cms-page page-enter">
    <div class="cms-shell">
        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Assign CMS Access</h1>
                </div>
            </div>
        </div>

        <div class="relative z-10 mt-4 px-4 sm:px-6 lg:px-7 pb-8">
            
        <div class="cms-layout">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <div class="card-header-icon">
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <div>
                            <h2 class="card-title">CMS Access Form</h2>
                        </div>
                    </div>
                    <span class="entry-badge">Access Setup</span>
                </div>

                <form id="assignCmsAccessForm" method="POST" action="{{ route('admin.assign-cms-access.store') }}"
                    novalidate>
                    @csrf

                    <div class="card-body">
                        <div class="section-block">
                            <div class="section-head-left">
                                <div class="section-icon">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                                <div>
                                    <h3 class="section-title">User Selection</h3>
                                </div>
                            </div>

                            <div class="cms-grid">
                                <div class="field-group full search-combo">
                                    <label for="user_search" class="field-label">
                                        Select User<span class="required-mark">*</span>
                                    </label>

                                    <div class="user-search-row voice-search-row">
                                        <div class="search-input-wrap" data-search-wrapper>
                                            <i class="fa-solid fa-magnifying-glass cms-search-leading-icon"></i>

                                            <input type="text" id="user_search" class="access-input cms-search-input"
                                                placeholder="Search faculty by name or email" autocomplete="off"
                                                data-search-input>

                                            <button type="button" id="userSearchClearBtn" class="cms-search-clear-btn"
                                                data-search-clear aria-label="Clear search">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>

                                            <button type="button" id="toggleUserDropdown" class="dropdown-toggle-btn"
                                                aria-label="Show user list">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </button>
                                        </div>

                                        <div class="voice-input-toggle">
                                            <button type="button" id="cmsSearchMicBtn" class="voice-search-mic external"
                                                data-voice-trigger data-voice-target="#user_search"
                                                data-voice-status="#cmsSearchVoiceStatus"
                                                aria-label="Toggle voice input" aria-pressed="false">
                                                <i class="fa-solid fa-microphone"></i>
                                            </button>

                                            <span id="cmsSearchVoiceStatus" class="voice-status hidden"
                                                aria-live="polite"></span>
                                        </div>
                                    </div>

                                    <div id="searchResults" class="search-results"></div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="external_admin_id" id="external_admin_id">

                        <div class="section-block">
                            <div class="section-head-left">
                                <div class="section-icon">
                                    <i class="fa-solid fa-id-card"></i>
                                </div>
                                <div>
                                    <h3 class="section-title">Synced User Information</h3>
                                </div>
                            </div>

                            <div class="synced-user-layout">
                                <div class="synced-row synced-row-top">
                                    <div class="field-group">
                                        <label for="fname" class="field-label">First Name</label>
                                        <input type="text" name="fname" id="fname" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="lname" class="field-label">Last Name</label>
                                        <input type="text" name="lname" id="lname" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="age" class="field-label">Age</label>
                                        <input type="number" name="age" id="age" class="access-input" readonly>
                                    </div>
                                </div>

                                <div class="synced-row synced-row-mid">
                                    <div class="field-group">
                                        <label for="email" class="field-label">Email</label>
                                        <input type="email" name="email" id="email" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="office" class="field-label">Office</label>
                                        <input type="text" name="office" id="office" class="access-input" readonly>
                                    </div>
                                </div>

                                <div class="synced-row synced-row-full">
                                    <div class="field-group">
                                        <label for="address" class="field-label">Address</label>
                                        <input type="text" name="address" id="address" class="access-input" readonly>
                                    </div>
                                </div>

                                <div class="synced-row synced-row-bottom">
                                    <div class="field-group">
                                        <label for="contact_number" class="field-label">Contact Number</label>
                                        <input type="text" name="contact_number" id="contact_number"
                                            class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="gender" class="field-label">Gender</label>
                                        <input type="text" name="gender" id="gender" class="access-input" readonly>
                                    </div>

                                    <div class="field-group">
                                        <label for="senior_pwd" class="field-label">Senior / PWD</label>
                                        <input type="text" name="senior_pwd" id="senior_pwd" class="access-input"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-block">
                            <div class="section-head-left">
                                <div class="section-icon">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                                <div>
                                    <h3 class="section-title">Access Configuration</h3>
                                </div>
                            </div>

                            <div class="cms-role-status">
                                <div class="field-group">
                                    <label for="cms_role" class="field-label">
                                        CMS Role<span class="required-mark">*</span>
                                    </label>
                                    <select name="cms_role" id="cms_role" class="access-select" required>
                                        <option value="" disabled selected hidden>Select CMS Role</option>
                                        <option value="admin">Admin</option>
                                        <option value="patient">Patient</option>
                                        <option value="dentist">Dentist</option>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label for="cms_status" class="field-label">
                                        CMS Access Status<span class="required-mark">*</span>
                                    </label>
                                    <select name="cms_status" id="cms_status" class="access-select" required>
                                        <option value="" disabled selected hidden>Select Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="access-card-footer">
                        <button type="button" class="btn-reset" id="resetAssignCmsBtn">
                            <i class="fa-solid fa-arrow-left"></i>
                            Reset
                        </button>

                        <button type="submit" class="btn-save">
                            <i class="fa-solid fa-user-plus"></i>
                            Save Access
                        </button>
                    </div>
                </form>
            </div>

            <div class="sidebar-stack">
                <div class="info-card">
                    <div class="preview-inner">
                        <div class="preview-avatar">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>

                        <div class="preview-name" id="preview_name">No user selected</div>
                        <div class="preview-email" id="preview_email">Select a user to preview synced information.
                        </div>

                        <div class="preview-meta">
                            <div class="preview-meta-item">
                                <div class="preview-meta-label">Office</div>
                                <div class="preview-meta-value" id="preview_office">—</div>
                            </div>

                            <div class="preview-meta-item">
                                <div class="preview-meta-label">Contact Number</div>
                                <div class="preview-meta-value" id="preview_contact">—</div>
                            </div>

                            <div class="preview-meta-item">
                                <div class="preview-meta-label">Address</div>
                                <div class="preview-meta-value" id="preview_address">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card quick-notes-card">
                    <div class="section-head quick-notes-head admin-mb-xs">
                        <div class="section-head-left">
                            <div class="section-icon">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div>
                                <h3 class="section-title quick-notes-title">Quick Notes</h3>
                                <div class="section-note">Small guidance for cleaner admin workflow.</div>
                            </div>
                        </div>
                    </div>

                    <div class="tip-list">
                        <div class="tip-item">
                            <i class="fa-solid fa-check"></i>
                            <span>Select from the dropdown first to avoid manually typing inconsistent user
                                details.</span>
                        </div>
                        <div class="tip-item">
                            <i class="fa-solid fa-user-gear"></i>
                            <span>Assign the appropriate CMS role before saving so the account is mapped
                                correctly.</span>
                        </div>
                        <div class="tip-item">
                            <i class="fa-solid fa-shield"></i>
                            <span>Use <strong>Inactive</strong> status when the user record should remain stored but
                                access must be disabled.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const searchInput = document.getElementById('user_search');
        const toggleButton = document.getElementById('toggleUserDropdown');
        const clearSearchButton = document.getElementById('userSearchClearBtn');
        const resultsBox = document.getElementById('searchResults');

        const externalAdminId = document.getElementById('external_admin_id');
        const fname = document.getElementById('fname');
        const lname = document.getElementById('lname');
        const email = document.getElementById('email');
        const office = document.getElementById('office');
        const address = document.getElementById('address');
        const age = document.getElementById('age');
        const gender = document.getElementById('gender');
        const contactNumber = document.getElementById('contact_number');
        const seniorPwd = document.getElementById('senior_pwd');

        const previewName = document.getElementById('preview_name');
        const previewEmail = document.getElementById('preview_email');
        const previewOffice = document.getElementById('preview_office');
        const previewContact = document.getElementById('preview_contact');
        const previewAddress = document.getElementById('preview_address');
        const resetBtn = document.getElementById('resetAssignCmsBtn');

        let fullUserList = [];
        let dropdownOpen = false;
        let fullListLoaded = false;
        let isDropdownMode = false;
        let usersFetchPromise = null;

        @if ($errors -> any())
            window.showToast?.({
                type: 'error',
                title: 'Unable to save CMS access',
                message: @json($errors -> first()),
                duration: 7000,
            });
        @endif

        function toggleUserSearchClear(input) {
            if (!clearSearchButton) return;

            clearSearchButton.classList.toggle('show', (input.value || '').trim().length > 0);
        }

        window.clearUserSearch = function () {
            if (!searchInput) return;

            if (window.clearSearchInput) {
                window.clearSearchInput(searchInput);
            } else {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                searchInput.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                searchInput.focus();
            }

            clearFormFields();
            hideResults();
            toggleUserSearchClear(searchInput);
        };

        function closeCmsCustomDropdowns(except = null) {
            document.querySelectorAll('.cms-custom-select.is-open').forEach(wrapper => {
                if (wrapper === except) return;

                wrapper.classList.remove('is-open');
                wrapper.querySelector('.cms-custom-select-btn')?.setAttribute('aria-expanded', 'false');
            });
        }

        function syncCmsCustomSelects(root = document) {
            const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
            const wrappers = [];

            if (scope.matches && scope.matches('.cms-custom-select')) {
                wrappers.push(scope);
            }

            scope.querySelectorAll?.('.cms-custom-select').forEach(wrapper => {
                if (!wrappers.includes(wrapper)) wrappers.push(wrapper);
            });

            wrappers.forEach(wrapper => {
                const select = wrapper.querySelector('select');
                const valueText = wrapper.querySelector('[data-cms-custom-select-value]');
                const button = wrapper.querySelector('.cms-custom-select-btn');

                if (!select || !valueText || !button) return;

                const selectedOption = select.options[select.selectedIndex];
                const selectedValue = select.value || '';

                valueText.textContent = selectedOption?.textContent?.trim() || 'Select option';

                wrapper.classList.toggle('is-disabled', select.disabled);
                button.disabled = select.disabled;

                wrapper.classList.remove(
                    'role-admin',
                    'role-dentist',
                    'role-patient',
                    'status-active',
                    'status-inactive',
                    'has-value'
                );

                if (selectedValue) {
                    wrapper.classList.add('has-value');

                    if (select.id === 'cms_role') {
                        wrapper.classList.add(`role-${selectedValue}`);
                    }

                    if (select.id === 'cms_status') {
                        wrapper.classList.add(`status-${selectedValue}`);
                    }
                }

                wrapper.querySelectorAll('.cms-custom-select-option').forEach(option => {
                    const isActive = Number(option.dataset.index) === select.selectedIndex;

                    option.classList.toggle('is-active', isActive);
                    option.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
            });
        }

        function initCmsCustomDropdowns(root = document) {
            const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

            scope.querySelectorAll('#assignCmsAccessForm select.access-select').forEach(select => {
                if (select.dataset.customDropdownReady === 'true') return;

                select.dataset.customDropdownReady = 'true';
                select.classList.add('cms-native-select');

                const wrapper = document.createElement('div');
                wrapper.className = 'cms-custom-select';

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'cms-custom-select-btn';
                button.setAttribute('aria-haspopup', 'listbox');
                button.setAttribute('aria-expanded', 'false');

                const valueSpan = document.createElement('span');
                valueSpan.setAttribute('data-cms-custom-select-value', '');

                const chevron = document.createElement('i');
                chevron.className = 'fa-solid fa-chevron-down';
                chevron.setAttribute('aria-hidden', 'true');

                button.appendChild(valueSpan);
                button.appendChild(chevron);

                const menu = document.createElement('div');
                menu.className = 'cms-custom-select-menu';
                menu.setAttribute('role', 'listbox');

                Array.from(select.options).forEach(option => {
                    if (option.hidden) return;

                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'cms-custom-select-option';
                    item.dataset.value = option.value;
                    item.dataset.index = String(option.index);
                    item.setAttribute('role', 'option');

                    if (select.id === 'cms_role' && option.value) {
                        item.classList.add(`role-${option.value}`);
                    }

                    if (select.id === 'cms_status' && option.value) {
                        item.classList.add(`status-${option.value}`);
                    }

                    const labelSpan = document.createElement('span');
                    labelSpan.textContent = option.textContent.trim();

                    const checkIcon = document.createElement('i');
                    checkIcon.className = 'fa-solid fa-check cms-custom-select-check';
                    checkIcon.setAttribute('aria-hidden', 'true');

                    item.appendChild(labelSpan);
                    item.appendChild(checkIcon);

                    item.addEventListener('click', event => {
                        event.preventDefault();
                        event.stopPropagation();

                        if (select.disabled) return;

                        select.selectedIndex = option.index;

                        select.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        select.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));

                        wrapper.classList.remove('is-open');
                        button.setAttribute('aria-expanded', 'false');

                        syncCmsCustomSelects(wrapper);
                    });

                    menu.appendChild(item);
                });

                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(select);
                wrapper.appendChild(button);
                wrapper.appendChild(menu);

                button.addEventListener('click', event => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (select.disabled) return;

                    const willOpen = !wrapper.classList.contains('is-open');

                    closeCmsCustomDropdowns(wrapper);

                    wrapper.classList.toggle('is-open', willOpen);
                    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });

                select.addEventListener('change', () => syncCmsCustomSelects(wrapper));

                syncCmsCustomSelects(wrapper);
            });
        }

        document.addEventListener('click', event => {
            if (event.target.closest('.cms-custom-select')) return;
            closeCmsCustomDropdowns();
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape') return;
            closeCmsCustomDropdowns();
        });

        initCmsCustomDropdowns(document);

        function hideResults() {
            resultsBox.classList.remove('is-open');
            resultsBox.innerHTML = '';
            dropdownOpen = false;
            isDropdownMode = false;
        }

        function showResults() {
            if (!resultsBox.innerHTML.trim()) {
                hideResults();
                return;
            }

            resultsBox.classList.add('is-open');
            dropdownOpen = true;
        }

        function resetPreview() {
            previewName.textContent = 'No user selected';
            previewEmail.textContent = 'Select a user to preview synced information.';
            previewOffice.textContent = '—';
            previewContact.textContent = '—';
            previewAddress.textContent = '—';
        }

        function clearFormFields() {
            externalAdminId.value = '';
            fname.value = lname.value = email.value = office.value =
                address.value = age.value = gender.value =
                contactNumber.value = seniorPwd.value = '';
            resetPreview();
        }

        function resetAssignCmsForm() {
            searchInput.value = '';
            externalAdminId.value = '';
            fname.value = lname.value = email.value = office.value =
                address.value = age.value = gender.value =
                contactNumber.value = seniorPwd.value = '';
            document.getElementById('cms_role').value = '';
            document.getElementById('cms_status').value = '';
            syncCmsCustomSelects(document);
            setCmsFieldError(searchInput, '');
            setCmsFieldError(document.getElementById('cms_role'), '');
            setCmsFieldError(document.getElementById('cms_status'), '');

            hideResults();
            toggleUserSearchClear(searchInput);
            resetPreview();

            if (window.initSearchClearButtons) {
                window.initSearchClearButtons();
            }

            if (clearSearchButton) {
                clearSearchButton.addEventListener('click', function () {
                    clearFormFields();
                    hideResults();
                    toggleUserSearchClear(searchInput);
                });
            }
        }

        function fillUser(user) {
            externalAdminId.value = user.admin_id ?? '';
            setCmsFieldError(searchInput, '');
            searchInput.value = user.full_name ?? '';
            fname.value = user.fname ?? '';
            lname.value = user.lname ?? '';
            email.value = user.email ?? '';
            office.value = user.office ?? '';
            address.value = user.address ?? '';
            age.value = user.age ?? '';
            gender.value = user.gender ?? '';
            contactNumber.value = user.contact_number ?? '';
            seniorPwd.value = user.senior_pwd ?? '';

            previewName.textContent = user.full_name ?? 'Selected user';
            previewEmail.textContent = user.email ?? 'No email available';
            previewOffice.textContent = user.office ?? '—';
            previewContact.textContent = user.contact_number ?? '—';
            previewAddress.textContent = user.address ?? '—';

            toggleUserSearchClear(searchInput);
            hideResults();
        }

        function renderNoResults(message = 'No results found.') {
            resultsBox.innerHTML = `<div class="search-empty">${message}</div>`;
            showResults();
        }

        function renderResults(users) {
            resultsBox.innerHTML = '';
            users.forEach(user => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'search-item';
                item.innerHTML = `
<div class="search-name">${user.full_name ?? ''}</div>
<div class="search-email">${user.email ?? ''}</div>
`;
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    fillUser(user);
                });
                resultsBox.appendChild(item);
            });
            showResults();
        }

        async function fetchAllUsers() {
            if (fullListLoaded) return fullUserList;
            if (usersFetchPromise) return usersFetchPromise;

            usersFetchPromise = fetch('/admin/external-admins/search', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async res => {
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    if (!data || !data.success || !Array.isArray(data.data)) throw new Error(
                        'Invalid response format');
                    fullUserList = data.data;
                    fullListLoaded = true;
                    return fullUserList;
                })
                .catch(err => {
                    console.error('Fetch all users error:', err);
                    return fullUserList;
                })
                .finally(() => {
                    usersFetchPromise = null;
                });

            return usersFetchPromise;
        }

        function filterUsersLocally(query) {
            const term = query.trim().toLowerCase();
            if (!term) return [];
            return fullUserList.filter(u => [u.full_name, u.fname, u.lname, u.email, u.office]
                .some(v => String(v ?? '').toLowerCase().includes(term))
            );
        }

        searchInput.addEventListener('input', async function () {
            const query = this.value.trim();
            toggleUserSearchClear(this);
            clearFormFields();
            isDropdownMode = false;

            if (!query) {
                hideResults();
                return;
            }
            if (!fullListLoaded) await fetchAllUsers();

            const filtered = filterUsersLocally(query);
            filtered.length ? renderResults(filtered) : renderNoResults('No results found.');
        });

        toggleButton.addEventListener('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (dropdownOpen && isDropdownMode) {
                hideResults();
                return;
            }
            isDropdownMode = true;

            if (!fullListLoaded) await fetchAllUsers();
            fullUserList.length ? renderResults(fullUserList) : renderNoResults(
                'No users available.');
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideResults();
        });

        document.addEventListener('click', function (e) {
            const inside = searchInput.contains(e.target) ||
                toggleButton.contains(e.target) ||
                resultsBox.contains(e.target);
            if (!inside) hideResults();
        });
        const assignForm = document.getElementById('assignCmsAccessForm');
        const cmsRole = document.getElementById('cms_role');
        const cmsStatus = document.getElementById('cms_status');

        function getFieldErrorHost(field) {
            if (!field) return null;

            if (field.classList.contains('access-select')) {
                return field.closest('.cms-custom-select') || field.closest('.field-group');
            }

            return field.closest('.field-group') || field.parentElement;
        }

        function setCmsFieldError(field, message = '') {
            if (!field) return;

            const host = getFieldErrorHost(field);
            const fieldGroup = field.closest('.field-group');
            const customSelect = field.closest('.cms-custom-select');

            field.classList.toggle('is-invalid', Boolean(message));
            host?.classList.toggle('is-invalid', Boolean(message));
            customSelect?.classList.toggle('is-invalid', Boolean(message));

            let errorEl = fieldGroup?.querySelector(`[data-error-for="${field.id}"]`);

            if (!errorEl && fieldGroup) {
                errorEl = document.createElement('div');
                errorEl.className = 'st-field-error cms-field-error';
                errorEl.dataset.errorFor = field.id;
                fieldGroup.appendChild(errorEl);
            }

            if (errorEl) {
                errorEl.innerHTML = message ?
                    `<i class="fa-solid fa-circle-exclamation"></i><span>${message}</span>` :
                    '';
                errorEl.classList.toggle('hidden', !message);
            }
        }

        function validateAssignCmsForm({
            showToastMessage = false
        } = {}) {
            let valid = true;

            if (!externalAdminId.value.trim()) {
                setCmsFieldError(searchInput, 'Please select a user from the dropdown list.');
                valid = false;
            } else {
                setCmsFieldError(searchInput, '');
            }

            if (!cmsRole.value) {
                setCmsFieldError(cmsRole, 'Please select a CMS role.');
                valid = false;
            } else {
                setCmsFieldError(cmsRole, '');
            }

            if (!cmsStatus.value) {
                setCmsFieldError(cmsStatus, 'Please select an access status.');
                valid = false;
            } else {
                setCmsFieldError(cmsStatus, '');
            }

            if (!valid && showToastMessage) {
                window.showToast?.({
                    type: 'error',
                    title: 'Complete required fields',
                    message: 'Select a user, CMS role, and access status before saving.',
                    duration: 6000,
                });

                const firstError = assignForm.querySelector('.is-invalid');
                firstError?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }

            return valid;
        }

        assignForm?.addEventListener('submit', function (event) {
            if (!validateAssignCmsForm({
                showToastMessage: true
            })) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        searchInput?.addEventListener('input', () => {
            if (searchInput.value.trim()) {
                setCmsFieldError(searchInput, '');
            }
        });

        cmsRole?.addEventListener('change', () => {
            setCmsFieldError(cmsRole, cmsRole.value ? '' : 'Please select a CMS role.');
            syncCmsCustomSelects(document);
        });

        cmsStatus?.addEventListener('change', () => {
            setCmsFieldError(cmsStatus, cmsStatus.value ? '' : 'Please select an access status.');
            syncCmsCustomSelects(document);
        });

        if (resetBtn) resetBtn.addEventListener('click', resetAssignCmsForm);

        toggleUserSearchClear(searchInput);
        resetPreview();

        if (window.initGlobalVoiceInputs) {
            window.initGlobalVoiceInputs(document);
        }
    });
</script>
@endsection