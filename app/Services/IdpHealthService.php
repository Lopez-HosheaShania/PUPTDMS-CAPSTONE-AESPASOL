<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IdpHealthService
{
    private const CACHE_KEY = 'idp_health_status';

    public function status(): array
    {
        $ttl = max(5, (int) config('services.idp.health_cache_seconds', 30));

        return Cache::remember(self::CACHE_KEY, now()->addSeconds($ttl), function () {
            return $this->checkNow();
        });
    }

    public function isAvailable(): bool
    {
        return (bool) data_get($this->status(), 'available', false);
    }

    public function loginViewData(): array
    {
        $status = $this->status();

        return [
            'idpAvailable' => (bool) ($status['available'] ?? false),
            'idpStatusMessage' => (string) ($status['message'] ?? ''),
            'idpCheckedAt' => $status['checked_at'] ?? null,
        ];
    }

    private function checkNow(): array
    {
        if (filter_var(config('services.idp.force_down', false), FILTER_VALIDATE_BOOL)) {
            return [
                'available' => false,
                'message' => 'Primary SSO is temporarily unavailable. Forced locally for testing.',
                'checked_at' => now()->toIso8601String(),
                'forced' => true,
            ];
        }

        $healthUrl = trim((string) (
            config('services.idp.health_url')
            ?: config('services.idp.login_url')
            ?: config('services.oidc.authorize_url')
        ));

        if ($healthUrl === '') {
            return [
                'available' => false,
                'message' => 'Primary SSO is not configured.',
                'checked_at' => now()->toIso8601String(),
            ];
        }

        $timeout = max(1, (int) config('services.idp.health_timeout', 3));

        try {
            $response = Http::timeout($timeout)
                ->withoutRedirecting()
                ->acceptJson()
                ->get($healthUrl);

            $statusCode = $response->status();
            $available = ($statusCode >= 200 && $statusCode < 400) || in_array($statusCode, [401, 403], true);

            return [
                'available' => $available,
                'message' => $available
                    ? 'Primary SSO is available.'
                    : 'Primary SSO is temporarily unavailable.',
                'checked_at' => now()->toIso8601String(),
                'status_code' => $statusCode,
            ];
        } catch (ConnectionException $e) {
            Log::warning('IDP health check failed', [
                'url' => $healthUrl,
                'message' => $e->getMessage(),
            ]);

            return [
                'available' => false,
                'message' => 'Primary SSO is temporarily unavailable.',
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::warning('IDP health check errored', [
                'url' => $healthUrl,
                'message' => $e->getMessage(),
            ]);

            return [
                'available' => false,
                'message' => 'Primary SSO is temporarily unavailable.',
                'checked_at' => now()->toIso8601String(),
            ];
        }
    }
}
