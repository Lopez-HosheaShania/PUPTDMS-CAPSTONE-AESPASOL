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
            ['code' => 'D',  'description' => 'Decayed (Caries indicated for Filling)', 'category' => 'Condition'],
            ['code' => 'RC', 'description' => 'Recurrent Caries', 'category' => 'Condition'],
            ['code' => 'M',  'description' => 'Missing due to Caries', 'category' => 'Condition'],
            ['code' => 'F',  'description' => 'Filled', 'category' => 'Condition'],
            ['code' => 'I',  'description' => 'Caries Indicated for Extraction', 'category' => 'Condition'],
            ['code' => 'RF', 'description' => 'Root Fragment', 'category' => 'Condition'],
            ['code' => 'MO', 'description' => 'Missing due to Other Causes', 'category' => 'Condition'],
            ['code' => 'IM', 'description' => 'Impacted Tooth', 'category' => 'Condition'],
            ['code' => 'J',  'description' => 'Jacket Crown', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'A',  'description' => 'Amalgam Filling', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'AB', 'description' => 'Abutment', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'P',  'description' => 'Pontic', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'IN', 'description' => 'Inlay', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'LC', 'description' => 'Light Cure Composite', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'TP', 'description' => 'Temporary Pasta', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'RM', 'description' => 'Removable Denture', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'X',  'description' => 'Extraction due to Caries', 'category' => 'Surgery'],
            ['code' => 'XO', 'description' => 'Extraction due to Other Causes', 'category' => 'Surgery'],
            ['code' => '✓',  'description' => 'Present Teeth', 'category' => 'Surgery'],
            ['code' => 'CM', 'description' => 'Congenitally Missing', 'category' => 'Surgery'],
            ['code' => 'SP', 'description' => 'Supernumerary', 'category' => 'Surgery'],
        ];

        foreach ($legends as $legend) {
            ToothLegend::updateOrCreate(
                ['code' => $legend['code']],
                ['description' => $legend['description'], 'category' => $legend['category']]
            );
        }
    }
}
