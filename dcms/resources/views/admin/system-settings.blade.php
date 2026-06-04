@extends('layouts.admin')

@section('title', 'System Settings | PUP Taguig Dental Clinic')

@section('content')

<main id="mainContent" class="system-settings-page admin-page-shell page-enter">
  <div class="w-full">

    <div class="page-banner">
      <div class="page-banner-inner">

        <div>
          <h1 class="page-title page-banner-title">System Settings</h1>
          <p class="page-subtitle">Manage notification preferences, backups, and system behavior.</p>
        </div>

        <div class="settings-banner-actions">

          <button type="button" id="settingsBannerSaveBtn" data-settings-save onclick="submitSettingsForm();"
            class="ui-btn ui-btn-secondary settings-banner-save">
            <i class="fa-solid fa-floppy-disk"></i>
            Save Changes
          </button>
        </div>
      </div>
    </div>

    <form id="settingsForm" action="{{ route('admin.system_settings.update') }}" method="POST">
      @csrf

      <div class="settings-view" id="settingsListView">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

          <div class="settings-sidebar-col lg:col-span-1">
            <div class="settings-sidebar-card sticky top-[82px]">
              <div class="settings-sidebar-head">
                <p class="settings-sidebar-kicker">Settings Menu</p>
              </div>
              <div class="p-2">
                <a href="#" class="settings-nav-item active" onclick="switchTab('general', this); return false;"><i
                    class="fa-solid fa-sliders"></i> General</a>
                {{-- Temporarily hidden: Clinic Info
                <a href="#" class="settings-nav-item" onclick="switchTab('clinic', this); return false;"><i
                    class="fa-solid fa-hospital"></i> Clinic Info</a>
                --}}
                <a href="#" class="settings-nav-item" onclick="switchTab('notifications', this); return false;"><i
                    class="fa-solid fa-bell"></i> Notifications</a>
                {{-- Temporarily hidden: Security
                <a href="#" class="settings-nav-item" onclick="switchTab('security', this); return false;"><i
                    class="fa-solid fa-shield-halved"></i> Security</a>
                --}}
                {{-- Temporarily hidden: Email / SMTP
                <a href="#" class="settings-nav-item" onclick="switchTab('email', this); return false;"><i
                    class="fa-solid fa-envelope"></i> Email / SMTP</a>
                --}}
                <a href="#" class="settings-nav-item" onclick="switchTab('backup', this); return false;"><i
                    class="fa-solid fa-database"></i> Backup & Data</a>
                {{-- Temporarily hidden: Integrations
                <a href="#" class="settings-nav-item" onclick="switchTab('integrations', this); return false;"><i
                    class="fa-solid fa-plug"></i> Integrations</a>
                --}}
              </div>
            </div>
          </div>

          <div class="lg:col-span-3 space-y-0">

            <div id="tab-general" class="settings-section active">
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
                      <select name="language" class="form-ctrl form-sel">
                        @foreach(['English (US)', 'Filipino'] as $opt)
                        <option {{ old('language', $settings['language']->value ?? 'English (US)') === $opt ? 'selected'
                          : '' }}>{{ $opt }}</option>
                        @endforeach
                      </select>
                    </div>

                    <div>
                      <label class="form-label">Timezone</label>
                      <select name="timezone" class="form-ctrl form-sel" disabled>
                        @foreach(['Asia/Manila (UTC+8)', 'UTC'] as $opt)
                        <option {{ old('timezone', $settings['timezone']->value ?? 'Asia/Manila (UTC+8)') === $opt ?
                          'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                      </select>
                      <div class="setting-note">This setting is locked for now.</div>
                    </div>

                    <div>
                      <label class="form-label">Date Format</label>
                      <select name="date_format" class="form-ctrl form-sel" disabled>
                        @foreach(['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD'] as $opt)
                        <option {{ old('date_format', $settings['date_format']->value ?? 'MM/DD/YYYY') === $opt ?
                          'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                      </select>
                      <div class="setting-note">This setting is locked for now.</div>
                    </div>

                    <div>
                      <label class="form-label">Time Format</label>
                      <select name="time_format" class="form-ctrl form-sel" disabled>
                        @foreach(['12-hour (AM/PM)', '24-hour'] as $opt)
                        <option {{ old('time_format', $settings['time_format']->value ?? '12-hour (AM/PM)') === $opt ?
                          'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                      </select>
                      <div class="setting-note">This setting is locked for now.</div>
                    </div>
                  </div>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">Maintenance Mode</div>
                      <div class="setting-row-desc">Temporarily disable patient-facing booking portal</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="maintenance_mode" value="0">
                      <input type="checkbox" name="maintenance_mode" value="1" {{ old('maintenance_mode',
                        $settings['maintenance_mode']->value ?? '0') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">Debug Mode</div>
                      <div class="setting-row-desc">Enable detailed error logging for developers</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="debug_mode" value="0">
                      <input type="checkbox" name="debug_mode" value="1" {{ old('debug_mode',
                        $settings['debug_mode']->value ?? '0') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

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
                      <input type="text" name="clinic_name" class="form-ctrl" placeholder="Clinic name"
                        value="{{ old('clinic_name', $settings['clinic_name']->value ?? 'PUP Taguig Dental Clinic') }}"
                        disabled>
                      <div class="setting-note">This setting is locked for now.</div>
                    </div>

                    <div>
                      <label class="form-label">Contact Number</label>
                      <input type="text" name="contact_number" class="form-ctrl" placeholder="+63 ..."
                        value="{{ old('contact_number', $settings['contact_number']->value ?? '') }}" disabled>
                      <div class="setting-note">This setting is locked for now.</div>
                    </div>

                    <div>
                      <label class="form-label">Email Address</label>
                      <input type="email" name="email_address" class="form-ctrl" placeholder="email@example.com"
                        value="{{ old('email_address', $settings['email_address']->value ?? '') }}" disabled>
                      <div class="setting-note">This setting is locked for now.</div>
                    </div>

                    <div class="md:col-span-2">
                      <label class="form-label">Address</label>
                      <input type="text" name="address" class="form-ctrl" placeholder="Full address"
                        value="{{ old('address', $settings['address']->value ?? '') }}" disabled>
                      <div class="setting-note">This setting is locked for now.</div>
                    </div>

                    <div>
                      <label class="form-label">Operating Since</label>
                      <input type="text" name="operating_since" class="form-ctrl" placeholder="Year"
                        value="{{ old('operating_since', $settings['operating_since']->value ?? '1998') }}" disabled>
                      <div class="setting-note">This setting is locked for now.</div>
                    </div>

                    <div>
                      <label class="form-label">Accreditation No.</label>
                      <input type="text" name="accreditation_no" class="form-ctrl"
                        placeholder="License / accreditation number"
                        value="{{ old('accreditation_no', $settings['accreditation_no']->value ?? '') }}" disabled>
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

            <div id="tab-notifications" class="settings-section">
              <div class="section-card">
                <div class="section-card-hdr">
                  <div class="section-card-hdr-left">
                    <i class="fa-solid fa-bell text-[#8B0000]"></i>
                    <h2 class="font-bold text-gray-800 text-sm">Notification Preferences</h2>
                  </div>
                </div>
                <div class="section-card-body">
                  <p class="text-xs text-gray-400 mb-4 font-medium uppercase tracking-wide">Admin Alerts</p>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">New Appointment Booked</div>
                      <div class="setting-row-desc">Notify admin when a patient books an appointment</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="notif_new_appointment" value="0">
                      <input type="checkbox" name="notif_new_appointment" value="1" {{ old('notif_new_appointment',
                        $settings['notif_new_appointment']->value ?? '1') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">Appointment Cancellation</div>
                      <div class="setting-row-desc">Notify when a patient cancels or no-shows</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="notif_cancellation" value="0">
                      <input type="checkbox" name="notif_cancellation" value="1" {{ old('notif_cancellation',
                        $settings['notif_cancellation']->value ?? '1') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">Document Request Submitted</div>
                      <div class="setting-row-desc">Alert when a patient requests a dental certificate</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="notif_document_request" value="0">
                      <input type="checkbox" name="notif_document_request" value="1" {{ old('notif_document_request',
                        $settings['notif_document_request']->value ?? '1') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>

                  <div class="mt-5 mb-4 pt-4 border-t border-gray-50">
                    <p class="text-xs text-gray-400 mb-4 font-medium uppercase tracking-wide">Patient Status Updates</p>

                    <div class="setting-row">
                      <div class="setting-row-info">
                        <div class="setting-row-label">Appointment Completed</div>
                        <div class="setting-row-desc">Notify patient when an appointment is marked as completed</div>
                      </div>
                      <label class="toggle-wrap">
                        <input type="hidden" name="notif_appointment_completed" value="0">
                        <input type="checkbox" name="notif_appointment_completed" value="1" {{
                          old('notif_appointment_completed', $settings['notif_appointment_completed']->value ?? '1') ===
                        '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                      </label>
                    </div>

                    <div class="setting-row">
                      <div class="setting-row-info">
                        <div class="setting-row-label">Appointment Rescheduled</div>
                        <div class="setting-row-desc">Notify patient when an appointment schedule is changed</div>
                      </div>
                      <label class="toggle-wrap">
                        <input type="hidden" name="notif_rescheduled" value="0">
                        <input type="checkbox" name="notif_rescheduled" value="1" {{ old('notif_rescheduled',
                          $settings['notif_rescheduled']->value ?? '1') === '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                      </label>
                    </div>

                    <div class="setting-row">
                      <div class="setting-row-info">
                        <div class="setting-row-label">Document Request Approved</div>
                        <div class="setting-row-desc">Notify patient when a document request is approved and ready for
                          pickup</div>
                      </div>
                      <label class="toggle-wrap">
                        <input type="hidden" name="notif_document_approved" value="0">
                        <input type="checkbox" name="notif_document_approved" value="1" {{
                          old('notif_document_approved', $settings['notif_document_approved']->value ?? '1') === '1' ?
                        'checked' : '' }}>
                        <span class="toggle-slider"></span>
                      </label>
                    </div>

                    <div class="setting-row">
                      <div class="setting-row-info">
                        <div class="setting-row-label">Document Request Rejected</div>
                        <div class="setting-row-desc">Notify patient when a document request is rejected</div>
                      </div>
                      <label class="toggle-wrap">
                        <input type="hidden" name="notif_document_rejected" value="0">
                        <input type="checkbox" name="notif_document_rejected" value="1" {{
                          old('notif_document_rejected', $settings['notif_document_rejected']->value ?? '1') === '1' ?
                        'checked' : '' }}>
                        <span class="toggle-slider"></span>
                      </label>
                    </div>
                  </div>

                  <div class="mt-5 mb-4 pt-4 border-t border-gray-50">
                    <p class="text-xs text-gray-400 mb-4 font-medium uppercase tracking-wide">Patient Reminders</p>

                    <div class="setting-row">
                      <div class="setting-row-info">
                        <div class="setting-row-label">Appointment Reminder (24h before)</div>
                        <div class="setting-row-desc">Send an email/SMS to patient 24 hours before their slot</div>
                      </div>
                      <label class="toggle-wrap">
                        <input type="hidden" name="notif_reminder_24h" value="0">
                        <input type="checkbox" name="notif_reminder_24h" value="1" {{ old('notif_reminder_24h',
                          $settings['notif_reminder_24h']->value ?? '1') === '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                      </label>
                    </div>

                    <div class="setting-row">
                      <div class="setting-row-info">
                        <div class="setting-row-label">Appointment Confirmation</div>
                        <div class="setting-row-desc">Send confirmation email immediately after booking</div>
                      </div>
                      <label class="toggle-wrap">
                        <input type="hidden" name="notif_confirmation" value="0">
                        <input type="checkbox" name="notif_confirmation" value="1" {{ old('notif_confirmation',
                          $settings['notif_confirmation']->value ?? '1') === '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                      </label>
                    </div>

                    <div class="setting-row">
                      <div class="setting-row-info">
                        <div class="setting-row-label">Follow-up Reminder</div>
                        <div class="setting-row-desc">Remind patients of recommended follow-up schedule</div>
                      </div>
                      <label class="toggle-wrap">
                        <input type="hidden" name="notif_followup" value="0">
                        <input type="checkbox" name="notif_followup" value="1" {{ old('notif_followup',
                          $settings['notif_followup']->value ?? '0') === '1' ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                      </label>
                    </div>
                  </div>

                  <div class="mt-4">
                    <label class="form-label">Notification Channels</label>
                    <p class="text-xs text-gray-400 mb-2">Click to toggle. Selected channels will be saved.</p>
                    @php
                    $savedChannels = old('notif_channels', $settings['notif_channels']->value ?? 'Email,SMS,In-App');
                    $savedChannelsArray = is_array($savedChannels)
                    ? $savedChannels
                    : array_filter(array_map('trim', explode(',', (string) $savedChannels)));
                    @endphp
                    <div class="flex flex-wrap gap-2 mt-1">
                      @foreach(['Email','SMS','WhatsApp','In-App'] as $channel)
                      <label
                        class="permission-chip {{ in_array($channel, $savedChannelsArray, true) ? 'active' : '' }}">
                        <input type="checkbox" name="notif_channels[]" value="{{ $channel }}" class="hidden" {{
                          in_array($channel, $savedChannelsArray, true) ? 'checked' : '' }}
                          onchange="this.closest('label').classList.toggle('active', this.checked)">
                        @if($channel === 'Email')
                        <i class="fa-solid fa-envelope text-[10px]"></i>
                        @elseif($channel === 'SMS')
                        <i class="fa-solid fa-mobile-screen text-[10px]"></i>
                        @elseif($channel === 'WhatsApp')
                        <i class="fa-brands fa-whatsapp text-[10px]"></i>
                        @else
                        <i class="fa-solid fa-bell text-[10px]"></i>
                        @endif
                        {{ $channel }}
                      </label>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
            </div>

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

            <div id="tab-backup" class="settings-section">
              <div class="section-card">
                <div class="section-card-hdr">
                  <div class="section-card-hdr-left">
                    <i class="fa-solid fa-database text-[#8B0000]"></i>
                    <h2 class="font-bold text-gray-800 text-sm">Backup & Data Management</h2>
                  </div>
                </div>
                <div class="section-card-body">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label class="form-label">Auto Backup Frequency</label>
                      <select name="backup_frequency" class="form-ctrl form-sel">
                        @foreach(['Every 6 hours','Daily','Weekly','Monthly'] as $opt)
                        <option {{ old('backup_frequency', $settings['backup_frequency']->value ?? 'Daily') === $opt ?
                          'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div>
                      <label class="form-label">Backup Retention (days)</label>
                      <input type="number" name="backup_retention_days" class="form-ctrl" min="7" max="365"
                        value="{{ old('backup_retention_days', $settings['backup_retention_days']->value ?? 30) }}">
                    </div>
                    <div>
                      <label class="form-label">Backup Storage Location</label>
                      <select name="backup_storage" class="form-ctrl form-sel">
                        @foreach(['Local Server','Google Drive','AWS S3'] as $opt)
                        <option {{ old('backup_storage', $settings['backup_storage']->value ?? 'Local Server') === $opt
                          ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div>
                      <label class="form-label">Backup Time</label>
                      <div class="settings-timepicker-wrap">
                        <input type="text" name="backup_time" class="form-ctrl js-flatpickr-time"
                          value="{{ old('backup_time', $settings['backup_time']->value ?? '02:00') }}" readonly>
                        <i class="fa-regular fa-clock settings-timepicker-icon"></i>
                      </div>
                    </div>
                  </div>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">Auto Backup Enabled</div>
                      <div class="setting-row-desc">Automatically backup database on the schedule above</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="auto_backup_enabled" value="0">
                      <input type="checkbox" name="auto_backup_enabled" value="1" {{ old('auto_backup_enabled',
                        $settings['auto_backup_enabled']->value ?? '1') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">Include File Attachments</div>
                      <div class="setting-row-desc">Include uploaded documents and images in the backup</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="backup_include_files" value="0">
                      <input type="checkbox" name="backup_include_files" value="1" {{ old('backup_include_files',
                        $settings['backup_include_files']->value ?? '1') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">Encrypt Backups</div>
                      <div class="setting-row-desc">Use AES-256 encryption on all backup files</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="backup_encrypt" value="0">
                      <input type="checkbox" name="backup_encrypt" value="1" {{ old('backup_encrypt',
                        $settings['backup_encrypt']->value ?? '1') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>

                  <div class="pt-4 flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.data_backup') }}" class="settings-backup-manager-btn">
                      <i class="fa-solid fa-database"></i>
                      <span>Open Backup Manager</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>

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

            <div id="settingsFloatingActions" class="floating-save-bar settings-floating-save-bar" aria-live="polite">
              <div class="fsb-text">
                <span class="fsb-title">System controls</span>
                <span class="fsb-sub">Save changes before navigating away.</span>
              </div>

              <div class="fsb-actions">
                <button type="button" id="settingsResetDefaultsBtn" class="btn-reset-defaults"
                  onclick="resetSettingsToDefaults()">
                  <i class="fa-solid fa-rotate-left"></i>
                  Reset Defaults
                </button>

                <button type="button" id="settingsDiscardBtn" class="btn-discard" onclick="discardSettingsChanges()"
                  disabled>
                  Discard
                </button>

                <button type="button" id="settingsSaveFloatBtn" data-settings-save class="btn-save-float"
                  onclick="submitSettingsForm();">
                  <i class="fa-solid fa-floppy-disk"></i>
                  Save Changes
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </form>
  </div>
</main>
@endsection

@section('scripts')
<script>
  function getActiveSettingsTabId() {
    const activeSection = document.querySelector('#settingsListView .settings-section.active');
    if (!activeSection) return 'general';

    return activeSection.id.replace('tab-', '');
  }

  function setActiveNav(tabId, clickedItem = null) {
    document.querySelectorAll('#settingsListView .settings-nav-item').forEach(item => {
      const onclickAttr = item.getAttribute('onclick') || '';
      const isCurrent = clickedItem === item || onclickAttr.includes("'" + tabId + "'");

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
  }

  function initializeClinicVoiceClearButtons(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    const wrappers = scope.querySelectorAll('#tab-clinic .voice-input-wrap');

    wrappers.forEach((wrapper) => {
      if (wrapper.dataset.clinicVoiceClearReady === 'true') return;

      const field = wrapper.querySelector('input.form-ctrl, textarea.form-ctrl');
      if (!field || field.readOnly || field.disabled) return;

      const isTextarea = field.tagName.toLowerCase() === 'textarea';

      let row = wrapper.parentElement && wrapper.parentElement.classList.contains('clinic-voice-row')
        ? wrapper.parentElement
        : null;

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
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));

        if (status) status.classList.add('hidden');

        clearBtn.classList.add('hidden');
        field.focus();
      });

      field.addEventListener('input', toggleClear);
      toggleClear();

      wrapper.dataset.clinicVoiceClearReady = 'true';
    });
  }

  function closeSettingsCustomDropdowns(except = null) {
    document.querySelectorAll('.settings-custom-select.is-open').forEach(wrapper => {
      if (wrapper === except) return;
      wrapper.classList.remove('is-open');
      wrapper.querySelector('.settings-custom-select-btn')?.setAttribute('aria-expanded', 'false');
    });
  }

  function syncSettingsCustomSelects(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

    const wrappers = [];

    if (scope.matches && scope.matches('.settings-custom-select')) {
      wrappers.push(scope);
    }

    scope.querySelectorAll?.('.settings-custom-select').forEach(wrapper => {
      if (!wrappers.includes(wrapper)) wrappers.push(wrapper);
    });

    wrappers.forEach(wrapper => {
      const select = wrapper.querySelector('select');
      const valueText = wrapper.querySelector('[data-custom-select-value]');
      const button = wrapper.querySelector('.settings-custom-select-btn');

      if (!select || !valueText || !button) return;

      const selectedOption = select.options[select.selectedIndex];

      valueText.textContent = selectedOption?.textContent?.trim() || 'Select option';

      wrapper.classList.toggle('is-disabled', select.disabled);
      button.disabled = select.disabled;

      wrapper.querySelectorAll('.settings-custom-select-option').forEach(option => {
        const isActive = Number(option.dataset.index) === select.selectedIndex;

        option.classList.toggle('is-active', isActive);
        option.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
    });
  }

  function initSettingsCustomDropdowns(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

    scope.querySelectorAll('#settingsForm select.form-sel').forEach(select => {
      if (select.dataset.customDropdownReady === 'true') return;

      select.dataset.customDropdownReady = 'true';
      select.classList.add('settings-native-select');

      const wrapper = document.createElement('div');
      wrapper.className = 'settings-custom-select';

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'settings-custom-select-btn';
      button.setAttribute('aria-haspopup', 'listbox');
      button.setAttribute('aria-expanded', 'false');
      button.innerHTML = `
      <span data-custom-select-value></span>
      <i class="fa-solid fa-chevron-down"></i>
    `;

      const menu = document.createElement('div');
      menu.className = 'settings-custom-select-menu';
      menu.setAttribute('role', 'listbox');

      Array.from(select.options).forEach(option => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'settings-custom-select-option';
        item.dataset.value = option.value;
        item.dataset.index = String(option.index);
        item.setAttribute('role', 'option');

        const labelSpan = document.createElement('span');
        labelSpan.textContent = option.textContent.trim();

        const checkIcon = document.createElement('i');
        checkIcon.className = 'fa-solid fa-check settings-custom-select-check';
        checkIcon.setAttribute('aria-hidden', 'true');

        item.appendChild(labelSpan);
        item.appendChild(checkIcon);

        item.addEventListener('click', event => {
          event.preventDefault();
          event.stopPropagation();

          if (select.disabled) return;

          select.selectedIndex = option.index;

          select.dispatchEvent(new Event('input', { bubbles: true }));
          select.dispatchEvent(new Event('change', { bubbles: true }));

          wrapper.classList.remove('is-open');
          button.setAttribute('aria-expanded', 'false');

          syncSettingsCustomSelects(wrapper);
          updateSettingsFloatingBar();
        });

        menu.appendChild(item);
      });

      select.parentNode.insertBefore(wrapper, select);
      wrapper.appendChild(select);
      wrapper.appendChild(button);
      wrapper.appendChild(menu);

      button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();

        if (select.disabled) return;

        const willOpen = !wrapper.classList.contains('is-open');

        closeSettingsCustomDropdowns(wrapper);

        wrapper.classList.toggle('is-open', willOpen);
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });

      select.addEventListener('change', () => syncSettingsCustomSelects(wrapper));

      syncSettingsCustomSelects(wrapper);
    });
  }

  document.addEventListener('click', event => {
    if (event.target.closest('.settings-custom-select')) return;
    closeSettingsCustomDropdowns();
  });

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    closeSettingsCustomDropdowns();
  });

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
    debug_mode: '0',

    notif_new_appointment: '1',
    notif_cancellation: '1',
    notif_document_request: '1',
    notif_appointment_completed: '1',
    notif_rescheduled: '1',
    notif_document_approved: '1',
    notif_document_rejected: '1',
    notif_reminder_24h: '1',
    notif_confirmation: '1',
    notif_followup: '0',
    notif_channels: ['Email', 'SMS', 'In-App'],

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

  function syncSettingsVisualState() {
    document.querySelectorAll('#settingsForm .permission-chip input[type="checkbox"]').forEach(input => {
      input.closest('.permission-chip')?.classList.toggle('active', input.checked);
    });

    syncSettingsCustomSelects(document);
  }

  function mountSettingsFloatingBar() {
    const bar = document.getElementById('settingsFloatingActions');
    if (!bar) return;

    bar.classList.add('settings-floating-save-bar');

    if (bar.parentElement !== document.body) {
      document.body.appendChild(bar);
    }
  }

  function updateSettingsButtons() {
    const discardBtn = document.getElementById('settingsDiscardBtn');
    const resetBtn = document.getElementById('settingsResetDefaultsBtn');
    const saveButtons = document.querySelectorAll('[data-settings-save]');

    if (discardBtn) {
      discardBtn.disabled = !settingsIsDirty || settingsIsSubmitting;
      discardBtn.classList.toggle('is-disabled', discardBtn.disabled);
    }

    if (resetBtn) {
      resetBtn.disabled = settingsIsSubmitting;
      resetBtn.classList.toggle('is-disabled', resetBtn.disabled);
    }

    saveButtons.forEach(button => {
      if (!button.dataset.defaultHtml) {
        button.dataset.defaultHtml = button.innerHTML;
      }

      button.disabled = settingsIsSubmitting;
      button.classList.toggle('is-disabled', settingsIsSubmitting);

      button.innerHTML = settingsIsSubmitting
        ? '<i class="fa-solid fa-spinner spin"></i> Saving...'
        : button.dataset.defaultHtml;
    });
  }

  function updateSettingsFloatingBar() {
    const form = document.getElementById('settingsForm');
    const bar = document.getElementById('settingsFloatingActions');
    if (!form || !bar) return;

    settingsIsDirty = serializeSettingsForm(form) !== settingsInitialState;

    bar.classList.remove('show');
    bar.classList.toggle('is-dirty', settingsIsDirty);
    bar.classList.toggle('is-clean', !settingsIsDirty);

    if (!settingsIsDirty) {
      updateSettingsButtons();
      return;
    }

    bar.classList.add('show');

    const title = bar.querySelector('.fsb-title');
    const sub = bar.querySelector('.fsb-sub');

    if (title) {
      title.textContent = 'Unsaved changes';
    }

    if (sub) {
      sub.textContent = 'Review your changes before leaving this page.';
    }

    updateSettingsButtons();
  }

  function setNamedFieldValue(form, name, value) {
    const fields = Array.from(form.querySelectorAll('input, select, textarea'))
      .filter(field => field.name === name || field.name === `${name}[]`);

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
        field.checked = String(value) === String(field.value) || String(value) === '1' || value === true;
      } else {
        if (field._flatpickr && field.classList.contains('js-flatpickr-time')) {
          field._flatpickr.setDate(String(value), true, 'H:i');
        } else {
          field.value = value;
        }
      }

      field.dispatchEvent(new Event('input', { bubbles: true }));
      field.dispatchEvent(new Event('change', { bubbles: true }));
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
          ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
      });

      const contentType = response.headers.get('content-type') || '';
      const data = contentType.includes('application/json')
        ? await response.json().catch(() => null)
        : null;

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
        message: options.successMessage || data?.message || 'System settings were saved successfully.',
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

    mountSettingsFloatingBar();

    settingsInitialState = serializeSettingsForm(form);
    syncSettingsVisualState();

    document.querySelectorAll('[data-settings-save]').forEach(button => {
      button.dataset.defaultHtml = button.innerHTML;
    });

    form.addEventListener('input', updateSettingsFloatingBar);
    form.addEventListener('change', updateSettingsFloatingBar);

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      submitSettingsForm();
    });

    updateSettingsFloatingBar();
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSettingsCustomDropdowns(document);
    initializeClinicVoiceClearButtons(document);
    initSettingsFloatingActions();
  });

  document.addEventListener('voice:refresh', function (event) {
    initializeClinicVoiceClearButtons(event && event.detail ? event.detail.root : document);
  });
</script>
@endsection