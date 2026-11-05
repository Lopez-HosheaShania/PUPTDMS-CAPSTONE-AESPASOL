<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignedDentistService
{
    public const DENTIST_ROLES = ['dentist', 'dentist_role', 'head_dentist'];

    public static function isDentist(?User $user): bool
    {
        return $user?->hasAnyRole(self::DENTIST_ROLES) ?? false;
    }

    public function availableDentists(): Builder
    {
        return User::where('status', 'active')
            ->whereHas('role', fn ($q) => $q->whereIn('slug', self::DENTIST_ROLES))
            ->whereDoesntHave('employmentStatus', fn ($q) => $q->whereNotNull('access_ends_at')->where('access_ends_at', '<=', now()))
            ->whereDoesntHave('dentistTransitions', function ($q) {
                $q->whereNotNull('effective_date')->where(function ($q) {
                    $q->where('status', 'active')->orWhere(function ($q) {
                        $q->where('status', 'scheduled')->whereDate('effective_date', '<=', today());
                    });
                });
            });
    }

    public function defaultDentist(): ?User
    {
        $currentId = $this->currentDentistId();
        if ($currentId) {
            return $this->availableDentists()->whereKey($currentId)->first();
        }

        // Preserve booking during setup only when there is exactly one candidate.
        $dentists = $this->availableDentists()->limit(2)->get();
        return $dentists->count() === 1 ? $dentists->first() : null;
    }

    public function findForBooking(int $patientId): ?User
    {
        $currentId = $this->currentDentistId();
        $assignedId = DB::table('patient_care_assignments')->where('patient_id', $patientId)->value('dentist_id');
        if ($currentId && $assignedId && (int) $assignedId !== $currentId) {
            return null;
        }

        return $assignedId
            ? $this->availableDentists()->whereKey($assignedId)->first()
            : $this->defaultDentist();
    }

    public function findForPatient(int $patientId, ?User $actor = null): ?User
    {
        $currentId = $this->currentDentistId();
        if ($currentId) {
            if (self::isDentist($actor) && (int) $actor->id !== $currentId) {
                return null;
            }
            return $this->findForBooking($patientId);
        }
        $assignedId = DB::table('patient_care_assignments')->where('patient_id', $patientId)->value('dentist_id');
        if ($assignedId) {
            return $this->availableDentists()->whereKey($assignedId)->first();
        }

        if (self::isDentist($actor)) {
            return $this->availableDentists()->whereKey($actor->id)->first();
        }

        $appointedIds = DB::table('appointments')->where('patient_id', $patientId)
            ->whereIn('status', ['pending', 'confirmed', 'upcoming', 'rescheduled'])
            ->orderBy('appointment_date')->orderBy('appointment_time')->pluck('dentist_id');
        foreach ($appointedIds->unique()->filter() as $id) {
            if ($dentist = $this->availableDentists()->whereKey($id)->first()) {
                return $dentist;
            }
        }
        
        return $this->defaultDentist();
    }

    public function requireForPatient(int $patientId, ?User $actor = null): User
    {
        return $this->findForPatient($patientId, $actor)
            ?? throw ValidationException::withMessages(['dentist_id' => 'An available responsible dentist must be assigned before recording this visit.']);
    }

    private function currentDentistId(): ?int
    {
        $clinic = app(ClinicDentistService::class);
        return (DB::transactionLevel() > 0 ? $clinic->lockAssignment() : $clinic->assignment())->dentist_id;
    }
}
