<div id="record_modal" class="ui-modal" role="dialog" aria-modal="true" aria-labelledby="recordModalTitle">

    <div class="ui-modal-card record-modal-wide">

        <div class="modal-hd appointment-modal-header">
            <div class="modal-heading">

                <div class="appointment-modal-header-icon">
                    <i class="fa-solid fa-file-medical"></i>
                </div>

                <div class="appointment-modal-header-copy">
                    <span class="appointment-modal-eyebrow">
                        Dental Record
                    </span>

                    <h3 id="recordModalTitle" class="appointment-modal-title">
                        <span id="m_service">—</span>
                    </h3>

                    <div class="record-modal-meta">
                        <span>
                            <i class="fa-regular fa-calendar"></i>
                            <span id="m_date">—</span>
                        </span>

                        <span>
                            <i class="fa-regular fa-clock"></i>
                            <span id="m_time">—</span>
                        </span>
                    </div>
                </div>
            </div>

            <button type="button" class="modal-x" id="modalCloseBtn" aria-label="Close dental record">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-bd">

            <div class="record-modal-summary record-modal-summary-three">
                <div class="record-summary-card">
                    <span class="record-summary-label">
                        <i class="fa-solid fa-shield-halved"></i>
                        Status
                    </span>

                    <div class="global-label-row">

                        <div class="global-info-group">

                            <span id="m_status" class="status-pill status-default">

                                <span class="status-dot"></span>
                                —
                            </span>

                            <span id="recordEditedBadge" class="global-info-subvalue" hidden>

                                Edited ·
                                <span id="recordEditedVersion">
                                    v1
                                </span>

                                ·

                                <span id="recordViewHistoryCount">
                                    0
                                </span>
                                versions

                            </span>

                        </div>


                        <div class="global-label-meta">

                            <button type="button" id="recordViewHistoryBtn" class="ui-action-btn ui-action-view"
                                data-tooltip="View edit history" aria-label="View edit history" hidden>

                                <i class="fa-solid fa-clock-rotate-left"></i>

                            </button>

                        </div>

                    </div>
                </div>

                <div class="record-summary-card">
                    <span class="record-summary-label">
                        <i class="fa-regular fa-hourglass-half"></i>
                        Duration
                    </span>

                    <strong id="m_duration" class="record-summary-value">
                        —
                    </strong>
                </div>

                <div class="record-summary-card">
                    <span class="record-summary-label">
                        <i class="fa-solid fa-calendar-plus"></i>
                        Follow-up Appointment
                    </span>

                    <strong id="m_follow_up" class="record-summary-value">
                        —
                    </strong>
                </div>
            </div>

            <div class="record-modal-grid-two">
                <div class="record-modal-section">
                    <div class="record-modal-section-title">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <span>Treatment</span>
                    </div>

                    <div class="record-modal-content">
                        <div id="m_remarks" class="odontogram-preview-marking-list">
                            —
                        </div>
                    </div>
                </div>

                <div class="record-modal-section">
                    <div class="record-modal-section-title">
                        <i class="fa-solid fa-eye"></i>
                        <span>Oral Examination</span>
                    </div>

                    <div class="record-modal-content">
                        <div id="m_oral">—</div>
                    </div>
                </div>
            </div>

            <div class="record-modal-grid-two">
                <div class="record-modal-section">
                    <div class="record-modal-section-title">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Diagnosis</span>
                    </div>

                    <div class="record-modal-content">
                        <div id="m_diagnosis">—</div>
                    </div>
                </div>

                <div class="record-modal-section">
                    <div class="record-modal-section-title">
                        <i class="fa-solid fa-prescription-bottle-medical"></i>
                        <span>Prescription</span>
                    </div>

                    <div class="record-modal-content">
                        <div id="m_prescription">—</div>
                    </div>
                </div>
            </div>

            <div id="recordOdontogramSection" class="record-modal-section record-modal-odontogram-section">

                <div class="global-label-row">

                    <div class="record-modal-section-title global-label-main">
                        <i class="fa-solid fa-tooth"></i>

                        <span>
                            Odontogram
                        </span>
                    </div>

                    <div class="global-label-meta">
                        <a href="#" id="recordModalEditBtn" class="ui-btn ui-btn-primary ui-btn-sm" hidden>

                            <i class="fa-solid fa-pen-to-square"></i>

                            <span>
                                Edit Odontogram
                            </span>
                        </a>
                    </div>

                </div>

                <div id="recordEditLockNotice" class="global-confirm-alert mb-4" hidden>
                    <i class="fa-solid fa-lock"></i>

                    <div>
                        <strong>
                            Editing period expired
                        </strong>

                        <p id="recordEditLockMessage" class="modal-helper-text">
                            The editing period for this odontogram has expired.
                            This record is now read-only.
                        </p>
                    </div>
                </div>

                <div id="m_odontogram" class="record-modal-odontogram relative">

                    @include('components.odontogram-preview', [
                        'odontogramData' => [],
                        'showEditButton' => false,
                        'showHistoricalNotice' => true,
                        'tallPreview' => true,
                    ])

                </div>

            </div>

            <details id="recordRevisionHistorySection" class="global-accordion record-modal-section"
                data-accordion-reveal-on-open hidden>

                <summary class="global-accordion-summary">

                    <span class="global-accordion-left">

                        <i class="fa-solid fa-clock-rotate-left"></i>

                        <span class="global-accordion-title">
                            Edit History
                        </span>

                        <span class="global-info-group">
                            <span id="recordRevisionCountBadge" class="global-info-subvalue">
                                0 versions
                            </span>
                        </span>

                    </span>

                    <i class="fa-solid fa-chevron-down global-accordion-chevron"></i>

                </summary>

                <div class="global-accordion-body">

                    <div id="recordRevisionHistoryContent" data-show-more data-show-more-step="3"
                        data-show-more-label="versions">

                        <div class="record-modal-content">

                            <div id="recordRevisionHistoryList"
                                class="table-list-view max-h-[360px] overflow-y-auto overscroll-contain"
                                data-show-more-list>
                            </div>

                        </div>

                        <x-show-more label="versions" />

                    </div>

                </div>

            </details>

        </div>

        <div class="modal-ft">
            <button type="button" class="ui-btn ui-btn-secondary" id="modalCloseFooter">
                Close
            </button>
        </div>

    </div>
</div>
