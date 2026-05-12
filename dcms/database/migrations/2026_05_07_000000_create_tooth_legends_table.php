<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tooth_legends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tooth_id')->constrained('teeth')->onDelete('cascade');
            $table->foreignId('legend_id')->constrained('legends')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['tooth_id', 'legend_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_legends');
    }
};