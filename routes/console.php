<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('flss:sync-academic-year')->dailyAt('01:00');
Schedule::command('appointments:send-follow-up-reminders')->dailyAt('08:00');
Schedule::command('inventory:send-expiration-alerts')->dailyAt('08:15');
Schedule::command('appointments:auto-end-dentist-duty')
    ->dailyAt('20:00')
    ->timezone(config('app.timezone', 'Asia/Manila'))
    ->withoutOverlapping();
Schedule::command('dentists:deactivate-expired')->everyFifteenMinutes();
