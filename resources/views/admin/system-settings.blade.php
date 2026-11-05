@extends('layouts.app')

@section('layout-role', $layoutRole ?? 'admin')

@section('title', 'System Settings')

@section('styles')
    @vite('resources/css/pages/admin/system-settings.css')
@endsection

@section('content')

    @php
        $canManageRetention = $canManageRetention ?? false;
        $allowedSettingGroups = $allowedSettingGroups ?? ['general', 'notifications'];
        $canManageGeneralSettings = in_array('general', $allowedSettingGroups, true);
        $canManageNotificationSettings = in_array('notifications', $allowedSettingGroups, true);
        $defaultSettingsTab = $canManageGeneralSettings
            ? 'general'
            : ($canManageNotificationSettings
                ? 'notifications'
                : 'general');
        if ($canManageRetention && (request('tab') === 'retention' || old('retention_policy_id'))) {
            $defaultSettingsTab = 'retention';
        }
    @endphp

    <main id="mainContent" class="system-settings-page app-page-shell page-enter">
        <div class="w-full">

            <x-page-banner title="System Settings" subtitle="Manage notification preferences, backups, and system behavior.">
                <x-slot:actions>
                    <x-button id="settingsBannerSaveBtn" type="button" variant="secondary" icon="fa-solid fa-floppy-disk"
                        class="settings-banner-save" data-settings-save data-ui-click="action-57cd63974b29">
                        Save Changes
                    </x-button>
                </x-slot:actions>
            </x-page-banner>

            <form id="settingsForm" action="{{ route($updateRoute ?? 'admin.system_settings.update') }}" method="POST"
                data-global-selects>
                @csrf

                <div class="settings-view" id="settingsListView">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

                        <div class="settings-sidebar-col lg:col-span-1">
                            <div class="settings-sidebar-card sticky top-[82px]">
                                <div class="settings-sidebar-head">
                                    <p class="settings-sidebar-kicker">Settings Menu</p>
                                </div>
                                <div class="p-2">
                                    @if ($canManageGeneralSettings)
                                        <a href="#"
                                            class="settings-nav-item {{ $defaultSettingsTab === 'general' ? 'active' : '' }}"
                                            data-ui-click="action-524cbe50222f" data-settings-tab="general"><i
                                                class="fa-solid fa-sliders"></i> General</a>
                                    @endif
                                    {{-- Temporarily hidden: Clinic Info
                                <a href="#" class="settings-nav-item"
                                    data-ui-click="action-aba23a6c84ff" data-settings-tab="clinic"><i
                                        class="fa-solid fa-hospital"></i> Clinic Info</a>
                                --}}
                                    @if ($canManageNotificationSettings)
                                        <a href="#"
                                            class="settings-nav-item {{ $defaultSettingsTab === 'notifications' ? 'active' : '' }}"
                                            data-ui-click="action-3360e45d2f7f" data-settings-tab="notifications"><i
                                                class="fa-solid fa-bell"></i> Notifications</a>
                                    @endif
                                    @if ($canManageRetention)
                                        <a href="?tab=retention"
                                            class="settings-nav-item {{ $defaultSettingsTab === 'retention' ? 'active' : '' }}"
                                            data-ui-click="action-e48f4aaf6db9" data-settings-tab="retention"><i
                                                class="fa-solid fa-shield-halved"></i> Data Retention</a>
                                    @endif
                                    {{-- Temporarily hidden: Email/SMTP
                                <a href="#" class="settings-nav-item"
                                    data-ui-click="action-bb4a13913035" data-settings-tab="email"><i
                                        class="fa-solid fa-envelope"></i> Email / SMTP</a>
                                --}}
                                    {{-- Temporarily hidden: Integrations
                                <a href="#" class="settings-nav-item"
                                    data-ui-click="action-5a9e853d1e90" data-settings-tab="integrations"><i
                                        class="fa-solid fa-plug"></i> Integrations</a>
                                --}}
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-3 space-y-0">

                            @if ($canManageRetention)
                                @include('admin.partials.retention-settings')
                            @endif

                            @if ($canManageGeneralSettings)
                                <div id="tab-general"
                                    class="settings-section {{ $defaultSettingsTab === 'general' ? 'active' : '' }}">
                                    <div class="section-card">
                                        <div class="section-card-hdr">
                                            <div class="section-card-hdr-left">
                                                <i class="fa-solid fa-sliders text-[#8B0000]"></i>
                                                <h2 class="font-bold text-gray-800 text-sm">General Preferences</h2>
                                            </div>
                                        </div>
                                        <div class="section-card-body">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                <div>
                                                    <label class="form-label">System Language</label>
                                                    <x-select name="language" class="form-ctrl form-sel">
                                                        @foreach (['English (US)', 'Filipino'] as $opt)
                                                            <option value="{{ $opt }}"
                                                                @selected(old('language', $settings['language']->value ?? 'English (US)') === $opt)>
                                                                {{ $opt }}
                                                            </option>
                                                        @endforeach
                                                    </x-select>
                                                </div>

                                                <div>
                                                    <label class="form-label">Timezone</label>
                                                    <x-select name="timezone" class="form-ctrl form-sel" disabled>
                                                        @foreach (['Asia/Manila (UTC+8)', 'UTC'] as $opt)
                                                            <option value="{{ $opt }}"
                                                                @selected(old('timezone', $settings['timezone']->value ?? 'Asia/Manila (UTC+8)') === $opt)>
                                                                {{ $opt }}
                                                            </option>
                                                        @endforeach
                                                    </x-select>
                                                    <div class="setting-note">This setting is locked for now.</div>
                                                </div>

                                                <div>
                                                    <label class="form-label">Date Format</label>
                                                    <x-select name="date_format" class="form-ctrl form-sel" disabled>
                                                        @foreach (['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD'] as $opt)
                                                            <option value="{{ $opt }}"
                                                                @selected(old('date_format', $settings['date_format']->value ?? 'MM/DD/YYYY') === $opt)>
                                                                {{ $opt }}
                                                            </option>
                                                        @endforeach
                                                    </x-select>
                                                    <div class="setting-note">This setting is locked for now.</div>
                                                </div>

                                                <div>
                                                    <label class="form-label">Time Format</label>
                                                    <x-select
    name="time_format"
    class="form-ctrl form-sel"
    disabled
