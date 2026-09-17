<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const FIELDS = ['approved_at', 'approved_by', 'rejection_reason'];

    private function fields(Blueprint $table): void
    {
        $table->timestamp('approved_at')->nullable();
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->text('rejection_reason')->nullable();
    }

    private function checkTransaction(): void
    {
        if (DB::getDriverName() === 'sqlite' && DB::connection()->transactionLevel() > 0) {
            throw new RuntimeException('Run the SQLite document request rebuild outside a transaction.');
        }
    }

    public function up(): void
    {
        $this->checkTransaction();
        foreach (self::FIELDS as $field) {
            if (! Schema::hasColumn('document_requests', $field)) {
                throw new RuntimeException('Expected original document request review fields.');
            }
        }
        if (! Schema::hasTable('document_request_reviews')) {
            Schema::create('document_request_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_request_id')->unique()->constrained('document_requests')->cascadeOnDelete();
                $this->fields($table);
                $table->index('approved_at');
            });
        }
        DB::transaction(function () {
            DB::table('document_requests')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach (self::FIELDS as $field) {
                        $values[$field] = $row->{$field};
                    }
                    if (count(array_filter($values, fn ($value) => $value !== null)) === 0) {
                        continue;
                    }
                    DB::table('document_request_reviews')->updateOrInsert(['document_request_id' => $row->id], $values);
                    $saved = DB::table('document_request_reviews')->where('document_request_id', $row->id)->first();
                    foreach ($values as $field => $value) {
                        if ($saved->{$field} !== $value) {
                            throw new RuntimeException('Review copy verification failed; original fields retained.');
                        }
                    }
                }
            });
        });
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(self::FIELDS);
        });
    }

    public function down(): void
    {
        $this->checkTransaction();
        Schema::table('document_requests', fn (Blueprint $table) => $this->fields($table));
        DB::table('document_request_reviews')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $values = [];
                foreach (self::FIELDS as $field) {
                    $values[$field] = $row->{$field};
                }
                DB::table('document_requests')->where('id', $row->document_request_id)->update($values);
            }
        });
        Schema::drop('document_request_reviews');
    }
};
