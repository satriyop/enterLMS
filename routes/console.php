<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:purge-trashed')->daily();

// Send enrollment deadline reminders at 8 AM daily
// Checks for deadlines in 3 days and 1 day
Schedule::command('app:send-deadline-reminders --days=3,1')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();