>
    @foreach (['12-hour (AM/PM)', '24-hour'] as $opt)
        <option
            value="{{ $opt }}"
            @selected(
                old(
                    'time_format',
                    $settings['time_format']->value ?? '12-hour (AM/PM)'
                ) === $opt
            )
        >
            {{ $opt }}
        </option>
    @endforeach
</x-select>
                                                    <div class="setting-note">This setting is locked for now.</div>
                                                </div>
                                            </div>

                                            <div class="setting-row">
                                                <div class="setting-row-info">
                                                    <div class="setting-row-label">Maintenance Mode</div>
                                                    <div class="setting-row-desc">Temporarily disable appointment booking.
                                                        Patients can still access their dashboard.</div>
                                                </div>
                                                <x-switch
    name="maintenance_mode"
    value="1"
    unchecked-value="0"
    :checked="old(
        'maintenance_mode',
        $settings['maintenance_mode']->value ?? '0'
    ) === '1'"
/>
                                            </div>

                                            <div class="mt-5 mb-4 pt-4 border-t border-gray-50">
                                                <p class="text-xs text-gray-400 mb-4 font-medium uppercase tracking-wide">AI
                                                    Services</p>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                <div>
                                                    <label class="form-label">AI Operating Mode</label>
                                                    <x-select
    name="ai_mode"
    class="form-ctrl form-sel"
>
    @foreach ([
        'normal' => 'Normal',
        'degraded' => 'Degraded',
        'offline' => 'Offline',
    ] as $value => $label)
        <option
            value="{{ $value }}"
            @selected(
                old(
                    'ai_mode',
                    $settings['ai_mode']->value ?? 'normal'
                ) === $value
            )
        >
            {{ $label }}
        </option>
    @endforeach
</x-select>
                                                    <div class="setting-note">Offline disables AI calls and uses fallback
                                                        workflows only.</div>
                                                </div>
                                            </div>

                                            <div class="setting-row">
                                                <div class="setting-row-info">
                                                    <div class="setting-row-label">AI Chatbot</div>
                                                    <div class="setting-row-desc">Allow the chatbot to call the AI service
                                                        when available</div>
                                                </div>
                                                <x-switch
    name="ai_chatbot_enabled"
    value="1"
    unchecked-value="0"
    :checked="old(
        'ai_chatbot_enabled',
        $settings['ai_chatbot_enabled']->value ?? '1'
    ) === '1'"
/>
                                            </div>

                                            <div class="setting-row">
                                                <div class="setting-row-info">
                                                    <div class="setting-row-label">
                                                        AI Signature Verification
                                                    </div>

                                                    <div class="setting-row-desc">
                                                        When disabled, patients can only draw their signature.
                                                        Uploaded signatures require successful AI verification.
                                                    </div>
                                                </div>

                                                <x-switch
    name="ai_signature_enabled"
    value="1"
    unchecked-value="0"
    :checked="old(
        'ai_signature_enabled',
        $settings['ai_signature_enabled']->value ?? '1'
    ) === '1'"
/>
                                            </div>

                                            <div class="setting-row">
                                                <div class="setting-row-info">
                                                    <div class="setting-row-label">
                                                        AI Report Narratives
                                                    </div>

                                                    <div class="setting-row-desc">
                                                        If unavailable, reports will still
                                                        generate using the built-in fallback narrative
                                                    </div>
                                                </div>

                                                <x-switch
    name="ai_reports_enabled"
    value="1"
    unchecked-value="0"
    :checked="old(
        'ai_reports_enabled',
        $settings['ai_reports_enabled']->value ?? '1'
    ) === '1'"
