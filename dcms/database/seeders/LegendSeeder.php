<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Legend;
use App\Models\ToothLegend;

class LegendSeeder extends Seeder
{
    public function run(): void
    {
        $legends = [
            ['code' => 'D',  'description' => 'Decayed', 'category' => 'Condition'],
            ['code' => 'M',  'description' => 'Missing due to Caries', 'category' => 'Missing / Extraction'],
            ['code' => 'F',  'description' => 'Filled', 'category' => 'Restoration'],
            ['code' => 'I',  'description' => 'Indicated for Extraction', 'category' => 'Missing / Extraction'],
            ['code' => 'RF', 'description' => 'Root Fragment', 'category' => 'Condition'],
            ['code' => 'MO', 'description' => 'Missing due to Other Causes', 'category' => 'Missing / Extraction'],
            ['code' => 'IM', 'description' => 'Impacted Tooth', 'category' => 'Condition'],
            ['code' => 'J',  'description' => 'Jacket Crown', 'category' => 'Restoration'],
            ['code' => 'A',  'description' => 'Amalgam Filling', 'category' => 'Restoration'],
            ['code' => 'AB', 'description' => 'Abutment', 'category' => 'Prosthetics & Support'],
            ['code' => 'P',  'description' => 'Pontic', 'category' => 'Prosthetics & Support'],
            ['code' => 'IN', 'description' => 'Inlay', 'category' => 'Restoration'],
            ['code' => 'LC', 'description' => 'Light Cure Composite', 'category' => 'Restoration'],
            ['code' => 'RM', 'description' => 'Removable Denture', 'category' => 'Prosthetics & Support'],
            ['code' => 'X',  'description' => 'Extraction due to Caries', 'category' => 'Missing / Extraction'],
            ['code' => 'XO', 'description' => 'Extraction due to Other Causes', 'category' => 'Missing / Extraction'],
            ['code' => 'PT', 'description' => 'Present Tooth', 'category' => 'Condition'],
            ['code' => 'CM', 'description' => 'Congenitally Missing', 'category' => 'Condition'],
            ['code' => 'SP', 'description' => 'Supernumerary', 'category' => 'Condition'],
        ];

        foreach ($legends as $legend) {
            ToothLegend::updateOrCreate(
                ['code' => $legend['code']],
                ['description' => $legend['description'], 'category' => $legend['category']]
            );
        }
    }
}