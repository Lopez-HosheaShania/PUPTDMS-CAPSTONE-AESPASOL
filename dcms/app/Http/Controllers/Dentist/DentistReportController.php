<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DocumentTemplate;
use App\Services\DocumentTemplateRenderer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\AuditLogger;

class DentistReportController extends Controller
{
    public function index()
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $now       = Carbon::now();
        $thisMonth = $now->month;
        $thisYear  = $now->year;
        $today     = $now->toDateString();
        $lastMonth = $now->copy()->subMonth();

        $patientsThisMonth = Appointment::whereYear('appointment_date', $thisYear)
            ->whereMonth('appointment_date', $thisMonth)
            ->distinct('patient_id')->count('patient_id');

        $patientsLastMonth = Appointment::whereYear('appointment_date', $lastMonth->year)
            ->whereMonth('appointment_date', $lastMonth->month)
            ->distinct('patient_id')->count('patient_id');

        $patientsDelta = $patientsLastMonth > 0
            ? round((($patientsThisMonth - $patientsLastMonth) / $patientsLastMonth) * 100)
            : null;

        $appointmentsToday = Appointment::whereDate('appointment_date', $today)
            ->whereIn('status', ['pending', 'confirmed'])->count();

        $yesterday = $now->copy()->subDay()->toDateString();
        $appointmentsYesterday = Appointment::whereDate('appointment_date', $yesterday)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])->count();

        $appointmentsDelta = $appointmentsToday - $appointmentsYesterday;

        $casesThisMonth = Appointment::whereYear('appointment_date', $thisYear)
            ->whereMonth('appointment_date', $thisMonth)
            ->where('status', 'completed')->count();

        $casesLastMonth = Appointment::whereYear('appointment_date', $lastMonth->year)
            ->whereMonth('appointment_date', $lastMonth->month)
            ->where('status', 'completed')->count();

        $casesDelta = $casesLastMonth > 0
            ? round((($casesThisMonth - $casesLastMonth) / $casesLastMonth) * 100)
            : null;

        $lowStockItems = DB::table('inventory_items')
            ->whereRaw('(qty - used) <= (qty * 0.30)')->count();

        [$gadLabels, $gadFemale, $gadMale] = $this->buildGadData($thisYear, $thisMonth);

        [$weekLabels, $weeklyDatasets] = $this->buildWeeklyData($thisYear, $thisMonth);

        $totalAppointmentsThisMonth = Appointment::whereYear('appointment_date', $thisYear)
            ->whereMonth('appointment_date', $thisMonth)
            ->count();

        $cancelledAppointments = Appointment::whereYear('appointment_date', $thisYear)
            ->whereMonth('appointment_date', $thisMonth)
            ->where('status', 'cancelled')
            ->count();

        $cancellationRate = $totalAppointmentsThisMonth > 0
            ? round(($cancelledAppointments / $totalAppointmentsThisMonth) * 100)
            : 0;

        $daysElapsedThisMonth = max(1, min($now->day, $now->daysInMonth));
        $avgPatientsPerDay = round($patientsThisMonth / $daysElapsedThisMonth, 1);

        $patientVisitCounts = Appointment::select('patient_id', DB::raw('COUNT(*) as total_visits'))
            ->whereNotNull('patient_id')
            ->groupBy('patient_id')
            ->get();

        $returningPatients = $patientVisitCounts->where('total_visits', '>', 1)->count();
        $newPatients = $patientVisitCounts->where('total_visits', 1)->count();

        $topServices = Appointment::whereYear('appointment_date', $thisYear)
            ->whereMonth('appointment_date', $thisMonth)
            ->whereNotNull('service_type')
            ->select('service_type as name', DB::raw('COUNT(*) as total'))
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $inventoryItems = DB::table('inventory_items')
            ->select('category', 'name', 'qty', 'used')->orderBy('name')->get();

        $medicineItems = $inventoryItems->where('category', 'Medicine')->values();
        $suppliesItems = $inventoryItems->where('category', 'Supplies')->values();

        $lowStockRows     = DB::table('inventory_items')
            ->whereRaw('(qty - used) <= (qty * 0.30)')
            ->orderByRaw('(qty - used) ASC')->get();

        $lowStockMedicine = $lowStockRows->where('category', 'Medicine')->values();
        $lowStockSupplies = $lowStockRows->where('category', 'Supplies')->values();

        $periodOptions = [];
        for ($i = 0; $i < 3; $i++) {
            $periodOptions[] = $now->copy()->subMonths($i)->format('M Y');
        }

        $notifications = collect([]);
        $documentTemplates = DocumentTemplate::query()
            ->active()
            ->orderBy('name')
            ->get();

        AuditLogger::log(
            'view',
            'dentist_reports',
            "Dentist viewed reports dashboard"
        );

        return view('dentist.dentist-report', compact(
            'patientsThisMonth',
            'patientsDelta',
            'appointmentsToday',
            'appointmentsDelta',
            'casesThisMonth',
            'casesDelta',
            'lowStockItems',
            'gadLabels',
            'gadFemale',
            'gadMale',
            'weekLabels',
            'weeklyDatasets',
            'medicineItems',
            'suppliesItems',
            'lowStockMedicine',
            'lowStockSupplies',
            'periodOptions',
            'totalAppointmentsThisMonth',
            'cancelledAppointments',
            'cancellationRate',
            'avgPatientsPerDay',
            'returningPatients',
            'newPatients',
            'topServices',
            'notifications',
            'documentTemplates'
        ));
    }

    public function printTemplate(DocumentTemplate $template)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        abort_unless($template->status === 'active', 404);

        $renderer = app(DocumentTemplateRenderer::class);
        $renderedContent = $renderer->renderForPreview($template);

        AuditLogger::log(
            'view',
            'dentist_reports',
            "Dentist opened printable template: {$template->name}"
        );

        return view('dentist.document-template-print', compact('template', 'renderedContent'));
    }

    public function gadData(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $parsed = Carbon::createFromFormat('M Y', $request->input('period'))
            ?? Carbon::createFromFormat('F Y', $request->input('period'));

        [$labels, $female, $male] = $this->buildGadData($parsed->year, $parsed->month);

        $hasData = array_sum($female) + array_sum($male) > 0;

        AuditLogger::log(
            'view',
            'dentist_reports',
            "Dentist viewed GAD chart data"
        );

        return response()->json([
            'labels' => $labels,
            'female' => $female,
            'male'   => $male,
            'empty'  => !$hasData,
        ]);
    }

    public function weeklyData(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $parsed = Carbon::createFromFormat('M Y', $request->input('period'))
            ?? Carbon::createFromFormat('F Y', $request->input('period'));

        [$weekLabels, $datasets] = $this->buildWeeklyData($parsed->year, $parsed->month);

        AuditLogger::log(
            'view',
            'dentist_reports',
            "Dentist viewed weekly report data"
        );

        return response()->json([
            'labels'   => $weekLabels,
            'datasets' => $datasets,
            'empty'    => empty($datasets),
        ]);
    }

    public function dailyTreatmentRecord()
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        AuditLogger::log(
            'view',
            'dentist_daily_treatment_record',
            'Dentist viewed daily treatment record'
        );

        return view('dentist.daily-treatment');
    }

    public function dailyTreatmentRecordList(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = DB::table('daily_treatment_records');

        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->input('month'));

            $query->whereYear('treatment_date', $year)
                ->whereMonth('treatment_date', $month);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_email', 'like', "%{$search}%")
                    ->orWhere('patient_phone', 'like', "%{$search}%")
                    ->orWhere('office_type', 'like', "%{$search}%")
                    ->orWhere('program_code', 'like', "%{$search}%")
                    ->orWhere('treatment_done', 'like', "%{$search}%");
            });
        }

        if ($request->filled('office_type')) {
            $query->where('office_type', $request->input('office_type'));
        }

        if ($request->filled('program_code')) {
            $query->where('program_code', $request->input('program_code'));
        }

        if ($request->input('sort_name') === 'az') {
            $query->orderBy('patient_name', 'asc');
        } elseif ($request->input('sort_name') === 'za') {
            $query->orderBy('patient_name', 'desc');
        } elseif ($request->input('sort_date') === 'asc') {
            $query->orderBy('treatment_date', 'asc');
        } else {
            $query->orderBy('treatment_date', 'desc');
        }

        $records = $query->get()->map(function ($record) {
            return [
                'treatment_date' => $record->treatment_date ?? null,
                'patient_name' => $record->patient_name ?? '',
                'patient_email' => $record->patient_email ?? '',
                'patient_phone' => $record->patient_phone ?? '',
                'office_type' => $record->office_type ?? '',
                'program_code' => $record->program_code ?? '',
                'gender' => $record->gender ?? '',
                'treatment_done' => $record->treatment_done ?? '',
                'minutes_processed' => $record->minutes_processed ?? 0,
                'has_signature' => !empty($record->patient_signature ?? null),
            ];
        });

        return response()->json([
            'data' => $records,
        ]);
    }

    private function buildGadData(int $year, int $month): array
    {
        $gadRaw = DB::table('daily_treatment_records')
            ->whereYear('treatment_date', $year)
            ->whereMonth('treatment_date', $month)
            ->select('office_type', 'gender', DB::raw('COUNT(*) as total'))
            ->groupBy('office_type', 'gender')
            ->get();

        $gadLabels = ['Student', 'Administrative', 'Faculty', 'Dependent'];
        $gadFemale = [];
        $gadMale   = [];

        foreach ($gadLabels as $label) {
            $key       = $label === 'Student' ? null : $label;
            $gadFemale[] = (int) $gadRaw->where('office_type', $key)->where('gender', 'Female')->sum('total');
            $gadMale[]   = (int) $gadRaw->where('office_type', $key)->where('gender', 'Male')->sum('total');
        }

        return [$gadLabels, $gadFemale, $gadMale];
    }

    private function buildWeeklyData(int $year, int $month): array
    {
        $topServices = Appointment::whereYear('appointment_date', $year)
            ->whereMonth('appointment_date', $month)
            ->select('service_type', DB::raw('COUNT(*) as total'))
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->limit(3)
            ->pluck('service_type')
            ->toArray();

        if (empty($topServices)) {
            return [[], []];
        }

        $daysInMonth  = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $weeksInMonth = (int) ceil($daysInMonth / 7);
        $weekLabels   = array_map(fn($i) => "Week $i", range(1, $weeksInMonth));

        $weeklyRaw = Appointment::whereYear('appointment_date', $year)
            ->whereMonth('appointment_date', $month)
            ->whereIn('service_type', $topServices)
            ->select(
                'service_type',
                DB::raw('CEIL(DAY(appointment_date) / 7) as week_num'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('service_type', 'week_num')
            ->get();

        $chartColors = [
            ['border' => '#8B0000', 'bg' => 'rgba(139,0,0,0.08)'],
            ['border' => '#F59E0B', 'bg' => 'rgba(245,158,11,0.08)'],
            ['border' => '#3B82F6', 'bg' => 'rgba(59,130,246,0.08)'],
        ];

        $datasets = [];
        foreach ($topServices as $i => $service) {
            $data = [];
            for ($w = 1; $w <= $weeksInMonth; $w++) {
                $data[] = (int) $weeklyRaw->where('service_type', $service)->where('week_num', $w)->sum('total');
            }
            $color      = $chartColors[$i] ?? ['border' => '#6B7280', 'bg' => 'rgba(107,114,128,0.08)'];
            $datasets[] = [
                'label'           => $service,
                'data'            => $data,
                'borderColor'     => $color['border'],
                'backgroundColor' => $color['bg'],
                'tension'         => 0.4,
                'pointRadius'     => 5,
                'fill'            => true,
            ];
        }

        return [$weekLabels, $datasets];
    }
}
