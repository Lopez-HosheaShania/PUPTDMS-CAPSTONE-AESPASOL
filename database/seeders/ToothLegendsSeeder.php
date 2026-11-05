<?php

namespace Database\Seeders;

use App\Models\ToothLegend;
use Illuminate\Database\Seeder;

class ToothLegendsSeeder extends Seeder
{
    public function run(): void
    {
        $legends = [
            ['code' => 'D',  'description' => 'Decayed (Caries indicated for filling)', 'category' => 'Condition'],
            ['code' => 'RC', 'description' => 'Recurrent Caries', 'category' => 'Condition'],
            ['code' => 'M',  'description' => 'Missing due to caries', 'category' => 'Condition'],
            ['code' => 'F',  'description' => 'Filled', 'category' => 'Condition'],
            ['code' => 'I',  'description' => 'Caries indicated for extraction', 'category' => 'Condition'],
            ['code' => 'RF', 'description' => 'Root fragment', 'category' => 'Condition'],
            ['code' => 'MO', 'description' => 'Missing due to other causes', 'category' => 'Condition'],
            ['code' => 'IM', 'description' => 'Impacted tooth', 'category' => 'Condition'],

            ['code' => 'J',  'description' => 'Jacket crown', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'A',  'description' => 'Amalgam filling', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'AB', 'description' => 'Abutment', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'P',  'description' => 'Pontic', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'IN', 'description' => 'Inlay', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'LC', 'description' => 'Light cure composite', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'TP', 'description' => 'Temporary Pasta', 'category' => 'Restoration and Prosthetics'],
            ['code' => 'RM', 'description' => 'Removable denture', 'category' => 'Restoration and Prosthetics'],

            ['code' => 'X',  'description' => 'Extraction due to caries', 'category' => 'Surgery'],
            ['code' => 'XO', 'description' => 'Extraction due to other causes', 'category' => 'Surgery'],
            ['code' => '✓',  'description' => 'Present teeth', 'category' => 'Surgery'],
            ['code' => 'CM', 'description' => 'Congenitally missing', 'category' => 'Surgery'],
            ['code' => 'SP', 'description' => 'Supernumerary', 'category' => 'Surgery'],
        ];

        foreach ($legends as $legend) {
            ToothLegend::updateOrCreate(
                ['code' => $legend['code']],
                $legend
            );
        }
    }
}
