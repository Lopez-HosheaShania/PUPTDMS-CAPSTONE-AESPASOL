import {
    setOdontogramPreviewData
} from '../odontogram/odontogram-preview';

function parseRecordDatasetJson(value, fallback) {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value);
    } catch (_) {
        return fallback;
    }
}

function normalizeRecordData(source) {
    if (source instanceof HTMLElement) {
        if (source.dataset.record) {
            try {
                return normalizeRecordData(JSON.parse(source.dataset.record));
            } catch (_) {
            }
        }

        return {
            id:
                source.dataset.apptId ||
                source.dataset.appointmentId ||
                null,

            service: source.dataset.service ||
                source.dataset.type ||
                '',

            date: source.dataset.date ||
                '',

            time: source.dataset.time ||
                '',

            status: source.dataset.status ||
                '',

            durationSeconds: source.dataset.durationSeconds ??
                null,

            duration: source.dataset.duration ||
                '',

            remarks: source.dataset.remarks ||
                source.dataset.treatment ||
                '',

            treatmentItems: parseRecordDatasetJson(
                source.dataset.treatmentItems,
                []
            ),

            oral: source.dataset.oral ||
                source.dataset.oralExamination ||
                '',

            diagnosis: source.dataset.diagnosis ||
                '',

            prescription: source.dataset.prescription ||
                source.dataset.prescriptions ||
                '',

            followUp: parseRecordDatasetJson(
                source.dataset.followUp,
                null
            ),

            editUrl:
                source?.edit_url ||
                source?.editUrl ||
                '',

            editLocked:
                source?.edit_locked ??
                source?.editLocked ??
                false,

            editLockReason:
                source?.edit_lock_reason ||
                source?.editLockReason ||
                '',

            editDeadline:
                source?.edit_deadline ||
                source?.editDeadline ||
                null,

            revisions: parseRecordDatasetJson(
                source.dataset.revisions,
                []
            ),

            currentVersion:
                source.dataset.currentVersion ?
                    Number(
                        source.dataset.currentVersion
                    ) :
                    null,

            odontogramData: parseRecordDatasetJson(
                source.dataset.odontogramData,
                []
            )
        };
    }

    return {
        id:
            source?.id ??
            source?.appointment_id ??
            null,

        service:
            source?.service ||
            source?.service_type ||
            source?.type ||
            '',

        date: source?.date ||
            source?.appointment_date ||
            '',

        time: source?.time ||
            source?.appointment_time ||
            '',

        status: source?.status ||
            '',

        durationSeconds: source?.duration_seconds ??
            source?.procedure_duration_seconds ??
            null,

        duration: source?.duration ??
            '',

        remarks: source?.remarks ||
            source?.treatment_notes ||
            source?.treatment ||
            '',

        treatmentItems: source?.treatment_items ||
            source?.treatmentItems || [],

        oral: source?.oral ||
            source?.oral_examination ||
            '',

        diagnosis: source?.diagnosis ||
            '',

        prescription: source?.prescription ||
            source?.prescriptions ||
            '',

        followUp: source?.follow_up ||
            source?.followUp ||
            null,

        editUrl:
            source?.edit_url ||
            source?.editUrl ||
            '',

        editLocked:
            source?.edit_locked ??
            source?.editLocked ??
            false,

        editLockReason:
            source?.edit_lock_reason ||
            source?.editLockReason ||
            '',

        editDeadline:
            source?.edit_deadline ||
            source?.editDeadline ||
            null,

        revisions:
            source?.revisions ||
            source?.revision_history ||
            [],

        currentVersion:
            source?.current_version ??
            source?.currentVersion ??
            null,

        odontogramData: source?.odontogram ||
            source?.odontogram_data ||
            source?.odontogramData || []
    };
}

