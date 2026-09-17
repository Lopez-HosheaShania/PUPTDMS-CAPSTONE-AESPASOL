<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const GROUPS = [
        'user_deactivation_employments' => ['user_deactivations', 'user_deactivation_id', 'ud_employment', ['employment_status', 'last_working_date']],
        'user_deactivation_accesses' => ['user_deactivations', 'user_deactivation_id', 'ud_access', ['account_status', 'access_ends_at']],
        'dentist_transition_item_assignments' => ['dentist_transition_items', 'dentist_transition_item_id', 'dti_assignment', ['original_dentist_id', 'successor_dentist_id']],
        'dentist_transition_item_states' => ['dentist_transition_items', 'dentist_transition_item_id', 'dti_state', ['transfer_status', 'is_critical']],
    ];

    private function assertSafeSchemaChange(): void
    {
        if (DB::getDriverName() === 'sqlite' && DB::connection()->transactionLevel() > 0) {
            throw new RuntimeException('Run the SQLite transition item table rebuild outside a transaction.');
        }
    }

    private function fields(Blueprint $table, array $fields, bool $restoring = false): void
    {
        foreach ($fields as $field) {
            match ($field) {
                'employment_status', 'account_status' => $table->string($field, 40)->nullable(),
                'last_working_date' => $table->date($field)->nullable(),
                'access_ends_at' => $table->timestamp($field)->nullable(),
                'original_dentist_id' => $table->foreignId($field)->nullable($restoring)->constrained('users', indexName: $table->getTable() === 'dentist_transition_items' ? 'dentist_transition_items_original_dentist_id_foreign' : 'dti_assignment_original_fk')->restrictOnDelete(),
                'successor_dentist_id' => $table->foreignId($field)->nullable()->constrained('users', indexName: $table->getTable() === 'dentist_transition_items' ? 'dentist_transition_items_successor_dentist_id_foreign' : 'dti_assignment_successor_fk')->nullOnDelete(),
                'transfer_status' => $table->string($field, 40)->default('pending'),
                'is_critical' => $table->boolean($field)->default(false),
            };
        }
    }

    public function up(): void
    {
        $this->assertSafeSchemaChange();
        foreach (self::GROUPS as [$parent, $foreignKey, $prefix, $fields]) {
            foreach ($fields as $field) {
                if (! Schema::hasColumn($parent, $field)) {
                    throw new RuntimeException("Missing source column {$parent}.{$field}; reconcile the schema before migrating.");
                }
            }
        }

        foreach (self::GROUPS as $name => [$parent, $foreignKey, $prefix, $fields]) {
            if (! Schema::hasTable($name)) {
                Schema::create($name, function (Blueprint $table) use ($parent, $foreignKey, $prefix, $fields) {
                    $table->id();
                    $table->foreignId($foreignKey)->unique($prefix.'_parent_unique')
                        ->constrained($parent, indexName: $prefix.'_parent_fk')->cascadeOnDelete();
                    $this->fields($table, $fields);
                    if (in_array('transfer_status', $fields, true)) {
                        $table->index('transfer_status', 'dti_state_status_idx');
                    }
                });
            }
        }

        DB::transaction(function () {
            foreach (self::GROUPS as $name => [$parent, $foreignKey, $prefix, $fields]) {
                DB::table($parent)->orderBy('id')->chunkById(500, function ($rows) use ($name, $foreignKey, $fields) {
                    foreach ($rows as $row) {
                        $values = array_intersect_key((array) $row, array_flip($fields));
                        // Keep optional all-null historical snapshots sparse.
                        if (count(array_filter($values, fn ($value) => $value !== null)) === 0) {
                            DB::table($name)->where($foreignKey, $row->id)->delete();

                            continue;
                        }
                        DB::table($name)->updateOrInsert([$foreignKey => $row->id], $values);
                        $copied = DB::table($name)->where($foreignKey, $row->id)->first();
                        foreach ($values as $field => $value) {
                            if ($copied->{$field} !== $value) {
                                throw new RuntimeException('Deactivation/transition detail copy verification failed; source columns retained.');
                            }
                        }
                    }
                });
            }
        });

        Schema::table('user_deactivations', fn (Blueprint $table) => $table->dropColumn(['employment_status', 'account_status', 'last_working_date', 'access_ends_at']));
        Schema::table('dentist_transition_items', function (Blueprint $table) {
            $table->dropForeign(['original_dentist_id']);
            $table->dropForeign(['successor_dentist_id']);
            $table->dropIndex('dti_transition_status_idx');
            $table->dropColumn(['original_dentist_id', 'successor_dentist_id', 'transfer_status', 'is_critical']);
        });
    }

    public function down(): void
    {
        $this->assertSafeSchemaChange();
        foreach (self::GROUPS as [$parent, $foreignKey, $prefix, $fields]) {
            Schema::table($parent, fn (Blueprint $table) => $this->fields($table, $fields, true));
        }
        DB::transaction(function () {
            foreach (self::GROUPS as $name => [$parent, $foreignKey, $prefix, $fields]) {
                DB::table($parent)->orderBy('id')->chunkById(500, function ($rows) use ($name, $parent, $foreignKey, $fields) {
                    foreach ($rows as $row) {
                        $detail = DB::table($name)->where($foreignKey, $row->id)->first();
                        if ($parent === 'dentist_transition_items' && ! $detail) {
                            throw new RuntimeException('A required transition item detail is missing; normalized tables retained.');
                        }
                        $values = [];
                        foreach ($fields as $field) {
                            $values[$field] = $detail?->{$field};
                        }
                        DB::table($parent)->where('id', $row->id)->update($values);
                        $copied = DB::table($parent)->where('id', $row->id)->first();
                        foreach ($values as $field => $value) {
                            if ($copied->{$field} !== $value) {
                                throw new RuntimeException('Deactivation/transition rollback verification failed; normalized tables retained.');
                            }
                        }
                    }
                });
            }
        });
        Schema::table('dentist_transition_items', function (Blueprint $table) {
            $table->unsignedBigInteger('original_dentist_id')->nullable(false)->change();
            $table->index(['dentist_transition_id', 'transfer_status'], 'dti_transition_status_idx');
        });
        foreach (array_keys(self::GROUPS) as $name) {
            Schema::drop($name);
        }
    }
};
