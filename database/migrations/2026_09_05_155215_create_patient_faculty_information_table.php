<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_faculty_information', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->unique()
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->string('faculty_code')->nullable()->index();

            $table->timestamps();
        });

        $now = now();

        DB::table('patients as p')
            ->join(
                'patient_information as pi',
                'pi.patient_id',
                '=',
                'p.id'
            )
            ->where('p.classification', 'faculty')
            ->select([
                'p.id as patient_id',
                'pi.faculty_code',
            ])
            ->orderBy('p.id')
            ->chunk(100, function ($rows) use ($now) {
                foreach ($rows as $row) {
                    DB::table('patient_faculty_information')
                        ->updateOrInsert(
                            [
                                'patient_id' => $row->patient_id,
                            ],
                            [
                                'faculty_code' => $row->faculty_code,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]
                        );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'patient_faculty_information'
        );
    }
};