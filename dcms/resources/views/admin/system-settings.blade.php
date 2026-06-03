@extends('layouts.admin')

@section('title', 'System Settings | PUP Taguig Dental Clinic')

@section('styles')
<style>
    :root {
    --crimson: #8B0000;
    --crimson-dark: #6b0000;
    --crimson-light: #fef2f2;
    --header-h: 64px;
    --sidebar-w: 240px;
  }
  /* Page Banner */
  .page-banner {
    background: linear-gradient(135deg, #6b0000 0%, #8B0000 60%, #c0392b 100%);
    padding: 1.75rem 2rem 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(139, 0, 0, .25);
    border-radius: 16px;
    margin-bottom: 1.5rem;
  }

  .page-banner::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }

  .page-banner-inner {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .page-title {
    font-size: 2rem;
    font-weight: 900;
    color: #fff;
  }

  .settings-view-toggle {
    display: inline-flex;
    align-items: center;
    background: #FAFAF9;
    border: 1.5px solid #E0DDD8;
    border-radius: 12px;
    padding: 3px;
    gap: 3px;
    height: 42px;
  }

  .settings-view-toggle-btn {
    width: 34px;
    height: 34px;
    padding: 0;
    border: none;
    background: transparent;
    color: #6b7280;
    border-radius: 9px;
    font-size: .82rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .15s ease;
    flex-shrink: 0;
  }

  .settings-view-toggle-btn:hover {
    background: #f3f4f6;
    color: #8B0000;
  }

  .settings-view-toggle-btn.active {
    background: #8B0000;
    color: #fff;
    box-shadow: 0 2px 8px rgba(139, 0, 0, .15);
  }

  .settings-view[hidden] {
    display: none !important;
  }

  .settings-grid-view {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.25rem;
  }

  .settings-grid-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f0eaea;
    box-shadow: 0 2px 12px rgba(139, 0, 0, .04);
    overflow: hidden;
  }

  .scrollbar-thin::-webkit-scrollbar {
    width: 6px;
  }

  .scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
  }

  .scrollbar-thin::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
  }

  #toastContainer {
    position: fixed !important;
    top: calc(var(--header-h, 64px) + 20px) !important;
    right: 20px !important;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
  }

  #toastContainer .toast {
    min-width: 300px;
    max-width: 360px;
    background: white !important;
    border-radius: 14px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, .18) !important;
    padding: 14px 18px 14px 16px !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px;
    opacity: 0;
    transform: translateX(340px);
    transition: all .35s cubic-bezier(.68, -.55, .265, 1.55);
    position: relative;
    overflow: hidden;
    pointer-events: all;
    flex-direction: row !important;
  }

  #toastContainer .toast::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
  }

  #toastContainer .toast.error::before {
    background: #8B0000 !important;
  }

  #toastContainer .toast.success::before {
    background: #15803d !important;
  }

  #toastContainer .toast.show {
    opacity: 1 !important;
    transform: translateX(0) !important;
  }

  #toastContainer .toast.hide {
    opacity: 0 !important;
    transform: translateX(340px) !important;
  }

  #toastContainer .toast-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  #toastContainer .toast.error .toast-icon-wrap {
    background: rgba(139, 0, 0, .08);
  }

  #toastContainer .toast.success .toast-icon-wrap {
    background: rgba(21, 128, 61, .08);
  }

  #toastContainer .toast-icon {
    font-size: 17px;
  }

  #toastContainer .toast.error .toast-icon {
    color: #8B0000 !important;
  }

  #toastContainer .toast.success .toast-icon {
    color: #15803d !important;
  }

  #toastContainer .toast-body {
    flex: 1;
    min-width: 0;
  }

  #toastContainer .toast-title {
    font-size: 13px;
    font-weight: 700;
    color: #1A0A0A !important;
  }

  #toastContainer .toast-msg {
    font-size: 12px;
    color: #888 !important;
    margin-top: 2px;
    line-height: 1.4;
  }

  #toastContainer .toast-close {
    background: none !important;
    border: none;
    cursor: pointer;
    color: #CCC;
    font-size: 13px;
    flex-shrink: 0;
    padding: 2px 4px;
  }

  .stat-mini {
    transition: transform .2s ease, box-shadow .2s ease;
  }

  .stat-mini:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, .1);
  }

  .settings-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: all .15s;
    font-size: .78rem;
    font-weight: 500;
    color: #4a5568;
    text-decoration: none;
    margin-bottom: 2px;
  }

  .settings-nav-item:hover {
    background: var(--crimson-light);
    color: var(--crimson);
  }

  .settings-nav-item.active {
    background: linear-gradient(135deg, var(--crimson) 0%, var(--crimson-dark) 100%);
    color: #fff;
    box-shadow: 0 3px 10px rgba(139, 0, 0, .25);
    font-weight: 600;
  }

  .settings-nav-item i {
    width: 16px;
    text-align: center;
    font-size: 12px;
    flex-shrink: 0;
  }

  .settings-nav-item .badge {
    padding: 1px 6px;
    border-radius: 999px;
    font-size: .58rem;
    font-weight: 700;
    margin-left: auto;
  }

  .settings-nav-item.active .badge {
    background: rgba(255, 255, 255, .25);
    color: #fff;
  }

  .settings-nav-item:not(.active) .badge {
    background: #f0f0f0;
    color: #888;
  }

  .settings-section {
    display: none;
  }

  .settings-section.active {
    display: block;
  }

  .form-label {
    font-size: .72rem;
    font-weight: 700;
    color: #5c5550;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: .4rem;
    display: block;
  }

  .form-ctrl {
    width: 100%;
    border: 1.5px solid #e8e2dd;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: .82rem;
    color: #1a1410;
    background: #fff;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    font-family: 'Inter', sans-serif;
  }

  .form-ctrl:focus {
    border-color: #8B0000;
    box-shadow: 0 0 0 3px rgba(139, 0, 0, .08);
  }

  .form-ctrl:disabled {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
    opacity: 1;
  }

  #tab-clinic .voice-input-wrap,
  #settingsGridSectionContent .voice-input-wrap {
    position: relative;
    width: 100%;
  }

  #tab-clinic .voice-input-wrap > .form-ctrl.has-voice-padding,
  #settingsGridSectionContent .voice-input-wrap > .form-ctrl.has-voice-padding {
    padding-right: 2.35rem;
  }

  #tab-clinic .voice-input-wrap > textarea.form-ctrl.has-voice-padding,
  #settingsGridSectionContent .voice-input-wrap > textarea.form-ctrl.has-voice-padding {
    padding-right: 2.35rem;
  }

  #tab-clinic .clinic-voice-row,
  #settingsGridSectionContent .clinic-voice-row {
    display: flex;
    align-items: stretch;
    gap: .55rem;
    width: 100%;
  }

  #tab-clinic .clinic-voice-row .voice-input-wrap,
  #settingsGridSectionContent .clinic-voice-row .voice-input-wrap {
    flex: 1 1 auto;
    min-width: 0;
  }

  #tab-clinic .clinic-voice-clear-btn,
  #settingsGridSectionContent .clinic-voice-clear-btn {
    border: none;
    background: transparent;
    color: #dc2626;
    font-size: .78rem;
    font-weight: 600;
    line-height: 1;
    padding: 0 .1rem;
    margin: 0;
    cursor: pointer;
    align-self: center;
    flex: 0 0 auto;
    transition: color .15s ease;
  }

  #tab-clinic .clinic-voice-row.is-textarea,
  #settingsGridSectionContent .clinic-voice-row.is-textarea {
    align-items: flex-start;
  }

  #tab-clinic .clinic-voice-row.is-textarea .clinic-voice-clear-btn,
  #settingsGridSectionContent .clinic-voice-row.is-textarea .clinic-voice-clear-btn {
    margin-top: 10px;
    align-self: flex-start;
  }

  #tab-clinic .clinic-voice-clear-btn.hidden,
  #settingsGridSectionContent .clinic-voice-clear-btn.hidden {
    display: none;
  }

  #tab-clinic .clinic-voice-clear-btn:hover,
  #settingsGridSectionContent .clinic-voice-clear-btn:hover {
    color: #991b1b;
  }

  #tab-clinic .voice-input-wrap .voice-mic-btn,
  #settingsGridSectionContent .voice-input-wrap .voice-mic-btn {
    position: absolute;
    right: 10px;
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
    color: #9ca3af; 
    cursor: not-allowed; 
    pointer-events: none; 
    z-index: 4;
    opacity: 0.6; 
  }

  #tab-clinic .voice-input-wrap > textarea.form-ctrl.is-voice-textarea + .voice-mic-btn,
  #settingsGridSectionContent .voice-input-wrap > textarea.form-ctrl.is-voice-textarea + .voice-mic-btn {
    top: 10px;
    transform: none;
  }

  #tab-clinic .voice-input-wrap [data-voice-status],
  #settingsGridSectionContent .voice-input-wrap [data-voice-status] {
    position: absolute;
    right: 0;
    top: -1.35rem;
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
    background: rgba(255, 255, 255, .92);
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
  }

  #tab-clinic .voice-input-wrap [data-voice-status].hidden,
  #settingsGridSectionContent .voice-input-wrap [data-voice-status].hidden {
    display: none;
  }

  #tab-clinic .voice-input-wrap [data-voice-status].is-listening,
  #settingsGridSectionContent .voice-input-wrap [data-voice-status].is-listening {
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: #eff6ff;
  }

  #tab-clinic .voice-input-wrap [data-voice-status].is-error,
  #settingsGridSectionContent .voice-input-wrap [data-voice-status].is-error {
    color: #b91c1c;
    border-color: #fecaca;
    background: #fef2f2;
  }

  #tab-clinic .voice-input-wrap [data-voice-status].is-success,
  #settingsGridSectionContent .voice-input-wrap [data-voice-status].is-success {
    color: #166534;
    border-color: #bbf7d0;
    background: #f0fdf4;
  }

  .setting-note {
    font-size: .68rem;
    color: #9ca3af;
    margin-top: 6px;
  }

  .form-sel {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 16px;
    padding-right: 32px;
  }

  .setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid #f8f4f4;
  }

  .setting-row:last-child {
    border-bottom: none;
  }

  .setting-row-info {
    flex: 1;
    min-width: 0;
    padding-right: 1rem;
  }

  .setting-row-label {
    font-size: .82rem;
    font-weight: 700;
    color: #1a1410;
  }

  .setting-row-desc {
    font-size: .72rem;
    color: #9ca3af;
    margin-top: 2px;
  }

  .toggle-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
  }

  .toggle-wrap input {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
  }

  .toggle-slider {
    width: 42px;
    height: 24px;
    background: #e5e7eb;
    border-radius: 999px;
    transition: background .2s;
    position: relative;
    display: block;
  }

  .toggle-slider::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 18px;
    height: 18px;
    background: #fff;
    border-radius: 50%;
    transition: transform .2s;
    box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
  }

  .toggle-wrap input:checked + .toggle-slider {
    background: #8B0000;
  }

  .toggle-wrap input:checked + .toggle-slider::after {
    transform: translateX(18px);
  }

  .permission-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: .68rem;
    font-weight: 600;
    background: #fef2f2;
    color: var(--crimson);
    border: 1px solid #fce8e8;
    cursor: pointer;
    transition: all .15s;
    user-select: none;
  }

  .permission-chip:hover,
  .permission-chip.active {
    background: var(--crimson);
    color: #fff;
    border-color: var(--crimson);
  }

  .section-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f0eaea;
    box-shadow: 0 2px 12px rgba(139, 0, 0, .04);
    overflow: hidden;
    margin-bottom: 1.25rem;
  }

  .section-card-hdr {
    padding: 14px 20px;
    border-bottom: 1px solid #f8f4f4;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .section-card-hdr-left {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .section-card-body {
    padding: 20px;
  }

  .badge-online {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: .68rem;
    font-weight: 700;
  }

  .badge-offline {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: .68rem;
    font-weight: 700;
  }

  [data-theme="dark"] .section-card {
    background: #161b22;
    border-color: #21262d;
  }

  [data-theme="dark"] .section-card-hdr {
    background: #0d1117;
    border-color: #21262d;
  }

  [data-theme="dark"] .setting-row {
    border-color: #21262d;
  }

  [data-theme="dark"] .setting-row-label {
    color: #e5e7eb;
  }

  [data-theme="dark"] .form-ctrl {
    background: #0d1117;
    border-color: #30363d;
    color: #e6edf3;
  }

  [data-theme="dark"] #tab-clinic .voice-input-wrap [data-voice-status],
  [data-theme="dark"] #settingsGridSectionContent .voice-input-wrap [data-voice-status] {
    background: rgba(13, 17, 23, .92);
    border-color: #30363d;
  }

  [data-theme="dark"] .settings-nav-item {
    color: #d1d5db;
  }

  [data-theme="dark"] .settings-nav-item:hover {
    background: rgba(139, 0, 0, .2);
    color: #fff;
  }

  [data-theme="dark"] #toastContainer .toast {
    background: #161b22 !important;
  }

  [data-theme="dark"] #toastContainer .toast-title {
    color: #e5e7eb !important;
  }

  [data-theme="dark"] #toastContainer .toast-msg {
    color: #9ca3af !important;
  }

  @media (max-width: 767px) {
    .settings-page {
      margin-left: 0;
      padding: 82px 1rem 2rem;
    }

    #settingsListView {
      display: none !important;
    }

    #settingsGridView {
      display: block !important;
    }

    #settingsViewToggle {
      display: none !important;
    }

    .settings-grid-view {
      grid-template-columns: 1fr;
    }
  }
