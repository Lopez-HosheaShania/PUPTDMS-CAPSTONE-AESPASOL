<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_case_classifications', function (Blueprint $table) {
            $table->foreignId('appointment_id')->primary()->constrained('appointments')->cascadeOnDelete();
            $table->boolean('is_emergency');
            $table->timestamps();
        });

        // Preserve historical classifications before walk-ins gain an explicit choice.
        DB::table('appointments')->select(['id', 'is_walk_in', 'created_at', 'updated_at'])
            ->chunkById(500, function ($appointments) {
                DB::table('appointment_case_classifications')->insert($appointments->map(fn ($appointment) => [
                    'appointment_id' => $appointment->id,
                    'is_emergency' => (bool) $appointment->is_walk_in,
                    'created_at' => $appointment->created_at,
                    'updated_at' => $appointment->updated_at,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_case_classifications');
    }
};
