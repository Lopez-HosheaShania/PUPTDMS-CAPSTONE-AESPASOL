<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_procedure_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_procedure_id')->unique()
                ->constrained('appointment_procedures')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
        });

        DB::transaction(function () {
            DB::table('appointment_procedures')->orderBy('id')->chunkById(500, function ($procedures) {
                foreach ($procedures as $procedure) {
                    $values = [
                        'appointment_procedure_id' => $procedure->id,
                        'started_at' => $procedure->procedure_started_at,
                        'completed_at' => $procedure->procedure_completed_at,
                        'duration_seconds' => $procedure->procedure_duration_seconds,
                        'created_at' => $procedure->created_at,
                        'updated_at' => $procedure->updated_at,
                    ];
                    DB::table('appointment_procedure_timings')->insert($values);
                    $saved = (array) DB::table('appointment_procedure_timings')->where('appointment_procedure_id', $procedure->id)->first();
                    unset($saved['id']);
                    if ($saved !== $values) {
                        throw new RuntimeException('Timing verification failed; original timing columns retained.');
                    }
                }
            });
        });

        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->dropColumn(['procedure_started_at', 'procedure_completed_at', 'procedure_duration_seconds']);
        });
    }

    public function down(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->timestamp('procedure_started_at')->nullable();
            $table->timestamp('procedure_completed_at')->nullable();
            $table->unsignedInteger('procedure_duration_seconds')->nullable();
        });
        DB::table('appointment_procedure_timings')->orderBy('id')->chunkById(500, function ($timings) {
            foreach ($timings as $timing) {
                DB::table('appointment_procedures')->where('id', $timing->appointment_procedure_id)->update([
                    'procedure_started_at' => $timing->started_at,
                    'procedure_completed_at' => $timing->completed_at,
                    'procedure_duration_seconds' => $timing->duration_seconds,
                ]);
            }
        });
        Schema::dropIfExists('appointment_procedure_timings');
    }
};
