<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Shared\InventoryController as SharedInventoryController;
use App\Models\AppointmentProcedure;
use Illuminate\Http\Request;

class InventoryController extends SharedInventoryController
{
    public function procedureNotes(Request $request)
    {
        abort_unless($request->user()?->hasAnyRole(['dentist', 'dentist_role'])
            && !$request->session()->has('impersonated_role'), 403);
        $notes = AppointmentProcedure::query()
            ->whereNotNull('inventory_notes')->whereRaw("TRIM(inventory_notes) <> ''")
            ->whereHas('appointment', function ($query) use ($request) {
                $query->where('dentist_id', $request->user()->id)->where('status', 'completed');
            })
            ->with(['appointment.patient', 'appointment.dentist', 'appointment.serviceType'])
            ->leftJoin('appointment_procedure_timings as note_timing', 'note_timing.appointment_procedure_id', '=', 'appointment_procedures.id')
            ->leftJoin('appointments as note_visit', 'note_visit.id', '=', 'appointment_procedures.appointment_id')
            ->select('appointment_procedures.*')
            ->orderByRaw('COALESCE(note_timing.completed_at, note_visit.appointment_date) DESC')
            ->orderByDesc('appointment_procedures.id')
            ->get();

        return response()->json([
            'data' => $notes->map(function ($procedure) {
                $appointment = $procedure->appointment;

                return [
                    'procedure_id' => $procedure->id,
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient?->id,
                    'patient_name' => $appointment->patient?->name ?? 'Patient unavailable',
                    'procedure_name' => $appointment->service_type_name ?? 'Dental procedure',
                    'procedure_date' => $procedure->procedure_completed_at?->format('M d, Y h:i A')
                        ?? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y'),
                    'visit_date' => \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y'),
                    'dentist_name' => $appointment->dentist?->name,
                    'inventory_notes' => $procedure->inventory_notes,
                ];
            }),
            'total' => $notes->count(),
        ])->header('Cache-Control', 'no-store');
    }

    protected function actorLabel(): string
    {
        return 'Dentist';
    }


    protected function layoutRole(): string
    {
        return 'dentist';
    }


    protected function isDentistView(): bool
    {
        return true;
    }


    protected function inventoryRouteNames(): array
    {
        return [
            'data' =>
            'dentist.dentist.inventory.data',

            'store' =>
            'dentist.dentist.inventory.store',

            'update' =>
            'dentist.dentist.inventory.update',

            'destroy' =>
            'dentist.dentist.inventory.destroy',
        ];
    }


    protected function inventoryWatcherKey(): string
    {
        return 'dentist-inventory';
    }
}
