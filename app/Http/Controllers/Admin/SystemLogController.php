<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check()) {
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Your session has expired.',
                ], 401);
            }

            return redirect('/login');
        }

        $perPageInput = (int) $request->input('per_page', 10);

        $perPage = in_array(
            $perPageInput,
            [10, 20, 50, 100],
            true
        ) ? $perPageInput : 10;

        $role = $request->input('role', 'all');
        $search = $request->input('search');
        $sort = $request->input('sort', 'desc');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $actionType = $request->input('action_type');
        $module = $request->input('module');
        $status = $this->normalizeStatus($request->input('status', 'active'));

        $hasLogFilters = $request->filled('search')
            || $request->filled('role')
            || $request->filled('sort')
            || $request->filled('date_from')
            || $request->filled('date_to')
            || $request->filled('action_type')
            || $request->filled('module')
            || $request->filled('status')
            || $request->filled('page')
            || $request->filled('per_page');

        if (!$request->ajax() && !$hasLogFilters) {
            AuditLogger::log('view', 'system_logs', 'Admin viewed system logs');
        }

        $logs = $this->buildFilteredQuery($request)
            ->orderBy('created_at', $sort === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $totalCount = $this->statusScopedQuery($status)->count();
        $adminCount = $this->statusScopedQuery($status)->forActorRole('admin')->count();
        $dentistCount = $this->statusScopedQuery($status)->forActorRole('dentist')->count();
        $patientCount = $this->statusScopedQuery($status)->forActorRole('patient')->count();
        $loginCount = $this->statusScopedQuery($status)->where('action', 'like', '%login%')->count();
        $archivedCount = AuditLog::where('is_archived', true)->count();
        $activeCount = AuditLog::where('is_archived', false)->count();
        $errorCount = $this->statusScopedQuery($status)->where(function ($query) {
            $query->where('action', 'like', '%error%')
                ->orWhere('action', 'like', '%failed%')
                ->orWhere('action', 'like', '%exception%');
        })->count();

        if ($request->ajax()) {
            return response()->json([
                'logs' => $logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'actor_role' => strtolower($log->actor_role ?? 'other'),
                        'actor_identifier' => $log->actor_identifier ?? '—',
                        'actor_name' => $log->actor_name ?? $log->actor_identifier ?? 'Unknown User',
                        'action' => $log->action ?? '',
                        'module' => $log->module ?? '',
                        'description' => $log->description ?? 'No description provided.',
                        'full_description' => $log->full_description,
                        'is_archived' => (bool) $log->is_archived,
                        'archived_at' => optional($log->archived_at)->format('M j, Y h:i A'),
                        'created_at_day' => optional($log->created_at)->format('M j, Y'),
                        'created_at_time' => optional($log->created_at)->format('h:i:s A'),
                    ];
                }),
                'pagination' => [
                    'total' => $logs->total(),
                    'from' => $logs->firstItem() ?? 0,
                    'to' => $logs->lastItem() ?? 0,
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                ],
                'counts' => [
                    'total' => $totalCount,
                    'admin' => $adminCount,
                    'dentist' => $dentistCount,
                    'patient' => $patientCount,
                    'login' => $loginCount,
                    'error' => $errorCount,
                    'active' => $activeCount,
                    'archived' => $archivedCount,
                ],
                'filters' => [
                    'role' => $role,
                    'search' => $search,
                    'status' => $status,
                    'sort' => $sort,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'action_type' => $actionType,
                    'module' => $module,
                ],
            ]);
        }

        return view('admin.system-logs', compact(
            'logs',
            'perPage',
            'role',
            'search',
            'status',
            'sort',
            'dateFrom',
            'dateTo',
            'actionType',
            'module',
            'totalCount',
            'adminCount',
            'dentistCount',
            'patientCount',
            'loginCount',
            'errorCount',
            'activeCount',
            'archivedCount'
        ) + [
            'layoutRole' => $this->resolveLayoutRole(),
            'routeNames' => [
                'index' => $this->routeName('index'),
                'check' => $this->routeName('check'),
                'archive' => $this->routeName('archive'),
                'export' => $this->routeName('export'),
            ],
        ]);
    }

    public function fetchLatest(Request $request)
    {
        $perPage = in_array($request->input('per_page'), [10, 20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 10;

        $logs = $this->statusScopedQuery($this->normalizeStatus($request->input('status', 'active')))
            ->latest()
            ->take($perPage)
            ->get();

        return response()->json([
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'actor_role' => strtolower($log->actor_role ?? 'other'),
                    'actor_identifier' => $log->actor_identifier ?? '—',
                    'actor_name' => $log->actor_name ?? $log->actor_identifier ?? 'Unknown User',
                    'action' => strtolower($log->action ?? ''),
                    'module' => $log->module ?? '',
                    'description' => $log->description ?? 'No description provided.',
                    'full_description' => $log->full_description,
                    'is_archived' => (bool) $log->is_archived,
                    'archived_at' => optional($log->archived_at)->format('M j, Y h:i A'),
                    'created_at_day' => optional($log->created_at)->format('M j, Y'),
                    'created_at_time' => optional($log->created_at)->format('h:i:s A'),
                ];
            }),
        ]);
    }

    public function checkLatest(Request $request)
    {
        $status = $this->normalizeStatus($request->input('status', 'active'));
        $latest =
            $this
            ->statusScopedQuery($status)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'latest_id' =>
            $latest?->id ?? 0,

            'total' =>
            $this
                ->statusScopedQuery($status)
                ->count(),
        ]);
    }

    public function archive(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'older_than_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'role' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'action_type' => ['nullable', 'string'],
            'module' => ['nullable', 'string'],
        ]);

        $olderThanDays = (int) ($validated['older_than_days'] ?? 90);
        $cutoff = now()->subDays($olderThanDays)->endOfDay();

        $ids = $this->buildFilteredQuery($request, 'active')
            ->where('created_at', '<=', $cutoff)
            ->pluck('id');

        $count = $ids->count();

        if ($count === 0) {
            return response()->json([
                'message' => 'No active logs matched the selected filters and archive threshold.',
                'archived_count' => 0,
            ]);
        }

        AuditLog::whereIn('id', $ids)->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'archive_logs',
            'system_logs',
            "Archived {$count} system log entr" . ($count === 1 ? 'y' : 'ies') . " older than {$olderThanDays} days."
        );

        return response()->json([
            'message' => "Archived {$count} log entr" . ($count === 1 ? 'y' : 'ies') . '.',
            'archived_count' => $count,
        ]);
    }

    public function export(Request $request)
    {
        @set_time_limit(120);
        @ini_set('max_execution_time', '120');
        @ini_set('memory_limit', '512M');

        $validated = $request->validate([
            'role' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'action_type' => ['nullable', 'string'],
            'module' => ['nullable', 'string'],
        ]);

        $status = $this->normalizeStatus(
            $request->input('status', 'active')
        );
        $request->merge(['status' => $status]);

        $logs =
            $this
            ->buildFilteredQuery(
                $request
            )
            ->select([
                'id',
                'action',
                'module',
                'description',
                'is_archived',
                'archived_at',
                'created_at',
            ])
            ->orderBy(
                'created_at',
                $request->input('sort') === 'asc'
                    ? 'asc'
                    : 'desc'
            )
            ->get();


        if ($logs->isEmpty()) {
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'No logs found for the selected export filters.',
                ], 422);
            }

            return redirect()
                ->route($this->routeName('index'), $request->query())
                ->with(
                    'error',
                    'No logs found for the selected export filters.'
                );
        }

        AuditLogger::log(
            'export_pdf',
            'system_logs',
            'Admin exported ' .
                $logs->count() .
                ' filtered system log entr' .
                ($logs->count() === 1 ? 'y' : 'ies') .
                ' as PDF.'
        );

        $pdf = Pdf::loadView('admin.system-logs-pdf', [
            'logs' => $logs,
            'generatedAt' => now(),
            'filters' => [
                'status' => $status,
                'role' => $request->input('role', 'all'),
                'search' => $request->input('search'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'action_type' => $request->input('action_type'),
                'module' => $request->input('module'),
            ],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('system-logs-' . $status . '-' . now()->format('Y-m-d-His') . '.pdf');
    }

    protected function buildFilteredQuery(Request $request, ?string $statusOverride = null)
    {
        $role = $request->input('role', 'all');
        $search = trim((string) $request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $actionType = $request->input('action_type');
        $module = $request->input('module');
        $status = $this->normalizeStatus($statusOverride ?? $request->input('status', 'active'));

        $query = $this->statusScopedQuery($status);

        if ($role === 'login') {
            $query->where('action', 'like', '%login%');
        } elseif ($role === 'error') {
            $query->where(function ($builder) {
                $builder->where('action', 'like', '%error%')
                    ->orWhere('action', 'like', '%failed%')
                    ->orWhere('action', 'like', '%exception%');
            });
        } elseif (in_array($role, ['admin', 'dentist', 'patient'], true)) {
            $query->forActorRole($role);
        }

        $searchId = ltrim($search, '#');

        if ($search !== '') {
            $query->where(
                function ($builder) use (
                    $search,
                    $searchId
                ) {
                    $builder
                        ->whereHas('actorSnapshot', function ($actor) use ($search) {
                            $actor->where('actor_identifier', 'like', "%{$search}%")
                                ->orWhere('actor_name', 'like', "%{$search}%")
                                ->orWhere('actor_role', 'like', "%{$search}%");
                        })
                        ->orWhere(
                            'action',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'module',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        );

                    $builder->orWhereHas('descriptionDetails', fn ($detail) => $detail->where('full_description', 'like', "%{$search}%"));

                    if (
                        ctype_digit(
                            $searchId
                        )
                    ) {
                        $builder->orWhere(
                            'id',
                            (int) $searchId
                        );
                    }
                }
            );
        }

        if ($actionType) {
            $query->where('action', 'like', "%{$actionType}%");
        }

        if ($module) {
            $query->where('module', 'like', "%{$module}%");
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    protected function statusScopedQuery(string $status)
    {
        return AuditLog::query()
            ->when($status === 'active', fn($query) => $query->where('is_archived', false))
            ->when($status === 'archived', fn($query) => $query->where('is_archived', true));
    }

    protected function normalizeStatus(?string $status): string
    {
        return in_array($status, ['active', 'archived', 'all'], true) ? $status : 'active';
    }

    private function resolveLayoutRole(): string
    {
        return request()->routeIs('dentist.system_logs*') ? 'dentist' : 'admin';
    }

    private function routeName(string $action): string
    {
        if (request()->routeIs('dentist.system_logs*')) {
            return match ($action) {
                'index' => 'dentist.system_logs',
                'check' => 'dentist.system_logs.check',
                'archive' => 'dentist.system_logs.archive',
                'export' => 'dentist.system_logs.export',
            };
        }

        return match ($action) {
            'index' => 'admin.system_logs',
            'check' => 'admin.system_logs.check',
            'archive' => 'admin.system_logs.archive',
            'export' => 'admin.system_logs.export',
        };
    }
}
