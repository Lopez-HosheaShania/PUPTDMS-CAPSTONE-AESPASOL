<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SQLite must disable foreign keys while rebuilding the parent table.
    public $withinTransaction = false;

    private const GROUPS = [
        'appointment_transfers' => ['original_dentist_id', 'transferred_by', 'transferred_at', 'transfer_reason'],
        'appointment_reminders' => ['follow_up_reminder_sent_at', 'follow_up_today_reminder_sent_at', 'follow_up_one_day_reminder_sent_at'],
    ];

    public function up(): void
    {
        $this->assertSafeSchemaChange();
        $sourceFields = Schema::getColumnListing('appointments');
        foreach (self::GROUPS['appointment_transfers'] as $field) {
            if (! in_array($field, $sourceFields, true)) {
                throw new RuntimeException('Expected original appointment transfer columns before migration.');
            }
        }
        if (! Schema::hasTable('appointment_transfers')) {
            Schema::create('appointment_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
                $table->foreignId('original_dentist_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('transferred_at')->nullable();
                $table->string('transfer_reason', 120)->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('appointment_reminders')) {
            Schema::create('appointment_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
                $table->timestamp('follow_up_reminder_sent_at')->nullable();
                $table->timestamp('follow_up_today_reminder_sent_at')->nullable();
                $table->timestamp('follow_up_one_day_reminder_sent_at')->nullable();
                $table->timestamps();
            });
        }

        DB::transaction(function () {
            DB::table('appointments')->orderBy('id')->chunkById(500, function ($appointments) {
                foreach ($appointments as $appointment) {
                    foreach (self::GROUPS as $table => $fields) {
                        $values = ['appointment_id' => $appointment->id];
                        foreach ($fields as $field) {
                            $values[$field] = $appointment->{$field} ?? null;
                        }
                        $values['created_at'] = $appointment->created_at;
                        $values['updated_at'] = $appointment->updated_at;
                        DB::table($table)->updateOrInsert(['appointment_id' => $appointment->id], $values);
                        $saved = (array) DB::table($table)->where('appointment_id', $appointment->id)->first();
                        unset($saved['id']);
                        if ($saved !== $values) {
                            throw new RuntimeException('Appointment detail verification failed; original columns retained.');
                        }
                    }
                }
            });
        });

        Schema::table('appointments', function (Blueprint $table) use ($sourceFields) {
            $table->dropForeign(['original_dentist_id']);
            $table->dropForeign(['transferred_by']);
            $table->dropColumn(array_values(array_intersect(array_merge(...array_values(self::GROUPS)), $sourceFields)));
        });
    }

    public function down(): void
    {
        $this->assertSafeSchemaChange();
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('original_dentist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->string('transfer_reason', 120)->nullable();
            $table->timestamp('follow_up_reminder_sent_at')->nullable();
            $table->timestamp('follow_up_today_reminder_sent_at')->nullable();
            $table->timestamp('follow_up_one_day_reminder_sent_at')->nullable();
        });
        foreach (self::GROUPS as $table => $fields) {
            DB::table($table)->orderBy('id')->chunkById(500, function ($rows) use ($fields) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($fields as $field) {
                        $values[$field] = $row->{$field};
                    }
                    DB::table('appointments')->where('id', $row->appointment_id)->update($values);
                }
            });
            Schema::dropIfExists($table);
        }
    }

    private function assertSafeSchemaChange(): void
    {
        if (DB::getDriverName() === 'sqlite' && DB::connection()->transactionLevel() > 0) {
            throw new RuntimeException('Run this SQLite table rebuild outside a transaction so foreign key cascades can be disabled.');
        }
    }
};
