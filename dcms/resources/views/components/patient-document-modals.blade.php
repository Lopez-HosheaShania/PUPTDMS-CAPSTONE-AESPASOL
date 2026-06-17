<dialog id="dentalClearanceModal" class="patient-doc-modal">
    <form id="clearanceRequestForm" method="POST" action="{{ route('patient.document.requests.store') }}"
        class="patient-doc-modal-box" novalidate>
        @csrf

        <div id="clearanceWarning" class="patient-doc-warning hidden">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>Please complete all required fields.</span>
        </div>

        <div class="patient-doc-modal-head">
            <div class="patient-doc-title-wrap">
                <div class="patient-doc-icon patient-doc-icon-clearance">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
                <div>
                    <p class="patient-doc-eyebrow">Document Request</p>
                    <h3 class="patient-doc-title">Clearance</h3>
                </div>
            </div>

            <button type="button" class="patient-doc-x" onclick="closeDocModal('dentalClearanceModal')"
                aria-label="Close clearance request modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="patient-doc-modal-body">
            <p class="patient-doc-help">
                Select the clearance type and purpose. Processing may take up to three (3) working days.
            </p>

            <div class="patient-doc-field-grid">
                <div class="patient-doc-field">
                    <label class="patient-doc-label">Type of Clearance</label>
                    <div class="doc-dd" data-doc-dropdown>
                        <input type="hidden" name="document_type" required>
                        <button type="button" class="doc-dd-btn" data-doc-toggle aria-expanded="false">
                            <span data-doc-value>Select type of clearance</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="doc-dd-menu" data-doc-menu>
                            <button type="button" class="doc-dd-option" data-value="Dental Clearance">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-file-circle-check"></i></span>
                                <span>
                                    <strong>Dental Clearance</strong>
                                    <small>Standard clearance for official school or clinic requirements.</small>
                                </span>
                            </button>
                            <button type="button" class="doc-dd-option" data-value="Annual Dental Clearance">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-calendar-check"></i></span>
                                <span>
                                    <strong>Annual Dental Clearance</strong>
                                    <small>Yearly clearance for annual compliance or submission.</small>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="patient-doc-field">
                    <label class="patient-doc-label">Purpose</label>
                    <div class="doc-dd" data-doc-dropdown>
                        <input type="hidden" name="purpose" required>
                        <button type="button" class="doc-dd-btn" data-doc-toggle aria-expanded="false">
                            <span data-doc-value>Select purpose</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="doc-dd-menu" data-doc-menu>
                            <button type="button" class="doc-dd-option" data-value="On-the-Job Training (OJT)">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-briefcase"></i></span>
                                <span>
                                    <strong>On-the-Job Training (OJT)</strong>
                                    <small>For internship or training clearance submission.</small>
                                </span>
                            </button>
                            <button type="button" class="doc-dd-option" data-value="Employment Requirement">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-id-badge"></i></span>
                                <span>
                                    <strong>Employment Requirement</strong>
                                    <small>For job application or employment compliance.</small>
                                </span>
                            </button>
                            <button type="button" class="doc-dd-option" data-value="Academic Requirement">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                                <span>
                                    <strong>Academic Requirement</strong>
                                    <small>For school, class, or program document submission.</small>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="patient-doc-modal-footer">
            <button type="button" class="patient-doc-btn patient-doc-btn-secondary"
                onclick="closeDocModal('dentalClearanceModal')">
                Cancel
            </button>
            <button type="submit" class="patient-doc-btn patient-doc-btn-primary">
                <i class="fa-solid fa-paper-plane"></i>
                Submit Request
            </button>
        </div>
    </form>
</dialog>

