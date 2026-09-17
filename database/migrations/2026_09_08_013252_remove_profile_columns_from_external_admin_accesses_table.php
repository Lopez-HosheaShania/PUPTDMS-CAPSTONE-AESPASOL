<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->dropUnique(['external_admin_id']);

            $table->dropColumn([
                'external_admin_id',
                'fname',
                'lname',
                'email',
                'office',
                'address',
                'age',
                'gender',
                'contact_number',
                'senior_pwd',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->string('external_admin_id')->nullable()->after('external_admin_profile_id');
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('email')->nullable();
            $table->string('office')->nullable();
            $table->text('address')->nullable();
            $table->integer('age')->nullable();
            $table->string('gender')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('senior_pwd')->nullable();
        });

        DB::table('external_admin_accesses')
            ->join(
                'external_admin_profiles',
                'external_admin_profiles.id',
                '=',
                'external_admin_accesses.external_admin_profile_id'
            )
            ->update([
                'external_admin_accesses.external_admin_id' =>
                    DB::raw('external_admin_profiles.external_admin_id'),

                'external_admin_accesses.fname' =>
                    DB::raw('external_admin_profiles.fname'),

                'external_admin_accesses.lname' =>
                    DB::raw('external_admin_profiles.lname'),

                'external_admin_accesses.email' =>
                    DB::raw('external_admin_profiles.email'),

                'external_admin_accesses.office' =>
                    DB::raw('external_admin_profiles.office'),

                'external_admin_accesses.address' =>
                    DB::raw('external_admin_profiles.address'),

                'external_admin_accesses.age' =>
                    DB::raw('external_admin_profiles.age'),

                'external_admin_accesses.gender' =>
                    DB::raw('external_admin_profiles.gender'),

                'external_admin_accesses.contact_number' =>
                    DB::raw('external_admin_profiles.contact_number'),

                'external_admin_accesses.senior_pwd' =>
                    DB::raw('external_admin_profiles.senior_pwd'),
            ]);

        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->string('external_admin_id')
                ->nullable(false)
                ->change();

            $table->unique('external_admin_id');
        });
    }
};