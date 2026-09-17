<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dentist\DentistReportController;
use App\Models\DocumentRequest;
use App\Notifications\DocumentRequestApprovedNotification;
use App\Notifications\DocumentRequestRejectedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

use Illuminate\Support\Facades\Mail;

use App\Mail\DocumentRequestApprovedMail;
use App\Mail\DocumentRequestRejectedMail;

class DocumentRequestController extends Controller
{
    private const VALID_STATUSES = ['pending', 'approved', 'rejected'];

    private const LEGACY_APPROVED_STATUSES = [
        'approved',
        'ready',
        'ready-for-pickup',
        'ready-for-release',
        'released',
    ];

    public function index(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $status   = $this->normalizeStatusFilter($request->get('status', ''));
        $type     = trim((string) $request->get('type', ''));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo   = trim((string) $request->get('date_to', ''));
        $sort     = trim((string) $request->get('sort', 'newest'));

        $perPageInput = (int) $request->input('per_page', 10);

        $perPage = in_array(
            $perPageInput,
            [10, 20, 50, 100],
            true
        ) ? $perPageInput : 10;

        $query = $this->buildFilteredQuery($request);

        $requests = $query->paginate($perPage)->withQueryString();

        $stats = $this->getDocumentRequestStats();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,

                'requests' => $requests->getCollection()
                    ->map(fn($requestItem) => $this->formatRequestPayload($requestItem))
                    ->values(),

                'pagination' => [
                    'total' => $requests->total(),
                    'from' => $requests->firstItem() ?? 0,
                    'to' => $requests->lastItem() ?? 0,
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                ],

                'stats' => $stats,

                'types' => DocumentRequest::withStateColumns()
                    ->whereNotNull('document_type')
                    ->pluck('document_type')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values(),

                'filters' => [
                    'search' => $search,
                    'status' => $status ?: 'all',
                    'type' => $type,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'sort' => $sort,
                    'per_page' => $perPage,
                ],
            ]);
        }

        $refreshItems = DocumentRequest::withStateColumns()
            ->select('id')
            ->orderByDesc('id')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
            ])
            ->values();

        return view('shared.document-requests', [
            'role' => 'admin',
            'refreshItems' => $refreshItems,
            'requests' => $requests,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sort' => $sort,
            'perPage' => $perPage,

            'routes' => [
                'index' => route(
                    'admin.document-requests.index'
                ),

                'data' => null,

                'approve' => url('/admin/document-requests/__ID__/approve'),
                'reject' => url('/admin/document-requests/__ID__/reject'),

                'export' => route(
                    'admin.document-requests.export'
                ),

                'print_queue' => route(
                    'admin.document-requests.print-queue'
                ),
            ],

            'methods' => [
                'approve' => 'PATCH',
                'reject' => 'PATCH',
            ],

            'permissions' => [
                'can_approve' => true,
                'can_reject' => true,
                'can_export' => true,
                'can_print' => true,
            ],
        ]);
    }

    public function data(Request $request)
    {
        $requests = $this->buildFilteredQuery($request)
            ->get()
            ->map(fn($requestItem) => $this->formatRequestPayload($requestItem))
            ->values();

        return response()->json([
            'success' => true,
            'requests' => $requests,
            'stats' => $this->getDocumentRequestStats(),
            'types' => DocumentRequest::withStateColumns()
                ->whereNotNull('document_type')
                ->pluck('document_type')
                ->filter()
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function show($id)
    {
        $documentRequest = DocumentRequest::withStateColumns()->with('patient')->findOrFail($id);

        return response()->json($this->formatRequestPayload($documentRequest));
    }

    public function approve($id)
    {
        try {
            $documentRequest = DB::transaction(function () use ($id) {
                $requestItem = DocumentRequest::withStateColumns()->with('patient.user')->findOrFail($id);

                $requestItem->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'rejection_reason' => null,
                ]);

                return $requestItem->fresh(['patient.user', 'approvedBy']);
            });

            if ($documentRequest->patient?->user) {
                $documentRequest->patient->user->notify(
                    new DocumentRequestApprovedNotification($documentRequest)
                );
            } else {
                Log::warning('Document request approved but patient user was not found.', [
                    'document_request_id' => $documentRequest->id,
                    'patient_id' => $documentRequest->patient_id,
                ]);
            }

            try {
                $documentRequest->loadMissing('patient');

                $patientEmail = $documentRequest->patient?->email;

                if ($patientEmail) {
                    Mail::to($patientEmail)
                        ->send(new DocumentRequestApprovedMail($documentRequest));

                    Log::info('Document request approval email sent.', [
                        'document_request_id' => $documentRequest->id,
                        'patient_id' => $documentRequest->patient_id,
                        'email' => $patientEmail,
                    ]);
                } else {
                    Log::warning('Document request approval email not sent: patient has no email.', [
                        'document_request_id' => $documentRequest->id,
                        'patient_id' => $documentRequest->patient_id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Document request approval email failed.', [
                    'document_request_id' => $documentRequest->id,
                    'patient_id' => $documentRequest->patient_id,
                    'error' => $e->getMessage(),
                ]);
            }

            return app(DentistReportController::class)
                ->buildApprovedDocumentRequestPdfResponse($documentRequest);
        } catch (\Throwable $e) {
            Log::error('Document request approval failed.', [
                'document_request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve document request.',
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $documentRequest = DocumentRequest::withStateColumns()->with('patient.user')->findOrFail($id);

            $documentRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
            ]);

            $documentRequest->refresh();
            $documentRequest->loadMissing('patient.user');

            if ($documentRequest->patient?->user) {
                $documentRequest->patient->user->notify(
                    new DocumentRequestRejectedNotification($documentRequest)
                );
            } else {
                Log::warning('Document request rejected but patient user was not found.', [
                    'document_request_id' => $documentRequest->id,
                    'patient_id' => $documentRequest->patient_id,
                ]);
            }

            try {
                $documentRequest->loadMissing('patient');

                $patientEmail = $documentRequest->patient?->email;

                if ($patientEmail) {
                    Mail::to($patientEmail)
                        ->send(new DocumentRequestRejectedMail($documentRequest));

                    Log::info('Document request rejection email sent.', [
                        'document_request_id' => $documentRequest->id,
                        'patient_id' => $documentRequest->patient_id,
                        'email' => $patientEmail,
                    ]);
                } else {
                    Log::warning('Document request rejection email not sent: patient has no email.', [
                        'document_request_id' => $documentRequest->id,
                        'patient_id' => $documentRequest->patient_id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Document request rejection email failed.', [
                    'document_request_id' => $documentRequest->id,
                    'patient_id' => $documentRequest->patient_id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document request rejected successfully.',
                'status' => 'rejected',
                'request' => $this->formatRequestPayload($documentRequest),
                'stats' => $this->getDocumentRequestStats(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Document request rejection failed.', [
                'document_request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject document request.',
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $requests = $this->buildFilteredQuery($request)->get();

        $filename = 'document_requests_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');

            fwrite($file, "\xEF\xBB\xBF");

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
                $payload = $this->formatRequestPayload($request);

                fputcsv($file, [
                    $payload['reference_number'],
                    $payload['patient_name'],
                    $payload['patient_identifier'],
                    $payload['document_type'],
                    $payload['purpose'],
                    $payload['request_date'],
                    $payload['request_time'],
                    ucfirst($payload['status']),
                    optional($request->created_at)?->format('Y-m-d h:i A'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function printQueue()
    {
        $requests = DocumentRequest::withStateColumns()->with('patient')
            ->whereIn(DB::raw('LOWER(status)'), array_merge(['pending'], self::LEGACY_APPROVED_STATUSES))
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.document-request-print-queue', compact('requests'));
    }

    private function buildFilteredQuery(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $status   = $this->normalizeStatusFilter($request->get('status', ''));
        $type     = trim((string) $request->get('type', ''));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo   = trim((string) $request->get('date_to', ''));
        $sort     = trim((string) $request->get('sort', 'newest'));

        $query = DocumentRequest::withStateColumns()->with('patient');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'reference_number',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'document_type',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'purpose',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'status',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'patient',
                        function ($patientQuery) use ($search) {
                            $patientQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'information',
                                    function ($informationQuery) use ($search) {
                                        $informationQuery
                                            ->where(
                                                'student_no',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'faculty_code',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
            });
        }

        if ($status !== '' && $status !== 'all') {
            if ($status === 'approved') {
                $query->whereIn(DB::raw('LOWER(status)'), self::LEGACY_APPROVED_STATUSES);
            } else {
                $query->whereRaw('LOWER(status) = ?', [$status]);
            }
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

            case 'az':
            case 'alpha':
                $query
                    ->leftJoin(
                        'patients',
                        'document_requests.patient_id',
                        '=',
                        'patients.id'
                    )
                    ->orderBy('patients.name', 'asc')
                    ->select('document_requests.*');
                break;

            case 'za':
                $query
                    ->leftJoin(
                        'patients',
                        'document_requests.patient_id',
                        '=',
                        'patients.id'
                    )
                    ->orderBy('patients.name', 'desc')
                    ->select('document_requests.*');
                break;

            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query;
    }

    private function getDocumentRequestStats(): array
    {
        $counts = DocumentRequest::withStateColumns()
            ->selectRaw('LOWER(status) as status_key, COUNT(*) as total')
            ->groupBy('status_key')
            ->pluck('total', 'status_key')
            ->toArray();

        return [
            'total' => DocumentRequest::count(),
            'all' => DocumentRequest::count(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'approved' => (int) (
                ($counts['approved'] ?? 0)
                + ($counts['ready'] ?? 0)
                + ($counts['ready-for-pickup'] ?? 0)
                + ($counts['ready-for-release'] ?? 0)
                + ($counts['released'] ?? 0)
            ),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    private function formatRequestPayload(DocumentRequest $documentRequest): array
    {
        $documentRequest->loadMissing('patient');

        $patient = $documentRequest->patient;
        $createdAt = $documentRequest->created_at;

        $patient?->loadMissing('information');

        $patientIdentifier =
            $patient?->information?->student_no
            ?? $patient?->information?->faculty_code
            ?? $patient?->id
            ?? 'No ID set';

        return [
            'id' => $documentRequest->id,
            'reference_number' => $documentRequest->reference_number
                ?? ('DR-' . str_pad((string) $documentRequest->id, 5, '0', STR_PAD_LEFT)),
            'patient_name' => optional($patient)->name ?? 'Unknown Patient',
            'patient_identifier' => $patientIdentifier,
            'patient_id' => $patientIdentifier,
            'sub_label' => $patientIdentifier,
            'document_type' => $this->formatDocumentType($documentRequest->document_type ?? 'Document'),
            'document_type_raw' => $documentRequest->document_type ?? 'Document',
            'purpose' => $documentRequest->purpose ?: '—',
            'status' => $this->normalizeStatus($documentRequest->status),
            'request_date' => optional($createdAt)?->format('M d, Y') ?? '—',
            'request_time' => optional($createdAt)?->format('h:i A') ?? '',
            'request_sort_date' => optional($createdAt)?->format('Y-m-d H:i:s') ?? '',
            'filter_date' => optional($createdAt)?->format('Y-m-d') ?? '',
            'copies_needed' => $documentRequest->copies_needed ?? 1,
            'rejection_reason' => $documentRequest->rejection_reason ?? null,
            'patient_photo_url' => optional($patient)->profile_photo_url
                ?? optional($patient)->profile_picture_url
                ?? optional($patient)->avatar_url
                ?? optional($patient)->photo_url
                ?? '',
            'activities' => [
                [
                    'date' => optional($createdAt)?->format('M d, Y h:i A') ?? '—',
                    'description' => 'Request submitted.',
                ],
            ],
        ];
    }

    private function normalizeStatus($status): string
    {
        $status = strtolower(str_replace('_', '-', (string) ($status ?: 'pending')));

        if (in_array($status, self::LEGACY_APPROVED_STATUSES, true)) {
            return 'approved';
        }

        if ($status === 'rejected') {
            return 'rejected';
        }

        return 'pending';
    }

    private function normalizeStatusFilter($status): string
    {
        $status = strtolower(str_replace('_', '-', trim((string) $status)));

        if ($status === '' || $status === 'all') {
            return '';
        }

        if (in_array($status, ['ready', 'ready-for-pickup', 'ready-for-release', 'released'], true)) {
            return 'approved';
        }

        return in_array($status, self::VALID_STATUSES, true) ? $status : '';
    }

    private function formatDocumentType($type): string
    {
        return ucwords(str_replace(['_', '-'], ' ', (string) ($type ?: 'Document')));
    }
}
