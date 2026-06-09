<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting && $setting->value !== null
            ? $setting->value
            : $default;
    }

    public static function setSetting(string $key, mixed $value, string $group = 'general'): void
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
            ]
        );
    }

    public static function getMany(array $keys): Collection
    {
        return static::query()
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');
    }

    public static function isEnabled(string $key, bool $default = true): bool
    {
        $value = static::getSetting($key, $default ? '1' : '0');

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function notificationChannels(): array
    {
        $value = static::getSetting('notif_channels', 'Email,SMS,In-App');

        if (is_array($value)) {
            return $value;
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    public static function notificationChannelEnabled(string $channel, bool $default = true): bool
    {
        $channels = static::notificationChannels();

        if (empty($channels)) {
            return $default;
        }

        foreach ($channels as $savedChannel) {
            if (strtolower($savedChannel) === strtolower($channel)) {
                return true;
            }
        }

        return false;
    }

    public static function notificationVia(string $key): array
    {
        $defaults = [
            'notif_new_appointment' => ['database', 'broadcast'],
            'notif_appointment_cancelled' => ['database', 'broadcast'],
            'notif_document_request' => ['database', 'broadcast'],

            'notif_dentist_emergency_out' => ['database', 'broadcast'],
        ];

        return $defaults[$key] ?? ['database'];
    }
}