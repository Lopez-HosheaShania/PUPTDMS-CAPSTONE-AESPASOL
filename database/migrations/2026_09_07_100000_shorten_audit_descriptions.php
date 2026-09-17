<?php

use App\Support\AuditDescription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_log_descriptions')) {
            Schema::create('audit_log_descriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audit_log_id')->unique()->constrained('audit_logs')->cascadeOnDelete();
                $table->longText('full_description')->nullable();
            });
        }
        DB::transaction(function () {
            DB::table('audit_logs')->orderBy('id')->chunkById(500, function ($logs) {
                foreach ($logs as $log) {
                    $summary = AuditDescription::summarize($log->description, $log->action, $log->module);
                    if ($summary === $log->description) {
                        continue;
                    }
                    DB::table('audit_log_descriptions')->insertOrIgnore(['audit_log_id' => $log->id, 'full_description' => $log->description]);
                    DB::table('audit_logs')->where('id', $log->id)->update(['description' => $summary]);
                }
            });
        });
    }

    public function down(): void
    {
        DB::table('audit_log_descriptions')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('audit_logs')->where('id', $row->audit_log_id)->update(['description' => $row->full_description]);
            }
        });
        Schema::drop('audit_log_descriptions');
    }
};
