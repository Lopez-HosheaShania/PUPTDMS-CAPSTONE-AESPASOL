<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ClinicalAccessService
{
    public function scope(Builder $query): void
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role?->slug, ['dentist', 'dentist_role'], true)) {
            return;
        }
        if ($user->status !== 'active' || DB::table('dentist_transitions')
            ->where('dentist_id', $user->id)->whereNotNull('effective_date')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere(function ($q) {
                    $q->where('status', 'scheduled')->whereDate('effective_date', '<=', today());
                });
            })->exists()) {
            $query->whereRaw('1 = 0');
            return;
        }

        $column = $query->getModel() instanceof Patient ? 'patients.id' : $query->getModel()->qualifyColumn('patient_id');
        $intake = $query->getModel() instanceof Patient
            && request()->routeIs('dentist.walk-in.*', 'dentist.odontogram.existing-appointment.*', 'dentist.odontogram.discard')
            && ($user->hasPermission('manage_walk_in_patients') || $user->hasPermission('manage_appointments') || $user->hasPermission('create_procedure_records') || $user->hasPermission('create_medical_records'));
        $query->where(function ($q) use ($user, $column, $intake) {
            $q->whereIn($column, DB::table('patient_care_assignments')->select('patient_id')->where('dentist_id', $user->id))
                ->orWhereIn($column, DB::table('appointments')->select('patient_id')->where('dentist_id', $user->id)
                    ->whereIn('status', ['pending', 'confirmed', 'upcoming', 'rescheduled']))
                ->orWhereIn($column, DB::table('document_requests')->select('patient_id')->where('assigned_dentist_id', $user->id)
                    ->join('document_request_states', 'document_request_states.document_request_id', '=', 'document_requests.id')
                    ->where('document_request_states.status', 'pending'))
                ->orWhere(function ($q) use ($user, $column) {
                    // An explicit handover supersedes all historical care links.
                    $q->whereNotIn($column, DB::table('patient_care_assignments')->select('patient_id'))
                        ->where(function ($q) use ($user, $column) {
                            $q->whereIn($column, DB::table('appointments')->select('patient_id')->where('dentist_id', $user->id))
                                ->orWhereIn($column, DB::table('document_requests')->select('patient_id')->where('assigned_dentist_id', $user->id));
                        });
                });
            if ($intake) {
                // Authorized intake can establish a first care relationship. Existing
                // clinical assignments must still be handed over by an administrator.
                $q->orWhere(function ($q) use ($column) {
                    $q->whereNotIn($column, DB::table('patient_care_assignments')->select('patient_id'))
                        ->whereNotIn($column, DB::table('appointments')->select('patient_id')->whereNotNull('dentist_id'))
                        ->whereNotIn($column, DB::table('document_requests')->select('patient_id')->whereNotNull('assigned_dentist_id'));
                });
            }
        });
    }
}
