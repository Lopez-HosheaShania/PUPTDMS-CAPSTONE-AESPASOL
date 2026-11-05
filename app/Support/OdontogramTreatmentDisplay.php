<?php

namespace App\Support;

class OdontogramTreatmentDisplay
{
    private const SURFACE_LABELS = [
        'top' => 'Top',
        'left' => 'Left',
        'center' => 'Center',
        'right' => 'Right',
        'bottom' => 'Bottom',
    ];

    public static function items(array|string|null $rawOdontogram): array
    {
        $entries = self::normalizeEntries($rawOdontogram);
        $treatments = [];

        foreach ($entries as $entry) {
            $status = self::normalizeRecord(data_get($entry, 'status'));
            if ($status !== null) {
                $treatments[] = self::buildItem('Status', $status);
            }

            $surfaceItems = [];

            foreach (self::SURFACE_LABELS as $surfaceKey => $surfaceLabel) {
                $surface = self::normalizeRecord(data_get($entry, "surfaces.$surfaceKey"));

                if ($surface === null) {
                    continue;
                }

                $item = self::buildItem($surfaceLabel, $surface);
                $surfaceItems[] = $item;
                $treatments[] = $item;
            }

            $threeD = self::normalizeRecord(data_get($entry, 'threeD') ?? data_get($entry, 'three_d'));

            if ($threeD === null || self::isDuplicateThreeDEntry($threeD, $surfaceItems)) {
                continue;
            }

            $treatments[] = self::buildItem('3D', $threeD);
        }

        return array_values(array_filter(
            self::uniqueItems($treatments),
            static fn (array $item): bool => $item['code'] !== ''
        ));
    }

    public static function summary(array|string|null $rawOdontogram, string $emptyText = 'No treatment recorded.'): string
    {
        $labels = array_values(array_unique(array_map(
            static fn (array $item): string => $item['label'],
            array_filter(
                self::items($rawOdontogram),
                static fn (array $item): bool => $item['label'] !== ''
            )
        )));

        return $labels !== []
            ? implode(', ', $labels)
            : $emptyText;
    }

    private static function normalizeEntries(array|string|null $rawOdontogram): array
    {
        if (is_string($rawOdontogram)) {
            $decoded = json_decode($rawOdontogram, true);
            $rawOdontogram = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($rawOdontogram)) {
            return [];
        }

        return array_is_list($rawOdontogram)
            ? $rawOdontogram
            : array_values($rawOdontogram);
    }

    private static function normalizeRecord(mixed $record): ?array
    {
        $code = strtoupper(trim((string) data_get($record, 'code', '')));

        if ($code === '') {
            return null;
        }

        if (in_array($code, ['PT', '+'], true)) {
            $code = '✓';
        }

        return [
            'code' => $code,
            'label' => trim((string) data_get($record, 'label', $code)),
            'colorHex' => trim((string) (data_get($record, 'colorHex') ?? data_get($record, 'color_hex', ''))),
        ];
    }

    private static function buildItem(string $surface, array $record): array
    {
        return [
            'surface' => $surface,
            'code' => $record['code'],
            'label' => $record['label'],
            'colorHex' => $record['colorHex'],
        ];
    }

    private static function isDuplicateThreeDEntry(array $threeD, array $surfaceItems): bool
    {
        $signature = self::recordSignature($threeD);

        foreach ($surfaceItems as $surfaceItem) {
            if (self::recordSignature($surfaceItem) === $signature) {
                return true;
            }
        }

        return false;
    }

    private static function uniqueItems(array $items): array
    {
        $unique = [];

        foreach ($items as $item) {
            $key = strtolower(implode('|', [
                $item['surface'] ?? '',
                $item['code'] ?? '',
                $item['label'] ?? '',
            ]));

            if (! isset($unique[$key])) {
                $unique[$key] = $item;
            }
        }

        return $unique;
    }

    private static function recordSignature(array $record): string
    {
        return strtolower(implode('|', [
            $record['code'] ?? '',
            $record['label'] ?? '',
        ]));
    }
}
