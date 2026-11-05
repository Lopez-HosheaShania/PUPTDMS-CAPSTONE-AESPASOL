<?php

namespace App\Console\Commands;

use App\Services\DentistDutyService;
use Illuminate\Console\Command;

class AutoEndDentistDutyCommand extends Command
{
    protected $signature = 'appointments:auto-end-dentist-duty';

    protected $description = 'Automatically clock out active dentists at 8:00 PM Asia/Manila and cancel their remaining same-day appointments.';

    public function handle(DentistDutyService $service): int
    {
        $summary = $service->autoClockOutActiveDentists();

        if (! $summary['ran']) {
            $this->info('Skipped automatic dentist duty end before 8:00 PM Asia/Manila.');

            return self::SUCCESS;
        }

        $this->info('Dentists automatically clocked out: ' . $summary['dentists_clocked_out']);
        $this->info('Appointments automatically cancelled: ' . $summary['appointments_cancelled']);
        $this->info('Patient notifications created: ' . $summary['notifications_created']);

        return self::SUCCESS;
    }
}
