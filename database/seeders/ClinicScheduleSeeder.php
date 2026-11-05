<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClinicSchedule;

class ClinicScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'days_label'  => 'Mon – Fri',
                'days'        => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'status'      => 'open',
                'open_time'   => '09:00:00',
                'close_time'  => '17:00:00',
                'break_time'  => null,
                'max_slots'   => 5,
                'slot_duration_minutes' => 60,
                'notes'       => 'Default clinic hours',
                'is_active'   => true,
            ],
            [
                'days_label'  => 'Sat',
                'days'        => ['Sat'],
                'status'      => 'closed',
                'open_time'   => null,
                'close_time'  => null,
                'break_time'  => null,
                'max_slots'   => 0,
                'slot_duration_minutes' => 60,
                'notes'       => 'Weekend closure',
                'is_active'   => true,
            ],
            [
                'days_label'  => 'Sun',
                'days'        => ['Sun'],
                'status'      => 'closed',
                'open_time'   => null,
                'close_time'  => null,
                'break_time'  => null,
                'max_slots'   => 0,
                'slot_duration_minutes' => 60,
                'notes'       => 'Weekend closure',
                'is_active'   => true,
            ],
        ];

        foreach ($defaults as $rule) {
            ClinicSchedule::updateOrCreate(
                ['days_label' => $rule['days_label']],
                $rule
            );
        }
    }
}
