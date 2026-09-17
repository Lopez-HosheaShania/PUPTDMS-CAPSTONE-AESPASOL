<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment;
use App\Models\Patient;
use App\Helpers\AuditLogger;
use App\Models\DocumentRequest;

class RecordController extends Controller
{
    public function index()
    {
        $patientId = session('impersonated_patient_id') ?: session('patient_id');

        if (!$patientId) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient = Patient::findOrFail($patientId);

        $perPage = (int) request('per_page', 10);

        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $latestRecordDate = Appointment::query()
            ->where('patient_id', $patient->id)
            ->whereIn(
                'status',
                ['completed', 'cancelled']
            )
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->value('appointment_date');

        $latestDate = $latestRecordDate
            ? \Carbon\Carbon::parse(
                $latestRecordDate
            )->format('M d, Y')
            : null;

        $records = Appointment::with(['procedure', 'dentist'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate($perPage, ['*'], 'records_page')
            ->withQueryString();

        $completedOdontogramVisits = Appointment::query()
            ->with([
                'procedure:id,appointment_id',
            ])
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->orderBy('id', 'asc')
            ->get([
                'id',
                'appointment_date',
                'appointment_time',
            ]);

        $previousOdontogramByVisitId = [];
        $previousCompletedSnapshot = [];

        foreach ($completedOdontogramVisits as $completedVisit) {
            $previousOdontogramByVisitId[$completedVisit->id] =
                $previousCompletedSnapshot;

            $currentSnapshot = data_get(
                $completedVisit,
                'procedure.odontogram_data',
                []
            );

            if (is_string($currentSnapshot)) {
                $decodedSnapshot = json_decode(
                    $currentSnapshot,
                    true
                );

                $currentSnapshot =
                    is_array($decodedSnapshot)
                    ? $decodedSnapshot
                    : [];
            }

            if (
                is_array($currentSnapshot) &&
                !empty($currentSnapshot)
            ) {
                $previousCompletedSnapshot =
                    array_values($currentSnapshot);
            }
        }

        $documentRequestsPerPage = (int) request(
            'document_requests_per_page',
            10
        );

        if (
            !in_array(
                $documentRequestsPerPage,
                [10, 20, 50, 100],
                true
            )
        ) {
            $documentRequestsPerPage = 10;
        }

        $documentRequests = DocumentRequest::withStateColumns()
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->paginate(
                $documentRequestsPerPage,
                ['*'],
                'document_requests_page'
            )
            ->withQueryString();

        $documentRequestStatusCounts = DocumentRequest::withStateColumns()
            ->where('patient_id', $patient->id)
            ->selectRaw(
                'LOWER(status) as status_key, COUNT(*) as total'
            )
            ->groupByRaw('LOWER(status)')
            ->pluck('total', 'status_key')
            ->toArray();

        $documentRequestStats = [
            'total' => array_sum(
                array_map('intval', $documentRequestStatusCounts)
            ),

            'pending' => (int) (
                $documentRequestStatusCounts['pending']
                ?? 0
            ),

            'approved' => (int) (
                ($documentRequestStatusCounts['approved'] ?? 0)
                + ($documentRequestStatusCounts['ready'] ?? 0)
                + ($documentRequestStatusCounts['ready-for-pickup'] ?? 0)
                + ($documentRequestStatusCounts['ready_for_pickup'] ?? 0)
                + ($documentRequestStatusCounts['ready-for-release'] ?? 0)
                + ($documentRequestStatusCounts['ready_for_release'] ?? 0)
                + ($documentRequestStatusCounts['released'] ?? 0)
            ),

            'rejected' => (int) (
                $documentRequestStatusCounts['rejected']
                ?? 0
            ),
        ];

        AuditLogger::log(
            'view',
            'records',
            "Patient viewed dental records"
        );

        return view('patient.record', compact(
            'patient',
            'records',
            'latestDate',
            'documentRequests',
            'documentRequestStats',
            'previousOdontogramByVisitId'
        ));
    }
}
