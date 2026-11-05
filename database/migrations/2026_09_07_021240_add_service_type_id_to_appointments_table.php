<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('service_type_id')
                ->nullable()
                ->after('transferred_by')
                ->constrained('service_types')
                ->restrictOnDelete();
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::table('appointments')->update([
                'service_type_id' => DB::table('service_types')->select('id')
                    ->whereColumn('service_types.name', 'appointments.service_type')->limit(1),
            ]);

            return;
        }

        DB::table('appointments')
            ->join(
                'service_types',
                'appointments.service_type',
                '=',
                'service_types.name'
            )
            ->update([
                'appointments.service_type_id' => DB::raw('service_types.id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->dropColumn('service_type_id');
        });
    }
};
