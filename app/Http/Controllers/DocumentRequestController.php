<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Dentist\DentistReportController;
use Illuminate\Http\Request;
use App\Models\DocumentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\Auth;
use App\Notifications\DocumentRequestSubmittedNotification;
use App\Notifications\DocumentRequestApprovedNotification;
use App\Notifications\DocumentRequestRejectedNotification;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

use App\Mail\DocumentRequestApprovedMail;
use App\Mail\DocumentRequestRejectedMail;

class DocumentRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:100',
            'purpose' => 'required|string|max:150',
        ]);

        $user = Auth::user();
        $patient = Patient::where('user_id', Auth::id())->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found. Please log in again.',
            ], 401);
        }

        if ($user && $user->email && $patient->email !== $user->email) {
            $patient->forceFill(['email' => $user->email])->save();
        }

        session(['patient_id' => $patient->id]);

        try {
            $documentRequest = DB::transaction(function () use ($validated, $patient) {
                // Serialize document-request submissions for this patient so the
                // duplicate-request and two-per-day limits cannot be bypassed by
                // near-simultaneous submissions.
                $lockedPatient = Patient::query()
                    ->lockForUpdate()
                    ->findOrFail($patient->id);

                $documentType = trim((string) $validated['document_type']);
                $purpose = trim((string) $validated['purpose']);
                $documentTypeKey = $this->canonicalDocumentTypeKey($documentType);

                if ($this->requiresPriorClinicalRecord($documentTypeKey)) {
                    $hasPriorClinicalRecord = Appointment::query()
                        ->where('patient_id', $lockedPatient->id)
                        ->whereRaw('LOWER(status) = ?', ['completed'])
                        ->whereHas('procedure')
                        ->exists();

                    if (!$hasPriorClinicalRecord) {
                        throw ValidationException::withMessages([
                            'document_type' => 'No completed dental record found yet.',
                        ]);
                    }
                }

                $hasSamePendingRequest = DocumentRequest::query()
                    ->where('patient_id', $lockedPatient->id)
                    ->whereRaw('LOWER(status) = ?', ['pending'])
                    ->get(['document_type'])
                    ->contains(function (DocumentRequest $existingRequest) use ($documentTypeKey) {
                        return $this->canonicalDocumentTypeKey($existingRequest->document_type) === $documentTypeKey;
                    });

                if ($hasSamePendingRequest) {
                    throw ValidationException::withMessages([
                        'document_type' => 'You already have a pending request for this document.',
                    ]);
                }

                $todayRequestCount = DocumentRequest::query()
                    ->where('patient_id', $lockedPatient->id)
                    ->whereDate('request_date', now()->toDateString())
                    ->count();

                if ($todayRequestCount >= 2) {
                    throw ValidationException::withMessages([
                        "document_type" => "You've reached today's 2-document request limit.",
                    ]);
                }

                $nextId = (DocumentRequest::max('id') ?? 0) + 1;
                $assignedDentistId = Appointment::query()
                    ->where('patient_id', $lockedPatient->id)
                    ->whereNotNull('dentist_id')
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('appointment_time')
                    ->value('dentist_id');

                return DocumentRequest::create([
                    'patient_id' => $lockedPatient->id,
                    'assigned_dentist_id' => $assignedDentistId,
                    'reference_number' => 'DOC-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT),
                    'document_type' => $documentType,
                    'purpose' => $purpose,
                    'request_date' => now()->toDateString(),
                    'request_time' => now()->toTimeString(),
                    'status' => 'pending',
                ]);
            });

            try {
                $recipients = User::whereHas('role', function ($query) {
                    $query->whereIn('slug', ['dentist', 'admin']);
                })->get()->unique('id');

                foreach ($recipients as $recipient) {
                    try {
                        $recipient->notify(
                            new DocumentRequestSubmittedNotification($documentRequest)
                        );
                    } catch (\Throwable $notificationException) {
                        Log::warning('Document request notification failed.', [
                            'document_request_id' => $documentRequest->id,
                            'recipient_id' => $recipient->id,
                            'error' => $notificationException->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $notificationException) {
                Log::warning('Document request notification dispatch failed.', [
                    'document_request_id' => $documentRequest->id,
                    'error' => $notificationException->getMessage(),
                ]);
            }

            try {
                AuditLogger::log(
                    'create',
                    'document_request',
                    'Patient submitted document request'
                );
            } catch (\Throwable $auditException) {
                Log::warning('Document request audit logging failed.', [
                    'document_request_id' => $documentRequest->id,
                    'error' => $auditException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document request submitted successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
                    ?: 'The document request could not be submitted.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Document request submission failed.', [
                'patient_id' => $patient->id,
                'document_type' => $validated['document_type'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request. Please try again.',
            ], 500);
        }
    }

    public function index()
    {
        $requests = DocumentRequest::where('patient_id', session('patient_id'))
            ->orderByDesc('created_at')
            ->get();

        return view('document-requests.index', compact('requests'));
    }

    public function dentistIndex(Request $request)
    {
        $activeRole =
            session('impersonated_role')
            ?: session('role')
            ?: optional(optional(Auth::user())->role)->slug;

        abort_unless(optional(Auth::user())->canAccessClinicalArea($activeRole), 403);

        $search = trim(
            (string) $request->get('search', '')
        );

        $status = $this->normalizeDocumentRequestStatusFilter(
            $request->get('status', '')
        );

        $type = trim(
            (string) $request->get('type', '')
        );

        $dateFrom = trim(
            (string) $request->get('date_from', '')
        );

        $dateTo = trim(
            (string) $request->get('date_to', '')
        );

        $sort = trim(
            (string) $request->get('sort', 'newest')
        );

        $perPageInput = (int) $request->input(
            'per_page',
            10
        );

        $perPage = in_array(
            $perPageInput,
            [10, 20, 50, 100],
            true
        )
            ? $perPageInput
            : 10;

        $query = $this->buildDocumentRequestQuery(
            $request
        );

        $requests = $query
            ->paginate($perPage)
            ->withQueryString();

        $stats = $this->getDocumentRequestStats();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,

                'requests' => $requests
                    ->getCollection()
                    ->map(
                        fn($item) =>
                        $this->formatDocumentRequestPayload(
                            $item
                        )
                    )
                    ->values(),

                'pagination' => [
                    'total' =>
                    $requests->total(),

                    'from' =>
                    $requests->firstItem() ?? 0,

                    'to' =>
                    $requests->lastItem() ?? 0,

                    'current_page' =>
                    $requests->currentPage(),

                    'last_page' =>
                    $requests->lastPage(),

                    'per_page' =>
                    $requests->perPage(),
                ],

                'stats' => $stats,

                'types' => DocumentRequest::query()
                    ->whereNotNull('document_type')
                    ->pluck('document_type')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values(),
            ]);
        }

        $user = Auth::user();

        $notifications = $user
            ? $user->currentRoleNotifications()
            ->latest()
            ->take(10)
            ->get()
            : collect();

        $refreshItems = DocumentRequest::query()
            ->select('id')
            ->orderByDesc('id')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
            ])
            ->values();

        return view(
            'shared.document-requests',
            [
                'role' => 'dentist',
                'requests' => $requests,
                'stats' => $stats,
                'notifications' => $notifications,
                'refreshItems' => $refreshItems,
                'search' => $search,
                'status' => $status,
                'type' => $type,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'sort' => $sort,
                'perPage' => $perPage,

                'routes' => [
                    'index' => route(
                        'dentist.dentist.documentrequests'
                    ),

                    'data' => null,

                    'approve' => url('/dentist/document-requests/__ID__/approve'),
                    'reject' => url('/dentist/document-requests/__ID__/reject'),

                    'export' => null,
                    'print_queue' => null,
                ],

                'methods' => [
                    'approve' => 'POST',
                    'reject' => 'POST',
                ],

                'permissions' => [
                    'can_approve' => true,
                    'can_reject' => true,
                    'can_export' => false,
                    'can_print' => false,
                ],
            ]
        );
    }

    public function dentistData(Request $request)
    {
        $activeRole =
            session('impersonated_role')
            ?: session('role')
            ?: optional(optional(Auth::user())->role)->slug;

        abort_unless(optional(Auth::user())->canAccessClinicalArea($activeRole), 403);

        $requests = $this
            ->buildDocumentRequestQuery($request)
            ->get()
            ->map(
                fn($item) =>
                $this->formatDocumentRequestPayload(
                    $item
                )
            )
            ->values();

        return response()->json([
            'success' => true,

            'requests' => $requests,

            'stats' =>
            $this->getDocumentRequestStats(),

            'types' => DocumentRequest::query()
                ->whereNotNull('document_type')
                ->where('document_type', '!=', '')
                ->pluck('document_type')
                ->filter()
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function approve(Request $request, $id)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        try {
            $docRequest = DB::transaction(function () use ($id) {
                $documentRequest = DocumentRequest::with('patient.user')->findOrFail($id);

                $documentRequest->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => Auth::id(),
                    'rejection_reason' => null,
                ]);

                return $documentRequest->fresh(['patient.user', 'approvedBy']);
            });

            if ($docRequest->patient && $docRequest->patient->user) {
                $docRequest->patient->user->notify(
                    new DocumentRequestApprovedNotification($docRequest)
                );
            }

            try {
                $docRequest->loadMissing('patient');

                $patientEmail = $docRequest->patient?->email;

                if ($patientEmail) {
                    Mail::to($patientEmail)
                        ->send(new DocumentRequestApprovedMail($docRequest));

                    Log::info('Document request approval email sent.', [
                        'document_request_id' => $docRequest->id,
                        'patient_id' => $docRequest->patient_id,
                        'email' => $patientEmail,
                    ]);
                } else {
                    Log::warning('Document request approval email not sent: patient has no email.', [
                        'document_request_id' => $docRequest->id,
                        'patient_id' => $docRequest->patient_id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Document request approval email failed.', [
                    'document_request_id' => $docRequest->id,
                    'patient_id' => $docRequest->patient_id,
                    'error' => $e->getMessage(),
                ]);
            }

            return app(DentistReportController::class)
                ->buildApprovedDocumentRequestPdfResponse($docRequest);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve document request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $docRequest = DocumentRequest::with('patient.user')->findOrFail($id);

        $docRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        if ($docRequest->patient && $docRequest->patient->user) {
            $docRequest->patient->user->notify(
                new DocumentRequestRejectedNotification($docRequest)
            );
        }

        try {
            $docRequest->loadMissing('patient');

            $patientEmail = $docRequest->patient?->email;

            if ($patientEmail) {
                Mail::to($patientEmail)
                    ->send(new DocumentRequestRejectedMail($docRequest));

                Log::info('Document request rejection email sent.', [
                    'document_request_id' => $docRequest->id,
                    'patient_id' => $docRequest->patient_id,
                    'email' => $patientEmail,
                ]);
            } else {
                Log::warning('Document request rejection email not sent: patient has no email.', [
                    'document_request_id' => $docRequest->id,
                    'patient_id' => $docRequest->patient_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Document request rejection email failed.', [
                'document_request_id' => $docRequest->id,
                'patient_id' => $docRequest->patient_id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document request rejected.'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,ready,released',
        ]);

        $docRequest = DocumentRequest::findOrFail($id);

        $updates = [
            'status' => $request->status,
        ];

        if ($request->status === 'approved') {
            $updates['approved_at'] = now();
            $updates['approved_by'] = Auth::id();
            $updates['rejection_reason'] = null;
        }

        if ($request->status === 'rejected') {
            $updates['approved_at'] = null;
            $updates['approved_by'] = null;
        }

        $docRequest->update($updates);

        return back()->with('success', 'Request updated.');
    }
    private const LEGACY_APPROVED_STATUSES = [
        'approved',
        'ready',
        'ready-for-pickup',
        'ready-for-release',
        'released',
    ];

    private function buildDocumentRequestQuery(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $status = $this->normalizeDocumentRequestStatusFilter(
            $request->get('status', '')
        );
        $type = trim((string) $request->get('type', ''));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo = trim((string) $request->get('date_to', ''));
        $sort = trim((string) $request->get('sort', 'newest'));

        $query = DocumentRequest::with('patient');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('student_no', 'like', "%{$search}%")
                            ->orWhere('faculty_code', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '' && $status !== 'all') {
            if ($status === 'approved') {
                $query->whereIn(
                    DB::raw('LOWER(status)'),
                    self::LEGACY_APPROVED_STATUSES
                );
            } else {
                $query->whereRaw('LOWER(status) = ?', [$status]);
            }
        }

        if ($type !== '') {
            $query->whereRaw(
                'LOWER(document_type) = ?',
                [strtolower($type)]
            );
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
        $counts = DocumentRequest::query()
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

    private function formatDocumentRequestPayload(
        DocumentRequest $documentRequest
    ): array {
        $documentRequest->loadMissing('patient');

        $patient = $documentRequest->patient;
        $createdAt = $documentRequest->created_at;

        $patientIdentifier =
            optional($patient)->student_no
            ?? optional($patient)->student_number
            ?? optional($patient)->student_id
            ?? optional($patient)->faculty_code
            ?? optional($patient)->employee_no
            ?? optional($patient)->id
            ?? 'No ID set';

        return [
            'id' => $documentRequest->id,

            'reference_number' =>
            $documentRequest->reference_number
                ?? (
                    'DR-' .
                    str_pad(
                        (string) $documentRequest->id,
                        5,
                        '0',
                        STR_PAD_LEFT
                    )
                ),

            'patient_name' =>
            optional($patient)->name
                ?? optional($patient)->full_name
                ?? 'Unknown Patient',

            'patient_identifier' => $patientIdentifier,
            'patient_id' => $patientIdentifier,
            'sub_label' => $patientIdentifier,

            'document_type' => $this->formatDocumentRequestType(
                $documentRequest->document_type
                    ?? 'Document'
            ),

            'document_type_raw' =>
            $documentRequest->document_type
                ?? 'Document',

            'purpose' =>
            $documentRequest->purpose ?: '—',

            'status' => $this->normalizeDocumentRequestStatus(
                $documentRequest->status
            ),

            'request_date' =>
            optional($createdAt)?->format('M d, Y')
                ?? '—',

            'request_time' =>
            optional($createdAt)?->format('h:i A')
                ?? '',

            'request_sort_date' =>
            optional($createdAt)?->format('Y-m-d H:i:s')
                ?? '',

            'filter_date' =>
            optional($createdAt)?->format('Y-m-d')
                ?? '',

            'copies_needed' =>
            $documentRequest->copies_needed ?? 1,

            'rejection_reason' =>
            $documentRequest->rejection_reason,

            'patient_photo_url' =>
            optional($patient)->profile_photo_url
                ?? optional($patient)->profile_picture_url
                ?? optional($patient)->avatar_url
                ?? optional($patient)->photo_url
                ?? '',
        ];
    }

    private function normalizeDocumentRequestStatus($status): string
    {
        $status = strtolower(
            str_replace(
                '_',
                '-',
                (string) ($status ?: 'pending')
            )
        );

        if (
            in_array(
                $status,
                self::LEGACY_APPROVED_STATUSES,
                true
            )
        ) {
            return 'approved';
        }

        if ($status === 'rejected') {
            return 'rejected';
        }

        return 'pending';
    }

    private function normalizeDocumentRequestStatusFilter($status): string
    {
        $status = strtolower(
            str_replace(
                '_',
                '-',
                trim((string) $status)
            )
        );

        if ($status === '' || $status === 'all') {
            return '';
        }

        if (
            in_array(
                $status,
                [
                    'ready',
                    'ready-for-pickup',
                    'ready-for-release',
                    'released',
                ],
                true
            )
        ) {
            return 'approved';
        }

        return in_array(
            $status,
            ['pending', 'approved', 'rejected'],
            true
        )
            ? $status
            : '';
    }

    private function formatDocumentRequestType($type): string
    {
        return ucwords(
            str_replace(
                ['_', '-'],
                ' ',
                (string) ($type ?: 'Document')
            )
        );
    }
    private function canonicalDocumentTypeKey(?string $documentType): string
    {
        $normalized = strtolower(trim((string) $documentType));
        $normalized = str_replace(['_', '-', '/'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return match ($normalized) {
            'all dental records',
            'medical records',
            'diagnosis and treatment',
            'dental health record' => 'dental_health_record',
            'annual dental clearance' => 'annual_dental_clearance',
            'dental clearance' => 'dental_clearance',
            default => $normalized,
        };
    }

    private function requiresPriorClinicalRecord(string $documentTypeKey): bool
    {
        return in_array(
            $documentTypeKey,
            [
                'dental_health_record',
                'dental_clearance',
                'annual_dental_clearance',
            ],
            true
        );
    }

}
