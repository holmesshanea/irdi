<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('messaging:restore-expired')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Daily reminder; idempotent via last_reminded_at. Requires Laravel scheduler to run.
Schedule::command('permissions:remind-expiring')->dailyAt('09:00');