<dialog id="dentalHealthRecordModal" class="patient-doc-modal">
    <form id="healthRecordRequestForm" method="POST" action="{{ route('patient.document.requests.store') }}"
        class="patient-doc-modal-box" novalidate>
        @csrf

        <div id="healthRecordWarning" class="patient-doc-warning hidden">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>Please complete all required fields.</span>
        </div>

        <div class="patient-doc-modal-head">
            <div class="patient-doc-title-wrap">
                <div class="patient-doc-icon patient-doc-icon-health">
                    <i class="fa-solid fa-file-medical"></i>
                </div>
                <div>
                    <p class="patient-doc-eyebrow">Document Request</p>
                    <h3 class="patient-doc-title">Health Record</h3>
                </div>
            </div>

            <button type="button" class="patient-doc-x" onclick="closeDocModal('dentalHealthRecordModal')"
                aria-label="Close health record request modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="patient-doc-modal-body">
            <p class="patient-doc-help">
                Choose the health record you need and the reason for the request. Processing may take up to three (3)
                working days.
            </p>

            <div class="patient-doc-field-grid">
                <div class="patient-doc-field">
                    <label class="patient-doc-label">Type of Record</label>
                    <div class="doc-dd" data-doc-dropdown>
                        <input type="hidden" name="document_type" required>
                        <button type="button" class="doc-dd-btn" data-doc-toggle aria-expanded="false">
                            <span data-doc-value>Select type of record</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="doc-dd-menu" data-doc-menu>
                            <button type="button" class="doc-dd-option" data-value="All Dental Records">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-folder-open"></i></span>
                                <span>
                                    <strong>All Dental Records</strong>
                                    <small>Includes available dental history, diagnosis, and treatment details.</small>
                                </span>
                            </button>
                            <button type="button" class="doc-dd-option" data-value="Medical Records">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-notes-medical"></i></span>
                                <span>
                                    <strong>Medical Records</strong>
                                    <small>Includes related health information recorded by the clinic.</small>
                                </span>
                            </button>
                            <button type="button" class="doc-dd-option" data-value="Diagnosis and Treatment">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-stethoscope"></i></span>
                                <span>
                                    <strong>Diagnosis and Treatment</strong>
                                    <small>Focused copy of diagnosis notes and treatment information.</small>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="patient-doc-field">
                    <label class="patient-doc-label">Purpose</label>
                    <div class="doc-dd" data-doc-dropdown>
                        <input type="hidden" name="purpose" required>
                        <button type="button" class="doc-dd-btn" data-doc-toggle aria-expanded="false">
                            <span data-doc-value>Select purpose</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="doc-dd-menu" data-doc-menu>
                            <button type="button" class="doc-dd-option" data-value="Personal Record">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-user-check"></i></span>
                                <span>
                                    <strong>Personal Record</strong>
                                    <small>For your own file or personal reference.</small>
                                </span>
                            </button>
                            <button type="button" class="doc-dd-option" data-value="Academic Requirement">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                                <span>
                                    <strong>Academic Requirement</strong>
                                    <small>For class, school office, or program requirement.</small>
                                </span>
                            </button>
                            <button type="button" class="doc-dd-option" data-value="Employment Requirement">
                                <span class="doc-dd-option-icon"><i class="fa-solid fa-id-badge"></i></span>
                                <span>
                                    <strong>Employment Requirement</strong>
                                    <small>For job application or employment compliance.</small>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="patient-doc-modal-footer">
            <button type="button" class="patient-doc-btn patient-doc-btn-secondary"
                onclick="closeDocModal('dentalHealthRecordModal')">
                Cancel
            </button>
            <button type="submit" class="patient-doc-btn patient-doc-btn-primary">
                <i class="fa-solid fa-paper-plane"></i>
                Submit Request
            </button>
        </div>
    </form>
</dialog>

