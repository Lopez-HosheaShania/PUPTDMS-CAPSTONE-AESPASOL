@extends('layouts.dentist')

@section('title', 'Dental Services Record | PUP Taguig Dental Clinic')

@section('content')
@php
$records = $records ?? [
[
'date' => '12/01/25',
'timeIn' => '08:30 AM',
'name' => 'Dela Cruz, Juan M.',
'program' => 'BSIT 3-1',
'age' => 21,
'gad' => ['gender' => 'Male', 'priority' => ['PWD']],
'email' => 'juan@gmail.com',
'contact' => '0917-123-4567',
'timeOut' => '09:00 AM',
'duration' => '30 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/01/25',
'timeIn' => '09:10 AM',
'name' => 'Santos, Maria L.',
'program' => 'Faculty',
'age' => 45,
'gad' => ['gender' => 'Female', 'priority' => []],
'email' => 'maria@gmail.com',
'contact' => '0998-456-7890',
'timeOut' => '10:00 AM',
'duration' => '50 mins',
'type' => 'Emergency',
'department' => 'Faculty',
],
[
'date' => '12/02/25',
'timeIn' => '08:45 AM',
'name' => 'Reyes, Paul A.',
'program' => 'Administrative',
'age' => 38,
'gad' => ['gender' => 'Male', 'priority' => []],
'email' => 'paul@gmail.com',
'contact' => '0920-888-1234',
'timeOut' => '09:15 AM',
'duration' => '30 mins',
'type' => 'Non-Emergency',
'department' => 'Administrative',
],
[
'date' => '12/02/25',
'timeIn' => '10:30 AM',
'name' => 'Lopez, Ana C.',
'program' => 'BSBA - HRM 2-2',
'age' => 20,
'gad' => ['gender' => 'Female', 'priority' => []],
'email' => 'ana@gmail.com',
'contact' => '0916-555-7891',
'timeOut' => '11:05 AM',
'duration' => '35 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/03/25',
'timeIn' => '09:00 AM',
'name' => 'Torres, Elaine C.',
'program' => 'Dependent',
'age' => 62,
'gad' => ['gender' => 'Female', 'priority' => ['Senior']],
'email' => 'elaine@gmail.com',
'contact' => '0999-332-4488',
'timeOut' => '09:50 AM',
'duration' => '50 mins',
'type' => 'Non-Emergency',
'department' => 'Dependent',
],
[
'date' => '12/03/25',
'timeIn' => '10:40 AM',
'name' => 'Castillo, Brian R.',
'program' => 'BSECE 2-2',
'age' => 20,
'gad' => ['gender' => 'Male', 'priority' => ['PWD']],
'email' => 'brian@gmail.com',
'contact' => '0908-777-5566',
'timeOut' => '11:15 AM',
'duration' => '35 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/04/25',
'timeIn' => '08:20 AM',
'name' => 'Mendoza, Joshua P.',
'program' => 'BSPSYCH 3-1',
'age' => 21,
'gad' => ['gender' => 'Male', 'priority' => []],
'email' => 'josh@gmail.com',
'contact' => '0917-889-3342',
'timeOut' => '08:50 AM',
'duration' => '30 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/04/25',
'timeIn' => '09:45 AM',
'name' => 'Navarro, Rhea T.',
'program' => 'Faculty',
'age' => 41,
'gad' => ['gender' => 'Female', 'priority' => []],
'email' => 'rhea@gmail.com',
'contact' => '0995-441-2098',
'timeOut' => '10:30 AM',
'duration' => '45 mins',
'type' => 'Emergency',
'department' => 'Faculty',
],
[
'date' => '12/05/25',
'timeIn' => '08:10 AM',
'name' => 'Cruz, Daniel S.',
'program' => 'BSIT 4-1',
'age' => 22,
'gad' => ['gender' => 'Male', 'priority' => []],
'email' => 'daniel@gmail.com',
'contact' => '0928-334-8899',
'timeOut' => '08:40 AM',
'duration' => '30 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/05/25',
'timeIn' => '09:30 AM',
'name' => 'Ramos, Angela D.',
'program' => 'BSED - ENG 2-1',
'age' => 19,
'gad' => ['gender' => 'Female', 'priority' => []],
'email' => 'angela@gmail.com',
'contact' => '0915-223-7781',
'timeOut' => '10:00 AM',
'duration' => '30 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/06/25',
'timeIn' => '10:15 AM',
'name' => 'Tan, Michael K.',
'program' => 'Administrative',
'age' => 36,
'gad' => ['gender' => 'Male', 'priority' => []],
'email' => 'mike@gmail.com',
'contact' => '0991-667-9900',
'timeOut' => '10:55 AM',
'duration' => '40 mins',
'type' => 'Non-Emergency',
'department' => 'Administrative',
],
[
'date' => '12/06/25',
'timeIn' => '01:20 PM',
'name' => 'Lim, Samantha J.',
'program' => 'DOMT 1-2',
'age' => 18,
'gad' => ['gender' => 'Female', 'priority' => []],
'email' => 'sam@gmail.com',
'contact' => '0922-889-4455',
'timeOut' => '01:45 PM',
'duration' => '25 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/07/25',
'timeIn' => '08:40 AM',
'name' => 'Bautista, Kevin A.',
'program' => 'BSME 3-1',
'age' => 21,
'gad' => ['gender' => 'Male', 'priority' => []],
'email' => 'kevin@gmail.com',
'contact' => '0919-556-1123',
'timeOut' => '09:10 AM',
'duration' => '30 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/07/25',
'timeIn' => '10:05 AM',
'name' => 'Flores, Christine M.',
'program' => 'BSBA - MM 4-1',
'age' => 22,
'gad' => ['gender' => 'Female', 'priority' => []],
'email' => 'cflores@gmail.com',
'contact' => '0918-774-9921',
'timeOut' => '10:50 AM',
'duration' => '45 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
[
'date' => '12/09/25',
'timeIn' => '08:25 AM',
'name' => 'Perez, John R.',
'program' => 'BSIT 2-1',
'age' => 19,
'gad' => ['gender' => 'Male', 'priority' => []],
'email' => 'john@gmail.com',
'contact' => '0927-888-1122',
'timeOut' => '08:55 AM',
'duration' => '30 mins',
'type' => 'Non-Emergency',
'department' => 'Student',
],
];
@endphp

<main id="mainContent" class="dentist-page-shell dentist-records-page dental-services-page page-enter">
    <div class="w-full">

        <section class="dentist-hero mb-5">
            <div class="dentist-hero-content">
                <div class="dentist-hero-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <div class="min-w-0">
                    <div class="dentist-hero-eyebrow">
                        <i class="fa-solid fa-table-list"></i>
                        Dental Services
                    </div>

                    <h1 class="dentist-hero-title">Dental Services Record</h1>
                </div>
            </div>

            <div class="dentist-hero-actions">
                <input type="text" id="monthPicker" class="form-input-custom service-period-input js-flatpickr-month"
                    placeholder="Select month">

                <button type="button" onclick="document.getElementById('createReportModal').showModal()"
                    class="btn-primary-global">
                    <i class="fa-solid fa-plus"></i>
                    Create Report
                </button>
            </div>
        </section>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-5 mb-8 kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon bg-red-50">
                    <i class="fa-solid fa-file-lines text-[#8B0000]"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="kpi-value" id="statTotal">0</div>
                    <div class="kpi-label">Total Records</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon bg-amber-50">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="kpi-value text-amber-600" id="statEmergency">0</div>
                    <div class="kpi-label">Emergency</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon bg-green-50">
                    <i class="fa-solid fa-circle-check text-green-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="kpi-value text-green-600" id="statNonEmergency">0</div>
                    <div class="kpi-label">Non-Emergency</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon bg-blue-50">
                    <i class="fa-solid fa-venus text-blue-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="kpi-value text-blue-600" id="statFemale">0</div>
                    <div class="kpi-label">Female Patients</div>
                </div>
            </div>
        </div>

        <div class="card service-records-card">
            <div class="chart-card-header">
                <span class="chart-title">
                    <i class="fa-solid fa-table-list"></i> Patient Records
                </span>

                <div class="service-toolbar search-filter-row">
                    <div class="voice-search-row service-search-row">
                        <div class="search-wrap global-search" data-search-wrapper>
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>

                            <input id="searchInput" type="text" class="search-input" data-search-input
                                placeholder="Search name, program, contact…">

                            <button type="button" class="search-clear" data-search-clear aria-label="Clear search">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <button type="button" class="voice-search-mic external" data-voice-trigger
                            data-voice-target="#searchInput" data-voice-status="#dentalServicesVoiceStatus"
                            aria-label="Use voice search">
                            <i class="fa-solid fa-microphone"></i>
                        </button>

                        <span id="dentalServicesVoiceStatus" class="voice-status hidden" data-voice-status></span>
                    </div>

                    <button id="openFilter" type="button" class="global-filter-btn" aria-pressed="false"
                        onclick="openFilterDrawer('filterModal')">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Filter</span>
                        <span id="filterBadge" class="filter-badge"></span>
                    </button>

                    <button id="externalClearFilterBtn" type="button" class="global-filter-reset-btn hidden">
                        <i class="fa-solid fa-rotate-left"></i>
                        Reset
                    </button>

                    <div id="dentalServicesViewToggle" class="view-toggle-container" data-global-view-toggle
                        data-view-root="#mainContent" data-list-view="#dentalServicesListView"
                        data-grid-view="#dentalServicesGridView" data-storage-key="DentalServicesViewMode">

                        <div class="view-slider"></div>

                        <button type="button" class="btn-view-mode active" data-view-mode="list" aria-label="List view">
                            <i class="fa-solid fa-list"></i>
                            <span class="view-mode-label sr-only">List View</span>
                        </button>

                        <button type="button" class="btn-view-mode" data-view-mode="grid" aria-label="Grid view">
                            <i class="fa-solid fa-grip"></i>
                            <span class="view-mode-label sr-only">Grid View</span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="dentalServicesListView" class="table-responsive-fix service-table-wrap">
                <table class="data-table service-table service-dental-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Name of Patient</th>
                            <th>Course / Dept</th>
                            <th>Age</th>
                            <th>Male</th>
                            <th>Female</th>
                            <th>Senior</th>
                            <th>PWD</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Time Out</th>
                            <th>Duration</th>
                            <th>Emergency</th>
                            <th>Non-Emerg.</th>
                            <th>Sig.</th>
                        </tr>
                    </thead>
                    <tbody id="dentalServicesTableBody"></tbody>
                </table>
            </div>
            <div id="dentalServicesGridView" class="service-record-grid" hidden></div>
        </div>
    </div>
</main>

<div id="filterModal" class="filter-drawer-wrapper" aria-hidden="true">
    <div class="filter-drawer-overlay" onclick="closeFilterDrawer('filterModal')"></div>

    <aside class="filter-drawer-panel">
        <div class="filter-drawer-header">
            <span class="filter-drawer-title"><i class="fa-solid fa-sliders"></i> Filter</span>
            <button class="fp-close-btn" type="button" onclick="closeFilterPanel()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="filter-drawer-body">

            <div class="fp-section">
                <div class="filter-section-title">Sort by Name</div>
                <label class="choice-chip">
                    <input type="radio" name="sort" value="az">
                    <span>A → Z</span>
                </label>
                <label class="choice-chip">
                    <input type="radio" name="sort" value="za">
                    <span>Z → A</span>
                </label>
            </div>

            <div class="fp-section">
                <div class="fp-section-title">Date Order</div>
                <label class="choice-chip">
                    <input type="radio" name="dateOrder" value="asc">
                    <span>Ascending</span>
                </label>
                <label class="choice-chip">
                    <input type="radio" name="dateOrder" value="desc">
                    <span>Descending</span>
                </label>
            </div>

            <div class="fp-section">
                <div class="fp-section-title">Gender</div>
                <label class="choice-chip">
                    <input type="radio" name="gender" value="Male">
                    <span>Male</span>
                </label>
                <label class="choice-chip">
                    <input type="radio" name="gender" value="Female">
                    <span>Female</span>
                </label>
            </div>

            <div class="fp-section">
                <div class="fp-section-title">Priority</div>
                <label class="choice-chip">
                    <input type="checkbox" name="gad" value="PWD" class="gadPriority">
                    <span>PWD</span>
                </label>
                <label class="choice-chip">
                    <input type="checkbox" name="gad" value="Senior" class="gadPriority">
                    <span>Senior</span>
                </label>
            </div>

            <div class="fp-section">
                <div class="fp-section-title">Type</div>
                <label class="choice-chip">
                    <input type="radio" name="type" value="Emergency">
                    <span>Emergency</span>
                </label>
                <label class="choice-chip">
                    <input type="radio" name="type" value="Non-Emergency">
                    <span>Non-Emergency</span>
                </label>
            </div>

            <div class="fp-section">
                <div class="fp-section-title">Department</div>
                <label class="choice-chip">
                    <input type="radio" name="department" value="Administrative" class="departmentRadio">
                    <span>Administrative</span>
                </label>
                <label class="choice-chip">
                    <input type="radio" name="department" value="Faculty" class="departmentRadio">
                    <span>Faculty</span>
                </label>
                <label class="choice-chip">
                    <input type="radio" name="department" value="Dependent" class="departmentRadio">
                    <span>Dependent</span>
                </label>
            </div>

        </div>

        <div class="filter-drawer-footer">
            <button class="filter-clear-btn" id="clearFilterBtn" type="button">Clear All</button>
            <button class="filter-apply-btn" id="applyFiltersBtn" type="button">
                <i class="fa-solid fa-check mr-1"></i> Apply
            </button>
        </div>
</div>

<dialog id="createReportModal" class="modal">
    <div class="modal-box max-w-xl p-0 rounded-2xl overflow-hidden bg-white shadow-2xl flex flex-col"
        style="max-height:min(90vh,640px);">
        <div
            class="bg-gradient-to-r from-[#8B0000] to-[#660000] px-6 py-4 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-file-circle-plus text-white text-base"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-white leading-tight">Create Dental Services Report</h2>
                    <p class="text-white/65 text-[11px] mt-0.5">Generate and download a report</p>
                </div>
            </div>
            <button type="button" onclick="closeCreateModal()"
                class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/35 flex items-center justify-center text-white transition-all flex-shrink-0">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="overflow-y-auto flex-1 px-6 py-5">
            <form id="reportForm" class="space-y-4" novalidate>
                <div>
                    <label class="block text-[11px] font-bold text-[#8B0000] uppercase tracking-wider mb-1">Report
                        Name</label>
                    <input id="reportName" type="text" placeholder="e.g. December 2025 Dental Report"
                        class="w-full px-3.5 py-2 rounded-xl border border-gray-300 bg-white text-sm focus:outline-none focus:border-[#8B0000] transition-colors placeholder-gray-400">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-[#8B0000] uppercase tracking-wider mb-1">Report
                        Type</label>
                    <select id="reportType"
                        class="w-full px-3.5 py-2 rounded-xl border border-gray-300 bg-white text-sm focus:outline-none focus:border-[#8B0000] transition-colors">
                        <option selected>Dental Services Report</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-[#8B0000] uppercase tracking-wider mb-1">Date
                        Range</label>
                    <div class="grid grid-cols-2 gap-3">
                        <input id="dateFrom" type="text" class="form-input-custom js-flatpickr-date-range-from"
                            placeholder="From date">

                        <input id="dateTo" type="text" class="form-input-custom js-flatpickr-date-range-to"
                            placeholder="To date">
                    </div>
                </div>

                <div>
                    <label
                        class="block text-[11px] font-bold text-[#8B0000] uppercase tracking-wider mb-1">Quantity</label>
                    <input id="reportQty" type="number" value="1" min="1"
                        class="w-36 px-3.5 py-2 rounded-xl border border-gray-300 bg-white text-sm focus:outline-none focus:border-[#8B0000] transition-colors">
                </div>
            </form>
        </div>

        <div class="flex-shrink-0 border-t border-gray-100 px-6 py-4 flex justify-end gap-3 bg-gray-50">
            <button type="button" onclick="closeCreateModal()"
                class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 bg-white text-sm font-bold hover:bg-gray-50 transition-all">
                Back
            </button>
            <button type="button" id="downloadReportBtn"
                class="px-6 py-2.5 rounded-xl bg-[#8B0000] hover:bg-[#6b0000] text-white text-sm font-bold flex items-center gap-2 shadow-sm transition-all">
                <i class="fa-solid fa-download"></i> Download Report
            </button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button onclick="closeCreateModal()"></button></form>
</dialog>

<div class="toast" id="toast">
    <i class="fa-solid fa-circle-check"></i> Download Complete
</div>
@endsection

@section('scripts')
<script>
    const records = @json($records);

    let searchKeyword = '';
    let nameSort = null;
    let dateSort = null;
    let selectedMonth = null;
    let selectedCalendarYear = null;
    let selectedGender = null;
    let selectedPriority = [];
    let selectedType = null;
    let selectedDepartment = null;


    function closeCreateModal() {
        document.getElementById('createReportModal').close();
        document.getElementById('reportForm').reset();
    }

    function renderRecords(data) {
        const tbody = document.getElementById('dentalServicesTableBody');
        tbody.innerHTML = '';

        const grid = document.getElementById('dentalServicesGridView');
        if (grid) grid.innerHTML = '';

        document.getElementById('statTotal').textContent = data.length;
        document.getElementById('statEmergency').textContent = data.filter(r => r.type === 'Emergency').length;
        document.getElementById('statNonEmergency').textContent = data.filter(r => r.type === 'Non-Emergency').length;
        document.getElementById('statFemale').textContent = data.filter(r => r.gad.gender === 'Female').length;

        if (!data.length) {
            tbody.innerHTML = `
    <tr>
        <td colspan="16">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <h3 class="empty-state-title">No records found</h3>
                <p class="empty-state-sub">Try adjusting your search or filters.</p>
            </div>
        </td>
    </tr>
`;
            if (grid) {
                grid.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fa-regular fa-folder-open"></i>
            </div>
            <p class="empty-state-title">No records found</p>
            <p class="empty-state-sub">Try adjusting your search or filters.</p>
        </div>
    `;
            }
            return;
        }

        data.forEach((r) => {
            const programMarkup = r.program;

            const emergencyMark = r.type === 'Emergency' ?
                `<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>` :
                '';

            const nonEmergencyMark = r.type === 'Non-Emergency' ?
                `<span class="check-mark"><i class="fa-solid fa-check"></i></span>` :
                '';

            tbody.innerHTML += `
        <tr>
          <td class="muted-cell whitespace-nowrap">${r.date}</td>
          <td class="whitespace-nowrap text-[11px]">${r.timeIn}</td>
          <td class="name-cell">${r.name}</td>
          <td>${programMarkup}</td>
          <td>${r.age}</td>
          <td>${r.gad.gender === 'Male' ? '<span class="check-mark"><i class="fa-solid fa-check"></i></span>' : ''}</td>
          <td>${r.gad.gender === 'Female' ? '<span class="check-mark"><i class="fa-solid fa-check"></i></span>' : ''}</td>
          <td>${r.gad.priority.includes('Senior') ? '<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>' : ''}</td>
          <td>${r.gad.priority.includes('PWD') ? '<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>' : ''}</td>
          <td class="muted-cell">${r.email}</td>
          <td class="text-[11px]">${r.contact}</td>
          <td class="whitespace-nowrap text-[11px]">${r.timeOut}</td>
          <td class="text-[11px]">${r.duration}</td>
          <td>${emergencyMark}</td>
          <td>${nonEmergencyMark}</td>
          <td><span class="check-mark"><i class="fa-solid fa-check"></i></span></td>
        </tr>
      `;

            if (grid) {
                grid.innerHTML += `
        <article class="service-record-card">
            <div class="service-record-card-head">
                <div>
                    <h3>${r.name}</h3>
                    <p>${r.date} • ${r.timeIn} - ${r.timeOut}</p>
                </div>
                <span class="service-record-chip">${r.type}</span>
            </div>

            <div class="service-record-meta">
                <span><i class="fa-solid fa-graduation-cap"></i>${r.program}</span>
                <span><i class="fa-solid fa-envelope"></i>${r.email}</span>
                <span><i class="fa-solid fa-phone"></i>${r.contact}</span>
                <span><i class="fa-solid fa-clock"></i>${r.duration}</span>
            </div>

            <div class="service-record-footer">
                <span>${r.gad.gender}</span>
                <span>${r.department}</span>
            </div>
        </article>
    `;
            }
        });
    }

    function parseRecordDate(dateString) {
        const [month, day, year] = dateString.split('/');
        return new Date(`20${year}-${month}-${day}`);
    }

    function applyFilters() {
        let data = [...records];

        if (searchKeyword) {
            data = data.filter(r =>
                `${r.name} ${r.program} ${r.type} ${r.contact} ${r.email}`
                    .toLowerCase()
                    .includes(searchKeyword)
            );
        }

        if (selectedGender) {
            data = data.filter(r => r.gad.gender === selectedGender);
        }

        if (selectedPriority.length) {
            data = data.filter(r =>
                selectedPriority.every(p => r.gad.priority.includes(p))
            );
        }

        if (selectedType) {
            data = data.filter(r => r.type === selectedType);
        }

        if (selectedDepartment) {
            data = data.filter(r => r.department === selectedDepartment);
        }

        if (selectedMonth && selectedCalendarYear) {
            data = data.filter(r => {
                const [month, , year] = r.date.split('/');
                return month === selectedMonth && `20${year}` === selectedCalendarYear;
            });
        }

        if (dateSort === 'asc') {
            data.sort((a, b) => parseRecordDate(a.date) - parseRecordDate(b.date));
        }

        if (dateSort === 'desc') {
            data.sort((a, b) => parseRecordDate(b.date) - parseRecordDate(a.date));
        }

        if (nameSort === 'az') {
            data.sort((a, b) => a.name.localeCompare(b.name));
        }

        if (nameSort === 'za') {
            data.sort((a, b) => b.name.localeCompare(a.name));
        }

        renderRecords(data);
    }

    function updateFilterButtonState() {
        const activeCount = [
            selectedGender,
            selectedType,
            selectedDepartment,
            nameSort,
            dateSort,
            selectedMonth && selectedCalendarYear ? 'month' : null,
            ...selectedPriority
        ].filter(Boolean).length;

        window.setGlobalFilterButtonState({
            buttonId: 'openFilter',
            badgeId: 'filterBadge',
            resetId: 'externalClearFilterBtn',
            count: activeCount
        });
    }

    document.getElementById('externalClearFilterBtn')?.addEventListener('click', () => {
        document.getElementById('clearFilterBtn').click();
    });
    document.addEventListener('DOMContentLoaded', function () {

        const clearFilterBtn = document.getElementById('clearFilterBtn');
        const clearBtn = document.getElementById('clearBtn');
        const searchInput = document.getElementById('searchInput');
        const monthPicker = document.getElementById('monthPicker');
        const downloadReportBtn = document.getElementById('downloadReportBtn');

        document.getElementById('applyFiltersBtn').addEventListener('click', () => {
            applyFilters();
            closeFilterDrawer('filterModal');
        });

        clearFilterBtn.addEventListener('click', () => {
            selectedGender = null;
            selectedPriority = [];
            selectedType = null;
            selectedDepartment = null;
            nameSort = null;
            dateSort = null;

            document.querySelectorAll('input').forEach(i => i.checked = false);

            applyFilters();
        });

        searchInput.addEventListener('input', (e) => {
            searchKeyword = e.target.value.trim().toLowerCase();
            applyFilters();
        });

        document.querySelectorAll("input[name='sort']").forEach(radio => {
            radio.addEventListener('change', () => {
                nameSort = radio.value;
                applyFilters();
            });
        });

        document.querySelectorAll("input[name='dateOrder']").forEach(radio => {
            radio.addEventListener('change', () => {
                dateSort = radio.value;
                applyFilters();
            });
        });

        document.querySelectorAll("input[name='gender']").forEach(radio => {
            radio.addEventListener('change', () => {
                selectedGender = radio.value;
                applyFilters();
            });
        });

        document.querySelectorAll('.gadPriority').forEach(cb => {
            cb.addEventListener('change', () => {
                selectedPriority = [...document.querySelectorAll('.gadPriority:checked')]
                    .map(i => i.value);
                applyFilters();
            });
        });

        document.querySelectorAll("input[name='type']").forEach(radio => {
            radio.addEventListener('change', () => {
                selectedType = radio.value;
                applyFilters();
            });
        });

        document.querySelectorAll(".departmentRadio").forEach(radio => {
            radio.addEventListener('change', () => {
                selectedDepartment = radio.value;
                applyFilters();
            });
        });

        monthPicker.addEventListener('change', (e) => {
            if (!e.target.value) {
                selectedMonth = null;
                selectedCalendarYear = null;
            } else {
                const [year, month] = e.target.value.split('-');
                selectedMonth = month;
                selectedCalendarYear = year;
            }
            applyFilters();
        });

        downloadReportBtn.addEventListener('click', () => {
            const toast = document.getElementById('toast');
            closeCreateModal();
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        });

        const now = new Date();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const year = now.getFullYear();

        monthPicker.value = `${year}-${month}`;
        selectedMonth = month;
        selectedCalendarYear = String(year);

        applyFilters();
    });
</script>
@endsection