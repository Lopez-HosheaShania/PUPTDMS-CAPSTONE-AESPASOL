@extends('layouts.dentist')

@section('title', 'Daily Treatment Record | PUP Taguig Dental Clinic')
@section('content')
<main id="mainContent" class="dentist-page-shell dentist-records-page daily-treatment-page page-enter">
  <div class="w-full">

    <section class="dentist-hero mb-5">
      <div class="dentist-hero-content">
        <div class="dentist-hero-icon">
          <i class="fa-solid fa-notes-medical"></i>
        </div>

        <div class="min-w-0">
          <div class="dentist-hero-eyebrow">
            <i class="fa-solid fa-clipboard-list"></i>
            Dental Services
          </div>

          <h1 class="dentist-hero-title">Daily Treatment Record</h1>
        </div>
      </div>

      <div class="dentist-hero-actions">
        <a href="{{ route('dentist.dentist.report') }}" class="btn-secondary-global">
          <i class="fa-solid fa-arrow-left"></i>
          Back
        </a>

        <button type="button" class="btn-primary-global"
          onclick="document.getElementById('createReportModal').showModal()">
          <i class="fa-solid fa-plus"></i>
          Create Report
        </button>
      </div>
    </section>

    <section class="card service-records-card">
      <div class="card-header">
        <div class="card-header-left">
          <div class="card-header-icon">
            <i class="fa-solid fa-table-list"></i>
          </div>

          <div>
            <h2 class="card-title">Treatment Records</h2>
            <p class="card-subtitle">Daily patient treatment entries</p>
          </div>
        </div>

        <div class="card-header-right service-toolbar search-filter-row">
          <input type="text" id="monthPicker" class="form-input-custom service-period-input js-flatpickr-month"
            placeholder="Select month">

          <div class="voice-search-row service-search-row">
            <div class="search-wrap global-search" data-search-wrapper>
              <i class="fa-solid fa-magnifying-glass search-icon"></i>

              <input id="searchInput" type="text" class="search-input" data-search-input
                placeholder="Search patient, program, treatment…">

              <button type="button" class="search-clear" data-search-clear aria-label="Clear search">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>

            <button type="button" class="voice-search-mic external" data-voice-trigger data-voice-target="#searchInput"
              data-voice-status="#dailySearchVoiceStatus" aria-label="Use voice search">
              <i class="fa-solid fa-microphone"></i>
            </button>

            <span id="dailySearchVoiceStatus" class="voice-status hidden" data-voice-status></span>
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

          <div id="dailyTreatmentViewToggle" class="view-toggle-container" data-global-view-toggle
            data-view-root="#mainContent" data-list-view="#dailyListView" data-grid-view="#dailyGridView"
            data-storage-key="DailyTreatmentViewMode">

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

      <div class="card-body">
        <div id="dailyListView" class="table-responsive-fix service-table-wrap">
          <table class="data-table service-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Patient Name</th>
                <th>Email / Contact Number</th>
                <th>Office / Program</th>
                <th>Gender</th>
                <th>Treatment Done</th>
                <th>Minutes Processed</th>
                <th>Signature</th>
              </tr>
            </thead>

            <tbody id="dailyTableBody"></tbody>
          </table>
        </div>
        <div id="dailyGridView" class="service-record-grid" hidden></div>
      </div>
    </section>
  </div>
</main>

