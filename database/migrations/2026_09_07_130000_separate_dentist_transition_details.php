<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const GROUPS = [
        'dentist_transition_details' => ['dentist_transitions', 'dentist_transition_id', ['handover_notes', 'remarks', 'reviewed_by', 'approved_by', 'completed_at']],
        'dentist_transition_cancellations' => ['dentist_transitions', 'dentist_transition_id', ['cancelled_at', 'cancellation_reason']],
        'dentist_transition_item_resolutions' => ['dentist_transition_items', 'dentist_transition_item_id', ['resolution_type', 'remarks', 'transferred_by', 'transferred_at']],
    ];

    private function fields(Blueprint $table, array $fields): void
    {
        foreach ($fields as $field) {
            if (in_array($field, ['reviewed_by', 'approved_by', 'transferred_by'], true)) {
                $table->foreignId($field)->nullable()->constrained('users')->nullOnDelete();
            } elseif (str_ends_with($field, '_at')) {
                $table->timestamp($field)->nullable();
            } elseif ($field === 'resolution_type') {
                $table->string($field, 40)->nullable();
            } else {
                $table->text($field)->nullable();
            }
        }
    }

    private function assertSafeSchemaChange(): void
    {
        if (DB::getDriverName() === 'sqlite' && DB::connection()->transactionLevel() > 0) {
            throw new RuntimeException('Run the SQLite transition table rebuild outside a transaction.');
        }
    }

    public function up(): void
    {
        $this->assertSafeSchemaChange();
        foreach (self::GROUPS as $name => [$parent, $foreignKey, $fields]) {
            foreach ($fields as $field) {
                if (! Schema::hasColumn($parent, $field)) {
                    throw new RuntimeException("Expected source field {$parent}.{$field}; original data must be checked before retrying.");
                }
            }
        }
        foreach (self::GROUPS as $name => [$parent, $foreignKey, $fields]) {
            if (! Schema::hasTable($name)) {
                Schema::create($name, function (Blueprint $table) use ($name, $parent, $foreignKey, $fields) {
                    $table->id();
                    $prefix = match ($name) {
                        'dentist_transition_details' => 'dt_details',
                        'dentist_transition_cancellations' => 'dt_cancel',
                        default => 'dt_resolution',
                    };
                    $table->foreignId($foreignKey)->unique($prefix.'_parent_unique')
                        ->constrained($parent, indexName: $prefix.'_parent_fk')->cascadeOnDelete();
                    $this->fields($table, $fields);
                });
            }
        }
        DB::transaction(function () {
            foreach (self::GROUPS as $name => [$parent, $foreignKey, $fields]) {
                DB::table($parent)->orderBy('id')->chunkById(500, function ($rows) use ($name, $foreignKey, $fields) {
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($fields as $field) {
                            $values[$field] = $row->{$field};
                        }
                        if (count(array_filter($values, fn ($value) => $value !== null)) === 0) {
                            continue;
                        }
                        DB::table($name)->updateOrInsert([$foreignKey => $row->id], $values);
                        $saved = DB::table($name)->where($foreignKey, $row->id)->first();
                        foreach ($values as $field => $value) {
                            if ($saved->{$field} !== $value) {
                                throw new RuntimeException('Transition detail copy failed verification; original fields retained.');
                            }
                        }
                    }
                });
            }
        });
        foreach (['dentist_transitions', 'dentist_transition_items'] as $parent) {
            Schema::table($parent, function (Blueprint $table) use ($parent) {
                foreach (self::GROUPS as [$source, $foreignKey, $fields]) {
                    if ($source !== $parent) {
                        continue;
                    }
                    foreach (array_intersect($fields, ['reviewed_by', 'approved_by', 'transferred_by']) as $field) {
                        $table->dropForeign([$field]);
                    }
                    $table->dropColumn($fields);
                }
            });
        }
    }

    public function down(): void
    {
        $this->assertSafeSchemaChange();
        foreach (self::GROUPS as $name => [$parent, $foreignKey, $fields]) {
            Schema::table($parent, fn (Blueprint $table) => $this->fields($table, $fields));
            DB::table($name)->orderBy('id')->chunkById(500, function ($rows) use ($parent, $foreignKey, $fields) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($fields as $field) {
                        $values[$field] = $row->{$field};
                    }
                    DB::table($parent)->where('id', $row->{$foreignKey})->update($values);
                }
            });
        }
        foreach (array_keys(self::GROUPS) as $name) {
            Schema::drop($name);
        }
    }
};
