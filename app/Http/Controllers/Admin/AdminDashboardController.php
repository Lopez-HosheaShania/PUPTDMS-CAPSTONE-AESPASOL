<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dentist\DentistReportController;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\AcademicPeriod;
use App\Models\Inventory;
use Carbon\Carbon;
use App\Models\AuditLog;
use App\Helpers\AuditLogger;
use App\Helpers\PhilippineHolidays;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index(DentistReportController $dentistReportController)
    {
        $user = Auth::user();

        if (!$user || !in_array(optional($user->role)->slug, ['admin', 'super_admin'], true)) {
            return redirect('/login');
        }

        AuditLogger::log(
            'view',
            'admin_dashboard',
            'Admin viewed the dashboard'
        );

        $now = Carbon::now();

        // Reuse the exact same GAD computation used by Dentist Reports.
        // No second query implementation and no separate dashboard endpoint.
        $gadDashboardData = $dentistReportController->gadChartDataForPeriod(
            $now->year,
            $now->month
        );

        $totalPatients = Patient::count();

        $appointmentsThisMonth = Appointment::whereYear('appointment_date', $now->year)
            ->whereMonth('appointment_date', $now->month)
            ->count();

        $documentsThisMonth = \App\Models\DocumentRequest::withStateColumns()->whereYear('request_date', $now->year)
            ->whereMonth('request_date', $now->month)
            ->where('status', 'approved')
            ->count();

        $inventoryItems = Inventory::get();

        $inventoryTotal = $inventoryItems->count();
        $inventoryMedicine = $inventoryItems->where('category', 'Medicine')->count();
        $inventorySupplies = $inventoryItems->where('category', 'Supplies')->count();
        $inventoryLowStock = $inventoryItems->filter(fn($item) => $item->balance > 0 && $item->balance <= 5)->count();
        $inventoryOutOfStock = $inventoryItems->filter(fn($item) => $item->balance <= 0)->count();
        $inventoryInStock = $inventoryItems->filter(fn($item) => $item->balance > 5)->count();

        $inventoryCriticalItems = $inventoryItems
            ->filter(fn($item) => $item->balance <= 5)
            ->sortBy('balance')
            ->take(5)
            ->values();

        $notifications = [];

        $recentLogs = AuditLog::latest()->take(5)->get()->map(function ($log) {
            return $log;
        });

        $logThisMonth = AuditLog::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();

        $logInfo = AuditLog::where('action', 'view')->count();

        $logWarnings = AuditLog::where('action', 'login')->count();

        $logErrors = AuditLog::where('action', 'error')->count();

        $activePeriod = AcademicPeriod::with([
            'academicYear',
            'academicTerm',
        ])
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->first();

        $holidays = PhilippineHolidays::recordsRange(1, 1);

        return view('admin.admin-dashboard', compact(
            'totalPatients',
            'appointmentsThisMonth',
            'documentsThisMonth',
            'notifications',
            'recentLogs',
            'logThisMonth',
            'logInfo',
            'logWarnings',
            'logErrors',
            'activePeriod',
            'holidays',
            'inventoryTotal',
            'inventoryMedicine',
            'inventorySupplies',
            'inventoryLowStock',
            'inventoryOutOfStock',
            'inventoryInStock',
            'inventoryCriticalItems',
            'gadDashboardData'
        ));
    }
}
