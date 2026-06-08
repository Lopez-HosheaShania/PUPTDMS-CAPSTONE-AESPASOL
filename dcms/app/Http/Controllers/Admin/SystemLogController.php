<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Helpers\AuditLogger;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect('/admin/login');
        }

        $perPage = in_array($request->input('per_page'), [10, 20, 50, 100])
            ? (int) $request->input('per_page') : 10;

        $role    = $request->input('role', 'all');
        $search  = $request->input('search');

        $sort       = $request->input('sort', 'desc');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $actionType = $request->input('action_type');
        $module     = $request->input('module');

        $hasLogFilters = $request->filled('search')
            || $request->filled('role')
            || $request->filled('sort')
            || $request->filled('date_from')
            || $request->filled('date_to')
            || $request->filled('action_type')
            || $request->filled('module')
            || $request->filled('page')
            || $request->filled('per_page');

        if (!$request->ajax() && !$hasLogFilters) {
            AuditLogger::log('view', 'system_logs', 'Admin viewed system logs');
        }

        $query = AuditLog::query();

        if ($role === 'login') {
            $query->where('action', 'like', '%login%');
        } elseif ($role === 'error') {
            $query->where(function ($q) {
                $q->where('action', 'like', '%error%')
                    ->orWhere('action', 'like', '%failed%')
                    ->orWhere('action', 'like', '%exception%');
            });
        } elseif (in_array($role, ['admin', 'dentist', 'patient'])) {
            $query->where('actor_role', $role);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_identifier', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('actor_role', 'like', "%{$search}%");
            });
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

        $query->orderBy('created_at', $sort === 'asc' ? 'asc' : 'desc');

        $logs = $query->paginate($perPage)->withQueryString();

        $totalCount   = AuditLog::count();
        $adminCount   = AuditLog::where('actor_role', 'admin')->count();
        $dentistCount = AuditLog::where('actor_role', 'dentist')->count();
        $patientCount = AuditLog::where('actor_role', 'patient')->count();
        $loginCount   = AuditLog::where('action', 'like', '%login%')->count();
        $errorCount = AuditLog::where(function ($q) {
            $q->where('action', 'like', '%error%')
                ->orWhere('action', 'like', '%failed%')
                ->orWhere('action', 'like', '%exception%');
        })->count();

        if ($request->ajax()) {
            return response()->json([
                'logs' => $logs->map(function ($log) {
                    return [
                        'id'               => $log->id,
                        'actor_role'       => strtolower($log->actor_role ?? 'other'),

                        'actor_identifier' => $log->actor_identifier ?? '—',
                        'actor_name'       => $log->actor_name ?? $log->actor_identifier ?? 'Unknown User',

                        'action'           => $log->action ?? '',
                        'module'           => $log->module ?? '',
                        'description'      => $log->description ?? 'No description provided.',
                        'created_at_day'   => optional($log->created_at)->format('M j, Y'),
                        'created_at_time'  => optional($log->created_at)->format('h:i:s A'),
                    ];
                }),

                'pagination' => [
                    'total'        => $logs->total(),
                    'from'         => $logs->firstItem() ?? 0,
                    'to'           => $logs->lastItem() ?? 0,
                    'current_page' => $logs->currentPage(),
                    'last_page'    => $logs->lastPage(),
                    'per_page'     => $logs->perPage(),
                ],

                'counts' => [
                    'total'   => $totalCount,
                    'admin'   => $adminCount,
                    'dentist' => $dentistCount,
                    'patient' => $patientCount,
                    'login'   => $loginCount,
                    'error'   => $errorCount,
                ],

                'filters' => [
                    'role'        => $role,
                    'search'      => $search,
                    'sort'        => $sort,
                    'date_from'   => $dateFrom,
                    'date_to'     => $dateTo,
                    'action_type' => $actionType,
                    'module'      => $module,
                ],
            ]);
        }

        return view('admin.system-logs', compact(
            'logs',
            'perPage',
            'role',
            'search',
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
            'errorCount'
        ));
    }

    public function fetchLatest(Request $request)
    {
        $perPage = in_array($request->input('per_page'), [10, 20, 50, 100])
            ? (int) $request->input('per_page')
            : 10;

        $logs = AuditLog::latest()->take($perPage)->get();

        return response()->json([
            'logs' => $logs->map(function ($log) {
                return [
                    'id'               => $log->id,
                    'actor_role'       => strtolower($log->actor_role ?? 'other'),

                    'actor_identifier' => $log->actor_identifier ?? '—',
                    'actor_name'       => $log->actor_name ?? $log->actor_identifier ?? 'Unknown User',

                    'action'           => strtolower($log->action ?? ''),
                    'module'           => $log->module ?? '',
                    'description'      => $log->description ?? 'No description provided.',
                    'created_at_day'   => optional($log->created_at)->format('M j, Y'),
                    'created_at_time'  => optional($log->created_at)->format('h:i:s A'),
                ];
            }),
        ]);
    }

    public function checkLatest()
    {
        $latest = AuditLog::latest()->first();

        return response()->json([
            'latest_id' => $latest?->id ?? 0,
            'total'     => AuditLog::count(),
        ]);
    }
}
