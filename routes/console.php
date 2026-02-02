<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule auto-sync optical power setiap 10 menit
// DISABLED: Aktifkan di server production dengan Control Panel
// Schedule::command('onu:sync-power')->everyTenMinutes()->withoutOverlapping();
