<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const FIELDS = ['reserved_booking_period_id', 'reserved_booking_period_slot_id', 'is_follow_up', 'follow_up_for_appointment_id', 'follow_up_reason'];

    private function checkTransaction(): void
    {
        if (DB::getDriverName() === 'sqlite' && DB::connection()->transactionLevel() > 0) {
            throw new RuntimeException('Run this SQLite parent table rebuild outside a transaction.');
        }
    }

    public function up(): void
    {
        $this->checkTransaction();
        $columns = Schema::getColumnListing('appointments');
        if (! Schema::hasIndex('appointments', 'appointment_id_patient_unique')) {
            Schema::table('appointments', fn (Blueprint $table) => $table->unique(['id', 'patient_id'], 'appointment_id_patient_unique'));
        }
        if (! Schema::hasTable('appointment_reserved_bookings')) {
            Schema::create('appointment_reserved_bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id')->unique();
                $table->unsignedBigInteger('booking_patient_id');
                // Keep patient/period uniqueness enforceable and patient identity consistent with the parent.
                $table->foreign(['appointment_id', 'booking_patient_id'], 'reserved_booking_appointment_patient_fk')
                    ->references(['id', 'patient_id'])->on('appointments')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('reserved_booking_period_id')->nullable()->constrained('reserved_booking_periods', indexName: 'reserved_booking_period_fk')->restrictOnDelete();
                $table->foreignId('reserved_booking_period_slot_id')->nullable()->constrained('reserved_booking_period_slots', indexName: 'reserved_booking_slot_fk')->restrictOnDelete();
                $table->unique(['booking_patient_id', 'reserved_booking_period_id'], 'reserved_booking_patient_period_unique');
                $table->unique('reserved_booking_period_slot_id', 'reserved_booking_slot_unique');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('appointment_follow_ups')) {
            Schema::create('appointment_follow_ups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
                $table->boolean('is_follow_up')->default(false);
                $table->foreignId('follow_up_for_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
                $table->text('follow_up_reason')->nullable();
                $table->timestamps();
            });
        }
        DB::transaction(function () {
            DB::table('appointments')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $common = ['appointment_id' => $row->id, 'created_at' => $row->created_at, 'updated_at' => $row->updated_at];
                    DB::table('appointment_reserved_bookings')->updateOrInsert(['appointment_id' => $row->id], $common + [
                        'booking_patient_id' => $row->patient_id,
                        'reserved_booking_period_id' => $row->reserved_booking_period_id,
                        'reserved_booking_period_slot_id' => $row->reserved_booking_period_slot_id,
                    ]);
                    DB::table('appointment_follow_ups')->updateOrInsert(['appointment_id' => $row->id], $common + [
                        'is_follow_up' => $row->is_follow_up ?? false,
                        'follow_up_for_appointment_id' => $row->follow_up_for_appointment_id,
                        'follow_up_reason' => $row->follow_up_reason,
                    ]);
                    $booking = DB::table('appointment_reserved_bookings')->where('appointment_id', $row->id)->first();
                    $followUp = DB::table('appointment_follow_ups')->where('appointment_id', $row->id)->first();
                    foreach (self::FIELDS as $field) {
                        $actual = str_starts_with($field, 'reserved_') ? $booking->{$field} : $followUp->{$field};
                        $expected = $row->{$field} ?? ($field === 'is_follow_up' ? false : null);
                        if ($field === 'is_follow_up' ? (bool) $actual !== (bool) $expected : $actual !== $expected) {
                            throw new RuntimeException('Booking/follow-up copy verification failed; original columns retained.');
                        }
                    }
                }
            });
        });
        $foreignKeys = Schema::getForeignKeys('appointments');
        $oldIndexes = array_filter(['appointment_patient_reserved_period_unique', 'appointment_reserved_period_slot_unique'],
            fn ($index) => Schema::hasIndex('appointments', $index));
        Schema::table('appointments', function (Blueprint $table) use ($columns, $foreignKeys, $oldIndexes) {
            foreach ($foreignKeys as $foreignKey) {
                if (array_intersect($foreignKey['columns'], self::FIELDS)) {
                    $table->dropForeign($foreignKey['columns']);
                }
            }
            foreach ($oldIndexes as $index) {
                $table->dropUnique($index);
            }
            $table->dropColumn(array_values(array_intersect(self::FIELDS, $columns)));
        });
    }

    public function down(): void
    {
        $this->checkTransaction();
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('reserved_booking_period_id')->nullable()->constrained('reserved_booking_periods')->restrictOnDelete();
            $table->foreignId('reserved_booking_period_slot_id')->nullable()->constrained('reserved_booking_period_slots')->restrictOnDelete();
            $table->boolean('is_follow_up')->default(false);
            $table->foreignId('follow_up_for_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->text('follow_up_reason')->nullable();
        });
        foreach (['appointment_reserved_bookings' => array_slice(self::FIELDS, 0, 2), 'appointment_follow_ups' => array_slice(self::FIELDS, 2)] as $table => $fields) {
            DB::table($table)->orderBy('id')->chunkById(500, function ($rows) use ($fields) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($fields as $field) {
                        $values[$field] = $row->{$field};
                    }
                    DB::table('appointments')->where('id', $row->appointment_id)->update($values);
                }
            });
            Schema::drop($table);
        }
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointment_id_patient_unique');
            $table->unique(['patient_id', 'reserved_booking_period_id'], 'appointment_patient_reserved_period_unique');
            $table->unique('reserved_booking_period_slot_id', 'appointment_reserved_period_slot_unique');
        });
    }
};
