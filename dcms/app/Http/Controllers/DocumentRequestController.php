<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\AuditLogger;
use App\Notifications\DocumentRequestSubmittedNotification;
use App\Notifications\DocumentRequestApprovedNotification;
use App\Notifications\DocumentRequestRejectedNotification;

class DocumentRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|max:100',
            'purpose' => 'required|string|max:150',
        ]);

        $patient = Patient::where('user_id', auth()->id())->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found. Please log in again.',
            ], 401);
        }

        session(['patient_id' => $patient->id]);

        try {
            $documentRequest = DB::transaction(function () use ($request, $patient) {
                $nextId = (DocumentRequest::max('id') ?? 0) + 1;

                return DocumentRequest::create([
                    'patient_id' => $patient->id,
                    'reference_number' => 'DOC-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT),
                    'document_type' => $request->document_type,
                    'purpose' => $request->purpose,
                    'request_date' => Carbon::now()->toDateString(),
                    'request_time' => Carbon::now()->toTimeString(),
                    'status' => 'pending',
                ]);
            });

           $recipients = User::whereHas('role', function ($query) {
                $query->whereIn('slug', ['dentist', 'admin']);
            })->get()->unique('id');

            foreach ($recipients as $recipient) {
                $recipient->notify(new DocumentRequestSubmittedNotification($documentRequest));
            }

            if ($patient) {
                AuditLogger::log(
                    'create',
                    'document_request',
                    'Patient submitted document request'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Document request submitted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request: ' . $e->getMessage(),
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
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $notifications = auth()->user()
            ? auth()->user()->notifications()->latest()->take(10)->get()
            : collect([]);

        return view('dentist.dentist-documentrequests', compact('notifications'));
    }

    public function dentistData(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = DocumentRequest::with('patient');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('student_no', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->whereRaw(
                'LOWER(document_type) = ?',
                [strtolower($request->type)]
            );
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        $requests = $query
            ->latest('request_date')
            ->latest('request_time')
            ->get()
            ->map(function ($req) {
                $patient = $req->patient;

                $fullName = $patient->full_name ?? $patient->name ?? 'Unknown Patient';

                $parts = explode(' ', trim($fullName));
                $last = count($parts) > 1 ? array_pop($parts) : ($parts[0] ?? '—');
                $first = implode(' ', $parts);
                $displayName = $first ? "{$last}, {$first}" : $last;

                $studentNo = trim((string) ($patient->student_no ?? ''));
                $identifier = $studentNo !== '' ? 'Student No: ' . $studentNo : null;

                return [
                    'id' => $req->id,
                    'reference_number' => $req->reference_number,
                    'status' => $req->status,
                    'document_type' => $req->document_type,
                    'purpose' => $req->purpose ?? '—',
                    'request_date' => Carbon::parse($req->request_date)->format('M d, Y'),
                    'request_time' => Carbon::parse($req->request_time)->format('h:i A'),
                    'patient_name' => $displayName,
                    'sub_label' => $identifier,
                    'patient_identifier' => $identifier,
                ];
            });

        $counts = DocumentRequest::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $purposes = DocumentRequest::query()
            ->whereNotNull('purpose')
            ->where('purpose', '!=', '')
            ->distinct()
            ->orderBy('purpose')
            ->pluck('purpose')
            ->values();

        $types = DocumentRequest::query()
            ->whereNotNull('document_type')
            ->where('document_type', '!=', '')
            ->distinct()
            ->orderBy('document_type')
            ->pluck('document_type')
            ->values();

        return response()->json([
            'requests' => $requests,
            'stats' => [
                'all' => DocumentRequest::count(),
                'pending' => $counts['pending'] ?? 0,
                'approved' => $counts['approved'] ?? 0,
                'ready' => $counts['ready'] ?? 0,
                'released' => $counts['released'] ?? 0,
                'rejected' => $counts['rejected'] ?? 0,
            ],
            'purposes' => $purposes,
            'types' => $types,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $docRequest = DocumentRequest::with('patient.user')->findOrFail($id);
        $docRequest->update(['status' => 'approved']);

        if ($docRequest->patient && $docRequest->patient->user) {
            $docRequest->patient->user->notify(
                new DocumentRequestApprovedNotification($docRequest)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Document request approved.'
        ]);
    }

    public function reject(Request $request, $id)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $docRequest = DocumentRequest::with('patient.user')->findOrFail($id);

        $docRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        if ($docRequest->patient && $docRequest->patient->user) {
            $docRequest->patient->user->notify(
                new DocumentRequestRejectedNotification($docRequest)
            );
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

        DocumentRequest::findOrFail($id)->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Request updated.');
    }
}