function formatRecordDurationSeconds(
    seconds
) {
    const total =
        Number(seconds);

    if (
        !Number.isFinite(total) ||
        total <= 0
    ) {
        return '—';
    }

    const hours =
        Math.floor(
            total / 3600
        );

    const minutes =
        Math.floor(
            (
                total % 3600
            ) / 60
        );

    const remainingSeconds =
        total % 60;

    const parts = [];

    if (hours) {
        parts.push(
            `${hours} hr${hours === 1 ? '' : 's'}`
        );
    }

    if (minutes) {
        parts.push(
            `${minutes} min${minutes === 1 ? '' : 's'}`
        );
    }

    if (
        remainingSeconds ||
        (!hours && !minutes)
    ) {
        parts.push(
            `${remainingSeconds} sec${remainingSeconds === 1 ? '' : 's'}`
        );
    }

    return parts.join(' ');
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

function formatRecordTreatment(value) {
    const normalized =
        String(value ?? '')
            .trim()
            .toLowerCase();

    if (!normalized) {
        return 'No treatment record yet.';
    }

    if (normalized === 'finished') {
        return 'Finished procedure';
    }

    if (normalized === 'follow_up') {
        return 'Follow-up required';
    }

    return String(value)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, letter =>
            letter.toUpperCase()
        );
}

function formatRecordFollowUp(followUp) {
    if (!followUp) {
        return 'No follow-up appointment scheduled.';
    }

    const parts = [
        followUp.date,
        followUp.time,
        followUp.service
    ].filter(Boolean);

    let text = parts.join(' • ');

    if (followUp.reason) {
        text += `${text ? ' • ' : ''}${followUp.reason}`;
    }

    return text ||
        'No follow-up appointment scheduled.';
}

function formatRecordTreatments(
    rawOdontogram
) {
    let odontogram =
        rawOdontogram;

    if (
        typeof odontogram ===
        'string'
    ) {
        try {
            odontogram =
                JSON.parse(
                    odontogram ||
                    '[]'
                );
        } catch (_) {
            odontogram = [];
        }
    }

    const entries =
        Array.isArray(
            odontogram
        ) ?
            odontogram :
            Object.values(
                odontogram || {}
            );

    const treatments = [];

    const addTreatment = (
        surface,
        record
    ) => {
        if (
            !record ||
            !record.code
        ) {
            return;
        }

        let code =
            String(
                record.code
            )
                .trim()
                .toUpperCase();

        if (
            code === 'PT' ||
            code === '+'
        ) {
            code = '✓';
        }

        const label =
            String(
                record.label ||
                code
            ).trim();

        const colorHex =
            String(
                record.colorHex ||
                record.color_hex ||
                ''
            ).trim();

        treatments.push({
            surface,
            code,
            label,
            colorHex,
        });
    };

    entries.forEach(entry => {
        const surfaceTreatments = [];

        if (entry?.status) {
            addTreatment(
                'Status',
                entry.status
            );
        }

        const surfaces =
            entry?.surfaces || {};

        [
            'top',
            'left',
            'center',
            'right',
            'bottom'
        ].forEach(surface => {

            const record =
                surfaces?.[
                surface
                ];

            if (!record) {
                return;
            }

            const surfaceLabel =
                surface
                    .charAt(0)
                    .toUpperCase() +
                surface.slice(1);

            addTreatment(
                surfaceLabel,
                record
            );

            surfaceTreatments.push({
                code: String(
                    record.code || ''
                )
                    .trim()
                    .toUpperCase(),
                label: String(
                    record.label ||
                    record.code ||
                    ''
                )
                    .trim()
                    .toLowerCase(),
            });
        });

        const threeDRecord =
            entry?.threeD ||
            entry?.three_d;

        const normalizedThreeDCode =
            String(
                threeDRecord?.code || ''
            )
                .trim()
                .toUpperCase();

        const normalizedThreeDLabel =
            String(
                threeDRecord?.label ||
                threeDRecord?.code ||
                ''
            )
                .trim()
                .toLowerCase();

        const hasMatchingSurfaceTreatment =
            !!threeDRecord &&
            surfaceTreatments.some(
                treatment =>
                    treatment.code ===
                    normalizedThreeDCode &&
                    treatment.label ===
                    normalizedThreeDLabel
            );

        const statusCode =
            String(
                entry?.status?.code || ''
            )
                .trim()
                .toUpperCase();

        const statusLabel =
            String(
                entry?.status?.label ||
                entry?.status?.code ||
                ''
            )
                .trim()
                .toLowerCase();

        const hasMatchingStatus =
            !!threeDRecord &&
            !!entry?.status &&
            statusCode === normalizedThreeDCode &&
            statusLabel === normalizedThreeDLabel;

        if (
            threeDRecord &&
            !hasMatchingSurfaceTreatment &&
            !hasMatchingStatus
        ) {
            addTreatment(
                '3D',
                threeDRecord
            );
        }
    });

    return treatments;
}

