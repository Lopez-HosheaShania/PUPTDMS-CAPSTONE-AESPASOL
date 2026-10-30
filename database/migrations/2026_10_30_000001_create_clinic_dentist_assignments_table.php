<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_dentist_assignments', function (Blueprint $table) {
            $table->enum('slot', ['primary'])->primary();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('dentist_transition_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed the lockable clinic position, never guess between existing accounts.
        DB::table('clinic_dentist_assignments')->insert([
            'slot' => 'primary', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_dentist_assignments');
    }
};
