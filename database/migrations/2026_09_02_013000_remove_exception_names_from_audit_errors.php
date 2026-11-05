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
            ->orderBy('id')
            ->chunkById(100, function ($logs): void {
                foreach ($logs as $log) {
                    $parts = explode(' | ', (string) $log->description);

                    if (count($parts) < 3 || ! str_ends_with($parts[1], 'Exception')) {
                        continue;
                    }

                    DB::table('audit_logs')
                        ->where('id', $log->id)
                        ->update([
                            'description' => implode(' | ', [$parts[0], $parts[2]]),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Exception names removed from historical descriptions cannot be reconstructed.
    }
};