function safeRecordText(
    value
) {
    return String(
        value ?? ''
    )
        .replaceAll(
            '&',
            '&amp;'
        )
        .replaceAll(
            '<',
            '&lt;'
        )
        .replaceAll(
            '>',
            '&gt;'
        )
        .replaceAll(
            '"',
            '&quot;'
        )
        .replaceAll(
            "'",
            '&#039;'
        );
}

function renderRecordTreatments(
    containerId,
    treatments,
    fallbackText = ''
) {
    const container =
        document.getElementById(
            containerId
        );

    if (!container) {
        return;
    }

    if (
        !Array.isArray(
            treatments
        ) ||
        treatments.length === 0
    ) {
        container.textContent =
            String(
                fallbackText ||
                'No treatment record yet.'
            ).trim();

        return;
    }

    container.innerHTML =
        treatments
            .map(
                treatment => `
                    <span class="odontogram-preview-marking">
                        <i
                            class="odontogram-preview-marking-swatch"
                            style="background:${safeRecordText(
                    treatment.colorHex ||
                    '#111827'
                )}"
                        ></i>

                        <span>${safeRecordText(
                    treatment.surface
                )}: ${safeRecordText(
                    treatment.code
                )} - ${safeRecordText(
                    treatment.label
                )}</span>
                    </span>
                `
            )
            .join('');
}

function formatRecordRevisionRole(role) {
    const normalized =
        String(role || '')
            .trim()
            .replace(/_/g, ' ');

    if (!normalized) {
        return 'User';
    }

    return normalized.replace(
        /\b\w/g,
        letter => letter.toUpperCase()
    );
}

function formatRecordRevisionReason(reason) {
    const labels = {
        wrong_tooth: 'Wrong tooth',
        wrong_surface: 'Wrong surface',
        wrong_condition: 'Wrong condition / treatment',
        treatment_changed: 'Treatment changed',
        data_entry_error: 'Data entry error',
        other: 'Other'
    };

    return labels[
        String(reason || '')
            .trim()
            .toLowerCase()
    ] || 'Correction';
}

