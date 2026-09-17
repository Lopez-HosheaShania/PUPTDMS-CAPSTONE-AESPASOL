<?php

namespace App\Support;

class AuditDescription
{
    public static function summarize(?string $text, ?string $action = null, ?string $module = null): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }
        $summary = trim($text);
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
}
