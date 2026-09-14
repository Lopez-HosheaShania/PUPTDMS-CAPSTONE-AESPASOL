<?php

namespace App\Http\Controllers\Dentist;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\DentalHistory;
use App\Models\DentalHistoryAnswer;
use App\Models\DentalHistoryConcern;
use App\Models\DentalHistoryConditionDate;
use App\Models\DocumentRequest;
use App\Models\DocumentTemplate;
use App\Models\Inventory;
use App\Models\MedicalHistory;
use App\Models\MedicalHistoryAnswer;
use App\Models\MedicalHistoryDiseaseAnswer;
use App\Models\Patient;
use App\Models\PatientOdontogram;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use setasign\Fpdi\Fpdi;
use App\Models\ServiceType;

class DentistReportController extends Controller
{
    public function buildApprovedDocumentRequestPdfResponse(DocumentRequest $documentRequest)
    {
        $payload = $this->generateApprovedDocumentRequestPdfPayload($documentRequest);

        if ($payload === null) {
            abort(422, 'This document request does not have a PDF template yet.');
        }

        return response($payload['content'], 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $payload['file_name'] . '"')
            ->header('Content-Length', (string) strlen($payload['content']))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Approval-Message', 'Document request approved successfully.');
    }

    public function index()
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $now = Carbon::now();
        $thisMonth = $now->month;
        $thisYear = $now->year;
        $today = $now->toDateString();
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

        $topServices = Appointment::query()
            ->join(
                'service_types',
                'appointments.service_type_id',
                '=',
                'service_types.id'
            )
            ->whereYear('appointments.appointment_date', $thisYear)
            ->whereMonth('appointments.appointment_date', $thisMonth)
            ->select(
                'service_types.name as name',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(
                'service_types.id',
                'service_types.name'
            )
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $inventoryItems = DB::table('inventory_items')
            ->select('category', 'name', 'qty', 'used')->orderBy('name')->get();

        $medicineItems = $inventoryItems->where('category', 'Medicine')->values();
        $suppliesItems = $inventoryItems->where('category', 'Supplies')->values();

        $lowStockRows = DB::table('inventory_items')
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
            'Dentist viewed reports dashboard'
        );

