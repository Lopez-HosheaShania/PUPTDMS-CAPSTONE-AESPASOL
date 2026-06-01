@extends('layouts.admin')

@section('title', 'Faculty Integration | PUP Taguig Dental Clinic')

@section('content')
<main id="mainContent" class="admin-page-shell faculty-page">
    <div class="faculty-shell">
        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Faculty Integration</h1>
                </div>
            </div>
        </div>

        <div class="faculty-layout">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-left">
                        <div class="card-header-icon">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <div>
                            <h2 class="card-title">Faculty Integration Form</h2>
                        </div>
                    </div>
                    <span class="entry-badge">Faculty Setup</span>
                </div>

                @if (session('success'))
                <div class="status-alert success">
                    {{ session('success') }}
                </div>
                @endif

                @if (session('error'))
                <div class="status-alert error">
                    {{ session('error') }}
                </div>
                @endif

                @if ($errors->any())
                <div class="status-alert error">
                    <ul class="admin-alert-list">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form id="facultyIntegrationForm" method="POST" action="{{ route('admin.faculty.store') }}">
                    @csrf

                    <div class="card-body">
                        <div class="section-block">
                            <div class="section-head">
                                <div class="section-head-left">
                                    <div class="section-icon">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                    <div>
                                        <h3 class="section-title">Faculty Selection</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="faculty-grid">
                                <div class="field-group full search-combo">
                                    <label for="faculty_search" class="field-label">
                                        Select Faculty<span class="required-mark">*</span>
                                    </label>

                                    <div class="faculty-search-row">
                                        <div class="search-input-wrap">
                                            <input type="text" id="faculty_search" class="access-input"
                                                placeholder="Search faculty by name, email, or faculty code"
                                                autocomplete="off">

                                            <button type="button" id="toggleFacultyDropdown" class="dropdown-toggle-btn"
                                                aria-label="Show faculty list">
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </button>
                                        </div>

                                        <button type="button" id="facultySearchClearBtn"
                                            class="faculty-search-clear-btn hidden" onclick="clearFacultySearch()">
                                            Clear
                                        </button>

                                        {{-- External circular mic button (matches Add User style) --}}
                                        <div class="voice-input-toggle">
                                            <button type="button" id="facultyMicBtn" class="voice-search-mic external"
                                                aria-label="Toggle voice input" aria-pressed="false">
                                                <i class="fa-solid fa-microphone"></i>
                                            </button>
                                            <span id="facultyVoiceStatus" class="voice-status hidden"
                                                aria-live="polite"></span>
                                        </div>
                                    </div>

                                    <div id="facultyResults" class="search-results"></div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="faculty_json" name="faculty_json">

                        <div class="section-block">
                            <div class="section-head">
                                <div class="section-head-left">
                                    <div class="section-icon">
                                        <i class="fa-solid fa-id-card"></i>
                                    </div>
                                    <div>
                                        <h3 class="section-title">Synced Faculty Information</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="faculty-grid">
                                <div class="field-group">
                                    <label for="faculty_id" class="field-label">External Faculty ID</label>
                                    <input type="text" id="faculty_id" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="faculty_code" class="field-label">Faculty Code</label>
                                    <input type="text" id="faculty_code" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="first_name" class="field-label">First Name</label>
                                    <input type="text" id="first_name" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="middle_name" class="field-label">Middle Name</label>
                                    <input type="text" id="middle_name" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="last_name" class="field-label">Last Name</label>
                                    <input type="text" id="last_name" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="suffix_name" class="field-label">Suffix Name</label>
                                    <input type="text" id="suffix_name" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="faculty_type" class="field-label">Faculty Type</label>
                                    <input type="text" id="faculty_type" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="department" class="field-label">Department</label>
                                    <input type="text" id="department" class="access-input" readonly>
                                </div>

                                <div class="field-group full">
                                    <label for="email" class="field-label">Email</label>
                                    <input type="email" id="email" class="access-input" readonly>
                                </div>
                            </div>

                            <div class="faculty-grid-3 admin-mt-4">
                                <div class="field-group">
                                    <label for="birthday" class="field-label">Birthday</label>
                                    <input type="text" id="birthday" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="gender" class="field-label">Gender</label>
                                    <input type="text" id="gender" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="zipcode" class="field-label">Zipcode</label>
                                    <input type="text" id="zipcode" class="access-input" readonly>
                                </div>
                            </div>

                            <div class="faculty-grid-3 admin-mt-4">
                                <div class="field-group">
                                    <label for="house_num" class="field-label">House / Unit No.</label>
                                    <input type="text" id="house_num" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="street" class="field-label">Street</label>
                                    <input type="text" id="street" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="barangay" class="field-label">Barangay</label>
                                    <input type="text" id="barangay" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="city" class="field-label">City</label>
                                    <input type="text" id="city" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="province" class="field-label">Province</label>
                                    <input type="text" id="province" class="access-input" readonly>
                                </div>

                                <div class="field-group">
                                    <label for="country" class="field-label">Country</label>
                                    <input type="text" id="country" class="access-input" readonly>
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

                            <div class="faculty-grid">
                                <div class="field-group">
                                    <label for="cms_role" class="field-label">
                                        CMS Role<span class="required-mark">*</span>
                                    </label>
                                    <select name="cms_role" id="cms_role" class="access-select" required>
                                        <option value="" disabled selected hidden>Select CMS Role</option>
                                        <option value="patient">Patient</option>
                                        <option value="admin">Admin</option>
                                        <option value="dentist">Dentist</option>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label for="account_status" class="field-label">
                                        Account Status<span class="required-mark">*</span>
                                    </label>
                                    <select name="account_status" id="account_status" class="access-select" required>
                                        <option value="" disabled selected hidden>Select Status</option>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="access-card-footer">
                        <button type="button" class="btn-cancel" id="cancelFacultyBtn">
                            <i class="fa-solid fa-arrow-left"></i>
                            Cancel
                        </button>

                        <button type="submit" class="btn-save">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Faculty
                        </button>
                    </div>
                </form>
            </div>

            <div class="sidebar-stack">
                <div class="info-card preview-card">
                    <div class="preview-inner">
                        <div class="preview-avatar">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>

                        <div class="preview-name" id="preview_name">No faculty selected</div>
                        <div class="preview-email" id="preview_email">Select a faculty record to preview the synced
                            information.</div>

                        <div class="preview-meta">
                            <div class="preview-meta-item">
                                <div class="preview-meta-label">Faculty Code</div>
                                <div class="preview-meta-value" id="preview_code">—</div>
                            </div>

                            <div class="preview-meta-item">
                                <div class="preview-meta-label">Department</div>
                                <div class="preview-meta-value" id="preview_department">—</div>
                            </div>

                            <div class="preview-meta-item">
                                <div class="preview-meta-label">Faculty Type</div>
                                <div class="preview-meta-value" id="preview_type">—</div>
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
                            <span>Select from the dropdown or filtered search results to keep faculty information
                                accurately synced.</span>
                        </div>
                        <div class="tip-item">
                            <i class="fa-solid fa-user-gear"></i>
                            <span>Review the faculty type, department, and email before assigning the CMS role.</span>
                        </div>
                        <div class="tip-item">
                            <i class="fa-solid fa-shield"></i>
                            <span>Use <strong>Inactive</strong> status when the record should remain stored but account
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

        // ─── Element refs ───────────────────────────────────────────────────────────
        const searchInput = document.getElementById('faculty_search');
        const toggleButton = document.getElementById('toggleFacultyDropdown');
        const clearSearchButton = document.getElementById('facultySearchClearBtn');
        const resultsBox = document.getElementById('facultyResults');

        const facultyJson = document.getElementById('faculty_json');
        const facultyId = document.getElementById('faculty_id');
        const firstName = document.getElementById('first_name');
        const middleName = document.getElementById('middle_name');
        const lastName = document.getElementById('last_name');
        const suffixName = document.getElementById('suffix_name');
        const facultyCode = document.getElementById('faculty_code');
        const facultyType = document.getElementById('faculty_type');
        const department = document.getElementById('department');
        const email = document.getElementById('email');
        const birthday = document.getElementById('birthday');
        const gender = document.getElementById('gender');
        const houseNum = document.getElementById('house_num');
        const street = document.getElementById('street');
        const barangay = document.getElementById('barangay');
        const city = document.getElementById('city');
        const province = document.getElementById('province');
        const country = document.getElementById('country');
        const zipcode = document.getElementById('zipcode');

        const previewName = document.getElementById('preview_name');
        const previewEmail = document.getElementById('preview_email');
        const previewCode = document.getElementById('preview_code');
        const previewDepartment = document.getElementById('preview_department');
        const previewType = document.getElementById('preview_type');
        const cancelFacultyBtn = document.getElementById('cancelFacultyBtn');

        let faculties = [];
        let dropdownOpen = false;
        let isDropdownMode = false;

        // ─── Helpers ────────────────────────────────────────────────────────────────
        function toggleFacultySearchClear(input) {
            if (!clearSearchButton) return;
            (input.value || '').trim().length > 0
                ? clearSearchButton.classList.remove('hidden')
                : clearSearchButton.classList.add('hidden');
        }

        window.clearFacultySearch = function () {
            if (!searchInput) return;
            searchInput.value = '';
            clearFields();
            hideResults();
            toggleFacultySearchClear(searchInput);
            searchInput.focus();
        };

        function showResults() {
            resultsBox.style.display = 'block';
            dropdownOpen = true;
        }

        function hideResults() {
            resultsBox.style.display = 'none';
            resultsBox.innerHTML = '';
            dropdownOpen = false;
            isDropdownMode = false;
        }

        function resetPreview() {
            previewName.textContent = 'No faculty selected';
            previewEmail.textContent = 'Select a faculty record to preview the synced information.';
            previewCode.textContent = '—';
            previewDepartment.textContent = '—';
            previewType.textContent = '—';
        }

        function clearFields() {
            facultyJson.value = '';
            facultyId.value = firstName.value = middleName.value = lastName.value =
                suffixName.value = facultyCode.value = facultyType.value =
                department.value = email.value = birthday.value = gender.value =
                houseNum.value = street.value = barangay.value = city.value =
                province.value = country.value = zipcode.value = '';
            resetPreview();
        }

        function resetFacultyForm() {
            searchInput.value = '';
            clearFields();
            document.getElementById('cms_role').value = '';
            document.getElementById('account_status').value = '';
            hideResults();
            toggleFacultySearchClear(searchInput);
        }

        function fillFaculty(faculty) {
            const profile = faculty.profile ?? {};
            const addr = profile.address ?? {};

            facultyJson.value = JSON.stringify(faculty);
            searchInput.value = `${faculty.first_name ?? ''} ${faculty.last_name ?? ''}`.trim();

            facultyId.value = faculty.faculty_id ?? '';
            firstName.value = faculty.first_name ?? '';
            middleName.value = faculty.middle_name ?? '';
            lastName.value = faculty.last_name ?? '';
            suffixName.value = faculty.suffix_name ?? '';
            facultyCode.value = faculty.faculty_code ?? '';
            facultyType.value = faculty.faculty_type ?? '';
            department.value = faculty.department ?? '';
            email.value = faculty.email ?? '';
            birthday.value = profile.birthday ?? '';
            gender.value = profile.gender ?? '';
            houseNum.value = addr.house_num ?? '';
            street.value = addr.street ?? '';
            barangay.value = addr.barangay ?? '';
            city.value = addr.city ?? '';
            province.value = addr.province ?? '';
            country.value = addr.country ?? '';
            zipcode.value = addr.zipcode ?? '';

            previewName.textContent = `${faculty.first_name ?? ''} ${faculty.last_name ?? ''}`.trim() || 'Selected faculty';
            previewEmail.textContent = faculty.email ?? 'No email available';
            previewCode.textContent = faculty.faculty_code ?? '—';
            previewDepartment.textContent = faculty.department ?? '—';
            previewType.textContent = faculty.faculty_type ?? '—';

            toggleFacultySearchClear(searchInput);
            hideResults();
        }

        function renderNoResults(message = 'No results found.') {
            resultsBox.innerHTML = `<div class="search-empty">${message}</div>`;
            showResults();
        }

        function renderResults(list) {
            resultsBox.innerHTML = '';

            if (!Array.isArray(list) || list.length === 0) {
                renderNoResults();
                return;
            }

            list.forEach(faculty => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'search-item';

                const fullName = `${faculty.first_name ?? ''} ${faculty.middle_name ?? ''} ${faculty.last_name ?? ''}`
                    .replace(/\s+/g, ' ').trim();

                item.innerHTML = `
                        <div class="search-name">${fullName || 'Unnamed Faculty'}</div>
                        <div class="search-email">${faculty.email ?? faculty.faculty_code ?? ''}</div>
                    `;

                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    fillFaculty(faculty);
                });

                resultsBox.appendChild(item);
            });

            showResults();
        }

        function filterFaculties(query) {
            const q = query.trim().toLowerCase();
            if (!q) return [];

            return faculties.filter(f => {
                const name = `${f.first_name ?? ''} ${f.middle_name ?? ''} ${f.last_name ?? ''}`.toLowerCase();
                return name.includes(q) ||
                    (f.email ?? '').toLowerCase().includes(q) ||
                    (f.faculty_code ?? '').toLowerCase().includes(q) ||
                    (f.department ?? '').toLowerCase().includes(q);
            });
        }

        // ─── Load faculty list ──────────────────────────────────────────────────────
        fetch('/faculties', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(data => { faculties = Array.isArray(data) ? data : []; })
            .catch(err => { console.error('Failed to load faculties:', err); faculties = []; });

        // ─── Search input ───────────────────────────────────────────────────────────
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            toggleFacultySearchClear(this);
            clearFields();
            isDropdownMode = false;

            if (!query) { hideResults(); return; }

            const filtered = filterFaculties(query);
            filtered.length ? renderResults(filtered) : renderNoResults('No results found.');
        });

        // ─── Dropdown toggle ────────────────────────────────────────────────────────
        toggleButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (dropdownOpen && isDropdownMode) { hideResults(); return; }
            isDropdownMode = true;
            faculties.length ? renderResults(faculties) : renderNoResults('No faculty records available.');
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

        if (cancelFacultyBtn) cancelFacultyBtn.addEventListener('click', resetFacultyForm);

        toggleFacultySearchClear(searchInput);
        resetPreview();

        // ─── Voice input for faculty search (external circular button) ──────────────
        (function () {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const micBtn = document.getElementById('facultyMicBtn');
            const status = document.getElementById('facultyVoiceStatus');

            if (!micBtn || !status || !SpeechRecognition) {
                if (micBtn) micBtn.disabled = true;
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