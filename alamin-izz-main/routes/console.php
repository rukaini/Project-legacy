<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('edutrain:close-expired-trainings')->dailyAt('00:01');
Schedule::command('edutrain:send-deadline-reminders')->dailyAt('07:00');