<div id="docSuccessModal" class="doc-success-overlay hidden" aria-hidden="true">
    <div class="doc-success-card" id="docSuccessModalContent" role="dialog" aria-modal="true"
        aria-labelledby="docSuccessTitle">
        <div class="doc-success-icon-wrap">
            <div class="doc-success-icon-ring">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

        <p class="doc-success-eyebrow">Request Submitted</p>
        <h3 id="docSuccessTitle" class="doc-success-title">Request Sent!</h3>
        <p id="docSuccessMessage" class="doc-success-message">
            Your document request has been successfully submitted and is now pending review by the dental clinic.
        </p>

        <div class="doc-success-summary">
            <div class="doc-success-row">
                <span><i class="fa-solid fa-file-lines"></i> Document</span>
                <strong id="docSuccessType">Document Request</strong>
            </div>
            <div class="doc-success-row">
                <span><i class="fa-solid fa-clipboard-list"></i> Purpose</span>
                <strong id="docSuccessPurpose">Not specified</strong>
            </div>
            <div class="doc-success-row">
                <span><i class="fa-regular fa-clock"></i> Processing</span>
                <strong>Up to 3 working days</strong>
            </div>
        </div>

        <button type="button" onclick="closeDocSuccessModal()" class="doc-success-btn">
            Okay, got it!
        </button>
    </div>
</div>

