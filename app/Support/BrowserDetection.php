<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrowserDetection
{

    public static function deviceDetailsFromRequest(Request $request): array
    {
        $userAgent = (string) $request->userAgent();

        return [
            'browser_name' => self::detectFromRequest($request),
            'device_type' => self::detectDeviceType($userAgent),
            'device_name' => self::detectDeviceName($userAgent),
            'os_name' => self::detectOperatingSystem($userAgent),
        ];
    }

    public static function detectDeviceType(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return 'unknown';
        }

        return match (true) {
            Str::contains($userAgent, ['iPad', 'Tablet'], true) => 'tablet',

            Str::contains($userAgent, 'Android', true)
                && !Str::contains($userAgent, 'Mobile', true) => 'tablet',

            Str::contains($userAgent, ['iPhone', 'Android', 'Mobile'], true) => 'mobile',

            Str::contains($userAgent, [
                'Windows',
                'Macintosh',
                'Linux',
                'X11',
            ], true) => 'desktop',

            default => 'unknown',
        };
    }

    public static function detectOperatingSystem(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return 'Unknown OS';
        }

        return match (true) {
            Str::contains($userAgent, 'Windows NT 10.0', true) => 'Windows',
            Str::contains($userAgent, 'Windows', true) => 'Windows',

            Str::contains($userAgent, 'iPhone', true) => 'iOS',
            Str::contains($userAgent, 'iPad', true) => 'iPadOS',

            Str::contains($userAgent, 'Android', true) => 'Android',

            Str::contains($userAgent, 'Macintosh', true) => 'macOS',

            Str::contains($userAgent, ['Linux', 'X11'], true) => 'Linux',

            default => 'Unknown OS',
        };
    }

    public static function detectDeviceName(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return 'Unknown Device';
        }

        if (Str::contains($userAgent, 'iPhone', true)) {
            return 'iPhone';
        }

        if (Str::contains($userAgent, 'iPad', true)) {
            return 'iPad';
        }

        if (Str::contains($userAgent, 'Macintosh', true)) {
            return 'Mac';
        }

        if (Str::contains($userAgent, 'Android', true)) {
            if (
                preg_match(
                    '/Android\s[^;)]*;\s*([^;)]+?)(?:\s+Build\/[^;)]+)?[;)]/i',
                    $userAgent,
                    $matches
                )
            ) {
                $model = trim($matches[1]);

                $model = preg_replace('/\s+Build\/.*$/i', '', $model);

                if (
                    $model !== ''
                    && !in_array(strtolower($model), [
                        // Chrome's reduced Android user agent uses "K" as a
                        // generic model placeholder rather than a device name.
                        'k',
                        'wv',
                        'mobile',
                        'tablet',
                        'linux',
                    ], true)
                ) {
                    return $model;
                }
            }

            return Str::contains($userAgent, 'Mobile', true)
                ? 'Android Phone'
                : 'Android Tablet';
        }

        if (Str::contains($userAgent, 'Windows', true)) {
            return 'Windows PC';
        }

        if (Str::contains($userAgent, ['Linux', 'X11'], true)) {
            return 'Linux PC';
        }

        return 'Unknown Device';
    }

    public static function deviceNameForDisplay(?string $storedDeviceName, ?string $userAgent): string
    {
        $deviceName = trim((string) $storedDeviceName);

        if (
            $deviceName !== ''
            && !(
                strtolower($deviceName) === 'k'
                && Str::contains($userAgent, 'Android', true)
            )
        ) {
            return $deviceName;
        }

        return self::detectDeviceName($userAgent);
    }

    public static function detectFromRequest(Request $request): string
    {
        $browserNameHint = $request->hasSession()
            ? $request->session()->get('browser_name_hint')
            : null;

        $provided = self::normalizeBrowserName(
            $request->input('browser_name')
                ?? $request->header('X-Browser-Name')
                ?? $browserNameHint
        );

        if ($provided !== null) {
            return $provided;
        }

        $clientHint = self::detectFromClientHints($request->header('Sec-CH-UA'));

        if ($clientHint !== null) {
            return $clientHint;
        }

        return self::detectFromUserAgent($request->userAgent());
    }

    public static function detectFromUserAgent(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return 'Browser';
        }

        return match (true) {
            Str::contains($userAgent, 'Brave/', true) => 'Brave',
            Str::contains($userAgent, 'Edg/', true) => 'Edge',
            Str::contains($userAgent, 'OPR/', true) => 'Opera',
            Str::contains($userAgent, 'Firefox/', true) => 'Firefox',
            Str::contains($userAgent, 'Chrome/', true) => 'Chrome',
            Str::contains($userAgent, 'Safari/', true) => 'Safari',
            default => 'Browser',
        };
    }

    public static function normalizeBrowserName(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $browser = trim((string) $value);

        if ($browser === '') {
            return null;
        }

        $normalized = strtolower($browser);

        return match (true) {
            str_contains($normalized, 'brave') => 'Brave',
            str_contains($normalized, 'edge') || str_contains($normalized, 'edg') => 'Edge',
            str_contains($normalized, 'opera') || str_contains($normalized, 'opr') => 'Opera',
            str_contains($normalized, 'firefox') => 'Firefox',
            str_contains($normalized, 'chrome') || str_contains($normalized, 'chromium') => 'Chrome',
            str_contains($normalized, 'safari') => 'Safari',
            default => null,
        };
    }

    public static function detectFromClientHints(?string $clientHints): ?string
    {
        if (blank($clientHints)) {
            return null;
        }

        $normalized = strtolower($clientHints);

        return match (true) {
            str_contains($normalized, '"brave"') => 'Brave',
            str_contains($normalized, '"microsoft edge"') || str_contains($normalized, '"edge"') => 'Edge',
            str_contains($normalized, '"opera"') => 'Opera',
            str_contains($normalized, '"firefox"') => 'Firefox',
            str_contains($normalized, '"google chrome"') || str_contains($normalized, '"chromium"') => 'Chrome',
            str_contains($normalized, '"safari"') => 'Safari',
            default => null,
        };
    }
}
