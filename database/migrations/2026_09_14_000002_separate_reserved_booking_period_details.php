<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GROUPS = [
        'reserved_booking_period_schedules' => ['reserved_date', 'active_reserved_date', 'start_time', 'end_time', 'is_active'],
        'reserved_booking_period_configurations' => ['booking_mode', 'timeslot_duration_minutes', 'max_capacity', 'restrict_services'],
        'reserved_booking_period_targets' => ['target_patient_type', 'program_code', 'year_level', 'section'],
    ];

    private const CHECKS = [
        'reserved_period_valid_time_chk' => ['reserved_booking_period_schedules', 'start_time < end_time'],
        'reserved_period_capacity_chk' => ['reserved_booking_period_configurations', 'max_capacity BETWEEN 1 AND 500'],
        'reserved_period_duration_chk' => ['reserved_booking_period_configurations', "(booking_mode = 'timeslot' AND timeslot_duration_minutes BETWEEN 5 AND 240 AND MOD(timeslot_duration_minutes, 5) = 0) OR (booking_mode = 'date_only' AND timeslot_duration_minutes IS NULL)"],
        'reserved_period_target_fields_chk' => ['reserved_booking_period_targets', "(target_patient_type = 'student' AND program_code IS NOT NULL AND year_level IS NOT NULL AND section IS NOT NULL) OR (target_patient_type <> 'student' AND program_code IS NULL AND year_level IS NULL AND section IS NULL)"],
    ];

    private function columns(Blueprint $table, array $fields, bool $nullable = false, bool $change = false): void
    {
        foreach ($fields as $field) {
            $column = match ($field) {
                'reserved_date', 'active_reserved_date' => $table->date($field),
                'start_time', 'end_time' => $table->time($field),
                'is_active' => $table->boolean($field)->default(true),
                'restrict_services' => $table->boolean($field)->default(false),
                'booking_mode' => $table->enum($field, ['timeslot', 'date_only']),
                'target_patient_type' => $table->enum($field, ['student', 'faculty', 'administrative', 'guest']),
                'timeslot_duration_minutes', 'max_capacity' => $table->unsignedSmallInteger($field),
                'year_level' => $table->unsignedTinyInteger($field),
                default => $table->string($field, 50),
            };
            $column->nullable($nullable || in_array($field, ['active_reserved_date', 'timeslot_duration_minutes', 'program_code', 'year_level', 'section'], true));
            if ($change) {
                $column->change();
            }
        }
    }

    private function checks(bool $toDetails): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        $version = strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version);
        $drop = str_contains($version, 'mariadb') ? 'DROP CONSTRAINT' : 'DROP CHECK';
        foreach (self::CHECKS as $name => [$detail, $expression]) {
            $source = $toDetails ? 'reserved_booking_periods' : $detail;
            $destination = $toDetails ? $detail : 'reserved_booking_periods';
            if (DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
                ->where('TABLE_NAME', $source)->where('CONSTRAINT_NAME', $name)->exists()) {
                DB::statement("ALTER TABLE {$source} {$drop} {$name}");
            }
            if (! DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
                ->where('TABLE_NAME', $destination)->where('CONSTRAINT_NAME', $name)->exists()) {
                DB::statement("ALTER TABLE {$destination} ADD CONSTRAINT {$name} CHECK ({$expression})");
            }
        }
    }

    public function up(): void
    {
        foreach (self::GROUPS as $name => $fields) {
            if (! Schema::hasTable($name)) {
                Schema::create($name, function (Blueprint $table) use ($fields, $name) {
                    $table->id();
                    $suffix = str_replace('reserved_booking_period_', '', $name);
                    $table->foreignId('reserved_booking_period_id')->unique('reserved_'.$suffix.'_period_unique')
                        ->constrained('reserved_booking_periods', indexName: 'reserved_'.$suffix.'_period_fk')->cascadeOnDelete();
                    $this->columns($table, $fields);
                    if ($name === 'reserved_booking_period_schedules') {
                        $table->unique('active_reserved_date', 'reserved_schedule_active_date_unique');
                        $table->index(['reserved_date', 'is_active', 'start_time', 'end_time'], 'reserved_schedule_date_active_times_idx');
                    }
                    if ($name === 'reserved_booking_period_targets') {
                        $table->index(['target_patient_type', 'program_code', 'year_level', 'section'], 'reserved_target_group_idx');
                    }
                });
            }
        }

        DB::transaction(function () {
            DB::table('reserved_booking_periods')->orderBy('id')->chunkById(500, function ($periods) {
                foreach ($periods as $period) {
                    foreach (self::GROUPS as $name => $fields) {
                        $values = array_intersect_key((array) $period, array_flip($fields));
                        if (count($values) !== count($fields)) {
                            throw new RuntimeException('Reserved period source columns are missing; reconcile the schema before retrying.');
                        }
                        DB::table($name)->updateOrInsert(['reserved_booking_period_id' => $period->id], $values);
                        $copied = DB::table($name)->where('reserved_booking_period_id', $period->id)->first();
                        foreach ($fields as $field) {
                            if ($copied->{$field} !== $period->{$field}) {
                                throw new RuntimeException('Reserved period detail verification failed; original columns retained.');
                            }
                        }
                    }
                }
            });
        });

        $this->checks(true);
        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->dropUnique('reserved_period_active_date_unique');
            $table->dropIndex('reserved_period_date_active_times_idx');
            $table->dropIndex('reserved_period_target_idx');
            $table->dropColumn(array_merge(...array_values(self::GROUPS)));
        });
    }

    public function down(): void
    {
        // Nullable during copy so a populated table can be restored without fake dates.
        Schema::table('reserved_booking_periods', fn (Blueprint $table) => $this->columns($table, array_merge(...array_values(self::GROUPS)), true));
        DB::transaction(function () {
            DB::table('reserved_booking_periods')->orderBy('id')->chunkById(500, function ($periods) {
                foreach ($periods as $period) {
                    foreach (self::GROUPS as $name => $fields) {
                        $detail = DB::table($name)->where('reserved_booking_period_id', $period->id)->first();
                        if (! $detail) {
                            throw new RuntimeException('Reserved period detail is missing; normalized tables retained.');
                        }
                        $values = array_intersect_key((array) $detail, array_flip($fields));
                        DB::table('reserved_booking_periods')->where('id', $period->id)->update($values);
                        $copied = DB::table('reserved_booking_periods')->where('id', $period->id)->first();
                        foreach ($fields as $field) {
                            if ($copied->{$field} !== $detail->{$field}) {
                                throw new RuntimeException('Reserved period rollback verification failed; normalized tables retained.');
                            }
                        }
                    }
                }
            });
        });
        // MySQL can restore NOT NULL in place after the copy. Avoid rebuilding a
        // referenced SQLite parent table inside the test transaction.
        if (DB::getDriverName() === 'mysql') {
            Schema::table('reserved_booking_periods', fn (Blueprint $table) => $this->columns($table, array_merge(...array_values(self::GROUPS)), false, true));
        }
        $this->checks(false);
        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->unique('active_reserved_date', 'reserved_period_active_date_unique');
            $table->index(['reserved_date', 'is_active', 'start_time', 'end_time'], 'reserved_period_date_active_times_idx');
            $table->index(['target_patient_type', 'program_code', 'year_level', 'section'], 'reserved_period_target_idx');
        });
        foreach (array_keys(self::GROUPS) as $name) {
            Schema::drop($name);
        }
    }
};
