<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = ['request_date', 'request_time', 'status'];

    public function up(): void
    {
        foreach (self::FIELDS as $field) {
            if (! Schema::hasColumn('document_requests', $field)) {
                throw new RuntimeException('Expected original request date, time, and status columns.');
            }
        }
        if (! Schema::hasTable('document_request_states')) {
            Schema::create('document_request_states', function (Blueprint $table) {
                $table->foreignId('document_request_id')->primary()->constrained('document_requests')->cascadeOnDelete();
                $table->date('request_date');
                $table->time('request_time');
                $table->enum('status', ['pending', 'approved', 'ready', 'released', 'rejected'])->default('pending');
                $table->index(['status', 'request_date']);
                $table->index('request_date');
            });
        }
        DB::transaction(function () {
            DB::table('document_requests')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach (self::FIELDS as $field) {
                        $values[$field] = $row->{$field};
                    }
                    DB::table('document_request_states')->updateOrInsert(['document_request_id' => $row->id], $values);
                    $saved = DB::table('document_request_states')->where('document_request_id', $row->id)->first();
                    foreach ($values as $field => $value) {
                        if ($saved->{$field} !== $value) {
                            throw new RuntimeException('Request state verification failed; original columns retained.');
                        }
                    }
                }
            });
        });
        Schema::table('document_requests', fn (Blueprint $table) => $table->dropColumn(self::FIELDS));
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->date('request_date')->nullable();
            $table->time('request_time')->nullable();
            $table->enum('status', ['pending', 'approved', 'ready', 'released', 'rejected'])->default('pending');
        });
        DB::table('document_request_states')->orderBy('document_request_id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $values = [];
                foreach (self::FIELDS as $field) {
                    $values[$field] = $row->{$field};
                }
                DB::table('document_requests')->where('id', $row->document_request_id)->update($values);
            }
        }, 'document_request_id');
        Schema::drop('document_request_states');
    }
};
