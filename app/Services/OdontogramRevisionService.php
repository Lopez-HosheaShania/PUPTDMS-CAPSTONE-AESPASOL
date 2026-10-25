<?php

namespace App\Services;

use App\Models\AppointmentProcedure;
use App\Models\OdontogramRevision;
use App\Models\PatientOdontogram;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OdontogramRevisionService
{
    public function computeChanges($old, $new): array
    {
        $old = $this->normalizeSnapshot($old);
        $new = $this->normalizeSnapshot($new);

        $oldByTooth = collect($old)->keyBy(
            fn($entry) => (string) $this->toothNumber($entry)
        );

        $newByTooth = collect($new)->keyBy(
            fn($entry) => (string) $this->toothNumber($entry)
        );

        $toothNumbers = $oldByTooth
            ->keys()
            ->merge($newByTooth->keys())
            ->unique()
            ->filter()
            ->values();

        $changes = [];

        foreach ($toothNumbers as $toothNumber) {
            $oldEntry = $oldByTooth->get($toothNumber);
            $newEntry = $newByTooth->get($toothNumber);

            $this->appendChange(
                $changes,
                (int) $toothNumber,
                'status',
                data_get($oldEntry, 'status'),
                data_get($newEntry, 'status')
            );

            foreach (
                ['top', 'left', 'center', 'right', 'bottom']
                as $surface
            ) {
                $this->appendChange(
                    $changes,
                    (int) $toothNumber,
                    $surface,
                    data_get($oldEntry, "surfaces.$surface"),
                    data_get($newEntry, "surfaces.$surface")
                );
            }

            $this->appendChange(
                $changes,
                (int) $toothNumber,
                'threeD',
                data_get($oldEntry, 'threeD')
                    ?? data_get($oldEntry, 'three_d'),
                data_get($newEntry, 'threeD')
                    ?? data_get($newEntry, 'three_d')
            );
        }

        return $changes;
    }

    public function recordInitialRevision(
        AppointmentProcedure $procedure,
        User $user
    ): OdontogramRevision {
        $procedure->loadMissing('appointment.patient');

        $appointment = $procedure->appointment;

        if (!$appointment || !$appointment->patient_id) {
            throw ValidationException::withMessages([
                'appointment' => 'Unable to resolve the patient for this odontogram revision.',
            ]);
        }

        return DB::transaction(function () use (
            $procedure,
            $appointment,
            $user
        ) {
            Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingRevision = OdontogramRevision::query()
                ->where('appointment_id', $appointment->id)
                ->orderBy('version_number')
                ->first();

            if ($existingRevision) {
                return $existingRevision;
            }

            $patientOdontogram = PatientOdontogram::query()
                ->where('patient_id', $appointment->patient_id)
                ->where('last_appointment_id', $appointment->id)
                ->first();

            $snapshot = $this->normalizeSnapshot(
                $procedure->odontogram_data ?? []
            );

            $activeRole = session('impersonated_role')
                ?: session('role')
                ?: optional($user->role)->slug
                ?: 'unknown';

            return OdontogramRevision::create([
                'patient_id' => $appointment->patient_id,
                'patient_odontogram_id' => $patientOdontogram?->id,
                'appointment_id' => $appointment->id,
                'version_number' => 1,
                'odontogram_data' => $snapshot,
                'previous_odontogram_data' => null,
                'changes' => [],
                'reason_category' => 'other',
                'reason_notes' => 'Initial record',
                'changed_by' => $user->id,
                'changed_by_role' => $activeRole,
                'ip_address' => request()->ip(),
            ]);
        });
    }

    public function recordRevision(
        Model $odontogramable,
        array $newData,
        array $oldData,
        string $category,
        string $notes,
        User $user
    ): OdontogramRevision {
        $changes = $this->computeChanges($oldData, $newData);

        if ($changes === []) {
            throw ValidationException::withMessages([
                'odontogram_data' => 'No odontogram changes were detected.',
            ]);
        }

        [$patientId, $patientOdontogramId, $appointmentId] =
            $this->resolveRevisionContext($odontogramable);

        return DB::transaction(function () use (
            $patientId,
            $patientOdontogramId,
            $appointmentId,
            $newData,
            $oldData,
            $changes,
            $category,
            $notes,
            $user
        ) {
            if ($appointmentId !== null) {
                Appointment::query()
                    ->whereKey($appointmentId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $versionNumber = $this->nextVersionNumber(
                $patientId,
                $patientOdontogramId,
                $appointmentId
            );

            $activeRole = session('impersonated_role')
                ?: session('role')
                ?: optional($user->role)->slug
                ?: 'unknown';

            return OdontogramRevision::create([
                'patient_id' => $patientId,
                'patient_odontogram_id' => $patientOdontogramId,
                'appointment_id' => $appointmentId,
                'version_number' => $versionNumber,
                'odontogram_data' => $newData,
                'previous_odontogram_data' => $oldData,
                'changes' => $changes,
                'reason_category' => $category,
                'reason_notes' => trim($notes),
                'changed_by' => $user->id,
                'changed_by_role' => $activeRole,
                'ip_address' => request()->ip(),
            ]);
        });
    }

    public function history(
        int $patientId,
        ?int $appointmentId = null
    ): Collection {
        $query = OdontogramRevision::query()
            ->with('changedBy')
            ->where('patient_id', $patientId);

        if ($appointmentId !== null) {
            $query->where('appointment_id', $appointmentId);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('version_number')
            ->get();
    }

    public function getVersion(int $revisionId): OdontogramRevision
    {
        return OdontogramRevision::query()
            ->with('changedBy')
            ->findOrFail($revisionId);
    }

    private function resolveRevisionContext(Model $odontogramable): array
    {
        if ($odontogramable instanceof AppointmentProcedure) {
            $odontogramable->loadMissing('appointment.patient');

            $appointment = $odontogramable->appointment;

            if (!$appointment || !$appointment->patient_id) {
                throw ValidationException::withMessages([
                    'appointment' => 'Unable to resolve the patient for this odontogram revision.',
                ]);
            }

            $patientOdontogram = PatientOdontogram::query()
                ->where('patient_id', $appointment->patient_id)
                ->where('last_appointment_id', $appointment->id)
                ->first();

            return [
                (int) $appointment->patient_id,
                $patientOdontogram?->id,
                (int) $appointment->id,
            ];
        }

        if ($odontogramable instanceof PatientOdontogram) {
            return [
                (int) $odontogramable->patient_id,
                (int) $odontogramable->id,
                $odontogramable->last_appointment_id
                    ? (int) $odontogramable->last_appointment_id
                    : null,
            ];
        }

        throw ValidationException::withMessages([
            'odontogram' => 'Unsupported odontogram record type.',
        ]);
    }

    private function nextVersionNumber(
        int $patientId,
        ?int $patientOdontogramId,
        ?int $appointmentId
    ): int {
        $query = OdontogramRevision::query();

        if ($appointmentId !== null) {
            $query->where('appointment_id', $appointmentId);
        } elseif ($patientOdontogramId !== null) {
            $query->where('patient_odontogram_id', $patientOdontogramId);
        } else {
            $query->where('patient_id', $patientId)
                ->whereNull('appointment_id')
                ->whereNull('patient_odontogram_id');
        }

        $latestVersion = $query
            ->lockForUpdate()
            ->max('version_number');

        return ((int) $latestVersion) + 1;
    }

    private function appendChange(
        array &$changes,
        int $tooth,
        string $surface,
        $old,
        $new
    ): void {
        $oldCode = $this->recordCode($old);
        $newCode = $this->recordCode($new);

        if ($oldCode === $newCode) {
            return;
        }

        $changes[] = [
            'tooth' => $tooth,
            'surface' => $surface,
            'old_code' => $oldCode !== '' ? $oldCode : null,
            'new_code' => $newCode !== '' ? $newCode : null,
        ];
    }

    private function recordCode($record): string
    {
        return strtoupper(
            trim(
                (string) data_get(
                    $record,
                    'code',
                    ''
                )
            )
        );
    }

    private function toothNumber($entry): int
    {
        return (int) (
            data_get($entry, 'tooth')
            ?? data_get($entry, 'tooth_number')
            ?? 0
        );
    }

    private function normalizeSnapshot($snapshot): array
    {
        if ($snapshot instanceof Collection) {
            $snapshot = $snapshot->all();
        }

        if (is_string($snapshot)) {
            $decoded = json_decode($snapshot, true);

            $snapshot = is_array($decoded)
                ? $decoded
                : [];
        }

        if (!is_array($snapshot)) {
            return [];
        }

        return array_values(
            array_filter(
                $snapshot,
                fn($entry) => (is_array($entry) || is_object($entry))
                    && $this->toothNumber($entry) > 0
            )
        );
    }
}
