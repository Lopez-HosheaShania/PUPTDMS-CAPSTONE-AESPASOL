@extends('layouts.admin')

@section('title', 'System Settings | PUP Taguig Dental Clinic')

@section('content')
<div id="toastContainer" role="region" aria-live="polite"></div>

<main id="mainContent" class="admin-page-shell">
  <div class="admin-page-container">

    <div class="page-banner">
      <div class="page-banner-inner">

        <div>
          <h1 class="page-title">System Settings</h1>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <button type="button" onclick="document.getElementById('settingsForm').submit();" class="flex items-center gap-2 bg-white hover:bg-gray-100 text-[#8B0000]
            px-5 py-2.5 rounded-lg font-semibold text-sm shadow transition-all">
            <i class="fa-solid fa-floppy-disk"></i>
            Save Changes
          </button>

          <div class="settings-view-toggle" id="settingsViewToggle">
            <button type="button" class="settings-view-toggle-btn active" id="settingsListViewBtn" title="List view"
              aria-label="List view">
              <i class="fa-solid fa-table-list"></i>
            </button>
            <button type="button" class="settings-view-toggle-btn" id="settingsGridViewBtn" title="Grid view"
              aria-label="Grid view">
              <i class="fa-solid fa-grip"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      @php
      $miniCards = [
      ['icon'=>'fa-circle-check','color'=>'from-green-500 to-green-600','val'=>'Online','label'=>'System
      Status','sub'=>'Settings module active'],
      ['icon'=>'fa-sliders','color'=>'from-[#8B0000]
      to-[#6B0000]','val'=>'Live','label'=>'Configuration','sub'=>'Changes saved to database'],
      ['icon'=>'fa-bell','color'=>'from-blue-500
      to-blue-600','val'=>'Ready','label'=>'Notifications','sub'=>'Preferences configurable'],
      ['icon'=>'fa-database','color'=>'from-amber-500 to-amber-600','val'=>'Backup','label'=>'Data
      Settings','sub'=>'Retention and schedule'],
      ];
      @endphp

      @foreach($miniCards as $card)
      <div class="stat-mini bg-white rounded-xl p-4 shadow border border-gray-100 overflow-hidden relative">
        <div
          class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-gray-100 to-transparent rounded-full -mr-10 -mt-10">
        </div>
        <div class="relative">
          <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $card['color'] }} flex items-center justify-center shadow mb-3">
            <i class="fa-solid {{ $card['icon'] }} text-white text-sm"></i>
          </div>
          <p class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold mb-0.5">{{ $card['label'] }}</p>
          <p class="text-xl font-extrabold text-gray-800">{{ $card['val'] }}</p>
          <p class="text-[10px] text-gray-400 mt-0.5">{{ $card['sub'] }}</p>
        </div>
      </div>
      @endforeach
    </div>

    <form id="settingsForm" action="{{ route('admin.system_settings.update') }}" method="POST">
      @csrf

      <div class="settings-view" id="settingsListView">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

          <div class="settings-sidebar-col lg:col-span-1">
            <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden sticky top-[82px]">
              <div class="px-4 py-3 border-b bg-gray-50">
                <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Settings Menu</p>
              </div>
              <div class="p-2">
                <a href="#" class="settings-nav-item active" onclick="switchTab('general', this); return false;"><i
                    class="fa-solid fa-sliders"></i> General</a>
                <a href="#" class="settings-nav-item" onclick="switchTab('clinic', this); return false;"><i
                    class="fa-solid fa-hospital"></i> Clinic Info</a>
                <a href="#" class="settings-nav-item" onclick="switchTab('notifications', this); return false;"><i
                    class="fa-solid fa-bell"></i> Notifications</a>
                <a href="#" class="settings-nav-item" onclick="switchTab('security', this); return false;"><i
                    class="fa-solid fa-shield-halved"></i> Security</a>
                <a href="#" class="settings-nav-item" onclick="switchTab('email', this); return false;"><i
                    class="fa-solid fa-envelope"></i> Email / SMTP</a>
                <a href="#" class="settings-nav-item" onclick="switchTab('backup', this); return false;"><i
                    class="fa-solid fa-database"></i> Backup & Data</a>
                <a href="#" class="settings-nav-item" onclick="switchTab('integrations', this); return false;"><i
                    class="fa-solid fa-plug"></i> Integrations</a>
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
                    @php $savedChannels = old('notif_channels', $settings['notif_channels']->value ?? 'Email,SMS');
                    @endphp
                    <div class="flex flex-wrap gap-2 mt-1">
                      @foreach(['Email','SMS','WhatsApp','In-App'] as $channel)
                      <label class="permission-chip {{ str_contains($savedChannels, $channel) ? 'active' : '' }}">
                        <input type="checkbox" name="notif_channels[]" value="{{ $channel }}" class="hidden" {{
                          str_contains($savedChannels, $channel) ? 'checked' : '' }}
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
                      <input type="time" name="backup_time" class="form-ctrl"
                        value="{{ old('backup_time', $settings['backup_time']->value ?? '02:00') }}">
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

                  <div class="mt-5 pt-4 border-t border-gray-50 flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.data_backup') }}"
                      class="flex items-center gap-2 text-sm font-semibold text-[#8B0000] bg-red-50 border border-red-200 px-4 py-2 rounded-xl hover:bg-red-100 transition-all">
                      <i class="fa-solid fa-database text-xs"></i> Open Backup Manager
                    </a>
                  </div>
                </div>
              </div>
            </div>

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

            <div
              class="bg-white rounded-xl shadow border border-gray-100 px-5 py-4 flex items-center justify-between mt-4">
              <p class="text-xs text-gray-400">
                Changes are saved per section. Remember to click <strong class="text-gray-600">Save Changes</strong>
                before navigating away.
              </p>
              <div class="flex items-center gap-3">
                <button type="button" onclick="resetSettingsNotice()"
                  class="text-sm font-semibold text-gray-500 hover:text-gray-700 px-4 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition-all">
                  Reset to Defaults
                </button>
                <button type="button" onclick="document.getElementById('settingsForm').submit();"
                  class="flex items-center gap-2 bg-[#8B0000] hover:bg-[#760000] text-white px-5 py-2 rounded-xl font-semibold text-sm shadow transition-all">
                  <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </form>
    <div class="settings-view" id="settingsGridView" hidden>
      <div class="settings-grid-view">

        <div class="settings-grid-card">
          <div class="section-card-hdr">
            <div class="section-card-hdr-left">
              <i class="fa-solid fa-sliders text-[#8B0000]"></i>
              <h2 class="font-bold text-gray-800 text-sm">Settings Menu</h2>
            </div>
          </div>
          <div class="section-card-body">
            <div class="grid grid-cols-1 gap-2">
              <a href="#" class="settings-nav-item active"
                onclick="switchTab('general', this); syncSettingsGridTabs('general'); return false;"><i
                  class="fa-solid fa-sliders"></i> General</a>
              <a href="#" class="settings-nav-item"
                onclick="switchTab('clinic', this); syncSettingsGridTabs('clinic'); return false;"><i
                  class="fa-solid fa-hospital"></i> Clinic Info</a>
              <a href="#" class="settings-nav-item"
                onclick="switchTab('notifications', this); syncSettingsGridTabs('notifications'); return false;"><i
                  class="fa-solid fa-bell"></i> Notifications</a>
              <a href="#" class="settings-nav-item"
                onclick="switchTab('security', this); syncSettingsGridTabs('security'); return false;"><i
                  class="fa-solid fa-shield-halved"></i> Security</a>
              <a href="#" class="settings-nav-item"
                onclick="switchTab('email', this); syncSettingsGridTabs('email'); return false;"><i
                  class="fa-solid fa-envelope"></i> Email / SMTP</a>
              <a href="#" class="settings-nav-item"
                onclick="switchTab('backup', this); syncSettingsGridTabs('backup'); return false;"><i
                  class="fa-solid fa-database"></i> Backup & Data</a>
              <a href="#" class="settings-nav-item"
                onclick="switchTab('integrations', this); syncSettingsGridTabs('integrations'); return false;"><i
                  class="fa-solid fa-plug"></i> Integrations</a>
            </div>
          </div>
        </div>

        <div class="settings-grid-card">
          <div class="section-card-hdr">
            <div class="section-card-hdr-left">
              <i class="fa-solid fa-layer-group text-[#8B0000]"></i>
              <h2 class="font-bold text-gray-800 text-sm">Active Section</h2>
            </div>
          </div>
          <div class="section-card-body">
            <div id="settingsGridSectionContent"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
  function showToast(title, message, type = 'error') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;

    const icon = type === 'error'
      ? '<i class="fa-solid fa-circle-exclamation toast-icon"></i>'
      : '<i class="fa-solid fa-circle-check toast-icon"></i>';

    t.innerHTML = `
      <div class="toast-icon-wrap">${icon}</div>
      <div class="toast-body">
        <div class="toast-title">${title}</div>
        <div class="toast-msg">${message}</div>
      </div>
      <button class="toast-close" onclick="closeToast(this)">
        <i class="fa-solid fa-xmark"></i>
      </button>
    `;

    c.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));

    setTimeout(() => {
      if (!t.isConnected) return;
      t.classList.remove('show');
      t.classList.add('hide');
      setTimeout(() => {
        if (t.isConnected) t.remove();
      }, 400);
    }, 4500);
  }

  function closeToast(button) {
    const toast = button.closest('.toast');
    if (!toast) return;

    toast.classList.remove('show');
    toast.classList.add('hide');

    setTimeout(() => {
      if (toast.isConnected) toast.remove();
    }, 400);
  }

  function switchTab(tabId, el) {
    document.querySelectorAll('.settings-section').forEach(section => {
      section.classList.remove('active');
    });

    document.querySelectorAll('#settingsListView .settings-nav-item').forEach(item => {
      item.classList.remove('active');
    });

    document.querySelectorAll('#settingsGridView .settings-nav-item').forEach(item => {
      item.classList.remove('active');
    });

    const section = document.getElementById('tab-' + tabId);
    if (section) {
      section.classList.add('active');
    }

    if (el) {
      el.classList.add('active');
    }

    document.querySelectorAll('#settingsGridView .settings-nav-item').forEach(item => {
      const onclickAttr = item.getAttribute('onclick') || '';
      if (onclickAttr.includes("'" + tabId + "'")) {
        item.classList.add('active');
      }
    });

    renderSettingsGridActiveSection();
  }

  function resetSettingsNotice() {
    showToast('Notice', 'Reset to defaults is not connected yet.', 'error');
  }

  function getPreferredSettingsView() {
    if (window.innerWidth <= 767) return 'grid';
    return localStorage.getItem('systemSettingsView') || 'list';
  }

  function applySettingsView(view, save = true) {
    const listView = document.getElementById('settingsListView');
    const gridView = document.getElementById('settingsGridView');
    const listBtn = document.getElementById('settingsListViewBtn');
    const gridBtn = document.getElementById('settingsGridViewBtn');

    if (!listView || !gridView) return;

    const finalView = window.innerWidth <= 767 ? 'grid' : view;

    if (finalView === 'grid') {
      listView.hidden = true;
      gridView.hidden = false;
      renderSettingsGridActiveSection();
    } else {
      listView.hidden = false;
      gridView.hidden = true;
    }

    if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
    if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

    if (save && window.innerWidth > 767) {
      localStorage.setItem('systemSettingsView', finalView);
    }
  }

  function initSettingsViewToggle() {
    const listBtn = document.getElementById('settingsListViewBtn');
    const gridBtn = document.getElementById('settingsGridViewBtn');

    applySettingsView(getPreferredSettingsView(), false);

    if (listBtn && !listBtn.dataset.bound) {
      listBtn.dataset.bound = '1';
      listBtn.addEventListener('click', () => applySettingsView('list', true));
    }

    if (gridBtn && !gridBtn.dataset.bound) {
      gridBtn.dataset.bound = '1';
      gridBtn.addEventListener('click', () => applySettingsView('grid', true));
    }
  }

  function getActiveSettingsTabId() {
    const activeSection = document.querySelector('.settings-section.active');
    if (!activeSection) return 'general';
    return activeSection.id.replace('tab-', '');
  }

  function renderSettingsGridActiveSection() {
    const activeId = getActiveSettingsTabId();
    const source = document.getElementById('tab-' + activeId);
    const target = document.getElementById('settingsGridSectionContent');

    if (!source || !target) return;

    target.innerHTML = source.innerHTML;

    target.querySelectorAll('[data-voice-ready]').forEach((el) => {
      el.removeAttribute('data-voice-ready');
    });

    document.dispatchEvent(new CustomEvent('voice:refresh', {
      detail: { root: target }
    }));
  }

  function syncSettingsGridTabs(tabId) {
    setTimeout(() => {
      renderSettingsGridActiveSection();
    }, 0);
  }

  function initializeClinicVoiceClearButtons(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    const wrappers = scope.querySelectorAll('#tab-clinic .voice-input-wrap, #settingsGridSectionContent .voice-input-wrap');

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
        clearBtn.textContent = 'Clear';
        row.appendChild(clearBtn);
      }

      const status = wrapper.querySelector('[data-voice-status]');

      function toggleClear() {
        if ((field.value || '').trim().length > 0) {
          clearBtn.classList.remove('hidden');
        } else {
          clearBtn.classList.add('hidden');
        }
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

  document.addEventListener('DOMContentLoaded', function () {
    initSettingsViewToggle();
    renderSettingsGridActiveSection();
    initializeClinicVoiceClearButtons(document);

    window.addEventListener('resize', function () {
      applySettingsView(getPreferredSettingsView(), false);
    });
  });

  document.addEventListener('voice:refresh', function (event) {
    initializeClinicVoiceClearButtons(event && event.detail ? event.detail.root : document);
  });
</script>
@endsection