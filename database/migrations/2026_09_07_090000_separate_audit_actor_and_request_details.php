<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GROUPS = [
        'audit_log_actors' => ['actor_id', 'actor_role', 'actor_identifier', 'actor_name'],
        'audit_log_requests' => ['ip_address', 'user_agent', 'browser_name', 'device_type', 'device_name', 'os_name'],
    ];

    public function up(): void
    {
        $sourceColumns = Schema::getColumnListing('audit_logs');
        foreach (self::GROUPS as $name => $fields) {
            if (! Schema::hasTable($name)) {
                Schema::create($name, function (Blueprint $table) use ($fields) {
                    $table->id();
                    $table->foreignId('audit_log_id')->unique()->constrained('audit_logs')->cascadeOnDelete();
                    foreach ($fields as $field) {
                        if ($field === 'actor_id') {
                            // Historical identifier, intentionally independent of user deletion.
                            $table->unsignedBigInteger($field)->nullable()->index();
                        } elseif ($field === 'user_agent') {
                            $table->text($field)->nullable();
                        } else {
                            $table->string($field)->nullable();
                        }
                    }
                    if (in_array('actor_role', $fields, true)) {
                        $table->index('actor_role');
                        $table->index('actor_identifier');
                    }
                });
            }
        }
        DB::transaction(function () {
            DB::table('audit_logs')->orderBy('id')->chunkById(500, function ($logs) {
                foreach ($logs as $log) {
                    foreach (self::GROUPS as $table => $fields) {
                        $values = ['audit_log_id' => $log->id];
                        foreach ($fields as $field) {
                            $values[$field] = $log->{$field} ?? null;
                        }
                        DB::table($table)->updateOrInsert(['audit_log_id' => $log->id], $values);
                        $saved = DB::table($table)->where('audit_log_id', $log->id)->first();
                        foreach ($fields as $field) {
                            $expected = $values[$field];
                            // Some existing installations use an integer actor_identifier; retain its textual value.
                            if ($field === 'actor_identifier' && $expected !== null) {
                                $expected = (string) $expected;
                            }
                            if ($saved->{$field} !== $expected) {
                                throw new RuntimeException('Audit detail verification failed; original columns retained.');
                            }
                        }
                    }
                }
            });
        });
        Schema::table('audit_logs', function (Blueprint $table) use ($sourceColumns) {
            $table->dropColumn(array_values(array_intersect(array_merge(...array_values(self::GROUPS)), $sourceColumns)));
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (self::GROUPS as $fields) {
                foreach ($fields as $field) {
                    if ($field === 'actor_id') {
                        $table->unsignedBigInteger($field)->nullable();
                    } elseif ($field === 'user_agent') {
                        $table->text($field)->nullable();
                    } else {
                        $table->string($field)->nullable();
                    }
                }
            }
        });
        foreach (self::GROUPS as $name => $fields) {
            DB::table($name)->orderBy('id')->chunkById(500, function ($rows) use ($fields) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($fields as $field) {
                        $values[$field] = $row->{$field};
                    }
                    DB::table('audit_logs')->where('id', $row->audit_log_id)->update($values);
                }
            });
            Schema::drop($name);
        }
    }
};
