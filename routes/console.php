<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

if (config('queue.default') !== 'sync') {
    Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()->withoutOverlapping();
}
Schedule::command('alerts:deadlines')->hourly()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
