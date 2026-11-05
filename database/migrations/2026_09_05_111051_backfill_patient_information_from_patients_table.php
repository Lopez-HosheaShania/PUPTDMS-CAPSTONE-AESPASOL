<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copy the existing patient information into the normalized table.
     *
     * The original columns in patients are intentionally kept during
     * the transition period.
     */
    public function up(): void
    {
        if (
            ! Schema::hasTable('patients') ||
            ! Schema::hasTable('patient_information')
        ) {
            return;
        }

        DB::table('patients')
            ->orderBy('id')
            ->chunkById(200, function ($patients): void {
                foreach ($patients as $patient) {
                    DB::table('patient_information')->updateOrInsert(
                        [
                            'patient_id' => $patient->id,
                        ],
                        [
                            'phone' => $patient->phone,
                            'birthdate' => $patient->birthdate,
                            'gender' => $patient->gender,

                            'place_of_birth' => $patient->place_of_birth,
                            'height_m' => $patient->height_m,
                            'weight_kg' => $patient->weight_kg,

                            'faculty_code' => $patient->faculty_code,
                            'student_no' => $patient->student_no,

                            'course_code' => $patient->course_code,
                            'course_name' => $patient->course_name,
                            'year_level' => $patient->year_level,
                            'section' => $patient->section,

                            'is_pwd' => $patient->is_pwd ?? false,
                            'is_senior' => $patient->is_senior ?? false,
                            'address' => $patient->address,

                            'created_at' => $patient->created_at ?? now(),
                            'updated_at' => $patient->updated_at ?? now(),
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};