<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GROUPS = [
        'daily_treatment_patients' => ['patient_name', 'patient_email', 'patient_phone', 'office_type', 'program_code', 'age', 'gender', 'is_senior', 'is_pwd'],
        'daily_treatment_signatures' => ['has_signature', 'signature_path'],
    ];

    private function columns(Blueprint $table, array $fields): void
    {
        foreach ($fields as $field) {
            match ($field) {
                'patient_name' => $table->string($field, 150),
                'patient_email' => $table->string($field, 190)->nullable(),
                'patient_phone' => $table->string($field, 30)->nullable(),
                'program_code' => $table->string($field, 50)->nullable(),
                'age' => $table->unsignedTinyInteger($field)->nullable(),
                'is_senior', 'is_pwd', 'has_signature' => $table->boolean($field)->default(false),
                'gender' => $table->enum($field, ['Male', 'Female', 'Other'])->nullable(),
                default => $table->string($field)->nullable(),
            };
        }
    }

    public function up(): void
    {
        $sourceColumns = Schema::getColumnListing('daily_treatment_records');
        foreach (self::GROUPS as $name => $fields) {
            if (! Schema::hasTable($name)) {
                Schema::create($name, function (Blueprint $table) use ($fields, $name) {
                    $table->id();
                    $table->foreignId('daily_treatment_record_id')->unique()
                        ->constrained('daily_treatment_records', indexName: $name === 'daily_treatment_patients' ? 'dt_patient_record_fk' : 'dt_signature_record_fk')->cascadeOnDelete();
                    $this->columns($table, $fields);
                    if ($name === 'daily_treatment_patients') {
                        $table->index('patient_name');
                        $table->index('office_type');
                        $table->index('program_code');
                    }
                });
            }
        }
        DB::transaction(function () {
            DB::table('daily_treatment_records')->orderBy('id')->chunkById(500, function ($records) {
                foreach ($records as $record) {
                    foreach (self::GROUPS as $table => $fields) {
                        $values = ['daily_treatment_record_id' => $record->id];
                        foreach ($fields as $field) {
                            $values[$field] = $record->{$field} ?? (in_array($field, ['is_senior', 'is_pwd', 'has_signature'], true) ? 0 : null);
                        }
                        DB::table($table)->updateOrInsert(['daily_treatment_record_id' => $record->id], $values);
                        $saved = DB::table($table)->where('daily_treatment_record_id', $record->id)->first();
                        foreach ($fields as $field) {
                            if ($saved->{$field} !== $values[$field]) {
                                throw new RuntimeException('Daily treatment copy verification failed; original fields retained.');
                            }
                        }
                    }
                }
            });
        });
        $indexes = array_filter(['daily_treatment_records_patient_name_index', 'daily_treatment_records_office_type_index', 'daily_treatment_records_program_code_index'],
            fn ($index) => Schema::hasIndex('daily_treatment_records', $index));
        Schema::table('daily_treatment_records', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $index) {
                $table->dropIndex($index);
            }
        });
        Schema::table('daily_treatment_records', fn (Blueprint $table) => $table->dropColumn(
            array_values(array_intersect(array_merge(...array_values(self::GROUPS)), $sourceColumns))
        ));
    }

    public function down(): void
    {
        Schema::table('daily_treatment_records', function (Blueprint $table) {
            // Nullable while backfilling existing rows.
            $table->string('patient_name', 150)->nullable();
            $this->columns($table, array_values(array_diff(array_merge(...array_values(self::GROUPS)), ['patient_name'])));
            $table->index('patient_name');
            $table->index('office_type');
            $table->index('program_code');
        });
        foreach (self::GROUPS as $table => $fields) {
            DB::table($table)->orderBy('id')->chunkById(500, function ($rows) use ($fields) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($fields as $field) {
                        $values[$field] = $row->{$field};
                    }
                    DB::table('daily_treatment_records')->where('id', $row->daily_treatment_record_id)->update($values);
                }
            });
            Schema::drop($table);
        }
    }
};
