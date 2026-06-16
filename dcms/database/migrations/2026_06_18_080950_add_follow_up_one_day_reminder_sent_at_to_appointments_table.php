<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'follow_up_one_day_reminder_sent_at')) {
                $table->timestamp('follow_up_one_day_reminder_sent_at')
                    ->nullable()
                    ->after('follow_up_reminder_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'follow_up_one_day_reminder_sent_at')) {
                $table->dropColumn('follow_up_one_day_reminder_sent_at');
            }
        });
    }
};