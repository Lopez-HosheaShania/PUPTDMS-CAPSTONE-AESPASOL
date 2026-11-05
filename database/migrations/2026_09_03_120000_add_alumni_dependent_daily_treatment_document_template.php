<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $contentPath = resource_path('views/admin/document-templates-defaults/daily-treatment.blade.php');

        if (! File::exists($contentPath)) {
            return;
        }

        $content = File::get($contentPath);
        $now = now();

        DB::table('document_templates')->updateOrInsert(
            ['code' => 'DTR-ALUMNI'],
            [
                'name' => 'Daily Treatment Record - Alumni / Dependent',
                'document_type' => 'daily_treatment_record',
                'category' => 'Record',
                'engine' => 'html',
                'output_format' => 'pdf',
                'header_content' => null,
                'content' => $content,
                'footer_content' => null,
                'paper_size' => 'Legal',
                'orientation' => 'landscape',
                'status' => 'active',
                'is_default' => true,
                'version' => 1,
                'notes' => 'Alumni / Dependent PDF variant for the daily treatment record.',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('document_templates')
            ->where('code', 'DTR-ALUMNI')
            ->delete();
    }
};
