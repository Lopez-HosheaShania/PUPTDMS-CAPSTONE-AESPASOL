<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\DailyTreatmentRecord;
use Illuminate\Http\Request;

class DailyTreatmentRecordController extends Controller
{
    public function index()
    {
        $notifications = [];

        return view('dentist.reports.daily-treatment-record', compact('notifications'));
    }

    public function list(Request $request)
    {
        $query = DailyTreatmentRecord::query();

        if ($request->filled('month') && preg_match('/^\d{4}-\d{2}$/', (string) $request->month)) {
            [$year, $month] = explode('-', $request->month);

            $query->whereYear('treatment_date', $year)
                ->whereMonth('treatment_date', $month);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_email', 'like', "%{$search}%")
                    ->orWhere('patient_phone', 'like', "%{$search}%")
                    ->orWhere('office_type', 'like', "%{$search}%")
                    ->orWhere('program_code', 'like', "%{$search}%")
                    ->orWhere('gender', 'like', "%{$search}%")
                    ->orWhere('treatment_done', 'like', "%{$search}%");
            });
        }

        if ($request->filled('office_type')) {
            $query->where('office_type', $request->office_type);
        }

        if ($request->filled('program_code')) {
            $query->where('program_code', $request->program_code);
        }

        $hasDateSort = $request->filled('sort_date');
        $hasNameSort = $request->filled('sort_name');

        if ($hasDateSort) {
            $query->orderBy('treatment_date', $request->sort_date === 'asc' ? 'asc' : 'desc');
        }

        if ($hasNameSort) {
            $query->orderBy('patient_name', $request->sort_name === 'za' ? 'desc' : 'asc');
        }

        if (!$hasDateSort && !$hasNameSort) {
            $query->orderByDesc('treatment_date');
        }

        $query->orderByDesc('id');

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $records = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $records->items(),
            'meta' => [
                'total' => $records->total(),
                'per_page' => $records->perPage(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
            ],
        ]);
    }
}
