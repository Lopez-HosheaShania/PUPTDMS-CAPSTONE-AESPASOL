<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;

class CancelElapsedAppointmentsCommand extends Command
{
    protected $signature = 'appointments:cancel-elapsed';

    protected $description = 'Cancel upcoming or rescheduled appointments whose scheduled date has elapsed.';

    public function handle(): int
    {
        $cancelled = Appointment::cancelElapsedScheduledAppointments();

        $this->info("Elapsed appointments cancelled: {$cancelled}");

        return self::SUCCESS;
    }
}