function formatRecordRevisionDate(value) {
    if (!value) {
        return 'Unknown date';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat(
        'en-PH',
        {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        }
    ).format(date);
}

function getRecordRevisionChanges(changes) {
    const items =
        Array.isArray(changes)
            ? changes
            : [];

    return items.filter(change => {
        if (change?.surface !== 'threeD') {
            return true;
        }

        return !items.some(other =>
            other !== change &&
            Number(other?.tooth) ===
            Number(change?.tooth) &&
            other?.surface !== 'threeD' &&
            (other?.old_code || null) ===
            (change?.old_code || null) &&
            (other?.new_code || null) ===
            (change?.new_code || null)
        );
    });
}

function formatRecordRevisionChange(change) {
    const surface =
        String(
            change?.surface || ''
        )
            .replace(
                'threeD',
                '3D'
            )
            .replace(
                /\b\w/g,
                letter =>
                    letter.toUpperCase()
            );

    const oldCode =
        change?.old_code ||
        'Healthy';

    const newCode =
        change?.new_code ||
        'Healthy';

    return `Tooth #${change?.tooth} · ${surface}: ${oldCode} → ${newCode}`;
}

function setRecordRevisionViewedVersion(
    modal,
    viewedVersion
) {
    if (!modal) {
        return;
    }

    const currentVersion =
        Number(
            modal.__recordCurrentVersion || 0
        );

    const activeVersion =
        Number(
            viewedVersion ||
            currentVersion
        );

    modal.__recordViewedVersion =
        activeVersion;

    modal
        .querySelectorAll(
            '[data-record-revision-row]'
        )
        .forEach(row => {

            const version =
                Number(
                    row.dataset
                        .recordRevisionVersion ||
                    0
                );

            const viewingBadge =
                row.querySelector(
                    '[data-record-revision-viewing]'
                );

            const viewButton =
                row.querySelector(
                    '[data-record-revision-view-button]'
                );

            const isViewingHistorical =
                version !== currentVersion &&
                version === activeVersion;

            if (viewingBadge) {
                viewingBadge.hidden =
                    !isViewingHistorical;
            }

            if (viewButton) {
                viewButton.hidden =
                    isViewingHistorical;
            }
        });
}

function setRecordHistoryExpanded(modal, expanded,) {
    if (!modal) {
        return;
    }

    const section =
        modal.querySelector(
            '#recordRevisionHistorySection'
        );

    if (!section) {
        return;
    }

    if (
        window.GlobalAccordion
            ?.setOpen
    ) {
        window.GlobalAccordion.setOpen(
            section,
            expanded
        );
    } else {
        section.open =
            expanded;
    }
}

function showRecordOdontogramSnapshot(
    modal,
    odontogramData,
    options = {}
) {
    if (!modal) {
        return;
    }

    const preview =
        modal.querySelector(
            '[data-odontogram-preview]'
        );

    if (!preview) {
        return;
    }

    const notice =
        modal.querySelector(
            '#recordHistoricalVersionNotice'
        );

    const noticeTitle =
        modal.querySelector(
            '#recordHistoricalVersionTitle'
        );

    const editBtn =
        modal.querySelector(
            '#recordModalEditBtn'
        );

    const editLockNotice =
        modal.querySelector(
            '#recordEditLockNotice'
        );

    const editLockMessage =
        modal.querySelector(
            '#recordEditLockMessage'
        );

    const odontogramSection =
        modal.querySelector(
            '#recordOdontogramSection'
        );

    const isHistorical =
        options.historical === true;

    const editUrl =
        String(
            modal.__recordData
                ?.editUrl || ''
        ).trim();

    const editLocked =
        !!editUrl &&
        modal.__recordData
            ?.editLocked === true;

    const editDisabled =
        isHistorical ||
        editLocked;

    if (editLockNotice) {
        editLockNotice.hidden =
            !editLocked;
    }

    if (
        editLocked &&
        editLockMessage
    ) {
        const deadline =
            modal.__recordData
                ?.editDeadline;

        editLockMessage.textContent =
            deadline
                ? `The 7-day editing period for this odontogram ended on ${formatRecordRevisionDate(deadline)}. This record is now read-only.`
                : (
                    modal.__recordData
                        ?.editLockReason ||
                    'The 7-day editing period for this odontogram has expired. This record is now read-only.'
                );
    }

    const version =
        Number(
            options.version || 0
        );

    setRecordRevisionViewedVersion(
        modal,
        isHistorical
            ? version
            : (
                modal.__recordCurrentVersion ||
                version
            )
    );

    if (editBtn) {

        editBtn.hidden =
            !editUrl;

        const editBtnIcon =
            editBtn.querySelector('i');

        const editBtnText =
            editBtn.querySelector('span');

        if (editLocked) {
            if (editBtnIcon) {
                editBtnIcon.className =
                    'fa-solid fa-lock';
            }

            if (editBtnText) {
                editBtnText.textContent =
                    'Editing Locked';
            }
        } else {
            if (editBtnIcon) {
                editBtnIcon.className =
                    'fa-solid fa-pen-to-square';
            }

            if (editBtnText) {
                editBtnText.textContent =
                    'Edit Odontogram';
            }
        }

        if (
            editUrl &&
            !editDisabled
        ) {
            editBtn.href =
                editUrl;

            editBtn.removeAttribute(
                'tabindex'
            );
        } else {
            editBtn.removeAttribute(
                'href'
            );

            editBtn.setAttribute(
                'tabindex',
                '-1'
            );
        }

        editBtn.setAttribute(
            'aria-disabled',
            String(editDisabled)
        );

        editBtn.title =
            isHistorical
                ? 'Return to the current version to edit.'
                : editLocked
                    ? (
                        modal.__recordData
                            ?.editLockReason ||
                        'The editing period for this odontogram has expired.'
                    )
                    : 'Edit odontogram';

        editBtn.classList.toggle(
            'pointer-events-none',
            editDisabled
        );

        editBtn.classList.toggle(
            'opacity-60',
            editDisabled
        );
    }

    if (notice) {
        notice.hidden =
            !isHistorical;
    }

    if (
        isHistorical &&
        noticeTitle
    ) {
        noticeTitle.textContent =
            `Viewing version ${version}`;
    }

    window.requestAnimationFrame(
        () => {
            setOdontogramPreviewData(
                preview,
                odontogramData || [],
                {
                    animateSurfaceChanges:
                        options
                            .animateSurfaceChanges ===
                        true
                }
            );

            if (
                options.scrollIntoView &&
                odontogramSection
            ) {
                odontogramSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    );
}

function renderRecordRevisionHistory(
    modal,
    data
) {
    if (!modal) {
        return;
    }

    const revisions =
        Array.isArray(data?.revisions)
            ? [...data.revisions]
            : [];

    revisions.sort(
        (a, b) =>
            Number(
                b?.version_number || 0
            ) -
            Number(
                a?.version_number || 0
            )
    );

    modal.__recordRevisions =
        revisions;

    const currentVersion =
        Number(
            data?.currentVersion ||
            revisions[0]?.version_number ||
            0
        );

    modal.__recordCurrentVersion =
        currentVersion;

    modal
        .querySelectorAll(
            '[data-record-latest-version]'
        )
        .forEach(label => {
            label.textContent =
                `v${currentVersion}`;
        });

    const list =
        modal.querySelector(
            '#recordRevisionHistoryList'
        );

    const historySection =
        modal.querySelector(
            '#recordRevisionHistorySection'
        );

    const countBadge =
        modal.querySelector(
            '#recordRevisionCountBadge'
        );

    const editedBadge =
        modal.querySelector(
            '#recordEditedBadge'
        );

    const editedVersion =
        modal.querySelector(
            '#recordEditedVersion'
        );

    const viewHistoryButton =
        modal.querySelector(
            '#recordViewHistoryBtn'
        );

    const viewHistoryCount =
        modal.querySelector(
            '#recordViewHistoryCount'
        );

    const showMoreRoot =
        modal.querySelector(
            '#recordRevisionHistoryContent'
        );

    const hasEdits =
        revisions.length > 1 &&
        currentVersion > 1;

    if (historySection) {
        historySection.hidden =
            !hasEdits;
    }

    if (editedBadge) {
        editedBadge.hidden =
            !hasEdits;
    }

    if (editedVersion) {
        editedVersion.textContent =
            `v${currentVersion}`;
    }

    if (viewHistoryButton) {
        viewHistoryButton.hidden =
            !hasEdits;
    }

    if (viewHistoryCount) {
        viewHistoryCount.textContent =
            String(revisions.length);
    }

    if (countBadge) {
        countBadge.textContent =
            `${revisions.length} version${revisions.length === 1
                ? ''
                : 's'
            }`;
    }

    if (!list) {
        return;
    }

    list.replaceChildren();

    if (!hasEdits) {
        window.ShowMore?.reset(
            showMoreRoot
        );

        return;
    }

    revisions.forEach(
        revision => {
            const version =
                Number(
                    revision?.version_number || 0
                );

            const isCurrent =
                version === currentVersion;

            const isInitial =
                version === 1;

            const changes =
                getRecordRevisionChanges(
                    revision?.changes
                );

            const notes =
                String(
                    revision?.reason_notes || ''
                ).trim();

            const reasonDetail =
                String(
                    revision?.reason_detail || ''
                ).trim();

            const showNotes =
                notes &&
                !(
                    isInitial &&
                    notes.toLowerCase() ===
                    'initial record'
                );

            const row =
                document.createElement(
                    'div'
                );

            row.className =
                'table-list-row relative px-5 py-5';

            row.setAttribute(
                'data-show-more-item',
                ''
            );

            row.setAttribute(
                'data-record-revision-row',
                ''
            );

            row.dataset.recordRevisionVersion =
                String(version);

            const timeline =
                document.createElement(
                    'div'
                );

            timeline.className =
                'relative pl-7';


            const timelineLine =
                document.createElement(
                    'span'
                );

            timelineLine.className =
                'absolute left-2 top-0 bottom-0 border-l border-[var(--border)]';


            const timelineDot =
                document.createElement(
                    'span'
                );

            timelineDot.className =
                isCurrent
                    ? 'table-dot table-dot-success absolute left-[5px] top-[7px]'
                    : 'table-dot table-dot-muted absolute left-[5px] top-[7px]';


            timeline.append(
                timelineLine,
                timelineDot
            );

            const content =
                document.createElement(
                    'div'
                );

            content.className =
                'min-w-0';

            const header =
                document.createElement(
                    'div'
                );

            header.className =
                'global-label-row';


            const headerMain =
                document.createElement(
                    'div'
                );

            headerMain.className =
                'global-label-main';


            const versionTitle =
                document.createElement(
                    'strong'
                );

            versionTitle.className =
                'record-summary-value';

            versionTitle.textContent =
                isInitial
                    ? `v${version} · Initial record`
                    : `v${version}`;


            const versionGroup =
                document.createElement(
                    'div'
                );

            versionGroup.className =
                'global-info-group';

            versionGroup.appendChild(
                versionTitle
            );

            if (isCurrent) {

                const currentBadge =
                    document.createElement(
                        'span'
                    );

                currentBadge.className =
                    'status-pill status-completed';

                currentBadge.textContent =
                    'Current';

                versionGroup.appendChild(
                    currentBadge
                );

            } else {

                const viewingBadge =
                    document.createElement(
                        'span'
                    );

                viewingBadge.className =
                    'status-pill status-cancelled';

                viewingBadge.textContent =
                    'Viewing';

                viewingBadge.hidden =
                    true;

                viewingBadge.setAttribute(
                    'data-record-revision-viewing',
                    ''
                );

                versionGroup.appendChild(
                    viewingBadge
                );
            }

            headerMain.appendChild(
                versionGroup
            );

            header.appendChild(
                headerMain
            );

            if (!isCurrent) {
                const actions =
                    document.createElement(
                        'div'
                    );

                actions.className =
                    'global-label-meta';


                const viewButton =
                    document.createElement(
                        'button'
                    );

                viewButton.type =
                    'button';

                viewButton.setAttribute(
                    'data-record-revision-view-button',
                    ''
                );

                viewButton.className =
                    'ui-btn ui-btn-secondary ui-btn-sm';

                viewButton.innerHTML = `
                <i class="fa-regular fa-eye"></i>
                <span>View Version</span>
            `;

                viewButton.addEventListener(
                    'click',
                    () => {
                        showRecordOdontogramSnapshot(
                            modal,
                            revision?.odontogram_data || [],
                            {
                                version,

                                historical: true,

                                scrollIntoView: true,

                                animateSurfaceChanges: true
                            }
                        );
                    }
                );

                actions.appendChild(
                    viewButton
                );

                header.appendChild(
                    actions
                );
            }

            content.appendChild(
                header
            );

            const meta =
                document.createElement(
                    'div'
                );

            meta.className =
                'record-modal-meta mt-2';

            const actor =
                document.createElement(
                    'span'
                );

            actor.innerHTML = `
            <i class="fa-solid fa-user-doctor"></i>

            ${safeRecordText(
                revision?.changed_by_name ||
                'Unknown user'
            )}

            (${safeRecordText(
                formatRecordRevisionRole(
                    revision?.changed_by_role
                )
            )})
        `;

            const date =
                document.createElement(
                    'span'
                );

            date.innerHTML = `
            <i class="fa-regular fa-clock"></i>

            ${safeRecordText(
                formatRecordRevisionDate(
                    revision?.created_at
                )
            )}
        `;

            meta.append(
                actor,
                date
            );

            content.appendChild(
                meta
            );

            if (!isInitial) {
                const correctionDetails =
                    document.createElement(
                        'div'
                    );

                correctionDetails.className =
                    'mt-4 flex flex-col gap-3';

                const reasonBlock =
                    document.createElement(
                        'div'
                    );


                const reasonLabel =
                    document.createElement(
                        'span'
                    );

                reasonLabel.className =
                    'global-form-label';

                reasonLabel.textContent =
                    'Correction Reason';


                const reasonBadge =
                    document.createElement(
                        'span'
                    );

                reasonBadge.className =
                    'status-pill status-default';

                reasonBadge.textContent =
                    revision?.reason_type_label ||
                    formatRecordRevisionReason(
                        revision?.reason_type_code
                    );

                reasonBlock.append(
                    reasonLabel,
                    reasonBadge
                );

                correctionDetails.appendChild(
                    reasonBlock
                );

                if (reasonDetail) {
                    const detailCard =
                        document.createElement('div');

                    detailCard.className =
                        'info-card';

                    const detailLabel =
                        document.createElement('span');

                    detailLabel.className =
                        'global-form-label';

                    detailLabel.textContent =
                        'Specified Reason';

                    const detailText =
                        document.createElement('p');

                    detailText.className =
                        'modal-helper-text';

                    detailText.textContent =
                        reasonDetail;

                    detailCard.append(
                        detailLabel,
                        detailText
                    );

                    correctionDetails.appendChild(
                        detailCard
                    );
                }

                if (showNotes) {
                    const noteCard =
                        document.createElement(
                            'div'
                        );

                    noteCard.className =
                        'info-card';

                    const noteLabel =
                        document.createElement(
                            'span'
                        );

                    noteLabel.className =
                        'global-form-label';

                    noteLabel.textContent =
                        'Correction Note';

                    const note =
                        document.createElement(
                            'p'
                        );

                    note.className =
                        'modal-helper-text';

                    note.textContent =
                        notes;


                    noteCard.append(
                        noteLabel,
                        note
                    );

                    correctionDetails.appendChild(
                        noteCard
                    );
                }

                if (changes.length) {
                    const changesBlock =
                        document.createElement(
                            'div'
                        );

                    const changesLabel =
                        document.createElement(
                            'span'
                        );

                    changesLabel.className =
                        'global-form-label';

                    changesLabel.textContent =
                        changes.length === 1
                            ? 'Changed Tooth'
                            : 'Changed Teeth';

                    const changesList =
                        document.createElement(
                            'div'
                        );

                    changesList.className =
                        'flex flex-wrap gap-2 mt-2';

                    changes.forEach(
                        change => {
                            const changePill =
                                document.createElement(
                                    'div'
                                );

                            changePill.className =
                                'global-info-pill';

                            changePill.textContent =
                                formatRecordRevisionChange(
                                    change
                                );

                            changesList.appendChild(
                                changePill
                            );
                        }
                    );

                    changesBlock.append(
                        changesLabel,
                        changesList
                    );

                    correctionDetails.appendChild(
                        changesBlock
                    );
                }

                content.appendChild(
                    correctionDetails
                );
            }

            timeline.appendChild(
                content
            );

            row.appendChild(
                timeline
            );

            list.appendChild(
                row
            );
        }
    );

    setRecordRevisionViewedVersion(
        modal,
        currentVersion
    );

    window.ShowMore?.reset(
        showMoreRoot
    );
}

function setRecordModalData(
    source,
    alreadyNormalized = false
) {
    const data =
        alreadyNormalized ?
            source :
            normalizeRecordData(
                source || {}
            );

    setText('m_service', data.service);
    setText('m_date', data.date);
    setText('m_time', formatRecordTime(data.time));
    const normalizedRecordStatus = String(
        data.status || ''
    )
        .trim()
        .toLowerCase();

    const isCancelledRecord =
        normalizedRecordStatus === 'cancelled' ||
        normalizedRecordStatus === 'canceled';

    setText(
        'm_duration',
        isCancelledRecord ?
            '—' :
            (
                data.durationSeconds !== null &&
                    data.durationSeconds !== undefined ?
                    formatRecordDurationSeconds(
                        data.durationSeconds
                    ) :
                    formatRecordDuration(
                        data.duration
                    )
            )
    );

    var status = normalizedRecordStatus;
    var sEl = document.getElementById('m_status');

    if (sEl) {
        const normalizedStatus =
            status === 'canceled' ?
                'cancelled' :
                status.split(' - ')[0];

        sEl.className =
            'status-pill status-' +
            (
                [
                    'completed',
                    'cancelled',
                    'rescheduled',
                    'upcoming',
                    'pending'
                ].includes(normalizedStatus) ?
                    normalizedStatus :
                    'default'
            );

        sEl.innerHTML = `
        <span class="status-dot"></span>
        ${formatRecordStatus(status)}
    `;
    }

    const treatments =
        Array.isArray(
            data.treatmentItems
        ) &&
            data.treatmentItems.length
            ? data.treatmentItems
            : formatRecordTreatments(
                data.odontogramData
            );

    renderRecordTreatments(
        'm_remarks',
        treatments,
        data.remarks ||
        'No treatment record yet.'
    );

    setText(
        'm_oral',
        data.oral ||
        'No oral examination record yet.'
    );

    setText(
        'm_diagnosis',
        data.diagnosis ||
        'No diagnosis record yet.'
    );

    setText(
        'm_prescription',
        data.prescription ||
        'No prescription recorded.'
    );

    setText(
        'm_follow_up',
        formatRecordFollowUp(data.followUp)
    );

    const editBtn =
        document.getElementById(
            'recordModalEditBtn'
        );

    if (editBtn) {
        const editUrl =
            String(
                data.editUrl || ''
            ).trim();

        editBtn.hidden =
            !editUrl;
    }
}

export async function openRecordModal(
    source
) {
    initRecordModal();

    const modal =
        document.getElementById(
            'record_modal'
        );

    if (!modal) {
        return;
    }

    const recordData =
        normalizeRecordData(
            source || {}
        );

    modal.__recordData =
        recordData;

    setRecordModalData(
        recordData,
        true
    );

    renderRecordRevisionHistory(
        modal,
        recordData
    );

    setRecordHistoryExpanded(
        modal,
        false
    );

    window.openModal?.(
        'record_modal'
    );

    showRecordOdontogramSnapshot(
        modal,
        recordData.odontogramData ||
        [],
        {
            version:
                recordData
                    .currentVersion ||
                0,

            historical: false
        }
    );
}

export function closeRecordModal() {
    window.closeModal?.(
        'record_modal'
    );
}

function initRecordModal() {
    const modal =
        document.getElementById(
            'record_modal'
        );

    if (
        !modal ||
        modal.dataset.initialized ===
        'true'
    ) {
        return;
    }

    modal.dataset.initialized =
        'true';

    const closeBtn =
        modal.querySelector(
            '#modalCloseBtn'
        );

    const closeFooter =
        modal.querySelector(
            '#modalCloseFooter'
        );

    const viewHistoryBtn =
        modal.querySelector(
            '#recordViewHistoryBtn'
        );

    const backToCurrentBtn =
        modal.querySelector(
            '#recordBackToCurrentBtn'
        );

    const card =
        modal.querySelector(
            '.ui-modal-card'
        );

    closeBtn?.addEventListener(
        'click',
        closeRecordModal
    );

    closeFooter?.addEventListener(
        'click',
        closeRecordModal
    );

    viewHistoryBtn?.addEventListener(
        'click',
        () => {
            setRecordHistoryExpanded(
                modal,
                true
            );
        }
    );

    backToCurrentBtn?.addEventListener(
        'click',
        () => {
            const data =
                modal.__recordData;

            showRecordOdontogramSnapshot(
                modal,
                data?.odontogramData || [],
                {
                    version:
                        data?.currentVersion || 0,

                    historical: false,

                    animateSurfaceChanges: true
                }
            );
        }
    );

    modal.addEventListener(
        'click',
        event => {
            if (
                card &&
                !card.contains(
                    event.target
                )
            ) {
                closeRecordModal();
            }
        }
    );
}
