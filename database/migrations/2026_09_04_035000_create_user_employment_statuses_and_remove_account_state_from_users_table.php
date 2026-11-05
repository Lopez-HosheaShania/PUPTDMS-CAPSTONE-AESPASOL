<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USER_COLUMNS = [
        'employment_status',
        'account_status',
        'last_working_date',
        'access_ends_at',
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason',
    ];

    public function up(): void
    {
        Schema::create('user_employment_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employment_status', 40)->nullable();
            $table->string('account_status', 40)->nullable();
            $table->date('last_working_date')->nullable();
            $table->timestamp('access_ends_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('users')
            ->orderBy('id')
            ->each(function (object $user) use ($now) {
                DB::table('user_employment_statuses')->insert([
                    'user_id' => $user->id,
                    'employment_status' => $user->employment_status,
                    'account_status' => $user->account_status,
                    'last_working_date' => $user->last_working_date,
                    'access_ends_at' => $user->access_ends_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['deactivated_by']);
            $table->dropColumn(self::USER_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employment_status', 40)->nullable();
            $table->string('account_status', 40)->nullable();
            $table->date('last_working_date')->nullable();
            $table->timestamp('access_ends_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deactivation_reason')->nullable();
        });

        $now = now();

        DB::table('user_employment_statuses')
            ->orderBy('user_id')
            ->each(function (object $employment) use ($now) {
                DB::table('users')
                    ->where('id', $employment->user_id)
                    ->update([
                        'employment_status' => $employment->employment_status,
                        'account_status' => $employment->account_status,
                        'last_working_date' => $employment->last_working_date,
                        'access_ends_at' => $employment->access_ends_at,
                        'updated_at' => $now,
                    ]);
            });

        Schema::dropIfExists('user_employment_statuses');
    }
};
