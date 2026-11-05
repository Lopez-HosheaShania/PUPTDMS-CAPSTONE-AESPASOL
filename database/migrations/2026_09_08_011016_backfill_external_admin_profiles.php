<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('external_admin_accesses')
            ->orderBy('id')
            ->chunkById(100, function ($accesses) {
                foreach ($accesses as $access) {
                    $profile = DB::table('external_admin_profiles')
                        ->where('external_admin_id', $access->external_admin_id)
                        ->first();

                    $profileData = [
                        'fname' => $access->fname,
                        'lname' => $access->lname,
                        'email' => $access->email,
                        'office' => $access->office,
                        'address' => $access->address,
                        'age' => $access->age,
                        'gender' => $access->gender,
                        'contact_number' => $access->contact_number,
                        'senior_pwd' => $access->senior_pwd,
                        'updated_at' => now(),
                    ];

                    if (!$profile) {
                        $profileId = DB::table('external_admin_profiles')
                            ->insertGetId(array_merge(
                                [
                                    'external_admin_id' => $access->external_admin_id,
                                    'created_at' => now(),
                                ],
                                $profileData
                            ));
                    } else {
                        DB::table('external_admin_profiles')
                            ->where('id', $profile->id)
                            ->update($profileData);

                        $profileId = $profile->id;
                    }

                    DB::table('external_admin_accesses')
                        ->where('id', $access->id)
                        ->update([
                            'external_admin_profile_id' => $profileId,
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('external_admin_accesses')
            ->update([
                'external_admin_profile_id' => null,
            ]);
    }
};