        return view('shared.reports', [
            'layoutRole' => 'dentist',
            'pageTitle' => 'Reports & Analytics',
            'pageShellClass' => 'app-page-shell dentist-report-page',

            'isAdminView' => false,
            'isDentistView' => true,

            'reportStats' => [
                'patients_this_month' => $patientsThisMonth,
                'patients_delta' => $patientsDelta,

                'appointments_today' => $appointmentsToday,
                'appointments_delta' => $appointmentsDelta,

                'cases_this_month' => $casesThisMonth,
                'cases_delta' => $casesDelta,
                'completed_appointments' => $casesThisMonth,

                'cancellation_rate' => $cancellationRate,
                'average_patients_per_day' => $avgPatientsPerDay,

                'returning_patients' => $returningPatients,
                'new_patients' => $newPatients,

                'low_stock_items' => $lowStockItems,
                'total_appointments_this_month' => $totalAppointmentsThisMonth,
                'cancelled_appointments' => $cancelledAppointments,
            ],

            'reportCharts' => [
                'gad' => [
                    'labels' => $gadLabels,
                    'female' => $gadFemale,
                    'male' => $gadMale,
                ],

                'weekly' => [
                    'labels' => $weekLabels,
                    'datasets' => $weeklyDatasets,
                ],
            ],

            'reportInventory' => [
                'medicine_items' => $medicineItems,
                'supplies_items' => $suppliesItems,
                'low_stock_medicine' => $lowStockMedicine,
                'low_stock_supplies' => $lowStockSupplies,
            ],

            'topServices' => $topServices,
            'periodOptions' => $periodOptions,
            'documentTemplates' => $documentTemplates,
            'customReportTemplates' => $customReportTemplates,
        ]);
    }

    public function printTemplate(DocumentTemplate $template)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        abort_unless($template->status === 'active', 404);

        $templatePath = $this->getPrintableTemplatePdfPath($template);

        if (! $templatePath || ! file_exists($templatePath)) {
            abort(404, 'PDF template file was not found.');
        }

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($templatePath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templatePage = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templatePage);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templatePage, 0, 0, $size['width'], $size['height'], true);
        }

        AuditLogger::log(
            'view',
            'dentist_reports',
            "Dentist opened exact PDF template: {$template->name}"
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $template->name ?: 'document-template');

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $safeName . '.pdf"');
    }

    private function getPrintableTemplatePdfPath(DocumentTemplate $template): ?string
    {
        $documentType = strtolower(trim((string) $template->document_type));
        $code = strtoupper(trim((string) $template->code));

        $pathsByType = [
            'daily_treatment_record' => 'daily-treatment-record-template.pdf',
            'dental_services' => 'dental-services-template.pdf',
            'dental_health_record' => 'dental-health-record-template.pdf',
            'annual_dental_clearance' => 'annual-dental-clearance-template.pdf',
            'dental_clearance' => 'dental-clearance-template.pdf',
            'gad_report' => 'gad-accomplishment-template.pdf',
            'dental_supplies_inventory' => 'dental-supplies-inventory-template.pdf',
            'medicine_inventory' => 'medicine-inventory-template.pdf',
            'monthly_report' => 'monthly-report-template.pdf',
            'dental_cases' => 'dental-cases-template.pdf',
        ];

        $pathsByCode = [
            'DTR-DEFAULT' => 'daily-treatment-record-template.pdf',
            'DTR-FACULTY' => 'dental-treatment-record-faculty.pdf',
            'DTR-ALUMNI' => 'daily-treatment-record-alumni-dependent.pdf',
            'DSRV-DEFAULT' => 'dental-services-template.pdf',
            'DHREC-DEFAULT' => 'dental-health-record-template.pdf',
            'ADCL-DEFAULT' => 'annual-dental-clearance-template.pdf',
            'DCLR-DEFAULT' => 'dental-clearance-template.pdf',
            'GADR-DEFAULT' => 'gad-accomplishment-template.pdf',
            'DINV-DEFAULT' => 'dental-supplies-inventory-template.pdf',
            'MINV-DEFAULT' => 'medicine-inventory-template.pdf',
            'MONTHLY-REPORT' => 'monthly-report-template.pdf',
            'DCASE-DEFAULT' => 'dental-cases-template.pdf',
        ];

        $fileName = $pathsByCode[$code] ?? $pathsByType[$documentType] ?? null;

        if (! $fileName) {
            return null;
        }

        return storage_path('app/report-templates/' . $fileName);
    }

    public function gadData(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $parsed = Carbon::createFromFormat('M Y', $request->input('period'))
            ?? Carbon::createFromFormat('F Y', $request->input('period'));

        [$labels, $female, $male] = $this->buildGadData($parsed->year, $parsed->month);

        $hasData = array_sum($female) + array_sum($male) > 0;

        AuditLogger::log(
            'view',
            'dentist_reports',
            'Dentist viewed GAD chart data'
        );

        return response()->json([
            'labels' => $labels,
            'female' => $female,
            'male' => $male,
            'empty' => ! $hasData,
        ]);
    }

    public function weeklyData(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $parsed = Carbon::createFromFormat('M Y', $request->input('period'))
            ?? Carbon::createFromFormat('F Y', $request->input('period'));

        [$weekLabels, $datasets] = $this->buildWeeklyData($parsed->year, $parsed->month);

        AuditLogger::log(
            'view',
            'dentist_reports',
            'Dentist viewed weekly report data'
        );

        return response()->json([
            'labels' => $weekLabels,
            'datasets' => $datasets,
            'empty' => empty($datasets),
        ]);
    }

    public function downloadGadReport(Request $request)
    {
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/gad-accomplishment-template.pdf');

        if (! file_exists($templatePath)) {
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
            $pdf->AddPage(
                $size['orientation'],
                [
                    $size['width'],
                    $size['height'],
                ]
            );

            $pdf->useTemplate(
                $template,
                0,
                0,
                $size['width'],
                $size['height'],
                true
            );

            $this->drawGadPdfPage(
                $pdf,
                $counts,
                $from,
                $to
            );
        }
        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded GAD Accomplishment Report PDF'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        $pdfContent = $pdf->Output('S');

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Content-Length', (string) strlen($pdfContent))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function downloadAnnualDentalClearance(Request $request)
    {
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/annual-dental-clearance-template.pdf');

        if (! file_exists($templatePath)) {
            return response()->json([
                'message' => 'Annual Dental Clearance PDF template was not found.',
            ], 404);
        }

        $approvedRequests = DocumentRequest::withStateColumns()->with(['patient', 'approvedBy'])->withReviewColumns()
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

        $pdfContent = $pdf->Output('S');

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Content-Length', (string) strlen($pdfContent))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function downloadDentalClearance(Request $request)
    {
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/dental-clearance-template.pdf');

        if (! file_exists($templatePath)) {
            return response()->json([
                'message' => 'Dental Clearance PDF template was not found.',
            ], 404);
        }

        $approvedRequests = DocumentRequest::withStateColumns()->with(['patient', 'approvedBy'])->withReviewColumns()
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
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = $this->getPrintableTemplatePdfPath($templateRecord)
            ?? storage_path('app/report-templates/dental-services-template.pdf');

        if (! file_exists($templatePath)) {
            return response()->json([
                'message' => 'Dental Services PDF template was not found.',
            ], 404);
        }

        $records = Appointment::with([
            'patient.medicalHistory',
            'procedure',
        ])
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->filter(function (Appointment $appointment) use ($templateRecord) {
                return $this->matchesDentalServicesTemplateAudience(
                    $appointment,
                    $templateRecord
                );
            })
            ->values();

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

        $rowsPerPage = 16;

        $recordChunks =
            $this->reportPages(
                $records,
                $rowsPerPage
            );
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

    private function matchesDentalServicesTemplateAudience(
        Appointment $appointment,
        DocumentTemplate $template
    ): bool {
        $audience =
            $this->getDentalServicesTemplateAudience(
                $template
            );

        if ($audience === 'all') {
            return true;
        }

        $patient =
            $appointment->patient;

        $patientAudience =
            $this->getDentalServicesPatientAudience(
                $patient
            );

        if ($audience === 'faculty') {
            return $patientAudience === 'faculty';
        }

        if ($audience === 'student') {
            return $patientAudience === 'student';
        }

        return true;
    }

    private function getDentalServicesTemplateAudience(
        DocumentTemplate $template
    ): string {
        $haystack = strtolower(trim(implode(' ', array_filter([
            $template->name,
            $template->code,
            $template->notes,
        ]))));

        if ($haystack === '') {
            return 'all';
        }

        if (
            str_contains($haystack, 'faculty') ||
            str_contains($haystack, 'administrative')
        ) {
            return 'faculty';
        }

        if (
            str_contains($haystack, 'student') ||
            str_contains($haystack, 'students')
        ) {
            return 'student';
        }

        return 'all';
    }

    private function getDentalServicesPatientAudience($patient): string
    {
        if (! $patient) {
            return 'unknown';
        }

        return $this->categorizePatientForReports($patient);
    }

    public function downloadMedicineInventoryReport(Request $request)
    {
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/medicine-inventory-template.pdf');

        if (! file_exists($templatePath)) {
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

        $itemChunks =
            $this->reportPages(
                $items,
                $rowsPerPage
            );
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
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/dental-supplies-inventory-template.pdf');

        if (! file_exists($templatePath)) {
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

        $itemChunks =
            $this->reportPages(
                $items,
                $rowsPerPage
            );
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
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = $this->getPrintableTemplatePdfPath($templateRecord);

        if (! $templatePath || ! file_exists($templatePath)) {
            return response()->json([
                'message' => 'Daily Treatment Record PDF template was not found.',
            ], 404);
        }
        $records = Appointment::with([
            'patient.medicalHistory',
            'procedure',
        ])
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->filter(function (Appointment $appointment) use ($templateRecord) {
                return $this->matchesDailyTreatmentTemplateAudience(
                    $appointment,
                    $templateRecord
                );
            })
            ->values();

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

        $recordChunks =
            $this->reportPages(
                $records,
                $rowsPerPage
            );
        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            foreach ($recordChunks as $chunk) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);

                $this->drawDailyTreatmentRecordRows(
                    $pdf,
                    $chunk->values(),
                    strtoupper(trim((string) ($templateRecord->code ?? '')))
                );
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

    private function matchesDailyTreatmentTemplateAudience(
        Appointment $appointment,
        DocumentTemplate $template
    ): bool {
        $audience =
            $this->getDailyTreatmentTemplateAudience(
                $template
            );

        if ($audience === 'all') {
            return true;
        }

        $patientAudience =
            $this->getDailyTreatmentPatientAudience(
                $appointment->patient
            );

        if ($audience === 'faculty_admin') {
            return in_array($patientAudience, ['faculty', 'administrative'], true);
        }

        if ($audience === 'alumni_dependent') {
            return $patientAudience === 'alumni_dependent';
        }

        if ($audience === 'student') {
            return $patientAudience === 'student';
        }

        return true;
    }

    private function getDailyTreatmentTemplateAudience(
        DocumentTemplate $template
    ): string {
        $code = strtoupper(trim((string) ($template->code ?? '')));

        if ($code === 'DTR-FACULTY') {
            return 'faculty_admin';
        }

        if ($code === 'DTR-ALUMNI') {
            return 'alumni_dependent';
        }

        if ($code === 'DTR-DEFAULT') {
            return 'student';
        }

        $haystack = strtolower(trim(implode(' ', array_filter([
            $template->name,
            $template->notes,
        ]))));

        if (
            str_contains($haystack, 'alumni') ||
            str_contains($haystack, 'dependent')
        ) {
            return 'alumni_dependent';
        }

        if (
            str_contains($haystack, 'faculty') ||
            str_contains($haystack, 'administrative')
        ) {
            return 'faculty_admin';
        }

        if (
            str_contains($haystack, 'student') ||
            str_contains($haystack, 'students')
        ) {
            return 'student';
        }

        return 'all';
    }

    private function getDailyTreatmentPatientAudience($patient): string
    {
        if (! $patient) {
            return 'unknown';
        }

        return match ($this->resolveDailyTreatmentPatientClassification($patient)) {
            'administrative' => 'administrative',
            'faculty' => 'faculty',
            'alumni', 'dependent', 'dependent_alumni' => 'alumni_dependent',
            default => 'student',
        };
    }

    public function downloadDentalCasesReport(Request $request)
    {
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/dental-cases-template.pdf');

        if (! file_exists($templatePath)) {
            return response()->json([
                'message' => 'Dental Cases PDF template was not found.',
            ], 404);
        }

        $appointments = Appointment::with(['patient', 'procedure'])
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

        $casePages = $this->dentalCasesPages(
            $caseGroups,
            3
        );

        $copies = (int) $validated['quantity'];

        for ($copy = 1; $copy <= $copies; $copy++) {
            foreach ($casePages as $pageGroups) {
                $pdf->AddPage(
                    $size['orientation'],
                    [
                        $size['width'],
                        $size['height'],
                    ]
                );

                $pdf->useTemplate(
                    $template,
                    0,
                    0,
                    $size['width'],
                    $size['height'],
                    true
                );

                $this->drawDentalCasesPage(
                    $pdf,
                    $pageGroups,
                    $from,
                    $to
                );
            }
        }

        AuditLogger::log(
            'download',
            'dentist_reports',
            'Dentist downloaded Dental Cases PDF for ' . $appointments->count() . ' completed appointment(s).'
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);
        $fileName = $safeName . '.pdf';

        $pdfContent = $pdf->Output('S');

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Content-Length', (string) strlen($pdfContent))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function downloadMonthlyReport(Request $request)
    {
        if (! Auth::check()) {
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

        $to = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $from->copy()->endOfDay();

        $templatePath = storage_path('app/report-templates/monthly-report-template.pdf');

        if (! file_exists($templatePath)) {
            return response()->json([
                'message' => 'Monthly Report PDF template was not found.',
            ], 404);
        }

        $appointments = Appointment::with(['patient', 'procedure'])
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
            $validated = $request->validate([
                'report_name' => ['nullable', 'string', 'max:100'],
                'document_template_id' => ['nullable', 'integer', 'exists:document_templates,id'],
                'document_request_id' => ['nullable', 'integer', 'exists:document_requests,id'],
                'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
                'date_from' => ['nullable', 'date', 'before_or_equal:today'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            ]);

            if (! empty($validated['document_template_id'])) {
                $templateRecord = DocumentTemplate::query()
                    ->whereKey($validated['document_template_id'])
                    ->where('status', 'active')
                    ->firstOrFail();

                if ($templateRecord->document_type !== 'dental_health_record') {
                    abort(422, 'This download route is only for the Dental Health Record.');
                }
            }

            $templatePath = storage_path('app/report-templates/dental-health-record-template.pdf');

            if (! file_exists($templatePath)) {
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

            $isPatientSpecific = false;

            if (! empty($validated['document_request_id'])) {
                $documentRequest = DocumentRequest::withStateColumns()->with('patient')
                    ->findOrFail($validated['document_request_id']);

                $requestType = strtolower(str_replace([' ', '-'], '_', trim((string) $documentRequest->document_type)));
                $approvedStatuses = ['approved', 'ready', 'ready-for-pickup', 'ready-for-release', 'released'];

                abort_unless($requestType === 'dental_health_record', 422, 'The selected request is not for a Dental Health Record.');
                abort_unless(in_array(strtolower((string) $documentRequest->status), $approvedStatuses, true), 422, 'The Dental Health Record request must be approved first.');
                abort_unless($documentRequest->patient, 422, 'The selected document request has no patient record.');

                $patients = collect([$documentRequest->patient]);
                $isPatientSpecific = true;
            } elseif (! empty($validated['patient_id'])) {
                $patients = collect([Patient::findOrFail($validated['patient_id'])]);
                $isPatientSpecific = true;
            } else {
                $patients = Appointment::with('patient')
                    ->where('status', 'completed')
                    ->whereDate('appointment_date', '>=', $from->toDateString())
                    ->whereDate('appointment_date', '<=', $to->toDateString())
                    ->orderBy('appointment_date')
                    ->orderBy('appointment_time')
                    ->get()
                    ->pluck('patient')
                    ->filter()
                    ->unique('id')
                    ->values();
            }

            if ($patients->isEmpty()) {
                return response()->json([
                    'message' => 'No patients with completed appointments were found for the selected Dental Health Record date range.',
                ], 422);
            }

            $pdf = new Fpdi('P', 'pt');
            $pdf->SetAutoPageBreak(false);
            $templatePageCount = $pdf->setSourceFile($templatePath);

            if ($templatePageCount !== 2) {
                return response()->json([
                    'message' => 'The Dental Health Record template must contain exactly two pages.',
                ], 422);
            }

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

                $patientTreatments = Appointment::with(['dentist', 'procedure'])
                    ->where('patient_id', $patient->id)
                    ->where('status', 'completed')
                    ->when(! $isPatientSpecific, function ($query) use ($from, $to) {
                        $query->whereDate('appointment_date', '>=', $from->toDateString())
                            ->whereDate('appointment_date', '<=', $to->toDateString());
                    })
                    ->orderBy('appointment_date')
                    ->orderBy('appointment_time')
                    ->get();
                $procedureDiagnosisByAppointment = AppointmentProcedure::query()
                    ->whereIn('appointment_id', $patientTreatments->pluck('id')->filter()->all())
                    ->get()
                    ->groupBy('appointment_id')
                    ->map(function ($procedures) {
                        return trim((string) ($procedures->last()->diagnosis ?? ''));
                    });

                $patientAppointmentIds = $patientTreatments->pluck('id')->filter()->values();

                $appointmentProcedure = AppointmentProcedure::query()
                    ->forPatient($patient->id)
                    ->when(
                        $patientAppointmentIds->isNotEmpty(),
                        fn($q) => $q->whereIn('appointment_id', $patientAppointmentIds->all())
                    )
                    ->latest('id')
                    ->first();

                $savedOdontogram = PatientOdontogram::query()
                    ->where('patient_id', $patient->id)
                    ->latest('updated_at')
                    ->first();

                $odontogramData = $savedOdontogram?->odontogram_data
                    ?: ($appointmentProcedure?->odontogram_data ?? []);

                $this->addDentalHealthTemplatePage($pdf, 1);
                $this->drawDentalHealthRecordPageOne(
                    $pdf,
                    $patient,
                    $dentalHistory,
                    $dentalAnswers,
                    $odontogramData
                );

                $this->addDentalHealthTemplatePage($pdf, 2);
                $this->drawDentalHealthRecordPageTwo(
                    $pdf,
                    $dentalAnswers,
                    $dentalConcern,
                    $dentalDates,
                    $medicalHistory,
                    $medicalAnswers,
                    $diseaseAnswers,
                    $patientTreatments,
                    $procedureDiagnosisByAppointment,
                    0,
                    true
                );

                $maxRowsPerPage = 10;

                for ($offset = $maxRowsPerPage; $offset < $patientTreatments->count(); $offset += $maxRowsPerPage) {
                    $this->addDentalHealthTemplatePage($pdf, 2);
                    $this->drawDentalHealthRecordPageTwo(
                        $pdf,
                        $dentalAnswers,
                        $dentalConcern,
                        $dentalDates,
                        $medicalHistory,
                        $medicalAnswers,
                        $diseaseAnswers,
                        $patientTreatments,
                        $procedureDiagnosisByAppointment,
                        $offset,
                        false
                    );
                }
            }

            AuditLogger::log(
                'download',
                'dentist_reports',
                'Dentist downloaded Dental Health Record PDF for ' . $patients->count() . ' patient(s).'
            );

            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($validated['report_name'] ?? ''));
            $fileName = $safeName !== ''
                ? $safeName . '.pdf'
                : 'dental-health-record-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.pdf';

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

            if (! $patient) {
                continue;
            }

            $groupKey = $this->classifyDentalCasesPatient($patient);

            $diagnosis = $this->getDentalCaseDiagnosisLabel(
                $appointment,
                $procedureDiagnosisByAppointment
            );

            if ($diagnosis === null) {
                continue;
            }

            if (! isset($groups[$groupKey][$diagnosis])) {
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

            if (! $patient) {
                continue;
            }

            $rowKey = $this->classifyMonthlyReportPatient($patient);
            $data[$rowKey]['patient_ids'][$patient->id] = true;

            $columnKey = $this->classifyMonthlyReportService(
                $this->getMonthlyReportServiceLabel($appointment)
            );

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
        return $this->categorizePatientForReports($patient);
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

    private function dentalCasesPages(
        array $caseGroups,
        int $rowsPerSection = 3
    ): array {
        $largestSectionCount = max(
            count($caseGroups['students'] ?? []),
            count($caseGroups['faculty'] ?? []),
            count($caseGroups['administrative'] ?? []),
            count($caseGroups['dependents'] ?? [])
        );

        $pageCount = max(
            1,
            (int) ceil(
                $largestSectionCount /
                    $rowsPerSection
            )
        );

        $pages = [];

        for (
            $page = 0;
            $page < $pageCount;
            $page++
        ) {
            $offset =
                $page *
                $rowsPerSection;

            $pages[] = [
                'students' =>
                array_slice(
                    $caseGroups['students'] ?? [],
                    $offset,
                    $rowsPerSection
                ),

                'faculty' =>
                array_slice(
                    $caseGroups['faculty'] ?? [],
                    $offset,
                    $rowsPerSection
                ),

                'administrative' =>
                array_slice(
                    $caseGroups['administrative'] ?? [],
                    $offset,
                    $rowsPerSection
                ),

                'dependents' =>
                array_slice(
                    $caseGroups['dependents'] ?? [],
                    $offset,
                    $rowsPerSection
                ),
            ];
        }

        return $pages;
    }

    private function getDentalCaseDiagnosisLabel(Appointment $appointment, $procedureDiagnosisByAppointment): ?string
    {
        $diagnosis = '';

        if ($procedureDiagnosisByAppointment instanceof \Illuminate\Support\Collection) {
            $diagnosis = trim((string) ($procedureDiagnosisByAppointment->get($appointment->id) ?? ''));
        } elseif (is_array($procedureDiagnosisByAppointment)) {
            $diagnosis = trim((string) ($procedureDiagnosisByAppointment[$appointment->id] ?? ''));
        }

        if ($diagnosis === '' && filled($appointment->procedure?->diagnosis)) {
            $diagnosis = trim((string) $appointment->procedure->diagnosis);
        }

        if ($diagnosis === '') {
            return null;
        }

        $diagnosis = $this->normalizeReportServiceLabel($diagnosis, '');
        if ($this->isDentalCaseProcedureLabel($diagnosis)) {
            return null;
        }

        return $diagnosis;
    }

    private function isDentalCaseProcedureLabel(string $diagnosis): bool
    {
        static $procedureLabels = null;

        if ($procedureLabels === null) {
            $procedureLabels = ServiceType::query()
                ->pluck('name')
                ->map(fn($name) => $this->normalizeDentalCaseLabel($name))
                ->filter()
                ->all();
        }

        return in_array(
            $this->normalizeDentalCaseLabel($diagnosis),
            $procedureLabels,
            true
        );
    }

    private function normalizeDentalCaseLabel(?string $value): string
    {
        return preg_replace(
            '/[^a-z0-9]+/',
            '',
            strtolower(trim((string) $value))
        ) ?? '';
    }

    private function getMonthlyReportServiceLabel(Appointment $appointment): string
    {
        $service = trim((string) ($appointment->service_type_name ?? ''));
        if ($service === '' && filled($appointment->procedure?->diagnosis)) {
            $service = trim((string) $appointment->procedure->diagnosis);
        }

        return $this->normalizeReportServiceLabel($service, 'Dental Service');
    }

    private function normalizeReportServiceLabel(?string $value, string $fallback = 'Dental Service'): string
    {
        $value = preg_replace('/\s+/', ' ', trim((string) $value)) ?? '';

        return $value !== '' ? $value : $fallback;
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
        return match ($this->categorizePatientForReports($patient)) {
            'faculty' => 'faculty',
            'administrative' => 'administrative',
            'dependent' => 'dependents',
            default => 'students',
        };
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

        $this->drawDentalCasesSection(
            $pdf,
            $caseGroups['students'] ?? [],
            236.3
        );

        $this->drawDentalCasesSection(
            $pdf,
            $caseGroups['faculty'] ?? [],
            328.4
        );

        $this->drawDentalCasesSection(
            $pdf,
            $caseGroups['administrative'] ?? [],
            420.4
        );

        $this->drawDentalCasesSection(
            $pdf,
            $caseGroups['dependents'] ?? [],
            513.3
        );
    }

    private function drawDentalCasesSection(
        Fpdi $pdf,
        array $rows,
        float $firstRowY
    ): void {
        $rowHeight = 15.4;

        foreach (
            array_slice($rows, 0, 3)
            as $index => $row
        ) {
            $y =
                $firstRowY +
                ($index * $rowHeight);

            $diagnosis =
                trim(
                    (string) (
                        $row['diagnosis']
                        ?? ''
                    )
                );

            $total =
                (string) (
                    (int) (
                        $row['total']
                        ?? 0
                    )
                );

            $pdf->SetFont(
                'Helvetica',
                '',
                7.2
            );

            $this->drawPdfCellAutoFont(
                $pdf,
                259.2,
                $y,
                $diagnosis,
                195,
                8,
                'L',
                'Helvetica',
                '',
                7.2,
                5.5
            );

            $pdf->SetFont(
                'Helvetica',
                'B',
                7.2
            );

            $this->drawPdfCell(
                $pdf,
                426.4,
                $y,
                $total,
                80,
                8,
                'C'
            );
        }
    }

    private function reportPages(
        $items,
        int $rowsPerPage
    ) {
        return collect($items)
            ->values()
            ->chunk($rowsPerPage);
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
        array $odontogramData
    ): void {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 8);

        $patientName = trim((string) ($patient->name ?? ''));
        [$lastName, $firstName, $middleName] = $this->splitDentalHealthRecordNameParts($patient, $patientName);

        $yearSection = '';

        if (! empty($patient->year_level)) {
            $yearSection .= 'Y' . $patient->year_level;
        }

        if (! empty($patient->section)) {
            $yearSection .= $yearSection !== '' ? ' - ' . $patient->section : $patient->section;
        }

        $facultyCollege = trim((string) ($patient->course_code ?? ''));

        if ($facultyCollege === '') {
            $facultyCollege = trim((string) ($patient->course_name ?? ''));
        }

        $adminDept = trim((string) ($patient->faculty_code ?? ''));

        $demographics = $this->getPatientDemographics($patient);
        $birthdate = $demographics['birthdate_short'];
        $age = $demographics['age'];
        $sex = $demographics['gender'];

        $lastDentalVisit = $dentalHistory && $dentalHistory->last_dental_visit
            ? Carbon::parse($dentalHistory->last_dental_visit)->format('m/d/y')
            : '';

        $previousDentist = trim((string) ($dentalHistory->previous_dentist ?? ''));
        $previousDentist = preg_replace('/^dr\.?\s*/i', '', $previousDentist) ?? $previousDentist;

        $this->drawPdfCellAutoFont($pdf, 96, 151, $lastName, 110, 8, 'C', 'Helvetica', '', 8, 5.8);
        $this->drawPdfCellAutoFont($pdf, 242, 151, $firstName, 150, 8, 'C', 'Helvetica', '', 8, 5.8);
        $this->drawPdfCellAutoFont($pdf, 372, 151, $middleName, 115, 8, 'C', 'Helvetica', '', 8, 5.8);
        $this->drawPdfCellAutoFont($pdf, 161, 177, $yearSection, 62, 8, 'C', 'Helvetica', '', 7.8, 5.6);
        $this->drawPdfCellAutoFont($pdf, 346, 177, $facultyCollege, 76, 8, 'C', 'Helvetica', '', 7.2, 5.4);
        $this->drawPdfCellAutoFont($pdf, 505, 176, $adminDept, 58, 8, 'C', 'Helvetica', '', 7.2, 5.4);
        $this->drawPdfCellAutoFont($pdf, 173, 191, $birthdate, 90, 8, 'C', 'Helvetica', '', 7.8, 5.6);
        $this->drawPdfCellAutoFont($pdf, 268, 191, $age, 40, 8, 'C', 'Helvetica', '', 7.8, 5.6);
        $this->drawPdfCellAutoFont($pdf, 390, 191, $sex, 95, 8, 'C', 'Helvetica', '', 7.2, 5.4);
        $this->drawDentalHealthOdontogram($pdf, $odontogramData);

        $this->drawPdfCellAutoFont(
            $pdf,
            220,
            513,
            $previousDentist,
            185,
            8,
            'L',
            'Helvetica',
            '',
            8,
            5.8
        );
        $this->drawPdfCellAutoFont($pdf, 203, 526, $lastDentalVisit, 195, 8, 'L', 'Helvetica', '', 8, 5.8);

        $pdf->SetFont('Helvetica', '', 7);

        $this->drawPdfCell($pdf, 247, 552, $this->dhrDentalAnswer($dentalAnswers, 'gum_bleeding'), 82, 8, 'C');
        $this->drawPdfCell($pdf, 214, 565, $this->dhrDentalAnswer($dentalAnswers, 'hot_cold_sensitive'), 68, 8, 'C');
        $this->drawPdfCell($pdf, 258, 578, $this->dhrDentalAnswer($dentalAnswers, 'sweet_sour_sensitive'), 128, 8, 'C');
        $this->drawPdfCell($pdf, 213, 591, $this->dhrDentalAnswer($dentalAnswers, 'tooth_pain'), 90, 8, 'C');
        $this->drawPdfCell($pdf, 254, 603, $this->dhrDentalAnswer($dentalAnswers, 'sores_lumps'), 42, 8, 'C');
        $this->drawPdfCell($pdf, 252, 616, $this->dhrDentalAnswer($dentalAnswers, 'head_neck_jaw_injuries'), 90, 8, 'C');
        $this->drawPdfCell($pdf, 80, 641, $this->dhrDentalAnswer($dentalAnswers, 'clicking'), 22, 8, 'C');
        $this->drawPdfCell($pdf, 156, 654, $this->dhrDentalAnswer($dentalAnswers, 'joint_pain'), 34, 8, 'C');
        $this->drawPdfCell($pdf, 168, 667, $this->dhrDentalAnswer($dentalAnswers, 'opening_closing'), 38, 8, 'C');
        $this->drawPdfCell($pdf, 140, 680, $this->dhrDentalAnswer($dentalAnswers, 'chewing_difficulty'), 24, 8, 'C');
        $this->drawPdfCell($pdf, 130, 693, $this->dhrDentalAnswer($dentalAnswers, 'frequent_headaches'), 38, 8, 'C');
        $this->drawPdfCell($pdf, 180, 705, $this->dhrDentalAnswer($dentalAnswers, 'clench_grind'), 38, 8, 'C');
        $this->drawPdfCell($pdf, 164, 718, $this->dhrDentalAnswer($dentalAnswers, 'lip_cheek_biting'), 55, 8, 'C');
        $this->drawPdfCell($pdf, 205, 731, $this->dhrDentalAnswer($dentalAnswers, 'loosening_teeth'), 34, 8, 'C');
        $this->drawPdfCell($pdf, 212, 744, $this->dhrDentalAnswer($dentalAnswers, 'food_caught'), 42, 8, 'C');
        $this->drawPdfCellAutoFont($pdf, 200, 768, $this->dhrDentalAnswer($dentalAnswers, 'reaction_medicine_anesthetic'), 315, 8, 'L', 'Helvetica', '', 7, 5.2);
    }

    private function drawDentalHealthRecordPageTwo(
        Fpdi $pdf,
        array $dentalAnswers,
        ?DentalHistoryConcern $dentalConcern,
        ?DentalHistoryConditionDate $dentalDates,
        ?MedicalHistory $medicalHistory,
        array $medicalAnswers,
        array $diseaseAnswers,
        $patientTreatments,
        $procedureDiagnosisByAppointment,
        int $treatmentOffset = 0,
        bool $includeHistorySection = true
    ): void {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 6.2);

        $goodHealth = $this->dhrMedicalBoolByKeys($medicalAnswers, ['good_health']);
        $goodHealthDetails = $this->dhrMedicalTextByKeys($medicalAnswers, ['good_health_details']);

        $lastMedicalExam = $this->dhrMedicalTextByKeys($medicalAnswers, ['medical_exam_date'])
            ?: $this->pickMedicalHistoryValue($medicalHistory, [
                'last_medical_examination',
                'last_medical_exam',
                'last_medical_checkup',
                'last_medical_visit',
                'medical_examination_date',
                'medical_exam_date',
                'last_checkup',
            ]);

        $receivingTreatment = $this->dhrMedicalTextByKeys($medicalAnswers, ['treatment_details']);
        $hospitalized = $this->dhrMedicalBoolByKeys($medicalAnswers, ['hospitalized']);
        $hospitalDetails = $this->dhrMedicalTextByKeys($medicalAnswers, ['hospital_details']);
        $medicineAllergy = $this->dhrMedicalTextByKeys($medicalAnswers, ['allergy_medicine']);
        $foodAllergy = $this->dhrMedicalTextByKeys($medicalAnswers, ['allergy_food']);
        $otherAllergy = $this->dhrMedicalTextByKeys($medicalAnswers, ['allergy_others']);
        $takingMedication = $this->dhrMedicalBoolByKeys($medicalAnswers, ['medication']);
        $medicationDetails = $this->dhrMedicalTextByKeys($medicalAnswers, ['medication_details']);
        $pregnant = $this->dhrMedicalBoolByKeys($medicalAnswers, ['pregnant']);
        $nursing = $this->dhrMedicalBoolByKeys($medicalAnswers, ['nursing']);
        $birthControl = $this->dhrMedicalBoolByKeys($medicalAnswers, ['birth_control']);
        $tobaccoUse = $this->dhrMedicalTextByKeys($medicalAnswers, ['tobacco_use']);
        $tobaccoPerDay = $this->dhrMedicalTextByKeys($medicalAnswers, ['tobacco_per_day']);
        $tobaccoPerWeek = $this->dhrMedicalTextByKeys($medicalAnswers, ['tobacco_per_week']);
        $headacheText = $this->dhrMedicalTextByKeys($medicalAnswers, ['headaches']);
        $earacheText = $this->dhrMedicalTextByKeys($medicalAnswers, ['earaches']);
        $neckAcheText = $this->dhrMedicalTextByKeys($medicalAnswers, ['neck_aches']);
        $additionalHealthInfo = $this->dhrMedicalTextByKeys($medicalAnswers, [
            'additional_health_info',
            'additional_information',
        ]);

        if ($includeHistorySection) {
            $this->drawPdfCellAutoFont($pdf, 303, 25, $this->dhrDentalAnswer($dentalAnswers, 'periodontal_treatment'), 180, 8, 'L', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCell($pdf, 214, 38, $this->dhrDentalAnswer($dentalAnswers, 'difficult_extraction'), 50, 8, 'C');
            $this->drawPdfCellAutoFont($pdf, 316, 38, $this->formatDhrDate($dentalDates?->extraction_date), 85, 8, 'C', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCellAutoFont($pdf, 345, 51, $this->dhrDentalAnswer($dentalAnswers, 'prolonged_bleeding'), 105, 8, 'C', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCell($pdf, 216, 64, $this->dhrDentalAnswer($dentalAnswers, 'dentures'), 45, 8, 'C');
            $this->drawPdfCell($pdf, 368, 64, $this->formatDhrDate($dentalDates?->dentures_date), 62, 8, 'C');
            $this->drawPdfCell($pdf, 197, 77, $this->dhrDentalAnswer($dentalAnswers, 'orthodontic'), 45, 8, 'C');
            $this->drawPdfCell($pdf, 352, 77, $this->formatDhrDate($dentalDates?->ortho_date), 62, 8, 'C');
            $this->drawPdfWrappedCell($pdf, 306, 103, trim((string) ($dentalConcern->additional_concerns ?? '')), 530, 15, 'L', 2);

            $pdf->SetFont('Helvetica', '', 6.2);

            $this->drawPdfCell($pdf, 157, 146, $this->dhrBoolMark($goodHealth, true), 38, 8, 'C');
            $this->drawPdfCell($pdf, 207, 146, $this->dhrBoolMark($goodHealth, false), 38, 8, 'C');
            $this->drawPdfCellAutoFont($pdf, 384, 146, $goodHealthDetails, 115, 8, 'L', 'Helvetica', '', 6.2, 4.8);

            $this->drawPdfCellAutoFont(
                $pdf,
                318,
                159,
                $lastMedicalExam,
                185,
                8,
                'L',
                'Helvetica',
                '',
                6.2,
                4.8
            );

            $this->drawPdfCellAutoFont($pdf, 230, 187, $receivingTreatment, 330, 8, 'L', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCell($pdf, 205, 201, $this->dhrBoolText($hospitalized), 65, 8, 'C');
            $this->drawPdfCellAutoFont($pdf, 252, 213, $hospitalDetails, 420, 8, 'L', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCellAutoFont($pdf, 134, 240, $medicineAllergy, 85, 8, 'C', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCellAutoFont($pdf, 253, 240, $foodAllergy, 135, 8, 'C', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCellAutoFont($pdf, 405, 240, $otherAllergy, 105, 8, 'C', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCell($pdf, 286, 253, $this->dhrBoolText($takingMedication), 30, 8, 'C');
            $this->drawPdfCellAutoFont($pdf, 184, 266, $medicationDetails, 135, 8, 'L', 'Helvetica', '', 6.2, 4.8);
            $this->drawPdfCell($pdf, 352, 279, $this->dhrBoolMark($pregnant, true), 24, 9, 'C');
            $this->drawPdfCell($pdf, 413, 279, $this->dhrBoolMark($pregnant, false), 24, 9, 'C');
            $this->drawPdfCell($pdf, 352, 292, $this->dhrBoolMark($nursing, true), 24, 9, 'C');
            $this->drawPdfCell($pdf, 413, 292, $this->dhrBoolMark($nursing, false), 24, 9, 'C');
            $this->drawPdfCell($pdf, 352, 305, $this->dhrBoolMark($birthControl, true), 24, 9, 'C');
            $this->drawPdfCell($pdf, 413, 305, $this->dhrBoolMark($birthControl, false), 24, 9, 'C');

            $pdf->SetFont('Helvetica', 'B', 7);

            $leftDiseaseY = [334, 346, 358, 370, 382, 397, 409, 421, 433, 445];
            $rightDiseaseY = [334, 346, 358, 370, 382, 397, 409, 421, 433, 445];
            $leftDiseases = [['hiv'], ['alcohol'], ['arthritis'], ['artificial'], ['asthma'], ['blood', 'transfusion'], ['cancer'], ['diabetes'], ['eating'], ['epilepsy']];
            $rightDiseases = [['faint'], ['blood', 'pressure'], ['glycemia'], ['kidney'], ['liver'], ['mental'], ['ulcer'], ['stroke'], ['tuberculosis'], ['venereal']];

            foreach ($leftDiseases as $index => $needles) {
                $this->drawPdfCell($pdf, 49, $leftDiseaseY[$index], $this->findDhrDiseaseMark($diseaseAnswers, $needles), 18, 8, 'C');
            }

            foreach ($rightDiseases as $index => $needles) {
                $this->drawPdfCell($pdf, 309, $rightDiseaseY[$index], $this->findDhrDiseaseMark($diseaseAnswers, $needles), 24, 8, 'C');
            }

            $pdf->SetFont('Helvetica', '', 7);

            $this->drawPdfCellAutoFont($pdf, 222, 459, $tobaccoUse, 30, 8, 'C', 'Helvetica', '', 7, 5.2);
            $this->drawPdfCellAutoFont($pdf, 356, 459, $tobaccoPerDay, 34, 8, 'C', 'Helvetica', '', 7, 5.2);
            $this->drawPdfCellAutoFont($pdf, 431, 459, $tobaccoPerWeek, 30, 8, 'C', 'Helvetica', '', 7, 5.2);
            $this->drawPdfCellAutoFont($pdf, 169, 471, $headacheText, 20, 8, 'C', 'Helvetica', '', 7, 5.2);
            $this->drawPdfCellAutoFont($pdf, 233, 471, $earacheText, 32, 8, 'C', 'Helvetica', '', 7, 5.2);
            $this->drawPdfCellAutoFont($pdf, 325, 471, $neckAcheText, 36, 8, 'C', 'Helvetica', '', 7, 5.2);
            $this->drawPdfWrappedCell($pdf, 303, 496, $additionalHealthInfo, 530, 10, 'L', 1);

            $pdf->SetFont('Helvetica', '', 7.2);
            $this->drawPdfCellAutoFont($pdf, 223, 511, trim((string) ($medicalHistory->emergency_person ?? '')), 82, 7, 'L', 'Helvetica', '', 7.2, 5.6);
            $this->drawPdfCellAutoFont($pdf, 380, 511, trim((string) ($medicalHistory->emergency_relation ?? '')), 82, 7, 'L', 'Helvetica', '', 7.2, 5.6);
            $this->drawPdfCellAutoFont($pdf, 154, 524, trim((string) ($medicalHistory->emergency_number ?? '')), 108, 7, 'L', 'Helvetica', '', 7.2, 5.6);

            $signaturePath = $this->getStoredSignaturePath(
                $medicalHistory?->patient_signature
            );

            if ($signaturePath) {
                $this->drawPdfImageInBox(
                    $pdf,
                    $signaturePath,
                    163,
                    537,
                    105,
                    10
                );
            }
        }

        $rowStartY = 585.0;
        $rowHeight = 19.3;
        $maxRows = 10;

        foreach ($patientTreatments->slice($treatmentOffset, $maxRows)->values() as $index => $appointment) {
            $y = $rowStartY + ($index * $rowHeight);

            $date = ! empty($appointment->appointment_date)
                ? Carbon::parse($appointment->appointment_date)->format('m/d/y')
                : '';

            $diagnosis = trim((string) ($procedureDiagnosisByAppointment[$appointment->id] ?? ''));

            if ($diagnosis === '' && ! empty($appointment->procedure?->diagnosis)) {
                $diagnosis = trim((string) $appointment->procedure->diagnosis);
            }

            if ($diagnosis === '') {
                $diagnosis = trim((string) ($appointment->service_type_name ?? ''));
            }

            $treatment = trim((string) ($appointment->service_type_name ?? ''));
            if ($treatment === '') {
                $treatment = 'Dental Service';
            }

            $attending = trim((string) ($appointment->dentist->name ?? ''));

            if ($attending === '') {
                $attending = 'Not assigned';
            }

            $pdf->SetFont('Helvetica', '', 6.2);

            $this->drawPdfCellAutoFont($pdf, 71, $y, $date, 66, 7, 'C', 'Helvetica', '', 6.2, 5.4);
            $this->drawPdfWrappedCell($pdf, 185, $y, $diagnosis, 150, 13, 'L', 2);
            $this->drawPdfWrappedCell($pdf, 343, $y, $treatment, 150, 13, 'L', 2);
            $this->drawPdfWrappedCell($pdf, 498, $y, $attending, 142, 13, 'L', 2);
        }
    }

    private function getDentalHealthAnswerMap(int $patientId): array
    {
        $orderedKeys = [
            'bleeding_gums',
            'sensitive_temp',
            'sensitive_taste',
            'tooth_pain',
            'sores',
            'injuries',
            'clicking',
            'joint_pain',
            'difficulty_moving',
            'difficulty_chewing',
            'jaw_headaches',
            'clench_grind',
            'biting',
            'teeth_loosening',
            'food_teeth',
            'med_reaction',
            'periodontal',
            'difficult_extraction',
            'prolonged_bleeding',
            'dentures',
            'ortho_treatment',
        ];

        $legacyAliases = [
            'bleeding_gums' => 'gum_bleeding',
            'sensitive_temp' => 'hot_cold_sensitive',
            'sensitive_taste' => 'sweet_sour_sensitive',
            'sores' => 'sores_lumps',
            'injuries' => 'head_neck_jaw_injuries',
            'difficulty_moving' => 'opening_closing',
            'difficulty_chewing' => 'chewing_difficulty',
            'jaw_headaches' => 'frequent_headaches',
            'biting' => 'lip_cheek_biting',
            'teeth_loosening' => 'loosening_teeth',
            'food_teeth' => 'food_caught',
            'med_reaction' => 'reaction_medicine_anesthetic',
            'periodontal' => 'periodontal_treatment',
            'ortho_treatment' => 'orthodontic',
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
                $rawCodeKey = strtolower(trim((string) ($answer->condition->code ?? '')));
                $normalizedCodeKey = $this->normalizeDhrKey($rawCodeKey);
                $questionKey = $this->normalizeDhrKey($answer->condition->question ?? '');

                if ($rawCodeKey !== '') {
                    $map[$rawCodeKey] = $value;

                    if (isset($legacyAliases[$rawCodeKey])) {
                        $map[$legacyAliases[$rawCodeKey]] = $value;
                    }
                }

                if ($normalizedCodeKey !== '') {
                    $map[$normalizedCodeKey] = $value;
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
        if (array_key_exists($key, $map)) {
            return (string) $map[$key];
        }

        $normalizedKey = $this->normalizeDhrKey($key);

        if ($normalizedKey !== '' && array_key_exists($normalizedKey, $map)) {
            return (string) $map[$normalizedKey];
        }

        return '';
    }

    private function getMedicalHealthAnswerMap(int $patientId, int $medicalHistoryId): array
    {
        $orderedKeys = [
            'good_health',
            'had_medical_exam',
            'good_health_details',
            'medical_exam_date',
            'under_treatment',
            'treatment_details',
            'hospitalized',
            'hospital_details',
            'allergy_medicine',
            'allergy_food',
            'allergy_others',
            'medication',
            'medication_details',
            'pregnant',
            'nursing',
            'birth_control',
            'tobacco_use',
            'tobacco_per_day',
            'tobacco_per_week',
            'headaches',
            'earaches',
            'neck_aches',
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
            if (! $answer->question) {
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
            if (! $answer->disease) {
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
            if (! $this->dhrKeyContainsAll($key, $needles)) {
                continue;
            }

            if (! empty($value['date'])) {
                return $this->formatDhrDate($value['date']);
            }

            if (! empty($value['text'])) {
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
            if (! $this->dhrKeyContainsAll($key, $needles)) {
                continue;
            }

            if (! empty($value['date'])) {
                return $this->formatDhrDate($value['date']);
            }

            if (! empty($value['text'])) {
                return trim((string) $value['text']);
            }

            return '';
        }

        return '';
    }

    private function dhrMedicalBoolByKeys(array $map, array $keys): ?bool
    {
        foreach ($keys as $key) {
            $value = $map[$this->normalizeDhrKey($key)] ?? null;

            if (is_array($value) && array_key_exists('bool', $value)) {
                return $value['bool'];
            }
        }

        return null;
    }

    private function dhrMedicalTextByKeys(array $map, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $map[$this->normalizeDhrKey($key)] ?? null;

            if (! is_array($value)) {
                continue;
            }

            if (! empty($value['date'])) {
                return $this->formatDhrDate($value['date']);
            }

            if (! empty($value['text'])) {
                return trim((string) $value['text']);
            }

            if (array_key_exists('bool', $value) && $value['bool'] !== null) {
                return $this->dhrBoolText($value['bool']);
            }
        }

        return '';
    }

    private function buildDhrTobaccoSummary(array $medicalAnswers): string
    {
        $perDay = $this->dhrMedicalTextByKeys($medicalAnswers, ['tobacco_per_day']);
        $perWeek = $this->dhrMedicalTextByKeys($medicalAnswers, ['tobacco_per_week']);

        if ($perDay !== '' && $perWeek !== '') {
            return $perDay . '/' . $perWeek;
        }

        if ($perDay !== '') {
            return $perDay;
        }

        if ($perWeek !== '') {
            return $perWeek;
        }

        return $this->dhrMedicalTextByKeys($medicalAnswers, ['tobacco_use']);
    }

    private function buildDhrAdditionalHealthInfo(array $medicalAnswers): string
    {
        $notes = [];

        foreach (
            [
                'earaches' => 'Earaches',
                'neck_aches' => 'Neck aches',
            ] as $key => $label
        ) {
            $value = $this->dhrMedicalTextByKeys($medicalAnswers, [$key]);

            if ($value !== '') {
                $notes[] = $label . ': ' . $value;
            }
        }

        return implode(', ', $notes);
    }

    private function splitDentalHealthRecordNameParts($patient, string $patientName): array
    {
        $patient->loadMissing('user');

        $lastName = trim((string) (data_get($patient, 'user.last_name') ?? ''));
        $firstName = trim((string) (data_get($patient, 'user.first_name') ?? ''));
        $middleName = trim((string) (data_get($patient, 'user.middle_name') ?? ''));
        [$fallbackLastName, $fallbackFirstName, $fallbackMiddleName] =
            $this->splitDentalHealthRecordFallbackName($patientName);

        if ($lastName !== '' && $firstName !== '') {
            return [$lastName, $firstName, $middleName];
        }

        $lastName = $lastName !== '' ? $lastName : $fallbackLastName;
        $firstName = $firstName !== '' ? $firstName : $fallbackFirstName;
        $middleName = $middleName !== '' ? $middleName : $fallbackMiddleName;

        if ($lastName !== '' || $firstName !== '' || $middleName !== '') {
            return [$lastName, $firstName, $middleName];
        }

        return ['', '', ''];
    }

    private function splitDentalHealthRecordFallbackName(string $patientName): array
    {
        $parts = preg_split('/\s+/', trim($patientName)) ?: [];
        $parts = array_values(array_filter($parts, fn($part) => $part !== ''));

        if (count($parts) === 0) {
            return ['', '', ''];
        }

        if (count($parts) === 1) {
            return ['', $parts[0], ''];
        }

        $lastName = array_pop($parts);
        $middleName = count($parts) > 1 ? array_pop($parts) : '';
        $firstName = implode(' ', $parts);

        return [$lastName, $firstName, $middleName];
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

            if (! str_contains($key, $needle)) {
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
        if (! $medicalHistory) {
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

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        AuditLogger::log(
            'view',
            'dentist_daily_treatment_record',
            'Dentist viewed daily treatment record'
        );

        $dailyTreatmentTemplates = DocumentTemplate::query()
            ->active()
            ->where('document_type', 'daily_treatment_record')
            ->orderByRaw("
                CASE
                    WHEN code = 'DTR-DEFAULT' THEN 0
                    WHEN code = 'DTR-FACULTY' THEN 1
                    WHEN code = 'DTR-ALUMNI' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('name')
            ->get();

        return view('dentist.daily-treatment', [
            'dailyTreatmentTemplates' => $dailyTreatmentTemplates,
        ]);
    }

    public function dailyTreatmentRecordList(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Appointment::query()
            ->with([
                'patient.medicalHistory',
                'patient.information',
                'procedure',
            ])
            ->where('status', 'completed')
            ->whereHas('patient');

        if ($request->filled('month') && preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month'))) {
            [$year, $month] = explode('-', $request->input('month'));

            $query->whereYear('appointment_date', $year)
                ->whereMonth('appointment_date', $month);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where(
                    'service_type',
                    'like',
                    "%{$search}%"
                )
                    ->orWhereHas(
                        'patient',
                        function ($patientQuery) use ($search) {
                            $patientQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'information',
                                    function ($informationQuery) use ($search) {
                                        $informationQuery
                                            ->where(
                                                'phone',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'course_code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'course_name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'faculty_code',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
            });
        }

        if ($request->filled('office_type')) {
            $officeType = strtolower(
                trim((string) $request->input('office_type'))
            );

            $query->whereHas(
                'patient',
                function ($patientQuery) use ($officeType) {

                    if ($officeType === 'student') {
                        $patientQuery->where(
                            'classification',
                            'student'
                        );

                        return;
                    }

                    if ($officeType === 'faculty') {
                        $patientQuery->where(
                            'classification',
                            'faculty'
                        );

                        return;
                    }

                    if (
                        in_array(
                            $officeType,
                            [
                                'administrative',
                                'administrative personnel',
                            ],
                            true
                        )
                    ) {
                        $patientQuery->where(
                            'classification',
                            'administrative'
                        );

                        return;
                    }

                    if (
                        in_array(
                            $officeType,
                            [
                                'dependent',
                                'alumni',
                                'dependent & alumni',
                            ],
                            true
                        )
                    ) {
                        $patientQuery->whereIn(
                            'classification',
                            ['alumni', 'dependent', 'dependent_alumni']
                        );
                    }
                }
            );
        }

        if ($request->filled('program_code')) {
            $programCode = trim(
                (string) $request->input('program_code')
            );

            $query->whereHas(
                'patient.information',
                function ($informationQuery) use ($programCode) {
                    $informationQuery->where(
                        'course_code',
                        $programCode
                    );
                }
            );
        }

        if ($request->input('sort_name') === 'az') {
            $query->join('patients as sort_patients', 'sort_patients.id', '=', 'appointments.patient_id')
                ->select('appointments.*')
                ->orderBy('sort_patients.name', 'asc')
                ->orderBy('appointments.appointment_date', 'desc')
                ->orderBy('appointments.appointment_time', 'desc');
        } elseif ($request->input('sort_name') === 'za') {
            $query->join('patients as sort_patients', 'sort_patients.id', '=', 'appointments.patient_id')
                ->select('appointments.*')
                ->orderBy('sort_patients.name', 'desc')
                ->orderBy('appointments.appointment_date', 'desc')
                ->orderBy('appointments.appointment_time', 'desc');
        } elseif ($request->input('sort_date') === 'asc') {
            $query->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');
        } else {
            $query->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc');
        }

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $records = $query
            ->paginate($perPage)
            ->through(function (Appointment $appointment) {
                $patient = $appointment->patient;
                $procedure = $appointment->procedure;
                $demographics = $this->getPatientDemographics($patient);

                $requestedDateTime = '';

                if (!empty($appointment->appointment_date)) {
                    $requestedDateTime = Carbon::parse($appointment->appointment_date)->format('m/d/y');
                }

                if (!empty($appointment->appointment_time)) {
                    $timeText = $this->formatPdfTime($appointment->appointment_time);
                    $requestedDateTime .= $requestedDateTime !== '' ? '  |  ' . $timeText : $timeText;
                }

                $processedDateTime = '';

                if ($procedure?->procedure_completed_at) {
                    $processedDateTime = Carbon::parse($procedure->procedure_completed_at)->format('m/d/y')
                        . '  |  '
                        . Carbon::parse($procedure->procedure_completed_at)->format('h:i A');
                } elseif (!empty($appointment->updated_at)) {
                    $processedDateTime = Carbon::parse($appointment->updated_at)->format('m/d/y')
                        . '  |  '
                        . Carbon::parse($appointment->updated_at)->format('h:i A');
                }

                return [
                    'id' => $appointment->id,
                    'treatment_date' => $appointment->appointment_date,
                    'requested_date_time' => $requestedDateTime,
                    'processed_date_time' => $processedDateTime,
                    'patient_name' => trim((string) ($patient->name ?? 'Unknown Patient')),
                    'patient_email' => trim((string) ($patient->email ?? '')),
                    'patient_phone' => trim((string) ($patient->phone ?? '')),
                    'office_type' => $this->dailyTreatmentOfficeType($patient),
                    'office_display' => $this->dailyTreatmentOfficeDisplay($patient),
                    'program_code' => trim((string) ($patient->course_code ?? '')),
                    'gender' => trim((string) ($demographics['gender'] ?? '')),
                    'treatment_done' => trim((string) ($appointment->service_type ?? 'Dental Service')),
                    'minutes_processed' => $this->formatPdfDurationMinutes(
                        (int) ($procedure?->procedure_duration_seconds ?? 0)
                    ),
                    'time_in' => !empty($appointment->appointment_time)
                        ? $this->formatPdfTime($appointment->appointment_time)
                        : null,
                    'time_out' => $procedure?->procedure_completed_at
                        ? Carbon::parse($procedure->procedure_completed_at)->format('h:i A')
                        : null,
                    'has_signature' => filled($patient?->medicalHistory?->patient_signature),
                    'signature_path' => $patient?->medicalHistory?->patient_signature,
                    'signature_url' => filled($patient?->medicalHistory?->patient_signature)
                        ? asset('storage/' . ltrim((string) $patient->medicalHistory->patient_signature, '/'))
                        : null,
                ];
            });

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

    private function dentalServicesCourseYearSectionDisplay($patient): string
    {
        if (! $patient) {
            return '';
        }

        if ($this->categorizePatientForReports($patient) === 'faculty') {
            return 'Faculty';
        }

        $programOrDept = trim((string) ($patient->course_code ?? ''));

        if ($programOrDept === '') {
            $programOrDept = trim((string) ($patient->course_name ?? ''));
        }

        if ($programOrDept !== '' && ! empty($patient->year_level)) {
            $programOrDept .= ' - Y' . $patient->year_level;
        }

        if ($programOrDept !== '' && ! empty($patient->section)) {
            $programOrDept .= ' / ' . $patient->section;
        }

        return $programOrDept;
    }

    private function dailyTreatmentOfficeDisplay($patient): string
    {
        if (! $patient) {
            return '—';
        }

        $category = $this->categorizePatientForReports($patient);
        $classification = $this->resolveDailyTreatmentPatientClassification($patient);

        if ($category === 'administrative') {
            return trim((string) (
                $patient->course_name
                ?? $patient->course_code
                ?? 'Administrative Personnel'
            )) ?: 'Administrative Personnel';
        }

        if ($category === 'faculty') {
            return trim((string) (
                $patient->faculty_code
                ?? $patient->course_name
                ?? 'Faculty'
            )) ?: 'Faculty';
        }

        if ($category === 'dependent') {
            return $classification === 'alumni' ? 'Alumni' : 'Dependent';
        }

        if ($category === 'student') {
            return trim((string) (
                $patient->course_code
                ?? $patient->course_name
                ?? 'Student'
            )) ?: 'Student';
        }

        return '—';
    }

    private function dailyTreatmentOfficeType($patient): string
    {
        if (!$patient) {
            return '';
        }

        return match ($this->categorizePatientForReports($patient)) {
            'student' =>
            'Student',

            'faculty' =>
            'Faculty',

            'administrative' =>
            'Administrative Personnel',

            'dependent' =>
            'Alumni / Dependent',

            default =>
            'Alumni / Dependent',
        };
    }

    private function buildGadData(int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $counts = $this->buildGadPdfCounts($from, $to);

        $gadLabels = ['Student', 'Faculty', 'Administrative', 'Dependent'];
        $gadFemale = [];
        $gadMale = [];

        for ($index = 0; $index < 4; $index++) {
            $gadFemale[] =
                (int) ($counts['gad_female'][$index] ?? 0)
                + (int) ($counts['senior_female'][$index] ?? 0)
                + (int) ($counts['pwd_female'][$index] ?? 0);

            $gadMale[] =
                (int) ($counts['gad_male'][$index] ?? 0)
                + (int) ($counts['senior_male'][$index] ?? 0)
                + (int) ($counts['pwd_male'][$index] ?? 0);
        }

        return [$gadLabels, $gadFemale, $gadMale];
    }

    private function buildWeeklyData(int $year, int $month): array
    {
        $topServices = Appointment::query()
            ->join(
                'service_types',
                'appointments.service_type_id',
                '=',
                'service_types.id'
            )
            ->whereYear('appointments.appointment_date', $year)
            ->whereMonth('appointments.appointment_date', $month)
            ->select(
                'service_types.id',
                'service_types.name',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(
                'service_types.id',
                'service_types.name'
            )
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        if ($topServices->isEmpty()) {
            return [[], []];
        }

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $weeksInMonth = (int) ceil($daysInMonth / 7);
        $weekLabels = array_map(fn($i) => "Week $i", range(1, $weeksInMonth));

        $topServiceIds = $topServices->pluck('id')->all();

        $weeklyRaw = Appointment::query()
            ->join(
                'service_types',
                'appointments.service_type_id',
                '=',
                'service_types.id'
            )
            ->whereYear('appointments.appointment_date', $year)
            ->whereMonth('appointments.appointment_date', $month)
            ->whereIn('appointments.service_type_id', $topServiceIds)
            ->select(
                'appointments.service_type_id',
                'service_types.name as service_type',
                DB::raw('CEIL(DAY(appointments.appointment_date) / 7) as week_num'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(
                'appointments.service_type_id',
                'service_types.name',
                'week_num'
            )
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
                $data[] = (int) $weeklyRaw
                    ->where('service_type_id', $service->id)
                    ->where('week_num', $w)
                    ->sum('total');
            }
            $color = $chartColors[$i] ?? ['border' => '#6B7280', 'bg' => 'rgba(107,114,128,0.08)'];
            $datasets[] = [
                'label' => $service->name,
                'data' => $data,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'tension' => 0.4,
                'pointRadius' => 5,
                'fill' => true,
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
            $values = $counts[$rowKey] ?? [null, null, null, null, null];

            $this->drawCenteredPdfText($pdf, $columns['students'], $y, $this->formatGadPdfValue($values[0] ?? null));
            $this->drawCenteredPdfText($pdf, $columns['faculty'], $y, $this->formatGadPdfValue($values[1] ?? null));
            $this->drawCenteredPdfText($pdf, $columns['administrative'], $y, $this->formatGadPdfValue($values[2] ?? null));
            $this->drawCenteredPdfText($pdf, $columns['dependent'], $y, $this->formatGadPdfValue($values[3] ?? null));
            $this->drawCenteredPdfText($pdf, $columns['total'], $y, $this->formatGadPdfValue($values[4] ?? null));
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
        $text = $this->preparePdfText($text);

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

        $appointments = Appointment::with('patient')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->whereHas('patient')
            ->get();

        $columnIndexes = [
            'student' => 0,
            'faculty' => 1,
            'administrative' => 2,
            'dependent' => 3,
        ];

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;

            if (! $patient) {
                continue;
            }

            $demographics = $this->getPatientDemographics($patient);

            $gender = $this->normalizeGadGender(
                $demographics['gender'] ?? null
            );

            if (! $gender) {
                continue;
            }


            $officeType = $this->categorizePatientForReports($patient);

            $columnIndex = $columnIndexes[$officeType] ?? 0;

            $genderRow = 'gad_' . $gender;

            $counts[$genderRow][$columnIndex]++;
            $counts[$genderRow][4]++;

            if ((bool) ($patient->is_senior ?? false)) {
                $seniorRow = 'senior_' . $gender;

                $counts[$seniorRow][$columnIndex]++;
                $counts[$seniorRow][4]++;
            }

            if ((bool) ($patient->is_pwd ?? false)) {
                $pwdRow = 'pwd_' . $gender;

                $counts[$pwdRow][$columnIndex]++;
                $counts[$pwdRow][4]++;
            }

            $counts['total'][$columnIndex]++;
            $counts['total'][4]++;
        }

        return $counts;
    }

    private function formatGadPdfValue(mixed $value): string
    {
        $count = (int) ($value ?? 0);

        return $count > 0 ? (string) $count : '';
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
        return $this->categorizePatientForReports((object) [
            'patient_type' => $officeType,
            'type' => $officeType,
        ]);
    }

    private function categorizePatientForReports($patient): string
    {
        if (! $patient) {
            return 'dependent';
        }

        $classification = $this->resolveDailyTreatmentPatientClassification($patient);

        if ($classification !== '') {
            return match ($classification) {
                'student' => 'student',
                'faculty' => 'faculty',
                'administrative' => 'administrative',
                default => 'dependent',
            };
        }

        if (
            filled($patient->student_no) ||
            filled($patient->student_number)
        ) {
            return 'student';
        }

        if (filled($patient->faculty_code)) {
            return 'faculty';
        }

        return 'dependent';
    }

    private function resolveDailyTreatmentPatientClassification($patient): string
    {
        if (! $patient) {
            return 'dependent';
        }

        $classification = strtolower(trim((string) ($patient->classification ?? '')));

        if ($classification !== '') {
            return match ($classification) {
                'student',
                'faculty',
                'administrative',
                'alumni',
                'dependent',
                'dependent_alumni' => $classification,
                default => 'dependent',
            };
        }

        if (
            filled($patient->student_no) ||
            filled($patient->student_number)
        ) {
            return 'student';
        }

        if (filled($patient->faculty_code)) {
            return 'faculty';
        }

        return 'dependent';
    }

    private function recordHasTruthyValue(object $record, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! property_exists($record, $column)) {
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

        if (! $user) {
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

    private function generateApprovedDocumentRequestPdfPayload(DocumentRequest $documentRequest): ?array
    {
        $documentRequest->loadMissing(['patient', 'approvedBy']);

        return match ($this->getApprovedDocumentRequestType($documentRequest->document_type)) {
            'annual_dental_clearance' => $this->buildAnnualDentalClearancePdfPayload($documentRequest),
            'dental_clearance' => $this->buildDentalClearancePdfPayload($documentRequest),
            'dental_health_record' => $this->buildDentalHealthRecordPdfPayload($documentRequest),
            default => null,
        };
    }

    private function getApprovedDocumentRequestType(?string $documentType): ?string
    {
        $normalized = strtolower(trim((string) $documentType));
        $normalized = str_replace(['-', '/'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return match ($normalized) {
            'annual dental clearance' => 'annual_dental_clearance',
            'dental clearance' => 'dental_clearance',
            'all dental records', 'medical records', 'diagnosis and treatment', 'dental health record' => 'dental_health_record',
            default => null,
        };
    }

    private function buildAnnualDentalClearancePdfPayload(DocumentRequest $documentRequest): array
    {
        $templatePath = storage_path('app/report-templates/annual-dental-clearance-template.pdf');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Annual Dental Clearance PDF template was not found.');
        }

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->setSourceFile($templatePath);

        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);
        $this->drawAnnualDentalClearancePage($pdf, $documentRequest);

        return [
            'file_name' => $this->buildApprovedDocumentRequestFileName($documentRequest, 'annual-dental-clearance'),
            'content' => $pdf->Output('S'),
        ];
    }

    private function buildDentalClearancePdfPayload(DocumentRequest $documentRequest): array
    {
        $templatePath = storage_path('app/report-templates/dental-clearance-template.pdf');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Dental Clearance PDF template was not found.');
        }

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->setSourceFile($templatePath);

        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);
        $this->drawDentalClearancePage($pdf, $documentRequest);

        return [
            'file_name' => $this->buildApprovedDocumentRequestFileName($documentRequest, 'dental-clearance'),
            'content' => $pdf->Output('S'),
        ];
    }

    private function buildDentalHealthRecordPdfPayload(DocumentRequest $documentRequest): array
    {
        $patient = $documentRequest->patient;

        if (!$patient) {
            throw new \RuntimeException('Unable to generate Dental Health Record because the patient record is missing.');
        }

        $templatePath = storage_path('app/report-templates/dental-health-record-template.pdf');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Dental Health Record template was not found.');
        }

        $patientTreatments = Appointment::with(['patient', 'dentist'])
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $dentalHistory = DentalHistory::where('patient_id', $patient->id)->latest()->first();
        $dentalConcern = DentalHistoryConcern::where('patient_id', $patient->id)->latest()->first();
        $dentalDates = DentalHistoryConditionDate::where('patient_id', $patient->id)->latest()->first();
        $medicalHistory = MedicalHistory::where('patient_id', $patient->id)->latest()->first();

        $dentalAnswers = $this->getDentalHealthAnswerMap($patient->id);
        $medicalAnswers = $medicalHistory
            ? $this->getMedicalHealthAnswerMap($patient->id, $medicalHistory->id)
            : [];
        $diseaseAnswers = $medicalHistory
            ? $this->getMedicalDiseaseAnswerMap($patient->id, $medicalHistory->id)
            : [];

        $procedureDiagnosisByAppointment = AppointmentProcedure::query()
            ->whereIn('appointment_id', $patientTreatments->pluck('id')->filter()->all())
            ->get()
            ->groupBy('appointment_id')
            ->map(function ($procedures) {
                return trim((string) ($procedures->last()->diagnosis ?? ''));
            });

        $patientAppointmentIds = $patientTreatments->pluck('id')->filter()->values();

        $appointmentProcedure = AppointmentProcedure::query()
            ->forPatient($patient->id)
            ->when(
                $patientAppointmentIds->isNotEmpty(),
                fn($q) => $q->whereIn('appointment_id', $patientAppointmentIds->all())
            )
            ->latest('id')
            ->first();

        $savedOdontogram = PatientOdontogram::query()
            ->where('patient_id', $patient->id)
            ->latest('updated_at')
            ->first();

        $odontogramData = $savedOdontogram?->odontogram_data
            ?: ($appointmentProcedure?->odontogram_data ?? []);

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $templatePageCount = $pdf->setSourceFile($templatePath);

        if ($templatePageCount !== 2) {
            throw new \RuntimeException('The Dental Health Record template must contain exactly two pages.');
        }

        $this->addDentalHealthTemplatePage($pdf, 1);
        $this->drawDentalHealthRecordPageOne($pdf, $patient, $dentalHistory, $dentalAnswers, $odontogramData);

        $this->addDentalHealthTemplatePage($pdf, 2);
        $this->drawDentalHealthRecordPageTwo(
            $pdf,
            $dentalAnswers,
            $dentalConcern,
            $dentalDates,
            $medicalHistory,
            $medicalAnswers,
            $diseaseAnswers,
            $patientTreatments,
            $procedureDiagnosisByAppointment,
            0,
            true
        );

        $maxRowsPerPage = 10;

        for ($offset = $maxRowsPerPage; $offset < $patientTreatments->count(); $offset += $maxRowsPerPage) {
            $this->addDentalHealthTemplatePage($pdf, 2);
            $this->drawDentalHealthRecordPageTwo(
                $pdf,
                $dentalAnswers,
                $dentalConcern,
                $dentalDates,
                $medicalHistory,
                $medicalAnswers,
                $diseaseAnswers,
                $patientTreatments,
                $procedureDiagnosisByAppointment,
                $offset,
                false
            );
        }

        return [
            'file_name' => $this->buildApprovedDocumentRequestFileName($documentRequest, 'dental-health-record'),
            'content' => $pdf->Output('S'),
        ];
    }

    private function buildApprovedDocumentRequestFileName(DocumentRequest $documentRequest, string $prefix): string
    {
        $reference = trim((string) ($documentRequest->reference_number ?? ('document-request-' . $documentRequest->id)));
        $reference = preg_replace('/[^A-Za-z0-9_\-]/', '_', $reference) ?: ('document-request-' . $documentRequest->id);

        return strtolower($prefix . '-' . $reference . '.pdf');
    }

    private function drawDentalServicesRows(Fpdi $pdf, $records): void
    {
        $pdf->SetTextColor(0, 0, 0);

        $startY = 208.0;
        $rowHeight = 26.5;

        foreach ($records as $index => $appointment) {
            $y = $startY + ($index * $rowHeight);

            $patient = $appointment->patient;

            $date = $appointment->appointment_date
                ? Carbon::parse($appointment->appointment_date)->format('m/d/y')
                : '';

            $timeIn = $this->formatPdfTime($appointment->appointment_time ?? null);

            $patientName = $this->formatPdfPatientNameSurnameFirst($patient?->name);
            $patientName = $patientName !== '' ? $patientName : 'Unknown Patient';

            $programOrDept = $this->dentalServicesCourseYearSectionDisplay($patient);

            $demographics = $this->getPatientDemographics($patient);
            $age = $demographics['age'];
            $gender = strtolower(trim((string) ($demographics['gender'] ?? '')));
            $email = trim((string) ($patient->email ?? ''));
            $contact = trim((string) ($patient->phone ?? ''));

            $isMale = str_starts_with($gender, 'm');
            $isFemale = str_starts_with($gender, 'f');
            $isSenior = (bool) ($patient->is_senior ?? false);
            $isPwd = (bool) ($patient->is_pwd ?? false);

            $procedure = $appointment->procedure;
            $visitType = strtolower(trim((string) ($appointment->visit_type ?? $appointment->concern ?? '')));
            $isWalkIn = (bool) ($appointment->is_walk_in ?? false);

            $isEmergency = $isWalkIn || str_contains($visitType, 'emergency');
            $isNonEmergency = ! $isEmergency;

            $timeProcessed = $procedure?->procedure_started_at
                ? $procedure->procedure_started_at->format('h:i A')
                : $timeIn;

            $processingTime = $this->formatPdfDurationMinutes(
                (int) ($procedure?->procedure_duration_seconds ?? 0)
            );

            if ($processingTime !== '') {
                $processingTime .= ((int) $processingTime === 1) ? ' min' : ' mins';
            }

            $signaturePath = $this->getStoredSignaturePath($patient?->medicalHistory?->patient_signature);

            $pdf->SetFont('Helvetica', '', 5.2);

            // DATE
            $this->drawPdfCell(
                $pdf,
                60.75,
                $y,
                $date,
                38,
                10,
                'C'
            );

            // TIME IN
            $this->drawPdfCell(
                $pdf,
                102.8,
                $y,
                $timeIn,
                40,
                10,
                'C'
            );

            // NAME OF PATIENT
            $this->drawPdfCellAutoFont(
                $pdf,
                156.9,
                $y,
                $patientName,
                58,
                10,
                'C',
                'Helvetica',
                '',
                5.2,
                3.5
            );

            // COURSE / YEAR / SECTION / DEPARTMENT
            $this->drawPdfCellAutoFont(
                $pdf,
                212.4,
                $y,
                $programOrDept,
                42,
                10,
                'C',
                'Helvetica',
                '',
                5.0,
                3.3
            );

            // AGE
            $this->drawPdfCell(
                $pdf,
                255.8,
                $y,
                $age,
                34,
                10,
                'C'
            );


            // ==========================
            // GAD
            // ==========================

            $pdf->SetFont('Helvetica', 'B', 5.5);

            // MALE
            $this->drawPdfCell(
                $pdf,
                295.85,
                $y,
                $isMale ? 'X' : '',
                34,
                10,
                'C'
            );

            // FEMALE
            $this->drawPdfCell(
                $pdf,
                335.9,
                $y,
                $isFemale ? 'X' : '',
                34,
                10,
                'C'
            );

            // SENIOR CITIZEN
            $this->drawPdfCell(
                $pdf,
                375.95,
                $y,
                $isSenior ? 'X' : '',
                34,
                10,
                'C'
            );

            // PWD
            $this->drawPdfCell(
                $pdf,
                416.05,
                $y,
                $isPwd ? 'X' : '',
                34,
                10,
                'C'
            );


            // ==========================
            // CONTACT / PROCESSING
            // ==========================

            $pdf->SetFont('Helvetica', '', 4.8);

            // EMAIL ADDRESS
            $this->drawPdfCellAutoFont(
                $pdf,
                470.0,
                $y,
                $email,
                62,
                10,
                'C',
                'Helvetica',
                '',
                4.8,
                2.8
            );

            // CONTACT NUMBER
            $this->drawPdfCellAutoFont(
                $pdf,
                529.25,
                $y,
                $contact,
                45,
                10,
                'C',
                'Helvetica',
                '',
                4.8,
                3.2
            );

            // TIME PROCESSED
            $this->drawPdfCell(
                $pdf,
                574.65,
                $y,
                $timeProcessed,
                36,
                10,
                'C'
            );

            // PROCESSING TIME
            $this->drawPdfCell(
                $pdf,
                614.7,
                $y,
                $processingTime,
                34,
                10,
                'C'
            );


            // ==========================
            // CASE TYPE
            // ==========================

            $pdf->SetFont('Helvetica', 'B', 5.5);

            // EMERGENCY
            $this->drawPdfCell(
                $pdf,
                653.35,
                $y,
                $isEmergency ? 'X' : '',
                30,
                10,
                'C'
            );

            // NON-EMERGENCY
            $this->drawPdfCell(
                $pdf,
                689.25,
                $y,
                $isNonEmergency ? 'X' : '',
                28,
                10,
                'C'
            );


            // ==========================
            // SIGNATURE
            // ==========================

            if ($signaturePath) {
                $this->drawPdfImageInBox(
                    $pdf,
                    $signaturePath,
                    737.6,
                    $y,
                    55,
                    18
                );
            }
        }
    }

    private function drawDailyTreatmentRecordRows(Fpdi $pdf, $records, string $templateCode = 'DTR-DEFAULT'): void
    {
        $pdf->SetTextColor(0, 0, 0);

        $isStudentTemplate = $templateCode === 'DTR-DEFAULT';

        $layout = $isStudentTemplate
            ? [
                'startY' => 183.0,
                'rowHeight' => 18.35,
                'textHeight' => 12.0,
                'fontSize' => 5.2,

                'columnLayout' => [
                    'requested' => [
                        'x' => 96.5,
                        'width' => 70,
                    ],

                    'patient' => [
                        'x' => 181.0,
                        'width' => 88,
                    ],

                    'contact' => [
                        'x' => 283.5,
                        'width' => 104,
                    ],

                    'office' => [
                        'x' => 365.0,
                        'width' => 45,
                    ],

                    'gender' => [
                        'x' => 409.0,
                        'width' => 31,
                    ],

                    'treatment' => [
                        'x' => 469.3,
                        'width' => 78,
                    ],

                    'processed' => [
                        'x' => 546.7,
                        'width' => 65,
                    ],

                    'minutes' => [
                        'x' => 615.5,
                        'width' => 58,
                    ],

                    'signature' => [
                        'x' => 689.2,
                        'width' => 46,
                        'height' => 10.5,
                    ],
                ],
            ]
            : [
                'startY' => 183.0,
                'rowHeight' => 18.35,
                'textHeight' => 12.0,
                'fontSize' => 5.2,

                'columnLayout' => [
                    'requested' => [
                        'x' => 96.5,
                        'width' => 70,
                    ],

                    'patient' => [
                        'x' => 181.0,
                        'width' => 88,
                    ],

                    'contact' => [
                        'x' => 283.5,
                        'width' => 104,
                    ],

                    'office' => [
                        'x' => 365.0,
                        'width' => 45,
                    ],

                    'gender' => [
                        'x' => 409.0,
                        'width' => 31,
                    ],

                    'treatment' => [
                        'x' => 469.3,
                        'width' => 78,
                    ],

                    'processed' => [
                        'x' => 546.7,
                        'width' => 65,
                    ],

                    'minutes' => [
                        'x' => 615.5,
                        'width' => 58,
                    ],

                    'signature' => [
                        'x' => 689.2,
                        'width' => 46,
                        'height' => 10.5,
                    ],
                ],
            ];

        $startY = $layout['startY'];
        $rowHeight = $layout['rowHeight'];
        $textHeight = $layout['textHeight'];
        $columnLayout = $layout['columnLayout'];

        foreach ($records as $index => $appointment) {
            $y = $startY + ($rowHeight / 2) + ($index * $rowHeight);

            $patient = $appointment->patient;

            $requestedDateTime = '';

            if (! empty($appointment->appointment_date)) {
                $requestedDateTime = Carbon::parse($appointment->appointment_date)->format('m/d/y');
            }

            if (! empty($appointment->appointment_time)) {
                $timeText = $this->formatPdfTime($appointment->appointment_time);
                $requestedDateTime .= $requestedDateTime !== '' ? '  |  ' . $timeText : $timeText;
            }

            $patientName = trim((string) ($patient->name ?? 'Unknown Patient'));

            $email = trim((string) ($patient->email ?? ''));
            $contact = trim((string) ($patient->phone ?? ''));

            $emailContact = $this->buildPdfContactBlock($email, $contact);

            $office =
                $this->dailyTreatmentOfficeDisplay(
                    $patient
                );

            if ($office === '—') {
                $office = '';
            }

            $demographics = $this->getPatientDemographics($patient);
            $gender = trim((string) ($demographics['gender'] ?? ''));
            $treatmentDone = trim((string) ($appointment->service_type_name ?? ''));
            if ($treatmentDone === '') {
                $treatmentDone = 'Dental Service';
            }

            $procedure = $appointment->procedure;
            $processedDateTime = '';

            if ($procedure?->procedure_completed_at) {
                $processedDateTime = Carbon::parse($procedure->procedure_completed_at)->format('m/d/y')
                    . '  |  '
                    . Carbon::parse($procedure->procedure_completed_at)->format('h:i A');
            } elseif (! empty($appointment->updated_at)) {
                $processedDateTime = Carbon::parse($appointment->updated_at)->format('m/d/y')
                    . '  |  '
                    . Carbon::parse($appointment->updated_at)->format('h:i A');
            }

            $minutesProcessed = $this->formatPdfDurationMinutes(
                (int) ($procedure?->procedure_duration_seconds ?? 0)
            );

            $signaturePath = $this->getStoredSignaturePath($patient?->medicalHistory?->patient_signature);

            $pdf->SetFont('Helvetica', '', $layout['fontSize']);

            $this->drawPdfCellAutoFont(
                $pdf,
                $columnLayout['requested']['x'],
                $y,
                $requestedDateTime,
                $columnLayout['requested']['width'],
                $textHeight,
                'C',
                'Helvetica',
                '',
                5.2,
                4.3
            );

            $this->drawPdfCellAutoFont(
                $pdf,
                $columnLayout['patient']['x'],
                $y,
                $patientName,
                $columnLayout['patient']['width'],
                $textHeight,
                'C',
                'Helvetica',
                '',
                5.3,
                4.0
            );

            $this->drawPdfCellAutoFont(
                $pdf,
                $columnLayout['contact']['x'],
                $y,
                $emailContact,
                $columnLayout['contact']['width'],
                $textHeight,
                'C',
                'Helvetica',
                '',
                5.0,
                3.7
            );

            $this->drawPdfCellAutoFont(
                $pdf,
                $columnLayout['office']['x'],
                $y,
                $office,
                $columnLayout['office']['width'],
                $textHeight,
                'C',
                'Helvetica',
                '',
                5.0,
                3.5
            );

            $this->drawPdfCellAutoFont(
                $pdf,
                $columnLayout['gender']['x'],
                $y,
                $gender,
                $columnLayout['gender']['width'],
                $textHeight,
                'C',
                'Helvetica',
                '',
                5.2,
                4.0
            );

            $this->drawPdfCellAutoFont(
                $pdf,
                $columnLayout['treatment']['x'],
                $y,
                $treatmentDone,
                $columnLayout['treatment']['width'],
                $textHeight,
                'C',
                'Helvetica',
                '',
                5.3,
                4.0
            );
            $this->drawPdfCellAutoFont(
                $pdf,
                $columnLayout['processed']['x'],
                $y,
                $processedDateTime,
                $columnLayout['processed']['width'],
                $textHeight,
                'C',
                'Helvetica',
                '',
                5.0,
                4.0
            );

            $this->drawPdfCellAutoFont(
                $pdf,
                $columnLayout['minutes']['x'],
                $y,
                $minutesProcessed,
                $columnLayout['minutes']['width'],
                $textHeight,
                'C',
                'Helvetica',
                '',
                5.3,
                4.2
            );

            if ($signaturePath) {
                $this->drawPdfImageInBox(
                    $pdf,
                    $signaturePath,
                    $columnLayout['signature']['x'],
                    $y,
                    $columnLayout['signature']['width'],
                    $columnLayout['signature']['height']
                );
            }
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
        $text = $this->fitPdfText($pdf, $this->preparePdfText($text), $width);

        $pdf->SetXY($centerX - ($width / 2), $centerY - ($height / 2));
        $pdf->Cell($width, $height, $text, 0, 0, $align);
    }

    private function drawPdfCellAutoFont(
        Fpdi $pdf,
        float $centerX,
        float $centerY,
        string $text,
        float $width,
        float $height,
        string $align = 'C',
        string $fontFamily = 'Helvetica',
        string $fontStyle = '',
        float $startFontSize = 4.0,
        float $minFontSize = 2.7
    ): void {
        $text = trim($this->preparePdfText($text));

        if ($text === '') {
            return;
        }

        $fontSize = $startFontSize;

        while ($fontSize > $minFontSize) {
            $pdf->SetFont($fontFamily, $fontStyle, $fontSize);

            if ($pdf->GetStringWidth($text) <= $width) {
                break;
            }

            $fontSize -= 0.05;
        }

        if ($pdf->GetStringWidth($text) > $width) {
            $text = $this->fitPdfText($pdf, $text, $width);
        }

        $pdf->SetXY($centerX - ($width / 2), $centerY - ($height / 2));
        $pdf->Cell($width, $height, $text, 0, 0, $align);
        $pdf->SetFont($fontFamily, $fontStyle, $startFontSize);
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
        $lines = preg_split('/\r\n|\r|\n/', trim($this->preparePdfText($text)));

        if (! $lines || count($lines) === 0) {
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

    private function drawPdfWrappedCell(
        Fpdi $pdf,
        float $centerX,
        float $centerY,
        string $text,
        float $width,
        float $height,
        string $align = 'C',
        int $maxLines = 2
    ): void {
        $lines = $this->wrapPdfText($pdf, $text, $width, $maxLines);

        if (count($lines) === 0) {
            return;
        }

        $lineHeight = min(3.2, max(2.8, ($height - 0.6) / max(count($lines), 1)));
        $totalHeight = count($lines) * $lineHeight;
        $startY = $centerY - ($totalHeight / 2);

        foreach ($lines as $lineIndex => $line) {
            $pdf->SetXY($centerX - ($width / 2), $startY + ($lineIndex * $lineHeight));
            $pdf->Cell($width, $lineHeight, $line, 0, 0, $align);
        }
    }

    private function wrapPdfText(Fpdi $pdf, string $text, float $maxWidth, int $maxLines = 2): array
    {
        $text = trim($this->preparePdfText((string) $text));

        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) $paragraph);

            if ($paragraph === '') {
                continue;
            }

            $words = preg_split('/\s+/', $paragraph) ?: [];
            $current = '';

            foreach ($words as $word) {
                $candidate = $current === '' ? $word : $current . ' ' . $word;

                if ($pdf->GetStringWidth($candidate) <= $maxWidth) {
                    $current = $candidate;

                    continue;
                }

                if ($current !== '') {
                    $lines[] = $this->fitPdfText($pdf, $current, $maxWidth);
                    $current = '';

                    if (count($lines) >= $maxLines) {
                        return array_slice($lines, 0, $maxLines);
                    }
                }

                $current = $this->fitPdfText($pdf, $word, $maxWidth);
            }

            if ($current !== '') {
                $lines[] = $this->fitPdfText($pdf, $current, $maxWidth);

                if (count($lines) >= $maxLines) {
                    return array_slice($lines, 0, $maxLines);
                }
            }
        }

        return array_slice($lines, 0, $maxLines);
    }

    private function buildPdfContactBlock(string $email, string $contact): string
    {
        $parts = array_values(array_filter([
            trim($email),
            trim($contact),
        ], fn($value) => $value !== ''));

        return implode('  |  ', $parts);
    }

    private function formatPdfPatientNameSurnameFirst(?string $fullName): string
    {
        $fullName = trim((string) $fullName);

        if ($fullName === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $parts = array_values(array_filter($parts, fn($part) => trim((string) $part) !== ''));

        if (count($parts) === 1) {
            return $parts[0];
        }

        $surname = array_pop($parts);
        $firstName = array_shift($parts) ?? '';
        $remainingNames = trim(implode(' ', $parts));
        $givenNames = trim($firstName . ' ' . $remainingNames);

        return trim($surname . ', ' . $givenNames);
    }

    private function formatPdfDurationMinutes(int $seconds): string
    {
        if ($seconds <= 0) {
            return '';
        }

        return (string) (int) ceil($seconds / 60);
    }

    private function getStoredSignaturePath(?string $relativePath): ?string
    {
        $relativePath = trim((string) $relativePath);

        if ($relativePath === '') {
            return null;
        }

        $absolutePath = storage_path('app/public/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

        return file_exists($absolutePath) ? $absolutePath : null;
    }

    private function drawPdfImageInBox(
        Fpdi $pdf,
        string $imagePath,
        float $centerX,
        float $centerY,
        float $boxWidth,
        float $boxHeight
    ): void {
        try {
            [$imageWidth, $imageHeight] = @getimagesize($imagePath) ?: [0, 0];

            if ($imageWidth <= 0 || $imageHeight <= 0) {
                return;
            }

            $fitRatio = min($boxWidth / $imageWidth, $boxHeight / $imageHeight);
            $drawWidth = $imageWidth * $fitRatio;
            $drawHeight = $imageHeight * $fitRatio;
            $x = $centerX - ($drawWidth / 2);
            $y = $centerY - ($drawHeight / 2);

            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($centerX - ($boxWidth / 2), $centerY - ($boxHeight / 2), $boxWidth, $boxHeight, 'F');
            $pdf->Image($imagePath, $x, $y, $drawWidth, $drawHeight);
            $pdf->Image($imagePath, $x + 0.10, $y, $drawWidth, $drawHeight);
            $pdf->Image($imagePath, $x, $y + 0.08, $drawWidth, $drawHeight);
            $pdf->Image($imagePath, $x + 0.10, $y + 0.08, $drawWidth, $drawHeight);
        } catch (\Throwable $e) {
            // Skip signature rendering when the stored image cannot be loaded.
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
            $text = substr($text, 0, -1);
        }

        return trim($text) . '...';
    }

    private function preparePdfText(string $text): string
    {
        $text = trim($text);

        if ($text === '' || ! mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        return mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
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

    private function getPatientDemographics($patient): array
    {
        $birthdateValue = $patient->birthdate ?? $patient->user?->birthdate ?? null;
        $genderValue = trim((string) ($patient->gender ?? $patient->user?->gender ?? ''));

        $birthdateShort = '';
        $age = '';

        if (! empty($birthdateValue)) {
            try {
                $parsedBirthdate = $birthdateValue instanceof Carbon
                    ? $birthdateValue
                    : Carbon::parse($birthdateValue);

                $birthdateShort = $parsedBirthdate->format('m/d/y');
                $age = (string) $parsedBirthdate->age;
            } catch (\Throwable) {
                $birthdateShort = '';
                $age = '';
            }
        }

        return [
            'birthdate_short' => $birthdateShort,
            'age' => $age,
            'gender' => $genderValue,
        ];
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
        $pdf->Rect(706.8, 67.0, 42, 6.5, 'F');

        $pdf->SetFont('Helvetica', '', 4.1);
        $pdf->SetXY(707.9, 67.2);
        $pdf->Cell(40, 5, now()->format('F Y'), 0, 0, 'L');
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

            if (! $tooth || ! isset($toothMap[$tooth])) {
                continue;
            }

            $pos = $toothMap[$tooth];
            $surfaces = is_array($item['surfaces'] ?? null) ? $item['surfaces'] : [];
            $wholeRecord = $this->dhrOdontogramRecord($item['status'] ?? null)
                ?: $this->dhrOdontogramRecord($item['threeD'] ?? ($item['three_d'] ?? null));
            $hasSurfaceMarks = false;

            foreach (['top', 'right', 'bottom', 'left', 'center'] as $surfaceKey) {
                $record = $this->dhrOdontogramRecord($surfaces[$surfaceKey] ?? null);

                if (! $record) {
                    continue;
                }

                $hasSurfaceMarks = true;
                $this->drawDentalHealthToothSurfaceMark($pdf, $pos['x'], $pos['y'], $surfaceKey, $record);
            }

            if ($wholeRecord && ! $hasSurfaceMarks) {
                $this->drawDentalHealthWholeToothMark($pdf, $pos['x'], $pos['y'], $wholeRecord);
            }

            $displayRecord = $wholeRecord;

            if (! $displayRecord) {
                foreach (['center', 'top', 'right', 'bottom', 'left'] as $surfaceKey) {
                    $displayRecord = $this->dhrOdontogramRecord($surfaces[$surfaceKey] ?? null);

                    if ($displayRecord) {
                        break;
                    }
                }
            }

            if (! $displayRecord) {
                continue;
            }

            $this->drawDentalHealthStatusBoxMark(
                $pdf,
                $tooth,
                $pos['x'],
                $pos['y'],
                $displayRecord
            );
        }

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
    }

    private function dhrOdontogramRecord($record): ?array
    {
        if (! is_array($record)) {
            return null;
        }

        $code = trim((string) ($record['code'] ?? ''));

        if ($code === '') {
            return null;
        }

        return [
            'code' => $code,
            'color' => $this->dhrOdontogramColor($record['colorHex'] ?? ($record['color_hex'] ?? null)),
        ];
    }

    private function dhrOdontogramColor(?string $hex): array
    {
        $hex = ltrim(trim((string) $hex), '#');

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [239, 68, 68];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function drawDentalHealthWholeToothMark(Fpdi $pdf, float $centerX, float $centerY, array $record): void
    {
        $this->drawDentalHealthFilledCircle($pdf, $centerX, $centerY - 2.2, 8.8, $record['color']);
    }

    private function drawDentalHealthToothSurfaceMark(
        Fpdi $pdf,
        float $centerX,
        float $centerY,
        string $surface,
        array $record
    ): void {
        if ($surface === 'center') {
            $this->drawDentalHealthFilledCircle($pdf, $centerX, $centerY - 2.2, 4.5, $record['color']);
            return;
        }

        $outer = 9.8;
        $inner = 3.7;
        $centerY -= 2.2;

        if (in_array($surface, ['top', 'right', 'bottom', 'left'], true)) {
            $this->drawDentalHealthCurvedSurfaceCap(
                $pdf,
                $centerX,
                $centerY,
                $outer,
                $inner,
                $surface,
                $record['color']
            );
            return;
        }
    }

    private function drawDentalHealthCurvedSurfaceCap(
        Fpdi $pdf,
        float $centerX,
        float $centerY,
        float $outerRadius,
        float $innerRadius,
        string $surface,
        array $rgb
    ): void {
        $anglesBySurface = [
            'top' => [225, 315],
            'right' => [315, 45],
            'bottom' => [45, 135],
            'left' => [135, 225],
        ];

        if (! isset($anglesBySurface[$surface])) {
            return;
        }

        [$startAngle, $endAngle] = $anglesBySurface[$surface];

        $commands = $this->dhrRingSegmentPath(
            $centerX,
            $centerY,
            $outerRadius,
            $innerRadius,
            $startAngle,
            $endAngle
        );

        $this->drawDentalHealthRawPath($pdf, $commands, $rgb);
    }

    private function dhrRingSegmentPath(
        float $centerX,
        float $centerY,
        float $outerRadius,
        float $innerRadius,
        float $startAngle,
        float $endAngle
    ): array {
        $outerPoints = $this->dhrArcPoints($centerX, $centerY, $outerRadius, $startAngle, $endAngle, true);
        $innerPoints = $this->dhrArcPoints($centerX, $centerY, $innerRadius, $endAngle, $startAngle, false);
        $commands = [['m', $outerPoints[0][0], $outerPoints[0][1]]];

        foreach (array_slice($outerPoints, 1) as $point) {
            $commands[] = ['l', $point[0], $point[1]];
        }

        foreach ($innerPoints as $point) {
            $commands[] = ['l', $point[0], $point[1]];
        }

        $commands[] = ['l', $outerPoints[0][0], $outerPoints[0][1]];

        return $commands;
    }

    private function dhrArcPoints(
        float $centerX,
        float $centerY,
        float $radius,
        float $startAngle,
        float $endAngle,
        bool $clockwise = true
    ): array {
        $delta = $endAngle - $startAngle;

        if ($clockwise && $delta < 0) {
            $delta += 360;
        } elseif (! $clockwise && $delta > 0) {
            $delta -= 360;
        }

        $segments = max(4, (int) ceil(abs($delta) / 10));
        $points = [];

        for ($i = 0; $i <= $segments; $i++) {
            $points[] = $this->dhrPointOnCircle(
                $centerX,
                $centerY,
                $radius,
                $startAngle + (($delta / $segments) * $i)
            );
        }

        return $points;
    }

    private function dhrPointOnCircle(float $centerX, float $centerY, float $radius, float $angle): array
    {
        $radians = deg2rad($angle);

        return [
            $centerX + ($radius * cos($radians)),
            $centerY + ($radius * sin($radians)),
        ];
    }

    private function drawDentalHealthStatusBoxMark(
        Fpdi $pdf,
        int $tooth,
        float $toothCenterX,
        float $toothCenterY,
        array $record
    ): void {
        [$r, $g, $b] = $record['color'];
        $boxWidth = 21.0;
        $boxHeight = 16.6;
        $statusBoxOffset = 29.5;
        $boxCenterY = $this->isDentalHealthUpperTooth($tooth)
            ? $toothCenterY - $statusBoxOffset
            : $toothCenterY + $statusBoxOffset;

        $boxX = $toothCenterX - ($boxWidth / 2);
        $boxY = $boxCenterY - ($boxHeight / 2);
        $inset = 1.0;

        $pdf->SetFillColor($r, $g, $b);
        $pdf->SetDrawColor($r, $g, $b);
        $pdf->Rect(
            $boxX + $inset,
            $boxY + $inset,
            $boxWidth - ($inset * 2),
            7.0,
            'F'
        );

        $codeLength = max(1, mb_strlen($record['code']));
        $fontSize = $codeLength >= 3 ? 4.5 : ($codeLength === 2 ? 5.1 : 6.2);

        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetFont('Helvetica', 'B', $fontSize);
        $pdf->SetXY($boxX, $boxY + 9.0);
        $pdf->Cell($boxWidth, 7.2, $record['code'], 0, 0, 'C');
    }

    private function isDentalHealthUpperTooth(int $tooth): bool
    {
        return ($tooth >= 11 && $tooth <= 28) ||
            ($tooth >= 51 && $tooth <= 65);
    }

    private function drawDentalHealthFilledCircle(
        Fpdi $pdf,
        float $centerX,
        float $centerY,
        float $radius,
        array $rgb
    ): void {
        $control = $radius * 0.5522847498;
        $commands = [
            ['m', $centerX + $radius, $centerY],
            ['c', $centerX + $radius, $centerY + $control, $centerX + $control, $centerY + $radius, $centerX, $centerY + $radius],
            ['c', $centerX - $control, $centerY + $radius, $centerX - $radius, $centerY + $control, $centerX - $radius, $centerY],
            ['c', $centerX - $radius, $centerY - $control, $centerX - $control, $centerY - $radius, $centerX, $centerY - $radius],
            ['c', $centerX + $control, $centerY - $radius, $centerX + $radius, $centerY - $control, $centerX + $radius, $centerY],
        ];

        $this->drawDentalHealthRawPath($pdf, $commands, $rgb);
    }

    private function drawDentalHealthFilledPolygon(Fpdi $pdf, array $points, array $rgb): void
    {
        if (count($points) < 3) {
            return;
        }

        $commands = [['m', $points[0][0], $points[0][1]]];

        foreach (array_slice($points, 1) as $point) {
            $commands[] = ['l', $point[0], $point[1]];
        }

        $this->drawDentalHealthRawPath($pdf, $commands, $rgb);
    }

    private function drawDentalHealthRawPath(Fpdi $pdf, array $commands, array $rgb): void
    {
        [$r, $g, $b] = $rgb;
        [$scale, $pageHeight] = $this->dhrPdfMetrics($pdf);

        $path = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);

        foreach ($commands as $command) {
            $operator = $command[0] ?? '';

            if ($operator === 'm' || $operator === 'l') {
                $path .= sprintf(
                    ' %.2F %.2F %s',
                    $command[1] * $scale,
                    ($pageHeight - $command[2]) * $scale,
                    $operator
                );
                continue;
            }

            if ($operator === 'c') {
                $path .= sprintf(
                    ' %.2F %.2F %.2F %.2F %.2F %.2F c',
                    $command[1] * $scale,
                    ($pageHeight - $command[2]) * $scale,
                    $command[3] * $scale,
                    ($pageHeight - $command[4]) * $scale,
                    $command[5] * $scale,
                    ($pageHeight - $command[6]) * $scale
                );
            }
        }

        $path .= ' h f';

        $this->dhrPdfOut($pdf, $path);
    }

    private function dhrPdfMetrics(Fpdi $pdf): array
    {
        return \Closure::bind(
            fn() => [$this->k, $this->h],
            $pdf,
            get_class($pdf)
        )();
    }

    private function dhrPdfOut(Fpdi $pdf, string $command): void
    {
        \Closure::bind(
            function (string $command): void {
                $this->_out($command);
            },
            $pdf,
            get_class($pdf)
        )($command);
    }

    private function dhrContrastingTextColor(array $rgb): array
    {
        [$r, $g, $b] = $rgb;
        $luminance = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $luminance < 140 ? [255, 255, 255] : [0, 0, 0];
    }
}
