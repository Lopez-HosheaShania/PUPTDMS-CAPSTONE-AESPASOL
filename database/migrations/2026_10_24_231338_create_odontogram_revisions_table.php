<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('odontogram_revisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->foreignId('patient_odontogram_id')
                ->nullable()
                ->constrained('patient_odontograms')
                ->nullOnDelete();

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->nullOnDelete();

            $table->unsignedInteger('version_number');

            $table->json('odontogram_data');

            $table->json('previous_odontogram_data')
                ->nullable();

            $table->json('changes')
                ->nullable();

            $table->string('reason_category');

            $table->text('reason_notes');

            $table->foreignId('changed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('changed_by_role');

            $table->string('ip_address', 45)
                ->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            $table->index(
                ['patient_id', 'created_at'],
                'odontogram_revisions_patient_created_index'
            );

            $table->index(
                ['appointment_id', 'version_number'],
                'odontogram_revisions_appointment_version_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odontogram_revisions');
    }
};