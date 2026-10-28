<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Services\AssignedDentistService;

final class DentistName
{
    public static function forUser(?User $user): string
    {
        return trim((string) ($user?->name ?: $user?->full_name));
    }

    public static function forAppointment(Appointment $appointment): string
    {
        return self::forUser(self::clinicianForAppointment($appointment));
    }

    public static function clinicianForAppointment(Appointment $appointment): ?User
    {
        $procedure = $appointment->relationLoaded('procedure') ? $appointment->getRelation('procedure') : null;
        if (! $appointment->relationLoaded('procedure') && $appointment->status === 'completed') {
            $procedure = $appointment->procedure()->without(['odontogram', 'timing'])
                ->select(['id', 'appointment_id', 'recorded_by'])->with('recordedBy')->first();
        }
        $dentist = null;
        foreach ([$procedure?->recordedBy, $appointment->dentist, $appointment->originalDentist] as $candidate) {
            
            if ($candidate && (! $candidate->role || AssignedDentistService::isDentist($candidate))) {
                $dentist = $candidate;
                break;
            }
        }
        $dentist ??= app(AssignedDentistService::class)->findForPatient($appointment->patient_id);

        return $dentist;
    }

    public static function forDocumentRequest(DocumentRequest $request): string
    {
        foreach ([$request->approvedBy, $request->assignedDentist] as $candidate) {
            if ($candidate && (! $candidate->role || AssignedDentistService::isDentist($candidate))) {
                return self::forUser($candidate);
            }
        }

        return self::forUser(app(AssignedDentistService::class)->findForPatient($request->patient_id));
    }

    public static function forRecords(iterable $records): array
    {
        $names = [];
        foreach ($records as $record) {
            $name = $record instanceof Appointment ? self::forAppointment($record)
                : trim((string) (data_get($record, 'dentist_name') ?? data_get($record, 'dentist', '')));
            if ($name !== '' && ! in_array($name, ['Not assigned', 'Unassigned', '—', '-'], true)) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }
}
