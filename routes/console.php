<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Polling aduan baru dari Lapor.go.id tiap 15 menit (saat scheduler jalan)
Schedule::command('lapor:poll --minutes=20')
    ->everyFifteenMinutes()
    ->name('lapor-poll')
    ->withoutOverlapping();

// PDP retention scrub setiap hari jam 02:00 WIB
Schedule::command('pdp:scrub --force')
    ->dailyAt('02:00')
    ->name('pdp-scrub')
    ->withoutOverlapping()
    ->emailOutputOnFailure(env('PDP_REPORT_EMAIL'));
