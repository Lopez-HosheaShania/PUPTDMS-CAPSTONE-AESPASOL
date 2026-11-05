<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_auth_security', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('last_failed_login_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamps();
        });

        Schema::create('user_deactivations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employment_status', 40)->nullable();
            $table->string('account_status', 40)->nullable();
            $table->date('last_working_date')->nullable();
            $table->timestamp('access_ends_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'deactivated_at']);
        });

        $now = now();

        DB::table('users')
            ->orderBy('id')
            ->each(function (object $user) use ($now) {
                DB::table('user_auth_security')->insert([
                    'user_id' => $user->id,
                    'last_login_at' => $user->last_login_at,
                    'failed_login_attempts' => $user->failed_login_attempts ?? 0,
                    'last_failed_login_at' => $user->last_failed_login_at,
                    'locked_until' => $user->locked_until,
                    'access_token' => $user->access_token,
                    'refresh_token' => $user->refresh_token,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($user->deactivated_at) {
                    DB::table('user_deactivations')->insert([
                        'user_id' => $user->id,
                        'deactivated_by' => $user->deactivated_by,
                        'employment_status' => $user->employment_status,
                        'account_status' => $user->account_status,
                        'last_working_date' => $user->last_working_date,
                        'access_ends_at' => $user->access_ends_at,
                        'deactivated_at' => $user->deactivated_at,
                        'reason' => $user->deactivation_reason,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_deactivations');
        Schema::dropIfExists('user_auth_security');
    }
};
