<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_odontograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_procedure_id')->unique()
                ->constrained('appointment_procedures')->cascadeOnDelete();
            $table->json('odontogram_data')->nullable();
            $table->timestamps();
        });

        DB::table('appointment_procedures')->orderBy('id')->chunkById(500, function ($procedures) {
            DB::table('appointment_odontograms')->insert($procedures->map(fn ($procedure) => [
                'appointment_procedure_id' => $procedure->id,
                'odontogram_data' => $procedure->odontogram_data,
                'created_at' => $procedure->created_at,
                'updated_at' => $procedure->updated_at,
            ])->all());
        });

        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->dropColumn('odontogram_data');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->json('odontogram_data')->nullable();
        });

        DB::table('appointment_odontograms')->orderBy('id')->chunkById(500, function ($snapshots) {
            foreach ($snapshots as $snapshot) {
                DB::table('appointment_procedures')->where('id', $snapshot->appointment_procedure_id)
                    ->update(['odontogram_data' => $snapshot->odontogram_data]);
            }
        });

        Schema::dropIfExists('appointment_odontograms');
    }
};
