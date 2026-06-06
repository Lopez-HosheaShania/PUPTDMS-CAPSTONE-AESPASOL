<?php

namespace App\Http\Controllers\Admin;

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
        $holidays = PhilippineHolidays::range(1, 1);

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

        $periods = AcademicPeriod::all();
        AuditLogger::log(
            'view',
            'academic_periods',
            'Admin viewed academic periods list'
        );

        return view('admin.academic-period', compact(
            'academicPeriods',
            'calendarPeriods',
            'activePeriod',
            'holidays'
        ));
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
            "Admin created an academic period"
        );

        return redirect()
            ->route('admin.academic_periods')
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
            "Admin updated academic period ID {$academicPeriod->id}"
        );

        return redirect()
            ->route('admin.academic_periods')
            ->with('success', 'Academic period updated successfully.');
    }

    public function destroy(AcademicPeriod $academicPeriod)
    {
        $academicPeriod->delete();

        AuditLogger::log(
            'delete',
            'academic_periods',
            "Admin deleted academic period ID {$academicPeriod->id}"
        );
        return redirect()
            ->route('admin.academic_periods')
            ->with('success', 'Academic period deleted successfully.');
    }

    public function setActive(AcademicPeriod $academicPeriod)
    {
        AcademicPeriod::query()->update(['is_active' => false]);

        $academicPeriod->update([
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.academic_periods')
            ->with('success', 'Academic period set as active successfully.');
    }

    public function syncFromFlss(FacultyApiService $facultyApiService)
    {
        try {
            $academicPeriod = $facultyApiService->syncActiveAcademicYearSemester();

            AuditLogger::log(
                'sync',
                'academic_periods',
                "Admin synced academic period from FLSS: {$academicPeriod->academic_year} - {$academicPeriod->semester}"
            );

            return redirect()
                ->route('admin.academic_periods')
                ->with('success', 'Academic period synced from FLSS successfully.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.academic_periods')
                ->with('error', 'Failed to sync academic period from FLSS: ' . $e->getMessage());
        }
    }
}
