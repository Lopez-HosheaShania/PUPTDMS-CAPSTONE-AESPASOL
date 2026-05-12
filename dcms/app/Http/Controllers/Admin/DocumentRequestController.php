<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use App\Notifications\DocumentRequestApprovedNotification;
use App\Notifications\DocumentRequestRejectedNotification;


class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $status   = trim((string) $request->get('status', ''));
        $type     = trim((string) $request->get('type', ''));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo   = trim((string) $request->get('date_to', ''));
        $sort     = trim((string) $request->get('sort', 'newest'));

        $query = DocumentRequest::with('patient');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('student_no', 'like', "%{$search}%")
                            ->orWhere('faculty_code', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
        }

        if ($type !== '') {
            $query->whereRaw('LOWER(document_type) = ?', [strtolower($type)]);
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        switch (strtolower($sort)) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;

            case 'alpha':
                $query->leftJoin('patients', 'document_requests.patient_id', '=', 'patients.id')
                    ->orderBy('patients.name', 'asc')
                    ->select('document_requests.*');
                break;

            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $requests = $query->paginate(10)->withQueryString();

        $stats = [
            'total'     => DocumentRequest::count(),
            'pending'   => DocumentRequest::whereRaw('LOWER(status) = ?', ['pending'])->count(),
            'approved'  => DocumentRequest::whereRaw('LOWER(status) = ?', ['approved'])->count(),
            'ready'     => DocumentRequest::whereRaw('LOWER(status) = ?', ['ready'])->count(),
            'released'  => DocumentRequest::whereRaw('LOWER(status) = ?', ['released'])->count(),
            'rejected'  => DocumentRequest::whereRaw('LOWER(status) = ?', ['rejected'])->count(),
        ];

        return view('admin.document-request', compact(
            'requests',
            'stats',
            'search',
            'status',
            'type',
            'dateFrom',
            'dateTo',
            'sort'
        ));
    }

    public function show($id)
    {
        $documentRequest = DocumentRequest::with('patient')->findOrFail($id);

        $patient = $documentRequest->patient;

        return response()->json([
            'id' => $documentRequest->id,
            'reference_number' => $documentRequest->reference_number,
            'document_type' => $documentRequest->document_type,
            'purpose' => $documentRequest->purpose,
            'request_date' => $documentRequest->request_date,
            'request_time' => $documentRequest->request_time,
            'status' => strtolower((string) $documentRequest->status),
            'created_at' => optional($documentRequest->created_at)?->format('M d, Y'),
            'patient_name' => optional($patient)->name ?? 'Unknown Patient',
            'patient_id' => optional($patient)->student_no
                ?? optional($patient)->faculty_code
                ?? optional($patient)->id
                ?? 'No ID',
            'copies_needed' => 1,
            'activities' => [
                [
                    'date' => optional($documentRequest->created_at)?->format('M d, Y h:i A') ?? '—',
                    'description' => 'Request submitted.',
                ],
            ],
        ]);
    }

    public function approve($id)
    {
        $documentRequest = DocumentRequest::with('patient.user')->findOrFail($id);

        $documentRequest->status = 'approved';
        $documentRequest->save();

        if ($documentRequest->patient && $documentRequest->patient->user) {
            $documentRequest->patient->user->notify(
                new DocumentRequestApprovedNotification($documentRequest)
            );
        }

        return redirect()
            ->route('admin.document-requests.index')
            ->with('success', 'Document request approved successfully.');
    }

    public function release($id)
    {
        $documentRequest = DocumentRequest::findOrFail($id);
        $documentRequest->status = 'released';
        $documentRequest->save();

        return redirect()
            ->route('admin.document-requests.index')
            ->with('success', 'Document request released successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $documentRequest = DocumentRequest::with('patient.user')->findOrFail($id);

        $documentRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason
        ]);

        if ($documentRequest->patient && $documentRequest->patient->user) {
            $documentRequest->patient->user->notify(
                new DocumentRequestRejectedNotification($documentRequest)
            );
        }

        return redirect()
            ->route('admin.document-requests.index')
            ->with('success', 'Document request rejected successfully.');
    }

    public function export(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $status   = trim((string) $request->get('status', ''));
        $type     = trim((string) $request->get('type', ''));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo   = trim((string) $request->get('date_to', ''));
        $sort     = trim((string) $request->get('sort', 'newest'));

        $query = DocumentRequest::with('patient');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('student_no', 'like', "%{$search}%")
                            ->orWhere('faculty_code', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
        }

        if ($type !== '') {
            $query->whereRaw('LOWER(document_type) = ?', [strtolower($type)]);
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        switch (strtolower($sort)) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;

            case 'alpha':
                $query->leftJoin('patients', 'document_requests.patient_id', '=', 'patients.id')
                    ->orderBy('patients.name', 'asc')
                    ->select('document_requests.*');
                break;

            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $requests = $query->get();

        $filename = 'document_requests_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Reference Number',
                'Patient Name',
                'Patient ID',
                'Document Type',
                'Purpose',
                'Request Date',
                'Request Time',
                'Status',
                'Created At',
            ]);

            foreach ($requests as $request) {
                fputcsv($file, [
                    $request->reference_number,
                    optional($request->patient)->name ?? '',
                    optional($request->patient)->student_no
                        ?? optional($request->patient)->faculty_code
                        ?? optional($request->patient)->id
                        ?? '',
                    $request->document_type,
                    $request->purpose,
                    $request->request_date,
                    $request->request_time,
                    $request->status,
                    optional($request->created_at)?->format('Y-m-d h:i A'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function printQueue()
    {
        $requests = DocumentRequest::with('patient')
            ->whereIn(DB::raw('LOWER(status)'), ['pending', 'approved', 'ready'])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.document-request-print-queue', compact('requests'));
    }
}
