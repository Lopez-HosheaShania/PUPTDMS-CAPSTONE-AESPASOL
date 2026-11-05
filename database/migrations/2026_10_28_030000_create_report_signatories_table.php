<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('license_number', 60);
            $table->string('signature_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_signatories');
    }
};
