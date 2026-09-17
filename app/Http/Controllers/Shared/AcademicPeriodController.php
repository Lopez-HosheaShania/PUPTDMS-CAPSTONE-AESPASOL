<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\PhilippineHolidays;
use App\Helpers\AuditLogger;
use App\Services\FacultyApiService;

class AcademicPeriodController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        // Fetch PH holidays (previous year, current year, next year)
        $holidays = PhilippineHolidays::recordsRange(1, 1);

        $query = AcademicPeriod::query();

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('academic_year', 'like', "%{$search}%")
                    ->orWhere('semester', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('status')) {
            switch ($request->status) {

                case 'Active':
                    $query->where('is_active', true);
                    break;

                case 'Upcoming':
                    $query->where('is_active', false)
                        ->whereDate('start_date', '>', $today);
                    break;

                case 'Ended':
                    $query->where('is_active', false)
                        ->whereDate('end_date', '<', $today);
                    break;

                case 'Inactive':
                    $query->where('is_active', false)
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today);
                    break;
            }
        }

        $academicPeriods = $query
            ->orderByDesc('start_date')
            ->paginate(10)
            ->withQueryString();

        $calendarPeriods = AcademicPeriod::query()
            ->orderBy('start_date')
            ->get();

        $activePeriod = AcademicPeriod::where('is_active', true)
            ->orderByDesc('start_date')
            ->first();

        AuditLogger::log(
            'view',
            'academic_periods',
            $this->auditActorLabel() . ' viewed academic periods list'
        );

        return view('shared.academic-period', compact(
            'academicPeriods',
            'calendarPeriods',
            'activePeriod',
            'holidays'
        ) + [
            'layoutRole' => $this->resolveLayoutRole(),
            'routeNames' => [
                'index' => $this->routeName('index'),
                'store' => $this->routeName('store'),
                'update' => $this->routeName('update'),
                'destroy' => $this->routeName('destroy'),
                'set_active' => $this->routeName('set_active'),
                'sync_flss' => $this->routeName('sync_flss'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', 'in:First Semester,Second Semester,Summer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'academic_year.regex' => 'Academic Year must be in YYYY-YYYY format.',
            'end_date.after' => 'End Date must be after Start Date.',
        ]);

        $this->ensureAcademicPeriodIsUnique($request, $validated['academic_year'], $validated['semester']);

        $isActive = (bool) ($validated['is_active'] ?? false);

        if ($isActive) {
            AcademicPeriod::query()->update(['is_active' => false]);
        }

        AcademicPeriod::create([
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'description' => $validated['description'] ?? null,
            'is_active' => $isActive,
        ]);

        AuditLogger::log(
            'create',
            'academic_periods',
            $this->auditActorLabel() . ' created an academic period'
        );

        return redirect()
            ->route($this->routeName('index'))
            ->with('success', 'Academic period added successfully.');
    }

    public function update(Request $request, AcademicPeriod $academicPeriod)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', 'in:First Semester,Second Semester,Summer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'academic_year.regex' => 'Academic Year must be in YYYY-YYYY format.',
            'end_date.after' => 'End Date must be after Start Date.',
        ]);

        $this->ensureAcademicPeriodIsUnique($request, $validated['academic_year'], $validated['semester'], $academicPeriod->id);

        $isActive = (bool) ($validated['is_active'] ?? false);

        if ($isActive) {
            AcademicPeriod::where('id', '!=', $academicPeriod->id)
                ->update(['is_active' => false]);
        }

        $academicPeriod->update([
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'description' => $validated['description'] ?? null,
            'is_active' => $isActive,
        ]);

        AuditLogger::log(
            'update',
            'academic_periods',
            $this->auditActorLabel() . " updated academic period ID {$academicPeriod->id}"
        );

        return redirect()
            ->route($this->routeName('index'))
            ->with('success', 'Academic period updated successfully.');
    }

    public function destroy(AcademicPeriod $academicPeriod)
    {
        $academicPeriod->delete();

        AuditLogger::log(
            'delete',
            'academic_periods',
            $this->auditActorLabel() . " deleted academic period ID {$academicPeriod->id}"
        );
        return redirect()
            ->route($this->routeName('index'))
            ->with('success', 'Academic period deleted successfully.');
    }

    public function setActive(AcademicPeriod $academicPeriod)
    {
        AcademicPeriod::query()->update(['is_active' => false]);

        $academicPeriod->update([
            'is_active' => true,
        ]);

        return redirect()
            ->route($this->routeName('index'))
            ->with('success', 'Academic period set as active successfully.');
    }

    public function syncFromFlss(
        Request $request,
        FacultyApiService $facultyApiService
    ) {
        try {
            $currentActive = AcademicPeriod::query()
                ->where('is_active', true)
                ->first();

            $previousState = $currentActive
                ? [
                    'academic_year' => $currentActive->academic_year,
                    'semester' => $currentActive->semester,
                    'start_date' => optional(
                        $currentActive->start_date
                    )->format('Y-m-d'),
                    'end_date' => optional(
                        $currentActive->end_date
                    )->format('Y-m-d'),
                ]
                : null;

            $academicPeriod = $facultyApiService
                ->syncActiveAcademicYearSemester();

            $academicPeriod->refresh();

            $currentState = [
                'academic_year' => $academicPeriod->academic_year,
                'semester' => $academicPeriod->semester,
                'start_date' => optional(
                    $academicPeriod->start_date
                )->format('Y-m-d'),
                'end_date' => optional(
                    $academicPeriod->end_date
                )->format('Y-m-d'),
            ];

            $alreadySynced =
                $previousState !== null &&
                $previousState === $currentState;

            $message = $alreadySynced
                ? "Academic year {$academicPeriod->academic_year} "
                . "{$academicPeriod->semester} is already synced with FLSS."
                : 'Academic period synced from FLSS successfully.';

            AuditLogger::log(
                'sync',
                'academic_periods',
                $alreadySynced
                    ? $this->auditActorLabel() . " checked FLSS sync; academic period was already synced: "
                    . "{$academicPeriod->academic_year} - "
                    . "{$academicPeriod->semester}"
                    : $this->auditActorLabel() . " synced academic period from FLSS: "
                    . "{$academicPeriod->academic_year} - "
                    . "{$academicPeriod->semester}"
            );

            $payload = [
                'id' => $academicPeriod->id,
                'academic_year' => $academicPeriod->academic_year,
                'semester' => $academicPeriod->semester,

                'start_date' => optional(
                    $academicPeriod->start_date
                )->format('Y-m-d'),

                'end_date' => optional(
                    $academicPeriod->end_date
                )->format('Y-m-d'),

                'start_date_display' => optional(
                    $academicPeriod->start_date
                )->format('M d, Y'),

                'end_date_display' => optional(
                    $academicPeriod->end_date
                )->format('M d, Y'),

                'end_date_long' => optional(
                    $academicPeriod->end_date
                )->format('F d, Y'),

                'description' => $academicPeriod->description,
                'is_active' => (bool) $academicPeriod->is_active,
                'status' => $academicPeriod->status,

                'progress_percent' => (int) (
                    $academicPeriod->progress_percent ?? 0
                ),

                'days_remaining' => (int) (
                    $academicPeriod->days_remaining ?? 0
                ),
                'update_url' => route($this->routeName('update'), $academicPeriod),
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'already_synced' => $alreadySynced,
                    'message' => $message,
                    'academic_period' => $payload,
                ]);
            }

            return redirect()
                ->route($this->routeName('index'))
                ->with(
                    $alreadySynced ? 'info' : 'success',
                    $message
                );
        } catch (\Throwable $e) {
            report($e);

            $message =
                'Failed to sync academic period from FLSS: '
                . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route($this->routeName('index'))
                ->with('error', $message);
        }
    }

    private function resolveLayoutRole(): string
    {
        return request()->routeIs('dentist.academic_periods*') ? 'dentist' : 'admin';
    }

    private function routeName(string $action): string
    {
        if (request()->routeIs('dentist.academic_periods*')) {
            return match ($action) {
                'index' => 'dentist.academic_periods',
                'store' => 'dentist.academic_periods.store',
                'update' => 'dentist.academic_periods.update',
                'destroy' => 'dentist.academic_periods.destroy',
                'set_active' => 'dentist.academic_periods.set_active',
                'sync_flss' => 'dentist.academic_periods.sync_flss',
            };
        }

        return match ($action) {
            'index' => 'admin.academic_periods',
            'store' => 'admin.academic_periods.store',
            'update' => 'admin.academic_periods.update',
            'destroy' => 'admin.academic_periods.destroy',
            'set_active' => 'admin.academic_periods.set_active',
            'sync_flss' => 'admin.academic_periods.sync_flss',
        };
    }

    private function auditActorLabel(): string
    {
        return $this->resolveLayoutRole() === 'dentist' ? 'Dentist' : 'Admin';
    }

    private function ensureAcademicPeriodIsUnique(
        Request $request,
        string $academicYear,
        string $semester,
        ?int $ignoreId = null
    ): void {
        $semesterAliases = $this->semesterAliases($semester);

        $duplicateQuery = AcademicPeriod::query()
            ->where('academic_year', $academicYear)
            ->whereIn('semester', $semesterAliases);

        if ($ignoreId !== null) {
            $duplicateQuery->whereKeyNot($ignoreId);
        }

        if (!$duplicateQuery->exists()) {
            return;
        }

        $request->validate([
            'academic_year' => [
                function ($attribute, $value, $fail) use ($semester) {
                    $fail("The {$value} {$semester} academic period already exists.");
                },
            ],
        ]);
    }

    private function semesterAliases(string $semester): array
    {
        return match ($semester) {
            'First Semester', '1st Semester' => ['First Semester', '1st Semester'],
            'Second Semester', '2nd Semester' => ['Second Semester', '2nd Semester'],
            default => [$semester],
        };
    }
}
