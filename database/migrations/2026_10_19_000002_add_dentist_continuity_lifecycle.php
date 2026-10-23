<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dentist_transitions', function (Blueprint $table) {
            $table->string('coverage_type', 20)->default('permanent');
            $table->date('effective_date')->nullable(); 
            $table->date('end_date')->nullable();
            $table->timestamp('activated_at')->nullable();
        });
        Schema::create('patient_care_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('dentist_transition_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_care_assignments');
        Schema::table('dentist_transitions', fn (Blueprint $table) => $table->dropColumn([
            'coverage_type', 'effective_date', 'end_date', 'activated_at',
        ]));
    }
};
