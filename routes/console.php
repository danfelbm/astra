<?php

use App\Jobs\Core\CleanExpiredOTPsJob;
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
