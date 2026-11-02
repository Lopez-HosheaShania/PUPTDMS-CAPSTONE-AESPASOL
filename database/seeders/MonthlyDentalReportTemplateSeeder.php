<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

class MonthlyDentalReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DocumentTemplate::withTrashed()->firstOrCreate(['code' => 'MREP-DEFAULT'], [
            'name' => 'Monthly Dental Report', 'document_type' => 'monthly_report',
            'category' => 'Report', 'engine' => 'html', 'output_format' => 'pdf',
            'content' => '<p>Monthly Dental Report — generate the official PDF from Reports after reviewing the patient and service counts.</p>',
            'paper_size' => 'A4', 'orientation' => 'landscape',
            'status' => 'active', 'is_default' => true, 'version' => 1,
            'notes' => 'Uses dental-monthly-report-template.pdf, completed visits, and reviewed generation-only counts. Tooth-based procedures count each tooth once per category per visit.',
        ]);
    }
}
