<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Tooth;
use App\Models\ToothLegend;
use App\Models\ToothSurface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Patient;

class OdontogramController extends Controller
{

    public function startForPatient(Patient $patient)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $appointment = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->first();

        if (!$appointment) {
            return redirect()
                ->route('dentist.dentist.patient.profile', ['patient' => $patient->id])
                ->with('error', 'No upcoming appointment found for this patient.');
        }

        return redirect()->route('dentist.odontogram', [
            'appointment' => $appointment->id,
        ]);
    }
    public function show(Appointment $appointment)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $appointment->load('patient');

        if (!$appointment->patient) {
            abort(404, 'Patient not found for this appointment.');
        }

        $patient = $appointment->patient;

        $procedure = AppointmentProcedure::where('appointment_id', $appointment->id)->first();

        $savedOdontogramData = $procedure?->odontogram_data ?? [];

        return view('dentist.dentist-odontogram', compact(
            'patient',
            'appointment',
            'procedure',
            'savedOdontogramData'
        ));
    }


    public function save(Request $request, Appointment $appointment)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $appointment->load('patient');

        if (!$appointment->patient) {
            return response()->json([
                'message' => 'Patient not found for this appointment.',
            ], 404);
        }

        $validated = $request->validate([
            'odontogram_data' => 'required|array',
            'odontogram_data.*.tooth' => 'nullable|integer|min:1',

            'odontogram_data.*.status.code' => 'nullable|string|max:20',
            'odontogram_data.*.status.label' => 'nullable|string|max:255',
            'odontogram_data.*.status.colorHex' => 'nullable|string|max:30',

            'odontogram_data.*.threeD.code' => 'nullable|string|max:20',
            'odontogram_data.*.threeD.label' => 'nullable|string|max:255',
            'odontogram_data.*.threeD.colorHex' => 'nullable|string|max:30',

            'odontogram_data.*.surfaces' => 'nullable|array',
            'odontogram_data.*.surfaces.*.code' => 'nullable|string|max:20',
            'odontogram_data.*.surfaces.*.label' => 'nullable|string|max:255',
            'odontogram_data.*.surfaces.*.colorHex' => 'nullable|string|max:30',

            'oral_examination' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'prescriptions' => 'nullable|string',
            'completion_action' => 'nullable|in:finished,follow_up',
            'has_applied_treatment' => 'required|accepted',
        ]);

        if (!$request->boolean('has_applied_treatment')) {
            return response()->json([
                'message' => 'Please apply at least one treatment to the tooth chart before finishing the procedure.',
            ], 422);
        }

        $patient = $appointment->patient;
        $completionAction = $validated['completion_action'] ?? 'finished';
        $rawOdontogramData = $validated['odontogram_data'] ?? [];

        $cleanOdontogramData = collect($rawOdontogramData)
            ->map(function ($entry) {
                $toothNumber = (int) data_get($entry, 'tooth', 0);

                if ($toothNumber <= 0) {
                    return null;
                }

                $cleanEntry = [
                    'tooth' => $toothNumber,
                    'toothName' => data_get($entry, 'toothName'),
                    'status' => null,
                    'surfaces' => [
                        'top' => null,
                        'left' => null,
                        'center' => null,
                        'right' => null,
                        'bottom' => null,
                    ],
                    'threeD' => null,
                ];

                $statusCode = trim((string) data_get($entry, 'status.code', ''));
                if ($statusCode !== '') {
                    $cleanEntry['status'] = [
                        'code' => $statusCode,
                        'label' => data_get($entry, 'status.label', $statusCode),
                        'colorHex' => data_get($entry, 'status.colorHex'),
                    ];
                }

                $threeDCode = trim((string) data_get($entry, 'threeD.code', ''));
                if ($threeDCode !== '') {
                    $cleanEntry['threeD'] = [
                        'code' => $threeDCode,
                        'label' => data_get($entry, 'threeD.label', $threeDCode),
                        'colorHex' => data_get($entry, 'threeD.colorHex'),
                    ];
                }

                foreach (['top', 'left', 'center', 'right', 'bottom'] as $surfaceKey) {
                    $surfaceCode = trim((string) data_get($entry, "surfaces.$surfaceKey.code", ''));

                    if ($surfaceCode !== '') {
                        $cleanEntry['surfaces'][$surfaceKey] = [
                            'code' => $surfaceCode,
                            'label' => data_get($entry, "surfaces.$surfaceKey.label", $surfaceCode),
                            'colorHex' => data_get($entry, "surfaces.$surfaceKey.colorHex"),
                        ];
                    }
                }

                $hasAppliedTreatment =
                    filled(data_get($cleanEntry, 'status.code')) ||
                    filled(data_get($cleanEntry, 'threeD.code')) ||
                    collect($cleanEntry['surfaces'])->contains(function ($surface) {
                        return filled(data_get($surface, 'code'));
                    });

                return $hasAppliedTreatment ? $cleanEntry : null;
            })
            ->filter()
            ->values()
            ->all();

        if (count($cleanOdontogramData) === 0) {
            return response()->json([
                'message' => 'Please apply at least one treatment to the tooth chart before finishing the procedure.',
            ], 422);
        }

        $legendCache = [];

        $resolveLegendId = function (?string $code, ?string $label = null) use (&$legendCache) {
            if (!$code) {
                return null;
            }

            $normalizedCode = strtoupper(trim($code));

            if ($normalizedCode === '') {
                return null;
            }

            if (isset($legendCache[$normalizedCode])) {
                return $legendCache[$normalizedCode];
            }

            $legend = ToothLegend::whereRaw('UPPER(code) = ?', [$normalizedCode])->first();

            if (!$legend) {
                $legend = ToothLegend::create([
                    'code' => $normalizedCode,
                    'description' => $label ?: $normalizedCode,
                    'category' => 'Odontogram',
                ]);
            }

            $legendCache[$normalizedCode] = $legend->id;

            return $legend->id;
        };

        $surfaceMap = [
            'top' => 1,
            'right' => 2,
            'bottom' => 3,
            'left' => 4,
            'center' => 5,
        ];

        $savedTeeth = 0;

        DB::transaction(function () use (
            $appointment,
            $patient,
            $validated,
            $cleanOdontogramData,
            $completionAction,
            $resolveLegendId,
            $surfaceMap,
            &$savedTeeth
        ) {
            foreach ($cleanOdontogramData as $entry) {
                $toothNumber = (int) $entry['tooth'];

                $tooth = Tooth::firstOrCreate([
                    'patient_id' => $patient->id,
                    'tooth_number' => $toothNumber,
                ]);

                $savedTeeth++;

                $toothLegendCode = data_get($entry, 'threeD.code') ?: data_get($entry, 'status.code');
                $toothLegendLabel = data_get($entry, 'threeD.label') ?: data_get($entry, 'status.label');

                $toothLegendId = $resolveLegendId($toothLegendCode, $toothLegendLabel);

                $tooth->legends()->sync($toothLegendId ? [$toothLegendId] : []);

                foreach ($surfaceMap as $surfaceKey => $surfaceNumber) {
                    $surface = ToothSurface::firstOrCreate([
                        'tooth_id' => $tooth->id,
                        'surface_number' => $surfaceNumber,
                    ]);

                    $surfaceCode = data_get($entry, "surfaces.$surfaceKey.code");
                    $surfaceLabel = data_get($entry, "surfaces.$surfaceKey.label");

                    $surfaceLegendId = $resolveLegendId($surfaceCode, $surfaceLabel);

                    $surface->legends()->sync($surfaceLegendId ? [$surfaceLegendId] : []);
                }
            }

            AppointmentProcedure::updateOrCreate(
                [
                    'appointment_id' => $appointment->id,
                ],
                [
                    'patient_id' => $patient->id,
                    'odontogram_data' => $cleanOdontogramData,
                    'oral_examination' => $validated['oral_examination'] ?? null,
                    'diagnosis' => $validated['diagnosis'] ?? null,
                    'prescriptions' => $validated['prescriptions'] ?? null,
                    'completion_action' => $completionAction,
                ]
            );

            $appointmentUpdate = [
                'status' => 'completed',
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('appointments', 'completed_at')) {
                $appointmentUpdate['completed_at'] = now();
            }

            DB::table('appointments')
                ->where('id', $appointment->id)
                ->update($appointmentUpdate);
        });

        $appointment->refresh();

        if ($appointment->status !== 'completed') {
            return response()->json([
                'message' => 'Procedure was saved, but appointment status was not updated to completed.',
                'current_status' => $appointment->status,
            ], 500);
        }

        return response()->json([
            'message' => $completionAction === 'follow_up'
                ? 'Procedure completed. You may now create a follow-up appointment.'
                : 'Procedure completed successfully.',
            'saved_teeth' => $savedTeeth,
            'status' => 'completed',
            'redirect_url' => route('dentist.dentist.appointments') . '?refresh=' . now()->timestamp,
        ]);
    }
}
