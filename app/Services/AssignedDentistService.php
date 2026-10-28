<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignedDentistService
{
    public static function isDentist(?User $user): bool
    {
        return $user?->hasAnyRole(['dentist', 'dentist_role', 'head_dentist']) ?? false;
    }

    private function available(): Builder
    {
        return User::where('status', 'active')
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['dentist', 'dentist_role', 'head_dentist']))
            ->whereDoesntHave('employmentStatus', fn ($q) => $q->whereNotNull('access_ends_at')->where('access_ends_at', '<=', now()))
            ->whereDoesntHave('dentistTransitions', function ($q) {
                $q->whereNotNull('effective_date')->where(function ($q) {
                    $q->where('status', 'active')->orWhere(function ($q) {
                        $q->where('status', 'scheduled')->whereDate('effective_date', '<=', today());
                    });
                });
            });
    }

    public function findForPatient(int $patientId, ?User $actor = null): ?User
    {
        $assignedId = DB::table('patient_care_assignments')->where('patient_id', $patientId)->value('dentist_id');
        if ($assignedId) {
            return $this->available()->whereKey($assignedId)->first();
        }

        if (self::isDentist($actor)) {
            return $this->available()->whereKey($actor->id)->first();
        }

        $appointedIds = DB::table('appointments')->where('patient_id', $patientId)
            ->whereIn('status', ['pending', 'confirmed', 'upcoming', 'rescheduled'])
            ->orderBy('appointment_date')->orderBy('appointment_time')->pluck('dentist_id');
        foreach ($appointedIds->unique()->filter() as $id) {
            if ($dentist = $this->available()->whereKey($id)->first()) {
                return $dentist;
            }
        }
        
        return $this->available()->orderBy('id')->first();
    }

    public function requireForPatient(int $patientId, ?User $actor = null): User
    {
        return $this->findForPatient($patientId, $actor)
            ?? throw ValidationException::withMessages(['dentist_id' => 'An available responsible dentist must be assigned before recording this visit.']);
    }
}
