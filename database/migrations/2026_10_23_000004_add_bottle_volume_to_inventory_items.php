<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('volume_per_bottle_ml', 10, 2)->nullable();
            $table->decimal('current_bottle_remaining_ml', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['volume_per_bottle_ml', 'current_bottle_remaining_ml']);
        });
    }
};
