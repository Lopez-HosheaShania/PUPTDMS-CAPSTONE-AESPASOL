<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $informationColumns = [
        'phone',
        'birthdate',
        'gender',
        'place_of_birth',
        'height_m',
        'weight_kg',
        'faculty_code',
        'student_no',
        'course_code',
        'course_name',
        'year_level',
        'section',
        'is_pwd',
        'is_senior',
        'address',
    ];

  public function up(): void
{
    $isPretending = DB::connection()->pretending();

    if (! $isPretending) {
        if (! Schema::hasTable('patient_information')) {
            throw new \RuntimeException(
                'Cannot remove patient information columns because patient_information does not exist.'
            );
        }

        $missingInformation = DB::table('patients as p')
            ->leftJoin(
                'patient_information as pi',
                'pi.patient_id',
                '=',
                'p.id'
            )
            ->whereNull('pi.id')
            ->count();

        if ($missingInformation > 0) {
            throw new \RuntimeException(
                "Cannot remove patient information columns. {$missingInformation} patient(s) have no patient_information row."
            );
        }
    }

    $columnsToDrop = $isPretending
        ? $this->informationColumns
        : array_values(
            array_filter(
                $this->informationColumns,
                fn (string $column) =>
                    Schema::hasColumn(
                        'patients',
                        $column
                    )
            )
        );

    if ($columnsToDrop === []) {
        return;
    }

    Schema::table(
        'patients',
        function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn(
                $columnsToDrop
            );
        }
    );
}
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'phone')) {
                $table->string('phone')->nullable();
            }

            if (! Schema::hasColumn('patients', 'birthdate')) {
                $table->date('birthdate')->nullable();
            }

            if (! Schema::hasColumn('patients', 'gender')) {
                $table->string('gender')->nullable();
            }

            if (! Schema::hasColumn('patients', 'place_of_birth')) {
                $table->string('place_of_birth')->nullable();
            }

            if (! Schema::hasColumn('patients', 'height_m')) {
                $table->decimal('height_m', 5, 2)->nullable();
            }

            if (! Schema::hasColumn('patients', 'weight_kg')) {
                $table->decimal('weight_kg', 5, 2)->nullable();
            }

            if (! Schema::hasColumn('patients', 'faculty_code')) {
                $table->string('faculty_code')->nullable();
            }

            if (! Schema::hasColumn('patients', 'student_no')) {
                $table->string('student_no')->nullable();
            }

            if (! Schema::hasColumn('patients', 'course_code')) {
                $table->string('course_code')->nullable();
            }

            if (! Schema::hasColumn('patients', 'course_name')) {
                $table->string('course_name')->nullable();
            }

            if (! Schema::hasColumn('patients', 'year_level')) {
                $table->integer('year_level')->nullable();
            }

            if (! Schema::hasColumn('patients', 'section')) {
                $table->string('section')->nullable();
            }

            if (! Schema::hasColumn('patients', 'is_pwd')) {
                $table->boolean('is_pwd')->default(false);
            }

            if (! Schema::hasColumn('patients', 'is_senior')) {
                $table->boolean('is_senior')->default(false);
            }

            if (! Schema::hasColumn('patients', 'address')) {
                $table->text('address')->nullable();
            }
        });


        DB::table('patient_information')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $information) {
                    DB::table('patients')
                        ->where(
                            'id',
                            $information->patient_id
                        )
                        ->update([
                            'phone' =>
                                $information->phone,

                            'birthdate' =>
                                $information->birthdate,

                            'gender' =>
                                $information->gender,

                            'place_of_birth' =>
                                $information->place_of_birth,

                            'height_m' =>
                                $information->height_m,

                            'weight_kg' =>
                                $information->weight_kg,

                            'faculty_code' =>
                                $information->faculty_code,

                            'student_no' =>
                                $information->student_no,

                            'course_code' =>
                                $information->course_code,

                            'course_name' =>
                                $information->course_name,

                            'year_level' =>
                                $information->year_level,

                            'section' =>
                                $information->section,

                            'is_pwd' =>
                                $information->is_pwd,

                            'is_senior' =>
                                $information->is_senior,

                            'address' =>
                                $information->address,
                        ]);
                }
            });
    }
};