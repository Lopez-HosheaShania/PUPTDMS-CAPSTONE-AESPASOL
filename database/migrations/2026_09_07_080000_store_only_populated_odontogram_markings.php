<?php

use App\Support\AppointmentOdontogramStorage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('appointment_odontogram_teeth', 'marking_layout')) {
            Schema::table('appointment_odontogram_teeth', function (Blueprint $table) {
                // Empty-field layout only; clinical markings remain in relational rows.
                $table->text('marking_layout')->nullable();
            });
        }
        $this->rewrite(false);
    }

    public function down(): void
    {
        $this->rewrite(true);
        Schema::table('appointment_odontogram_teeth', fn (Blueprint $table) => $table->dropColumn('marking_layout'));
    }

    private function rewrite(bool $includeEmpty): void
    {
        DB::transaction(function () use ($includeEmpty) {
            DB::table('appointment_odontograms')->orderBy('id')->chunkById(100, function ($snapshots) use ($includeEmpty) {
                foreach ($snapshots as $snapshot) {
                    $data = AppointmentOdontogramStorage::read(DB::connection(), $snapshot->id);
                    AppointmentOdontogramStorage::replace(DB::connection(), $snapshot->id, $data, $includeEmpty);
                    if (AppointmentOdontogramStorage::read(DB::connection(), $snapshot->id) !== $data) {
                        throw new RuntimeException('Odontogram reconstruction changed; transaction rolled back.');
                    }
                }
            });
        });
    }
};