<div id="filterModal" class="filter-drawer-wrapper" aria-hidden="true">
  <div class="filter-drawer-overlay" onclick="closeFilterDrawer('filterModal')"></div>

  <aside class="filter-drawer-panel service-filter-drawer">
    <div class="filter-drawer-header">
      <div class="filter-drawer-title">
        <i class="fa-solid fa-sliders"></i>
        <h2>Filter Records</h2>
      </div>

      <button type="button" class="fp-close-btn" onclick="closeFilterDrawer('filterModal')">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="filter-drawer-body">
      <div class="filter-section-title">Sort by Name</div>

      <div class="choice-chip-grid">
        <label class="choice-chip">
          <input type="radio" name="sort" value="az" class="chip-radio">
          <span>A → Z</span>
        </label>

        <label class="choice-chip">
          <input type="radio" name="sort" value="za" class="chip-radio">
          <span>Z → A</span>
        </label>
      </div>

      <div class="filter-soft-divider"></div>

      <div class="filter-section-title">Date Order</div>

      <div class="choice-chip-grid">
        <label class="choice-chip">
          <input type="radio" name="dateOrder" value="asc" class="chip-radio">
          <span>Ascending</span>
        </label>

        <label class="choice-chip">
          <input type="radio" name="dateOrder" value="desc" class="chip-radio">
          <span>Descending</span>
        </label>
      </div>

      <div class="filter-soft-divider"></div>

      <div class="filter-section-title">Office</div>

      <div class="choice-chip-grid">
        <label class="choice-chip">
          <input type="radio" name="office" class="chip-radio officeRadio" value="Administrative">
          <span>Administrative</span>
        </label>

        <label class="choice-chip">
          <input type="radio" name="office" class="chip-radio officeRadio" value="Faculty">
          <span>Faculty</span>
        </label>

        <label class="choice-chip">
          <input type="radio" name="office" class="chip-radio officeRadio" value="Dependent">
          <span>Dependent</span>
        </label>
      </div>

      <div class="filter-soft-divider"></div>

      <div class="filter-section-title">Course</div>

      <div class="choice-chip-grid service-course-grid">
        @foreach ([
        'BSIT',
        'BSECE',
        'BSBA - HRM',
        'BSED - ENG',
        'BSOA',
        'BSPSYCH',
        'DIT',
        'BSME',
        'BSBA - MM',
        'BSED -
        MATH',
        'DOMT',
        ] as $course)
        <label class="choice-chip">
          <input type="radio" name="course" class="chip-radio programRadio" value="{{ $course }}">
          <span>{{ $course }}</span>
        </label>
        @endforeach
      </div>
    </div>

    <div class="filter-drawer-footer">
      <button id="clearFilterBtn" type="button" class="filter-clear-btn">Clear</button>
      <button id="applyFiltersBtn" type="button" class="filter-apply-btn">
        <i class="fa-solid fa-check"></i>
        Apply
      </button>
  </aside>
</div>
</div>

<dialog id="createReportModal" class="modal">
  <div class="modal-box modal-box-custom service-report-modal">
    <div class="modal-header-custom">
      <div class="modal-icon-custom">
        <i class="fa-solid fa-file-circle-plus"></i>
      </div>

      <div>
        <h2 class="modal-title-custom">Create Daily Treatment Report</h2>
        <p class="modal-sub-custom">Generate and download a treatment report</p>
      </div>

      <button type="button" class="modal-x ml-auto" onclick="document.getElementById('createReportModal').close()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form id="reportForm" class="modal-scroll-body service-report-form">
      <div class="form-grid-2">
        <div>
          <label class="form-label-custom">Report Name</label>
          <input type="text" class="form-input-custom" placeholder="Enter report name">
        </div>

        <div>
          <label class="form-label-custom">Report Type</label>
          <select class="form-select-custom">
            <option selected>Daily Treatment Record</option>
          </select>
        </div>

        <div>
          <label class="form-label-custom">From</label>
          <input type="text" id="fromMonth" class="form-input-custom js-flatpickr-month" placeholder="From month">
        </div>

        <div>
          <label class="form-label-custom">To</label>
          <input type="text" id="toMonth" class="form-input-custom js-flatpickr-month" placeholder="To month">
        </div>

        <div>
          <label class="form-label-custom">Quantity</label>
          <input type="number" value="0" class="form-input-custom">
        </div>
      </div>
    </form>

    <div class="modal-footer-custom">
      <button type="button" class="modal-btn-ghost" onclick="document.getElementById('createReportModal').close()">
        Back
      </button>

      <button type="button" id="downloadReportBtn" class="modal-btn-confirm-approve">
        <i class="fa-solid fa-download"></i>
        Download Report
      </button>
    </div>
  </div>

  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<div id="downloadCompleteTab"
  class="hidden fixed top-4 left-1/2 -translate-x-1/2 bg-green-600 text-white py-2 px-8 rounded-lg shadow-lg">
  Download Complete