</style>
@endsection

@section('content')
<div id="toastContainer" role="region" aria-live="polite"></div>

<main id="mainContent" class="px-4 sm:px-6 pt-[82px] pb-8 min-h-[calc(100vh-82px)]">
  <div class="max-w-[1280px] mx-auto">

    <div class="page-banner">
      <div class="page-banner-inner">

        <div>
          <h1 class="page-title">System Settings</h1>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <button type="button"
            onclick="submitSettingsForm();"
            class="flex items-center gap-2 bg-white hover:bg-gray-100 text-[#8B0000]
            px-5 py-2.5 rounded-lg font-semibold text-sm shadow transition-all">
            <i class="fa-solid fa-floppy-disk"></i>
            Save Changes
          </button>

          <div class="settings-view-toggle" id="settingsViewToggle">
            <button type="button"
              class="settings-view-toggle-btn active"
              id="settingsListViewBtn"
              title="List view"
              aria-label="List view">
              <i class="fa-solid fa-table-list"></i>
            </button>
            <button type="button"
              class="settings-view-toggle-btn"
              id="settingsGridViewBtn"
              title="Grid view"
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
          ['icon'=>'fa-circle-check','color'=>'from-green-500 to-green-600','val'=>'Online','label'=>'System Status','sub'=>'Settings module active'],
          ['icon'=>'fa-sliders','color'=>'from-[#8B0000] to-[#6B0000]','val'=>'Live','label'=>'Configuration','sub'=>'Changes saved to database'],
          ['icon'=>'fa-bell','color'=>'from-blue-500 to-blue-600','val'=>'Ready','label'=>'Notifications','sub'=>'Preferences configurable'],
          ['icon'=>'fa-database','color'=>'from-amber-500 to-amber-600','val'=>'Backup','label'=>'Data Settings','sub'=>'Retention and schedule'],
        ];
      @endphp

      @foreach($miniCards as $card)
        <div class="stat-mini bg-white rounded-xl p-4 shadow border border-gray-100 overflow-hidden relative">
          <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-gray-100 to-transparent rounded-full -mr-10 -mt-10"></div>
          <div class="relative">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $card['color'] }} flex items-center justify-center shadow mb-3">
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
              <a href="#" class="settings-nav-item active" onclick="switchTab('general', this); return false;"><i class="fa-solid fa-sliders"></i> General</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('clinic', this); return false;"><i class="fa-solid fa-hospital"></i> Clinic Info</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('notifications', this); return false;"><i class="fa-solid fa-bell"></i> Notifications</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('security', this); return false;"><i class="fa-solid fa-shield-halved"></i> Security</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('email', this); return false;"><i class="fa-solid fa-envelope"></i> Email / SMTP</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('backup', this); return false;"><i class="fa-solid fa-database"></i> Backup & Data</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('integrations', this); return false;"><i class="fa-solid fa-plug"></i> Integrations</a>
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
                        <option {{ old('language', $settings['language']->value ?? 'English (US)') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-ctrl form-sel" disabled>
                      @foreach(['Asia/Manila (UTC+8)', 'UTC'] as $opt)
                        <option {{ old('timezone', $settings['timezone']->value ?? 'Asia/Manila (UTC+8)') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                      @endforeach
                    </select>
                    <div class="setting-note">This setting is locked for now.</div>
                  </div>

                  <div>
                    <label class="form-label">Date Format</label>
                    <select name="date_format" class="form-ctrl form-sel" disabled>
                      @foreach(['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD'] as $opt)
                        <option {{ old('date_format', $settings['date_format']->value ?? 'MM/DD/YYYY') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                      @endforeach
                    </select>
                    <div class="setting-note">This setting is locked for now.</div>
                  </div>

                   <div>
                    <label class="form-label">Time Format</label>
                    <select name="time_format" class="form-ctrl form-sel" disabled>
                      @foreach(['12-hour (AM/PM)', '24-hour'] as $opt)
                        <option {{ old('time_format', $settings['time_format']->value ?? '12-hour (AM/PM)') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
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
                    <input type="checkbox" name="maintenance_mode" value="1" {{ old('maintenance_mode', $settings['maintenance_mode']->value ?? '0') === '1' ? 'checked' : '' }}>
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
                    <input type="checkbox" name="debug_mode" value="1" {{ old('debug_mode', $settings['debug_mode']->value ?? '0') === '1' ? 'checked' : '' }}>
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
                      value="{{ old('clinic_name', $settings['clinic_name']->value ?? 'PUP Taguig Dental Clinic') }}" disabled>
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
                    <input type="text" name="accreditation_no" class="form-ctrl" placeholder="License / accreditation number"
                      value="{{ old('accreditation_no', $settings['accreditation_no']->value ?? '') }}" disabled>
                    <div class="setting-note">This setting is locked for now.</div>
                  </div>
                </div>

                <div>
                  <label class="form-label">Clinic Description</label>
                  <textarea name="description" class="form-ctrl resize-none" rows="3"
                    placeholder="Short description shown on patient portal…" disabled>{{ old('description', $settings['description']->value ?? 'The PUP Taguig Dental Clinic provides free dental services to PUP students, faculty, and staff.') }}</textarea>
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
                    <input type="checkbox" name="notif_new_appointment" value="1" {{ old('notif_new_appointment', $settings['notif_new_appointment']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                    <input type="checkbox" name="notif_cancellation" value="1" {{ old('notif_cancellation', $settings['notif_cancellation']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                    <input type="checkbox" name="notif_document_request" value="1" {{ old('notif_document_request', $settings['notif_document_request']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                      <input type="checkbox" name="notif_appointment_completed" value="1" {{ old('notif_appointment_completed', $settings['notif_appointment_completed']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                      <input type="checkbox" name="notif_rescheduled" value="1" {{ old('notif_rescheduled', $settings['notif_rescheduled']->value ?? '1') === '1' ? 'checked' : '' }}>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>

                  <div class="setting-row">
                    <div class="setting-row-info">
                      <div class="setting-row-label">Document Request Approved</div>
                      <div class="setting-row-desc">Notify patient when a document request is approved and ready for pickup</div>
                    </div>
                    <label class="toggle-wrap">
                      <input type="hidden" name="notif_document_approved" value="0">
                      <input type="checkbox" name="notif_document_approved" value="1" {{ old('notif_document_approved', $settings['notif_document_approved']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                      <input type="checkbox" name="notif_document_rejected" value="1" {{ old('notif_document_rejected', $settings['notif_document_rejected']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                      <input type="checkbox" name="notif_reminder_24h" value="1" {{ old('notif_reminder_24h', $settings['notif_reminder_24h']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                      <input type="checkbox" name="notif_confirmation" value="1" {{ old('notif_confirmation', $settings['notif_confirmation']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                      <input type="checkbox" name="notif_followup" value="1" {{ old('notif_followup', $settings['notif_followup']->value ?? '0') === '1' ? 'checked' : '' }}>
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
                      <label class="permission-chip {{ in_array($channel, $savedChannelsArray, true) ? 'active' : '' }}">
                        <input type="checkbox" name="notif_channels[]" value="{{ $channel }}" class="hidden"
                          {{ in_array($channel, $savedChannelsArray, true) ? 'checked' : '' }}
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
                        <option {{ old('backup_frequency', $settings['backup_frequency']->value ?? 'Daily') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
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
                        <option {{ old('backup_storage', $settings['backup_storage']->value ?? 'Local Server') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
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
                    <input type="checkbox" name="auto_backup_enabled" value="1" {{ old('auto_backup_enabled', $settings['auto_backup_enabled']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                    <input type="checkbox" name="backup_include_files" value="1" {{ old('backup_include_files', $settings['backup_include_files']->value ?? '1') === '1' ? 'checked' : '' }}>
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
                    <input type="checkbox" name="backup_encrypt" value="1" {{ old('backup_encrypt', $settings['backup_encrypt']->value ?? '1') === '1' ? 'checked' : '' }}>
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

          <div class="bg-white rounded-xl shadow border border-gray-100 px-5 py-4 flex items-center justify-between mt-4">
            <p class="text-xs text-gray-400">
              Changes are saved per section. Remember to click <strong class="text-gray-600">Save Changes</strong> before navigating away.
            </p>
            <div class="flex items-center gap-3">
              <button type="button" onclick="resetSettingsNotice()"
                class="text-sm font-semibold text-gray-500 hover:text-gray-700 px-4 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition-all">
                Reset to Defaults
              </button>
              <button type="button" onclick="submitSettingsForm();"
                class="flex items-center gap-2 bg-[#8B0000] hover:bg-[#760000] text-white px-5 py-2 rounded-xl font-semibold text-sm shadow transition-all">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
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
              <a href="#" class="settings-nav-item active" onclick="switchTab('general', this); syncSettingsGridTabs('general'); return false;"><i class="fa-solid fa-sliders"></i> General</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('clinic', this); syncSettingsGridTabs('clinic'); return false;"><i class="fa-solid fa-hospital"></i> Clinic Info</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('notifications', this); syncSettingsGridTabs('notifications'); return false;"><i class="fa-solid fa-bell"></i> Notifications</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('security', this); syncSettingsGridTabs('security'); return false;"><i class="fa-solid fa-shield-halved"></i> Security</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('email', this); syncSettingsGridTabs('email'); return false;"><i class="fa-solid fa-envelope"></i> Email / SMTP</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('backup', this); syncSettingsGridTabs('backup'); return false;"><i class="fa-solid fa-database"></i> Backup & Data</a>
              <a href="#" class="settings-nav-item" onclick="switchTab('integrations', this); syncSettingsGridTabs('integrations'); return false;"><i class="fa-solid fa-plug"></i> Integrations</a>
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
    </form>
  </div>
</main>
@endsection

@section('scripts')
<script>
  function showToast(title, message, type = 'error') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast ' + type;

    const icon = type === 'error'
      ? '<i class="fa-solid fa-circle-exclamation toast-icon"></i>'
      : '<i class="fa-solid fa-circle-check toast-icon"></i>';

    toast.innerHTML = `
      <div class="toast-icon-wrap">${icon}</div>
      <div class="toast-body">
        <div class="toast-title">${title}</div>
        <div class="toast-msg">${message}</div>
      </div>
      <button class="toast-close" type="button" onclick="closeToast(this)">
        <i class="fa-solid fa-xmark"></i>
      </button>
    `;

    container.appendChild(toast);
    requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

    setTimeout(() => {
      if (!toast.isConnected) return;
      toast.classList.remove('show');
      toast.classList.add('hide');

      setTimeout(() => {
        if (toast.isConnected) toast.remove();
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

  function resetSettingsNotice() {
    showToast('Notice', 'Reset to defaults is not connected yet.', 'error');
  }

  function getActiveSettingsTabId() {
    const activeSection = document.querySelector('#settingsListView .settings-section.active');
    if (!activeSection) return 'general';

    return activeSection.id.replace('tab-', '');
  }

  function setActiveNav(tabId, clickedItem = null) {
    document.querySelectorAll('#settingsListView .settings-nav-item, #settingsGridView .settings-nav-item').forEach(item => {
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
    renderSettingsGridActiveSection();
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

  function syncSettingsFormBeforeSubmit() {
    const listView = document.getElementById('settingsListView');
    const gridView = document.getElementById('settingsGridView');

    if (!listView || !gridView) return;

    const isGridActive = !gridView.hidden;

    listView.querySelectorAll('input, select, textarea, button').forEach((field) => {
      field.disabled = isGridActive;
    });

    gridView.querySelectorAll('input, select, textarea, button').forEach((field) => {
      field.disabled = !isGridActive;
    });
  }

  function submitSettingsForm() {
    const form = document.getElementById('settingsForm');
    if (!form) return;

    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }

    syncSettingsFormBeforeSubmit();
    form.submit();
  }

  function prepareSettingsFormSubmit() {
    const form = document.getElementById('settingsForm');

    if (!form || form.dataset.submitBound === '1') return;

    form.dataset.submitBound = '1';
    form.addEventListener('submit', syncSettingsFormBeforeSubmit);
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSettingsViewToggle();
    renderSettingsGridActiveSection();
    initializeClinicVoiceClearButtons(document);
    prepareSettingsFormSubmit();

    window.addEventListener('resize', function () {
      applySettingsView(getPreferredSettingsView(), false);
    });
  });

  document.addEventListener('voice:refresh', function (event) {
    initializeClinicVoiceClearButtons(event && event.detail ? event.detail.root : document);
  });
</script>
@endsection
