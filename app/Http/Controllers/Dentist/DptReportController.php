<?php

namespace App\Http\Controllers\Dentist;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Services\DptReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DptReportController extends Controller
{
    public function download(Request $request, DptReport $report)
    {
        abort_unless(Auth::check(), 403);
        $validated = $request->validate([
            'report_name' => ['required', 'string', 'max:100'],
            'document_template_id' => ['required', 'integer', 'exists:document_templates,id'],
            'date_from' => ['required', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $template = DocumentTemplate::active()->findOrFail($validated['document_template_id']);
        $type = $template->document_type;
        abort_unless(isset(DptReport::TEMPLATES[$type]), 422, 'Please select a DPT report template.');
        abort_unless(is_file(storage_path('app/report-templates/'.DptReport::TEMPLATES[$type]['file'])), 404, 'DPT PDF template was not found.');
        $from = Carbon::parse($validated['date_from'])->startOfDay();
        $to = Carbon::parse($validated['date_to'] ?? $validated['date_from'])->endOfDay();
        $records = $report->records($from, $to, $type === 'dpt_emergency');
        abort_if($records->isEmpty(), 422, 'No completed appointments found for the selected DPT report and date range.');
        $content = $report->pdf($type, $records, $from, $to, (int) $validated['quantity']);
        AuditLogger::log('download', 'dentist_reports', 'Downloaded '.$template->name.' for '.$records->count().' record(s).');
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['report_name']);

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$name.'.pdf"')
            ->header('Content-Length', (string) strlen($content))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