/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Temporarily hidden: Clinic Info
                        <div id="tab-clinic" class="settings-section">
                            <div class="section-card">
                                <div class="section-card-hdr">
                                    <div class="section-card-hdr-left">
                                        <i class="fa-solid fa-hospital text-[#8B0000]"></i>
                                        <h2 class="font-bold text-gray-800 text-sm">Clinic Information</h2>
                                    </div>
                                </div>
                                <div class="section-card-body">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div class="md:col-span-2">
                                            <label class="form-label">Clinic Name</label>
                                            <input type="text" name="clinic_name" class="form-ctrl"
                                                placeholder="Clinic name"
                                                value="{{ old('clinic_name', $settings['clinic_name']->value ?? 'PUP Taguig Dental Clinic') }}"
                                                disabled>
                                            <div class="setting-note">This setting is locked for now.</div>
                                        </div>

                                        <div>
                                            <label class="form-label">Contact Number</label>
                                            <input type="text" name="contact_number" class="form-ctrl"
                                                placeholder="+63 ..."
                                                value="{{ old('contact_number', $settings['contact_number']->value ?? '') }}"
                                                disabled>
                                            <div class="setting-note">This setting is locked for now.</div>
                                        </div>

                                        <div>
                                            <label class="form-label">Email Address</label>
                                            <input type="email" name="email_address" class="form-ctrl"
                                                placeholder="email@example.com"
                                                value="{{ old('email_address', $settings['email_address']->value ?? '') }}"
                                                disabled>
                                            <div class="setting-note">This setting is locked for now.</div>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="form-label">Address</label>
                                            <input type="text" name="address" class="form-ctrl"
                                                placeholder="Full address"
                                                value="{{ old('address', $settings['address']->value ?? '') }}"
                                                disabled>
                                            <div class="setting-note">This setting is locked for now.</div>
                                        </div>

                                        <div>
                                            <label class="form-label">Operating Since</label>
                                            <input type="text" name="operating_since" class="form-ctrl"
                                                placeholder="Year"
                                                value="{{ old('operating_since', $settings['operating_since']->value ?? '1998') }}"
                                                disabled>
                                            <div class="setting-note">This setting is locked for now.</div>
                                        </div>

                                        <div>
                                            <label class="form-label">Accreditation No.</label>
                                            <input type="text" name="accreditation_no" class="form-ctrl"
                                                placeholder="License / accreditation number"
                                                value="{{ old('accreditation_no', $settings['accreditation_no']->value ?? '') }}"
                                                disabled>
                                            <div class="setting-note">This setting is locked for now.</div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label">Clinic Description</label>
                                        <textarea name="description" class="form-ctrl resize-none" rows="3"
                                            placeholder="Short description shown on patient portal…"
                                            disabled>{{ old('description', $settings['description']->value ?? 'The PUP Taguig Dental Clinic provides free dental services to PUP students, faculty, and staff.') }}</textarea>
                                        <div class="setting-note">This setting is locked for now.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        --}}

                            @if ($canManageNotificationSettings)
                                <div id="tab-notifications"
                                    class="settings-section {{ $defaultSettingsTab === 'notifications' ? 'active' : '' }}">
                                    <div class="section-card">
                                        <div class="section-card-hdr">
                                            <div class="section-card-hdr-left">
                                                <i class="fa-solid fa-bell text-[#8B0000]"></i>
                                                <h2 class="font-bold text-gray-800 text-sm">Notification Preferences</h2>
                                            </div>
                                        </div>
                                        <div class="section-card-body">
                                            <p class="text-xs text-gray-400 mb-4 font-medium uppercase tracking-wide">Admin
                                                Alerts</p>

                                            <div class="setting-row">
                                                <div class="setting-row-info">
                                                    <div class="setting-row-label">
                                                        New Appointment Booked
                                                    </div>

                                                    <div class="setting-row-desc">
                                                        Notify admin when a patient books an
                                                        appointment
                                                    </div>
                                                </div>

                                                <x-switch
    name="notif_new_appointment"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_new_appointment',
        $settings['notif_new_appointment']->value ?? '1'
    ) === '1'"
/>
                                            </div>

                                            <div class="setting-row">
                                                <div class="setting-row-info">
                                                    <div class="setting-row-label">
                                                        Appointment Cancellation
                                                    </div>

                                                    <div class="setting-row-desc">
                                                        Notify when a patient cancels or no-shows
                                                    </div>
                                                </div>

                                                <x-switch
    name="notif_cancellation"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_cancellation',
        $settings['notif_cancellation']->value ?? '1'
    ) === '1'"
/>
                                            </div>

                                            <div class="setting-row">
                                                <div class="setting-row-info">
                                                    <div class="setting-row-label">
                                                        Document Request Submitted
                                                    </div>

                                                    <div class="setting-row-desc">
                                                        Alert when a patient requests a dental
                                                        certificate
                                                    </div>
                                                </div>

                                                <x-switch
    name="notif_document_request"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_document_request',
        $settings['notif_document_request']->value ?? '1'
    ) === '1'"
/>
                                            </div>

                                            <div class="mt-5 mb-4 pt-4 border-t border-gray-50">
                                                <p class="text-xs text-gray-400 mb-4 font-medium uppercase tracking-wide">
                                                    Patient Status Updates</p>

                                                <div class="setting-row">
                                                    <div class="setting-row-info">
                                                        <div class="setting-row-label">
                                                            Appointment Rescheduled
                                                        </div>

                                                        <div class="setting-row-desc">
                                                            Notify patient when an appointment
                                                            schedule is changed
                                                        </div>
                                                    </div>

                                                    <x-switch
    name="notif_rescheduled"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_rescheduled',
        $settings['notif_rescheduled']->value ?? '1'
    ) === '1'"
