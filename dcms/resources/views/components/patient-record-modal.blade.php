<dialog id="record_modal">
    <div class="patient-record-modal-inner">
        <div class="prm-head">
            <button class="prm-close-btn" id="modalCloseBtn" type="button">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <p class="prm-eyebrow">
                <i class="fa-solid fa-circle-check mr-1"></i> Dental Record
            </p>

            <h3 class="prm-title" id="m_service">—</h3>

            <div class="prm-meta-strip">
                <span class="prm-meta-chip">
                    <i class="fa-regular fa-calendar"></i>
                    <span id="m_date">—</span>
                </span>

                <span class="prm-meta-chip">
                    <i class="fa-regular fa-clock"></i>
                    <span id="m_time">—</span>
                </span>
            </div>
        </div>

        <div class="prm-body">
            <div class="prm-chip-row">
                <div class="prm-chip-box">
                    <div class="prm-chip-lbl">
                        <i class="fa-solid fa-shield-halved mr-1"></i> Status
                    </div>
                    <span id="m_status" class="chip-val">—</span>
                </div>

                <div class="prm-chip-box">
                    <div class="prm-chip-lbl">
                        <i class="fa-regular fa-hourglass-half mr-1"></i> Duration
                    </div>
                    <span id="m_duration" class="chip-val">—</span>
                </div>
            </div>

            <div class="prm-sec">
                <div class="prm-sec-head">
                    <span class="prm-sec-lbl"><i class="fa-solid fa-clipboard-list mr-1"></i> Treatment Notes</span>
                    <div class="prm-sec-rule"></div>
                </div>
                <div class="prm-sec-card"><span id="m_remarks">—</span></div>
            </div>

            <div class="prm-sec">
                <div class="prm-sec-head">
                    <span class="prm-sec-lbl"><i class="fa-solid fa-eye mr-1"></i> Oral Examination</span>
                    <div class="prm-sec-rule"></div>
                </div>
                <div class="prm-sec-card"><span id="m_oral">—</span></div>
            </div>

            <div class="prm-sec">
                <div class="prm-sec-head">
                    <span class="prm-sec-lbl"><i class="fa-solid fa-circle-info mr-1"></i> Diagnosis</span>
                    <div class="prm-sec-rule"></div>
                </div>
                <div class="prm-sec-card"><span id="m_diagnosis">—</span></div>
            </div>

            <div class="prm-sec">
                <div class="prm-sec-head">
                    <span class="prm-sec-lbl"><i class="fa-solid fa-prescription-bottle-medical mr-1"></i> Prescription</span>
                    <div class="prm-sec-rule"></div>
                </div>
                <div class="prm-sec-card"><span id="m_prescription">—</span></div>
            </div>
        </div>

        <div class="prm-footer">
            <button type="button" class="prm-close-main" id="modalCloseFooter">
                Close Record
            </button>
        </div>
    </div>
</dialog>

<script>
    function normalizeRecordData(source) {
    if (source instanceof HTMLElement) {
        return {
            service: source.dataset.service || source.dataset.type || '',
            date: source.dataset.date || '',
            time: source.dataset.time || '',
            status: source.dataset.status || '',
            duration: source.dataset.duration || '',
            remarks: source.dataset.remarks || '',
            oral: source.dataset.oral || source.dataset.oralExamination || '',
            diagnosis: source.dataset.diagnosis || '',
            prescription: source.dataset.prescription || ''
        };
    }

    return {
        service: source?.service || source?.service_type || source?.type || '',
        date: source?.date || source?.appointment_date || '',
        time: source?.time || source?.appointment_time || '',
        status: source?.status || '',
        duration: source?.duration || '',
        remarks: source?.remarks || source?.treatment_notes || '',
        oral: source?.oral || source?.oral_examination || '',
        diagnosis: source?.diagnosis || '',
        prescription: source?.prescription || ''
    };
}

function formatRecordTime(raw) {
    if (!raw) return '—';

    raw = String(raw).trim();

    if (raw.includes('–') || raw.includes('-')) return raw;
    if (/[AaPp][Mm]/.test(raw)) return raw;

    var m = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?/);
    if (!m) return raw;

    var h = parseInt(m[1], 10);
    var min = m[2];
    var ampm = h >= 12 ? 'PM' : 'AM';
    var hr = h % 12 || 12;

    return hr + ':' + min + ' ' + ampm;
}

function formatRecordDuration(raw) {
    if (!raw) return '—';

    raw = String(raw).trim();

    if (!raw || raw === '—') return '—';
    if (/[a-zA-Z]/.test(raw)) return raw;

    return raw + ' mins';
}

function formatRecordStatus(status) {
    status = String(status || '').trim();

    if (!status) return '—';

    return status.split(' - ').map(function (part, index) {
        if (index === 0) {
            var base = part.toLowerCase();
            return base.charAt(0).toUpperCase() + base.slice(1);
        }

        if (part.toLowerCase() === 'patient no-show') return 'No-show';
        if (part.toLowerCase() === 'no-show') return 'No-show';

        return part;
    }).join(' - ');
}

function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value && String(value).trim() ? value : '—';
}

function setRecordModalData(source) {
    var data = normalizeRecordData(source || {});

    setText('m_service', data.service);
    setText('m_date', data.date);
    setText('m_time', formatRecordTime(data.time));
    setText('m_duration', formatRecordDuration(data.duration));

    var status = String(data.status || '').trim().toLowerCase();
    var sEl = document.getElementById('m_status');

    if (sEl) {
        sEl.textContent = formatRecordStatus(status);
        sEl.className = 'chip-val';

        if (status === 'completed') {
            sEl.classList.add('bg-emerald-100', 'text-emerald-800');
        } else if (status.startsWith('cancelled') || status.startsWith('canceled')) {
            sEl.classList.add('bg-red-100', 'text-red-800');
        } else if (status === 'rescheduled') {
            sEl.classList.add('bg-yellow-100', 'text-yellow-800');
        } else {
            sEl.classList.add('bg-gray-100', 'text-gray-700');
        }
    }

    setText('m_remarks', data.remarks);
    setText('m_oral', data.oral);
    setText('m_diagnosis', data.diagnosis);
    setText('m_prescription', data.prescription);
}

function openRecordModal(source) {
    var modal = document.getElementById('record_modal');
    if (!modal) return;

    setRecordModalData(source || {});
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    modal.showModal();
}

function openRecordModalFromData(encodedJson) {
    try {
        openRecordModal(JSON.parse(decodeURIComponent(encodedJson)));
    } catch (e) {
        console.error('Invalid record data:', e);
    }
}

function closeRecordModal() {
    const modal = document.getElementById('record_modal');
    if (!modal) return;

    const inner = modal.querySelector('.patient-record-modal-inner');

    if (inner) {
        inner.style.animation = 'modalExit 0.25s ease-in forwards';
        setTimeout(() => {
            modal.close();
            inner.style.animation = '';
        }, 250);
    } else {
        modal.close();
    }

    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
}

function initRecordModal() {
    const modal = document.getElementById('record_modal');
    if (!modal) return;

    const closeBtn = document.getElementById('modalCloseBtn');
    const closeFooter = document.getElementById('modalCloseFooter');

    if (closeBtn) closeBtn.addEventListener('click', closeRecordModal);
    if (closeFooter) closeFooter.addEventListener('click', closeRecordModal);

    modal.addEventListener('click', (e) => {
        const inner = modal.querySelector('.patient-record-modal-inner');
        if (inner && !inner.contains(e.target)) closeRecordModal();
    });

    modal.addEventListener('close', () => {
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
    });
}
</script>