</div>
@endsection

@section('scripts')
<script>
  const DTR_LIST_URL = "{{ route('dentist.dentist.reports.daily-treatment-record.list') }}";

  let searchKeyword = "";
  let selectedOffice = null;
  let selectedProgram = null;
  let nameSort = null;
  let dateSort = null;
  let selectedMonth = null;
  let selectedYear = null;

  function formatDateToMMDDYY(dateStr) {
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;

    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    const yy = String(d.getFullYear()).slice(-2);

    return `${mm}/${dd}/${yy}`;
  }

  function renderDailyRecords(data) {
    const tbody = document.getElementById("dailyTableBody");
    tbody.innerHTML = "";

    const grid = document.getElementById("dailyGridView");
    if (grid) grid.innerHTML = "";

    if (!data || data.length === 0) {
      tbody.innerHTML = `
    <tr>
        <td colspan="8">
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

    data.forEach(record => {
      const contact = [
        record.patient_email ? record.patient_email : null,
        record.patient_phone ? record.patient_phone : null
      ].filter(Boolean).join(" / ");

      const officeOrProgram = record.office_type || record.program_code || "";

      const signature = record.has_signature ? "✔" : "";

      tbody.innerHTML += `
      <tr class="text-sm text-gray-800">
        <td>${formatDateToMMDDYY(record.treatment_date)}</td>
        <td>${record.patient_name ?? ""}</td>
        <td>${contact}</td>
        <td>${officeOrProgram}</td>
        <td>${record.gender ?? ""}</td>
        <td>${record.treatment_done ?? ""}</td>
        <td class="text-center">${record.minutes_processed ?? 0}</td>
        <td class="text-center">${signature}</td>
      </tr>
    `;

      if (grid) {
        grid.innerHTML += `
        <article class="service-record-card">
            <div class="service-record-card-head">
                <div>
                    <h3>${record.patient_name ?? ""}</h3>
                    <p>${formatDateToMMDDYY(record.treatment_date)}</p>
                </div>
                <span class="service-record-chip">${record.gender ?? "N/A"}</span>
            </div>

            <div class="service-record-meta">
                <span><i class="fa-solid fa-envelope"></i>${record.patient_email ?? "No email"}</span>
                <span><i class="fa-solid fa-phone"></i>${record.patient_phone ?? "No contact"}</span>
                <span><i class="fa-solid fa-building"></i>${record.office_type || record.program_code || "N/A"}</span>
                <span><i class="fa-solid fa-clock"></i>${record.minutes_processed ?? 0} mins</span>
            </div>

            <p class="service-record-treatment">${record.treatment_done ?? ""}</p>
        </article>
    `;
      }
    });
  }

  async function applyFilters() {
    const params = new URLSearchParams();

    if (selectedYear && selectedMonth) {
      params.set("month", `${selectedYear}-${selectedMonth}`);
    }

    if (searchKeyword) {
      params.set("search", searchKeyword);
    }

    if (selectedOffice) {
      params.set("office_type", selectedOffice);
    }
    if (selectedProgram) {
      params.set("program_code", selectedProgram);
    }

    if (nameSort) params.set("sort_name", nameSort);
    if (dateSort) params.set("sort_date", dateSort);

    try {
      const res = await fetch(`${DTR_LIST_URL}?${params.toString()}`, {
        headers: {
          "Accept": "application/json"
        }
      });

      if (!res.ok) throw new Error("Failed to load records");

      const json = await res.json();
      renderDailyRecords(json.data || []);
      updateFilterButtonState();
    } catch (err) {
      console.error(err);
      renderDailyRecords([]);
      updateFilterButtonState();
    }
  }

  document.getElementById("searchInput").addEventListener("input", e => {
    searchKeyword = e.target.value.trim().toLowerCase();
    applyFilters();
  });

  const officeRadios = document.querySelectorAll(".officeRadio");
  const programRadios = document.querySelectorAll(".programRadio");

  function disableRadios(radios) {
    radios.forEach(r => {
      r.disabled = true;
      r.closest("label")?.classList.add("filter-disabled");
    });
  }

  function enableRadios(radios) {
    radios.forEach(r => {
      r.disabled = false;
      r.closest("label")?.classList.remove("filter-disabled");
    });
  }

  officeRadios.forEach(radio => {
    radio.addEventListener("change", () => {
      selectedOffice = radio.value;
      selectedProgram = null;

      programRadios.forEach(p => p.checked = false);

      disableRadios(programRadios);
      enableRadios(officeRadios);

      updateFilterButtonState();

      applyFilters();
    });
  });

  programRadios.forEach(radio => {
    radio.addEventListener("change", () => {
      selectedProgram = radio.value;
      selectedOffice = null;

      officeRadios.forEach(o => o.checked = false);

      disableRadios(officeRadios);
      enableRadios(programRadios);

      updateFilterButtonState();

      applyFilters();
    });
  });

  document.querySelectorAll("input[name='dateOrder']").forEach(radio => {
    radio.addEventListener("change", () => {
      dateSort = radio.value;
      applyFilters();
      updateFilterButtonState();
    });
  });

  document.querySelectorAll("input[name='sort']").forEach(radio => {
    radio.addEventListener("change", () => {
      nameSort = radio.value;
      dateSort = null;
      applyFilters();
      updateFilterButtonState();
    });
  });

  const filterPill = document.getElementById("openFilter");

  function updateFilterButtonState() {
    const activeCount = [
      selectedOffice,
      selectedProgram,
      nameSort,
      dateSort
    ].filter(Boolean).length;

    window.setGlobalFilterButtonState({
      buttonId: "openFilter",
      badgeId: "filterBadge",
      resetId: "externalClearFilterBtn",
      count: activeCount
    });
  }
  document.getElementById("externalClearFilterBtn")?.addEventListener("click", () => {
    document.getElementById("clearFilterBtn").click();
  });

  document.getElementById("monthPicker").addEventListener("change", e => {
    if (!e.target.value) {
      selectedMonth = null;
      selectedYear = null;
    } else {
      const [year, month] = e.target.value.split("-");
      selectedMonth = month;
      selectedYear = year;
    }

    applyFilters();
  });

  document.getElementById("clearFilterBtn").addEventListener("click", () => {
    searchKeyword = "";
    selectedOffice = null;
    selectedProgram = null;
    nameSort = null;
    dateSort = null;


    document.getElementById("searchInput").value = "";

    document.querySelectorAll("input[type=radio]").forEach(r => {
      r.checked = false;
      r.disabled = false;
    });

    enableRadios(officeRadios);
    enableRadios(programRadios);

    updateFilterButtonState();
    applyFilters();
  });

  document.addEventListener("DOMContentLoaded", () => {
    const monthPicker = document.getElementById("monthPicker");
    const now = new Date();

    const month = String(now.getMonth() + 1).padStart(2, "0");
    const year = now.getFullYear();

    monthPicker.value = `${year}-${month}`;

    selectedMonth = month;
    selectedYear = String(year);

    applyFilters();
  });

  document.getElementById("applyFiltersBtn").addEventListener("click", () => {
    applyFilters();
    closeFilterDrawer("filterModal");
  });

  const now = new Date();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const year = now.getFullYear();

  document.getElementById("fromMonth").value = `${year}-${month}`;
  document.getElementById("toMonth").value = `${year}-${month}`;


  document.getElementById('downloadReportBtn').addEventListener('click', function () {
    setTimeout(function () {
      const downloadCompleteTab = document.getElementById('downloadCompleteTab');
      downloadCompleteTab.classList.remove('hidden');

      const form = document.getElementById('reportForm');
      form.reset();

      setTimeout(function () {
        downloadCompleteTab.classList.add('hidden');
      }, 3000);
    }, 1000);
  });
</script>
@endsection