/>
                                                </div>

                                                <div class="setting-row">
                                                    <div class="setting-row-info">
                                                        <div class="setting-row-label">
                                                            Appointment Confirmation
                                                        </div>

                                                        <div class="setting-row-desc">
                                                            Notify patient immediately after a
                                                            booking is successfully recorded
                                                        </div>
                                                    </div>

                                                    <x-switch
    name="notif_confirmation"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_confirmation',
        $settings['notif_confirmation']->value ?? '1'
    ) === '1'"
/>
                                                </div>

                                                <div class="setting-row">
                                                    <div class="setting-row-info">
                                                        <div class="setting-row-label">
                                                            Follow-up Appointment Scheduled
                                                        </div>

                                                        <div class="setting-row-desc">
                                                            Notify patient when the dentist sets
                                                            a follow-up appointment
                                                        </div>
                                                    </div>

                                                    <x-switch
    name="notif_follow_up_scheduled"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_follow_up_scheduled',
        $settings['notif_follow_up_scheduled']->value ?? '1'
    ) === '1'"
/>
                                                </div>

                                                <div class="setting-row">
                                                    <div class="setting-row-info">
                                                        <div class="setting-row-label">
                                                            Document Request Approved
                                                        </div>

                                                        <div class="setting-row-desc">
                                                            Notify patient when a document
                                                            request is approved and ready for
                                                            pickup
                                                        </div>
                                                    </div>

                                                    <x-switch
    name="notif_document_approved"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_document_approved',
        $settings['notif_document_approved']->value ?? '1'
    ) === '1'"
/>
                                                </div>

                                                <div class="setting-row">
                                                    <div class="setting-row-info">
                                                        <div class="setting-row-label">
                                                            Document Request Rejected
                                                        </div>

                                                        <div class="setting-row-desc">
                                                            Notify patient when a document
                                                            request is rejected
                                                        </div>
                                                    </div>

                                                    <x-switch
    name="notif_document_rejected"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_document_rejected',
        $settings['notif_document_rejected']->value ?? '1'
    ) === '1'"
/>
                                                </div>
                                            </div>

                                            <div class="mt-5 mb-4 pt-4 border-t border-gray-50">
                                                <p class="text-xs text-gray-400 mb-4 font-medium uppercase tracking-wide">
                                                    Patient Reminders</p>

                                                <div class="setting-row">
                                                    <div class="setting-row-info">
                                                        <div class="setting-row-label">
                                                            Appointment Reminder (24 Hours
                                                            Before Appointment)
                                                        </div>

                                                        <div class="setting-row-desc">
                                                            Notify the patient 24 hours before
                                                            the scheduled appointment time
                                                        </div>
                                                    </div>

                                                    <x-switch
    name="notif_reminder_24h"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_reminder_24h',
        $settings['notif_reminder_24h']->value ?? '1'
    ) === '1'"
/>
                                                </div>

                                                <div class="setting-row">
                                                    <div class="setting-row-info">
                                                        <div class="setting-row-label">
                                                            Follow-up Reminder (48 Hours Before
                                                            Appointment)
                                                        </div>

                                                        <div class="setting-row-desc">
                                                            Notify the patient 48 hours before
                                                            the scheduled follow-up appointment
                                                        </div>
                                                    </div>

                                                    <x-switch
    name="notif_follow_up_reminder"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_follow_up_reminder',
        $settings['notif_follow_up_reminder']->value ?? '1'
    ) === '1'"
/>
                                                </div>

                                                <div class="setting-row">
                                                    <div class="setting-row-info">
                                                        <div class="setting-row-label">
                                                            Follow-up Reminder (On the Day of
                                                            the Appointment)
                                                        </div>

                                                        <div class="setting-row-desc">
                                                            Notify the patient on the day of
                                                            their scheduled follow-up appointment
                                                        </div>
                                                    </div>

                                                    <x-switch
    name="notif_follow_up_today_reminder"
    value="1"
    unchecked-value="0"
    :checked="old(
        'notif_follow_up_today_reminder',
        $settings['notif_follow_up_today_reminder']->value ?? '1'
    ) === '1'"
