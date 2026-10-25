<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odontogram_revisions', function (Blueprint $table) {
            $table->string('reason_detail', 255)
                ->nullable()
                ->after('reason_category');

            $table->dropColumn(
                'previous_odontogram_data'
            );
        });
    }

    public function down(): void
    {
        Schema::table('odontogram_revisions', function (Blueprint $table) {
            $table->json('previous_odontogram_data')
                ->nullable()
                ->after('odontogram_data');

            $table->dropColumn(
                'reason_detail'
            );
        });
    }
};