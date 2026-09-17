<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_information', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->unique()
                ->constrained('patients')
                ->cascadeOnDelete();

            // Basic patient information
            $table->string('phone')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();

            // Personal information
            $table->string('place_of_birth')->nullable();
            $table->decimal('height_m', 5, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();

            // Institutional identity
            $table->string('faculty_code')->nullable()->index();
            $table->string('student_no')->nullable()->index();

            // Student academic information
            $table->string('course_code')->nullable()->index();
            $table->string('course_name')->nullable();
            $table->integer('year_level')->nullable();
            $table->string('section')->nullable();

            // Additional patient information
            $table->boolean('is_pwd')->default(false);
            $table->boolean('is_senior')->default(false);
            $table->text('address')->nullable();

            $table->timestamps();

            $table->index(
                ['course_code', 'year_level', 'section'],
                'patient_information_student_target_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_information');
    }
};