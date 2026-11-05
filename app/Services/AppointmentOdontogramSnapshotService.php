<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AppointmentOdontogramSnapshotService
{
    public function previousSnapshotsByAppointment(
        Collection $appointments
    ): array {
        $previousSnapshots = [];
        $latestSnapshotByPatient = [];

        $chronologicalAppointments = $appointments
            ->sortBy(function ($appointment) {
                return sprintf(
                    '%s %s %010d',
                    (string) (
                        $appointment->appointment_date
                        ?? '0000-00-00'
                    ),
                    (string) (
                        $appointment->appointment_time
                        ?? '00:00:00'
                    ),
                    (int) (
                        $appointment->id
                        ?? 0
                    )
                );
            })
            ->values();

        foreach (
            $chronologicalAppointments
            as $appointment
        ) {
            $appointmentId =
                (int) (
                    $appointment->id
                    ?? 0
                );

            if ($appointmentId <= 0) {
                continue;
            }

            $patientKey =
                (string) (
                    $appointment->patient_id
                    ?? data_get(
                        $appointment,
                        'patient.id',
                        ''
                    )
                );

            if ($patientKey === '') {
                $patientKey =
                    'appointment-' .
                    $appointmentId;
            }

            $previousSnapshots[$appointmentId] =
                $latestSnapshotByPatient[$patientKey]
                ?? [];

            $status = strtolower(
                trim(
                    (string) (
                        $appointment->status
                        ?? ''
                    )
                )
            );

            if ($status !== 'completed') {
                continue;
            }

            $currentSnapshot =
                $this->normalizeSnapshot(
                    data_get(
                        $appointment,
                        'procedure.odontogram_data',
                        []
                    )
                );

            /*
             * A completed appointment without an
             * odontogram must not erase the last
             * cumulative patient snapshot.
             */
            if (!empty($currentSnapshot)) {
                $latestSnapshotByPatient[$patientKey] = $currentSnapshot;
            }
        }

        return $previousSnapshots;
    }

    /**
     * Return only the odontogram entries that belong
     * to the selected appointment.
     */
    public function appointmentSnapshot(
        $currentSnapshot,
        $previousSnapshot = []
    ): array {
        $currentSnapshot =
            $this->normalizeSnapshot(
                $currentSnapshot
            );

        $previousSnapshot =
            $this->normalizeSnapshot(
                $previousSnapshot
            );

        $previousByTooth =
            collect($previousSnapshot)
            ->keyBy(function ($entry) {
                return (string) (
                    data_get(
                        $entry,
                        'tooth'
                    )
                    ?? data_get(
                        $entry,
                        'tooth_number'
                    )
                    ?? ''
                );
            });

        return collect($currentSnapshot)
            ->map(function ($entry) use (
                $previousByTooth
            ) {
                $toothNumber =
                    (int) (
                        data_get(
                            $entry,
                            'tooth'
                        )
                        ?? data_get(
                            $entry,
                            'tooth_number'
                        )
                        ?? 0
                    );

                if ($toothNumber <= 0) {
                    return null;
                }

                $previousEntry =
                    $previousByTooth->get(
                        (string) $toothNumber
                    );

                $recordCode =
                    function ($record): string {
                        return strtoupper(
                            trim(
                                (string) data_get(
                                    $record,
                                    'code',
                                    ''
                                )
                            )
                        );
                    };

                $currentStatus =
                    data_get(
                        $entry,
                        'status'
                    );

                $previousStatus =
                    data_get(
                        $previousEntry,
                        'status'
                    );

                $statusChanged =
                    $recordCode($currentStatus) !== '' &&
                    $recordCode($currentStatus) !==
                    $recordCode($previousStatus);

                $surfaceKeys = [
                    'top',
                    'left',
                    'center',
                    'right',
                    'bottom',
                ];

                $changedSurfaces = [
                    'top' => null,
                    'left' => null,
                    'center' => null,
                    'right' => null,
                    'bottom' => null,
                ];

                $hasSurfaceChange = false;

                foreach (
                    $surfaceKeys
                    as $surfaceKey
                ) {
                    $currentSurface =
                        data_get(
                            $entry,
                            "surfaces.$surfaceKey"
                        );

                    $previousSurface =
                        data_get(
                            $previousEntry,
                            "surfaces.$surfaceKey"
                        );

                    $currentCode =
                        $recordCode(
                            $currentSurface
                        );

                    $previousCode =
                        $recordCode(
                            $previousSurface
                        );

                    if (
                        $currentCode !== '' &&
                        $currentCode !== $previousCode
                    ) {
                        $changedSurfaces[$surfaceKey] = $currentSurface;

                        $hasSurfaceChange = true;
                    }
                }

                $currentThreeD =
                    data_get(
                        $entry,
                        'threeD'
                    )
                    ?? data_get(
                        $entry,
                        'three_d'
                    );

                $previousThreeD =
                    data_get(
                        $previousEntry,
                        'threeD'
                    )
                    ?? data_get(
                        $previousEntry,
                        'three_d'
                    );

                $threeDChanged =
                    $recordCode($currentThreeD) !== '' &&
                    $recordCode($currentThreeD) !==
                    $recordCode($previousThreeD);

                if (
                    !$statusChanged &&
                    !$hasSurfaceChange &&
                    !$threeDChanged
                ) {
                    return null;
                }

                $displayRecord = null;

                if ($threeDChanged) {
                    $displayRecord =
                        $currentThreeD;
                } elseif ($statusChanged) {
                    $displayRecord =
                        $currentStatus;
                } else {
                    foreach (
                        [
                            'center',
                            'top',
                            'right',
                            'bottom',
                            'left',
                        ]
                        as $surfaceKey
                    ) {
                        if (
                            !empty($changedSurfaces[$surfaceKey])
                        ) {
                            $displayRecord =
                                $changedSurfaces[$surfaceKey];

                            break;
                        }
                    }
                }

                $lastSelectedSurface =
                    data_get(
                        $entry,
                        'lastSelectedSurface'
                    )
                    ?? data_get(
                        $entry,
                        'last_selected_surface'
                    );

                if (
                    !$lastSelectedSurface ||
                    empty($changedSurfaces[$lastSelectedSurface] ?? null)
                ) {
                    $lastSelectedSurface =
                        collect($surfaceKeys)
                        ->first(
                            fn($surfaceKey) =>
                            !empty($changedSurfaces[$surfaceKey])
                        );
                }

                return [
                    'tooth' =>
                    $toothNumber,

                    'toothName' =>
                    data_get(
                        $entry,
                        'toothName'
                    )
                        ?? data_get(
                            $entry,
                            'tooth_name'
                        ),

                    'status' =>
                    $statusChanged
                        ? $currentStatus
                        : null,

                    'surfaces' =>
                    $changedSurfaces,

                    'threeD' =>
                    $displayRecord,

                    'lastSelectedSurface' =>
                    $lastSelectedSurface,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeSnapshot(
        $snapshot
    ): array {
        if ($snapshot instanceof Collection) {
            $snapshot =
                $snapshot->all();
        }

        if (is_string($snapshot)) {
            $decoded =
                json_decode(
                    $snapshot,
                    true
                );

            $snapshot =
                is_array($decoded)
                ? $decoded
                : [];
        }

        if (!is_array($snapshot)) {
            return [];
        }

        return array_values(
            array_filter(
                $snapshot,
                fn($entry) =>
                is_array($entry) ||
                    is_object($entry)
            )
        );
    }
}
