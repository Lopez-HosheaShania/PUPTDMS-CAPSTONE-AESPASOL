<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'odontogram_revision_reason_types',
            function (Blueprint $table) {
                $table->id();

                $table->string(
                    'code',
                    50
                )->unique();

                $table->string(
                    'label',
                    100
                );

                $table->boolean(
                    'requires_detail'
                )->default(false);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->unsignedSmallInteger(
                    'sort_order'
                )->default(0);

                $table->timestamps();
            }
        );

        $now = now();

        DB::table(
            'odontogram_revision_reason_types'
        )->insert([
            [
                'code' => 'wrong_tooth',
                'label' => 'Wrong Tooth',
                'requires_detail' => false,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'wrong_surface',
                'label' => 'Wrong Surface',
                'requires_detail' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'wrong_condition',
                'label' => 'Wrong Condition / Treatment',
                'requires_detail' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'treatment_changed',
                'label' => 'Treatment Information Changed',
                'requires_detail' => false,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'data_entry_error',
                'label' => 'Data Entry Error',
                'requires_detail' => false,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'other',
                'label' => 'Other',
                'requires_detail' => true,
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'odontogram_revision_reason_types'
        );
    }
};