<script>
    function setDocModalLock(isLocked) {
        document.body.classList.toggle('doc-modal-lock', Boolean(isLocked));
        document.documentElement.classList.toggle('doc-modal-lock', Boolean(isLocked));
        document.body.classList.toggle('overflow-hidden', Boolean(isLocked));
    }

    function closeDocDropdowns(exceptDropdown = null) {
        document.querySelectorAll('[data-doc-dropdown].open').forEach((dropdown) => {
            if (dropdown === exceptDropdown) return;
            dropdown.classList.remove('open');
            dropdown.querySelector('[data-doc-toggle]')?.setAttribute('aria-expanded', 'false');
        });
    }

    function resetDocDropdowns(scope) {
        scope.querySelectorAll('[data-doc-dropdown]').forEach((dropdown) => {
            const input = dropdown.querySelector('input[type="hidden"]');
            const valueText = dropdown.querySelector('[data-doc-value]');
            const toggle = dropdown.querySelector('[data-doc-toggle]');
            const firstLabel = toggle?.dataset.placeholder || valueText?.dataset.placeholder;

            if (input) input.value = '';
            dropdown.classList.remove('has-value', 'open');
            dropdown.querySelectorAll('.doc-dd-option').forEach((option) => option.classList.remove('is-selected'));
            toggle?.setAttribute('aria-expanded', 'false');

            if (valueText) {
                valueText.textContent = firstLabel || valueText.getAttribute('data-original-placeholder') || valueText.textContent;
            }
        });
    }

    function closeDocModal(id) {
        const modal = document.getElementById(id);
        if (modal && typeof modal.close === 'function') modal.close();
        closeDocDropdowns();
        if (!document.getElementById('docSuccessModal')?.classList.contains('is-open')) {
            setDocModalLock(false);
        }
    }

    function openDocModal(id) {
        const modal = document.getElementById(id);
        if (!modal || typeof modal.showModal !== 'function') return;
        setDocModalLock(true);
        modal.showModal();
    }

    window.closeDocModal = closeDocModal;
    window.openDocModal = openDocModal;

    function openDocSuccessModal(details = {}) {
        const modal = document.getElementById('docSuccessModal');
        const content = document.getElementById('docSuccessModalContent');
        const typeEl = document.getElementById('docSuccessType');
        const purposeEl = document.getElementById('docSuccessPurpose');
        const messageEl = document.getElementById('docSuccessMessage');

        if (!modal || !content) return;

        const documentType = details.documentType || 'Document Request';
        const purpose = details.purpose || 'Not specified';

        if (typeEl) typeEl.textContent = documentType;
        if (purposeEl) purposeEl.textContent = purpose;
        if (messageEl) {
            messageEl.textContent = `Your ${documentType} request for ${purpose} has been submitted successfully. The dental clinic will review it and notify you once it is ready for release.`;
        }

        setDocModalLock(true);
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        void modal.offsetWidth;
        modal.classList.add('is-open');
        content.classList.add('is-open');
    }

    function closeDocSuccessModal() {
        const modal = document.getElementById('docSuccessModal');
        const content = document.getElementById('docSuccessModalContent');

        if (!modal || !content) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        content.classList.remove('is-open');
        setDocModalLock(false);

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 220);
    }

    window.openDocSuccessModal = openDocSuccessModal;
    window.closeDocSuccessModal = closeDocSuccessModal;

    function showInlineWarning(warningEl, message) {
        if (!warningEl) return;
        const textEl = warningEl.querySelector('span') || warningEl;
        textEl.textContent = message;
        warningEl.classList.remove('hidden');

        setTimeout(() => {
            warningEl.classList.add('hidden');
        }, 2500);
    }

    function getDocRequestDetails(form) {
        return {
            documentType: form.querySelector('[name="document_type"]')?.value || 'Document Request',
            purpose: form.querySelector('[name="purpose"]')?.value || 'Not specified'
        };
    }

    async function submitDocumentRequestForm(formId, modalId, warningId) {
        const form = document.getElementById(formId);
        const modal = document.getElementById(modalId);
        const warningEl = document.getElementById(warningId);

        if (!form || form.dataset.docSubmitReady === 'true') return;
        form.dataset.docSubmitReady = 'true';

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const documentType = form.querySelector('[name="document_type"]');
            const purpose = form.querySelector('[name="purpose"]');
            const submitBtn = form.querySelector('button[type="submit"]');

            if (!documentType?.value || !purpose?.value) {
                showInlineWarning(warningEl, 'Please complete all required fields.');
                return;
            }

            const requestDetails = getDocRequestDetails(form);
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner spin"></i> Submitting...';

            try {
                const formData = new FormData(form);

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    showInlineWarning(warningEl, data.message || 'Failed to submit request.');
                    return;
                }

                if (modal && typeof modal.close === 'function') modal.close();
                form.reset();
                resetDocDropdowns(form);
                openDocSuccessModal(requestDetails);
            } catch (error) {
                showInlineWarning(warningEl, 'Something went wrong. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-doc-value]').forEach((valueEl) => {
            valueEl.dataset.originalPlaceholder = valueEl.textContent.trim();
        });

        document.querySelectorAll('[data-doc-toggle]').forEach((toggle) => {
            const valueEl = toggle.querySelector('[data-doc-value]');
            if (valueEl) toggle.dataset.placeholder = valueEl.textContent.trim();

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const dropdown = toggle.closest('[data-doc-dropdown]');
                const isOpen = dropdown.classList.contains('open');

                closeDocDropdowns(dropdown);
                dropdown.classList.toggle('open', !isOpen);
                toggle.setAttribute('aria-expanded', String(!isOpen));
            });
        });

        document.querySelectorAll('.doc-dd-option').forEach((option) => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const dropdown = option.closest('[data-doc-dropdown]');
                const input = dropdown.querySelector('input[type="hidden"]');
                const valueEl = dropdown.querySelector('[data-doc-value]');
                const toggle = dropdown.querySelector('[data-doc-toggle]');
                const label = option.querySelector('strong')?.textContent?.trim() || option.dataset.value;

                if (input) input.value = option.dataset.value || label;
                if (valueEl) valueEl.textContent = label;

                dropdown.classList.add('has-value');
                dropdown.querySelectorAll('.doc-dd-option').forEach((item) => item.classList.remove('is-selected'));
                option.classList.add('is-selected');
                dropdown.classList.remove('open');
                toggle?.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('[data-doc-dropdown]')) closeDocDropdowns();

            const opener = e.target.closest('[data-doc-open]');
            if (opener) {
                e.preventDefault();
                openDocModal(opener.dataset.docOpen);
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDocDropdowns();
        });

        document.querySelectorAll('.patient-doc-modal').forEach((modal) => {
            modal.addEventListener('close', function() {
                closeDocDropdowns();
                if (!document.getElementById('docSuccessModal')?.classList.contains('is-open')) {
                    setDocModalLock(false);
                }
            });
        });

        const successModal = document.getElementById('docSuccessModal');
        if (successModal) {
            successModal.addEventListener('click', function(e) {
                if (e.target === this) closeDocSuccessModal();
            });
        }

        submitDocumentRequestForm('clearanceRequestForm', 'dentalClearanceModal', 'clearanceWarning');
        submitDocumentRequestForm('healthRecordRequestForm', 'dentalHealthRecordModal', 'healthRecordWarning');
    });
</script>
