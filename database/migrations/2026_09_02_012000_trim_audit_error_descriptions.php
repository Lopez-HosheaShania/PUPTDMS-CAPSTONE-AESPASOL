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
                    $description = $this->compactDescription($parts);

                    if ($description !== $log->description) {
                        DB::table('audit_logs')
                            ->where('id', $log->id)
                            ->update(['description' => $description]);
                    }
                }
            });
    }

    public function down(): void
    {
        // The removed request paths and file locations cannot be reconstructed.
    }

    private function compactDescription(array $parts): string
    {
        if (count($parts) >= 3 && preg_match('/^(GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS) https?:\/\//', $parts[2])) {
            return implode(' | ', array_slice($parts, 0, 2));
        }

        return implode(' | ', array_slice($parts, 0, 3));
    }
};
