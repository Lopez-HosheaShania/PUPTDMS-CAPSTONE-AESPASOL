<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\OpenAIReportService;

class AdminReportController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // ---------------------------
        // PATIENT STATS
        // ---------------------------
        $totalPatients = DB::table('patients')->count();

        $newToday = DB::table('patients')
            ->whereDate('created_at', $now->toDateString())
            ->count();

        $newYesterday = DB::table('patients')
            ->whereDate('created_at', $now->copy()->subDay()->toDateString())
            ->count();

        $newThisWeek = DB::table('patients')
            ->whereBetween('created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek()
            ])
            ->count();

        $newThisMonth = DB::table('patients')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $lastMonth = $now->copy()->subMonth();

        $lastMonthPatients = DB::table('patients')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $newMonthPct = $lastMonthPatients > 0
            ? round((($newThisMonth - $lastMonthPatients) / $lastMonthPatients) * 100)
            : 0;

        $stats = [
            'total_patients' => $totalPatients,
            'new_today' => $newToday,
            'new_today_diff' => $newToday - $newYesterday,
            'new_this_week' => $newThisWeek,
            'new_this_month' => $newThisMonth,
            'new_month_pct' => $newMonthPct,
            'returning_pct' => 0,
            'avg_visits' => 0,
        ];

        // ---------------------------
        // TREATMENTS
        // ---------------------------
        $treatmentRaw = DB::table('appointments')
            ->select('service_type', DB::raw('COUNT(*) as total'))
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->where('status', 'completed')
            ->groupBy('service_type')
            ->get();

        $totalTreatments = $treatmentRaw->sum('total');

        $breakdown = $treatmentRaw->map(function ($item) use ($totalTreatments) {
            return [
                'name' => ucfirst($item->service_type ?? 'Other'),
                'count' => (int) $item->total,
                'pct' => $totalTreatments > 0 ? round(($item->total / $totalTreatments) * 100) : 0,
            ];
        });

        $treatments = [
            'total' => $totalTreatments,
            'breakdown' => $breakdown,
        ];

        // ---------------------------
        // APPOINTMENTS
        // ---------------------------
        $appointmentsTotal = DB::table('appointments')
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $completed = DB::table('appointments')
            ->where('status', 'completed')
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $cancelled = DB::table('appointments')
            ->where('status', 'cancelled')
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $noShow = DB::table('appointments')
            ->whereIn('status', ['no_show', 'no-show'])
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $appointments = [
            'total' => $appointmentsTotal,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'no_show' => $noShow,
            'completion_rate' => $appointmentsTotal > 0 ? round(($completed / $appointmentsTotal) * 100) : 0,
            'no_show_rate' => $appointmentsTotal > 0 ? round(($noShow / $appointmentsTotal) * 100) : 0,
            'cancelled_rate' => $appointmentsTotal > 0 ? round(($cancelled / $appointmentsTotal) * 100) : 0,
        ];

        // ---------------------------
        // INVENTORY
        // ---------------------------
        $inventoryItems = collect();
        $lowStockCount = 0;

        if (DB::getSchemaBuilder()->hasTable('inventory_items')) {
            $items = DB::table('inventory_items')->get();

            $inventoryItems = $items->map(function ($item) {
                $qty = isset($item->qty) ? (int) $item->qty : 0;
                $used = isset($item->used) ? (int) $item->used : 0;
                $minLevel = isset($item->min_level) ? (int) $item->min_level : 10;
                $inStock = $qty - $used;

                return [
                    'name' => $item->name ?? 'Unnamed Item',
                    'used' => $used,
                    'in_stock' => $inStock,
                    'min_level' => $minLevel,
                ];
            });

            $lowStockCount = $inventoryItems->filter(function ($item) {
                return $item['in_stock'] < $item['min_level'];
            })->count();
        }

        $inventory = [
            'items' => $inventoryItems,
            'low_stock_count' => $lowStockCount,
        ];

        // ---------------------------
        // CHARTS
        // ---------------------------
        $months = collect(range(1, 12))->map(function ($m) {
            return Carbon::create()->month($m)->format('M');
        });

        $barData = collect(range(1, 12))->map(function ($m) use ($now) {
            return DB::table('appointments')
                ->whereMonth('appointment_date', $m)
                ->whereYear('appointment_date', $now->year)
                ->where('status', 'completed')
                ->count();
        });

        $lineNewPatients = collect(range(1, 12))->map(function ($m) use ($now) {
            return DB::table('patients')
                ->whereMonth('created_at', $m)
                ->whereYear('created_at', $now->year)
                ->count();
        });

        $runningTotal = 0;
        $lineTotals = $lineNewPatients->map(function ($count) use (&$runningTotal) {
            $runningTotal += $count;
            return $runningTotal;
        });

        $charts = [
            'bar' => [
                'labels' => $months->values()->all(),
                'data' => $barData->values()->all(),
            ],
            'pie' => [
                'labels' => $breakdown->pluck('name')->values()->all(),
                'data' => $breakdown->pluck('count')->values()->all(),
            ],
            'line' => [
                'labels' => $months->values()->all(),
                'totals' => $lineTotals->values()->all(),
                'new_patients' => $lineNewPatients->values()->all(),
            ],
        ];

        return view('admin.reports', compact(
            'stats',
            'treatments',
            'appointments',
            'inventory',
            'charts'
        ));
    }

    public function aiGenerated(OpenAIReportService $openAIReportService)
    {
        $now = Carbon::now();
        $period = $now->format('F Y');

        // ---------------------------
        // PATIENT ANALYSIS
        // ---------------------------
        $totalPatients = DB::table('patients')->count();

        $newThisMonth = DB::table('patients')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        $lastMonth = $now->copy()->subMonth();

        $lastMonthPatients = DB::table('patients')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $patientGrowthRate = $lastMonthPatients > 0
            ? round((($newThisMonth - $lastMonthPatients) / $lastMonthPatients) * 100)
            : 0;

        // ---------------------------
        // APPOINTMENT ANALYSIS
        // ---------------------------
        $appointmentsTotal = DB::table('appointments')
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $completed = DB::table('appointments')
            ->where('status', 'completed')
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $cancelled = DB::table('appointments')
            ->where('status', 'cancelled')
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $noShow = DB::table('appointments')
            ->whereIn('status', ['no_show', 'no-show'])
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $completionRate = $appointmentsTotal > 0 ? round(($completed / $appointmentsTotal) * 100) : 0;
        $cancelledRate = $appointmentsTotal > 0 ? round(($cancelled / $appointmentsTotal) * 100) : 0;
        $noShowRate = $appointmentsTotal > 0 ? round(($noShow / $appointmentsTotal) * 100) : 0;

        // ---------------------------
        // TREATMENT ANALYSIS
        // ---------------------------
        $topTreatment = DB::table('appointments')
            ->select('service_type', DB::raw('COUNT(*) as total'))
            ->where('status', 'completed')
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->first();

        $totalTreatments = DB::table('appointments')
            ->where('status', 'completed')
            ->whereMonth('appointment_date', $now->month)
            ->whereYear('appointment_date', $now->year)
            ->count();

        $topTreatmentName = $topTreatment && $topTreatment->service_type
            ? ucfirst($topTreatment->service_type)
            : 'No dominant treatment yet';

        // ---------------------------
        // INVENTORY ANALYSIS
        // ---------------------------
        $lowStockItems = collect();

        if (DB::getSchemaBuilder()->hasTable('inventory_items')) {
            $lowStockItems = DB::table('inventory_items')
                ->get()
                ->map(function ($item) {
                    $qty = isset($item->qty) ? (int) $item->qty : 0;
                    $used = isset($item->used) ? (int) $item->used : 0;
                    $minLevel = isset($item->min_level) ? (int) $item->min_level : 10;
                    $inStock = $qty - $used;

                    return [
                        'name' => $item->name ?? 'Unnamed Item',
                        'in_stock' => $inStock,
                        'min_level' => $minLevel,
                    ];
                })
                ->filter(function ($item) {
                    return $item['in_stock'] < $item['min_level'];
                })
                ->values();
        }

        $lowStockCount = $lowStockItems->count();

        $criticalStockCount = $lowStockItems->filter(function ($item) {
            return $item['in_stock'] <= 0 || $item['in_stock'] < ($item['min_level'] * 0.5);
        })->count();

        // ---------------------------
        // AI-LIKE RISK CLASSIFICATION
        // ---------------------------
        $riskLevel = 'Low';
        $riskExplanation = 'The current statistics show stable system performance with no major operational concern detected.';

        if ($criticalStockCount > 0 || $cancelledRate >= 30 || $noShowRate >= 20) {
            $riskLevel = 'High';
            $riskExplanation = 'The system detected high operational risk due to critical stock levels, high cancellation rate, or high no-show rate.';
        } elseif ($lowStockCount > 0 || $cancelledRate >= 15 || $noShowRate >= 10 || $completionRate < 60) {
            $riskLevel = 'Moderate';
            $riskExplanation = 'The system detected moderate operational risk. Some areas require monitoring, especially appointment completion and inventory availability.';
        }

        // ---------------------------
        // GENERATED REPORT CONTENT
        // ---------------------------
        $growthDescription = $patientGrowthRate > 0
            ? 'increased'
            : ($patientGrowthRate < 0 ? 'decreased' : 'remained stable');

        $reportData = [
            'period' => $period,
            'generated_at' => $now->format('M d, Y h:i A'),

            'patients' => [
                'total_patients' => $totalPatients,
                'new_this_month' => $newThisMonth,
                'last_month_patients' => $lastMonthPatients,
                'patient_growth_rate' => $patientGrowthRate,
                'growth_description' => $growthDescription,
            ],

            'appointments' => [
                'total' => $appointmentsTotal,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'no_show' => $noShow,
                'completion_rate' => $completionRate,
                'cancelled_rate' => $cancelledRate,
                'no_show_rate' => $noShowRate,
            ],

            'treatments' => [
                'total_treatments' => $totalTreatments,
                'top_treatment' => $topTreatmentName,
            ],

            'inventory' => [
                'low_stock_count' => $lowStockCount,
                'critical_stock_count' => $criticalStockCount,
                'low_stock_items' => $lowStockItems->values()->all(),
            ],

            'risk' => [
                'risk_level' => $riskLevel,
                'risk_explanation' => $riskExplanation,
            ],
        ];

        $fallbackReport = [
            'executive_summary' => "For {$period}, the Dental Management System recorded {$totalPatients} total patients, with {$newThisMonth} new patient registrations this month. Patient growth {$growthDescription} by " . abs($patientGrowthRate) . "% compared to last month. The system also recorded {$appointmentsTotal} appointments, with a completion rate of {$completionRate}% and a cancellation rate of {$cancelledRate}%. Based on the available data, the generated report classifies the current operational risk level as {$riskLevel}.",

            'key_findings' => [
                "The system currently has {$totalPatients} registered patients.",
                "There were {$newThisMonth} new patient records created this month.",
                "Patient growth {$growthDescription} by " . abs($patientGrowthRate) . "% compared to last month.",
                "A total of {$appointmentsTotal} appointments were recorded for {$period}.",
                "{$completed} appointments were completed, resulting in a {$completionRate}% completion rate.",
                "{$cancelled} appointments were cancelled, resulting in a {$cancelledRate}% cancellation rate.",
                "{$noShow} appointments were marked as no-show, resulting in a {$noShowRate}% no-show rate.",
            ],

            'treatment_analysis' => [
                "The system recorded {$totalTreatments} completed treatment procedures this month.",
                "The most frequently recorded treatment is {$topTreatmentName}.",
                "Treatment data can help the clinic identify common patient needs and prepare the required dental materials in advance.",
            ],

            'inventory_analysis' => [
                "The system detected {$lowStockCount} item/s below the minimum stock threshold.",
                "{$criticalStockCount} item/s are considered critical and may require urgent restocking.",
                $lowStockCount > 0
                    ? 'Inventory monitoring should be prioritized to prevent service delays during clinic operations.'
                    : 'Inventory levels appear sufficient based on the current stock threshold data.',
            ],

            'recommendations' => [
                $completionRate < 60
                    ? 'Review appointment workflow and follow-up procedures to improve completion rate.'
                    : 'Maintain the current appointment monitoring process to sustain completion performance.',

                $cancelledRate >= 15
                    ? 'Analyze common cancellation reasons and improve reminder or rescheduling procedures.'
                    : 'Continue monitoring cancellations to keep appointment utilization stable.',

                $noShowRate >= 10
                    ? 'Strengthen patient reminders through notification channels to reduce no-show incidents.'
                    : 'Maintain the current patient reminder process to minimize no-shows.',

                $lowStockCount > 0
                    ? 'Restock low inventory items and review minimum stock thresholds.'
                    : 'Continue regular inventory audits to maintain sufficient stock levels.',

                'Use monthly report trends to support administrative planning, clinic scheduling, and resource allocation.',
            ],
        ];

        $aiContent = $openAIReportService->generate($reportData);

        $aiReport = array_merge([
            'period' => $period,
            'generated_at' => $now->format('M d, Y h:i A'),
            'risk_level' => $riskLevel,
            'risk_explanation' => $riskExplanation,
        ], is_array($aiContent) ? $aiContent : $fallbackReport);

        return view('admin.reports-ai-generated', compact('aiReport'));
    }
}
