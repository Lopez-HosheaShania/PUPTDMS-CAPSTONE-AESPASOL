<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\DptDocumentTemplateSeeder)->run();
    }

    public function down(): void
    {
        DB::table('document_templates')->whereIn('code', ['DPT-EMERGENCY', 'DPT-NON-EMERGENCY'])->delete();
    }
};
