<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'dentist_id')) {
                $table->foreignId('dentist_id')
                    ->nullable()
                    ->after('patient_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('appointments', 'is_follow_up')) {
                $table->boolean('is_follow_up')
                    ->default(false)
                    ->after('status');
            }

            if (!Schema::hasColumn('appointments', 'follow_up_for_appointment_id')) {
                $table->foreignId('follow_up_for_appointment_id')
                    ->nullable()
                    ->after('is_follow_up')
                    ->constrained('appointments')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('appointments', 'follow_up_reason')) {
                $table->text('follow_up_reason')
                    ->nullable()
                    ->after('follow_up_for_appointment_id');
            }

            if (!Schema::hasColumn('appointments', 'follow_up_reminder_sent_at')) {
                $table->timestamp('follow_up_reminder_sent_at')
                    ->nullable()
                    ->after('follow_up_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'follow_up_for_appointment_id')) {
                $table->dropForeign(['follow_up_for_appointment_id']);
            }

            if (Schema::hasColumn('appointments', 'dentist_id')) {
                $table->dropForeign(['dentist_id']);
            }

            $table->dropColumn([
                'follow_up_reminder_sent_at',
                'follow_up_reason',
                'follow_up_for_appointment_id',
                'is_follow_up',
                'dentist_id',
            ]);
        });
    }
};