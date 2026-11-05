<?php

namespace App\Services;

use App\Models\AiServiceLog;
use App\Models\SystemSetting;

class AiServiceManager
{
    private const VALID_MODES = ['normal', 'degraded', 'offline'];

    public function mode(): string
    {
        $configuredMode = strtolower(trim((string) setting('ai_mode', config('ai.mode', 'normal'))));

        return in_array($configuredMode, self::VALID_MODES, true)
            ? $configuredMode
            : 'normal';
    }

    public function featureEnabled(string $feature): bool
    {
        $default = (bool) config("ai.features.{$feature}.enabled", true);

        return SystemSetting::isEnabled("ai_{$feature}_enabled", $default);
    }

    public function shouldUse(string $feature): bool
    {
        return $this->mode() !== 'offline' && $this->featureEnabled($feature);
    }

    public function provider(string $feature): string
    {
        return (string) config("ai.features.{$feature}.provider", 'unknown');
    }

    public function recordSuccess(string $feature, ?string $message = null, array $context = []): void
    {
        $this->record($feature, 'success', $message, $context);
    }

    public function recordFailure(string $feature, ?string $message = null, array $context = []): void
    {
        $this->record($feature, 'failure', $message, $context);
    }

    public function recordFallback(string $feature, ?string $message = null, array $context = []): void
    {
        $this->record($feature, 'fallback', $message, $context);
    }

    private function record(string $feature, string $status, ?string $message, array $context): void
    {
        AiServiceLog::create([
            'feature' => $feature,
            'provider' => $this->provider($feature),
            'status' => $status,
            'mode' => $this->mode(),
            'message' => $message,
        ]);
    }
}
