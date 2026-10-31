// Explicit handlers migrated from trusted Blade templates. Never evaluate code from DOM attributes.
import { readActionArg } from './action-values';

export const actionHandlers = Object.freeze({
    // resources/views/admin/document-template.blade.php
    'action-cb61e864b11d': function (event) {
        closeTemplateFilterDrawer()
    },
    // resources/views/admin/document-template.blade.php
    'action-9525fd19c356': function (event) {
        resetTemplateFilters()
    },
    // resources/views/admin/document-template.blade.php
    'action-0efe3e880025': function (event) {
        closePreviewModal()
    },
    // resources/views/admin/document-template.blade.php
    'action-7286019d3d89': function (event) {
        event.stopPropagation(); window.handleTemplateActionClick(this)
    },
    // resources/views/admin/document-template.blade.php
    'action-cc1085fad7b8': function (event) {
        event.stopPropagation(); openTemplatePreview(readActionArg(this, 'click', 0, true))
    },
    // resources/views/admin/document-template.blade.php
    'action-27fedc7b9d1d': function (event) {
        openTemplatePreview(readActionArg(this, 'click', 0, true))
    },
    // resources/views/admin/document-template.blade.php
    'action-4faee489e166': function (event) {
        openTemplateFilterDrawer()
    },
    // resources/views/admin/patient-directory.blade.php
    'action-36814e8a29cf': function (event) {
        window.location.href = '/patient/record'
    },
    // resources/views/admin/patient-directory.blade.php
    'action-d58a9c6e6bde': function (event) {
        window.location.href = '/patient/dashboard'
    },
    // resources/views/admin/patient-directory.blade.php
    'action-297be29dcc37': function (event) {
        filterPatients()
    },
    // resources/views/admin/reports-ai-generated.blade.php
    'action-22499e15f3ee': function (event) {
        event.stopPropagation()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-6acb9f0eb412': function (event) {
        closeDeleteModal()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-12b6df6f3044': function (event) {
        startPatientImpersonation('patient', 'patient', '#059669', readActionArg(this, 'click', 0, true), readActionArg(this, 'click', 1, false))
    },
    // resources/views/admin/role-permissions.blade.php
    'action-d74329a746f8': function (event) {
        closePatientPicker()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-8a5c973b9806': function (event) {
        closeViewAs()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-3fbd142ad2bd': function (event) {
        confirmResetDefaults()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-394b0354d213': function (event) {
        closeResetConfirm()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-e15c3c78bc8e': function (event) {
        ajaxSaveRole(readActionArg(this, 'click', 0, false))
    },
    // resources/views/admin/role-permissions.blade.php
    'action-01fe488d50f6': function (event) {
        requestDiscardRoleChanges(readActionArg(this, 'click', 0, false))
    },
    // resources/views/admin/role-permissions.blade.php
    'action-e9aac4adc59f': function (event) {
        openViewAs()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-f71c950e5fcd': function (event) {
        onPermChange(this)
    },
    // resources/views/admin/role-permissions.blade.php
    'action-76cbb6258bf9': function (event) {
        onGroupMasterChange(this)
    },
    // resources/views/admin/role-permissions.blade.php
    'action-2b6cb68857e5': function (event) {
        event.stopPropagation();
    },
    // resources/views/admin/role-permissions.blade.php
    'action-47ae060a446f': function (event) {
        togglePermGroup(this)
    },
    // resources/views/admin/role-permissions.blade.php
    'action-93a40e63c926': function (event) {
        ajaxResetDefaults()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-b1ec55a9368e': function (event) {
        toggleAllGroups()
    },
    // resources/views/admin/role-permissions.blade.php
    'action-4befe37bff71': function (event) {
        event.stopPropagation(); openDeleteModal(
            readActionArg(this, 'click', 0, false),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/admin/role-permissions.blade.php
    'action-eee17b73e521': function (event) {
        selectRole(this)
    },
    // resources/views/admin/role-permissions.blade.php
    'action-39518fccd42a': function (event) {
        openNewRoleModal()
    },
    // resources/views/admin/session-management.blade.php
    'action-44daeba3893a': function (event) {
        return false;
    },
    // resources/views/admin/system-logs.blade.php
    'action-463079bd5291': function (event) {
        closeSlFilterPanel()
    },
    // resources/views/admin/system-logs.blade.php
    'action-1e0663440b55': function (event) {
        clearSlFilterPanelDraft()
    },
    // resources/views/admin/system-logs.blade.php
    'action-c059940c46c7': function (event) {
        applySlFilters()
    },
    // resources/views/admin/system-logs.blade.php
    'action-6a1c2afb8e04': function (event) {
        submitSlExportModal()
    },
    // resources/views/admin/system-logs.blade.php
    'action-215d4806d34c': function (event) {
        submitSlArchiveModal()
    },
    // resources/views/admin/system-logs.blade.php
    'action-673f2e1d1880': function (event) {
        setSlQuickDate('month')
    },
    // resources/views/admin/system-logs.blade.php
    'action-c457a107a6f8': function (event) {
        setSlQuickDate('week')
    },
    // resources/views/admin/system-logs.blade.php
    'action-ae64a4dd44f5': function (event) {
        setSlQuickDate('today')
    },
    // resources/views/admin/system-logs.blade.php
    'action-9294a71d2860': function (event) {
        closeSlOverflowMenu(); handleSlExportButtonClick()
    },
    // resources/views/admin/system-logs.blade.php
    'action-478b7a7c7da5': function (event) {
        closeSlOverflowMenu(); openSlArchiveModal()
    },
    // resources/views/admin/system-logs.blade.php
    'action-d8c154b64c66': function (event) {
        toggleSlOverflowMenu(event)
    },
    // resources/views/admin/system-logs.blade.php
    'action-2f4e0d8e81f5': function (event) {
        clearOnlySlFilters()
    },
    // resources/views/admin/system-logs.blade.php
    'action-23adcae5c050': function (event) {
        openSlFilterPanel()
    },
    // resources/views/admin/system-settings.blade.php
    'action-57cd63974b29': function (event) {
        submitSettingsForm();
    },
    // resources/views/admin/system-settings.blade.php
    'action-22c2e1201b39': function (event) {
        discardSettingsChanges()
    },
    // resources/views/admin/system-settings.blade.php
    'action-96e8179179ff': function (event) {
        resetSettingsToDefaults()
    },
    // resources/views/admin/system-settings.blade.php
    'action-5a9e853d1e90': function (event) {
        switchTab('integrations', this); return false;
    },
    // resources/views/admin/system-settings.blade.php
    'action-bb4a13913035': function (event) {
        switchTab('email', this); return false;
    },
    // resources/views/admin/system-settings.blade.php
    'action-e48f4aaf6db9': function (event) {
        switchTab('retention', this); return false;
    },
    // resources/views/admin/system-settings.blade.php
    'action-3360e45d2f7f': function (event) {
        switchTab('notifications', this); return false;
    },
    // resources/views/admin/system-settings.blade.php
    'action-aba23a6c84ff': function (event) {
        switchTab('clinic', this); return false;
    },
    // resources/views/admin/system-settings.blade.php
    'action-524cbe50222f': function (event) {
        switchTab('general', this); return false;
    },
    // resources/views/admin/user-management.blade.php
    'action-ff8f8b5ad104': function (event) {
        openViewModalFromButton(this)
    },
    // resources/views/admin/user-management.blade.php
    'action-585011303a01': function (event) {
        openResetModal(
            'users',
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/admin/user-management.blade.php
    'action-1818b87d7b42': function (event) {
        openToggleConfirm(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true),
            readActionArg(this, 'click', 2, true)
        )
    },
    // resources/views/admin/user-management.blade.php
    'action-300bbde9a403': function (event) {
        openEditModalFromButton(
            this,
            'users',
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true),
            readActionArg(this, 'click', 2, true),
            readActionArg(this, 'click', 3, true),
            readActionArg(this, 'click', 4, true)
        )
    },
    // resources/views/admin/user-management.blade.php
    'action-5630b39be24d': function (event) {
        closeModal('toggleConfirmModal')
    },
    // resources/views/admin/user-management.blade.php
    'action-21e03f3cb038': function (event) {
        closeModal('viewModal')
    },
    // resources/views/admin/user-management.blade.php
    'action-7a64b8647525': function (event) {
        togglePassVis('resetPasswordConf', 'resetEye2')
    },
    // resources/views/admin/user-management.blade.php
    'action-48a2e7efa309': function (event) {
        togglePassVis('resetPassword', 'resetEye')
    },
    // resources/views/admin/user-management.blade.php
    'action-3f17fa1e4fa6': function (event) {
        togglePassVis(
            'editAdminCurrentPassword',
            'editAdminPasswordEye'
        )
    },
    // resources/views/admin/user-management.blade.php
    'action-a3dc03fc8a29': function (event) {
        togglePassVis('addPasswordConf', 'addEye2')
    },
    // resources/views/admin/user-management.blade.php
    'action-350e508970e2': function (event) {
        copyFieldValue('addPassword')
    },
    // resources/views/admin/user-management.blade.php
    'action-c88265410365': function (event) {
        refreshGeneratedPassword()
    },
    // resources/views/admin/user-management.blade.php
    'action-9a066951b17a': function (event) {
        togglePassVis('addPassword', 'addEye')
    },
    // resources/views/admin/user-management.blade.php
    'action-202095daf052': function (event) {
        openResetModal('users', readActionArg(this, 'click', 0, true), readActionArg(this, 'click', 1, true))
    },
    // resources/views/admin/user-management.blade.php
    'action-43aabb4c9c5a': function (event) {
        openToggleConfirm(readActionArg(this, 'click', 0, true), readActionArg(this, 'click', 1, true), readActionArg(this, 'click', 2, true))
    },
    // resources/views/admin/user-management.blade.php
    'action-0c508fb9e874': function (event) {
        openEditModalFromButton(this, 'users', readActionArg(this, 'click', 0, true), readActionArg(this, 'click', 1, true), readActionArg(this, 'click', 2, true), readActionArg(this, 'click', 3, true), readActionArg(this, 'click', 4, true))
    },
    // resources/views/admin/user-management.blade.php
    'action-98b3dc94d815': function (event) {
        openModal('addModal', this)
    },
    // resources/views/auth/login.blade.php
    'action-8551a98bacd1': function (event) {
        this.src = readActionArg(this, 'error', 0, false)
    },
    // resources/views/auth/login.blade.php
    'action-9f9589849531': function (event) {
        toggleFaq(this)
    },
    // resources/views/auth/login.blade.php
    'action-01ea3b1981b5': function (event) {
        this.src = 'https://ui-avatars.com/api/?name=Nelson+Angeles&background=660000&color=FFFFFF&size=500'
    },
    // resources/views/auth/login.blade.php
    'action-bc43f465120b': function (event) {
        closeMobileMenu()
    },
    // resources/views/auth/login.blade.php
    'action-a21abd208743': function (event) {
        toggleMobileMenu()
    },
    // resources/views/auth/register.blade.php
    'action-6f467bb30429': function (event) {
        dismissToast(this.parentElement)
    },
    // resources/views/auth/register.blade.php
    'action-5817faec28d5': function (event) {
        togglePassword('confirmPassword', 'eyeConfirmPassword')
    },
    // resources/views/auth/register.blade.php
    'action-84cd2f54c0c6': function (event) {
        togglePassword('password', 'eyePassword')
    },
    // resources/views/components/appointment-record-card.blade.php
    'action-31134d4ab8af': function (event) {
        openRecordModal(
            JSON.parse(this.dataset.record)
        )
    },
    // resources/views/components/cancel-modal.blade.php
    'action-2428055b77ee': function (event) {
        confirmCancelAppointment()
    },
    // resources/views/components/cancel-modal.blade.php
    'action-021a85adb43d': function (event) {
        closeCancelAppointmentModal()
    },
    // resources/views/components/cancel-modal.blade.php
    'action-a8a78f310041': function (event) {
        handleCancelBackdropClick(event)
    },
    // resources/views/components/patient-document-modals.blade.php
    'action-2b8f389ffdda': function (event) {
        closeDocSuccessModal()
    },
    // resources/views/components/patient-document-modals.blade.php
    'action-2bb633a6f172': function (event) {
        closeDocModal('dentalHealthRecordModal')
    },
    // resources/views/components/patient-document-modals.blade.php
    'action-3321670b58fc': function (event) {
        closeDocModal('dentalClearanceModal')
    },
    // resources/views/components/reschedule-modal.blade.php
    'action-bfa80e93ba29': function (event) {
        clearRescheduleSlotSelection()
    },
    // resources/views/components/reschedule-modal.blade.php
    'action-2c27020b29c8': function (event) {
        handleRescheduleBackdropClick(event)
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-03ef2b7a6a82': function (event) {
        closeDailyFilterPanel()
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-cb96a9d9ae02': function (event) {
        clearDailyFilterPanelDraft()
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-085002056d44': function (event) {
        applyDailyFilters()
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-61e482642188': function (event) {
        removeDailyDraftChip(readActionArg(this, 'click', 0, false))
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-5c1f2fa75efb': function (event) {
        clearDailyFilters()
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-927eb715f561': function (event) {
        closeDownloadModal()
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-a9193769e801': function (event) {
        closeModalOnBackdrop(event, 'downloadCompleteModal')
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-9203578feefb': function (event) {
        openDailyFilterPanel()
    },
    // resources/views/dentist/daily-treatment.blade.php
    'action-dd409da78ef1': function (event) {
        openDailyCreateReportModal()
    },
    // resources/views/dentist/dental-services.blade.php
    'action-f5118f90860f': function (event) {
        closeDentalFilterModal()
    },
    // resources/views/dentist/dental-services.blade.php
    'action-70c63ba973e1': function (event) {
        applyDentalFiltersFromDrawer()
    },
    // resources/views/dentist/dental-services.blade.php
    'action-c0d7a614b8c1': function (event) {
        resetDentalFilters()
    },
    // resources/views/dentist/dental-services.blade.php
    'action-dafccddeeb94': function (event) {
        openDentalFilterModal()
    },
    // resources/views/dentist/dental-services.blade.php
    'action-9d8b6b4ff613': function (event) {
        openDentalCreateReportModal()
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-69de2f9aeac1': function (event) {
        cancelAppointmentFromModal(
            readActionArg(this, 'click', 0, false),
            readActionArg(this, 'click', 1, false),
            readActionArg(this, 'click', 2, false)
        )
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-c90ced68deff': function (event) {
        openRescheduleModalFromDay(
            readActionArg(this, 'click', 0, false),
            readActionArg(this, 'click', 1, false),
            readActionArg(this, 'click', 2, false),
            readActionArg(this, 'click', 3, false),
            readActionArg(this, 'click', 4, false)
        )
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-3f1275e108e2': function (event) {
        closeDayAppointmentsModal()
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-74913bcfd525': function (event) {
        event.preventDefault();
        event.stopPropagation();
        cancelAppointmentFromModal(
            readActionArg(this, 'click', 0, false),
            readActionArg(this, 'click', 1, false),
            readActionArg(this, 'click', 2, false)
        );
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-9ac3a192f5ae': function (event) {
        event.preventDefault();
        event.stopPropagation();
        openRescheduleModalFromDay(
            readActionArg(this, 'click', 0, false),
            readActionArg(this, 'click', 1, false),
            readActionArg(this, 'click', 2, false),
            readActionArg(this, 'click', 3, false),
            readActionArg(this, 'click', 4, false)
        );
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-bf29fd6dc371': function (event) {
        openStatusModal()
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-e972125b107b': function (event) {
        changeUpcomingAppointmentWindow(7)
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-98407bd7ef94': function (event) {
        changeUpcomingAppointmentWindow(-7)
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-f32ebdbeb64d': function (event) {
        goToUpcomingToday()
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-a0d942dab0f1': function (event) {
        renderUpcomingAppointmentsDay(readActionArg(this, 'click', 0, false))
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-96d2131d8000': function (event) {
        confirmStatus()
    },
    // resources/views/dentist/dentist-dashboard.blade.php
    'action-62892930edb7': function (event) {
        closeStatusModal()
    },
    // resources/views/dentist/dentist-walk-in.blade.php
    'action-0bcc7b50f274': function (event) {
        const action = readActionArg(
            this,
            'click',
            0,
            false
        );

        switch (action) {
            case 'editDentalHistoryFromReview()':
                window.editDentalHistoryFromReview?.();
                break;

            case 'editMedicalHistoryFromReview()':
                window.editMedicalHistoryFromReview?.();
                break;

            case 'editSignatureFromReview()':
                window.editSignatureFromReview?.();
                break;

            case 'editContactInformationFromReview()':
                window.editContactInformationFromReview?.();
                break;

            default:
                console.warn(
                    'Unknown booking review action:',
                    action
                );
        }
    },
    // resources/views/errors/layout.blade.php
    'action-1f0252ba48f2': function (event) {
        history.back()
    },
    // resources/views/partials/chatbot.blade.php
    'action-9469f0eb28d2': function (event) {
        window.location.href = readActionArg(this, 'click', 0, false)
    },
    // resources/views/partials/chatbot.blade.php
    'action-6ad3825239b7': function (event) {
        sendQuickMessage(readActionArg(this, 'click', 0, false))
    },
    // resources/views/partials/chatbot.blade.php
    'action-75963eb3acd5': function (event) {
        sendMessage()
    },
    // resources/views/partials/chatbot.blade.php
    'action-4f7aad6a7b70': function (event) {
        sendQuickMessage('Where can I request a dental clearance document?')
    },
    // resources/views/partials/chatbot.blade.php
    'action-9aa4327f110d': function (event) {
        sendQuickMessage('How do I book an appointment from the patient dashboard?')
    },
    // resources/views/partials/chatbot.blade.php
    'action-cfe2cb05f09d': function (event) {
        sendQuickMessage('Where can I view my odontogram in the Dental Records page?')
    },
    // resources/views/partials/chatbot.blade.php
    'action-0d26e7107f0a': function (event) {
        hideChatWarning()
    },
    // resources/views/partials/chatbot.blade.php
    'action-a1629b68d2fa': function (event) {
        toggleChat()
    },
    // resources/views/partials/chatbot.blade.php
    'action-257d14926763': function (event) {
        toggleAccessibilityFromMain()
    },
    // resources/views/partials/chatbot.blade.php
    'action-15220cbe0457': function (event) {
        closeAssistiveMenu(); toggleChat();
    },
    // resources/views/partials/chatbot.blade.php
    'action-907ec5cc753e': function (event) {
        toggleAssistiveMenu()
    },
    // resources/views/partials/impersonation-banner.blade.php
    'action-004507b18ecc': function (event) {
        window.returnToAdminPreview?.();
    },
    // resources/views/patient/appointment.blade.php
    'action-265f97b3419b': function (event) {
        openViewToothModal(readActionArg(this, 'click', 0, true))
    },
    // resources/views/patient/appointment.blade.php
    'action-2600bc4012b8': function (event) {
        apptShowFuture()
    },
    // resources/views/patient/appointment.blade.php
    'action-8d572863bc05': function (event) {
        scrollToAppointmentCalendar()
    },
    // resources/views/patient/appointment.blade.php
    'action-15d0e1e6763b': function (event) {
        apptShowPast()
    },
    // resources/views/patient/appointment.blade.php
    'action-3581bd1ec7b5': function (event) {
        apptShowPast(); document.getElementById('apptPastPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    // resources/views/patient/appointment.blade.php
    'action-9eb7d8dc0629': function (event) {
        handleScheduleCheckup()
    },
    // resources/views/patient/book-appointment.blade.php
    'action-ef7c4934d238': function (event) {
        cancelContactInformationEdit()
    },
    // resources/views/patient/book-appointment.blade.php
    'action-9247f8a4a5d1': function (event) {
        saveGlobalContactInformation()
    },
    // resources/views/patient/index.blade.php
    'action-d64bd6053e99': function (event) {
        handlePrivateInformationToggle(this)
    },
    // resources/views/patient/index.blade.php
    'action-1e919ba532ca': function (event) {
        scrollToCalendar(event)
    },
    // resources/views/patient/index.blade.php
    'action-573f9113de2d': function (event) {
        confirmShowPrivateInformation()
    },
    // resources/views/patient/index.blade.php
    'action-a2822b6ec471': function (event) {
        closePrivateInformationModal()
    },
    // resources/views/patient/index.blade.php
    'action-fa43d7668e58': function (event) {
        if (
            window.UPCOMING_DATA?.exists &&
            ['upcoming', 'rescheduled'].includes(
                String(window.UPCOMING_DATA.status || '').toLowerCase()
            )
        ) {
            event.preventDefault();
            window.openModal?.('activeAppointmentModal');
        }
    },
    // resources/views/patient/shared-profile.blade.php
    'action-b3866f05a448': function (event) {
        switchTreatmentTab('past')
    },
    // resources/views/patient/shared-profile.blade.php
    'action-52d5565ec9b2': function (event) {
        switchTreatmentTab('future')
    },
    // resources/views/patient/shared-profile.blade.php
    'action-c2a93914d63a': function (event) {
        togglePatientProfilePrivacy(this)
    },
    // resources/views/patient/shared-profile.blade.php
    'action-0c1e6fd96db0': function (event) {
        openStartModal()
    },
    // resources/views/shared/academic-period.blade.php
    'action-770a42e411be': function (event) {
        closeAcademicFilterModal()
    },
    // resources/views/shared/academic-period.blade.php
    'action-72beedf02378': function (event) {
        resetAcademicFilters()
    },
    // resources/views/shared/academic-period.blade.php
    'action-7147962c1ff8': function (event) {
        if (readActionArg(this, 'click', 0, true)) { openEditModal(readActionArg(this, 'click', 1, true)) }
    },
    // resources/views/shared/academic-period.blade.php
    'action-df6b78ee9bc8': function (event) {
        openDeleteModalFromButton(this)
    },
    // resources/views/shared/academic-period.blade.php
    'action-09999bb5940d': function (event) {
        openSetActiveModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/academic-period.blade.php
    'action-e63476c3cb8d': function (event) {
        openEditModal(readActionArg(this, 'click', 0, true))
    },
    // resources/views/shared/academic-period.blade.php
    'action-8c24c81f4fa3': function (event) {
        openSetActiveModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/academic-period.blade.php
    'action-03a9835a5e25': function (event) {
        openSetActiveModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/academic-period.blade.php
    'action-33d0a78d8e7b': function (event) {
        openAcademicFilterModal()
    },
    // resources/views/shared/add-existing-record.blade.php
    'action-65226e9bb18e': function (event) {
        this.parentElement.innerHTML =
            readActionArg(this, 'error', 0, false);
    },
    // resources/views/shared/appointments.blade.php
    'action-9dac37c8b8f2': function (event) {
        window.closeFilterDrawer?.('filterModal')
    },
    // resources/views/shared/appointments.blade.php
    'action-e7e704b5c8bd': function (event) {
        applyAppointmentDrawerFilters()
    },
    // resources/views/shared/appointments.blade.php
    'action-830526eaba30': function (event) {
        if (readActionArg(this, 'click', 0, true)) {
            cancelAppointmentFromModal(
                readActionArg(this, 'click', 1, false),
                readActionArg(this, 'click', 2, true),
                readActionArg(this, 'click', 3, true)
            )
        }
    },
    // resources/views/shared/appointments.blade.php
    'action-ab60db006e21': function (event) {
        if (readActionArg(this, 'click', 0, true)) {
            openRescheduleModal({
                id: readActionArg(this, 'click', 1, false),
                name: readActionArg(this, 'click', 2, true),
                datetime: readActionArg(this, 'click', 3, true),
                serviceType: readActionArg(this, 'click', 4, true),
                updateUrl: readActionArg(this, 'click', 5, false)
            })
        }
    },
    // resources/views/shared/appointments.blade.php
    'action-27b3080a773d': function (event) {
        openStartProcedureModal(this)
    },
    // resources/views/shared/appointments.blade.php
    'action-c449c32115b5': function (event) {
        openRecordModal(this)
    },
    // resources/views/shared/appointments.blade.php
    'action-aaaf28f0e0b0': function (event) {
        if (readActionArg(this, 'click', 0, true)) {
            cancelAppointmentFromModal(
                readActionArg(this, 'click', 1, false),
                readActionArg(this, 'click', 2, true),
                readActionArg(this, 'click', 3, true)
            )
        }
    },
    // resources/views/shared/appointments.blade.php
    'action-29ed24cdc40f': function (event) {
        if (readActionArg(this, 'click', 0, true)) {
            openRescheduleModal({
                id: readActionArg(this, 'click', 1, false),
                name: readActionArg(this, 'click', 2, true),
                datetime: readActionArg(this, 'click', 3, true),
                serviceType: readActionArg(this, 'click', 4, true),
                updateUrl: readActionArg(this, 'click', 5, false)
            })
        }
    },
    // resources/views/shared/appointments.blade.php
    'action-06d47d859fc5': function (event) {
        resetAppointmentFilters()
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-210cc7fbd9be': function (event) {
        openReservedPeriodModal()
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-3658b7ca536b': function (event) {
        openRuleModal()
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-fcae67819c6f': function (event) {
        removeReservedTimeslot(readActionArg(this, 'click', 0, true))
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-5d12ccc88311': function (event) {
        updateReservedTimeslot(readActionArg(this, 'change', 0, true), 'time', this.value)
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-d5fde1a38ad6': function (event) {
        openAppointmentDetailModal(readActionArg(this, 'click', 0, true))
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-9f3a82837bcb': function (event) {
        addReservedTimeslot()
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-443e42f7600e': function (event) {
        updateReservedSlotDuration()
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-a626fa8e15ce': function (event) {
        updateReservedStudentTargetDropdowns(document.getElementById('reservedProgramCode').value, this.value, '')
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-14444081b07c': function (event) {
        updateReservedStudentTargetDropdowns(this.value, '', '')
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-6aebdfee76f3': function (event) {
        toggleReservedStudentFields(this.value)
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-e5dcc598a9fd': function (event) {
        handleReservedPeriodStateChange()
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-75f748f7e0aa': function (event) {
        setReservedBookingMode(this.value)
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-2f4c982ff581': function (event) {
        submitRule()
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-f9d97e5305b3': function (event) {
        selectBreak(this)
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-958c6da8d111': function (event) {
        toggleStatusFields(this.value)
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-9ac08cc75f07': function (event) {
        toggleDay(this)
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-755e543aec03': function (event) {
        closeAppointmentDetailModal()
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-a96098d272f6': function (event) {
        window.closeModal('holidayListModal')
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-a10af097fbc8': function (event) {
        openReservedPeriodDeleteModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-f2843b92493b': function (event) {
        openReservedPeriodModal("edit", readActionArg(this, 'click', 0, true), readActionArg(this, 'click', 1, true))
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-d584b12ca4ac': function (event) {
        openReservedPeriodDeleteModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-bad117ef6f8f': function (event) {
        openReservedPeriodDeleteModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-84a17b07f767': function (event) {
        openScheduleDeleteModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-b1e8100ff8f6': function (event) {
        openRuleModal(
            "edit",
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-899c015d05d8': function (event) {
        openScheduleDeleteModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-cf79ab3d24c0': function (event) {
        openRuleModal(
            "edit",
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-6d843152ccce': function (event) {
        openScheduleDeleteModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-5ae65e27acd4': function (event) {
        openRuleModal(
            "edit",
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-62d7209e5e45': function (event) {
        window.openModal('holidayListModal')
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-28b22a237020': function (event) {
        openBlockedDateDeleteModal(
            readActionArg(this, 'click', 0, true),
            readActionArg(this, 'click', 1, true)
        )
    },
    // resources/views/shared/clinic-schedule.blade.php
    'action-35e136a0c602': function (event) {
        openBlockModal()
    },
    // resources/views/shared/dental-records.blade.php
    'action-b977f72cb77d': function (event) {
        closeDentalRecordFilters()
    },
    // resources/views/shared/dental-records.blade.php
    'action-46ebb44a144d': function (event) {
        clearDentalRecordFilterDraft()
    },
    // resources/views/shared/dental-records.blade.php
    'action-fdd006138fbd': function (event) {
        applyDentalRecordFilters()
    },
    // resources/views/shared/dental-records.blade.php
    'action-83507c89d4f2': function (event) {
        removeDentalRecordDraftFilter(readActionArg(this, 'click', 0, false))
    },
    // resources/views/shared/dental-records.blade.php
    'action-e83e40f120f5': function (event) {
        closeDentalRecordDetailsModal()
    },
    // resources/views/shared/dental-records.blade.php
    'action-f5aa0fb428b8': function (event) {
        setDentalRecordDraftFilter('datePreset', readActionArg(this, 'click', 0, false))
    },
    // resources/views/shared/dental-records.blade.php
    'action-753919bc8ad8': function (event) {
        event.stopPropagation(); openRecordPanel(readActionArg(this, 'click', 0, true))
    },
    // resources/views/shared/dental-records.blade.php
    'action-451e2fc88763': function (event) {
        clearDentalRecordFilters()
    },
    // resources/views/shared/dental-records.blade.php
    'action-5916d2256d78': function (event) {
        openDentalRecordFilters()
    },
    // resources/views/shared/document-requests.blade.php
    'action-88c34ac00542': function (event) {
        selectDocumentCard('g', readActionArg(this, 'click', 0, true))
    },
    // resources/views/shared/document-requests.blade.php
    'action-3d5d881f4339': function (event) {
        selectDocumentCard('m', readActionArg(this, 'click', 0, true))
    },
    // resources/views/shared/document-requests.blade.php
    'action-34bf4e6b59e3': function (event) {
        selectDocumentCard(
            'd',
            readActionArg(this, 'click', 0, true)
        )
    },
    // resources/views/shared/document-requests.blade.php
    'action-b2e920847258': function (event) {
        event.stopPropagation(); openReject(readActionArg(this, 'click', 0, true), readActionArg(this, 'click', 1, true))
    },
    // resources/views/shared/document-requests.blade.php
    'action-6f1c12e1421c': function (event) {
        event.stopPropagation(); openApprove(readActionArg(this, 'click', 0, true), readActionArg(this, 'click', 1, true))
    },
    // resources/views/shared/document-requests.blade.php
    'action-339eff24b32d': function (event) {
        resetAllFilters()
    },
    // resources/views/shared/document-requests.blade.php
    'action-3c9443718a88': function (event) {
        resetAdvancedFilters()
    },
    // resources/views/shared/document-requests.blade.php
    'action-6f3d9ae5533d': function (event) {
        openFilterModal()
    },
    // resources/views/shared/inventory.blade.php
    'action-945b15a06543': function (event) {
        closeFilterDrawer('filterModal')
    },
    // resources/views/shared/inventory.blade.php
    'action-109110749c54': function (event) {
        clearFilterPanelModal()
    },
    // resources/views/shared/inventory.blade.php
    'action-362188adc405': function (event) {
        saveFilterPanel()
    },
    // resources/views/shared/inventory.blade.php
    'action-a48d004fa9c4': function (event) {
        deleteItem(readActionArg(this, 'click', 0, true))
    },
    // resources/views/shared/inventory.blade.php
    'action-640c3ab8383f': function (event) {
        openEdit(readActionArg(this, 'click', 0, true))
    },
    // resources/views/shared/inventory.blade.php
    'action-8fba7d16f720': function (event) {
        computeEditBalance()
    },
    // resources/views/shared/inventory.blade.php
    'action-32e9868b0600': function (event) {
        formatStockNo(this)
    },
    // resources/views/shared/inventory.blade.php
    'action-ea53196008ea': function (event) {
        computeAddBalance()
    },
    // resources/views/shared/inventory.blade.php
    'action-0d547826d7f9': function (event) {
        openAddModal()
    },
    // resources/views/shared/inventory.blade.php
    'action-eb2a40caced3': function (event) {
        clearFilterPanel()
    },
    // resources/views/shared/inventory.blade.php
    'action-3b869f2f8687': function (event) {
        openFilterDrawer('filterModal')
    },
    // resources/views/shared/inventory.blade.php
    'action-527a7d205eda': function (event) {
        setTab('supplies', this)
    },
    // resources/views/shared/inventory.blade.php
    'action-6af6d8c34f27': function (event) {
        setTab('medicine', this)
    },
    // resources/views/shared/inventory.blade.php
    'action-0f4a99c12b4d': function (event) {
        setTab('all', this)
    },
    // resources/views/shared/reports.blade.php
    'action-4e1de46b4c89': function (event) {
        window.location.assign(
            readActionArg(this, 'click', 0, false)
        )
    },
    // resources/views/shared/reports.blade.php
    'action-132d158d6401': function (event) {
        openCreateReportModal()
    },
    // resources/views/shared/service-types.blade.php
    'action-e9850a0605f8': function (event) {
        closeModal('serviceDetailsModal')
    },
    // components/delete-confirm-modal
    'close-modal': function (event) {
        closeModal(readActionArg(this, 'click', 0, false))
    },
});
