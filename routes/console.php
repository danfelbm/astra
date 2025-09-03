<?php

use Modules\Core\Jobs\CleanExpiredOTPsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar limpieza automática de OTPs expirados
Schedule::job(new CleanExpiredOTPsJob)
    ->everyFiveMinutes()
    ->name('clean-expired-otps')
    ->withoutOverlapping();

// Programar limpieza automática de sesiones de urna expiradas
Schedule::command('urna:cleanup')
    ->everyFiveMinutes()
    ->name('clean-expired-urna-sessions')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/urna-cleanup.log'));
