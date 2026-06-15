<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceType;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'Oral Check-Up',
                'description' => 'Routine exam • Consultation',
            ],
            [
                'old_name' => 'Dental Cleaning',
                'name' => 'Oral Prophylaxis',
                'description' => 'Preventive cleaning • Plaque and calculus removal',
            ],
            [
                'name' => 'Restoration & Prosthesis',
                'description' => 'Fillings • Crowns • Bridges',
            ],
            [
                'name' => 'Dental Surgery',
                'description' => 'Extraction • Implants',
            ],
        ];

        foreach ($defaults as $service) {
            $matchingNames = array_values(array_filter([
                $service['old_name'] ?? null,
                $service['name'],
            ]));

            $serviceType = ServiceType::whereIn('name', $matchingNames)->first();

            if (!$serviceType) {
                $serviceType = new ServiceType();
            }

            $serviceType->name = $service['name'];
            $serviceType->description = $service['description'];
            $serviceType->is_active_for_booking = true;
            $serviceType->is_default = true;
            $serviceType->save();
        }
    }
}