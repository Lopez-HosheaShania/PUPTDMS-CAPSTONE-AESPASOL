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
use App\Models\DocumentRequest;
use App\Models\DailyTreatmentRecord;
use App\Models\Inventory;
use App\Models\DentalHistory;
use App\Models\DentalHistoryAnswer;
use App\Models\DentalHistoryConcern;
use App\Models\DentalHistoryConditionDate;
use App\Models\MedicalHistory;
use App\Models\MedicalHistoryAnswer;
use App\Models\MedicalHistoryDiseaseAnswer;
use App\Models\AppointmentProcedure;
use Illuminate\Support\Facades\Auth;
use setasign\Fpdi\Fpdi;

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

        $customReportTypes = [
            'dental_services',
            'daily_treatment_record',
            'dental_health_record',
            'annual_dental_clearance',
            'dental_clearance',
            'gad_report',
            'dental_supplies_inventory',
            'medicine_inventory',
            'monthly_report',
            'dental_cases',
        ];

        $customReportTemplates = DocumentTemplate::query()
            ->active()
            ->whereIn('document_type', $customReportTypes)
            ->get()
            ->sortBy(function ($template) use ($customReportTypes) {
                return array_search($template->document_type, $customReportTypes, true);
            })
            ->values();

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
            'documentTemplates',
            'customReportTemplates'
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

    public function downloadGadReport(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'gad_report') {
            abort(422, 'This download route is only for the GAD Accomplishment Report.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/gad-accomplishment-template.pdf');

        if (!file_exists($templatePath)) {
            abort(404, 'GAD report PDF template was not found.');
        }

        $counts = $this->buildGadPdfCounts($from, $to);

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

            $this->drawGadPdfPage($pdf, $counts, $from, $to);
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded GAD Accomplishment Report PDF'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadAnnualDentalClearance(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'annual_dental_clearance') {
            abort(422, 'This download route is only for Annual Dental Clearance.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/annual-dental-clearance-template.pdf');

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Annual Dental Clearance PDF template was not found.',
            ], 404);
        }

        $approvedRequests = DocumentRequest::with(['patient', 'approvedBy'])
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereRaw('LOWER(document_type) = ?', ['annual_dental_clearance'])
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereRaw('LOWER(document_type) LIKE ?', ['%annual%'])
                            ->whereRaw('LOWER(document_type) LIKE ?', ['%clearance%']);
                    });
            })
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('approved_at', [$from, $to])
                    ->orWhere(function ($fallbackQuery) use ($from, $to) {
                        $fallbackQuery->whereNull('approved_at')
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            })
            ->orderBy('approved_at')
            ->orderBy('updated_at')
            ->get();

        if ($approvedRequests->isEmpty()) {
            return response()->json([
                'message' => 'No approved Annual Dental Clearance requests found for the selected date range.',
            ], 422);
        }

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        foreach ($approvedRequests as $documentRequest) {
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

            $this->drawAnnualDentalClearancePage($pdf, $documentRequest);
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Annual Dental Clearance PDF for ' . $approvedRequests->count() . ' approved request(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadDentalClearance(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'dental_clearance') {
            abort(422, 'This download route is only for Dental Clearance.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/dental-clearance-template.pdf');

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Dental Clearance PDF template was not found.',
            ], 404);
        }

        $approvedRequests = DocumentRequest::with(['patient', 'approvedBy'])
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereRaw('LOWER(document_type) = ?', ['dental_clearance'])
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereRaw('LOWER(document_type) LIKE ?', ['%dental%'])
                            ->whereRaw('LOWER(document_type) LIKE ?', ['%clearance%'])
                            ->whereRaw('LOWER(document_type) NOT LIKE ?', ['%annual%']);
                    });
            })
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('approved_at', [$from, $to])
                    ->orWhere(function ($fallbackQuery) use ($from, $to) {
                        $fallbackQuery->whereNull('approved_at')
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            })
            ->orderBy('approved_at')
            ->orderBy('updated_at')
            ->get();

        if ($approvedRequests->isEmpty()) {
            return response()->json([
                'message' => 'No approved Dental Clearance requests found for the selected date range.',
            ], 422);
        }

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        foreach ($approvedRequests as $documentRequest) {
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

            $this->drawDentalClearancePage($pdf, $documentRequest);
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Dental Clearance PDF for ' . $approvedRequests->count() . ' approved request(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadDentalServicesReport(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'dental_services') {
            abort(422, 'This download route is only for Dental Services Record.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/dental-services-template.pdf');

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Dental Services PDF template was not found.',
            ], 404);
        }

        $records = Appointment::with('patient')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No completed dental service appointments found for the selected date range.',
            ], 422);
        }

        $pdf = new Fpdi('L', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $rowsPerPage = 55;
        $recordChunks = $records->chunk($rowsPerPage);
        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            foreach ($recordChunks as $chunk) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

                $this->drawDentalServicesTemplateDate($pdf);
                $this->drawDentalServicesRows($pdf, $chunk->values());
            }
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Dental Services Record PDF for ' . $records->count() . ' record(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadMedicineInventoryReport(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'medicine_inventory') {
            abort(422, 'This download route is only for Medicine Inventory.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/medicine-inventory-template.pdf');

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Medicine Inventory PDF template was not found.',
            ], 404);
        }

        $items = Inventory::query()
            ->whereRaw('LOWER(category) LIKE ?', ['%medicine%'])
            ->whereDate('date_received', '>=', $from->toDateString())
            ->whereDate('date_received', '<=', $to->toDateString())
            ->orderBy('date_received')
            ->orderBy('stock_no')
            ->orderBy('name')
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'message' => 'No medicine inventory records found for the selected date range.',
            ], 422);
        }

        $pdf = new Fpdi('L', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $rowsPerPage = 34;
        $itemChunks = $items->chunk($rowsPerPage);
        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            foreach ($itemChunks as $chunk) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

                $this->drawMedicineInventoryRows($pdf, $chunk->values(), $from, $to);
            }
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Medicine Inventory PDF for ' . $items->count() . ' item(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadDentalSuppliesInventoryReport(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'dental_supplies_inventory') {
            abort(422, 'This download route is only for Dental Supplies Inventory.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/dental-supplies-inventory-template.pdf');

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Dental Supplies Inventory PDF template was not found.',
            ], 404);
        }

        $items = Inventory::query()
            ->whereRaw('LOWER(category) LIKE ?', ['%suppl%'])
            ->whereDate('date_received', '>=', $from->toDateString())
            ->whereDate('date_received', '<=', $to->toDateString())
            ->orderBy('date_received')
            ->orderBy('stock_no')
            ->orderBy('name')
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'message' => 'No dental supplies inventory records found for the selected date range.',
            ], 422);
        }

        $pdf = new Fpdi('L', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $rowsPerPage = 34;
        $itemChunks = $items->chunk($rowsPerPage);
        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            foreach ($itemChunks as $chunk) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

                $this->drawDentalSuppliesInventoryRows($pdf, $chunk->values(), $from, $to);
            }
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Dental Supplies Inventory PDF for ' . $items->count() . ' item(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadDailyTreatmentRecordReport(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'daily_treatment_record') {
            abort(422, 'This download route is only for Daily Treatment Record.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/daily-treatment-record-template.pdf');

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Daily Treatment Record PDF template was not found.',
            ], 404);
        }
        $records = Appointment::with('patient')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No completed appointments found for the selected Daily Treatment Record date range.',
            ], 422);
        }

        $pdf = new Fpdi('L', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $rowsPerPage = 8;
        $recordChunks = $records->chunk($rowsPerPage);
        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            foreach ($recordChunks as $chunk) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

                $this->drawDailyTreatmentRecordRows($pdf, $chunk->values());
            }
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Daily Treatment Record PDF for ' . $records->count() . ' record(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadDentalCasesReport(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'dental_cases') {
            abort(422, 'This download route is only for Dental Cases.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/dental-cases-template.pdf');

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Dental Cases PDF template was not found.',
            ], 404);
        }

        $appointments = Appointment::with('patient')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->filter(fn($appointment) => $appointment->patient);

        if ($appointments->isEmpty()) {
            return response()->json([
                'message' => 'No completed dental cases found for the selected date range.',
            ], 422);
        }

        $procedureDiagnosisByAppointment = AppointmentProcedure::query()
            ->whereIn('appointment_id', $appointments->pluck('id')->all())
            ->get()
            ->groupBy('appointment_id')
            ->map(function ($procedures) {
                return trim((string) ($procedures->last()->diagnosis ?? ''));
            });

        $caseGroups = $this->buildDentalCasesGroups($appointments, $procedureDiagnosisByAppointment);

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

            $this->drawDentalCasesPage($pdf, $caseGroups, $from, $to);
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Dental Cases PDF for ' . $appointments->count() . ' completed appointment(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadMonthlyReport(Request $request)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $templateRecord = DocumentTemplate::query()
            ->whereKey($validated['document_template_id'])
            ->where('status', 'active')
            ->firstOrFail();

        if ($templateRecord->document_type !== 'monthly_report') {
            abort(422, 'This download route is only for Monthly Report.');
        }

        $from = Carbon::parse($validated['date_from'])->startOfDay();

        $to = !empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/monthly-report-template.pdf');

        if (!file_exists($templatePath)) {
            return response()->json([
                'message' => 'Monthly Report PDF template was not found.',
            ], 404);
        }

        $appointments = Appointment::with('patient')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->filter(fn($appointment) => $appointment->patient);

        if ($appointments->isEmpty()) {
            return response()->json([
                'message' => 'No completed dental services found for the selected Monthly Report date range.',
            ], 422);
        }

        $reportData = $this->buildMonthlyReportData($appointments);

        $pdf = new Fpdi('L', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

            $this->drawMonthlyReportPage($pdf, $reportData, $from, $to);
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Monthly Report PDF for ' . $appointments->count() . ' completed appointment(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function downloadDentalHealthRecord(Request $request)
    {
        try {
            $templatePath = storage_path('app/report-templates/dental-health-record-template.pdf');

            if (!file_exists($templatePath)) {
                return response()->json([
                    'message' => 'Dental Health Record template was not found. Please save it as storage/app/report-templates/dental-health-record-template.pdf.',
                ], 404);
            }

            $fromInput = $request->input('date_from')
                ?: $request->input('from_date')
                ?: $request->input('start_date')
                ?: now()->startOfMonth()->toDateString();

            $toInput = $request->input('date_to')
                ?: $request->input('to_date')
                ?: $request->input('end_date')
                ?: now()->toDateString();

            $from = Carbon::parse($fromInput)->startOfDay();
            $to = Carbon::parse($toInput)->endOfDay();

            $appointments = Appointment::with(['patient', 'dentist'])
                ->where('status', 'completed')
                ->whereDate('appointment_date', '>=', $from->toDateString())
                ->whereDate('appointment_date', '<=', $to->toDateString())
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get()
                ->filter(fn($appointment) => $appointment->patient);

            if ($appointments->isEmpty()) {
                return response()->json([
                    'message' => 'No completed appointments found for the selected Dental Health Record date range.',
                ], 422);
            }

            $patients = $appointments
                ->pluck('patient')
                ->filter()
                ->unique('id')
                ->values();

            $treatmentsByPatient = $appointments->groupBy('patient_id');

            $pdf = new Fpdi('P', 'pt');
            $pdf->SetAutoPageBreak(false);
            $pdf->setSourceFile($templatePath);

            foreach ($patients as $patient) {
                $dentalHistory = DentalHistory::where('patient_id', $patient->id)
                    ->latest()
                    ->first();

                $dentalConcern = DentalHistoryConcern::where('patient_id', $patient->id)
                    ->latest()
                    ->first();

                $dentalDates = DentalHistoryConditionDate::where('patient_id', $patient->id)
                    ->latest()
                    ->first();

                $medicalHistory = MedicalHistory::where('patient_id', $patient->id)
                    ->latest()
                    ->first();

                $dentalAnswers = $this->getDentalHealthAnswerMap($patient->id);

                $medicalAnswers = $medicalHistory
                    ? $this->getMedicalHealthAnswerMap($patient->id, $medicalHistory->id)
                    : [];

                $diseaseAnswers = $medicalHistory
                    ? $this->getMedicalDiseaseAnswerMap($patient->id, $medicalHistory->id)
                    : [];

                $patientTreatments = $treatmentsByPatient->get($patient->id, collect());

                $patientAppointmentIds = $patientTreatments->pluck('id')->filter()->values();

                $appointmentProcedure = AppointmentProcedure::query()
                    ->where('patient_id', $patient->id)
                    ->when(
                        $patientAppointmentIds->isNotEmpty(),
                        fn($q) => $q->whereIn('appointment_id', $patientAppointmentIds->all())
                    )
                    ->latest('id')
                    ->first();

                $this->addDentalHealthTemplatePage($pdf, 1);
                $this->drawDentalHealthRecordPageOne(
                    $pdf,
                    $patient,
                    $dentalHistory,
                    $dentalAnswers,
                    $appointmentProcedure
                );

                $this->addDentalHealthTemplatePage($pdf, 2);
                $this->drawDentalHealthRecordPageTwo($pdf, $dentalAnswers, $dentalConcern, $dentalDates, $medicalHistory, $medicalAnswers, $diseaseAnswers);

                $this->addDentalHealthTemplatePage($pdf, 3);
                $this->drawDentalHealthRecordPageThree($pdf, $medicalHistory, $patientTreatments);
            }

            $fileName = 'dental-health-record-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.pdf';

            return response($pdf->Output('S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to generate Dental Health Record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function buildDentalCasesGroups($appointments, $procedureDiagnosisByAppointment): array
    {
        $groups = [
            'students' => [],
            'faculty' => [],
            'administrative' => [],
            'dependents' => [],
        ];

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;

            if (!$patient) {
                continue;
            }

            $groupKey = $this->classifyDentalCasesPatient($patient);

            $diagnosis = trim((string) ($procedureDiagnosisByAppointment[$appointment->id] ?? ''));

            if ($diagnosis === '') {
                $diagnosis = trim((string) ($appointment->service_type ?? ''));
            }

            if ($diagnosis === '') {
                $diagnosis = 'Dental Service';
            }

            if (!isset($groups[$groupKey][$diagnosis])) {
                $groups[$groupKey][$diagnosis] = 0;
            }

            $groups[$groupKey][$diagnosis]++;
        }

        foreach ($groups as $groupKey => $cases) {
            arsort($cases);

            $groups[$groupKey] = collect($cases)
                ->map(fn($total, $diagnosis) => [
                    'diagnosis' => $diagnosis,
                    'total' => $total,
                ])
                ->take(3)
                ->values()
                ->all();
        }

        return $groups;
    }

    private function buildMonthlyReportData($appointments): array
    {
        $rows = ['student', 'faculty', 'administrative', 'dependent'];

        $columns = [
            'actual_patient',
            'rde',
            'charting',
            'inquiry',
            'rx',
            'med_rx',
            'extraction',
            'prophylaxis',
            'temporary',
            'permanent',
            'panoramic',
            'periapical',
            'consent',
            'clearance',
            'certification',
            'referral_dentist',
            'referral_medical',
            'suture_removal',
            'reinstall_jacket',
        ];

        $data = [];

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $data[$row][$column] = 0;
            }

            $data[$row]['patient_ids'] = [];
        }

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;

            if (!$patient) {
                continue;
            }

            $rowKey = $this->classifyMonthlyReportPatient($patient);
            $data[$rowKey]['patient_ids'][$patient->id] = true;

            $columnKey = $this->classifyMonthlyReportService($appointment->service_type ?? '');

            if ($columnKey && array_key_exists($columnKey, $data[$rowKey])) {
                $data[$rowKey][$columnKey]++;
            }
        }

        foreach ($rows as $row) {
            $data[$row]['actual_patient'] = count($data[$row]['patient_ids']);
            unset($data[$row]['patient_ids']);
        }

        $data['total'] = [];

        foreach ($columns as $column) {
            $data['total'][$column] = 0;

            foreach ($rows as $row) {
                $data['total'][$column] += (int) ($data[$row][$column] ?? 0);
            }
        }

        return $data;
    }

    private function classifyMonthlyReportPatient($patient): string
    {
        $studentNo = strtolower(trim((string) ($patient->student_no ?? '')));
        $courseCode = strtolower(trim((string) ($patient->course_code ?? '')));
        $courseName = strtolower(trim((string) ($patient->course_name ?? '')));
        $yearLevel = strtolower(trim((string) ($patient->year_level ?? '')));
        $section = strtolower(trim((string) ($patient->section ?? '')));
        $facultyCode = strtolower(trim((string) ($patient->faculty_code ?? '')));

        $combined = trim(implode(' ', array_filter([
            $studentNo,
            $courseCode,
            $courseName,
            $yearLevel,
            $section,
            $facultyCode,
        ])));

        if (
            $studentNo !== '' ||
            $courseCode !== '' ||
            $courseName !== '' ||
            $yearLevel !== '' ||
            $section !== ''
        ) {
            return 'student';
        }

        if (str_contains($combined, 'admin') || str_contains($combined, 'administrative')) {
            return 'administrative';
        }

        if ($facultyCode !== '' || str_contains($combined, 'faculty')) {
            return 'faculty';
        }

        return 'dependent';
    }

    private function classifyMonthlyReportService(?string $serviceType): ?string
    {
        $service = strtolower(trim((string) $serviceType));

        if ($service === '') {
            return null;
        }

        if (str_contains($service, 'rde')) {
            return 'rde';
        }

        if (str_contains($service, 'chart')) {
            return 'charting';
        }

        if (str_contains($service, 'inquiry') || str_contains($service, 'consult')) {
            return 'inquiry';
        }

        if (str_contains($service, 'med') && str_contains($service, 'rx')) {
            return 'med_rx';
        }

        if (str_contains($service, 'rx') || str_contains($service, 'prescription')) {
            return 'rx';
        }

        if (str_contains($service, 'extract')) {
            return 'extraction';
        }

        if (str_contains($service, 'prophy') || str_contains($service, 'cleaning') || str_contains($service, 'oral prophylaxis')) {
            return 'prophylaxis';
        }

        if (str_contains($service, 'temporary')) {
            return 'temporary';
        }

        if (str_contains($service, 'permanent')) {
            return 'permanent';
        }

        if (str_contains($service, 'panoramic')) {
            return 'panoramic';
        }

        if (str_contains($service, 'periapical')) {
            return 'periapical';
        }

        if (str_contains($service, 'consent')) {
            return 'consent';
        }

        if (str_contains($service, 'clearance')) {
            return 'clearance';
        }

        if (str_contains($service, 'cert')) {
            return 'certification';
        }

        if (str_contains($service, 'dentist') && str_contains($service, 'referral')) {
            return 'referral_dentist';
        }

        if (str_contains($service, 'medical') && str_contains($service, 'referral')) {
            return 'referral_medical';
        }

        if (str_contains($service, 'suture')) {
            return 'suture_removal';
        }

        if (str_contains($service, 'jacket')) {
            return 'reinstall_jacket';
        }

        return 'inquiry';
    }

    private function drawMonthlyReportPage(Fpdi $pdf, array $reportData, Carbon $from, Carbon $to): void
    {
        $pdf->SetTextColor(0, 0, 0);

   
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(318, 50, 165, 18, 'F');

        $pdf->SetFont('Helvetica', 'B', 8);
        $this->drawCenteredPdfText(
            $pdf,
            397,
            59,
            $this->formatReportPeriodLabel($from, $to, 'As of'),
            165,
            8
        );

   
        $rowY = [
            'student' => 176.8,
            'faculty' => 207.2,
            'administrative' => 237.8,
            'dependent' => 268.3,
            'total' => 304.8,
        ];

      
        $colX = [
            'actual_patient' => 73.7,
            'rde' => 134.6,
            'charting' => 169.4,
            'inquiry' => 205.2,
            'rx' => 238.0,
            'med_rx' => 273.1,
            'extraction' => 308.9,
            'prophylaxis' => 345.4,
            'temporary' => 383.5,
            'permanent' => 421.0,
            'panoramic' => 458.9,
            'periapical' => 497.3,
            'consent' => 531.8,
            'clearance' => 564.8,
            'certification' => 602.4,
            'referral_dentist' => 635.0,
            'referral_medical' => 661.7,
            'suture_removal' => 696.0,
            'reinstall_jacket' => 735.5,
        ];

        foreach ($rowY as $rowKey => $y) {
            foreach ($colX as $colKey => $x) {
                $value = (int) ($reportData[$rowKey][$colKey] ?? 0);

                if ($value === 0) {
                    continue;
                }

                $pdf->SetFont('Helvetica', 'B', 7);
                $this->drawPdfCell($pdf, $x, $y, (string) $value, 18, 7, 'C');
            }
        }
    }

    private function classifyDentalCasesPatient($patient): string
    {
        $studentNo = strtolower(trim((string) ($patient->student_no ?? '')));
        $courseCode = strtolower(trim((string) ($patient->course_code ?? '')));
        $courseName = strtolower(trim((string) ($patient->course_name ?? '')));
        $yearLevel = strtolower(trim((string) ($patient->year_level ?? '')));
        $section = strtolower(trim((string) ($patient->section ?? '')));
        $facultyCode = strtolower(trim((string) ($patient->faculty_code ?? '')));

        $combined = trim(implode(' ', array_filter([
            $studentNo,
            $courseCode,
            $courseName,
            $yearLevel,
            $section,
            $facultyCode,
        ])));

        if (
            $studentNo !== '' ||
            $courseCode !== '' ||
            $courseName !== '' ||
            $yearLevel !== '' ||
            $section !== ''
        ) {
            return 'students';
        }

        if (str_contains($combined, 'admin') || str_contains($combined, 'administrative')) {
            return 'administrative';
        }

        if ($facultyCode !== '' || str_contains($combined, 'faculty')) {
            return 'faculty';
        }

        return 'dependents';
    }

    private function drawDentalCasesPage(Fpdi $pdf, array $caseGroups, Carbon $from, Carbon $to): void
    {
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(230, 154, 155, 18, 'F');

        $pdf->SetFont('Helvetica', 'B', 10);
        $this->drawCenteredPdfText(
            $pdf,
            306,
            164,
            strtoupper($this->formatDentalCasesPeriodLabel($from, $to)),
            160,
            10
        );

        $this->drawDentalCasesSection($pdf, $caseGroups['students'] ?? [], 225.5);
        $this->drawDentalCasesSection($pdf, $caseGroups['faculty'] ?? [], 320.5);
        $this->drawDentalCasesSection($pdf, $caseGroups['administrative'] ?? [], 415.5);
        $this->drawDentalCasesSection($pdf, $caseGroups['dependents'] ?? [], 511.5);
    }

    private function drawDentalCasesSection(Fpdi $pdf, array $rows, float $firstRowY): void
    {
        $rowHeight = 15.5;

        foreach (array_slice($rows, 0, 3) as $index => $row) {
            $y = $firstRowY + ($index * $rowHeight);

            $diagnosis = trim((string) ($row['diagnosis'] ?? ''));
            $total = (string) ((int) ($row['total'] ?? 0));

            $pdf->SetFont('Helvetica', '', 7.2);

            $this->drawPdfCell($pdf, 262, $y, $diagnosis, 210, 8, 'L');

            $pdf->SetFont('Helvetica', 'B', 7.2);
            $this->drawPdfCell($pdf, 430, $y, $total, 88, 8, 'C');
        }
    }

    private function formatDentalCasesPeriodLabel(Carbon $from, Carbon $to): string
    {
        if ($from->isSameMonth($to) && $from->isSameYear($to)) {
            return $from->format('F Y');
        }

        if ($from->isSameYear($to)) {
            return $from->format('F') . ' TO ' . $to->format('F Y');
        }

        return $from->format('F Y') . ' TO ' . $to->format('F Y');
    }

    private function addDentalHealthTemplatePage(Fpdi $pdf, int $pageNumber): void
    {
        $templateId = $pdf->importPage($pageNumber);
        $size = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
    }

    private function drawDentalHealthRecordPageOne(
        Fpdi $pdf,
        $patient,
        ?DentalHistory $dentalHistory,
        array $dentalAnswers,
        ?AppointmentProcedure $appointmentProcedure
    ): void {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 8);

        $patientName = trim((string) ($patient->name ?? ''));

        $yearSection = '';

        if (!empty($patient->year_level)) {
            $yearSection .= 'Y' . $patient->year_level;
        }

        if (!empty($patient->section)) {
            $yearSection .= $yearSection !== '' ? ' - ' . $patient->section : $patient->section;
        }

        $facultyCollege = trim((string) ($patient->course_code ?? ''));

        if ($facultyCollege === '') {
            $facultyCollege = trim((string) ($patient->course_name ?? ''));
        }

        $adminDept = trim((string) ($patient->faculty_code ?? ''));

        $birthdate = !empty($patient->birthdate)
            ? Carbon::parse($patient->birthdate)->format('m/d/y')
            : '';

        $age = !empty($patient->birthdate)
            ? (string) Carbon::parse($patient->birthdate)->age
            : '';

        $sex = trim((string) ($patient->gender ?? ''));

        $lastDentalVisit = $dentalHistory && $dentalHistory->last_dental_visit
            ? Carbon::parse($dentalHistory->last_dental_visit)->format('m/d/y')
            : '';

        $previousDentist = trim((string) ($dentalHistory->previous_dentist ?? ''));

        $this->drawPdfCell($pdf, 333, 167, $patientName, 470, 8, 'L');
        $this->drawPdfCell($pdf, 157, 193, $yearSection, 95, 8, 'L');
        $this->drawPdfCell($pdf, 313, 193, $facultyCollege, 125, 8, 'L');
        $this->drawPdfCell($pdf, 510, 193, $adminDept, 100, 8, 'L');
        $this->drawPdfCell($pdf, 158, 208, $birthdate, 130, 8, 'L');
        $this->drawPdfCell($pdf, 250, 208, $age, 45, 8, 'C');
        $this->drawPdfCell($pdf, 385, 208, $sex, 110, 8, 'L');
        $this->drawDentalHealthOdontogram($pdf, $appointmentProcedure?->odontogram_data ?? []);

        $this->drawPdfCell($pdf, 250, 587, $previousDentist, 215, 8, 'L');
        $this->drawPdfCell($pdf, 205, 600, $lastDentalVisit, 205, 8, 'L');

        $pdf->SetFont('Helvetica', '', 7);

        $this->drawPdfCell($pdf, 272, 642, $this->dhrDentalAnswer($dentalAnswers, 'gum_bleeding'), 70, 8, 'L');
        $this->drawPdfCell($pdf, 238, 655, $this->dhrDentalAnswer($dentalAnswers, 'hot_cold_sensitive'), 70, 8, 'L');
        $this->drawPdfCell($pdf, 292, 668, $this->dhrDentalAnswer($dentalAnswers, 'sweet_sour_sensitive'), 90, 8, 'L');
        $this->drawPdfCell($pdf, 245, 682, $this->dhrDentalAnswer($dentalAnswers, 'tooth_pain'), 70, 8, 'L');
        $this->drawPdfCell($pdf, 284, 695, $this->dhrDentalAnswer($dentalAnswers, 'sores_lumps'), 70, 8, 'L');
        $this->drawPdfCell($pdf, 292, 708, $this->dhrDentalAnswer($dentalAnswers, 'head_neck_jaw_injuries'), 70, 8, 'L');
        $this->drawPdfCell($pdf, 93, 735, $this->dhrDentalAnswer($dentalAnswers, 'clicking'), 45, 8, 'L');
        $this->drawPdfCell($pdf, 162, 748, $this->dhrDentalAnswer($dentalAnswers, 'joint_pain'), 45, 8, 'L');
    }

    private function drawDentalHealthRecordPageTwo(
        Fpdi $pdf,
        array $dentalAnswers,
        ?DentalHistoryConcern $dentalConcern,
        ?DentalHistoryConditionDate $dentalDates,
        ?MedicalHistory $medicalHistory,
        array $medicalAnswers,
        array $diseaseAnswers
    ): void {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 6.2);

        $lastMedicalExam = $this->findDhrMedicalTextOnly($medicalAnswers, ['last', 'medical'])
            ?: $this->findDhrMedicalTextOnly($medicalAnswers, ['medical', 'examination'])
            ?: $this->pickMedicalHistoryValue($medicalHistory, [
                'last_medical_examination',
                'last_medical_exam',
                'last_medical_checkup',
                'last_medical_visit',
                'medical_examination_date',
                'medical_exam_date',
                'last_checkup',
            ]);

        $medicineAllergy = $this->pickMedicalHistoryValue($medicalHistory, [
            'medicine_allergy',
            'medicines_allergy',
            'allergy_medicines',
            'allergies_medicines',
            'medicine_allergies',
            'medication_allergy',
        ]);

        $foodAllergy = $this->pickMedicalHistoryValue($medicalHistory, [
            'food_allergy',
            'foods_allergy',
            'allergy_foods',
            'allergies_foods',
            'food_allergies',
        ]);

        $otherAllergy = $this->pickMedicalHistoryValue($medicalHistory, [
            'other_allergy',
            'others_allergy',
            'allergy_others',
            'allergies_others',
            'other_allergies',
        ]);

        if ($medicineAllergy === '' && $foodAllergy === '' && $otherAllergy === '') {
            $medicineAllergy = $this->findDhrMedicalTextOnly($medicalAnswers, ['allerg'])
                ?: $this->pickMedicalHistoryValue($medicalHistory, [
                    'allergies',
                    'allergy',
                    'allergy_details',
                    'allergies_details',
                ]);
        }

        $this->drawPdfCell($pdf, 162, 42, $this->dhrDentalAnswer($dentalAnswers, 'opening_closing'), 26, 8, 'C');
        $this->drawPdfCell($pdf, 130, 55, $this->dhrDentalAnswer($dentalAnswers, 'chewing_difficulty'), 26, 8, 'C');
        $this->drawPdfCell($pdf, 136, 68, $this->dhrDentalAnswer($dentalAnswers, 'frequent_headaches'), 24, 8, 'C');
        $this->drawPdfCell($pdf, 186, 82, $this->dhrDentalAnswer($dentalAnswers, 'clench_grind'), 23, 8, 'C');
        $this->drawPdfCell($pdf, 170, 95, $this->dhrDentalAnswer($dentalAnswers, 'lip_cheek_biting'), 22, 8, 'C');
        $this->drawPdfCell($pdf, 210, 109, $this->dhrDentalAnswer($dentalAnswers, 'loosening_teeth'), 22, 8, 'C');
        $this->drawPdfCell($pdf, 220, 122, $this->dhrDentalAnswer($dentalAnswers, 'food_caught'), 23, 8, 'C');

        $this->drawPdfCell($pdf, 300, 149, $this->dhrDentalAnswer($dentalAnswers, 'reaction_medicine_anesthetic'), 380, 8, 'L');
        $this->drawPdfCell($pdf, 325, 162, $this->dhrDentalAnswer($dentalAnswers, 'periodontal_treatment'), 170, 8, 'L');

        $this->drawPdfCell($pdf, 260, 175, $this->dhrDentalAnswer($dentalAnswers, 'difficult_extraction'), 70, 8, 'L');
        $this->drawPdfCell($pdf, 382, 175, $this->formatDhrDate($dentalDates?->extraction_date), 100, 8, 'L');

        $this->drawPdfCell($pdf, 390, 189, $this->dhrDentalAnswer($dentalAnswers, 'prolonged_bleeding'), 130, 8, 'L');

        $this->drawPdfCell($pdf, 216.5, 202, $this->dhrDentalAnswer($dentalAnswers, 'dentures'), 48, 8, 'C');
        $this->drawPdfCell($pdf, 370.0, 202, $this->formatDhrDate($dentalDates?->dentures_date), 65, 8, 'C');

        $this->drawPdfCell($pdf, 196.8, 215, $this->dhrDentalAnswer($dentalAnswers, 'orthodontic'), 48, 8, 'C');
        $this->drawPdfCell($pdf, 353.7, 215, $this->formatDhrDate($dentalDates?->ortho_date), 65, 8, 'C');

        $this->drawPdfMultiCell($pdf, 305, 244, trim((string) ($dentalConcern->additional_concerns ?? '')), 520, 12, 'L');

        $pdf->SetFont('Helvetica', '', 6.2);

        $goodHealth = $this->findDhrMedicalBool($medicalAnswers, ['good', 'health']);
        $hospitalized = $this->findDhrMedicalBool($medicalAnswers, ['hospitalized']);
        $takingMedication = $this->findDhrMedicalBool($medicalAnswers, ['medication']);

        $pregnant = $this->findDhrMedicalBool($medicalAnswers, ['pregnant']);
        $nursing = $this->findDhrMedicalBool($medicalAnswers, ['nursing']);
        $birthControl = $this->findDhrMedicalBool($medicalAnswers, ['birth', 'control']);

        $this->drawPdfCell($pdf, 153, 303, $this->dhrBoolMark($goodHealth, true), 16, 8, 'C');
        $this->drawPdfCell($pdf, 200, 303, $this->dhrBoolMark($goodHealth, false), 16, 8, 'C');
        $this->drawPdfCell($pdf, 384, 303, $this->findDhrMedicalTextOnly($medicalAnswers, ['good', 'health']), 115, 8, 'L');

      
        $this->drawPdfCell(
            $pdf,
            312,
            316,
            $lastMedicalExam,
            185,
            8,
            'L'
        );

        $this->drawPdfCell($pdf, 230, 343, $this->findDhrMedicalTextOnly($medicalAnswers, ['receiving']), 330, 8, 'L');

        $this->drawPdfCell($pdf, 205, 356, $this->dhrBoolText($hospitalized), 65, 8, 'C');
        $this->drawPdfCell($pdf, 252, 369, $this->findDhrMedicalTextOnly($medicalAnswers, ['hospitalized']), 430, 8, 'L');

        $this->drawPdfCell($pdf, 165, 396, $medicineAllergy, 115, 8, 'L');
        $this->drawPdfCell($pdf, 295, 396, $foodAllergy, 125, 8, 'L');
        $this->drawPdfCell($pdf, 445, 396, $otherAllergy, 145, 8, 'L');

        $this->drawPdfCell($pdf, 286, 410, $this->dhrBoolText($takingMedication), 32, 8, 'C');
        $this->drawPdfCell($pdf, 184, 423, $this->findDhrMedicalTextOnly($medicalAnswers, ['medication']), 140, 8, 'L');

        $this->drawPdfCell($pdf, 356, 438, $this->dhrBoolMark($pregnant, true), 20, 8, 'C');
        $this->drawPdfCell($pdf, 417, 438, $this->dhrBoolMark($pregnant, false), 20, 8, 'C');

        $this->drawPdfCell($pdf, 356, 452, $this->dhrBoolMark($nursing, true), 20, 8, 'C');
        $this->drawPdfCell($pdf, 417, 452, $this->dhrBoolMark($nursing, false), 20, 8, 'C');

        $this->drawPdfCell($pdf, 356, 466, $this->dhrBoolMark($birthControl, true), 20, 8, 'C');
        $this->drawPdfCell($pdf, 417, 466, $this->dhrBoolMark($birthControl, false), 20, 8, 'C');

        $pdf->SetFont('Helvetica', 'B', 7);

        $this->drawPdfCell($pdf, 45, 505, $this->findDhrDiseaseMark($diseaseAnswers, ['hiv']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 505, $this->findDhrDiseaseMark($diseaseAnswers, ['faint']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 517, $this->findDhrDiseaseMark($diseaseAnswers, ['alcohol']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 517, $this->findDhrDiseaseMark($diseaseAnswers, ['blood', 'pressure']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 529, $this->findDhrDiseaseMark($diseaseAnswers, ['arthritis']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 529, $this->findDhrDiseaseMark($diseaseAnswers, ['glycemia']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 541, $this->findDhrDiseaseMark($diseaseAnswers, ['artificial']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 541, $this->findDhrDiseaseMark($diseaseAnswers, ['kidney']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 553, $this->findDhrDiseaseMark($diseaseAnswers, ['asthma']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 553, $this->findDhrDiseaseMark($diseaseAnswers, ['liver']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 568, $this->findDhrDiseaseMark($diseaseAnswers, ['blood', 'transfusion']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 568, $this->findDhrDiseaseMark($diseaseAnswers, ['mental']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 580, $this->findDhrDiseaseMark($diseaseAnswers, ['cancer']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 580, $this->findDhrDiseaseMark($diseaseAnswers, ['ulcer']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 592, $this->findDhrDiseaseMark($diseaseAnswers, ['diabetes']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 592, $this->findDhrDiseaseMark($diseaseAnswers, ['stroke']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 605, $this->findDhrDiseaseMark($diseaseAnswers, ['eating']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 605, $this->findDhrDiseaseMark($diseaseAnswers, ['tuberculosis']), 18, 8, 'C');

        $this->drawPdfCell($pdf, 45, 618, $this->findDhrDiseaseMark($diseaseAnswers, ['epilepsy']), 18, 8, 'C');
        $this->drawPdfCell($pdf, 300, 618, $this->findDhrDiseaseMark($diseaseAnswers, ['venereal']), 18, 8, 'C');

        $pdf->SetFont('Helvetica', '', 7);

        $this->drawPdfCell($pdf, 222, 644, $this->findDhrMedicalText($medicalAnswers, ['tobacco']), 32, 8, 'C');
        $this->drawPdfCell($pdf, 172, 672, $this->findDhrMedicalText($medicalAnswers, ['headache']), 24, 8, 'C');
        $this->drawPdfMultiCell($pdf, 305, 698, $this->findDhrMedicalText($medicalAnswers, ['additional']), 520, 12, 'L');
    }

    private function drawDentalHealthRecordPageThree(Fpdi $pdf, ?MedicalHistory $medicalHistory, $patientTreatments): void
    {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 7.2);

        $this->drawPdfCell($pdf, 255, 55.7, trim((string) ($medicalHistory->emergency_person ?? '')), 105, 7, 'L');
        $this->drawPdfCell($pdf, 430, 55.7, trim((string) ($medicalHistory->emergency_relation ?? '')), 95, 7, 'L');
        $this->drawPdfCell($pdf, 176, 68.6, trim((string) ($medicalHistory->emergency_number ?? '')), 105, 7, 'L');
        $rowStartY = 151.0;
        $rowHeight = 18.25;
        $maxRows = 13;

        foreach ($patientTreatments->take($maxRows)->values() as $index => $appointment) {
            $y = $rowStartY + ($index * $rowHeight);

            $date = !empty($appointment->appointment_date)
                ? Carbon::parse($appointment->appointment_date)->format('m/d/y')
                : '';

            $diagnosis = '';
            $treatment = trim((string) ($appointment->service_type ?? ''));

            if ($treatment === '') {
                $treatment = 'Dental Service';
            }

            $attending = trim((string) ($appointment->dentist->name ?? ''));

            if ($attending === '') {
                $attending = 'DR. NELSON P. ANGELES';
            }

            $pdf->SetFont('Helvetica', '', 6.2);

            $this->drawPdfCell($pdf, 59.9, $y, $date, 42, 7, 'C');
            $this->drawPdfMultiCell($pdf, 153.5, $y, $diagnosis, 128, 8, 'L');
            $this->drawPdfMultiCell($pdf, 293.9, $y, $treatment, 128, 8, 'L');
            $this->drawPdfMultiCell($pdf, 434.3, $y, $attending, 128, 8, 'L');
        }
    }
    private function getDentalHealthAnswerMap(int $patientId): array
    {
        $orderedKeys = [
            'gum_bleeding',
            'hot_cold_sensitive',
            'sweet_sour_sensitive',
            'tooth_pain',
            'sores_lumps',
            'head_neck_jaw_injuries',
            'clicking',
            'joint_pain',
            'opening_closing',
            'chewing_difficulty',
            'frequent_headaches',
            'clench_grind',
            'lip_cheek_biting',
            'loosening_teeth',
            'food_caught',
            'reaction_medicine_anesthetic',
            'periodontal_treatment',
            'difficult_extraction',
            'prolonged_bleeding',
            'dentures',
            'orthodontic',
        ];

        $answers = DentalHistoryAnswer::with('condition')
            ->where('patient_id', $patientId)
            ->get()
            ->sortBy(function ($answer) {
                return $answer->condition->sort_order ?? 999;
            })
            ->values();

        $map = [];

        foreach ($answers as $index => $answer) {
            $value = $this->dhrBoolText($answer->answer);

            $sortOrder = $answer->condition?->sort_order;

            $fallbackKey = null;

            if ($sortOrder !== null) {
                $sortOrder = (int) $sortOrder;

                $fallbackKey = $orderedKeys[$sortOrder - 1] ?? $orderedKeys[$sortOrder] ?? null;
            }

            $fallbackKey = $fallbackKey ?? ($orderedKeys[$index] ?? null);

            if ($fallbackKey) {
                $map[$fallbackKey] = $value;
            }

            if ($answer->condition) {
                $codeKey = $this->normalizeDhrKey($answer->condition->code ?? '');
                $questionKey = $this->normalizeDhrKey($answer->condition->question ?? '');

                if ($codeKey !== '') {
                    $map[$codeKey] = $value;
                }

                if ($questionKey !== '') {
                    $map[$questionKey] = $value;
                }
            }
        }

        return $map;
    }

    private function dhrDentalAnswer(array $map, string $key): string
    {
        return $map[$key] ?? '';
    }

    private function getMedicalHealthAnswerMap(int $patientId, int $medicalHistoryId): array
    {
        $orderedKeys = [
            'good_health',
            'last_medical_examination',
            'receiving_treatment',
            'hospitalized',
            'allergies',
            'taking_medication',
            'pregnant',
            'nursing',
            'birth_control',
            'tobacco',
            'headache',
            'additional_health_information',
        ];

        $answers = MedicalHistoryAnswer::with('question')
            ->where('patient_id', $patientId)
            ->where('medical_history_id', $medicalHistoryId)
            ->get()
            ->sortBy(function ($answer) {
                return $answer->question->sort_order ?? 999;
            })
            ->values();

        $map = [];

        foreach ($answers as $index => $answer) {
            if (!$answer->question) {
                continue;
            }

            $value = [
                'bool' => $this->normalizeDhrNullableBool($answer->answer_bool),
                'text' => trim((string) ($answer->answer_text ?? '')),
                'date' => $answer->answer_date,
            ];

            $sortOrder = $answer->question?->sort_order;
            $fallbackKey = null;

            if ($sortOrder !== null) {
                $sortOrder = (int) $sortOrder;
                $fallbackKey = $orderedKeys[$sortOrder - 1] ?? $orderedKeys[$sortOrder] ?? null;
            }

            $fallbackKey = $fallbackKey ?? ($orderedKeys[$index] ?? null);

            $possibleKeys = [
                $fallbackKey,
                $answer->question->code ?? '',
                $answer->question->question ?? '',
                $answer->question->label ?? '',
                $answer->question->name ?? '',
            ];

            foreach ($possibleKeys as $possibleKey) {
                $key = $this->normalizeDhrKey($possibleKey);

                if ($key !== '') {
                    $map[$key] = $value;
                }
            }
        }

        return $map;
    }

    private function getMedicalDiseaseAnswerMap(int $patientId, int $medicalHistoryId): array
    {
        $answers = MedicalHistoryDiseaseAnswer::with('disease')
            ->where('patient_id', $patientId)
            ->where('medical_history_id', $medicalHistoryId)
            ->where('has_disease', true)
            ->get();

        $map = [];

        foreach ($answers as $answer) {
            if (!$answer->disease) {
                continue;
            }

            $codeKey = $this->normalizeDhrKey($answer->disease->code ?? '');
            $labelKey = $this->normalizeDhrKey($answer->disease->label ?? '');

            if ($codeKey !== '') {
                $map[$codeKey] = true;
            }

            if ($labelKey !== '') {
                $map[$labelKey] = true;
            }
        }

        return $map;
    }

    private function findDhrDentalAnswer(array $map, array $needles): string
    {
        foreach ($map as $key => $value) {
            if ($this->dhrKeyContainsAll($key, $needles)) {
                return $value;
            }
        }

        return '';
    }

    private function findDhrMedicalBool(array $map, array $needles): ?bool
    {
        foreach ($map as $key => $value) {
            if ($this->dhrKeyContainsAll($key, $needles)) {
                return $value['bool'] ?? null;
            }
        }

        return null;
    }

    private function findDhrMedicalText(array $map, array $needles): string
    {
        foreach ($map as $key => $value) {
            if (!$this->dhrKeyContainsAll($key, $needles)) {
                continue;
            }

            if (!empty($value['date'])) {
                return $this->formatDhrDate($value['date']);
            }

            if (!empty($value['text'])) {
                return trim((string) $value['text']);
            }

            if (array_key_exists('bool', $value)) {
                return $this->dhrBoolText($value['bool']);
            }
        }

        return '';
    }

    private function findDhrMedicalTextOnly(array $map, array $needles): string
    {
        foreach ($map as $key => $value) {
            if (!$this->dhrKeyContainsAll($key, $needles)) {
                continue;
            }

            if (!empty($value['date'])) {
                return $this->formatDhrDate($value['date']);
            }

            if (!empty($value['text'])) {
                return trim((string) $value['text']);
            }

            return '';
        }

        return '';
    }

    private function findDhrDiseaseMark(array $map, array $needles): string
    {
        foreach ($map as $key => $value) {
            if ($value && $this->dhrKeyContainsAll($key, $needles)) {
                return 'X';
            }
        }

        return '';
    }

    private function dhrKeyContainsAll(string $key, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = $this->normalizeDhrKey($needle);

            if ($needle === '') {
                continue;
            }

            if (!str_contains($key, $needle)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeDhrKey(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['_', '-', '/', '\\', '(', ')', '?', ',', '.', ':'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value ?? '');
    }

    private function normalizeDhrNullableBool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        if (in_array($value, ['1', 'yes', 'true', 'y', 'on'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'no', 'false', 'n', 'off'], true)) {
            return false;
        }

        return null;
    }

    private function dhrBoolText($value): string
    {
        $bool = $this->normalizeDhrNullableBool($value);

        if ($bool === null) {
            return '';
        }

        return $bool ? 'Yes' : 'No';
    }

    private function pickMedicalHistoryValue(?MedicalHistory $medicalHistory, array $fields): string
    {
        if (!$medicalHistory) {
            return '';
        }

        foreach ($fields as $field) {
            $value = data_get($medicalHistory, $field);

            if ($value === null || $value === '') {
                continue;
            }

            if ($value instanceof \Carbon\CarbonInterface) {
                return $value->format('m/d/y');
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            if (preg_match('/date|exam|examination|checkup|visit/i', $field)) {
                try {
                    return Carbon::parse($value)->format('m/d/y');
                } catch (\Throwable $e) {
                    return $value;
                }
            }

            return $value;
        }

        return '';
    }

    private function dhrBoolMark(?bool $value, bool $expected): string
    {
        if ($value === null) {
            return '';
        }

        return $value === $expected ? 'X' : '';
    }

    private function formatDhrDate($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('m/d/y');
        } catch (\Throwable $e) {
            return trim((string) $value);
        }
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

    private function drawGadPdfPage(Fpdi $pdf, array $counts, Carbon $from, Carbon $to): void
    {
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(320, 216, 155, 18, 'F');

        $pdf->SetFont('Helvetica', 'B', 10);
        $this->drawCenteredPdfText(
            $pdf,
            397,
            225,
            $this->formatReportPeriodLabel($from, $to, 'as of'),
            170,
            12
        );

        $columns = [
            'students' => 441,
            'faculty' => 487,
            'administrative' => 542,
            'dependent' => 601,
            'total' => 649,
        ];

        $rows = [
            'gad_male' => 329,
            'gad_female' => 343,
            'senior_male' => 357,
            'senior_female' => 372,
            'pwd_male' => 386,
            'pwd_female' => 401,
            'total' => 415,
        ];

        $pdf->SetFont('Helvetica', 'B', 10);

        foreach ($rows as $rowKey => $y) {
            $values = $counts[$rowKey] ?? [0, 0, 0, 0, 0];

            $this->drawCenteredPdfText($pdf, $columns['students'], $y, (string) $values[0]);
            $this->drawCenteredPdfText($pdf, $columns['faculty'], $y, (string) $values[1]);
            $this->drawCenteredPdfText($pdf, $columns['administrative'], $y, (string) $values[2]);
            $this->drawCenteredPdfText($pdf, $columns['dependent'], $y, (string) $values[3]);
            $this->drawCenteredPdfText($pdf, $columns['total'], $y, (string) $values[4]);
        }
    }

    private function drawCenteredPdfText(
        Fpdi $pdf,
        float $centerX,
        float $centerY,
        string $text,
        float $width = 36,
        float $height = 10
    ): void {
        $pdf->SetXY($centerX - ($width / 2), $centerY - ($height / 2));
        $pdf->Cell($width, $height, $text, 0, 0, 'C');
    }

    private function buildGadPdfCounts(Carbon $from, Carbon $to): array
    {
        $counts = [
            'gad_male' => [0, 0, 0, 0, 0],
            'gad_female' => [0, 0, 0, 0, 0],
            'senior_male' => [0, 0, 0, 0, 0],
            'senior_female' => [0, 0, 0, 0, 0],
            'pwd_male' => [0, 0, 0, 0, 0],
            'pwd_female' => [0, 0, 0, 0, 0],
            'total' => [0, 0, 0, 0, 0],
        ];

        $records = DB::table('daily_treatment_records')
            ->whereDate('treatment_date', '>=', $from->toDateString())
            ->whereDate('treatment_date', '<=', $to->toDateString())
            ->get();

        $columnIndexes = [
            'student' => 0,
            'faculty' => 1,
            'administrative' => 2,
            'dependent' => 3,
        ];

        foreach ($records as $record) {
            $gender = $this->normalizeGadGender($record->gender ?? null);

            if (!$gender) {
                continue;
            }

            $officeType = $this->normalizeGadOfficeType($record->office_type ?? null);
            $columnIndex = $columnIndexes[$officeType] ?? 0;

            $isSenior = $this->recordHasTruthyValue($record, [
                'is_senior_citizen',
                'senior_citizen',
                'is_senior',
            ]);

            $isPwd = $this->recordHasTruthyValue($record, [
                'is_pwd',
                'pwd',
                'is_person_with_disability',
            ]);

            if ($isSenior) {
                $rowKey = 'senior_' . $gender;
            } elseif ($isPwd) {
                $rowKey = 'pwd_' . $gender;
            } else {
                $rowKey = 'gad_' . $gender;
            }

            $counts[$rowKey][$columnIndex]++;
            $counts[$rowKey][4]++;

            $counts['total'][$columnIndex]++;
            $counts['total'][4]++;
        }

        return $counts;
    }

    private function normalizeGadGender(?string $gender): ?string
    {
        $gender = strtolower(trim((string) $gender));

        if (str_starts_with($gender, 'm')) {
            return 'male';
        }

        if (str_starts_with($gender, 'f')) {
            return 'female';
        }

        return null;
    }

    private function normalizeGadOfficeType(?string $officeType): string
    {
        $officeType = strtolower(trim((string) $officeType));

        if ($officeType === '' || str_contains($officeType, 'student')) {
            return 'student';
        }

        if (str_contains($officeType, 'faculty')) {
            return 'faculty';
        }

        if (str_contains($officeType, 'admin')) {
            return 'administrative';
        }

        if (
            str_contains($officeType, 'dependent') ||
            str_contains($officeType, 'guest') ||
            str_contains($officeType, 'alumni')
        ) {
            return 'dependent';
        }

        return 'student';
    }

    private function recordHasTruthyValue(object $record, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!property_exists($record, $column)) {
                continue;
            }

            $value = strtolower(trim((string) $record->{$column}));

            if (in_array($value, ['1', 'yes', 'true', 'y'], true)) {
                return true;
            }
        }

        return false;
    }

    private function drawAnnualDentalClearancePage(Fpdi $pdf, DocumentRequest $documentRequest): void
    {
        $patient = $documentRequest->patient;

        $patientName = strtoupper(trim((string) ($patient->name ?? '')));
        $patientName = $patientName !== '' ? $patientName : 'UNKNOWN PATIENT';

        $issuedDate = now()->format('F d, Y');

        $examinedDate = $documentRequest->approved_at
            ? Carbon::parse($documentRequest->approved_at)->format('F d, Y')
            : Carbon::parse($documentRequest->updated_at)->format('F d, Y');

        $dentistName = $this->getCurrentDentistNameForPdf();
        $licenseNumber = (string) config('app.dentist_license_no', '');

        $pdf->SetTextColor(0, 0, 0);


        $pdf->SetFont('Helvetica', '', 10.5);
        $this->drawCenteredPdfText($pdf, 496, 224, $issuedDate, 126, 12);

        $pdf->SetFont('Helvetica', 'B', 10.5);
        $this->drawCenteredPdfText($pdf, 384, 274, $patientName, 356, 12);

        $pdf->SetFont('Helvetica', '', 10.5);
        $this->drawCenteredPdfText($pdf, 492, 302, $examinedDate, 140, 12);

        if ($dentistName !== '') {
            $pdf->SetFont('Helvetica', 'B', 10.5);
            $this->drawCenteredPdfText($pdf, 448, 418, $dentistName, 134, 12);
        }

        if ($licenseNumber !== '') {
            $pdf->SetFont('Helvetica', '', 10.5);
            $this->drawCenteredPdfText($pdf, 492, 440, $licenseNumber, 129, 12);
        }
    }

    private function getCurrentDentistNameForPdf(): string
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return '';
        }

        $directName = trim((string) ($user->name ?? ''));

        if ($directName !== '') {
            return $directName;
        }

        $parts = array_filter([
            $user->first_name ?? null,
            $user->middle_name ?? null,
            $user->last_name ?? null,
        ]);

        return trim(implode(' ', $parts));
    }

    private function drawDentalClearancePage(Fpdi $pdf, DocumentRequest $documentRequest): void
    {
        $patient = $documentRequest->patient;

        $patientName = strtoupper(trim((string) ($patient->name ?? '')));
        $patientName = $patientName !== '' ? $patientName : 'UNKNOWN PATIENT';

        $issuedDate = now()->format('F d, Y');

        $examinedDate = $documentRequest->approved_at
            ? Carbon::parse($documentRequest->approved_at)->format('F d, Y')
            : Carbon::parse($documentRequest->updated_at)->format('F d, Y');

        $dentistName = $this->getCurrentDentistNameForPdf();
        $licenseNumber = (string) config('app.dentist_license_no', '');

        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('Helvetica', '', 10.5);
        $this->drawCenteredPdfText($pdf, 496, 224, $issuedDate, 126, 12);

        $pdf->SetFont('Helvetica', 'B', 10.5);
        $this->drawCenteredPdfText($pdf, 384, 274, $patientName, 356, 12);

        $pdf->SetFont('Helvetica', '', 10.5);
        $this->drawCenteredPdfText($pdf, 492, 302, $examinedDate, 140, 12);

        if ($dentistName !== '') {
            $pdf->SetFont('Helvetica', 'B', 10.5);
            $this->drawCenteredPdfText($pdf, 448, 418, $dentistName, 134, 12);
        }

        if ($licenseNumber !== '') {
            $pdf->SetFont('Helvetica', '', 10.5);
            $this->drawCenteredPdfText($pdf, 492, 440, $licenseNumber, 129, 12);
        }
    }

    private function drawDentalServicesRows(Fpdi $pdf, $records): void
    {
        $pdf->SetTextColor(0, 0, 0);

        $startY = 147.2;
        $rowHeight = 7.32;

        foreach ($records as $index => $appointment) {
            $y = $startY + ($index * $rowHeight);

            $patient = $appointment->patient;

            $date = $appointment->appointment_date
                ? Carbon::parse($appointment->appointment_date)->format('m/d/y')
                : '';

            $timeIn = $this->formatPdfTime($appointment->appointment_time ?? null);

            $patientName = trim((string) ($patient->name ?? ''));
            $patientName = $patientName !== '' ? $patientName : 'Unknown Patient';

            $programOrDept = trim((string) ($patient->course_code ?? ''));

            if ($programOrDept === '') {
                $programOrDept = trim((string) ($patient->course_name ?? ''));
            }

            if ($programOrDept !== '' && !empty($patient->year_level)) {
                $programOrDept .= ' - Y' . $patient->year_level;
            }

            if ($programOrDept !== '' && !empty($patient->section)) {
                $programOrDept .= ' / ' . $patient->section;
            }

            if ($programOrDept === '') {
                $programOrDept = trim((string) ($patient->faculty_code ?? ''));
            }

            if ($programOrDept === '') {
                $programOrDept = '—';
            }

            $age = '';

            if (!empty($patient->birthdate)) {
                $age = (string) Carbon::parse($patient->birthdate)->age;
            }

            $gender = strtolower(trim((string) ($patient->gender ?? '')));
            $email = trim((string) ($patient->email ?? ''));
            $contact = trim((string) ($patient->phone ?? ''));

            $isMale = str_starts_with($gender, 'm');
            $isFemale = str_starts_with($gender, 'f');
            $isSenior = (bool) ($patient->is_senior ?? false);
            $isPwd = (bool) ($patient->is_pwd ?? false);

            $isEmergency = false;
            $isNonEmergency = true;

            $timeProcessed = '';
            $processingTime = '';
            $signatureText = '';

            $pdf->SetFont('Helvetica', '', 4.2);

            $this->drawPdfCell($pdf, 84.0, $y, $date, 32, 5.5);
            $this->drawPdfCell($pdf, 121.6, $y, $timeIn, 36, 5.5);
            $this->drawPdfCell($pdf, 194.3, $y, $patientName, 100, 5.5, 'L');
            $this->drawPdfCell($pdf, 270.8, $y, $programOrDept, 44, 5.5);
            $this->drawPdfCell($pdf, 307.5, $y, $age, 20, 5.5);

            $pdf->SetFont('Helvetica', 'B', 4.4);

            $this->drawPdfCell($pdf, 330.7, $y, $isMale ? 'X' : '', 16, 5.5);
            $this->drawPdfCell($pdf, 352.3, $y, $isFemale ? 'X' : '', 18, 5.5);
            $this->drawPdfCell($pdf, 380.5, $y, $isSenior ? 'X' : '', 31, 5.5);
            $this->drawPdfCell($pdf, 407.7, $y, $isPwd ? 'X' : '', 17, 5.5);

            $pdf->SetFont('Helvetica', '', 4.0);

            $this->drawPdfCell($pdf, 447.2, $y, $email, 54, 5.5, 'L');
            $this->drawPdfCell($pdf, 498.6, $y, $contact, 40, 5.5);
            $this->drawPdfCell($pdf, 538.3, $y, $timeProcessed, 32, 5.5);
            $this->drawPdfCell($pdf, 573.5, $y, $processingTime, 32, 5.5);

            $pdf->SetFont('Helvetica', 'B', 4.4);

            $this->drawPdfCell($pdf, 609.4, $y, $isEmergency ? 'X' : '', 32, 5.5);
            $this->drawPdfCell($pdf, 649.5, $y, $isNonEmergency ? 'X' : '', 39, 5.5);
            $this->drawPdfCell($pdf, 706.4, $y, $signatureText, 65, 5.5);
        }
    }

    private function drawDailyTreatmentRecordRows(Fpdi $pdf, $records): void
    {
        $pdf->SetTextColor(0, 0, 0);

        $startY = 211.0;
        $rowHeight = 10.2;

        foreach ($records as $index => $appointment) {
            $y = $startY + ($index * $rowHeight);

            $patient = $appointment->patient;

            $requestedDateTime = '';

            if (!empty($appointment->appointment_date)) {
                $requestedDateTime = Carbon::parse($appointment->appointment_date)->format('m/d/y');
            }

            if (!empty($appointment->appointment_time)) {
                $requestedDateTime .= "\n" . $this->formatPdfTime($appointment->appointment_time);
            }

            $patientName = trim((string) ($patient->name ?? 'Unknown Patient'));

            $email = trim((string) ($patient->email ?? ''));
            $contact = trim((string) ($patient->phone ?? ''));

            $emailContact = $email;

            if ($contact !== '') {
                $emailContact .= $emailContact !== '' ? "\n" . $contact : $contact;
            }

            $office = trim((string) ($patient->course_code ?? ''));

            if ($office === '') {
                $office = trim((string) ($patient->course_name ?? ''));
            }

            if ($office === '') {
                $office = trim((string) ($patient->faculty_code ?? ''));
            }

            if ($office === '') {
                $office = '—';
            }

            $gender = trim((string) ($patient->gender ?? ''));
            $treatmentDone = trim((string) ($appointment->service_type ?? ''));

            if ($treatmentDone === '') {
                $treatmentDone = 'Dental Service';
            }

            $processedDateTime = $requestedDateTime;
            $minutesProcessed = '';
            $signatureText = '';

            $pdf->SetFont('Helvetica', '', 4.6);

            $this->drawPdfMultiCell($pdf, 118, $y, $requestedDateTime, 58, 10, 'C');
            $this->drawPdfMultiCell($pdf, 199, $y, $patientName, 92, 10, 'L');
            $this->drawPdfMultiCell($pdf, 299, $y, $emailContact, 94, 10, 'L');
            $this->drawPdfMultiCell($pdf, 370, $y, $office, 46, 10, 'C');
            $this->drawPdfMultiCell($pdf, 411, $y, $gender, 36, 10, 'C');
            $this->drawPdfMultiCell($pdf, 469, $y, $treatmentDone, 70, 10, 'L');
            $this->drawPdfMultiCell($pdf, 539, $y, $processedDateTime, 62, 10, 'C');
            $this->drawPdfMultiCell($pdf, 613, $y, $minutesProcessed, 76, 10, 'C');
            $this->drawPdfMultiCell($pdf, 704, $y, $signatureText, 76, 10, 'C');
        }
    }

    private function drawPdfCell(
        Fpdi $pdf,
        float $centerX,
        float $centerY,
        string $text,
        float $width,
        float $height,
        string $align = 'C'
    ): void {
        $text = $this->fitPdfText($pdf, $text, $width);

        $pdf->SetXY($centerX - ($width / 2), $centerY - ($height / 2));
        $pdf->Cell($width, $height, $text, 0, 0, $align);
    }

    private function drawPdfMultiCell(
        Fpdi $pdf,
        float $centerX,
        float $centerY,
        string $text,
        float $width,
        float $height,
        string $align = 'C'
    ): void {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));

        if (!$lines || count($lines) === 0) {
            return;
        }

        $lineHeight = 3.6;
        $totalHeight = count($lines) * $lineHeight;
        $startY = $centerY - ($totalHeight / 2);

        foreach ($lines as $lineIndex => $line) {
            $line = $this->fitPdfText($pdf, trim($line), $width);

            $pdf->SetXY($centerX - ($width / 2), $startY + ($lineIndex * $lineHeight));
            $pdf->Cell($width, $lineHeight, $line, 0, 0, $align);
        }
    }

    private function fitPdfText(Fpdi $pdf, string $text, float $maxWidth): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if ($pdf->GetStringWidth($text) <= $maxWidth) {
            return $text;
        }

        while ($text !== '' && $pdf->GetStringWidth($text . '...') > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }

        return trim($text) . '...';
    }

    private function formatPdfTime($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('h:i A');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function drawDentalSuppliesInventoryRows(Fpdi $pdf, $items, Carbon $from, Carbon $to): void
    {
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(310, 99, 180, 15, 'F');

        $pdf->SetFont('Helvetica', 'B', 8.5);
        $this->drawCenteredPdfText(
            $pdf,
            396,
            107,
            $this->formatReportPeriodLabel($from, $to, 'As of'),
            220,
            10
        );

        $startY = 194.0;
        $rowHeight = 14.77;

        foreach ($items as $index => $item) {
            $y = $startY + ($index * $rowHeight);

            $dateReceived = $item->date_received
                ? Carbon::parse($item->date_received)->format('m/d/y')
                : '';

            $stockNo = trim((string) ($item->stock_no ?? ''));
            $name = trim((string) ($item->name ?? ''));
            $unit = trim((string) ($item->unit ?? ''));
            $quantity = (string) ((int) ($item->qty ?? 0));
            $consumed = (string) ((int) ($item->used ?? 0));
            $balance = (string) ((int) (($item->qty ?? 0) - ($item->used ?? 0)));

            $pdf->SetFont('Helvetica', '', 7);

            $this->drawPdfCell($pdf, 84.8, $y, $dateReceived, 62, 10);
            $this->drawPdfCell($pdf, 158.7, $y, $stockNo, 70, 10);
            $this->drawPdfCell($pdf, 330.6, $y, $name, 250, 10, 'L');
            $this->drawPdfCell($pdf, 498.3, $y, $unit, 62, 10);
            $this->drawPdfCell($pdf, 567.9, $y, $quantity, 62, 10);
            $this->drawPdfCell($pdf, 637.5, $y, $consumed, 62, 10);
            $this->drawPdfCell($pdf, 707.1, $y, $balance, 62, 10);
        }
    }

    private function drawMedicineInventoryRows(Fpdi $pdf, $items, Carbon $from, Carbon $to): void
    {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(310, 99, 180, 15, 'F');

        $pdf->SetFont('Helvetica', 'B', 8.5);
        $this->drawCenteredPdfText(
            $pdf,
            396,
            107,
            $this->formatReportPeriodLabel($from, $to, 'As of'),
            220,
            10
        );

        $startY = 194.5;
        $rowHeight = 14.77;

        foreach ($items as $index => $item) {
            $y = $startY + ($index * $rowHeight);

            $dateReceived = $item->date_received
                ? Carbon::parse($item->date_received)->format('m/d/y')
                : '';

            $stockNo = trim((string) ($item->stock_no ?? ''));
            $name = trim((string) ($item->name ?? ''));
            $unit = trim((string) ($item->unit ?? ''));
            $quantity = (string) ((int) ($item->qty ?? 0));
            $consumed = (string) ((int) ($item->used ?? 0));
            $balance = (string) ((int) (($item->qty ?? 0) - ($item->used ?? 0)));

            $pdf->SetFont('Helvetica', '', 7);

            $this->drawPdfCell($pdf, 84.8, $y, $dateReceived, 62, 10);
            $this->drawPdfCell($pdf, 158.7, $y, $stockNo, 70, 10);
            $this->drawPdfCell($pdf, 330.6, $y, $name, 250, 10, 'L');
            $this->drawPdfCell($pdf, 498.3, $y, $unit, 62, 10);
            $this->drawPdfCell($pdf, 567.9, $y, $quantity, 62, 10);
            $this->drawPdfCell($pdf, 637.5, $y, $consumed, 62, 10);
            $this->drawPdfCell($pdf, 707.1, $y, $balance, 62, 10);
        }
    }

    private function formatReportPeriodLabel(Carbon $from, Carbon $to, string $prefix = 'As of'): string
    {
        $prefix = trim($prefix);

        if ($from->isSameMonth($to) && $from->isSameYear($to)) {
            return $prefix . ' ' . strtoupper($from->format('F Y'));
        }

        if ($from->isSameYear($to)) {
            return $prefix . ' ' . strtoupper($from->format('F')) . ' TO ' . strtoupper($to->format('F Y'));
        }

        return $prefix . ' ' . strtoupper($from->format('F Y')) . ' TO ' . strtoupper($to->format('F Y'));
    }
    private function drawDentalServicesTemplateDate(Fpdi $pdf): void
    {


        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(662, 42, 88, 34, 'F');

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(668.5, 46.5, 72.5, 22.5, 'D');

        $pdf->SetFont('Helvetica', '', 5.2);

        $pdf->SetXY(671, 48.6);
        $pdf->Cell(68, 5, 'PUP-TDRP-MEDS-', 0, 0, 'L');

        $pdf->SetXY(671, 54.2);
        $pdf->Cell(68, 5, 'Rev.1', 0, 0, 'L');

        $pdf->SetXY(671, 59.8);
        $pdf->Cell(68, 5, now()->format('F Y'), 0, 0, 'L');
    }

    private function drawDentalHealthOdontogram(Fpdi $pdf, array $odontogramData): void
    {
        if (empty($odontogramData)) {
            return;
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', 'B', 5.2);

        $toothMap = [
            55 => ['x' => 194.4, 'y' => 266.0],
            54 => ['x' => 218.2, 'y' => 266.0],
            53 => ['x' => 242.4, 'y' => 266.0],
            52 => ['x' => 266.7, 'y' => 266.0],
            51 => ['x' => 290.9, 'y' => 266.0],

            61 => ['x' => 321.6, 'y' => 266.0],
            62 => ['x' => 345.4, 'y' => 266.0],
            63 => ['x' => 369.6, 'y' => 266.0],
            64 => ['x' => 393.9, 'y' => 266.0],
            65 => ['x' => 418.1, 'y' => 266.0],

            18 => ['x' => 121.7, 'y' => 322.0],
            17 => ['x' => 145.4, 'y' => 322.0],
            16 => ['x' => 169.7, 'y' => 322.0],
            15 => ['x' => 193.9, 'y' => 322.0],
            14 => ['x' => 218.2, 'y' => 322.0],
            13 => ['x' => 242.4, 'y' => 322.0],
            12 => ['x' => 266.7, 'y' => 322.0],
            11 => ['x' => 290.9, 'y' => 322.0],

            21 => ['x' => 319.5, 'y' => 317.2],
            22 => ['x' => 345.4, 'y' => 322.0],
            23 => ['x' => 369.6, 'y' => 322.0],
            24 => ['x' => 393.9, 'y' => 322.0],
            25 => ['x' => 418.1, 'y' => 322.0],
            26 => ['x' => 442.4, 'y' => 322.0],
            27 => ['x' => 466.6, 'y' => 322.0],
            28 => ['x' => 490.8, 'y' => 322.0],

            48 => ['x' => 121.7, 'y' => 392.0],
            47 => ['x' => 145.4, 'y' => 392.0],
            46 => ['x' => 169.7, 'y' => 392.0],
            45 => ['x' => 193.9, 'y' => 392.0],
            44 => ['x' => 218.2, 'y' => 392.0],
            43 => ['x' => 242.4, 'y' => 392.0],
            42 => ['x' => 266.7, 'y' => 392.0],
            41 => ['x' => 290.9, 'y' => 392.0],

            31 => ['x' => 321.6, 'y' => 392.0],
            32 => ['x' => 345.4, 'y' => 392.0],
            33 => ['x' => 369.6, 'y' => 392.0],
            34 => ['x' => 393.9, 'y' => 392.0],
            35 => ['x' => 418.1, 'y' => 392.0],
            36 => ['x' => 442.4, 'y' => 392.0],
            37 => ['x' => 466.6, 'y' => 392.0],
            38 => ['x' => 490.8, 'y' => 392.0],

            85 => ['x' => 194.4, 'y' => 449.8],
            84 => ['x' => 218.2, 'y' => 449.8],
            83 => ['x' => 242.4, 'y' => 449.8],
            82 => ['x' => 266.7, 'y' => 449.8],
            81 => ['x' => 290.9, 'y' => 449.8],

            71 => ['x' => 321.6, 'y' => 449.8],
            72 => ['x' => 345.4, 'y' => 449.8],
            73 => ['x' => 369.6, 'y' => 449.8],
            74 => ['x' => 393.9, 'y' => 449.8],
            75 => ['x' => 418.1, 'y' => 449.8],
        ];

        foreach ($odontogramData as $item) {
            $tooth = (int) ($item['tooth'] ?? 0);

            if (!$tooth || !isset($toothMap[$tooth])) {
                continue;
            }

            $code = trim((string) (
                $item['status']['code']
                ?? $item['threeD']['code']
                ?? ''
            ));

            if ($code === '') {
                continue;
            }

            $pos = $toothMap[$tooth];

            $this->drawPdfCell(
                $pdf,
                $pos['x'],
                $pos['y'],
                $code,
                18,
                5,
                'C'
            );
        }
    }
}
