@extends('layouts.admin')

@section('title', 'Assign CMS Access | PUP Taguig Dental Clinic')

@section('content')
<main id="mainContent" class="admin-page-shell">
    <div class="cms-shell">
        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Assign CMS Access</h1>
                </div>
            </div>
        </div>

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

                @if (session('success'))
                <div class="status-alert">
                    {{ session('success') }}
                </div>
                @endif

                <form id="assignCmsAccessForm" method="POST" action="{{ route('admin.assign-cms-access.store') }}">
                    @csrf

                    <div class="card-body">
                        <div class="section-block">
                            <div class="section-head">
                                <div class="section-head-left">
                                    <div class="section-icon">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                    <div>
                                        <h3 class="section-title">User Selection</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="cms-grid">
                                <div class="field-group full search-combo">
                                    <label for="user_search" class="field-label">
                                        Select User<span class="required-mark">*</span>
                                    </label>

                                    <div class="user-search-row">
                                        <div class="search-input-wrap">
                                            <input type="text" id="user_search" class="access-input"
                                                placeholder="Search faculty by name or email" autocomplete="off">

                                            <button type="button" id="toggleUserDropdown" class="dropdown-toggle-btn"
                                                aria-label="Show user list">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </button>
                                        </div>

                                        <button type="button" id="userSearchClearBtn"
                                            class="user-search-clear-btn hidden" onclick="clearUserSearch()">
                                            Clear
                                        </button>

                                        {{-- External circular mic button (matches Add User style) --}}
                                        <div class="voice-input-toggle">
                                            <button type="button" id="cmsSearchMicBtn" class="voice-search-mic external"
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
                            <div class="section-head">
                                <div class="section-head-left">
                                    <div class="section-icon">
                                        <i class="fa-solid fa-id-card"></i>
                                    </div>
                                    <div>
                                        <h3 class="section-title">Synced User Information</h3>
                                    </div>
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
                            <div class="section-head">
                                <div class="section-head-left">
                                    <div class="section-icon">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </div>
                                    <div>
                                        <h3 class="section-title">Access Configuration</h3>
                                    </div>
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
                        <button type="button" class="btn-cancel" id="cancelAssignCmsBtn">
                            <i class="fa-solid fa-arrow-left"></i>
                            Cancel
                        </button>

                        <button type="submit" class="btn-save">
                            <i class="fa-solid fa-user-plus"></i>
                            Save Access
                        </button>
                    </div>
                </form>
            </div>

            <div class="sidebar-stack">
                <div class="info-card preview-card">
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

                <div class="info-card">
                    <div class="section-head admin-mb-xs">
                        <div class="section-head-left">
                            <div class="section-icon">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Quick Notes</h3>
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

        // ─── Search / Dropdown logic ────────────────────────────────────────────────
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
        const cancelBtn = document.getElementById('cancelAssignCmsBtn');

        let fullUserList = [];
        let dropdownOpen = false;
        let fullListLoaded = false;
        let isDropdownMode = false;
        let usersFetchPromise = null;

        function toggleUserSearchClear(input) {
            if (!clearSearchButton) return;
            (input.value || '').trim().length > 0
                ? clearSearchButton.classList.remove('hidden')
                : clearSearchButton.classList.add('hidden');
        }

        window.clearUserSearch = function () {
            if (!searchInput) return;
            searchInput.value = '';
            clearFormFields();
            hideResults();
            toggleUserSearchClear(searchInput);
            searchInput.focus();
        };

        function hideResults() {
            resultsBox.style.display = 'none';
            resultsBox.innerHTML = '';
            dropdownOpen = false;
            isDropdownMode = false;
        }

        function showResults() {
            resultsBox.style.display = 'block';
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
            hideResults();
            toggleUserSearchClear(searchInput);
            resetPreview();
        }

        function fillUser(user) {
            externalAdminId.value = user.admin_id ?? '';
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
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(async res => {
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    if (!data || !data.success || !Array.isArray(data.data)) throw new Error('Invalid response format');
                    fullUserList = data.data;
                    fullListLoaded = true;
                    return fullUserList;
                })
                .catch(err => { console.error('Fetch all users error:', err); return fullUserList; })
                .finally(() => { usersFetchPromise = null; });

            return usersFetchPromise;
        }

        function filterUsersLocally(query) {
            const term = query.trim().toLowerCase();
            if (!term) return [];
            return fullUserList.filter(u =>
                [u.full_name, u.fname, u.lname, u.email, u.office]
                    .some(v => String(v ?? '').toLowerCase().includes(term))
            );
        }

        searchInput.addEventListener('input', async function () {
            const query = this.value.trim();
            toggleUserSearchClear(this);
            clearFormFields();
            isDropdownMode = false;

            if (!query) { hideResults(); return; }
            if (!fullListLoaded) await fetchAllUsers();

            const filtered = filterUsersLocally(query);
            filtered.length ? renderResults(filtered) : renderNoResults('No results found.');
        });

        toggleButton.addEventListener('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (dropdownOpen && isDropdownMode) { hideResults(); return; }
            isDropdownMode = true;

            if (!fullListLoaded) await fetchAllUsers();
            fullUserList.length ? renderResults(fullUserList) : renderNoResults('No users available.');
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

        if (cancelBtn) cancelBtn.addEventListener('click', resetAssignCmsForm);

        toggleUserSearchClear(searchInput);
        resetPreview();

        // ─── Voice input for CMS user search (external circular button) ────────────
        (function () {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const micBtn = document.getElementById('cmsSearchMicBtn');
            const status = document.getElementById('cmsSearchVoiceStatus');

            if (!micBtn || !status || !SpeechRecognition) {
                if (micBtn) { micBtn.disabled = true; }
                return;
            }

            let recognition = null;
            let listening = false;
            let manualStop = false;

            const setStatus = (text, state) => {
                status.textContent = text;
                status.className = 'voice-status' + (state ? ' is-' + state : '');
                text ? status.classList.remove('hidden') : status.classList.add('hidden');
            };

            const hideStatus = (delay) => setTimeout(() => status.classList.add('hidden'), delay || 0);

            const setMicState = (isActive) => {
                micBtn.classList.toggle('mic-active', isActive);
                micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                micBtn.innerHTML = isActive
                    ? '<i class="fa-solid fa-stop"></i>'
                    : '<i class="fa-solid fa-microphone"></i>';
            };

            const stopNow = () => {
                manualStop = true;
                listening = false;
                setMicState(false);
                setStatus('Voice captured.', 'success');
                hideStatus(1200);
                if (recognition) { try { recognition.abort(); } catch (e) { } }
            };

            const createRecognition = () => {
                const r = new SpeechRecognition();
                r.lang = 'en-US';
                r.continuous = false;
                r.interimResults = true;
                r.maxAlternatives = 1;

                let sawSpeech = false;
                let timeoutId = null;

                r.onstart = () => {
                    timeoutId = setTimeout(() => {
                        if (listening && !sawSpeech) { try { r.stop(); } catch (e) { } }
                    }, 6000);
                };

                r.onspeechend = () => { clearTimeout(timeoutId); try { r.stop(); } catch (e) { } };

                r.onresult = (event) => {
                    let transcript = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const res = event.results[i];
                        const chunk = (res && res[0] ? res[0].transcript : '').trim();
                        if (!chunk) continue;
                        sawSpeech = true;
                        if (res.isFinal) transcript = (transcript + ' ' + chunk).trim();
                        else if (!transcript) transcript = chunk;
                    }
                    if (transcript) {
                        clearTimeout(timeoutId);
                        searchInput.value = transcript;
                        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                        setStatus('Listening...', 'listening');
                    }
                };

                r.onerror = () => {
                    clearTimeout(timeoutId);
                    listening = false;
                    if (manualStop) { manualStop = false; return; }
                    setMicState(false);
                    setStatus("Didn't catch that. Try again.", 'error');
                    hideStatus(2500);
                };

                r.onend = () => {
                    clearTimeout(timeoutId);
                    if (manualStop) { manualStop = false; listening = false; setMicState(false); return; }
                    const hadSpeech = sawSpeech || !!searchInput.value.trim();
                    listening = false;
                    setMicState(false);
                    hadSpeech
                        ? (setStatus('Voice captured.', 'success'), hideStatus(2200))
                        : (setStatus("Didn't catch that. Try again.", 'error'), hideStatus(2500));
                };

                return r;
            };

            micBtn.addEventListener('click', () => {
                if (listening && recognition) { stopNow(); return; }

                recognition = createRecognition();
                try {
                    recognition.start();
                } catch (e) {
                    setStatus('Unable to start voice input.', 'error');
                    hideStatus(2500);
                    setMicState(false);
                    listening = false;
                    return;
                }
                listening = true;
                setMicState(true);
                setStatus('Listening...', 'listening');
            });
        })();

    });
</script>
@endsection