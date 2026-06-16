<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SystemSettingsController extends Controller
{
    private const BOOLEAN_KEYS = [
        'maintenance_mode',
        'debug_mode',

        'notif_new_appointment',
        'notif_cancellation',
        'notif_document_request',
        'notif_appointment_completed',
        'notif_rescheduled',
        'notif_document_approved',
        'notif_document_rejected',
        'notif_reminder_24h',
        'notif_confirmation',
        'notif_follow_up_reminder',
        'notif_follow_up_today_reminder',

        'auto_backup_enabled',
        'backup_include_files',
        'backup_encrypt',
    ];

    private const ALLOWED_GROUPS = [
        'general' => [
            'language',
            'maintenance_mode',
            'debug_mode',
        ],

        'clinic' => [
            'clinic_name',
            'contact_number',
            'email_address',
            'address',
            'operating_since',
            'accreditation_no',
            'description',
        ],

        'notifications' => [
            'notif_new_appointment',
            'notif_cancellation',
            'notif_document_request',
            'notif_appointment_completed',
            'notif_rescheduled',
            'notif_document_approved',
            'notif_document_rejected',
            'notif_reminder_24h',
            'notif_confirmation',
            'notif_follow_up_reminder',
            'notif_follow_up_today_reminder',
            'notif_channels',
        ],

        'backup' => [
            'backup_frequency',
            'backup_retention_days',
            'backup_storage',
            'backup_time',
            'auto_backup_enabled',
            'backup_include_files',
            'backup_encrypt',
        ],
    ];

    public function index()
    {
        if (!session('admin_logged_in')) {
            return redirect('/admin/login');
        }

        $settings = SystemSetting::query()->get()->keyBy('key');
        $notifications = collect([]);

        AuditLogger::log(
            'view',
            'system_settings',
            'Admin viewed the system settings page'
        );

        return view('admin.system-settings', compact('settings', 'notifications'));
    }

    public function update(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect('/admin/login');
        }

        $validated = $request->validate($this->rules(), $this->messages());

        foreach (self::ALLOWED_GROUPS as $group => $keys) {
            foreach ($keys as $key) {
                $value = $this->resolveValue($request, $validated, $key);

                SystemSetting::setSetting($key, $value, $group);
            }
        }

        AuditLogger::log(
            'update',
            'system_settings',
            'Admin updated system settings'
        );

        return redirect()
            ->route('admin.system_settings')
            ->with('success', 'Settings saved successfully.');
    }

    private function resolveValue(Request $request, array $validated, string $key): string
    {
        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return $request->boolean($key) ? '1' : '0';
        }

        if ($key === 'notif_channels') {
            $channels = $validated['notif_channels'] ?? [];

            return empty($channels) ? '' : implode(',', $channels);
        }

        $value = $validated[$key] ?? '';

        if (is_array($value)) {
            return implode(',', $value);
        }

        return trim((string) $value);
    }

    private function rules(): array
    {
        return [
            'language' => ['nullable', Rule::in(['English (US)', 'Filipino'])],

            'maintenance_mode' => ['nullable', 'boolean'],
            'debug_mode' => ['nullable', 'boolean'],

            'clinic_name' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email_address' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'operating_since' => ['nullable', 'string', 'max:50'],
            'accreditation_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],

            'notif_new_appointment' => ['nullable', 'boolean'],
            'notif_cancellation' => ['nullable', 'boolean'],
            'notif_document_request' => ['nullable', 'boolean'],
            'notif_appointment_completed' => ['nullable', 'boolean'],
            'notif_rescheduled' => ['nullable', 'boolean'],
            'notif_document_approved' => ['nullable', 'boolean'],
            'notif_document_rejected' => ['nullable', 'boolean'],
            'notif_reminder_24h' => ['nullable', 'boolean'],
            'notif_confirmation' => ['nullable', 'boolean'],
            'notif_follow_up_reminder' => ['nullable', 'boolean'],
            'notif_follow_up_today_reminder' => ['nullable', 'boolean'],

            'notif_channels' => ['nullable', 'array'],
            'notif_channels.*' => [Rule::in(['Email', 'SMS', 'WhatsApp', 'In-App'])],

            'backup_frequency' => ['nullable', Rule::in(['Every 6 hours', 'Daily', 'Weekly', 'Monthly'])],
            'backup_retention_days' => ['nullable', 'integer', 'min:7', 'max:365'],
            'backup_storage' => ['nullable', Rule::in(['Local Server', 'Google Drive', 'AWS S3'])],
            'backup_time' => ['nullable', 'date_format:H:i'],
            'auto_backup_enabled' => ['nullable', 'boolean'],
            'backup_include_files' => ['nullable', 'boolean'],
            'backup_encrypt' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'email_address.email' => 'Please enter a valid clinic email address.',
            'backup_retention_days.integer' => 'Backup retention must be a valid number of days.',
            'backup_retention_days.min' => 'Backup retention must be at least 7 days.',
            'backup_retention_days.max' => 'Backup retention cannot exceed 365 days.',
            'backup_time.date_format' => 'Backup time must use the HH:MM format.',
            'notif_channels.*.in' => 'One or more selected notification channels are invalid.',
        ];
    }
}
