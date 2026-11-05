<?php

use App\Support\AppointmentOdontogramStorage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('appointment_odontograms')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                AppointmentOdontogramStorage::validate($row->odontogram_data === null
                    ? null : json_decode($row->odontogram_data, true, 512, JSON_THROW_ON_ERROR));
            }
        });

        Schema::table('appointment_odontograms', function (Blueprint $table) {
            $table->boolean('has_data')->default(false);
        });
        Schema::create('appointment_odontogram_teeth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_odontogram_id')->constrained('appointment_odontograms')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedInteger('tooth_number');
            $table->text('tooth_name')->nullable();
            $table->string('field_keys');
            $table->boolean('surfaces_null')->default(false);
            $table->unique(['appointment_odontogram_id', 'position'], 'odontogram_tooth_position_unique');
        });
        Schema::create('appointment_odontogram_markings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tooth_id')->constrained('appointment_odontogram_teeth')->cascadeOnDelete();
            $table->string('kind', 10);
            $table->string('surface', 10)->default('');
            $table->unsignedInteger('position');
            $table->text('code')->nullable();
            $table->text('label')->nullable();
            $table->text('color_hex')->nullable();
            $table->string('field_keys')->nullable();
            $table->unique(['tooth_id', 'kind', 'surface'], 'odontogram_marking_location_unique');
        });

        DB::transaction(function () {
            DB::table('appointment_odontograms')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $data = $row->odontogram_data === null ? null : json_decode($row->odontogram_data, true, 512, JSON_THROW_ON_ERROR);
                    AppointmentOdontogramStorage::replace(DB::connection(), $row->id, $data);
                    DB::table('appointment_odontograms')->where('id', $row->id)->update(['has_data' => $data !== null]);
                    if (AppointmentOdontogramStorage::read(DB::connection(), $row->id) !== $data) {
                        throw new RuntimeException('Odontogram snapshot verification failed; original JSON retained.');
                    }
                }
            });
        });

        Schema::table('appointment_odontograms', function (Blueprint $table) {
            $table->dropColumn('odontogram_data');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_odontograms', function (Blueprint $table) {
            $table->json('odontogram_data')->nullable();
        });
        DB::table('appointment_odontograms')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $data = AppointmentOdontogramStorage::read(DB::connection(), $row->id);
                DB::table('appointment_odontograms')->where('id', $row->id)->update([
                    'odontogram_data' => $data === null ? null : json_encode($data, JSON_THROW_ON_ERROR),
                ]);
            }
        });
        Schema::dropIfExists('appointment_odontogram_markings');
        Schema::dropIfExists('appointment_odontogram_teeth');
        Schema::table('appointment_odontograms', function (Blueprint $table) {
            $table->dropColumn('has_data');
        });
    }
};
