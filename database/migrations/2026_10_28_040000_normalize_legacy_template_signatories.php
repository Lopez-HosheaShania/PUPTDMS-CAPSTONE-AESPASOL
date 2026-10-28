<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // These literals identify old template content only. Runtime rendering
        // uses placeholders populated from the saved signatory or record.
        $legacy = [
            'Nelson P. Angeles, DMD' => '{{dentist_name}}',
            '/images/dr.angeles-sign.png' => '{{dentist_signature}}',
            'Ronilo I. Lim' => '{{prepared_by}}',
            '/images/sir.lim-sign.png' => '{{prepared_by_signature}}',
        ];

        DB::transaction(function () use ($legacy) {
            DB::table('document_templates')->orderBy('id')->chunkById(100, function ($templates) use ($legacy) {
                foreach ($templates as $template) {
                    $updates = [];
                    foreach (['header_content', 'content', 'footer_content'] as $column) {
                        if ($template->{$column} === null) {
                            continue;
                        }
                        $content = strtr($template->{$column}, $legacy);
                        if ($content !== $template->{$column}) {
                            $updates[$column] = $content;
                        }
                    }
                    if ($updates !== []) {
                        DB::table('document_templates')->where('id', $template->id)->update($updates);
                    }
                }
            });
        });
    }

    public function down(): void
    {
        // Do not restore personal details into dynamic or edited templates.
    }
};
