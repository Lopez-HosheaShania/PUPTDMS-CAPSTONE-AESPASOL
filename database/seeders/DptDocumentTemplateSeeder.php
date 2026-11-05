<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use App\Services\DptReport;
use Illuminate\Database\Seeder;

class DptDocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DptReport::TEMPLATES as $type => $template) {
            DocumentTemplate::firstOrCreate(['code' => $template['code']], [
                'name' => $template['name'], 'document_type' => $type,
                'category' => 'Report', 'engine' => 'html', 'output_format' => 'pdf',
                'content' => '<p>'.$template['name'].' — generate the official PDF from Reports using a date range.</p>',
                'paper_size' => 'A4', 'orientation' => 'portrait',
                'status' => 'active', 'is_default' => true, 'version' => 1,
                'notes' => 'Uses saved completed appointments and procedure processing times. Emergency means walk-in; non-emergency means scheduled.',
            ]);
        }
    }
}
