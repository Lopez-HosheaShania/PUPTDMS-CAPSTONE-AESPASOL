<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\MonthlyDentalReportTemplateSeeder)->run();
    }

    public function down(): void
    {
        // Retain template metadata and any user edits when rolling back this registration.
    }
};
