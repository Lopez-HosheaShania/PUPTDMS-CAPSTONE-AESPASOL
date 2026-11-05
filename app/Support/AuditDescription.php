<?php

namespace App\Support;

use Illuminate\Support\Str;

class AuditDescription
{
    public static function summarize(?string $text, ?string $action = null, ?string $module = null): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }
        $summary = trim($text);
        if ($module === 'data_retention') {
            $retentionSummary = self::retentionSummary($summary, $action);
            if ($retentionSummary !== null) {
                return $retentionSummary;
            }
        }
        [$prefix, $payload] = self::structuredPayload($summary);
        if (str_starts_with($payload, '{') || str_starts_with($payload, '[')) {
            if ($prefix === '') return Str::headline($action ?: 'Event').' — '.Str::headline($module ?: 'system').'.';
            $summary = $prefix;
        }
        if (preg_match('/^(.+? #\d+)(?: \((.*?)\))? (created|updated|deleted)(?: fields: .*)?\.$/s', $summary, $match)) {
            $summary = ucfirst($match[3]).' '.$match[1];
            if (($match[2] ?? '') !== '' && mb_strlen($match[2]) <= 60) {
                $summary .= ': '.$match[2];
            }
        } else {
            $summary = preg_replace('/^(?:Admin|Super admin|Dentist|Patient|Clinical user \([^)]+\)) (?=(?:viewed|logged|created|updated|deleted|exported|synced|archived)\b)/i', '', $summary);
            $summary = ucfirst($summary);
        }
        if (mb_strlen($summary) > 180) {
            // Full text is retained separately; never silently truncate the audit evidence.
            if (preg_match('/^(\d{3})\s*\|/', $summary, $match)) {
                return $match[1].' | Error recorded';
            }
            $summary = ucfirst(str_replace('_', ' ', $action ?: 'Event')).' — '.str_replace('_', ' ', $module ?: 'system');
        }

        return $summary;
    }

    public static function details(?string $text, ?string $action = null, ?string $module = null): ?string
    {
        if ($text === null || $text === '') return $text;
        [$prefix, $payload] = self::structuredPayload($text);
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return str_starts_with($payload, '{') || str_starts_with($payload, '[')
                ? self::summarize($text, $action, $module) : $text;
        }
        $summary = self::summarize($text, $action, $module);
        if ($module === 'data_retention' && self::retentionSummary(trim($text), $action) !== null) {
            // Review metadata is already explained by the sentence; do not repeat it.
            if (in_array($action, ['retention_review_viewed', 'retention_password_verification_failed'], true)) return $summary;
            $before = $data['previous']['policy'] ?? $data['previous'] ?? null;
            $after = $data['updated'] ?? null;
            $lines = [];
            $previousPeriod = self::retentionPeriod($before);
            $period = self::retentionPeriod($after);
            if ($previousPeriod !== null && $previousPeriod !== $period) $lines[] = 'Previous retention period: '.$previousPeriod;
            if ($period !== null) $lines[] = 'Saved retention period: '.$period;
            if (is_array($after)) {
                foreach (['counting_rule' => 'Count from', 'status' => 'Policy status',
                    'approval_reference' => 'Approval reference', 'effective_from' => 'Policy effective date'] as $key => $label) {
                    if (isset($after[$key]) && is_scalar($after[$key]) && $after[$key] !== '') {
                        $value = $after[$key];
                        if ($key === 'counting_rule') {
                            $category = is_string($data['category'] ?? null) ? $data['category'] : '';
                            $value = config('retention.categories.'.$category.'.counting_rules.'.$value) ?? Str::headline((string) $value);
                        } elseif ($key === 'status') {
                            $value = $value === 'provisional' ? 'Pending Review' : Str::headline((string) $value);
                        }
                        $lines[] = $label.': '.$value;
                    }
                }
            }

            return $lines === [] ? $summary : implode("\n", $lines);
        }

        $lines = self::detailLines($data);

        return $lines === [] ? $summary : implode("\n", $lines);
    }

    private static function structuredPayload(string $text): array
    {
        if (preg_match('/^(.*?)\s*\|\s*([\[{].*)$/s', trim($text), $match)) {
            return [trim($match[1]), trim($match[2])];
        }

        return ['', trim($text)];
    }

    private static function detailLines(array $data, string $prefix = ''): array
    {
        $lines = [];
        foreach ($data as $key => $value) {
            $label = is_int($key) ? 'Item '.($key + 1) : match ($key) {
                'previous' => 'Before change', 'updated' => 'After change',
                default => preg_replace('/\bId\b/', 'ID', Str::headline($key)),
            };
            $label = $prefix.$label;
            if (is_array($value)) {
                $lines = array_merge($lines, $value === [] ? [$label.': None recorded'] : self::detailLines($value, $label.' / '));
                continue;
            }
            $display = match (true) {
                $value === null => 'Not recorded',
                is_bool($value) => $value ? 'Yes' : 'No',
                in_array($key, ['status', 'outcome', 'module', 'coverage_type', 'counting_rule', 'source'], true)
                    && is_string($value) => Str::headline($value),
                default => (string) $value,
            };
            $lines[] = $label.': '.$display;
        }

        return $lines;
    }

    private static function retentionSummary(string $text, ?string $action): ?string
    {
        $verb = match ($action) {
            'retention_review_viewed' => 'Viewed retention records',
            'retention_policy_created' => 'Created retention policy',
            'retention_policy_updated' => 'Updated retention policy',
            'retention_policy_approved' => 'Recorded retention policy approval',
            'retention_policy_activated' => 'Activated retention policy',
            'retention_policy_deactivated' => 'Deactivated retention policy',
            'retention_period_modified' => 'Changed retention period',
            'retention_counting_basis_changed' => 'Changed retention counting rule',
            'retention_password_verification_failed' => 'Retention change denied: admin password verification failed',
            default => null,
        };
        if ($verb === null || ! str_starts_with($text, '{')) {
            return null;
        }
        $data = json_decode($text, true);
        if (! is_array($data)) {
            return null;
        }

        $category = $data['category'] ?? null;
        $label = is_string($category) ? config('retention.categories.'.$category.'.label') : null;
        if (is_string($label)) {
            $verb .= ' for '.$label;
        } elseif (filter_var($data['policy_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $verb .= ' (policy #'.$data['policy_id'].')';
        }

        if ($action === 'retention_review_viewed'
            && filter_var($data['page'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $verb .= ' (page '.$data['page'].')';
        } elseif ($action === 'retention_period_modified') {
            $before = self::retentionPeriod($data['previous'] ?? null);
            $after = self::retentionPeriod($data['updated'] ?? null);
            if ($before !== null && $after !== null) {
                $verb .= ' from '.$before.' to '.$after;
            }
        }

        return $verb.'.';
    }

    private static function retentionPeriod(mixed $settings): ?string
    {
        if (! is_array($settings)
            || ! filter_var($settings['duration'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1200]])
            || ! in_array($settings['unit'] ?? null, ['days', 'weeks', 'months', 'years'], true)) {
            return null;
        }

        $unit = (int) $settings['duration'] === 1 ? rtrim($settings['unit'], 's') : $settings['unit'];

        return $settings['duration'].' '.$unit;
    }
}