/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Temporarily hidden: Security
                        <div id="tab-security" class="settings-section">
                            <div class="section-card">
                                <div class="section-card-hdr">
                                    <div class="section-card-hdr-left">
                                        <i class="fa-solid fa-shield-halved text-[#8B0000]"></i>
                                        <h2 class="font-bold text-gray-800 text-sm">Security Settings</h2>
                                    </div>
                                </div>
                                <div class="section-card-body">
                                    <p class="text-sm text-gray-500">
                                        This section is visible in the UI, but backend saving is currently excluded.
                                    </p>
                                </div>
                            </div>
                        </div>

                        --}}

                            {{-- Temporarily hidden: Email/SMTP
                        <div id="tab-email" class="settings-section">
                            <div class="section-card">
                                <div class="section-card-hdr">
                                    <div class="section-card-hdr-left">
                                        <i class="fa-solid fa-envelope text-[#8B0000]"></i>
                                        <h2 class="font-bold text-gray-800 text-sm">Email / SMTP Configuration</h2>
                                    </div>
                                    <span class="badge-offline">Backend Excluded</span>
                                </div>
                                <div class="section-card-body">
                                    <p class="text-sm text-gray-500">
                                        Email / SMTP settings are currently excluded from backend persistence.
                                    </p>
                                </div>
                            </div>
                        </div>

                        --}}

                            {{-- Temporarily hidden: Integrations
                        <div id="tab-integrations" class="settings-section">
                            <div class="section-card">
                                <div class="section-card-hdr">
                                    <div class="section-card-hdr-left">
                                        <i class="fa-solid fa-plug text-[#8B0000]"></i>
                                        <h2 class="font-bold text-gray-800 text-sm">Third-Party Integrations</h2>
                                    </div>
                                </div>
                                <div class="section-card-body">
                                    <p class="text-sm text-gray-500">
                                        Integrations are currently excluded from backend persistence.
                                    </p>
                                </div>
                            </div>
                        </div>

                        --}}

                            <x-floating-save-bar id="settingsFloatingActions" title="System controls"
                                subtitle="Save changes before navigating away.">
                                <x-slot:actions>

                                    <button type="button" id="settingsResetDefaultsBtn" class="btn-reset-defaults"
                                        data-ui-click="action-96e8179179ff">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        <span>Reset Defaults</span>
                                    </button>

                                    <button type="button" id="settingsDiscardBtn" class="btn-discard"
                                        data-ui-click="action-22c2e1201b39" disabled>
                                        Discard
                                    </button>

                                    <button type="button" id="settingsSaveFloatBtn" class="btn-save-float"
                                        data-settings-save data-ui-click="action-57cd63974b29">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        <span>Save Changes</span>
                                    </button>

                                </x-slot:actions>
                            </x-floating-save-bar>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    @if ($canManageRetention)
        <div id="retentionPasswordDialog" class="ui-modal modal-theme-primary" aria-hidden="true">
            <div class="ui-modal-card modal-sm" role="dialog" aria-modal="true"
                aria-labelledby="retentionPasswordTitle" aria-describedby="retentionPasswordDescription">
                <div class="modal-hd">
                    <div class="modal-heading">
                        <div class="modal-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></div>
                        <div class="modal-copy">
                            <h2 id="retentionPasswordTitle" class="modal-title">Confirm retention change</h2>
                            <p id="retentionPasswordDescription" class="modal-subtitle">Enter your current admin password
                                to confirm this retention change.</p>
                        </div>
                    </div>
                    <button type="button" class="modal-x" data-modal-close="retentionPasswordDialog"
                        aria-label="Close password confirmation">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-bd modal-form-grid">
                    <div class="global-confirm-alert">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                        <div>
                            <p>Policy change</p><strong id="retentionPasswordSummary"></strong>
                        </div>
                    </div>
                    <div class="global-form-group">
                        <label for="retentionPassword" class="global-form-label">Your admin password <span
                                class="required-mark">*</span></label>
                        <div class="global-control-wrap">
                            <i class="fa-solid fa-lock global-control-icon" aria-hidden="true"></i>
                            <input id="retentionPassword" name="password" type="password"
                                class="form-input-custom global-form-icon" autocomplete="current-password" required
                                maxlength="1024" data-modal-initial-focus>
                        </div>
                    </div>
                </div>
                <div class="modal-ft">
    <x-button
        id="retentionPasswordCancel"
        type="button"
        variant="secondary"
        data-modal-close="retentionPasswordDialog"
    >
        Cancel
    </x-button>

    <x-button
        id="retentionPasswordConfirm"
        type="button"
        variant="primary"
    >
        Confirm and save
    </x-button>
</div>
            </div>
        </div>
        @foreach ($retentionPolicies as $policy)
            <form id="retention-period-{{ $policy->id }}" action="{{ route('admin.retention.update', $policy) }}"
                method="POST" hidden data-global-validation
                data-retention-label="{{ config('retention.categories.' . $policy->category . '.label', $policy->category) }}">
                @csrf @method('PUT')
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="version" value="{{ $policy->version }}">
                <input type="hidden" name="confirmed" value="1">
                <input type="hidden" name="return_to" value="system_settings">
                <input type="hidden" name="retention_policy_id" value="{{ $policy->id }}">
            </form>
            @foreach (['approve', 'activate', 'deactivate'] as $retentionAction)
                @if (
                    ($retentionAction === 'approve' && $policy->status === 'provisional' && $policy->hasConfiguredRule()) ||
                        ($retentionAction === 'activate' && $policy->status === 'approved') ||
                        ($retentionAction === 'deactivate' && $policy->status === 'active'))
                    <form id="retention-{{ $retentionAction }}-{{ $policy->id }}"
                        action="{{ route('admin.retention.update', $policy) }}" method="POST" hidden
                        data-global-validation
                        data-retention-label="{{ config('retention.categories.' . $policy->category . '.label') }}"
                        data-retention-action="{{ $retentionAction }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="action" value="{{ $retentionAction }}">
                        <input type="hidden" name="version" value="{{ $policy->version }}">
                        <input type="hidden" name="confirmed" value="1">
                        <input type="hidden" name="retention_policy_id" value="{{ $policy->id }}">
                    </form>
                @endif
            @endforeach
        @endforeach
    @endif
@endsection

