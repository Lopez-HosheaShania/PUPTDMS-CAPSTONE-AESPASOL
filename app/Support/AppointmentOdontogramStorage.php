<?php

namespace App\Support;

use Illuminate\Database\Connection;
use InvalidArgumentException;

class AppointmentOdontogramStorage
{
    public static function validate(?array $data): void
    {
        if ($data !== null && ! array_is_list($data)) {
            throw new InvalidArgumentException('An odontogram must be a list of teeth.');
        }
        foreach ($data ?? [] as $entry) {
            if (! is_array($entry) || ! isset($entry['tooth']) || ! is_int($entry['tooth'])
                || array_diff(array_keys($entry), ['tooth', 'toothName', 'status', 'threeD', 'surfaces'])) {
                throw new InvalidArgumentException('Unsupported odontogram tooth structure.');
            }
            if (isset($entry['toothName']) && ! is_string($entry['toothName'])) {
                throw new InvalidArgumentException('Unsupported tooth name.');
            }
            if (isset($entry['surfaces']) && ! is_array($entry['surfaces'])) {
                throw new InvalidArgumentException('Unsupported odontogram surfaces.');
            }
            foreach (array_merge([$entry['status'] ?? null, $entry['threeD'] ?? null], array_values($entry['surfaces'] ?? [])) as $mark) {
                if ($mark === null) {
                    continue;
                }
                if (! is_array($mark) || array_diff(array_keys($mark), ['code', 'label', 'colorHex'])) {
                    throw new InvalidArgumentException('Unsupported odontogram marking structure.');
                }
                foreach ($mark as $value) {
                    if ($value !== null && ! is_string($value)) {
                        throw new InvalidArgumentException('Unsupported odontogram marking value.');
                    }
                }
            }
            foreach (array_keys($entry['surfaces'] ?? []) as $surface) {
                if (! in_array($surface, ['top', 'left', 'center', 'right', 'bottom'], true)) {
                    throw new InvalidArgumentException('Unsupported odontogram surface name.');
                }
            }
        }
    }

    public static function replace(Connection $connection, int $snapshotId, ?array $data, bool $includeEmpty = false): void
    {
        self::validate($data);
        $hasLayout = $connection->getSchemaBuilder()->hasColumn('appointment_odontogram_teeth', 'marking_layout');
        $includeEmpty = $includeEmpty || ! $hasLayout;
        $connection->table('appointment_odontogram_teeth')->where('appointment_odontogram_id', $snapshotId)->delete();
        foreach ($data ?? [] as $position => $entry) {
            $toothValues = [
                'appointment_odontogram_id' => $snapshotId,
                'position' => $position,
                'tooth_number' => $entry['tooth'],
                'tooth_name' => $entry['toothName'] ?? null,
                'field_keys' => implode(',', array_keys($entry)),
                'surfaces_null' => array_key_exists('surfaces', $entry) && $entry['surfaces'] === null,
            ];
            if ($hasLayout) {
                $layout = [];
                foreach (['status', 'threeD'] as $kind) {
                    if (array_key_exists($kind, $entry)) {
                        $layout[$kind] = self::hasMark($entry[$kind]) ? null : $entry[$kind];
                    }
                }
                if (array_key_exists('surfaces', $entry)) {
                    $layout['surfaces'] = $entry['surfaces'] === null ? null : [];
                    foreach ($entry['surfaces'] ?? [] as $surface => $mark) {
                        $layout['surfaces'][$surface] = self::hasMark($mark) ? null : $mark;
                    }
                }
                $toothValues['marking_layout'] = json_encode($layout, JSON_THROW_ON_ERROR);
            }
            $toothId = $connection->table('appointment_odontogram_teeth')->insertGetId($toothValues);
            $rows = [];
            $markPosition = 0;
            foreach (['status', 'threeD'] as $kind) {
                if (array_key_exists($kind, $entry)) {
                    if ($includeEmpty || self::hasMark($entry[$kind])) {
                        $rows[] = self::markingRow($toothId, $kind, '', $entry[$kind], $markPosition);
                    }
                    $markPosition++;
                }
            }
            foreach ($entry['surfaces'] ?? [] as $surface => $mark) {
                if ($includeEmpty || self::hasMark($mark)) {
                    $rows[] = self::markingRow($toothId, 'surface', $surface, $mark, $markPosition);
                }
                $markPosition++;
            }
            if ($rows !== []) {
                $connection->table('appointment_odontogram_markings')->insert($rows);
            }
        }
    }

    private static function hasMark(?array $mark): bool
    {
        foreach ($mark ?? [] as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private static function markingRow(int $toothId, string $kind, string $surface, ?array $mark, int $position): array
    {
        return [
            'tooth_id' => $toothId, 'kind' => $kind, 'surface' => $surface, 'position' => $position,
            'code' => $mark['code'] ?? null, 'label' => $mark['label'] ?? null,
            'color_hex' => $mark['colorHex'] ?? null,
            'field_keys' => $mark === null ? null : implode(',', array_keys($mark)),
        ];
    }

    public static function decode(iterable $teeth): array
    {
        $data = [];
        foreach ($teeth as $tooth) {
            $values = ['tooth' => (int) $tooth->tooth_number, 'toothName' => $tooth->tooth_name,
                'surfaces' => $tooth->surfaces_null ? null : []];
            if (($tooth->marking_layout ?? null) !== null) {
                $values = array_replace($values, json_decode($tooth->marking_layout, true, 512, JSON_THROW_ON_ERROR));
            }
            foreach ($tooth->markings as $mark) {
                $value = null;
                if ($mark->field_keys !== null) {
                    $value = [];
                    $columns = ['code' => $mark->code, 'label' => $mark->label, 'colorHex' => $mark->color_hex];
                    foreach (array_filter(explode(',', $mark->field_keys)) as $key) {
                        $value[$key] = $columns[$key];
                    }
                }
                if ($mark->kind === 'surface') {
                    $values['surfaces'][$mark->surface] = $value;
                } else {
                    $values[$mark->kind] = $value;
                }
            }
            $entry = [];
            foreach (explode(',', $tooth->field_keys) as $key) {
                $entry[$key] = $values[$key];
            }
            $data[] = $entry;
        }

        return $data;
    }

    public static function read(Connection $connection, int $snapshotId): ?array
    {
        $header = $connection->table('appointment_odontograms')->where('id', $snapshotId)->first();
        if (! $header->has_data) {
            return null;
        }
        $teeth = $connection->table('appointment_odontogram_teeth')->where('appointment_odontogram_id', $snapshotId)->orderBy('position')->get();
        $marks = $connection->table('appointment_odontogram_markings')->whereIn('tooth_id', $teeth->pluck('id'))->orderBy('position')->get()->groupBy('tooth_id');
        foreach ($teeth as $tooth) {
            $tooth->markings = $marks->get($tooth->id, collect());
        }

        return self::decode($teeth);
    }
}
