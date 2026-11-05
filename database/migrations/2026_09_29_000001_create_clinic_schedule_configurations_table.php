<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_schedule_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_schedule_id')
                ->unique()
                ->constrained('clinic_schedules')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('slot_duration_minutes')->default(60);
            $table->timestamps();
        });

        $now = now();

        DB::table('clinic_schedules')
            ->select('id', 'created_at', 'updated_at')
            ->orderBy('id')
            ->chunkById(500, function ($schedules) use ($now) {
                foreach ($schedules as $schedule) {
                    DB::table('clinic_schedule_configurations')->insert([
                        'clinic_schedule_id' => $schedule->id,
                        'slot_duration_minutes' => 60,
                        'created_at' => $schedule->created_at ?? $now,
                        'updated_at' => $schedule->updated_at ?? $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_schedule_configurations');
    }
};