@section('scripts')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        function initRetentionPasswordConfirmation() {
            const dialog = document.getElementById('retentionPasswordDialog');
            if (!dialog) return;

            const password = document.getElementById('retentionPassword');
            const confirm = document.getElementById('retentionPasswordConfirm');
            let pendingForm = null;
            let submitting = false;

            document.querySelectorAll('form[data-retention-label]').forEach(form => {
                form.addEventListener('submit', event => {
                    event.preventDefault();
                    if (submitting) return;
                    const fields = [...form.elements].filter(field =>
                        field.matches(
                            'input:not([type="hidden"]):not([type="password"]), select, textarea')
                    );
                    const invalid = fields.find(field => window.validateFormInputField ?
                        !window.validateFormInputField(field) :
                        !field.checkValidity() || (field.required && !field.value.trim()));
                    if (invalid) {
                        if (window.focusGlobalInvalidField) window.focusGlobalInvalidField(invalid);
                        else invalid.focus();
                        return;
                    }
                    pendingForm = form;
                    password.value = '';
                    password.setAttribute('form', form.id);
                    const action = form.dataset.retentionAction || 'update';
                    const duration = form.elements.namedItem('duration')?.value;
                    const unit = form.elements.namedItem('unit')?.selectedOptions[0]?.textContent;
                    const rule = form.elements.namedItem('counting_rule')?.selectedOptions[0]?.textContent;
                    document.getElementById('retentionPasswordSummary').textContent =
                        action === 'update' ?
                        `${form.dataset.retentionLabel}: ${duration} ${unit} — ${rule}` :
                        `${action}: ${form.dataset.retentionLabel}`;
                    window.openModal(dialog.id);
                });
            });

            function saveRetentionPeriod() {
                if (submitting || !pendingForm || !pendingForm.reportValidity()) return;
                submitting = true;
                confirm.disabled = true;
                // Submit the associated password with this policy only. A full reload
                // obtains the new version and requires a fresh password on the next save.
                HTMLFormElement.prototype.submit.call(pendingForm);
                password.value = '';
            }

            confirm.addEventListener('click', saveRetentionPeriod);
            password.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    saveRetentionPeriod();
                }
            });
            document.addEventListener('ui-modal:closed', event => {
                if (event.detail.modal !== dialog) return;
                password.value = '';
                password.removeAttribute('form');
                pendingForm = null;
            });
            window.addEventListener('pageshow', () => {
                password.value = '';
                submitting = false;
                confirm.disabled = false;
                if (dialog.classList.contains('open')) window.closeModal(dialog.id);
            });
        }

        function getActiveSettingsTabId() {
            const activeSection = document.querySelector('#settingsListView .settings-section.active');
            if (!activeSection) return 'general';

            return activeSection.id.replace('tab-', '');
        }

        function setActiveNav(tabId, clickedItem = null) {
            document.querySelectorAll('#settingsListView .settings-nav-item').forEach(item => {
                const itemTabId = item.dataset.settingsTab;
                const isCurrent = clickedItem === item || itemTabId === tabId;

                item.classList.toggle('active', isCurrent);
            });
        }

        function switchTab(tabId, clickedItem = null) {
            document.querySelectorAll('#settingsListView .settings-section').forEach(section => {
                section.classList.remove('active');
            });

            const section = document.getElementById('tab-' + tabId);
            if (section) {
                section.classList.add('active');
            }

            setActiveNav(tabId, clickedItem);
            const url = new URL(window.location.href);
            if (tabId === 'retention') url.searchParams.set('tab', 'retention');
            else url.searchParams.delete('tab');
            window.history.replaceState(null, '', url);
            updateSettingsFloatingBar();

        }

        function initializeClinicVoiceClearButtons(root = document) {
            const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
            const wrappers = scope.querySelectorAll('#tab-clinic .voice-input-wrap');

            wrappers.forEach((wrapper) => {
                if (wrapper.dataset.clinicVoiceClearReady === 'true') return;

                const field = wrapper.querySelector('input.form-ctrl, textarea.form-ctrl');
                if (!field || field.readOnly || field.disabled) return;

                const isTextarea = field.tagName.toLowerCase() === 'textarea';

                let row = wrapper.parentElement && wrapper.parentElement.classList.contains('clinic-voice-row') ?
                    wrapper.parentElement :
                    null;

                if (!row) {
                    row = document.createElement('div');
                    row.className = 'clinic-voice-row';
                    wrapper.parentNode.insertBefore(row, wrapper);
                    row.appendChild(wrapper);
                }

                row.classList.toggle('is-textarea', isTextarea);

                let clearBtn = row.querySelector('.clinic-voice-clear-btn');
                if (!clearBtn) {
                    clearBtn = document.createElement('button');
                    clearBtn.type = 'button';
                    clearBtn.className = 'clinic-voice-clear-btn hidden';
                    clearBtn.setAttribute('aria-label', 'Clear field');
                    clearBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                    row.appendChild(clearBtn);
                }

                const status = wrapper.querySelector('[data-voice-status]');

                function toggleClear() {
                    clearBtn.classList.toggle('hidden', (field.value || '').trim().length === 0);
                }

                clearBtn.addEventListener('click', () => {
                    field.value = '';
                    field.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    field.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));

                    if (status) status.classList.add('hidden');

                    clearBtn.classList.add('hidden');
                    field.focus();
                });

                field.addEventListener('input', toggleClear);
                toggleClear();

                wrapper.dataset.clinicVoiceClearReady = 'true';
            });
        }

        function serializeSettingsForm(form) {
            if (!form) return '';

            const data = new FormData(form);
            const normalized = {};

            data.forEach((value, key) => {
                if (!key || key === '_token' || key === '_method') return;

                const cleanKey = key.endsWith('[]') ? key.slice(0, -2) : key;

                if (key.endsWith('[]')) {
                    if (!Array.isArray(normalized[cleanKey])) {
                        normalized[cleanKey] = [];
                    }

                    normalized[cleanKey].push(String(value));
                    return;
                }

                normalized[cleanKey] = String(value);
            });

            Object.keys(normalized).forEach(key => {
                if (Array.isArray(normalized[key])) {
                    normalized[key].sort();
                }
            });

            return JSON.stringify(
                Object.keys(normalized)
                .sort()
                .map(key => [key, normalized[key]])
            );
        }

        const SETTINGS_DEFAULTS = {
            language: 'English (US)',
            maintenance_mode: '0',
            ai_mode: 'normal',
            ai_chatbot_enabled: '1',
            ai_signature_enabled: '1',
            ai_reports_enabled: '1',

            notif_new_appointment: '1',
            notif_cancellation: '1',
            notif_document_request: '1',
            notif_rescheduled: '1',
            notif_follow_up_scheduled: '1',
            notif_document_approved: '1',
            notif_document_rejected: '1',
            notif_reminder_24h: '1',
            notif_confirmation: '1',
            notif_follow_up_reminder: '1',
            notif_follow_up_today_reminder: '1',

            backup_frequency: 'Daily',
            backup_retention_days: '30',
            backup_storage: 'Local Server',
            backup_time: '02:00',
            auto_backup_enabled: '1',
            backup_include_files: '1',
            backup_encrypt: '1',
        };

        let settingsInitialState = '';
        let settingsIsDirty = false;
        let settingsIsSubmitting = false;
        const settingsSaveButtonStates = new WeakMap();

        function syncSettingsVisualState() {
            document
                .querySelectorAll(
                    '#settingsForm .permission-chip input[type="checkbox"]'
                )
                .forEach(input => {
                    input
                        .closest(
                            '.permission-chip'
                        )
                        ?.classList.toggle(
                            'active',
                            input.checked
                        );
                });

            document
                .querySelectorAll(
                    '#settingsForm .custom-select'
                )
                .forEach(wrapper => {
                    window.syncCustomSelect
                        ?.(wrapper);
                });
        }

        function updateSettingsButtons() {
            const discardBtn = document.getElementById('settingsDiscardBtn');
            const resetBtn = document.getElementById('settingsResetDefaultsBtn');
            const saveButtons = document.querySelectorAll('[data-settings-save]');
            const bannerSave = document.getElementById('settingsBannerSaveBtn');
            if (bannerSave) bannerSave.style.display = getActiveSettingsTabId() === 'retention' ? 'none' : '';

            if (discardBtn) {
                discardBtn.disabled = !settingsIsDirty || settingsIsSubmitting;
                discardBtn.classList.toggle('is-disabled', discardBtn.disabled);
            }

            if (resetBtn) {
                resetBtn.disabled = settingsIsSubmitting;
                resetBtn.classList.toggle('is-disabled', resetBtn.disabled);
            }

            saveButtons.forEach(button => {
                let state = settingsSaveButtonStates.get(button);
                if (!state) {
                    state = {
                        content: [...button.childNodes],
                        loading: false
                    };
                    settingsSaveButtonStates.set(button, state);
                }

                button.disabled = settingsIsSubmitting;
                button.classList.toggle('is-disabled', settingsIsSubmitting);

                if (state.loading !== settingsIsSubmitting) {
                    if (settingsIsSubmitting) {
                        const icon = document.createElement('i');
                        icon.className = 'fa-solid fa-spinner spin';
                        button.replaceChildren(icon, document.createTextNode(' Saving...'));
                    } else {
                        // Keep the existing nodes and listeners; serializing CSSOM
                        // styles back into innerHTML creates CSP-blocked attributes.
                        button.replaceChildren(...state.content);
                    }
                    state.loading = settingsIsSubmitting;
                }
            });
        }

        function updateSettingsFloatingBar() {
            const form =
                document.getElementById(
                    'settingsForm'
                );

            const bar =
                document.getElementById(
                    'settingsFloatingActions'
                );

            if (
                !form ||
                !bar
            ) {
                return;
            }

            settingsIsDirty =
                serializeSettingsForm(
                    form
                ) !==
                settingsInitialState;

            bar.classList.toggle(
                'is-dirty',
                settingsIsDirty
            );

            bar.classList.toggle(
                'is-clean',
                !settingsIsDirty
            );

            if (
                !settingsIsDirty ||
                getActiveSettingsTabId() ===
                'retention'
            ) {
                window.FloatingSaveBar
                    ?.hide(bar);

                updateSettingsButtons();

                return;
            }

            const title =
                bar.querySelector(
                    '.fsb-title'
                );

            const sub =
                bar.querySelector(
                    '.fsb-sub'
                );

            if (title) {
                title.textContent =
                    'Unsaved changes';
            }

            if (sub) {
                sub.textContent =
                    'Review your changes before leaving this page.';
            }

            window.FloatingSaveBar
                ?.show(bar);

            updateSettingsButtons();
        }

        function setNamedFieldValue(form, name, value) {
            const fields = Array.from(form.querySelectorAll('input, select, textarea'))
                .filter(field => field.form === form && (field.name === name || field.name === `${name}[]`));

            if (!fields.length) return;

            fields.forEach(field => {
                const type = (field.type || '').toLowerCase();

                if (type === 'hidden' && fields.some(item => item !== field && item.name === field.name)) {
                    return;
                }

                if (Array.isArray(value)) {
                    if (type === 'checkbox' || type === 'radio') {
                        field.checked = value.includes(field.value);
                    }
                } else if (type === 'checkbox' || type === 'radio') {
                    field.checked = String(value) === String(field.value) || String(value) === '1' || value ===
                        true;
                } else {
                    if (field._flatpickr && field.classList.contains('js-flatpickr-time')) {
                        field._flatpickr.setDate(String(value), true, 'H:i');
                    } else {
                        field.value = value;
                    }
                }

                field.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                field.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            });
        }

        async function resetSettingsToDefaults() {
            const form = document.getElementById('settingsForm');
            if (!form || settingsIsSubmitting) return;

            Object.entries(SETTINGS_DEFAULTS).forEach(([name, value]) => {
                setNamedFieldValue(form, name, value);
            });

            syncSettingsVisualState();

            settingsIsDirty = serializeSettingsForm(form) !== settingsInitialState;
            updateSettingsFloatingBar();

            if (!settingsIsDirty) {
                window.showToast?.({
                    type: 'info',
                    title: 'Already using defaults',
                    message: 'Your system settings are already set to the default values.',
                    duration: 4500,
                });
                return;
            }

            await submitSettingsForm({
                successTitle: 'Defaults restored',
                successMessage: 'System settings were reset to defaults and saved successfully.',
                noChangesTitle: 'Already using defaults',
                noChangesMessage: 'Your system settings are already set to the default values.',
            });
        }

        function discardSettingsChanges() {
            const form = document.getElementById('settingsForm');
            if (!form || !settingsIsDirty || settingsIsSubmitting) return;

            form.reset();

            requestAnimationFrame(() => {
                syncSettingsVisualState();
                updateSettingsFloatingBar();

                window.showToast?.({
                    type: 'info',
                    title: 'Changes discarded',
                    message: 'Your settings were restored to their last saved values.',
                    duration: 5000,
                });
            });
        }

        function getSettingsErrorMessage(error, fallback = 'Unable to save settings. Please try again.') {
            const data = error?.data;

            if (data?.message) return data.message;

            if (data?.errors) {
                const firstError = Object.values(data.errors).flat()[0];
                if (firstError) return firstError;
            }

            return fallback;
        }

        async function submitSettingsForm(options = {}) {
            const form = document.getElementById('settingsForm');
            if (!form || settingsIsSubmitting) return;

            settingsIsDirty = serializeSettingsForm(form) !== settingsInitialState;

            if (!settingsIsDirty) {
                window.showToast?.({
                    type: 'info',
                    title: options.noChangesTitle || 'No changes to save',
                    message: options.noChangesMessage || 'Edit any setting first before saving.',
                    duration: 4500,
                });
                return;
            }

            settingsIsSubmitting = true;
            updateSettingsButtons();

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(token ? {
                            'X-CSRF-TOKEN': token
                        } : {}),
                    },
                });

                const contentType = response.headers.get('content-type') || '';
                const data = contentType.includes('application/json') ?
                    await response.json().catch(() => null) :
                    null;

                if (!response.ok) {
                    const error = new Error('Settings save failed');
                    error.data = data;
                    error.response = response;
                    throw error;
                }

                settingsInitialState = serializeSettingsForm(form);
                settingsIsDirty = false;

                updateSettingsFloatingBar();

                window.showToast?.({
                    type: 'success',
                    title: options.successTitle || 'Settings saved',
                    message: options.successMessage || data?.message ||
                        'System settings were saved successfully.',
                    duration: 6000,
                });
            } catch (error) {
                window.showToast?.({
                    type: 'error',
                    title: 'Save failed',
                    message: getSettingsErrorMessage(error),
                    duration: 7000,
                });
            } finally {
                settingsIsSubmitting = false;
                updateSettingsFloatingBar();
            }
        }

        function initSettingsFloatingActions() {
            const form = document.getElementById('settingsForm');
            if (!form) return;

            window.FloatingSaveBar
                ?.mount(document);

            settingsInitialState = serializeSettingsForm(form);
            syncSettingsVisualState();

            form.addEventListener('input', updateSettingsFloatingBar);
            form.addEventListener('change', updateSettingsFloatingBar);

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                submitSettingsForm();
            });

            updateSettingsFloatingBar();
        }

        document.addEventListener(
            'DOMContentLoaded',
            async function() {
                initRetentionPasswordConfirmation();

                await window.initCustomSelects
                    ?.(document);

                initializeClinicVoiceClearButtons(
                    document
                );

                initSettingsFloatingActions();
            }
        );

        document.addEventListener('voice:refresh', function(event) {
            initializeClinicVoiceClearButtons(event && event.detail ? event.detail.root : document);
        });
    </script>
@endsection
