<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run the task generator on the first day of every month at midnight (00:00)
Schedule::command('tasks:generate-monthly')->monthlyOn(1, '00:00');

// Run the deadline checker every morning to notify clients
Schedule::command('tasks:check-deadlines')->dailyAt('08:00');
