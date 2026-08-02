<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(
    'app:release-expired-bookings'
)->everyMinute();

Schedule::command('restaurant:reminders')->dailyAt('08:00');

Schedule::command('app:release-expired-guest-holds')->everyMinute();
