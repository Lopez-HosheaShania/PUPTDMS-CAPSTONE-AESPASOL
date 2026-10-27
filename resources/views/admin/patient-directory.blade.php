<style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    .patient-search-row {
        display: flex;
        align-items: center;
        gap: .65rem;
        width: 100%;
        max-width: 430px;
        margin-bottom: 18px;
    }

    .patient-search-wrap {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        max-width: none;
        background: #f5f5f5;
        border: 2px solid #b91c1c;
        border-radius: 16px;
        padding: 0 14px;
        min-height: 48px;
        overflow: visible;
    }

    .patient-search-wrap:focus-within {
        border-color: #8B0000;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .12);
    }

    .patient-search-wrap input {
        width: 100%;
        border: none;
        background: none;
        outline: none;
        color: #1f2937;
        font-size: 1rem;
        padding: 0;
    }

    .patient-search-wrap input::placeholder {
        color: #9ca3af;
        font-size: 14px;
    }

    .patient-search-wrap input.has-voice-padding {
        padding-right: 0 !important;
    }

    .patient-search-wrap [data-voice-trigger],
    .patient-search-wrap .voice-search-mic {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        border: none;
        background: transparent;
        padding: 0;
        margin: 0;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #8B0000;
        cursor: pointer;
        z-index: 5;
    }

    .patient-search-wrap [data-voice-status] {
        position: absolute;
        right: 48px;
        top: -1.15rem;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        font-size: .74rem;
        font-weight: 700;
        line-height: 1;
        padding: .18rem .48rem;
        border-radius: 999px;
        pointer-events: none;
        z-index: 6;
        background: #f0fdf4;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
    }

    .patient-search-wrap [data-voice-status].hidden {
        display: none;
    }

    .patient-search-clear-btn {
        border: none;
        background: transparent;
        color: #dc2626;
        font-size: .95rem;
        font-weight: 700;
        line-height: 1;
        padding: 0;
        margin: 0;
        cursor: pointer;
        flex: 0 0 auto;
        transition: color .15s ease;
        align-self: center;
    }

    .patient-search-clear-btn.hidden {
        display: none;
    }

    .patient-search-clear-btn:hover {
        color: #991b1b;
    }
</style>

<div class="max-w-7xl mt-4 mx-auto fade-in">

    <div
        data-ui-presentation="presentation-a5e0610cadcf">
        <div>
            <div data-ui-presentation="presentation-dc8ec3996a54">
                Clinic Management
            </div>

            <h1 data-ui-presentation="presentation-72b790b505ec">
                Patient Directory
            </h1>

            <p data-ui-presentation="presentation-1d6fd219a2a1">
                View patient dashboards and records as Admin.
            </p>
        </div>
    </div>

    <div class="patient-search-row">
        <div class="search-wrap patient-search-wrap">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="patientSearch" placeholder="Search patient name..." data-ui-input="action-297be29dcc37"
                data-ui-presentation="presentation-fd2cf9e09b85">
        </div>
        <button type="button" id="patientSearchClearBtn" class="patient-search-clear-btn hidden"
            title="Clear">Clear</button>
    </div>

    <div data-ui-presentation="presentation-00af67f48c33">

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr data-ui-presentation="presentation-4157576c5fb2">
                        <th>ID</th>
                        <th>Patient Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody id="patientTableBody">
                    @foreach($patients as $patient)
                    <tr class="hover patient-row" data-ui-presentation="presentation-c98bf92f7006"
                        data-name="{{ strtolower($patient->name) }}" data-email="{{ strtolower($patient->email) }}">
                        <td data-ui-presentation="presentation-6b2a94ea3bb9">
                            {{ $patient->id }}
                        </td>

                        <td data-ui-presentation="presentation-96c8011139f8">
                            {{ $patient->name }}
                        </td>

                        <td data-ui-presentation="presentation-3a309476733d">
                            {{ $patient->email }}
                        </td>

                        <td data-ui-presentation="presentation-3a309476733d">
                            {{ $patient->phone ?? '—' }}
                        </td>

                        <td class="text-center">
                            <div data-ui-presentation="presentation-4edc8a0dd9d4">

                                <button data-ui-click="action-d58a9c6e6bde" data-ui-presentation="presentation-c985aed09217">
                                    <i class="fa-solid fa-eye"></i>
                                    View Dashboard
                                </button>

                                <button data-ui-click="action-36814e8a29cf" data-ui-presentation="presentation-fd02dceb6efb">
                                    <i class="fa-solid fa-file-medical"></i>
                                    View Record
                                </button>

                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div id="noPatientResults" data-ui-presentation="presentation-9726dc2e78a3">
                <div data-ui-presentation="presentation-0c34a6f2101e">
                    No results for "<span id="searchQueryText"></span>"
                </div>
                <div data-ui-presentation="presentation-905228b6d52d">
                    Try a different patient name.
                </div>
            </div>

        </div>
    </div>
</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    function filterPatients() {
        const input = document.getElementById('patientSearch');
        const query = input.value.trim().toLowerCase();
        const rows = document.querySelectorAll('.patient-row');
        const noResults = document.getElementById('noPatientResults');
        const searchQueryText = document.getElementById('searchQueryText');

        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.dataset.name || '';
            const email = row.dataset.email || '';
            const match = name.includes(query) || email.includes(query);

            row.style.display = match ? '' : 'none';

            if (match) {
                visibleCount++;
            }
        });

        if (query !== '' && visibleCount === 0) {
            noResults.style.display = 'block';
            searchQueryText.textContent = input.value;
        } else {
            noResults.style.display = 'none';
            searchQueryText.textContent = '';
        }

        syncPatientSearchClear();
    }

    function syncPatientSearchClear() {
        const input = document.getElementById('patientSearch');
        const clearBtn = document.getElementById('patientSearchClearBtn');
        if (!input || !clearBtn) return;
        clearBtn.classList.toggle('hidden', input.value.trim().length === 0);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('patientSearch');
        const clearBtn = document.getElementById('patientSearchClearBtn');
        if (!input || !clearBtn) return;

        syncPatientSearchClear();

        clearBtn.addEventListener('click', () => {
            input.value = '';
            const status = input.closest('.patient-search-wrap')?.querySelector('[data-voice-status]');
            if (status) status.classList.add('hidden');
            filterPatients();
            input.focus();
        });
    });
</script>