<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_information', function (Blueprint $table) {
            $table->dropIndex(['faculty_code']);
            $table->dropIndex(['student_no']);
            $table->dropIndex(['course_code']);
            $table->dropIndex('patient_information_student_target_index');
        });

        Schema::table('patient_information', function (Blueprint $table) {
            $table->dropColumn([
                'faculty_code',
                'student_no',
                'course_code',
                'course_name',
                'year_level',
                'section',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('patient_information', function (Blueprint $table) {
            $table->string('faculty_code')
                ->nullable()
                ->index()
                ->after('weight_kg');

            $table->string('student_no')
                ->nullable()
                ->index()
                ->after('faculty_code');

            $table->string('course_code')
                ->nullable()
                ->index()
                ->after('student_no');

            $table->string('course_name')
                ->nullable()
                ->after('course_code');

            $table->integer('year_level')
                ->nullable()
                ->after('course_name');

            $table->string('section')
                ->nullable()
                ->after('year_level');

            $table->index(['course_code', 'year_level', 'section'], 'patient_information_student_target_index');
        });


        if (Schema::hasTable('patient_student_information')) {
            DB::table('patient_student_information')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('patient_information')
                            ->where('patient_id', $row->patient_id)
                            ->update([
                                'student_no' => $row->student_no,
                                'course_code' => $row->course_code,
                                'course_name' => $row->course_name,
                                'year_level' => $row->year_level,
                                'section' => $row->section,
                            ]);
                    }
                });
        }

    

        if (Schema::hasTable('patient_faculty_information')) {
            DB::table('patient_faculty_information')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('patient_information')
                            ->where('patient_id', $row->patient_id)
                            ->update([
                                'faculty_code' => $row->faculty_code,
                            ]);
                    }
                });
        }
    }
};
