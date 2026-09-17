<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignDentistSuccessorsRequest;
use App\Http\Requests\CancelDentistTransitionRequest;
use App\Http\Requests\DentistTransitionRequest;
use App\Http\Requests\FinalizeDentistTransitionRequest;
use App\Models\DentistTransition;
use App\Models\User;
use App\Services\DentistTransitionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DentistTransitionController extends Controller
{
    public function __construct(private readonly DentistTransitionService $service) {}

    public function index(Request $request)
    {
        $this->authorizeAction('view_dentist_transitions');

        $perPageInput = (int) $request->input('per_page', 10);
        $perPage = in_array($perPageInput, [10, 20, 50, 100], true) ? $perPageInput : 10;

        $standardTransitionTypes = array_values(array_filter(
            DentistTransition::TYPES,
            fn($type) => $type !== 'other'
        ));

        $baseQuery = DentistTransition::query()
            ->with(['dentist.role', 'defaultSuccessor', 'items.successorDentist', 'checklistItems'])
            ->latest();

        $search = trim((string) $request->get('search', ''));

        if ($search !== '') {
            $baseQuery->where(function ($builder) use ($search) {
                $builder->whereHas('dentist', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                })->orWhereHas('defaultSuccessor', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            });
        }

        if ($status = trim((string) $request->get('status', ''))) {
            $baseQuery->where('status', $status);
        }

        if ($type = trim((string) $request->get('transition_type', ''))) {
            if ($type === 'other') {
                $customType = trim((string) $request->get('transition_type_other', ''));

                if ($customType !== '') {
                    $baseQuery->where('transition_type', $customType);
                } else {
                    $baseQuery->whereNotIn('transition_type', $standardTransitionTypes);
                }
            } else {
                $baseQuery->where('transition_type', $type);
            }
        }

        if ($successor = trim((string) $request->get('successor', ''))) {
            $baseQuery->whereHas('defaultSuccessor', function ($builder) use ($successor) {
                $builder->where('name', 'like', '%' . $successor . '%');
            });
        }

        if ($effectiveDate = trim((string) $request->get('effective_date', ''))) {
            $baseQuery->whereDate('access_ends_at', $effectiveDate);
        }

        $query = clone $baseQuery;
        $transitions = $query->paginate($perPage)->withQueryString();

        $statsQuery = clone $baseQuery;
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->whereIn('status', ['draft', 'pending_review', 'handover_in_progress', 'scheduled'])->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'transitions' => $transitions->getCollection()
                    ->map(fn(DentistTransition $transition) => $this->formatTransitionForResponse($transition))
                    ->values(),
                'pagination' => [
                    'total' => $transitions->total(),
                    'from' => $transitions->firstItem() ?? 0,
                    'to' => $transitions->lastItem() ?? 0,
                    'current_page' => $transitions->currentPage(),
                    'last_page' => $transitions->lastPage(),
                    'per_page' => $transitions->perPage(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('admin.dentist-continuity', [
            'pageMode' => 'index',
            'transitions' => $transitions,
            'stats' => $stats,
            'statuses' => DentistTransition::STATUSES,
            'types' => DentistTransition::TYPES,
            'perPage' => $perPage,
            'filters' => $request->only(['search', 'status', 'transition_type', 'transition_type_other', 'successor', 'effective_date']),
            'layoutRole' => $this->resolveLayoutRole(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create()
    {
        $this->authorizeAction('create_dentist_transitions');

        return view('admin.dentist-continuity', [
            'pageMode' => 'form',
            'transition' => new DentistTransition(),
            'dentists' => $this->activeDentists(),
            'types' => DentistTransition::TYPES,
            'formAction' => route($this->routeName('store')),
            'formMethod' => 'POST',
            'layoutRole' => $this->resolveLayoutRole(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(DentistTransitionRequest $request)
    {
        $this->authorizeAction('create_dentist_transitions');

        $transition = $this->service->createTransition($request->validated(), $request->user());

        return redirect()
            ->route($this->routeName('show'), $transition)
            ->with(
                'success',
                'Dentist handover confirmed successfully.'
            );
    }

    public function show(DentistTransition $transition)
    {
        $this->authorizeAction('view_dentist_transitions');

        $transition->load([
            'dentist.role',
            'defaultSuccessor',
            'initiatedBy',
            'reviewedBy',
            'approvedBy',
            'items.patient',
            'items.originalDentist',
            'items.successorDentist',
            'items.documentRequest',
            'checklistItems.completedBy',
        ]);

        $transition = $this->service->generateTransitionItems($transition);

        return view('admin.dentist-continuity', [
            'pageMode' => 'show',
            'transition' => $transition,
            'summary' => $this->service->generateImpactSummary($transition),
            'dentists' => $this->activeDentists($transition->dentist_id),
            'readiness' => $this->service->validateTransitionReadiness($transition),
            'layoutRole' => $this->resolveLayoutRole(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function edit(DentistTransition $transition)
    {
        $this->authorizeAction('update_dentist_transitions');

        return view('admin.dentist-continuity', [
            'pageMode' => 'form',
            'transition' => $transition,
            'dentists' => $this->activeDentists($transition->dentist_id),
            'types' => DentistTransition::TYPES,
            'formAction' => route($this->routeName('update'), $transition),
            'formMethod' => 'PUT',
            'layoutRole' => $this->resolveLayoutRole(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function update(DentistTransitionRequest $request, DentistTransition $transition)
    {
        $this->authorizeAction('update_dentist_transitions');

        if (in_array($transition->status, ['scheduled', 'completed', 'cancelled'], true)) {
            return back()->with('error', 'This transition can no longer be edited.');
        }

        $transition = $this->service->updateTransition($transition, $request->validated(), $request->user());

        return redirect()
            ->route($this->routeName('show'), $transition)
            ->with('success', 'Transition details updated successfully.');
    }

    public function generateItems(DentistTransition $transition)
    {
        $this->authorizeAction('update_dentist_transitions');

        $this->service->generateTransitionItems($transition);

        return back()->with('success', 'Impact summary refreshed successfully.');
    }

    public function assignments(AssignDentistSuccessorsRequest $request, DentistTransition $transition)
    {
        $this->authorizeAction('assign_dentist_successors');

        $this->service->updateSuccessorAssignments($transition, $request->validated(), $request->user());
        $this->service->notifyTransitionParticipants($transition, 'assignments_updated');

        return back()->with('success', 'Successor assignments updated successfully.');
    }

    public function checklist(Request $request, DentistTransition $transition)
    {
        $this->authorizeAction('update_dentist_transitions');

        $request->validate([
            'checklist' => ['required', 'array'],
            'checklist.*.remarks' => ['nullable', 'string'],
        ]);

        $this->service->updateChecklist(
            $transition,
            $request->only('checklist'),
            $request->user()
        );

        return back()->with(
            'success',
            'Handover checklist updated successfully.'
        );
    }

    public function finalize(FinalizeDentistTransitionRequest $request, DentistTransition $transition)
    {
        $this->authorizeAction('finalize_dentist_transitions');

        try {
            $this->service->finalizeTransition($transition, $request->user());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Transition finalized successfully.');
    }

    public function cancel(CancelDentistTransitionRequest $request, DentistTransition $transition)
    {
        $this->authorizeAction('cancel_dentist_transitions');

        $this->service->cancelTransition($transition, $request->validated()['cancellation_reason'], $request->user());

        return back()->with('success', 'Transition cancelled successfully.');
    }

    public function extendAccess(Request $request, DentistTransition $transition)
    {
        $this->authorizeAction('extend_dentist_access');

        $validated = $request->validate([
            'access_ends_at' => ['required', 'date', 'after:now'],
        ]);

        $this->service->extendAccess($transition, Carbon::parse($validated['access_ends_at']), $request->user());

        return back()->with('success', 'Dentist access extended successfully.');
    }

    private function activeDentists(?int $includeDentistId = null)
    {
        return User::query()
            ->with('role')
            ->whereHas('role', function ($query) {
                $query->where('slug', 'dentist');
            })
            ->where(function ($query) use ($includeDentistId) {
                $query->where('status', 'active');

                if ($includeDentistId) {
                    $query->orWhere('id', $includeDentistId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function authorizeAction(string $permission): void
    {
        $user = request()->user();

        abort_unless($user, 403);
        $allowed = $user->hasPermission($permission) || $user->hasPermission('manage_dentist_accounts');

        abort_unless($allowed, 403, 'Unauthorized.');
    }

    private function formatTransitionForResponse(DentistTransition $transition): array
    {
        $transitionType = (string) $transition->transition_type;
        $status = (string) $transition->status;

        return [
            'id' => $transition->id,
            'dentist_name' => $transition->dentist->name ?? 'Unknown dentist',
            'dentist_email' => $transition->dentist->email ?? 'No email',
            'transition_type' => $transitionType,
            'transition_type_label' => in_array($transitionType, DentistTransition::TYPES, true)
                ? str_replace('_', ' ', ucfirst($transitionType))
                : $transitionType,
            'last_working_date' => optional($transition->last_working_date)->format('M d, Y'),
            'access_expiration' => optional($transition->access_ends_at)->format('M d, Y'),
            'successor_name' => $this->resolveSuccessorName($transition),
            'progress_percentage' => (int) $transition->progress_percentage,
            'status' => $status,
            'status_label' => str_replace('_', ' ', ucfirst($status)),
            'show_url' => route($this->routeName('show'), $transition),
            'edit_url' => (
                $this->resolveLayoutRole() === 'admin'
                && ! in_array(
                    $status,
                    ['scheduled', 'completed', 'cancelled'],
                    true
                )
            )
                ? route($this->routeName('edit'), $transition)
                : null,
        ];
    }

    private function resolveLayoutRole(): string
    {
        return request()->routeIs('dentist.dentist.transitions.*') ? 'dentist' : 'admin';
    }

    private function routeName(string $action): string
    {
        return $this->routeNames()[$action];
    }

    private function routeNames(): array
    {
        if (request()->routeIs('dentist.dentist.transitions.*')) {
            return [
                'index' => 'dentist.dentist.transitions.index',
                'create' => 'dentist.dentist.transitions.create',
                'store' => 'dentist.dentist.transitions.store',
                'show' => 'dentist.dentist.transitions.show',
                'edit' => 'dentist.dentist.transitions.edit',
                'update' => 'dentist.dentist.transitions.update',
                'generate_items' => 'dentist.dentist.transitions.generate-items',
                'assignments' => 'dentist.dentist.transitions.assignments',
                'checklist' => 'dentist.dentist.transitions.checklist',
                'finalize' => 'dentist.dentist.transitions.finalize',
                'extend_access' => 'dentist.dentist.transitions.extend-access',
                'cancel' => 'dentist.dentist.transitions.cancel',
            ];
        }

        return [
            'index' => 'admin.dentist-transitions.index',
            'create' => 'admin.dentist-transitions.create',
            'store' => 'admin.dentist-transitions.store',
            'show' => 'admin.dentist-transitions.show',
            'edit' => 'admin.dentist-transitions.edit',
            'update' => 'admin.dentist-transitions.update',
            'generate_items' => 'admin.dentist-transitions.generate-items',
            'assignments' => 'admin.dentist-transitions.assignments',
            'checklist' => 'admin.dentist-transitions.checklist',
            'finalize' => 'admin.dentist-transitions.finalize',
            'extend_access' => 'admin.dentist-transitions.extend-access',
            'cancel' => 'admin.dentist-transitions.cancel',
        ];
    }

    private function resolveSuccessorName(DentistTransition $transition): string
    {
        if ($transition->defaultSuccessor?->name) {
            return $transition->defaultSuccessor->name;
        }

        $firstAssignedSuccessor = $transition->items
            ->first(fn($item) => $item->successorDentist?->name);

        return $firstAssignedSuccessor?->successorDentist?->name ?? 'Not assigned';
    }
}
