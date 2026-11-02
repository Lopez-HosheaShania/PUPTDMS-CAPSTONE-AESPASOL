<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('stock_no')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('inventory_items')->whereNull('stock_no')->exists()) {
            throw new RuntimeException('Assign stock numbers to inventory items before restoring the required stock number column.');
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('stock_no')->nullable(false)->change();
        });
    }
};
