<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasLegacyServices = Schema::hasColumn('reserved_booking_periods', 'allowed_services');
        if (! $hasLegacyServices && DB::table('reserved_booking_periods')->exists()) {
            throw new RuntimeException('Legacy allowed_services column is missing on a populated table. Reconcile the schema before migrating.');
        }

        // Validate before DDL: MySQL schema changes cannot be rolled back.
        DB::table('reserved_booking_periods')->orderBy('id')->chunkById(500, function ($periods) {
            foreach ($periods as $period) {
                $this->services($period->allowed_services);
            }
        });

        if (! Schema::hasColumn('reserved_booking_periods', 'restrict_services')) {
            Schema::table('reserved_booking_periods', function (Blueprint $table) {
                $table->boolean('restrict_services')->default(false);
            });
        }

        if (! Schema::hasTable('reserved_booking_period_services')) {
            Schema::create('reserved_booking_period_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reserved_booking_period_id')
                    ->constrained('reserved_booking_periods', indexName: 'reserved_period_service_period_fk')
                    ->cascadeOnDelete();
                // These are the original name-based restrictions, including retired names.
                // Linking to mutable service types would change rename/delete behavior.
                $table->text('service_name');
                $table->unsignedInteger('position');
                $table->unique(['reserved_booking_period_id', 'position'], 'reserved_period_service_position_unique');
            });
        }

        DB::transaction(function () {
            // Include inactive and soft-deleted periods so restoring them stays safe.
            DB::table('reserved_booking_periods')->orderBy('id')->chunkById(500, function ($periods) {
                foreach ($periods as $period) {
                    $services = $this->services($period->allowed_services);
                    DB::table('reserved_booking_periods')->where('id', $period->id)
                        ->update(['restrict_services' => $services !== null]);
                    DB::table('reserved_booking_period_services')->where('reserved_booking_period_id', $period->id)->delete();
                    foreach ($services ?? [] as $position => $name) {
                        DB::table('reserved_booking_period_services')->insert([
                            'reserved_booking_period_id' => $period->id,
                            'service_name' => $name,
                            'position' => $position,
                        ]);
                    }
                    $copied = DB::table('reserved_booking_period_services')
                        ->where('reserved_booking_period_id', $period->id)->orderBy('position')->pluck('service_name')->all();
                    if ($copied !== ($services ?? [])) {
                        throw new RuntimeException('Reserved service copy verification failed; original JSON retained.');
                    }
                }
            });
        });

        // An empty local table may already be missing the legacy column.
        if ($hasLegacyServices) {
            Schema::table('reserved_booking_periods', fn (Blueprint $table) => $table->dropColumn('allowed_services'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('reserved_booking_periods', 'allowed_services')) {
            Schema::table('reserved_booking_periods', fn (Blueprint $table) => $table->json('allowed_services')->nullable());
        }

        DB::transaction(function () {
            DB::table('reserved_booking_periods')->orderBy('id')->chunkById(500, function ($periods) {
                foreach ($periods as $period) {
                    $services = $period->restrict_services
                        ? DB::table('reserved_booking_period_services')->where('reserved_booking_period_id', $period->id)
                            ->orderBy('position')->pluck('service_name')->all()
                        : null;
                    DB::table('reserved_booking_periods')->where('id', $period->id)->update([
                        'allowed_services' => $services === null ? null : json_encode($services, JSON_THROW_ON_ERROR),
                    ]);
                    $copied = DB::table('reserved_booking_periods')->where('id', $period->id)->value('allowed_services');
                    if ($this->services($copied) !== $services) {
                        throw new RuntimeException('Reserved service rollback verification failed; normalized rows retained.');
                    }
                }
            });
        });

        Schema::dropIfExists('reserved_booking_period_services');
        Schema::table('reserved_booking_periods', fn (Blueprint $table) => $table->dropColumn('restrict_services'));
    }

    private function services(?string $json): ?array
    {
        if ($json === null) {
            return null;
        }

        $services = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($services) || ! array_is_list($services)
            || count(array_filter($services, 'is_string')) !== count($services)) {
            throw new RuntimeException('Reserved allowed_services must be a JSON list of strings; original data retained.');
        }

        return $services;
    }
};
