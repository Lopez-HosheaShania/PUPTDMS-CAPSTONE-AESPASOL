<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'odontogram_revisions',
            function (Blueprint $table) {
                $table->foreignId(
                    'reason_type_id'
                )
                    ->nullable()
                    ->after('changes')
                    ->constrained(
                        'odontogram_revision_reason_types'
                    )
                    ->restrictOnDelete();
            }
        );

        $reasonTypes =
            DB::table(
                'odontogram_revision_reason_types'
            )
                ->pluck(
                    'id',
                    'code'
                );

        foreach (
            $reasonTypes
            as $code => $id
        ) {
            DB::table(
                'odontogram_revisions'
            )
                ->where(
                    'reason_category',
                    $code
                )
                ->update([
                    'reason_type_id' =>
                        $id,
                ]);
        }

        DB::table(
            'odontogram_revisions'
        )
            ->where(
                'version_number',
                1
            )
            ->where(
                'reason_notes',
                'Initial record'
            )
            ->update([
                'reason_type_id' => null,
            ]);

        Schema::table(
            'odontogram_revisions',
            function (Blueprint $table) {
                $table->dropColumn(
                    'reason_category'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'odontogram_revisions',
            function (Blueprint $table) {
                $table->string(
                    'reason_category',
                    50
                )
                    ->nullable()
                    ->after('changes');
            }
        );

        $reasonTypes =
            DB::table(
                'odontogram_revision_reason_types'
            )
                ->pluck(
                    'code',
                    'id'
                );

        foreach (
            $reasonTypes
            as $id => $code
        ) {
            DB::table(
                'odontogram_revisions'
            )
                ->where(
                    'reason_type_id',
                    $id
                )
                ->update([
                    'reason_category' =>
                        $code,
                ]);
        }

        DB::table(
            'odontogram_revisions'
        )
            ->whereNull(
                'reason_category'
            )
            ->update([
                'reason_category' =>
                    'other',
            ]);

        Schema::table(
            'odontogram_revisions',
            function (Blueprint $table) {
                $table->dropForeign([
                    'reason_type_id',
                ]);

                $table->dropColumn(
                    'reason_type_id'
                );
            }
        );
    }
};