<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('app:purge-expired-items')->everyMinute();

Schedule::command('app:purge-expired-items')->daily();

Schedule::command('reservation:purge-expired-reservation')->everyMinute();