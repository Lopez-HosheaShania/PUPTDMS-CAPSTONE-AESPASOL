<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('audit_logs')
            ->where('action', 'like', '%error%')
            ->where('description', 'like', 'HTTP %')
            ->update([
                'description' => DB::raw("REPLACE(description, 'HTTP ', '')"),
            ]);
    }

    public function down(): void
    {
        // The original HTTP prefix is intentionally not restored.
    }
};
