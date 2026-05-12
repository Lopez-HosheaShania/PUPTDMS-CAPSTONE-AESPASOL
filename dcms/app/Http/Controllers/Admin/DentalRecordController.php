<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DentalRecordController extends Controller
{
    public function index()
    {
        // Default empty data if table doesn't exist
        $totalRecords = 0;
        $recordsToday = 0;
        $pending = 0;
        $records = collect();
        $topProcedure = 'No data yet';
        $completedThisWeek = 0;
        $patientsForFollowUp = 0;

        if (Schema::hasTable('dental_records')) {
            $totalRecords = DB::table('dental_records')->count();

            $recordsToday = DB::table('dental_records')
                ->whereDate('created_at', Carbon::today())
                ->count();

            $pending = DB::table('dental_records')
                ->where('status', 'pending')
                ->count();

            $records = DB::table('dental_records as dr')
                ->leftJoin('patients as p', 'dr.patient_id', '=', 'p.id')
                ->leftJoin('users as d', 'dr.dentist_id', '=', 'd.id')
                ->select(
                    'p.name as patient_name',
                    'dr.procedure_name as procedure',
                    'd.name as dentist_name',
                    'dr.status',
                    'dr.created_at as date'
                )
                ->latest('dr.created_at')
                ->get();

            // Get most common procedure
            $topProcedureObj = DB::table('dental_records')
                ->select('procedure_name', DB::raw('COUNT(*) as count'))
                ->groupBy('procedure_name')
                ->orderByDesc('count')
                ->first();
            $topProcedure = $topProcedureObj->procedure_name ?? 'No data yet';

            // Get completed this week
            $completedThisWeek = DB::table('dental_records')
                ->where('status', 'completed')
                ->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->count();

            // Get patients for follow-up (pending status)
            $patientsForFollowUp = DB::table('dental_records')
                ->where('status', 'pending')
                ->distinct('patient_id')
                ->count('patient_id');
        }

        return view('admin.admin-dental-records', compact(
            'totalRecords',
            'recordsToday',
            'pending',
            'records',
            'topProcedure',
            'completedThisWeek',
            'patientsForFollowUp'
        ));
    }
}