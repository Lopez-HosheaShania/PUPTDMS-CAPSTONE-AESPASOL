<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });
    }
};
