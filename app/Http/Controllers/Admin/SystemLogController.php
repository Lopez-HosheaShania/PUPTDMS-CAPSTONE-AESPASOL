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
        $actionTypes = $this->availableActionTypes();

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

        $logs->getCollection()->each(
            function (AuditLog $log) {
                $log->setAttribute(
                    'display_description',
                    $this->formatDisplayDescription(
                        $log
                    )
                );
            }
        );

        $initialLogsPayload =
            $logs
            ->getCollection()
            ->map(
                fn(AuditLog $log) =>
                $this->serializeAuditLog(
                    $log
                )
            )
            ->values();

        $counts =
            $this->systemLogCounts(
                $status
            );

        $totalCount = $counts['total'];
        $adminCount = $counts['admin'];
        $dentistCount = $counts['dentist'];
        $patientCount = $counts['patient'];
        $loginCount = $counts['login'];
        $errorCount = $counts['error'];
        $activeCount = $counts['active'];
        $archivedCount = $counts['archived'];

        if ($request->ajax()) {
            return response()->json([
                'logs' => $initialLogsPayload,
                'pagination' => [
                    'total' => $logs->total(),
                    'from' => $logs->firstItem() ?? 0,
                    'to' => $logs->lastItem() ?? 0,
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                ],
                'counts' => $counts,
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

        return view(
            'admin.system-logs',
            compact(
                'logs',
                'perPage',
                'role',
                'search',
                'status',
                'sort',
                'dateFrom',
                'dateTo',
                'actionType',
                'actionTypes',
                'module',
                'totalCount',
                'adminCount',
                'dentistCount',
                'patientCount',
                'loginCount',
                'errorCount',
                'activeCount',
                'archivedCount',
                'initialLogsPayload'
            ) + [
                'layoutRole' => $this->resolveLayoutRole(),
                'routeNames' => [
                    'index' => $this->routeName('index'),
                    'fetch' => $this->routeName('fetch'),
                    'check' => $this->routeName('check'),
                    'archive' => $this->routeName('archive'),
                    'export' => $this->routeName('export'),
                ],
            ]
        );
    }

    public function fetchLatest(
        Request $request
    ) {
        $afterId =
            max(
                0,
                (int) $request->input(
                    'after_id',
                    0
                )
            );

        $throughId =
            max(
                0,
                (int) $request->input(
                    'through_id',
                    0
                )
            );

        $limit =
            max(
                1,
                min(
                    (int) $request->input(
                        'limit',
                        50
                    ),
                    100
                )
            );

        $logs =
            $this
            ->buildFilteredQuery(
                $request
            )
            ->where(
                'id',
                '>',
                $afterId
            )
            ->when(
                $throughId > 0,
                fn($query) =>
                $query->where(
                    'id',
                    '<=',
                    $throughId
                )
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'logs' =>
            $logs
                ->map(
                    fn(AuditLog $log) =>
                    $this->serializeAuditLog(
                        $log
                    )
                )
                ->values(),

            'latest_id' =>
            (int) (
                $logs->max('id') ??
                $afterId
            ),
        ]);
    }

    public function checkLatest(
        Request $request
    ) {
        $batchLimit =
            max(
                1,
                min(
                    (int) $request->input(
                        'batch_limit',
                        50
                    ),
                    100
                )
            );

        $query =
            $this->buildFilteredQuery(
                $request
            );

        $latestId =
            (int) (
                (clone $query)
                ->max('id') ?? 0
            );

        $status =
            $this->normalizeStatus(
                $request->input(
                    'status',
                    'active'
                )
            );

        $statsLatestId =
            (int) (
                $this
                ->statusScopedQuery(
                    $status
                )
                ->max('id') ?? 0
            );

        $statsSinceId =
            $request->has(
                'stats_since_id'
            )
            ? max(
                0,
                (int) $request
                    ->input(
                        'stats_since_id',
                        0
                    )
            )
            : null;

        $statsChanged =
            $statsSinceId !== null &&
            $statsLatestId >
            $statsSinceId;

        if (
            !$request->has(
                'since_id'
            )
        ) {
            return response()->json([
                'since_id' => null,
                'latest_id' => $latestId,
                'stats_latest_id' => $statsLatestId,
                'counts' => null,
                'new_count' => 0,
                'new_ids' => [],
                'has_more' => false,
                'requires_full_refresh' => false,
            ]);
        }

        $sinceId =
            max(
                0,
                (int) $request->input(
                    'since_id',
                    0
                )
            );

        $newQuery =
            (clone $query)
            ->where(
                'id',
                '>',
                $sinceId
            );

        $newCount =
            (clone $newQuery)
            ->count();

        $newIds =
            (clone $newQuery)
            ->orderByDesc('id')
            ->limit($batchLimit)
            ->pluck('id')
            ->map(
                fn($id) => (int) $id
            )
            ->values()
            ->all();

        return response()->json([
            'since_id' => $sinceId,
            'latest_id' => $latestId,
            'stats_latest_id' => $statsLatestId,
            'counts' =>  $statsChanged ? $this->systemLogCounts(
                $status
            ) : null,
            'new_count' => $newCount,
            'new_ids' => $newIds,
            'has_more' => $newCount > count($newIds),
            'requires_full_refresh' => $newCount > $batchLimit,
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

    private function availableActionTypes(): array
    {
        $order = array_flip([
            'view',
            'login',
            'logout',
            'create',
            'update',
            'delete',
            'book',
            'archive',
            'export',
            'error',
        ]);

        return AuditLog::query()
            ->whereNotNull('action')
            ->distinct()
            ->pluck('action')
            ->map(
                fn($action) =>
                $this->normalizeActionType(
                    (string) $action
                )
            )
            ->filter()
            ->unique()
            ->sortBy(
                fn($type) =>
                $order[$type] ?? 999
            )
            ->mapWithKeys(
                fn($type) => [
                    $type =>
                    $this->actionTypeLabel(
                        $type
                    ),
                ]
            )
            ->all();
    }

    private function normalizeActionType(
        string $action
    ): ?string {
        $action =
            strtolower(
                trim($action)
            );

        if ($action === '') {
            return null;
        }

        return match (true) {
            str_contains(
                $action,
                'error'
            ),
            str_contains(
                $action,
                'failed'
            ),
            str_contains(
                $action,
                'exception'
            ) => 'error',

            str_contains(
                $action,
                'login'
            ) => 'login',

            str_contains(
                $action,
                'logout'
            ) => 'logout',

            str_contains(
                $action,
                'view'
            ) => 'view',

            str_contains(
                $action,
                'create'
            ) => 'create',

            str_contains(
                $action,
                'update'
            ) => 'update',

            str_contains(
                $action,
                'delete'
            ) => 'delete',

            str_contains(
                $action,
                'book'
            ) => 'book',

            str_contains(
                $action,
                'archive'
            ) => 'archive',

            str_contains(
                $action,
                'export'
            ) => 'export',

            default =>
            explode(
                '_',
                $action
            )[0] ?? $action,
        };
    }

    private function actionTypeLabel(
        string $type
    ): string {
        return ucwords(
            str_replace(
                '_',
                ' ',
                $type
            )
        );
    }
    protected function buildFilteredQuery(Request $request, ?string $statusOverride = null)
    {
        $roles = collect(
            explode(
                ',',
                strtolower(
                    (string) $request->input(
                        'role',
                        'all'
                    )
                )
            )
        )
            ->map(fn($role) => trim($role))
            ->filter()
            ->unique()
            ->values();

        if (
            $roles->isEmpty() ||
            $roles->contains('all')
        ) {
            $roles = collect(['all']);
        }
        $search = trim((string) $request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $actionType = $request->input('action_type');
        $module = $request->input('module');
        $status = $this->normalizeStatus($statusOverride ?? $request->input('status', 'active'));

        $query = $this->statusScopedQuery($status);

        $actorRoles =
            $roles
            ->filter(
                fn($role) =>
                in_array(
                    $role,
                    [
                        'admin',
                        'dentist',
                        'patient',
                    ],
                    true
                )
            )
            ->values();

        if (
            !$roles->contains('all') &&
            $actorRoles->isNotEmpty()
        ) {
            $query->whereHas(
                'actorSnapshot',
                function ($actor) use (
                    $actorRoles
                ) {
                    $actor->whereIn(
                        'actor_role',
                        $actorRoles->all()
                    );
                }
            );
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

                    $builder->orWhereHas('descriptionDetails', fn($detail) => $detail->where('full_description', 'like', "%{$search}%"));

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

        if (
            $actionType &&
            $actionType !== 'all'
        ) {
            if ($actionType === 'error') {
                $query->where(
                    function ($builder) {
                        $builder
                            ->where(
                                'action',
                                'like',
                                '%error%'
                            )
                            ->orWhere(
                                'action',
                                'like',
                                '%failed%'
                            )
                            ->orWhere(
                                'action',
                                'like',
                                '%exception%'
                            );
                    }
                );
            } else {
                $query->where(
                    'action',
                    'like',
                    "%{$actionType}%"
                );
            }
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

    protected function normalizeStatus(
        ?string $status
    ): string {
        $statuses = collect(
            explode(
                ',',
                strtolower(
                    (string) $status
                )
            )
        )
            ->map(
                fn($value) =>
                trim($value)
            )
            ->filter()
            ->unique();

        if (
            $statuses->contains('all') ||
            (
                $statuses->contains('active') &&
                $statuses->contains('archived')
            )
        ) {
            return 'all';
        }

        if (
            $statuses->contains('archived')
        ) {
            return 'archived';
        }

        return 'active';
    }

    private function formatDisplayDescription(
        AuditLog $log
    ): string {
        $description = trim(
            (string) (
                $log->description ?? ''
            )
        );

        $fullDescription = trim(
            (string) (
                $log->full_description ?? ''
            )
        );

        $module = strtolower(
            trim(
                (string) (
                    $log->module ?? ''
                )
            )
        );

        if ($module === 'inventory') {
            $itemId = null;

            if (
                preg_match(
                    '/\b(?:inventory\s+)?item\s+ID\s*#?\s*(\d+)\b/i',
                    $description,
                    $matches
                )
            ) {
                $itemId = $matches[1];
            }

            if (
                $itemId === null &&
                $fullDescription !== ''
            ) {
                $decoded =
                    json_decode(
                        $fullDescription,
                        true
                    );

                if (
                    is_array($decoded) &&
                    isset($decoded['item_id'])
                ) {
                    $itemId =
                        (string) $decoded['item_id'];
                }
            }

            if ($itemId !== null) {
                $actionLabel = ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        strtolower(
                            trim(
                                (string) $log->action
                            )
                        )
                    )
                );

                return
                    $actionLabel .
                    ' — inventory item ID ' .
                    $itemId;
            }
        }

        return $description !== ''
            ? $description
            : 'No description provided.';
    }

    private function systemLogCounts(
        string $status
    ): array {
        return [
            'total' =>
            $this
                ->statusScopedQuery(
                    $status
                )
                ->count(),

            'admin' =>
            $this
                ->statusScopedQuery(
                    $status
                )
                ->forActorRole(
                    'admin'
                )
                ->count(),

            'dentist' =>
            $this
                ->statusScopedQuery(
                    $status
                )
                ->forActorRole(
                    'dentist'
                )
                ->count(),

            'patient' =>
            $this
                ->statusScopedQuery(
                    $status
                )
                ->forActorRole(
                    'patient'
                )
                ->count(),

            'login' =>
            $this
                ->statusScopedQuery(
                    $status
                )
                ->where(
                    'action',
                    'like',
                    '%login%'
                )
                ->count(),

            'error' =>
            $this
                ->statusScopedQuery(
                    $status
                )
                ->where(
                    function ($query) {
                        $query
                            ->where(
                                'action',
                                'like',
                                '%error%'
                            )
                            ->orWhere(
                                'action',
                                'like',
                                '%failed%'
                            )
                            ->orWhere(
                                'action',
                                'like',
                                '%exception%'
                            );
                    }
                )
                ->count(),

            'active' =>
            AuditLog::where(
                'is_archived',
                false
            )->count(),

            'archived' =>
            AuditLog::where(
                'is_archived',
                true
            )->count(),
        ];
    }

    private function serializeAuditLog(
        AuditLog $log
    ): array {
        return [
            'id' =>
            $log->id,

            'actor_role' =>
            strtolower(
                $log->actor_role ??
                    'other'
            ),

            'actor_identifier' =>
            $log->actor_identifier ??
                '—',

            'actor_name' =>
            $log->actor_name ??
                $log->actor_identifier ??
                'Unknown User',

            'action' =>
            $log->action ?? '',

            'module' =>
            $log->module ?? '',

            'description' =>
            $log->description ??
                'No description provided.',

            'display_description' =>
            $this->formatDisplayDescription(
                $log
            ),

            'full_description' =>
            $log->full_description,

            'is_archived' =>
            (bool) $log->is_archived,

            'archived_at' =>
            optional(
                $log->archived_at
            )->format(
                'M j, Y h:i A'
            ),

            'created_at_day' =>
            optional(
                $log->created_at
            )->format(
                'M j, Y'
            ),

            'created_at_time' =>
            optional(
                $log->created_at
            )->format(
                'h:i:s A'
            ),
        ];
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
                'fetch' => 'dentist.system_logs.fetch',
                'check' => 'dentist.system_logs.check',
                'archive' => 'dentist.system_logs.archive',
                'export' => 'dentist.system_logs.export',
            };
        }

        return match ($action) {
            'index' => 'admin.system_logs',
            'fetch' => 'admin.system_logs.fetch',
            'check' => 'admin.system_logs.check',
            'archive' => 'admin.system_logs.archive',
            'export' => 'admin.system_logs.export',
        };
    }
}
