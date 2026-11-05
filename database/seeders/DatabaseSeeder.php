<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DentalHistoryConditionSeeder::class,
            MedicalHistoryQuestionSeeder::class,
            DiseaseSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            ServiceTypeSeeder::class,
            AcademicPeriodSeeder::class,
            ClinicScheduleSeeder::class,
            DocumentTemplateFieldSeeder::class,
            DefaultDocumentTemplateSeeder::class,
            MonthlyDentalReportTemplateSeeder::class,
            ToothLegendsSeeder::class,
            AdminLocalAccountSeeder::class,
        ]);
    }
}
