<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ConcurrentSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSessionManagementController extends Controller
{
    public function __construct(
        private readonly ConcurrentSessionService $concurrentSessionService
    ) {}

    private function sessionFilters(
        Request $request
    ): array {
        $roles = collect(
            explode(
                ',',
                (string) $request->input(
                    'roles',
                    $request->input(
                        'role',
                        'all'
                    )
                )
            )
        )
            ->map(
                fn($role) =>
                strtolower(
                    trim($role)
                )
            )
            ->filter(
                fn($role) =>
                in_array(
                    $role,
                    [
                        'all',
                        'admin',
                        'dentist',
                        'patient',
                    ],
                    true
                )
            )
            ->unique()
            ->values();

        if (
            $roles->isEmpty() ||
            $roles->contains('all')
        ) {
            $roles = collect(['all']);
        }

        return [
            'sort' =>
            (string) $request->input(
                'sort',
                'recent'
            ),

            'roles' =>
            $roles->all(),

            'role' =>
            $roles->implode(','),

            'device' =>
            (string) $request->input(
                'device',
                'all'
            ),

            'scope' =>
            (string) $request->input(
                'scope',
                'all'
            ),

            'search' =>
            (string) $request->input(
                'search',
                ''
            ),
        ];
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (!session('admin_logged_in')) {
            return redirect('/login');
        }

        $perPage = in_array((int) $request->input('per_page'), [10, 15, 20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 15;

        $filters =
            $this->sessionFilters(
                $request
            );

        $sessions = $this->concurrentSessionService->paginateActiveSessionsForAdmin(
            $perPage,
            $filters,
            $request->session()->getId()
        );

        $stats = $this->concurrentSessionService
            ->getAdminSessionStats($request->session()->getId());

        if (!$request->ajax()) {
            AuditLogger::log('view', 'session_management', 'Admin viewed the active session management dashboard');
        }

        return view('admin.session-management', [
            'sessions' => $sessions,
            'perPage' => $perPage,
            'filters' => $filters,
            'stats' => $stats,
            'notifications' => collect(),
        ]);
    }

    public function checkLatest(
        Request $request
    ): JsonResponse {
        if (!session('admin_logged_in')) {
            return response()->json(
                [
                    'message' =>
                    'Unauthorized.',
                ],
                401
            );
        }

        $perPage = in_array(
            (int) $request->input(
                'per_page'
            ),
            [
                10,
                15,
                20,
                50,
                100,
            ],
            true
        )
            ? (int) $request->input(
                'per_page'
            )
            : 15;

        $filters =
            $this->sessionFilters(
                $request
            );

        $currentSessionId =
            $request
            ->session()
            ->getId();

        $sessions =
            $this
            ->concurrentSessionService
            ->paginateActiveSessionsForAdmin(
                $perPage,
                $filters,
                $currentSessionId
            );

        return response()->json([
            'signature' =>
            $this
                ->concurrentSessionService
                ->getAdminSessionLiveSignature(),

            'stats' =>
            $this
                ->concurrentSessionService
                ->getAdminSessionStats(
                    $currentSessionId
                ),

            'pagination' => [
                'total' =>
                $sessions->total(),

                'from' =>
                $sessions->firstItem() ?? 0,

                'to' =>
                $sessions->lastItem() ?? 0,

                'current_page' =>
                $sessions->currentPage(),

                'last_page' =>
                $sessions->lastPage(),

                'per_page' =>
                $sessions->perPage(),
            ],
        ]);
    }

    public function destroySession(Request $request, string $reference): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor, 401);

        $revoked = $this->concurrentSessionService->revokeSessionByReferenceAsAdmin(
            $actor,
            $reference,
            $request->session()->getId()
        );

        return back()->with(
            $revoked ? 'success' : 'error',
            $revoked
                ? 'The selected session was logged out successfully.'
                : 'That session could not be removed. It may already be inactive or is the current admin session.'
        );
    }

    public function destroyUserSessions(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor, 401);

        $exceptSessionId = (int) $actor->id === (int) $user->id
            ? $request->session()->getId()
            : null;

        $revoked = $this->concurrentSessionService->revokeAllSessionsForUserAsAdmin(
            $actor,
            $user,
            $exceptSessionId
        );

        return back()->with(
            $revoked > 0 ? 'success' : 'error',
            $revoked > 0
                ? 'All eligible active sessions for the selected user were logged out.'
                : 'No eligible active sessions were found for that user.'
        );
    }
}
